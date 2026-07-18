<?php
// LIMA Solutions ERP - Phase 9.1 Hardening Migration Script
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migração da Fase 9.1 (Hardening & Índices)...\n";

try {
    // Helper function to check if column exists
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
        $stmt->execute(['column' => $column]);
        return (bool)$stmt->fetch();
    }

    // Helper function to check if index exists
    function indexExists($pdo, $table, $indexName) {
        $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = :indexName");
        $stmt->execute(['indexName' => $indexName]);
        return (bool)$stmt->fetch();
    }

    // 1. Alter timesheets table to add columns
    if (!columnExists($pdo, 'timesheets', 'approved_hourly_cost')) {
        $pdo->exec("ALTER TABLE `timesheets` ADD COLUMN `approved_hourly_cost` DECIMAL(10,2) DEFAULT 0.00 AFTER `hourly_rate`");
        echo "Coluna `approved_hourly_cost` adicionada com sucesso.\n";
    }
    if (!columnExists($pdo, 'timesheets', 'approved_billable_rate')) {
        $pdo->exec("ALTER TABLE `timesheets` ADD COLUMN `approved_billable_rate` DECIMAL(10,2) DEFAULT 0.00 AFTER `approved_hourly_cost`");
        echo "Coluna `approved_billable_rate` adicionada com sucesso.\n";
    }
    if (!columnExists($pdo, 'timesheets', 'invoiced_at')) {
        $pdo->exec("ALTER TABLE `timesheets` ADD COLUMN `invoiced_at` DATETIME DEFAULT NULL AFTER `rejected_at`");
        echo "Coluna `invoiced_at` adicionada com sucesso.\n";
    }
    if (!columnExists($pdo, 'timesheets', 'locked')) {
        $pdo->exec("ALTER TABLE `timesheets` ADD COLUMN `locked` TINYINT(1) DEFAULT 0 AFTER `invoiced_at`");
        echo "Coluna `locked` adicionada com sucesso.\n";
    }

    // 2. Add performance indexes
    if (!indexExists($pdo, 'timesheets', 'idx_ts_comp_proj_status_date')) {
        $pdo->exec("ALTER TABLE `timesheets` ADD INDEX `idx_ts_comp_proj_status_date` (`company_id`, `project_id`, `status`, `work_date`)");
        echo "Índice `idx_ts_comp_proj_status_date` adicionado.\n";
    }
    if (!indexExists($pdo, 'timesheets', 'idx_ts_comp_user_date')) {
        $pdo->exec("ALTER TABLE `timesheets` ADD INDEX `idx_ts_comp_user_date` (`company_id`, `user_id`, `work_date`)");
        echo "Índice `idx_ts_comp_user_date` adicionado.\n";
    }

    echo "Migração da Fase 9.1 concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro catastrófico na migração 9.1: " . $e->getMessage() . "\n";
}
