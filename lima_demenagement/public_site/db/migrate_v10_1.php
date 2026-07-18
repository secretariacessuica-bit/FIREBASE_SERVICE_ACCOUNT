<?php
// LIMA Solutions ERP - Phase 10.1 Migration
// Idempotent migration: adds billing_batch_id to timesheets and invoices tables.
// Run via CLI/SSH only (HTTP access is blocked by db/.htaccess).
//
// Usage: php migrate_v10_1.php

require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migracao da Fase 10.1 (Idempotencia + billing_batch_id)...\n";

try {
    // ─── Helper functions ────────────────────────────────────────────────────
    function columnExists10($pdo, $table, $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :col");
        $stmt->execute(['col' => $column]);
        return (bool)$stmt->fetch();
    }

    function indexExists10($pdo, $table, $indexName) {
        $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = :idx");
        $stmt->execute(['idx' => $indexName]);
        return (bool)$stmt->fetch();
    }

    // ─── 1. timesheets: add billing_batch_id ─────────────────────────────────
    if (!columnExists10($pdo, 'timesheets', 'billing_batch_id')) {
        $pdo->exec("ALTER TABLE `timesheets`
            ADD COLUMN `billing_batch_id` VARCHAR(64) DEFAULT NULL
            AFTER `locked`");
        echo "[OK] Coluna `billing_batch_id` adicionada a `timesheets`.\n";
    } else {
        echo "[SKIP] Coluna `billing_batch_id` ja existe em `timesheets`.\n";
    }

    // Index for batch lookup
    if (!indexExists10($pdo, 'timesheets', 'idx_ts_billing_batch')) {
        $pdo->exec("ALTER TABLE `timesheets`
            ADD INDEX `idx_ts_billing_batch` (`billing_batch_id`)");
        echo "[OK] Indice `idx_ts_billing_batch` adicionado.\n";
    } else {
        echo "[SKIP] Indice `idx_ts_billing_batch` ja existe.\n";
    }

    // ─── 2. invoices: add billing_batch_id ───────────────────────────────────
    if (!columnExists10($pdo, 'invoices', 'billing_batch_id')) {
        $pdo->exec("ALTER TABLE `invoices`
            ADD COLUMN `billing_batch_id` VARCHAR(64) DEFAULT NULL
            AFTER `created_by`");
        echo "[OK] Coluna `billing_batch_id` adicionada a `invoices`.\n";
    } else {
        echo "[SKIP] Coluna `billing_batch_id` ja existe em `invoices`.\n";
    }

    if (!indexExists10($pdo, 'invoices', 'idx_inv_billing_batch')) {
        $pdo->exec("ALTER TABLE `invoices`
            ADD INDEX `idx_inv_billing_batch` (`billing_batch_id`)");
        echo "[OK] Indice `idx_inv_billing_batch` adicionado.\n";
    } else {
        echo "[SKIP] Indice `idx_inv_billing_batch` ja existe.\n";
    }

    echo "\nMigracao da Fase 10.1 concluida com sucesso!\n";

} catch (Exception $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    exit(1);
}
