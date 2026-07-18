<?php
// LIMA Solutions ERP - Quotes API V1

require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../admin/sequences_helper.php';
require_once '../../../admin/timeline_helper.php';
require_once '../../../helpers/PdfTemplate.php';
require_once '../../../helpers/EmailHelper.php';
require_once '../../../modules/quotes/model/Quote.php';
require_once '../../../modules/quotes/controller/QuoteController.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = getActiveCompanyId();
if (!$companyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucune entreprise active sélectionnée.']);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce Module Access for 'quotes' (with fallback to 'invoices' handled inside helper)
enforceModuleAccess('quotes', $userRole, $companyId, 'view', $pdo);

$quoteModel = new Quote($pdo);
$controller = new QuoteController($quoteModel);

$method = $_SERVER['REQUEST_METHOD'];

// Standardized CSRF Protection Check for mutating requests
if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $clientCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionCsrfToken = $_SESSION['csrf_token'] ?? '';

    if (empty($sessionCsrfToken) || empty($clientCsrfToken) || !hash_equals($sessionCsrfToken, $clientCsrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Erreur de sécurité CSRF: Requête rejetée.']);
        exit();
    }
} else {
    $input = $_GET;
}

// GET requests: List, Search, View, PDF
if ($method === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 200) $limit = 50;
    $offset = ($page - 1) * $limit;

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $quote = $quoteModel->getById($id, $companyId);
        if (!$quote) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Devis non trouvé.']);
            exit();
        }

        // PDF Generation Trigger (supports ?pdf=1 or ?format=pdf)
        if ((isset($_GET['pdf']) && $_GET['pdf'] == 1) || (isset($_GET['format']) && $_GET['format'] === 'pdf')) {
            header('Content-Type: text/html; charset=utf-8');
            echo $quoteModel->renderPdf($id, $companyId);
            exit();
        }

        $items = $quoteModel->getItems($id, $companyId);
        echo json_encode([
            'success' => true,
            'message' => 'Devis chargé.',
            'data' => [
                'quote' => $quote,
                'items' => $items
            ]
        ]);
        exit();
    } else {
        $filters = [];
        if (isset($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }
        if (isset($_GET['status'])) {
            $filters['status'] = trim($_GET['status']);
        }

        $quotes = $quoteModel->getAll($companyId, $filters, $limit, $offset);
        $total = $quoteModel->getTotalCount($companyId, $filters);
        echo json_encode([
            'success' => true,
            'message' => 'Liste des devis.',
            'data' => [
                'quotes' => $quotes,
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

// Write mutation guards: Must have 'edit' permissions on 'quotes'
if (!hasModulePermission($userRole, 'quotes', 'edit', $pdo)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "Accès interdit: Droits d'écriture insuffisants."]);
    exit();
}

// POST mutation router
if ($method === 'POST') {
    $action = $input['action'] ?? '';

    // Status alteration
    if ($action === 'status') {
        $id = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        
        $validStatuses = ['Draft', 'Sent', 'Accepted', 'Rejected', 'Expired'];
        if (!$id || !in_array($status, $validStatuses)) {
            http_response_code(422); // Required 422 error code for invalid status
            echo json_encode(['success' => false, 'message' => 'Statut invalide.']);
            exit();
        }

        $oldQuote = $quoteModel->getById($id, $companyId);
        if (!$oldQuote) {
            http_response_code(444);
            echo json_encode(['success' => false, 'message' => 'Devis non trouvé.']);
            exit();
        }

        $result = $quoteModel->changeStatus($id, $status, $companyId, $_SESSION['user_id']);
        if ($result) {
            $newQuote = $quoteModel->getById($id, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'quotes', 'quotes', $id, 'Changed quote status to ' . $status, $pdo, $oldQuote, $newQuote, $reqId);
            logEntityEvent($companyId, 'quotes', 'quotes', $id, strtolower($status), $_SESSION['user_id'], "Statut modifié: " . $status, $pdo);

            // Trigger email notification if quote is accepted
            if ($status === 'Accepted') {
                $stmtCompany = $pdo->prepare("SELECT email FROM companies WHERE id = :cid LIMIT 1");
                $stmtCompany->execute(['cid' => $companyId]);
                $companyEmail = $stmtCompany->fetchColumn() ?: 'info@limasolutions.ch';

                EmailHelper::sendTemplateEmail($companyId, $companyEmail, 'quote_accepted_alert', [
                    'quote_number' => $newQuote['quote_number'],
                    'client_name' => $newQuote['client_name'],
                    'total_amount' => number_format($newQuote['total'], 2, '.', ''),
                    'accepted_date' => date('d.m.Y H:i'),
                    'quote_id' => $id
                ], $pdo);
            }

            echo json_encode(['success' => true, 'message' => 'Statut du devis mis à jour.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la mise à jour du statut.']);
        }
        exit();
    }

    // Soft delete
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID manquant.']);
            exit();
        }

        $oldQuote = $quoteModel->getById($id, $companyId);
        if (!$oldQuote) {
            http_response_code(444);
            echo json_encode(['success' => false, 'message' => 'Devis non trouvé.']);
            exit();
        }

        $result = $quoteModel->softDelete($id, $companyId, $_SESSION['user_id']);
        if ($result) {
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'quotes', 'quotes', $id, 'Deleted quote ID ' . $id, $pdo, $oldQuote, null, $reqId);
            logEntityEvent($companyId, 'quotes', 'quotes', $id, 'deleted', $_SESSION['user_id'], "Devis supprimé (Soft Delete)", $pdo);

            echo json_encode(['success' => true, 'message' => 'Devis supprimé avec succès.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la suppression.']);
        }
        exit();
    }

    // Update quote (fallback for POST requests acting as update)
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID manquant.']);
            exit();
        }

        $cleanData = $controller->sanitize($input);
        $items = $input['items'] ?? [];

        $errors = $controller->validate($cleanData, $items);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }

        $oldQuote = $quoteModel->getById($id, $companyId);
        if (!$oldQuote) {
            http_response_code(444);
            echo json_encode(['success' => false, 'message' => 'Devis non trouvé.']);
            exit();
        }
        
        $totals = $controller->calculateTotals($items, $cleanData['discount_percent'] ?? 0.00, $companyId, $pdo, $cleanData['currency'] ?? 'CHF');
        $data = array_merge($cleanData, $totals);
        $result = $quoteModel->update($id, $data, $totals['items'], $companyId, $_SESSION['user_id']);

        if ($result) {
            $newQuote = $quoteModel->getById($id, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'quotes', 'quotes', $id, 'Updated quote details', $pdo, $oldQuote, $newQuote, $reqId);
            logEntityEvent($companyId, 'quotes', 'quotes', $id, 'updated', $_SESSION['user_id'], "Devis mis à jour par l'utilisateur.", $pdo);

            echo json_encode(['success' => true, 'message' => 'Devis mis à jour avec succès.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la mise à jour.']);
        }
        exit();
    }

    // Default: Create Quote
    $cleanData = $controller->sanitize($input);
    $items = $input['items'] ?? [];

    $errors = $controller->validate($cleanData, $items);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    // Generate next unique quote sequence code
    $quoteNumber = generateSequence($companyId, 'Q', $pdo);
    
    // Recalculate server-side totals
    $totals = $controller->calculateTotals($items, $cleanData['discount_percent'] ?? 0.00, $companyId, $pdo, $cleanData['currency'] ?? 'CHF');

    $data = array_merge($cleanData, $totals);
    $data['quote_number'] = $quoteNumber;
    $data['created_by'] = $_SESSION['user_id'];
    $data['company_id'] = $companyId;

    try {
        $quoteId = $quoteModel->create($data, $totals['items'], $companyId, $_SESSION['user_id']);
        if ($quoteId) {
            $newQuote = $quoteModel->getById($quoteId, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'quotes', 'quotes', $quoteId, 'Created new quote: ' . $quoteNumber, $pdo, null, $newQuote, $reqId);
            logEntityEvent($companyId, 'quotes', 'quotes', $quoteId, 'created', $_SESSION['user_id'], "Orçamento criado com código: " . $quoteNumber, $pdo);

            // Send notification to the client
            $stmtClient = $pdo->prepare("SELECT email, name FROM clients WHERE id = :id LIMIT 1");
            $stmtClient->execute(['id' => $cleanData['client_id']]);
            $clientInfo = $stmtClient->fetch();
            if ($clientInfo && !empty($clientInfo['email'])) {
                EmailHelper::sendTemplateEmail($companyId, $clientInfo['email'], 'new_quote_alert', [
                    'quote_number' => $quoteNumber,
                    'client_name' => $clientInfo['name'],
                    'valid_until' => date('d.m.Y', strtotime($cleanData['valid_until'])),
                    'total_amount' => number_format($data['total'], 2, '.', ''),
                    'vat_amount' => number_format($data['tax_total'], 2, '.', ''),
                    'currency' => $data['currency'] ?? 'CHF'
                ], $pdo);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Devis créé avec succès.',
                'data' => [
                    'id' => $quoteId,
                    'quote_number' => $quoteNumber
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la création du devis.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    }
    exit();
}

// PUT HTTP Method handler
if ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit();
    }

    $cleanData = $controller->sanitize($input);
    $items = $input['items'] ?? [];

    $errors = $controller->validate($cleanData, $items);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    $oldQuote = $quoteModel->getById($id, $companyId);
    if (!$oldQuote) {
        http_response_code(444);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé.']);
        exit();
    }
    
    $totals = $controller->calculateTotals($items, $cleanData['discount_percent'] ?? 0.00, $companyId, $pdo, $cleanData['currency'] ?? 'CHF');
    $data = array_merge($cleanData, $totals);
    $result = $quoteModel->update($id, $data, $totals['items'], $companyId, $_SESSION['user_id']);

    if ($result) {
        $newQuote = $quoteModel->getById($id, $companyId);
        $reqId = bin2hex(random_bytes(16));
        logActivity($_SESSION['user_id'], $companyId, 'quotes', 'quotes', $id, 'Updated quote details', $pdo, $oldQuote, $newQuote, $reqId);
        logEntityEvent($companyId, 'quotes', 'quotes', $id, 'updated', $_SESSION['user_id'], "Devis mis à jour par l'utilisateur.", $pdo);

        echo json_encode(['success' => true, 'message' => 'Devis mis à jour avec succès.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Échec de la mise à jour.']);
    }
    exit();
}

// DELETE HTTP Method handler
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit();
    }

    $oldQuote = $quoteModel->getById($id, $companyId);
    if (!$oldQuote) {
        http_response_code(444);
        echo json_encode(['success' => false, 'message' => 'Devis non trouvé.']);
        exit();
    }

    $result = $quoteModel->softDelete($id, $companyId, $_SESSION['user_id']);
    if ($result) {
        $reqId = bin2hex(random_bytes(16));
        logActivity($_SESSION['user_id'], $companyId, 'quotes', 'quotes', $id, 'Deleted quote ID ' . $id, $pdo, $oldQuote, null, $reqId);
        logEntityEvent($companyId, 'quotes', 'quotes', $id, 'deleted', $_SESSION['user_id'], "Devis supprimé (Soft Delete)", $pdo);

        echo json_encode(['success' => true, 'message' => 'Devis supprimé avec succès.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Échec de la suppression.']);
    }
    exit();
}
