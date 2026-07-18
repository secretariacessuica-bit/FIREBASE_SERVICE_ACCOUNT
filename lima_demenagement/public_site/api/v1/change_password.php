<?php
// LIMA Solutions ERP - Change Password V1
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = isset($input['current_password']) ? $input['current_password'] : '';
$newPassword = isset($input['new_password']) ? $input['new_password'] : '';

if (!$currentPassword || !$newPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios ausentes.']);
    exit();
}

if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres.']);
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if ($user && password_verify($currentPassword, $user['password_hash'])) {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $update->execute([
            'hash' => $newHash,
            'id' => $userId
        ]);

        // Insert log in Audit Trail
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (:uid, 'Change Password', :ip, :ua)");
        $logStmt->execute([
            'uid' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?: 'Unknown'
        ]);

        echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A senha atual está incorreta.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar: ' . $e->getMessage()]);
}
exit();
