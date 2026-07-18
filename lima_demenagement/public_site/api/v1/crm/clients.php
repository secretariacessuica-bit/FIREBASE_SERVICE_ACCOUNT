<?php
// LIMA Solutions ERP - CRM Clients API V1

require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../admin/sequences_helper.php';
require_once '../../../admin/timeline_helper.php';
require_once '../../../helpers/EmailHelper.php';
require_once '../../../modules/crm/model/Client.php';
require_once '../../../modules/crm/controller/ClientController.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = getActiveCompanyId();
if (!$companyId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Aucune entreprise active selectionnée.'
    ]);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'viewer';

// 1. Enforce Module Access (CRM)
enforceModuleAccess('crm', $userRole, $companyId, 'view', $pdo);

$clientModel = new Client($pdo);
$controller = new ClientController($clientModel);

$method = $_SERVER['REQUEST_METHOD'];

// Standardized CSRF Protection Check for mutating requests
if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Read CSRF Token from Header or Body
    $clientCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionCsrfToken = $_SESSION['csrf_token'] ?? '';

    if (empty($sessionCsrfToken) || empty($clientCsrfToken) || !hash_equals($sessionCsrfToken, $clientCsrfToken)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur de sécurité CSRF: Requête rejetée.'
        ]);
        exit();
    }
} else {
    // For GET requests, we might parse query parameters
    $input = $_GET;
}

// GET Requests: List with pagination, Search with pagination, View single profile
if ($method === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_client_user') {
        $clientId = (int)($_GET['client_id'] ?? 0);
        if (!$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID client manquant.']);
            exit();
        }
        $stmt = $pdo->prepare("SELECT id, name, email, active, last_login, created_at FROM client_users WHERE client_id = :client_id AND company_id = :company_id LIMIT 1");
        $stmt->execute(['client_id' => $clientId, 'company_id' => $companyId]);
        $clientUser = $stmt->fetch();
        echo json_encode([
            'success' => true,
            'data' => ['client_user' => $clientUser ?: null]
        ]);
        exit();
    }
    if (isset($_GET['action']) && $_GET['action'] === 'get_client_messages') {
        $clientId = (int)($_GET['client_id'] ?? 0);
        if (!$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID client manquant.']);
            exit();
        }
        $stmtRead = $pdo->prepare("UPDATE client_messages SET read_at = NOW() WHERE client_id = :client_id AND company_id = :company_id AND sender_type = 'client' AND read_at IS NULL");
        $stmtRead->execute(['client_id' => $clientId, 'company_id' => $companyId]);

        $stmt = $pdo->prepare("SELECT * FROM client_messages WHERE client_id = :client_id AND company_id = :company_id ORDER BY created_at ASC");
        $stmt->execute(['client_id' => $clientId, 'company_id' => $companyId]);
        $messages = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => ['messages' => $messages]
        ]);
        exit();
    }
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 200) $limit = 50;
    $offset = ($page - 1) * $limit;

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $client = $clientModel->getById($id, $companyId);
        if (!$client) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Client non trouvé.'
            ]);
            exit();
        }
        echo json_encode([
            'success' => true,
            'message' => 'Profil client chargé.',
            'data' => ['client' => $client]
        ]);
        exit();
    } elseif (isset($_GET['search'])) {
        $term = trim($_GET['search']);
        $clients = $clientModel->search($term, $companyId, $limit, $offset);
        $total = $clientModel->getSearchCount($term, $companyId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Résultats de la recherche.',
            'data' => [
                'clients' => $clients,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total_records' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]
        ]);
        exit();
    } else {
        $clients = $clientModel->getAll($companyId, $limit, $offset);
        $total = $clientModel->getTotalCount($companyId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Liste des clients active.',
            'data' => [
                'clients' => $clients,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total_records' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]
        ]);
        exit();
    }
}

// Mutation Guard: Requires 'edit' permissions
if (!hasModulePermission($userRole, 'crm', 'edit', $pdo)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => "Accès interdit: Droits d'écriture insuffisants pour le module CRM."
    ]);
    exit();
}

