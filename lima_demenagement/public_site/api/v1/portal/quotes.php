<?php
// LIMA Solutions ERP - Client Portal Quotes API
require_once '../config.php';
require_once '../../../modules/quotes/model/Quote.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['client_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit();
}

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

$quoteModel = new Quote($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $quote = $quoteModel->getById($id, $companyId);
        if (!$quote || $quote['client_id'] != $clientId) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Devis non trouvé.']);
            exit();
        }

        // Render PDF if requested
        if ((isset($_GET['pdf']) && $_GET['pdf'] == 1) || (isset($_GET['format']) && $_GET['format'] === 'pdf')) {
            header('Content-Type: text/html; charset=utf-8');
            echo $quoteModel->renderPdf($id, $companyId);
            exit();
        }

        $items = $quoteModel->getItems($id, $companyId);
        echo json_encode([
            'success' => true,
            'data' => [
                'quote' => $quote,
                'items' => $items
            ]
        ]);
        exit();
    } else {
        // List quotes for this client
        $stmt = $pdo->prepare("SELECT * FROM quotes WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL ORDER BY created_at DESC");
        $stmt->execute(['client_id' => $clientId, 'company_id' => $companyId]);
        $quotes = $stmt->fetchAll();
        echo json_encode([
            'success' => true,
            'data' => [
                'quotes' => $quotes
            ]
        ]);
        exit();
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? '';

    // Validate CSRF
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

    if ($action === 'accept') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID devis manquant.']);
            exit();
        }

        $quote = $quoteModel->getById($id, $companyId);
        if (!$quote || $quote['client_id'] != $clientId) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Devis non trouvé.']);
            exit();
        }

        if ($quote['status'] !== 'Sent') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ce devis ne peut pas être accepté (statut actuel: ' . $quote['status'] . ').']);
            exit();
        }

        $pdo->beginTransaction();
        try {
            $stmtUpdate = $pdo->prepare("UPDATE quotes SET status = 'Accepted' WHERE id = :id AND company_id = :company_id");
            $stmtUpdate->execute(['id' => $id, 'company_id' => $companyId]);

            $stmtCreator = $pdo->prepare("SELECT created_by FROM quotes WHERE id = :id LIMIT 1");
            $stmtCreator->execute(['id' => $id]);
            $adminId = $stmtCreator->fetchColumn() ?: 1;

            require_once '../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'quotes', 'quotes', $id, 'accepted', $adminId, "Devis accepté par le client via le portail.", $pdo);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Devis accepté avec succès.']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
exit();
