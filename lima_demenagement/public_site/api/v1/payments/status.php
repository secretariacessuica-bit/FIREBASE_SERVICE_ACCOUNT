<?php
// LIMA Solutions ERP - Payment Transaction Status API
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

// Ensure user is logged in (either admin or portal client)
$userId = $_SESSION['user_id'] ?? null;
$clientUserId = $_SESSION['client_user_id'] ?? null;

if (!$userId && !$clientUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé. Veuillez vous connecter.']);
    exit();
}

$companyId = getActiveCompanyId();
if (!$companyId && $clientUserId) {
    $companyId = $_SESSION['company_id'] ?? null;
}

if (!$companyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contexte d\'entreprise manquant.']);
    exit();
}

$idempotencyKey = isset($_GET['key']) ? trim($_GET['key']) : '';
$transactionId = isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : null;

if (empty($idempotencyKey) && !$transactionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètre key ou transaction_id manquant.']);
    exit();
}

try {
    if ($transactionId) {
        $stmt = $pdo->prepare("SELECT pt.*, i.status AS invoice_status, i.balance_due FROM payment_transactions pt JOIN invoices i ON pt.invoice_id = i.id WHERE pt.id = :id AND pt.company_id = :company_id LIMIT 1");
        $stmt->execute(['id' => $transactionId, 'company_id' => $companyId]);
    } else {
        $stmt = $pdo->prepare("SELECT pt.*, i.status AS invoice_status, i.balance_due FROM payment_transactions pt JOIN invoices i ON pt.invoice_id = i.id WHERE pt.idempotency_key = :key AND pt.company_id = :company_id LIMIT 1");
        $stmt->execute(['key' => $idempotencyKey, 'company_id' => $companyId]);
    }
    
    $transaction = $stmt->fetch();

    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction introuvable.']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'transaction_id' => (int)$transaction['id'],
        'status' => $transaction['status'],
        'amount' => (float)$transaction['amount'],
        'currency' => $transaction['currency'],
        'invoice_id' => (int)$transaction['invoice_id'],
        'invoice_status' => $transaction['invoice_status'],
        'balance_due' => (float)$transaction['balance_due']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Exception in status endpoint: ' . $e->getMessage()]);
}
