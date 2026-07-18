<?php
// Session inspector helper endpoint with cookie extraction
require_once __DIR__ . '/../api/v1/config.php';
header('Content-Type: application/json; charset=utf-8');

$cookies = $_COOKIE;
$session_id = session_id();

$userId = $_SESSION['user_id'] ?? null;
$dbUser = null;
if ($userId) {
    $stmt = $pdo->prepare("SELECT id, name, email, role, active FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $dbUser = $stmt->fetch();
} else {
    // If no session user_id, dump all users to verify their roles
    $stmt = $pdo->query("SELECT id, name, email, role, active FROM users");
    $dbUser = $stmt->fetchAll();
}

echo json_encode([
    'success' => true,
    'session_id' => $session_id,
    'cookies' => $cookies,
    'session_user_id' => $_SESSION['user_id'] ?? null,
    'session_user_name' => $_SESSION['user_name'] ?? null,
    'session_user_email' => $_SESSION['user_email'] ?? null,
    'session_user_role' => $_SESSION['user_role'] ?? null,
    'data' => $dbUser
]);
