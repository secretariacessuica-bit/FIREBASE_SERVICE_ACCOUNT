<?php
// LIMA Solutions ERP - Mobile Authentication Helper V1
// Intercepta e valida cabeçalhos HTTP "Authorization: Bearer <token>"
// Se um token válido for fornecido, inicializa a sessão simulada para compatibilidade.

function checkMobileAuth($pdo) {
    // 1. Verificar cabeçalho Authorization
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
        $token = $matches[1];
        $tokenHash = hash('sha256', $token);

        try {
            $stmt = $pdo->prepare("SELECT * FROM mobile_tokens WHERE token_hash = :hash AND revoked_at IS NULL AND expires_at > NOW() LIMIT 1");
            $stmt->execute(['hash' => $tokenHash]);
            $mobileToken = $stmt->fetch();

            if ($mobileToken) {
                // Atualizar last_used_at de forma assíncrona/segura
                $update = $pdo->prepare("UPDATE mobile_tokens SET last_used_at = NOW() WHERE id = :id");
                $update->execute(['id' => $mobileToken['id']]);

                // Obter role do utilizador
                $stmtUser = $pdo->prepare("SELECT role, active FROM users WHERE id = :uid LIMIT 1");
                $stmtUser->execute(['uid' => $mobileToken['user_id']]);
                $user = $stmtUser->fetch();

                if ($user && $user['active'] == 1) {
                    // Popular sessão em memória para compatibilidade com helper de permissões
                    $_SESSION['user_id'] = (int)$mobileToken['user_id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['company_id'] = (int)$mobileToken['company_id'];
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log('[LIMA][mobile_auth] Auth execution error: ' . $e->getMessage());
        }
    }

    // 2. Fallback para sessão Web tradicional (Cookie)
    if (isset($_SESSION['user_id']) && isset($_SESSION['company_id'])) {
        return true;
    }

    return false;
}

function sendMobileError($code, $message, $httpStatus = 401) {
    http_response_code($httpStatus);
    echo json_encode([
        'success' => false,
        'data' => null,
        'error' => [
            'code' => $code,
            'message' => $message
        ]
    ]);
    exit();
}

function sendMobileSuccess($data = []) {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'error' => null
    ]);
    exit();
}
