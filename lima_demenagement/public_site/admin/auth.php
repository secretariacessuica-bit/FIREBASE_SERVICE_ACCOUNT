<?php
// LIMA Solutions ERP - Authentication Middleware
require_once __DIR__ . '/../api/v1/config.php';

// Send hardened HTTP security headers on every protected admin page.
sendSecurityHeaders();

// ─── Authentication Check ────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ─── Session Timeout Check (30 minutes) ─────────────────────────────────────────
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    header('Location: login.php?error=timeout');
    exit();
}
$_SESSION['last_activity'] = time();


// ─── Active Company Validation ───────────────────────────────────────────────
$activeCompanyId = getActiveCompanyId();

if (!$activeCompanyId) {
    $role = $_SESSION['user_role'] ?? 'viewer';

    if ($role === 'super_admin') {
        // Super admins may select a company to impersonate.
        header('Location: select_company.php');
    } else {
        // Regular users without an associated company cannot proceed.
        session_unset();
        session_destroy();
        header('Location: login.php?error=no_company');
    }
    exit();
}
