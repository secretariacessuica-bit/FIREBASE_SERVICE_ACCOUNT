<?php
// LIMA Solutions ERP - Marketplace Interests & Lead Capture API V1
require_once '../config.php';
require_once '../../../helpers/EmailHelper.php';
require_once '../../../helpers/ObservabilityHelper.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$itemId = (int)($input['item_id'] ?? 0);
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$message = trim($input['message'] ?? '');
$requestType = trim($input['request_type'] ?? 'buyer'); // buyer, delivery, storage, cleaning, moving

// Additional fields
$pickupCity = trim($input['pickup_city'] ?? '');
$deliveryCity = trim($input['delivery_city'] ?? '');
$storageDuration = trim($input['storage_duration'] ?? '');
$cleaningOpt = isset($input['cleaning_opt']) && ($input['cleaning_opt'] === true || $input['cleaning_opt'] == 1 || $input['cleaning_opt'] === 'true');

if (!$itemId || empty($name) || empty($email)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Veuillez saisir votre nom et adresse e-mail.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Format d\'adresse e-mail invalide.']);
    exit();
}

// ─── 1. Anti-Spam: Rate Limit Check by IP ────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($parts[0]);
}

try {
    $stmtLimit = $pdo->prepare("SELECT COUNT(*) FROM crm_leads WHERE ip_address = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmtLimit->execute(['ip' => $ip]);
    if ((int)$stmtLimit->fetchColumn() > 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Trop de requêtes soumises. Veuillez réessayer plus tard.']);
        exit();
    }

    // ─── 2. Fetch Item Details ────────────────────────────────────────────────
    $stmtItem = $pdo->prepare("SELECT i.*, c.email as seller_email, c.name as seller_name 
        FROM marketplace_items i
        JOIN clients c ON i.client_id = c.id
        WHERE i.id = :id AND i.status = 'Approved' LIMIT 1");
    $stmtItem->execute(['id' => $itemId]);
    $item = $stmtItem->fetch();

    if (!$item) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Annonce introuvable ou non disponible.']);
        exit();
    }

    $companyId = $item['company_id'];

    // Map request type to tags
    $tag = 'Marketplace Buyer';
    $logCategory = 'MARKETPLACE_INTEREST';
    if ($requestType === 'delivery') {
        $tag = 'Marketplace Delivery';
        $logCategory = 'MARKETPLACE_DELIVERY';
    } elseif ($requestType === 'moving') {
        $tag = 'Marketplace Moving';
        $logCategory = 'MARKETPLACE_MOVING';
    } elseif ($requestType === 'storage') {
        $tag = 'Marketplace Storage';
        $logCategory = 'MARKETPLACE_STORAGE';
    } elseif ($requestType === 'cleaning') {
        $tag = 'Marketplace Cleaning';
        $logCategory = 'MARKETPLACE_CLEANING';
    }

    // ─── 3. Anti-Spam: Duplicate Lead Protection ──────────────────────────────
    $stmtCheckLead = $pdo->prepare("
        SELECT id FROM crm_leads 
        WHERE company_id = :cid 
          AND email = :email 
          AND source_entity_type = 'marketplace_item' 
          AND source_entity_id = :item_id 
          AND tags = :tag 
        LIMIT 1
    ");
    $stmtCheckLead->execute([
        'cid' => $companyId,
        'email' => $email,
        'item_id' => $itemId,
        'tag' => $tag
    ]);
    if ($stmtCheckLead->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Votre demande a déjà été enregistrée pour cet objet.']);
        exit();
    }

    // Determine client ID if logged in
    $clientUserId = $_SESSION['client_user_id'] ?? null;
    $interestClientId = null;
    if ($clientUserId) {
        $interestClientId = $_SESSION['client_id'];
    }

    $pdo->beginTransaction();

    // ─── 4. Save to marketplace_interests ─────────────────────────────────────
    $stmt = $pdo->prepare("INSERT INTO marketplace_interests (item_id, client_id, name, email, phone, message) 
        VALUES (:item_id, :client_id, :name, :email, :phone, :msg)");
    $stmt->execute([
        'item_id' => $itemId,
        'client_id' => $interestClientId,
        'name' => $name,
        'email' => $email,
        'phone' => !empty($phone) ? $phone : null,
        'msg' => !empty($message) ? $message : null
    ]);

    // ─── 5. Create CRM Lead ───────────────────────────────────────────────────
    $crmDesc = "";
    if ($requestType === 'cleaning') {
        $crmDesc .= "Source: Marketplace\nType: Cleaning\nPriority: High\n";
    } elseif ($requestType === 'moving') {
        $crmDesc .= "Source: Marketplace\nType: Moving\nPriority: High\n";
    } else {
        $crmDesc .= "Source: Marketplace\n";
    }
    $crmDesc .= "Demande Marketplace pour l'objet : \"" . $item['title'] . "\" (ID #" . $item['id'] . ").\nMessage: " . $message;
    
    if ($requestType === 'delivery') {
        $crmDesc .= "\nVille de départ: " . $pickupCity . "\nVille d'arrivée: " . $deliveryCity;
    } elseif ($requestType === 'storage') {
        $crmDesc .= "\nDurée estimée de stockage: " . $storageDuration;
    }

    $stmtCrm = $pdo->prepare("
        INSERT INTO crm_leads (company_id, name, email, phone, status, notes, tags, utm_source, source_entity_type, source_entity_id, origin_address, destination_address, ip_address) 
        VALUES (:cid, :name, :email, :phone, 'New', :notes, :tag, 'Marketplace', 'marketplace_item', :item_id, :origin, :destination, :ip)
    ");
    $stmtCrm->execute([
        'cid' => $companyId,
        'name' => $name,
        'email' => $email,
        'phone' => !empty($phone) ? $phone : null,
        'notes' => $crmDesc,
        'tag' => $tag,
        'item_id' => $itemId,
        'origin' => ($requestType === 'delivery') ? $pickupCity : null,
        'destination' => ($requestType === 'delivery') ? $deliveryCity : null,
        'ip' => $ip
    ]);
    $leadId = $pdo->lastInsertId();

    // Recalculate lead score
    require_once '../../../modules/crm/model/Lead.php';
    $leadModel = new Lead($pdo);
    $leadModel->updateLeadScore($leadId);

    if ($requestType === 'cleaning' || $requestType === 'moving') {
        // Force high score to ensure "Priority: High" in CRM
        $pdo->prepare("UPDATE crm_leads SET lead_score = 90 WHERE id = :id")->execute(['id' => $leadId]);
    }

    // Handle optional cleaning checkbox (Cross-Sell)
    if ($cleaningOpt && $requestType !== 'cleaning') {
        $cleaningDesc = "Source: Marketplace\nType: Cleaning\nPriority: High\nDemande Marketplace de Nettoyage Fin de Bail complémentaire liée à l'objet : \"" . $item['title'] . "\" (ID #" . $item['id'] . ").\nMessage d'origine: " . $message;
        $stmtCrmCleaning = $pdo->prepare("
            INSERT INTO crm_leads (company_id, name, email, phone, status, notes, tags, utm_source, source_entity_type, source_entity_id, ip_address) 
            VALUES (:cid, :name, :email, :phone, 'New', :notes, 'Marketplace Cleaning', 'Marketplace', 'marketplace_item', :item_id, :ip)
        ");
        $stmtCrmCleaning->execute([
            'cid' => $companyId,
            'name' => $name,
            'email' => $email,
            'phone' => !empty($phone) ? $phone : null,
            'notes' => $cleaningDesc,
            'item_id' => $itemId,
            'ip' => $ip
        ]);
        $cleaningLeadId = $pdo->lastInsertId();
        
        $leadModel->updateLeadScore($cleaningLeadId);
        $pdo->prepare("UPDATE crm_leads SET lead_score = 90 WHERE id = :id")->execute(['id' => $cleaningLeadId]);
    }

    $pdo->commit();

    // ─── 6. Analytics Logging ─────────────────────────────────────────────────
    ObservabilityHelper::log(
        "Marketplace lead captured for item ID #$itemId (Type: $requestType)",
        $logCategory,
        'INFO',
        [
            'item_id' => $itemId,
            'lead_id' => $leadId,
            'request_type' => $requestType,
            'ip_address' => $ip
        ],
        $pdo
    );

    // ─── 7. Send Notification Email to Seller ─────────────────────────────────
    if (!empty($item['seller_email'])) {
        $subject = "[LIMA Marketplace] Nouveau message pour votre annonce : " . $item['title'];
        $body = "Bonjour " . htmlspecialchars($item['seller_name']) . ",\n\n" .
                "Un utilisateur a formulé une demande pour votre annonce \"" . htmlspecialchars($item['title']) . "\" (Type de demande: " . $tag . ").\n\n" .
                "Détails du demandeur :\n" .
                "Nom : " . htmlspecialchars($name) . "\n" .
                "E-mail : " . htmlspecialchars($email) . "\n" .
                "Téléphone : " . htmlspecialchars($phone ?: '-') . "\n" .
                "Message :\n" . htmlspecialchars($message) . "\n\n";

        if ($requestType === 'delivery') {
            $body .= "Détails de livraison :\n" .
                     "Départ : " . htmlspecialchars($pickupCity) . "\n" .
                     "Arrivée : " . htmlspecialchars($deliveryCity) . "\n\n";
        } elseif ($requestType === 'storage') {
            $body .= "Détails du stockage :\n" .
                     "Durée estimée : " . htmlspecialchars($storageDuration) . "\n\n";
        }

        if ($cleaningOpt) {
            $body .= "[Demande additionnelle de devis pour Nettoyage Fin de Bail incluse]\n\n";
        }

        $body .= "Merci d'utiliser LIMA Solutions ERP.\n" .
                 "L'équipe de support.";
        
        EmailHelper::send($item['seller_email'], $subject, $body, $companyId, $pdo);
    }

    echo json_encode(['success' => true, 'message' => 'Votre demande a été enregistrée avec succès.']);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
