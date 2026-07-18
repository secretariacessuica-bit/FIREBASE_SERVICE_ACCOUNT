<?php
// LIMA Solutions ERP - Phase 7.1 Migration Script (Payments Hardening & Reversals)
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migração da Fase 7.1 (Hardening de Pagamentos e Estornos)...\n";

try {
    // 1. Alter Table payments and activity_logs to add reversal columns
    $alterations = [
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `reversed_at` DATETIME DEFAULT NULL AFTER `receipt_path`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `reversed_by` INT DEFAULT NULL AFTER `reversed_at`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `reversal_reason` TEXT DEFAULT NULL AFTER `reversed_by`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `reversal_payment_id` INT DEFAULT NULL AFTER `reversal_reason`",
        "ALTER TABLE `activity_logs` ADD COLUMN IF NOT EXISTS `reversal_payment_id` INT DEFAULT NULL AFTER `request_id`"
    ];

    foreach ($alterations as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso: $query\n";
        } catch (PDOException $e) {
            echo "Aviso/Informação na query: " . $e->getMessage() . "\n";
        }
    }

    // 2. Add Constraints and Indices
    $constraints = [
        "ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_payments_reversal_payment_id` (`reversal_payment_id`)",
        "ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_payments_reversed_at` (`reversed_at`)",
        "ALTER TABLE `payments` ADD CONSTRAINT `fk_payments_reversal_payment` FOREIGN KEY IF NOT EXISTS (`reversal_payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT",
        "ALTER TABLE `payments` ADD CONSTRAINT `fk_payments_reversed_by` FOREIGN KEY IF NOT EXISTS (`reversed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT",
        "ALTER TABLE `activity_logs` ADD INDEX IF NOT EXISTS `idx_activity_logs_reversal_payment_id` (`reversal_payment_id`)"
    ];

    foreach ($constraints as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso: $query\n";
        } catch (PDOException $e) {
            echo "Aviso/Informação na query de restrições: " . $e->getMessage() . "\n";
        }
    }

    echo "Migração da Fase 7.1 concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro catastrófico na migração: " . $e->getMessage() . "\n";
}
