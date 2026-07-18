<?php
// LIMA Solutions ERP - Client Portal Authentication Middleware
require_once __DIR__ . '/../api/v1/config.php';

// Send hardened HTTP security headers
if (!function_exists('sendSecurityHeaders')) {
    function sendSecurityHeaders() {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; font-src 'self' https:; img-src 'self' data: https:;");
    }
}
sendSecurityHeaders();

if (!isset($_SESSION['client_user_id'])) {
    header('Location: /portal/login.php');
    exit();
}

// Verify that client user is still active in the database
$stmt = $pdo->prepare("SELECT active FROM client_users WHERE id = :id AND company_id = :company_id LIMIT 1");
$stmt->execute(['id' => $_SESSION['client_user_id'], 'company_id' => $_SESSION['client_company_id']]);
$active = $stmt->fetchColumn();

if ($active === false || (int)$active !== 1) {
    // Session is invalid or user deactivated
    session_unset();
    session_destroy();
    header('Location: /portal/login.php?error=deactivated');
    exit();
}
