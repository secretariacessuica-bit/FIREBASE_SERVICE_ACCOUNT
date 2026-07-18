<?php
// LIMA Solutions ERP - Marketplace Reservations Queue API
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../../helpers/EmailHelper.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'item_id manquant.']);
        exit();
    }

    try {
        // Expire old reservations first
        expireOldReservations($pdo, $itemId);

        $stmt = $pdo->prepare("SELECT id, name, position, status, expires_at 
                               FROM marketplace_reservations 
                               WHERE item_id = :item_id AND status IN ('active', 'waiting') 
                               ORDER BY position ASC");
        $stmt->execute(['item_id' => $itemId]);
        $queue = $stmt->fetchAll();

        echo json_encode(['success' => true, 'queue' => $queue]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
    }
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $itemId = isset($input['item_id']) ? (int)$input['item_id'] : 0;
    $name = isset($input['name']) ? trim($input['name']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : null;

    if (!$itemId || empty($name) || empty($email)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous os campos obrigatórios.']);
        exit();
    }

    try {
        // Expire old reservations first
        expireOldReservations($pdo, $itemId);

        // Check if user already in queue
        $stmt = $pdo->prepare("SELECT id FROM marketplace_reservations WHERE item_id = :item_id AND email = :email AND status IN ('active', 'waiting') LIMIT 1");
        $stmt->execute(['item_id' => $itemId, 'email' => $email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Vous êtes déjà dans la file d\'attente de cet objet.']);
            exit();
        }

        // Get company ID of the item
        $stmtItem = $pdo->prepare("SELECT company_id, title FROM marketplace_items WHERE id = :id AND status = 'Approved' LIMIT 1");
        $stmtItem->execute(['id' => $itemId]);
        $item = $stmtItem->fetch();
        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Annonce introuvable ou non disponible.']);
            exit();
        }
        $companyId = (int)$item['company_id'];

        // Get current max position
        $stmtPos = $pdo->prepare("SELECT MAX(position) FROM marketplace_reservations WHERE item_id = :item_id AND status IN ('active', 'waiting')");
        $stmtPos->execute(['item_id' => $itemId]);
        $maxPos = (int)$stmtPos->fetchColumn();
        $nextPos = $maxPos + 1;

        $status = 'waiting';
        $expiresAt = null;

        if ($nextPos === 1) {
            $status = 'active';
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        }

        // Determine client ID if logged in
        $clientUserId = $_SESSION['client_user_id'] ?? null;
        $clientId = $clientUserId ? ($_SESSION['client_id'] ?? null) : null;

        $stmtIns = $pdo->prepare("INSERT INTO marketplace_reservations (item_id, client_id, name, email, phone, position, status, expires_at)
                                  VALUES (:item_id, :client_id, :name, :email, :phone, :pos, :status, :exp)");
        $stmtIns->execute([
            'item_id' => $itemId,
            'client_id' => $clientId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'pos' => $nextPos,
            'status' => $status,
            'exp' => $expiresAt
        ]);

        if ($nextPos === 1) {
            // Notify User #1
            $subject = "Votre réservation de l'objet : " . $item['title'];
            $body = "Bonjour " . htmlspecialchars($name) . ",\n\n";
            $body .= "Bonne nouvelle ! L'objet \"" . htmlspecialchars($item['title']) . "\" est maintenant réservé pour vous pendant 24 heures.\n\n";
            $body .= "Veuillez contacter le vendeur ou finaliser l'achat via votre Espace Client avant l'expiration.\n\n";
            $body .= "Merci de faire confiance à LIMA Solutions.";
            
            try {
                EmailHelper::sendSimulatedEmail($companyId, $email, $subject, $body, null, $pdo);
            } catch (Exception $e) {
                // Squelch email logs
            }
        }

        echo json_encode([
            'success' => true,
            'message' => $nextPos === 1 ? 'Objet réservé avec succès ! Vous avez 24h.' : 'Vous avez été ajouté à la file d\'attente (Position #' . $nextPos . ').'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la réservation.']);
    }
    exit();
}

if ($method === 'DELETE') {
    // Release / Cancel reservation
    $resId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$resId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM marketplace_reservations WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $resId]);
        $res = $stmt->fetch();

        if (!$res) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Réservation introuvable.']);
            exit();
        }

        $itemId = (int)$res['item_id'];
        $wasActive = ($res['status'] === 'active');

        // Mark current as expired/cancelled
        $stmtDel = $pdo->prepare("UPDATE marketplace_reservations SET status = 'expired' WHERE id = :id");
        $stmtDel->execute(['id' => $resId]);

        // Reorder remaining positions
        $stmtRem = $pdo->prepare("SELECT * FROM marketplace_reservations WHERE item_id = :item_id AND status IN ('active', 'waiting') ORDER BY position ASC");
        $stmtRem->execute(['item_id' => $itemId]);
        $remaining = $stmtRem->fetchAll();

        $pos = 1;
        foreach ($remaining as $r) {
            $status = $r['status'];
            $expiresAt = $r['expires_at'];

            if ($pos === 1 && $wasActive) {
                $status = 'active';
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

                // Notify new position #1
                $stmtItem = $pdo->prepare("SELECT company_id, title FROM marketplace_items WHERE id = :id LIMIT 1");
                $stmtItem->execute(['id' => $itemId]);
                $item = $stmtItem->fetch();

                if ($item) {
                    $subject = "Bonne nouvelle ! L'objet " . $item['title'] . " é agora seu!";
                    $body = "Bonjour " . htmlspecialchars($r['name']) . ",\n\n";
                    $body .= "L'objet \"" . htmlspecialchars($item['title']) . "\" est maintenant réservé pour vous pendant 24 heures.\n\n";
                    $body .= "Merci de finaliser votre transaction au plus vite.";
                    
                    try {
                        EmailHelper::sendSimulatedEmail((int)$item['company_id'], $r['email'], $subject, $body, null, $pdo);
                    } catch (Exception $e) {}
                }
            }

            $stmtUpd = $pdo->prepare("UPDATE marketplace_reservations SET position = :pos, status = :status, expires_at = :exp WHERE id = :id");
            $stmtUpd->execute([
                'pos' => $pos,
                'status' => $status,
                'exp' => $expiresAt,
                'id' => $r['id']
            ]);
            $pos++;
        }

        echo json_encode(['success' => true, 'message' => 'Réservation annulée/libérée avec succès.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la libération.']);
    }
    exit();
}

