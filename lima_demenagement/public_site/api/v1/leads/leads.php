<?php
// LIMA Solutions ERP - CRM Leads API V1
// Supports both public submission and administrative management.

require_once '../config.php';
require_once '../../../helpers/EmailHelper.php';
require_once '../../../modules/crm/model/Lead.php';
require_once '../../../modules/crm/controller/LeadController.php';

header('Content-Type: application/json; charset=utf-8');

$leadModel = new Lead($pdo);
$controller = new LeadController($leadModel);

$method = $_SERVER['REQUEST_METHOD'];

// Parse incoming request body/inputs
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}
if ($method === 'GET') {
    $input = $_GET;
}

// ─── 1. PUBLIC ENDPOINT: Lead Submission (POST) ──────────────────────────────
if ($method === 'POST' && (!isset($input['action']) || $input['action'] !== 'convert')) {
    // Check if this is a public submission (i.e. no admin session is active or requesting public insert)
    // Public requests do not carry action=convert, they just submit form data
    $isAdminRequest = isset($_SESSION['user_id']) && isset($input['admin_action']);

    if (!$isAdminRequest) {
        // A. Honeypot check for bots (hidden field fax_number_alt)
        if (!empty($input['fax_number_alt'])) {
            // Silently accept request but do nothing to deceive the spammer
            $controller->sendJSONSuccess([], "Votre demande d'offre a été soumise avec succès (honeypot).");
        }

        // B. Rate Limit Check by IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        // Parse reverse-proxy headers if present
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
        }
        
        if (!$controller->checkRateLimit($ip, $pdo)) {
            $controller->sendJSONError("Trop de requêtes soumises. Veuillez réessayer plus tard.", 429);
        }

        // C. Company ID validation and sanitization
        $companyId = isset($input['company_id']) ? (int)$input['company_id'] : 1;
        try {
            $stmtComp = $pdo->query("SELECT id FROM companies WHERE active = 1");
            $allowedCompanies = $stmtComp->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array($companyId, $allowedCompanies)) {
                if (!empty($allowedCompanies)) {
                    $companyId = (int)$allowedCompanies[0];
                } else {
                    $companyId = 1;
                }
            }
        } catch (Exception $e) {
            $companyId = 1;
        }

        // D. Sanitize and Validate
        $cleanData = $controller->sanitize($input);
        $cleanData['company_id'] = $companyId;
        $cleanData['ip_address'] = $ip;
        
        // Populate Referer URL if missing
        if (empty($cleanData['referer_url'])) {
            $cleanData['referer_url'] = $_SERVER['HTTP_REFERER'] ?? null;
        }

        $errors = $controller->validate($cleanData);
        if (!empty($errors)) {
            $controller->sendJSONError(implode(' ', $errors), 422);
        }

        // E. Insert Lead
        try {
            $leadId = $leadModel->create($cleanData);
            if (!$leadId) {
                $controller->sendJSONError("Échec de la création de la lead.", 500);
            }

            // F. Trigger Simulated Emails using templates
            // Fetch company details to notify the staff
            $stmtCompany = $pdo->prepare("SELECT email, name FROM companies WHERE id = :cid LIMIT 1");
            $stmtCompany->execute(['cid' => $companyId]);
            $company = $stmtCompany->fetch();
            $companyEmail = $company['email'] ?? 'info@limasolutions.ch';
            $companyName = $company['name'] ?? 'Lima Déménagement';

            $replacements = [
                'lead_id' => $leadId,
                'name' => $cleanData['name'],
                'email' => $cleanData['email'],
                'phone' => !empty($cleanData['phone']) ? $cleanData['phone'] : '-',
                'service_date' => !empty($cleanData['service_date']) ? $cleanData['service_date'] : 'À convenir',
                'volume_m3' => !empty($cleanData['volume_m3']) ? $cleanData['volume_m3'] : '-',
                'origin_address' => !empty($cleanData['origin_address']) ? $cleanData['origin_address'] : '-',
                'destination_address' => !empty($cleanData['destination_address']) ? $cleanData['destination_address'] : '-',
                'notes' => !empty($cleanData['notes']) ? $cleanData['notes'] : '-',
                'utm_source' => !empty($cleanData['utm_source']) ? $cleanData['utm_source'] : '-',
                'utm_medium' => !empty($cleanData['utm_medium']) ? $cleanData['utm_medium'] : '-',
                'utm_campaign' => !empty($cleanData['utm_campaign']) ? $cleanData['utm_campaign'] : '-',
                'referer_url' => !empty($cleanData['referer_url']) ? $cleanData['referer_url'] : '-',
                'ip_address' => $ip,
                'company_name' => $companyName
            ];

            // Send lead confirmation to client
            EmailHelper::sendTemplateEmail($companyId, $cleanData['email'], 'lead_confirmation', $replacements, $pdo);

            // Send internal lead alert to staff
            EmailHelper::sendTemplateEmail($companyId, $companyEmail, 'internal_lead_alert', $replacements, $pdo);

            $controller->sendJSONSuccess([
                'id' => $leadId,
                'message' => "Votre demande d'offre a été soumise avec succès."
            ], "Demande d'offre enregistrée.");

        } catch (Exception $e) {
            $controller->sendJSONError("Erreur interne du serveur lors de la création de la lead: " . $e->getMessage(), 500);
        }
    }
}

