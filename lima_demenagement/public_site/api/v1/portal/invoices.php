<?php
// LIMA Solutions ERP - Client Portal Invoices API
require_once '../config.php';
require_once '../../../modules/invoices/model/Invoice.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['client_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit();
}

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

$invoiceModel = new Invoice($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $invoice = $invoiceModel->getById($id, $companyId);
        if (!$invoice || $invoice['client_id'] != $clientId) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
            exit();
        }

        // Render PDF if requested
        if ((isset($_GET['pdf']) && $_GET['pdf'] == 1) || (isset($_GET['format']) && $_GET['format'] === 'pdf')) {
            header('Content-Type: text/html; charset=utf-8');
            echo $invoiceModel->renderPdf($id, $companyId);
            exit();
        }

        $items = $invoiceModel->getItems($id, $companyId);
        echo json_encode([
            'success' => true,
            'data' => [
                'invoice' => $invoice,
                'items' => $items
            ]
        ]);
        exit();
    } else {
        // List invoices for this client
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL ORDER BY created_at DESC");
        $stmt->execute(['client_id' => $clientId, 'company_id' => $companyId]);
        $invoices = $stmt->fetchAll();
        echo json_encode([
            'success' => true,
            'data' => [
                'invoices' => $invoices
            ]
        ]);
        exit();
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
exit();
