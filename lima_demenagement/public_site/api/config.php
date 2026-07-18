<?php
// LIMA Solutions Platform - Config & Database Connection

// Database parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'lima_solutions');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session security configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Establish PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Return structured JSON error if connection fails during an API request
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()
    ]);
    exit();
}