// ─── 2. PROTECTED ENDPOINTS: Administrative Actions (Requires Session) ──────

// Require session authorization checks
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
if (!$companyId) {
    $controller->sendJSONError("Aucune entreprise active sélectionnée.", 400);
}

$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce CRM module viewing access
enforceModuleAccess('crm', $userRole, $companyId, 'view', $pdo);

// GET Method: List and Details
if ($method === 'GET') {
    if (isset($input['id'])) {
        $id = (int)$input['id'];
        $lead = $leadModel->getById($id, $companyId);
        if (!$lead) {
            $controller->sendJSONError("Lead introuvable.", 404);
        }
        $controller->sendJSONSuccess(['lead' => $lead], "Lead chargée.");
    } else {
        $statusFilter = isset($input['status']) ? trim($input['status']) : null;
        $leads = $leadModel->getAll($companyId, $statusFilter);
        $controller->sendJSONSuccess(['leads' => $leads], "Liste des leads chargée.");
    }
}

// PUT Method: Update pipeline status (Mutating Request, requires CSRF check and EDIT permission)
if ($method === 'PUT') {
    enforceModuleAccess('crm', $userRole, $companyId, 'edit', $pdo);

    // CSRF Check
    $clientCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionCsrfToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionCsrfToken) || empty($clientCsrfToken) || !hash_equals($sessionCsrfToken, $clientCsrfToken)) {
        $controller->sendJSONError("Erreur de sécurité CSRF: Requête rejetée.", 403);
    }

    $id = (int)($input['id'] ?? 0);
    $status = trim($input['status'] ?? '');

    if (!$id || empty($status)) {
        $controller->sendJSONError("Paramètres requis manquants.", 422);
    }

    try {
        $oldLead = $leadModel->getById($id, $companyId);
        if (!$oldLead) {
            $controller->sendJSONError("Lead introuvable.", 404);
        }

        $result = $leadModel->updateStatus($id, $status, $companyId);
        if ($result) {
            try {
                $leadModel->updateLeadScore($id);
            } catch (Exception $ex) {
                error_log("Failed to update score on status change: " . $ex->getMessage());
            }
            $newLead = $leadModel->getById($id, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'crm', 'crm_leads', $id, "Updated lead status to $status", $pdo, $oldLead, $newLead, $reqId);

            // Trigger pipeline_status_change only if status actually changed
            if ($oldLead['status'] !== $status) {
                // Fetch company email
                $stmtCompany = $pdo->prepare("SELECT email FROM companies WHERE id = :cid LIMIT 1");
                $stmtCompany->execute(['cid' => $companyId]);
                $companyEmail = $stmtCompany->fetchColumn() ?: 'info@limasolutions.ch';

                EmailHelper::sendTemplateEmail($companyId, $companyEmail, 'pipeline_status_change', [
                    'lead_id' => $id,
                    'lead_name' => $oldLead['name'],
                    'old_status' => $oldLead['status'],
                    'new_status' => $status
                ], $pdo);
            }

            $controller->sendJSONSuccess([], "Statut de la lead mis à jour.");
        } else {
            $controller->sendJSONError("Échec de la mise à jour.", 500);
        }
    } catch (Exception $e) {
        $controller->sendJSONError("Erreur lors de la mise à jour: " . $e->getMessage(), 500);
    }
}

