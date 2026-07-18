<?php
// LIMA Solutions ERP - Client Portal Authentication API V1
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

// Basic Session Rate Limit
if ($action === 'login') {
    $now = time();
    if (isset($_SESSION['last_login_attempt']) && ($now - $_SESSION['last_login_attempt']) < 3) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Trop de requêtes. Veuillez patienter quelques secondes.']);
        exit();
    }
    $_SESSION['last_login_attempt'] = $now;
}

if ($action === 'login') {
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($email) || empty($password)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Veuillez saisir votre courriel et votre mot de passe.']);
        exit();
    }

    // Lookup active portal user
    $stmt = $pdo->prepare("SELECT * FROM client_users WHERE email = :email AND active = 1 LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        sleep(2); // Slow down brute force
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Identifiants de connexion invalides.']);
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        sleep(2); // Slow down brute force
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Identifiants de connexion invalides.']);
        exit();
    }

    // Set client portal session
    $_SESSION['client_user_id'] = $user['id'];
    $_SESSION['client_id'] = $user['client_id'];
    $_SESSION['client_company_id'] = $user['company_id'];

    // Update last login
    $stmtUpdate = $pdo->prepare("UPDATE client_users SET last_login = NOW() WHERE id = :id");
    $stmtUpdate->execute(['id' => $user['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie.'
    ]);
    exit();
}

if ($action === 'forgot_password') {
    $email = trim($input['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Adresse courriel invalide.']);
        exit();
    }

    // Look up user
    $stmt = $pdo->prepare("SELECT id, name, company_id FROM client_users WHERE email = :email AND active = 1 LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $stmtUpdate = $pdo->prepare("UPDATE client_users SET password_reset_token = :token, password_reset_expires = :expires WHERE id = :id");
        $stmtUpdate->execute(['token' => $token, 'expires' => $expires, 'id' => $user['id']]);

        // Debug/Local Logging
        $isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '.local') !== false);
        if ($isLocal) {
            error_log("DEBUG RESET PASSWORD TOKEN FOR " . $email . " (" . $user['id'] . "): " . $token);
        }
    }

    // Always return a generic success message
    echo json_encode([
        'success' => true,
        'message' => 'Si le compte existe, des instructions ont été envoyées pour réinitialiser le mot de passe.'
    ]);
    exit();
}

if ($action === 'reset_password') {
    $token = trim($input['token'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($token) || empty($password)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Paramètres requis manquants.']);
        exit();
    }

    // Look up user by valid token
    $stmt = $pdo->prepare("SELECT id FROM client_users WHERE password_reset_token = :token AND password_reset_expires > NOW() AND active = 1 LIMIT 1");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le jeton de réinitialisation est invalide ou a expiré.']);
        exit();
    }

    // Hash new password and update
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmtUpdate = $pdo->prepare("UPDATE client_users SET password_hash = :hash, password_reset_token = NULL, password_reset_expires = NULL WHERE id = :id");
    $stmtUpdate->execute(['hash' => $hash, 'id' => $user['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Mot de passe réinitialisé avec succès.'
    ]);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
exit();
