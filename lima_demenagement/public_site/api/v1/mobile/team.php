<?php
// LIMA Solutions ERP - Mobile Team Endpoint V1
require_once '../config.php';
require_once 'auth_helper.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// POST login não requer autenticação prévia de token
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if (empty($action) && isset($input['action'])) {
    $action = $input['action'];
}

if ($method === 'POST' && $action === 'login') {
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $deviceName = trim($input['device_name'] ?? 'Mobile Device');
    $deviceUuid = trim($input['device_uuid'] ?? '');

    if (empty($email) || empty($password)) {
        sendMobileError('VALIDATION_ERROR', 'Email e password são obrigatórios.', 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND active = 1 LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Obter empresas associadas
            $stmtComp = $pdo->prepare("SELECT c.* FROM companies c 
                JOIN user_companies uc ON c.id = uc.company_id 
                WHERE uc.user_id = :user_id AND c.active = 1");
            $stmtComp->execute(['user_id' => $user['id']]);
            $companies = $stmtComp->fetchAll();

            if (empty($companies) && $user['role'] !== 'super_admin') {
                sendMobileError('FORBIDDEN', 'Utilizador não está associado a nenhuma empresa ativa.', 403);
            }

            $companyId = !empty($companies) ? $companies[0]['id'] : 1; // Default fallback

            // Gerar token Bearer de 64 caracteres legível
            $tokenRaw = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $tokenRaw);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Gravar token hash
            $ins = $pdo->prepare("INSERT INTO mobile_tokens (company_id, user_id, token_hash, device_name, device_uuid, expires_at) 
                VALUES (:cid, :uid, :hash, :dev, :uuid, :exp)");
            $ins->execute([
                'cid' => $companyId,
                'uid' => $user['id'],
                'hash' => $tokenHash,
                'dev' => $deviceName,
                'uuid' => $deviceUuid,
                'exp' => $expiresAt
            ]);

            sendMobileSuccess([
                'token' => $tokenRaw,
                'expires_at' => $expiresAt,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'company_id' => $companyId
                ]
            ]);
        } else {
            sendMobileError('UNAUTHORIZED', 'Credenciais inválidas.', 401);
        }
    } catch (Exception $e) {
        sendMobileError('SERVER_ERROR', 'Erro interno: ' . $e->getMessage(), 500);
    }
}

// Para qualquer outra operação, autenticação é obrigatória
if (!checkMobileAuth($pdo)) {
    sendMobileError('UNAUTHORIZED', 'Sessão expirada ou token inválido.');
}

$userId = $_SESSION['user_id'];
$companyId = $_SESSION['company_id'];

if ($method === 'GET') {
    if ($action === 'profile') {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, role, last_login FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            sendMobileSuccess(['profile' => $user]);
        } catch (Exception $e) {
            sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    if ($action === 'assignments') {
        try {
            // Projetos onde o staff está associado
            $stmt = $pdo->prepare("SELECT p.*, oa.status AS assignment_status, oa.assigned_at
                FROM projects p
                JOIN operational_assignments oa ON p.id = oa.project_id
                WHERE oa.user_id = :uid AND oa.company_id = :cid AND p.deleted_at IS NULL
                ORDER BY oa.assigned_at DESC");
            $stmt->execute(['uid' => $userId, 'cid' => $companyId]);
            $assignments = $stmt->fetchAll();
            sendMobileSuccess(['assignments' => $assignments]);
        } catch (Exception $e) {
            sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    sendMobileError('BAD_REQUEST', 'Ação inválida.', 400);
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido.']]);