// POST Action=Convert Method (Mutating Request, requires CSRF check and EDIT permission)
if ($method === 'POST' && isset($input['action']) && $input['action'] === 'convert') {
    enforceModuleAccess('crm', $userRole, $companyId, 'edit', $pdo);

    // CSRF Check
    $clientCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionCsrfToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionCsrfToken) || empty($clientCsrfToken) || !hash_equals($sessionCsrfToken, $clientCsrfToken)) {
        $controller->sendJSONError("Erreur de segurança CSRF: Requête rejetée.", 403);
    }

    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        $controller->sendJSONError("ID de lead manquant.", 400);
    }

    try {
        $oldLead = $leadModel->getById($id, $companyId);
        if (!$oldLead) {
            $controller->sendJSONError("Lead introuvable.", 404);
        }
        if ($oldLead['converted_client_id']) {
            $controller->sendJSONError("Ce lead est déjà converti.", 409);
        }

        $result = $leadModel->convertToClient($id, $companyId, $_SESSION['user_id']);
        
        if ($result['success']) {
            try {
                $leadModel->updateLeadScore($id);
            } catch (Exception $ex) {
                error_log("Failed to update score on convert: " . $ex->getMessage());
            }
            $newLead = $leadModel->getById($id, $companyId);
            $reqId = bin2hex(random_bytes(16));
            
            logActivity($_SESSION['user_id'], $companyId, 'crm', 'crm_leads', $id, "Converted lead to client ID " . $result['client_id'], $pdo, $oldLead, $newLead, $reqId);
            
            // Send client_welcome and internal_conversion_alert emails
            // Fetch company details
            $stmtCompany = $pdo->prepare("SELECT email, name FROM companies WHERE id = :cid LIMIT 1");
            $stmtCompany->execute(['cid' => $companyId]);
            $company = $stmtCompany->fetch();
            $companyEmail = $company['email'] ?? 'info@limasolutions.ch';
            $companyName = $company['name'] ?? 'Lima Déménagement';

            // Get generated customer code
            $stmtClient = $pdo->prepare("SELECT customer_code FROM clients WHERE id = :client_id LIMIT 1");
            $stmtClient->execute(['client_id' => $result['client_id']]);
            $customerCode = $stmtClient->fetchColumn() ?: '-';

            // Welcome email to the client
            EmailHelper::sendTemplateEmail($companyId, $oldLead['email'], 'client_welcome', [
                'name' => $oldLead['name'],
                'customer_code' => $customerCode,
                'company_name' => $companyName
            ], $pdo);

            // Conversion alert email to staff
            EmailHelper::sendTemplateEmail($companyId, $companyEmail, 'internal_conversion_alert', [
                'lead_name' => $oldLead['name'],
                'lead_email' => $oldLead['email'],
                'customer_code' => $customerCode,
                'client_id' => $result['client_id'],
                'is_duplicate' => $result['is_duplicate'] ? 'Client existant (Doublon associé)' : 'Nouveau dossier client unique',
                'converted_by_user_id' => $_SESSION['user_id'] ?? 1
            ], $pdo);

            $msg = $result['is_duplicate'] 
                ? "Lead associado ao cliente duplicado existente."
                : "Lead converti en nouveau client avec succès.";

            $controller->sendJSONSuccess([
                'client_id' => $result['client_id'],
                'is_duplicate' => $result['is_duplicate']
            ], $msg);
        } else {
            $controller->sendJSONError("Échec de la conversion: " . $result['message'], 500);
        }
    } catch (Exception $e) {
        $controller->sendJSONError("Erreur interne lors de la conversion: " . $e->getMessage(), 500);
    }
}
