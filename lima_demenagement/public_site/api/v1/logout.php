<?php
// LIMA Solutions ERP - Logout V1 Endpoint
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'])) {
    try {
        // Insert Audit Log for Logout
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (:uid, 'Logout', :ip, :ua)");
        $logStmt->execute([
            'uid' => $_SESSION['user_id'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?: 'Unknown'
        ]);
    } catch (Exception $e) {
        // Fail silently to complete logout process
    }
}

// Clear Session variables
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Sessão encerrada com sucesso!'
]);
exit();
