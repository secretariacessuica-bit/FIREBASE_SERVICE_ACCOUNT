<?php
// LIMA Solutions ERP - Marketplace Moderation API
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../helpers/MarketplaceHelper.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = getActiveCompanyId();
if (!$companyId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Aucune entreprise active sélectionnée.'
    ]);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'viewer';

// Security check: Only super_admin, admin, manager can access moderation API
if (!in_array($userRole, ['super_admin', 'admin', 'manager'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Accès interdit. Seuls les administrateurs et managers peuvent modérer.'
    ]);
    exit();
}

// Enforce module activation for the company
enforceModuleAccess('marketplace', $userRole, $companyId, 'view', $pdo);

$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch all items for the active company with filters
if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT i.*, c.name as category_name, cl.name as client_name, cl.email as client_email,
            (SELECT COUNT(*) FROM marketplace_interests WHERE item_id = i.id) as interests_count
            FROM marketplace_items i
            JOIN marketplace_categories c ON i.category_id = c.id
            JOIN clients cl ON i.client_id = cl.id
            WHERE i.company_id = :cid
            ORDER BY i.created_at DESC");
        $stmt->execute(['cid' => $companyId]);
        $items = $stmt->fetchAll();

        // Attach safe photo downloads links
        foreach ($items as &$it) {
            $stmtPhotos = $pdo->prepare("SELECT id FROM marketplace_photos WHERE item_id = :item_id");
            $stmtPhotos->execute(['item_id' => $it['id']]);
            $rawPhotos = $stmtPhotos->fetchAll();

            $it['photos'] = [];
            foreach ($rawPhotos as $p) {
                $it['photos'][] = "/api/v1/marketplace/items.php?action=download&photo_id=" . $p['id'];
            }
        }

        echo json_encode(['success' => true, 'items' => $items]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// POST: Moderate item status (Approve, Reject, Archive)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    // Standardized CSRF Protection
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

    $itemId = (int)($input['item_id'] ?? 0);
    $action = trim($input['action'] ?? ''); // Approve, Reject, Archive
    $rejectionReason = trim($input['rejection_reason'] ?? '');

    if (!$itemId || !in_array($action, ['Approve', 'Reject', 'Archive'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Paramètres invalides. Action doit être Approve, Reject ou Archive.']);
        exit();
    }

    if ($action === 'Reject' && empty($rejectionReason)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Le motif de rejet est obligatoire.']);
        exit();
    }

    // Verify item belongs to this company and fetch client details for CRM integration
    $stmtVerify = $pdo->prepare("
        SELECT i.id, i.status, i.title, i.description, i.company_id, i.client_id, i.request_delivery, i.request_storage,
               cl.name as client_name, cl.email as client_email, cl.phone as client_phone
        FROM marketplace_items i
        JOIN clients cl ON i.client_id = cl.id
        WHERE i.id = :id AND i.company_id = :cid
        LIMIT 1
    ");
    $stmtVerify->execute(['id' => $itemId, 'cid' => $companyId]);
    $item = $stmtVerify->fetch();

    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Annonce introuvable.']);
        exit();
    }

    // Determine target status
    $newStatus = 'Pending';
    if ($action === 'Approve') {
        $newStatus = 'Approved';
    } elseif ($action === 'Reject') {
        $newStatus = 'Rejected';
    } elseif ($action === 'Archive') {
        $newStatus = 'Archived';
    }

    try {
        $pdo->beginTransaction();

        // Update item status and rejection reason (if rejected, otherwise clear it or keep for history)
        if ($newStatus === 'Rejected') {
            $stmtUpdate = $pdo->prepare("UPDATE marketplace_items SET status = :status, rejection_reason = :reason WHERE id = :id");
            $stmtUpdate->execute(['status' => $newStatus, 'reason' => $rejectionReason, 'id' => $itemId]);
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE marketplace_items SET status = :status WHERE id = :id");
            $stmtUpdate->execute(['status' => $newStatus, 'id' => $itemId]);
        }

        // Spawn CRM Leads automatically if the item is approved
        if ($newStatus === 'Approved') {
            
            // Trigger Marketplace Demands Matching
            try {
                MarketplaceHelper::matchDemandsAndNotify($pdo, $itemId, $companyId);
            } catch (Exception $ex) {
                error_log("Failed to match marketplace demands: " . $ex->getMessage());
            }

            require_once '../../../modules/crm/model/Lead.php';
            $leadModel = new Lead($pdo);

            // 1. Delivery request lead
            if ((int)$item['request_delivery'] === 1) {
                // Deduplicate check: check if lead already exists for this item + tag
                $stmtCheck = $pdo->prepare("SELECT id FROM crm_leads WHERE company_id = :cid AND source_entity_type = 'marketplace_item' AND source_entity_id = :item_id AND tags = 'Marketplace Delivery' LIMIT 1");
                $stmtCheck->execute(['cid' => $companyId, 'item_id' => $itemId]);
                if (!$stmtCheck->fetch()) {
                    // Create CRM lead
                    $notes = "Demande d'offre de livraison pour l'objet Marketplace : \"" . $item['title'] . "\" (ID #" . $itemId . ").\nDescription: " . $item['description'];
                    $stmtLead = $pdo->prepare("INSERT INTO crm_leads (company_id, name, email, phone, status, notes, tags, utm_source, source_entity_type, source_entity_id) 
                        VALUES (:cid, :name, :email, :phone, 'New', :notes, 'Marketplace Delivery', 'Marketplace', 'marketplace_item', :item_id)");
                    $stmtLead->execute([
                        'cid' => $companyId,
                        'name' => $item['client_name'],
                        'email' => $item['client_email'],
                        'phone' => !empty($item['client_phone']) ? $item['client_phone'] : null,
                        'notes' => $notes,
                        'item_id' => $itemId
                    ]);
                    $leadId = $pdo->lastInsertId();
                    // Update score
                    try {
                        $leadModel->updateLeadScore($leadId);
                    } catch (Exception $ex) {
                        error_log("Failed to calculate lead score: " . $ex->getMessage());
                    }
                }
            }

            // 2. Storage request lead
            if ((int)$item['request_storage'] === 1) {
                // Deduplicate check: check if lead already exists for this item + tag
                $stmtCheck = $pdo->prepare("SELECT id FROM crm_leads WHERE company_id = :cid AND source_entity_type = 'marketplace_item' AND source_entity_id = :item_id AND tags = 'Marketplace Storage' LIMIT 1");
                $stmtCheck->execute(['cid' => $companyId, 'item_id' => $itemId]);
                if (!$stmtCheck->fetch()) {
                    // Create CRM lead
                    $notes = "Demande d'offre de stockage pour l'objet Marketplace : \"" . $item['title'] . "\" (ID #" . $itemId . ").\nDescription: " . $item['description'];
                    $stmtLead = $pdo->prepare("INSERT INTO crm_leads (company_id, name, email, phone, status, notes, tags, utm_source, source_entity_type, source_entity_id) 
                        VALUES (:cid, :name, :email, :phone, 'New', :notes, 'Marketplace Storage', 'Marketplace', 'marketplace_item', :item_id)");
                    $stmtLead->execute([
                        'cid' => $companyId,
                        'name' => $item['client_name'],
                        'email' => $item['client_email'],
                        'phone' => !empty($item['client_phone']) ? $item['client_phone'] : null,
                        'notes' => $notes,
                        'item_id' => $itemId
                    ]);
                    $leadId = $pdo->lastInsertId();
                    // Update score
                    try {
                        $leadModel->updateLeadScore($leadId);
                    } catch (Exception $ex) {
                        error_log("Failed to calculate lead score: " . $ex->getMessage());
                    }
                }
            }
        }

        // Record history/audit if any audit log helper or system exists
        if (file_exists('../../../admin/audit_helper.php')) {
            require_once '../../../admin/audit_helper.php';
            if (function_exists('logAuditAction')) {
                logAuditAction($companyId, $_SESSION['user_id'], 'Marketplace', "Modération annonce ID #$itemId - Action: $action. Novo estado: $newStatus. Motivo: $rejectionReason", $pdo);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Annonce mise à jour avec succès.', 'new_status' => $newStatus]);
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
exit();
