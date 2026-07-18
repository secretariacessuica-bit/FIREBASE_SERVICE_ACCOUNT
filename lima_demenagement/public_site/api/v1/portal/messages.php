<?php
// LIMA Solutions ERP - Client Portal Messages API V1
require_once '../config.php';
require_once '../../../helpers/EmailHelper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['client_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit();
}

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 1. Mark incoming staff messages as read
    $stmtRead = $pdo->prepare("UPDATE client_messages SET read_at = NOW() WHERE client_id = :client_id AND company_id = :company_id AND sender_type = 'staff' AND read_at IS NULL");
    $stmtRead->execute(['client_id' => $clientId, 'company_id' => $companyId]);

    // 2. Fetch all messages
    $stmt = $pdo->prepare("SELECT * FROM client_messages WHERE client_id = :client_id AND company_id = :company_id ORDER BY created_at ASC");
    $stmt->execute(['client_id' => $clientId, 'company_id' => $companyId]);
    $messages = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'messages' => $messages
        ]
    ]);
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $message = trim($input['message'] ?? '');

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

    if (empty($message)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Le message ne peut pas être vide.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO client_messages (company_id, client_id, sender_type, sender_id, message) VALUES (:company_id, :client_id, 'client', :sender_id, :message)");
        $stmt->execute([
            'company_id' => $companyId,
            'client_id' => $clientId,
            'sender_id' => $_SESSION['client_user_id'],
            'message' => $message
        ]);

        // Notify staff by email
        $stmtClient = $pdo->prepare("SELECT name FROM clients WHERE id = :id LIMIT 1");
        $stmtClient->execute(['id' => $clientId]);
        $clientName = $stmtClient->fetchColumn() ?: 'Client';

        $stmtComp = $pdo->prepare("SELECT name, email FROM companies WHERE id = :id LIMIT 1");
        $stmtComp->execute(['id' => $companyId]);
        $compInfo = $stmtComp->fetch();
        $compEmail = $compInfo['email'] ?? 'info@limasolutions.ch';
        $compName = $compInfo['name'] ?? 'Lima Déménagement';

        EmailHelper::sendTemplateEmail($companyId, $compEmail, 'new_message_alert', [
            'sender_name' => $clientName,
            'recipient_name' => $compName,
            'message_excerpt' => mb_substr($message, 0, 100),
            'portal_link' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'limasolutions.ch') . '/admin/index.html#/crm/clients/' . $clientId
        ], $pdo);

        echo json_encode(['success' => true, 'message' => 'Message envoyé.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Action non supportée.']);
exit();
