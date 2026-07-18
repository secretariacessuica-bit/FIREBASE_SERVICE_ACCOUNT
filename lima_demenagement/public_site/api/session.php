<?php
// LIMA Solutions Platform - Session Checker API
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'message' => 'Nenhum usuário autenticado.'
    ]);
}
exit();
