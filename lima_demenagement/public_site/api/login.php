<?php
// LIMA Solutions Platform - Login API endpoint
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit();
}

// Support both form-data and raw JSON input
$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : (isset($_POST['email']) ? trim($_POST['email']) : '');
$password = isset($input['password']) ? $_input['password'] : (isset($_POST['password']) ? $_POST['password'] : '');

// Handle missing fields using variable fallback
if (!$email || !$password) {
    // If input was decoded as JSON, use it, otherwise fallback
    $password = isset($input['password']) ? $input['password'] : (isset($_POST['password']) ? $_POST['password'] : '');
    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios.']);
        exit();
    }
}

try {
    // Search user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerate session id to protect against session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'message' => 'Login efetuado com sucesso!',
            'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