// POST: Create or Legacy wrapper for Update/Delete
if ($method === 'POST') {
    $action = $input['action'] ?? '';

    if ($action === 'save_client_user') {
        $clientId = (int)($input['client_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if (!$clientId || empty($name) || empty($email)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Paramètres requis manquants.']);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Format de courriel invalide.']);
            exit();
        }

        // Validate client exists in this company
        $client = $clientModel->getById($clientId, $companyId);
        if (!$client) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Client non trouvé.']);
            exit();
        }

        // Check if email already registered for another client in this company
        $stmtEmail = $pdo->prepare("SELECT id FROM client_users WHERE email = :email AND company_id = :company_id AND client_id != :client_id LIMIT 1");
        $stmtEmail->execute(['email' => $email, 'company_id' => $companyId, 'client_id' => $clientId]);
        if ($stmtEmail->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Cette adresse courriel est déjà utilisée.']);
            exit();
        }

        // Check if client user exists
        $stmtCheck = $pdo->prepare("SELECT * FROM client_users WHERE client_id = :client_id AND company_id = :company_id LIMIT 1");
        $stmtCheck->execute(['client_id' => $clientId, 'company_id' => $companyId]);
        $existing = $stmtCheck->fetch();

        $pdo->beginTransaction();
        try {
            $reqId = bin2hex(random_bytes(16));
            if ($existing) {
                // Update
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUpdate = $pdo->prepare("UPDATE client_users SET name = :name, email = :email, password_hash = :hash WHERE id = :id");
                    $stmtUpdate->execute(['name' => $name, 'email' => $email, 'hash' => $hash, 'id' => $existing['id']]);
                } else {
                    $stmtUpdate = $pdo->prepare("UPDATE client_users SET name = :name, email = :email WHERE id = :id");
                    $stmtUpdate->execute(['name' => $name, 'email' => $email, 'id' => $existing['id']]);
                }
                
                $stmtCheck->execute(['client_id' => $clientId, 'company_id' => $companyId]);
                $updated = $stmtCheck->fetch();
                
                logActivity($_SESSION['user_id'], $companyId, 'crm', 'client_users', $existing['id'], 'Updated client portal user details', $pdo, $existing, $updated, $reqId);
                logEntityEvent($companyId, 'crm', 'clients', $clientId, 'client_user_updated', $_SESSION['user_id'], "Accès portail client mis à jour pour " . $email, $pdo);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Accès portail client mis à jour.']);
            } else {
                // Insert
                if (empty($password)) {
                    throw new Exception("Le mot de passe est obligatoire pour la création de compte.");
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare("INSERT INTO client_users (company_id, client_id, name, email, password_hash, active) VALUES (:company_id, :client_id, :name, :email, :hash, 1)");
                $stmtInsert->execute(['company_id' => $companyId, 'client_id' => $clientId, 'name' => $name, 'email' => $email, 'hash' => $hash]);
                $newId = $pdo->lastInsertId();

                $stmtCheck->execute(['client_id' => $clientId, 'company_id' => $companyId]);
                $created = $stmtCheck->fetch();

                logActivity($_SESSION['user_id'], $companyId, 'crm', 'client_users', $newId, 'Created client portal user', $pdo, null, $created, $reqId);
                logEntityEvent($companyId, 'crm', 'clients', $clientId, 'client_user_created', $_SESSION['user_id'], "Accès portail client créé para " . $email, $pdo);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Accès portail client créé avec succès.']);
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'toggle_client_user') {
        $clientId = (int)($input['client_id'] ?? 0);
        $active = isset($input['active']) ? (int)$input['active'] : 1;

        if (!$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID client manquant.']);
            exit();
        }

        $stmtCheck = $pdo->prepare("SELECT * FROM client_users WHERE client_id = :client_id AND company_id = :company_id LIMIT 1");
        $stmtCheck->execute(['client_id' => $clientId, 'company_id' => $companyId]);
        $existing = $stmtCheck->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Compte portail client introuvable.']);
            exit();
        }

        $pdo->beginTransaction();
        try {
            $stmtUpdate = $pdo->prepare("UPDATE client_users SET active = :active WHERE id = :id");
            $stmtUpdate->execute(['active' => $active, 'id' => $existing['id']]);

            $stmtCheck->execute(['client_id' => $clientId, 'company_id' => $companyId]);
            $updated = $stmtCheck->fetch();

            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'crm', 'client_users', $existing['id'], ($active ? 'Enabled' : 'Disabled') . ' client portal access', $pdo, $existing, $updated, $reqId);
            logEntityEvent($companyId, 'crm', 'clients', $clientId, 'client_user_status_changed', $_SESSION['user_id'], "Accès portail " . ($active ? 'activé' : 'désactivé'), $pdo);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour com sucesso.']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'send_client_message') {
        $clientId = (int)($input['client_id'] ?? 0);
        $message = trim($input['message'] ?? '');

        if (!$clientId || empty($message)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Paramètres requis manquants.']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO client_messages (company_id, client_id, sender_type, sender_id, message) VALUES (:company_id, :client_id, 'staff', :sender_id, :message)");
            $stmt->execute([
                'company_id' => $companyId,
                'client_id' => $clientId,
                'sender_id' => $_SESSION['user_id'],
                'message' => $message
            ]);

            // Notify client by email
            $stmtClient = $pdo->prepare("SELECT name, email FROM clients WHERE id = :id LIMIT 1");
            $stmtClient->execute(['id' => $clientId]);
            $clientInfo = $stmtClient->fetch();

            if ($clientInfo && !empty($clientInfo['email'])) {
                $stmtComp = $pdo->prepare("SELECT name FROM companies WHERE id = :id LIMIT 1");
                $stmtComp->execute(['id' => $companyId]);
                $companyName = $stmtComp->fetchColumn() ?: 'Lima Déménagement';

                EmailHelper::sendTemplateEmail($companyId, $clientInfo['email'], 'new_message_alert', [
                    'sender_name' => $companyName,
                    'recipient_name' => $clientInfo['name'],
                    'message_excerpt' => mb_substr($message, 0, 100),
                    'portal_link' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'limasolutions.ch') . '/portal/index.php'
                ], $pdo);
            }

            echo json_encode(['success' => true, 'message' => 'Message envoyé.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID client manquant.']);
            exit();
        }
        $oldClient = $clientModel->getById($id, $companyId);
        $result = $clientModel->deactivate($id, $companyId);
        if ($result) {
            // Log to Audit Trail with before data snapshot
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'crm', 'clients', $id, 'Deactivated client ID ' . $id, $pdo, $oldClient, null, $reqId);
            
            // Log to Entity Timeline
            logEntityEvent($companyId, 'crm', 'clients', $id, 'deactivated', $_SESSION['user_id'], "Client désactivé (Soft Delete)", $pdo);

            echo json_encode([
                'success' => true,
                'message' => 'Client désactivé avec succès.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la désactivation du client.']);
        }
        exit();
    } elseif ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID client manquant.']);
            exit();
        }
        $cleanData = $controller->sanitize($input);
        $errors = $controller->validate($cleanData, true);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }
        $oldClient = $clientModel->getById($id, $companyId);
        $cleanData = $controller->sanitize($input);
        $errors = $controller->validate($cleanData, true);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }
        $result = $clientModel->update($id, $cleanData, $companyId);
        if ($result) {
            $newClient = $clientModel->getById($id, $companyId);
            // Log to Audit Trail with snapshots and request ID
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'crm', 'clients', $id, 'Updated client details for ID ' . $id, $pdo, $oldClient, $newClient, $reqId);

            // Log to Entity Timeline
            logEntityEvent($companyId, 'crm', 'clients', $id, 'updated', $_SESSION['user_id'], "Coordonnées du client mises à jour.", $pdo);

            echo json_encode([
                'success' => true,
                'message' => 'Client mis à jour avec succès.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la mise à jour du client.']);
        }
        exit();
    } else {
        // Create client
        $cleanData = $controller->sanitize($input);
        
        // Auto generate customer code if empty (transaction-safe sequence generator)
        if (empty($cleanData['customer_code'])) {
            $cleanData['customer_code'] = generateSequence($companyId, 'CLI', $pdo);
        }

        $errors = $controller->validate($cleanData);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }

        $cleanData['company_id'] = $companyId;
        $clientId = $clientModel->create($cleanData);
        
        if ($clientId) {
            $newClient = $clientModel->getById($clientId, $companyId);
            // Log to Audit Trail with snapshots and request ID
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'crm', 'clients', $clientId, 'Created new client: ' . $cleanData['customer_code'], $pdo, null, $newClient, $reqId);

            // Log to Entity Timeline
            logEntityEvent($companyId, 'crm', 'clients', $clientId, 'created', $_SESSION['user_id'], "Nouveau dossier client créé avec code " . $cleanData['customer_code'], $pdo);

            echo json_encode([
                'success' => true,
                'message' => 'Client créé avec succès.',
                'data' => [
                    'id' => $clientId,
                    'customer_code' => $cleanData['customer_code']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la création du client.']);
        }
        exit();
    }
}
