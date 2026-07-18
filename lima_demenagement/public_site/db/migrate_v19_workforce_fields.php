<?php
// LIMA Solutions ERP - Migrate V19 - Workforce Management Fields
// CLI-only execution restriction. Can not be executed via HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'This script must be run only through the SSH/CLI backend workflow.']);
    exit(1);
}

require_once dirname(__DIR__) . '/api/v1/config.php';

try {
    // 1. Add fields to users table
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'phone'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(30) DEFAULT NULL AFTER `email`");
        echo "Added phone column to users.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'postal_code'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `postal_code` VARCHAR(20) DEFAULT NULL AFTER `active`");
        echo "Added postal_code column to users.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'address'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `address` VARCHAR(255) DEFAULT NULL AFTER `postal_code`");
        echo "Added address column to users.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'hourly_cost'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `hourly_cost` DECIMAL(10,2) DEFAULT 0.00 AFTER `address`");
        echo "Added hourly_cost column to users.\n";
    }

    // 2. Enable 'staff' module for all companies in company_modules if not exists
    $companies = $pdo->query("SELECT id FROM companies")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($companies as $cid) {
        $pdo->exec("INSERT INTO company_modules (company_id, module_name, enabled) VALUES ($cid, 'staff', 1) ON DUPLICATE KEY UPDATE enabled=1");
    }
    echo "Enabled staff module for all companies.\n";

    // 3. Set standard permissions for staff module
    $roles = ['admin', 'manager'];
    foreach ($roles as $role) {
        $pdo->exec("INSERT INTO module_permissions (role, module_name, can_view, can_edit) VALUES ('$role', 'staff', 1, 1) ON DUPLICATE KEY UPDATE can_view=1, can_edit=1");
    }
    echo "Permissions set for staff module.\n";

    echo "Migration V19 completed successfully.\n";

} catch (Exception $e) {
    echo "Migration V19 failed: " . $e->getMessage() . "\n";
    exit(1);
}
