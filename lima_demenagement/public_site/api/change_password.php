<?php
// LIMA Solutions Platform - Change Password API Endpoint
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Session verification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado. Por favor, faça login.']);
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
    echo json_encode(['success' => false, 'message' => 'A senha atual e a nova senha são obrigatórias.']);
    exit();
}

if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres.']);
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    
    // Retrieve current hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if ($user && password_verify($currentPassword, $user['password_hash'])) {
        // Update hash
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $updateStmt->execute([
            'hash' => $newHash,
            'id' => $userId
        ]);

        echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A senha atual informada está incorreta.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno ao atualizar senha: ' . $e->getMessage()]);
}
