<?php
// LIMA Solutions ERP - Migrate V15 - Operations Observability
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

    $log = [];
    foreach ($queries as $query) {
        $pdo->exec($query);
        $log[] = "Success: $query";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migration V15 Operations Observability completed.',
        'log' => $log
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Migration V15 failed: ' . $e->getMessage()]);
}
