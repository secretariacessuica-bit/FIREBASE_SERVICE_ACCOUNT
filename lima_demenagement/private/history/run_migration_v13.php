<?php
// LIMA Solutions ERP - Direct Schema/Table Inserter for CRM Lead Scoring
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
        "ALTER TABLE `crm_leads` ADD COLUMN IF NOT EXISTS `lead_score` INT DEFAULT 0",
        "ALTER TABLE `crm_leads` ADD COLUMN IF NOT EXISTS `lead_score_reasons` JSON NULL",
        "ALTER TABLE `crm_leads` ADD COLUMN IF NOT EXISTS `priority_alert_sent_at` DATETIME NULL",
        "ALTER TABLE `crm_leads` ADD COLUMN IF NOT EXISTS `last_contacted_at` DATETIME NULL",
        "ALTER TABLE `crm_leads` ADD INDEX IF NOT EXISTS `idx_leads_score` (`lead_score`)"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "Success: $query\n";
        } catch (PDOException $e) {
            echo "Skipped/Error: " . $e->getMessage() . " on query: $query\n";
        }
    }

    echo "SUCCESS: Lead scoring columns added successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
