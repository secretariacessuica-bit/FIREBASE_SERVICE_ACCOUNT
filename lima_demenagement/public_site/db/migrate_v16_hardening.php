<?php
// LIMA Solutions ERP - Migrate V16 - Hardening Indexes & Pruning
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: application/json; charset=utf-8');

$role = $_SESSION['user_role'] ?? '';
if (!empty($role) && !in_array($role, ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès interdit.']);
    exit();
}

try {
    $queries = [
        // T06: Create Approved Performance Indexes
        "ALTER TABLE `crm_leads` ADD INDEX IF NOT EXISTS `idx_leads_dashboard` (`company_id`, `status`, `created_at`)",
        "ALTER TABLE `projects` ADD INDEX IF NOT EXISTS `idx_projects_kanban` (`company_id`, `start_date`, `status`)",
        "ALTER TABLE `timesheets` ADD INDEX IF NOT EXISTS `idx_timesheets_mobile_h` (`company_id`, `user_id`, `status`, `work_date`)",
        
        // T07: Purge activity logs older than 180 days
        "DELETE FROM `activity_logs` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 180 DAY)"
    ];

    $log = [];
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            $log[] = "Success: $query";
        } catch (PDOException $ex) {
            $log[] = "Info/Ignored: $query - " . $ex->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migration V16 Hardening complete.',
        'log' => $log
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Migration V16 failed: ' . $e->getMessage()]);
}