function expireOldReservations($pdo, $itemId) {
    // Expire active reservations older than 24h
    $stmt = $pdo->prepare("SELECT id FROM marketplace_reservations 
                           WHERE item_id = :item_id 
                           AND status = 'active' 
                           AND expires_at < NOW()");
    $stmt->execute(['item_id' => $itemId]);
    $expired = $stmt->fetchAll();

    foreach ($expired as $exp) {
        // We simulate a cancel logic for each expired one to auto-promote
        $stmtDel = $pdo->prepare("UPDATE marketplace_reservations SET status = 'expired' WHERE id = :id");
        $stmtDel->execute(['id' => $exp['id']]);

        // Reorder remaining
        $stmtRem = $pdo->prepare("SELECT * FROM marketplace_reservations WHERE item_id = :item_id AND status IN ('active', 'waiting') ORDER BY position ASC");
        $stmtRem->execute(['item_id' => $itemId]);
        $remaining = $stmtRem->fetchAll();

        $pos = 1;
        foreach ($remaining as $r) {
            $status = $r['status'];
            $expiresAt = $r['expires_at'];

            if ($pos === 1) {
                $status = 'active';
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

                // Notify new position #1
                $stmtItem = $pdo->prepare("SELECT company_id, title FROM marketplace_items WHERE id = :id LIMIT 1");
                $stmtItem->execute(['id' => $itemId]);
                $item = $stmtItem->fetch();

                if ($item) {
                    $subject = "Votre tour est arrivé ! Objet réservé : " . $item['title'];
                    $body = "Bonjour " . htmlspecialchars($r['name']) . ",\n\n";
                    $body .= "L'objet \"" . htmlspecialchars($item['title']) . "\" est maintenant réservé pour você pendant 24 heures.\n\n";
                    $body .= "Merci de finaliser votre transaction au mais vite.";
                    
                    try {
                        EmailHelper::sendSimulatedEmail((int)$item['company_id'], $r['email'], $subject, $body, null, $pdo);
                    } catch (Exception $e) {}
                }
            }

            $stmtUpd = $pdo->prepare("UPDATE marketplace_reservations SET position = :pos, status = :status, expires_at = :exp WHERE id = :id");
            $stmtUpd->execute([
                'pos' => $pos,
                'status' => $status,
                'exp' => $expiresAt,
                'id' => $r['id']
            ]);
            $pos++;
        }
    }
}
