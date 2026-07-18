<?php
// LIMA Solutions ERP - Login V1 Endpoint
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios.']);
    exit();
}

try {
    // Check user in database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND active = 1 LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Fetch companies associated with this user
        $stmtComp = $pdo->prepare("SELECT c.* FROM companies c 
            JOIN user_companies uc ON c.id = uc.company_id 
            WHERE uc.user_id = :user_id AND c.active = 1");
        $stmtComp->execute(['user_id' => $user['id']]);
        $companies = $stmtComp->fetchAll();

        if (empty($companies) && $user['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Usuário não está associado a nenhuma empresa ativa.']);
            exit();
        }

        // Initialize session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Set default active company assignment
        if (!empty($companies)) {
            $_SESSION['company_id'] = $companies[0]['id'];
            $_SESSION['company_name'] = $companies[0]['name'];
        } else {
            $_SESSION['company_id'] = null;
            $_SESSION['company_name'] = 'Todas as Empresas';
        }

        // Log last login timestamp
        $updateLogin = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $updateLogin->execute(['id' => $user['id']]);

        // Insert log in Audit Trail
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (:uid, 'Login', :ip, :ua)");
        $logStmt->execute([
            'uid' => $user['id'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?: 'Unknown'
        ]);

        // Update active users metric count
        require_once '../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::updateActiveUsers($pdo);

        echo json_encode([
            'success' => true,
            'message' => 'Login efetuado com sucesso!',
            'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'companies' => $companies
            ]
        ]);
    } else {
        http_response_code(401);
        require_once '../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::log("Failed login attempt for email: $email", 'FAILED_LOGIN', 'WARNING', ['email' => $email], $pdo);
        
        // Write a log to activity_logs table for database metrics tracking
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (NULL, 'FailedLogin', :ip, :ua)");
        $logStmt->execute([
            'ip' => $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?: 'Unknown'
        ]);

        echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
