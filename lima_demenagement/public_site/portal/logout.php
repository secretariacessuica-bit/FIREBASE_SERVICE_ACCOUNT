<?php
// LIMA Solutions ERP - Client Portal Logout
require_once __DIR__ . '/../api/v1/config.php';

// Clear client session variables only
unset($_SESSION['client_user_id']);
unset($_SESSION['client_id']);
unset($_SESSION['client_company_id']);

// If no admin session exists, destroy the session entirely
if (!isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
}

header('Location: /portal/login.php');
exit();
