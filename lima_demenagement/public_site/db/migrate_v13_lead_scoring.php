<?php
// LIMA Solutions ERP - Migrate V13 - CRM Lead Scoring & Sales Automation
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: application/json; charset=utf-8');

// Allow only admin or super_admin to execute if session exists
$role = $_SESSION['user_role'] ?? '';
if (!empty($role) && !in_array($role, ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès interdit.']);
    exit();
}

try {
    $queries = [
        "ALTER TABLE `crm_leads` ADD COLUMN IF NOT EXISTS `lead_score` INT DEFAULT 0",
        "ALTER TABLE `crm_leads` ADD COLUMN IF NOT EXISTS `lead_score_reasons` JSON NULL",
        "ALTER TABLE `crm_leads` ADD COLUMN IF NOT EXISTS `priority_alert_sent_at` DATETIME NULL",
        "ALTER TABLE `crm_leads` ADD COLUMN IF NOT EXISTS `last_contacted_at` DATETIME NULL",
        "ALTER TABLE `crm_leads` ADD INDEX IF NOT EXISTS `idx_leads_score` (`lead_score`)"
    ];

    $log = [];
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            $log[] = "Success: $query";
        } catch (PDOException $e) {
            $log[] = "Skipped/Error: " . $e->getMessage() . " on query: $query";
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migration V13 CRM Lead Scoring completed.',
        'log' => $log
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Migration V13 failed: ' . $e->getMessage()]);
}
