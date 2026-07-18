<?php
// LIMA Solutions ERP - Direct Schema/Table Inserter for Operations Observability
header('Content-Type: text/plain; charset=utf-8');

$configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__, 2) . '/private/config.php';
}

if (!file_exists($configPath)) {
    echo "Config file not found at: " . $configPath . "\n";
    exit();
}

require_once $configPath;

try {
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected successfully to: " . SECURE_DB_NAME . "\n";

    $queries = [
        "CREATE TABLE IF NOT EXISTS `system_metrics_daily` (
            `metric_date` DATE PRIMARY KEY,
            `active_users` INT DEFAULT 0,
            `failed_logins` INT DEFAULT 0,
            `smtp_success` INT DEFAULT 0,
            `smtp_failures` INT DEFAULT 0,
            `mobile_sync_success` INT DEFAULT 0,
            `mobile_sync_failures` INT DEFAULT 0,
            `api_errors` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "Success: $query\n";
        } catch (PDOException $e) {
            echo "Skipped/Error: " . $e->getMessage() . " on query: $query\n";
        }
    }

    echo "SUCCESS: Operations Observability metric table schema migration completed!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
