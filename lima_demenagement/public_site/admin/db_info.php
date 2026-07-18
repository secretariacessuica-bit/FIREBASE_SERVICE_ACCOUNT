<?php
header('Content-Type: text/plain; charset=utf-8');
$configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__, 2) . '/private/config.php';
}
require_once $configPath;
try {
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "--- USERS IN DB ---\n";
    $stmt = $pdo->query("SELECT id, name, email, role, active FROM users");
    $users = $stmt->fetchAll();
    foreach ($users as $u) {
        echo "ID: {$u['id']} | Name: {$u['name']} | Role: {$u['role']} | Active: {$u['active']}\n";
    }

    echo "\n--- PROJECTS IN DB ---\n";
    $stmt = $pdo->query("SELECT id, name, status, start_date, end_date FROM projects LIMIT 10");
    $projects = $stmt->fetchAll();
    foreach ($projects as $p) {
        echo "ID: {$p['id']} | Name: {$p['name']} | Status: {$p['status']} | Dates: {$p['start_date']} to {$p['end_date']}\n";
    }

    echo "\n--- CURRENT OPERATIONAL ASSIGNMENTS ---\n";
    $stmt = $pdo->query("SELECT * FROM operational_assignments LIMIT 10");
    $assigns = $stmt->fetchAll();
    foreach ($assigns as $a) {
        echo "ID: {$a['id']} | ProjID: {$a['project_id']} | UserID: {$a['user_id']} | Status: {$a['status']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
