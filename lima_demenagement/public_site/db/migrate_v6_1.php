<?php
// LIMA Solutions ERP - Phase 6.1 Migration Script
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migração da Fase 6.1...\n";

try {
    // 1. Alter Table invoices
    $sqlInvoices = [
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `cancellation_reason` TEXT DEFAULT NULL AFTER `internal_notes`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `document_hash` VARCHAR(64) DEFAULT NULL AFTER `cancellation_reason`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `fiscal_snapshot` LONGTEXT DEFAULT NULL AFTER `document_hash`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `pdf_path` VARCHAR(255) DEFAULT NULL AFTER `fiscal_snapshot`"
    ];

    foreach ($sqlInvoices as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso: $query\n";
        } catch (PDOException $e) {
            echo "Erro na query: " . $e->getMessage() . "\n";
        }
    }

    echo "Migração 6.1 concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro catastrófico na migração: " . $e->getMessage() . "\n";
}
