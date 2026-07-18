<?php
// LIMA Solutions ERP - Phase 6 Migration Script
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migração da Fase 6...\n";

try {
    // 1. Alter Table invoices
    $sqlInvoices = [
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `quote_id` INT DEFAULT NULL AFTER `client_id`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `currency` VARCHAR(10) DEFAULT 'CHF' AFTER `due_date`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `discount_amount`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `tax_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `discount_percent`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `balance_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `paid_amount`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `internal_notes` TEXT DEFAULT NULL AFTER `notes`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `created_by` INT DEFAULT NULL AFTER `internal_notes`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME DEFAULT NULL AFTER `updated_at`"
    ];

    foreach ($sqlInvoices as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso: $query\n";
        } catch (PDOException $e) {
            echo "Ignorado/Erro: " . $e->getMessage() . "\n";
        }
    }

    // Add indexes for invoices
    $indexesInvoices = [
        "ALTER TABLE `invoices` ADD INDEX IF NOT EXISTS `idx_invoices_company_status` (`company_id`, `status`)",
        "ALTER TABLE `invoices` ADD INDEX IF NOT EXISTS `idx_invoices_company_client` (`company_id`, `client_id`)",
        "ALTER TABLE `invoices` ADD INDEX IF NOT EXISTS `idx_invoices_quote_id` (`company_id`, `quote_id`)",
        "ALTER TABLE `invoices` ADD INDEX IF NOT EXISTS `idx_invoices_issue_date` (`company_id`, `issue_date`)",
        "ALTER TABLE `invoices` ADD INDEX IF NOT EXISTS `idx_invoices_due_date` (`company_id`, `due_date`)",
        "ALTER TABLE `invoices` ADD INDEX IF NOT EXISTS `idx_invoices_deleted` (`company_id`, `deleted_at`)"
    ];

    foreach ($indexesInvoices as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso índice: $query\n";
        } catch (PDOException $e) {
            echo "Ignorado índice: " . $e->getMessage() . "\n";
        }
    }

    // 2. Alter Table invoice_items
    $sqlItems = [
        "ALTER TABLE `invoice_items` ADD COLUMN IF NOT EXISTS `company_id` INT NOT NULL AFTER `id`",
        "ALTER TABLE `invoice_items` ADD COLUMN IF NOT EXISTS `position` INT NOT NULL DEFAULT 1 AFTER `invoice_id`",
        "ALTER TABLE `invoice_items` ADD COLUMN IF NOT EXISTS `unit_id` INT DEFAULT NULL AFTER `quantity`",
        "ALTER TABLE `invoice_items` ADD COLUMN IF NOT EXISTS `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`",
        "ALTER TABLE `invoice_items` ADD COLUMN IF NOT EXISTS `tax_rate_id` INT DEFAULT NULL AFTER `discount_percent`",
        "ALTER TABLE `invoice_items` ADD COLUMN IF NOT EXISTS `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `tax_rate_id`",
        "ALTER TABLE `invoice_items` ADD COLUMN IF NOT EXISTS `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`"
    ];

    foreach ($sqlItems as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso item: $query\n";
        } catch (PDOException $e) {
            echo "Ignorado item: " . $e->getMessage() . "\n";
        }
    }

    // Add indexes for invoice_items
    $indexesItems = [
        "ALTER TABLE `invoice_items` ADD INDEX IF NOT EXISTS `idx_invoice_items_invoice` (`invoice_id`)",
        "ALTER TABLE `invoice_items` ADD INDEX IF NOT EXISTS `idx_invoice_items_company_invoice` (`company_id`, `invoice_id`)",
        "ALTER TABLE `invoice_items` ADD INDEX IF NOT EXISTS `idx_invoice_items_position` (`invoice_id`, `position`)"
    ];

    foreach ($indexesItems as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso índice item: $query\n";
        } catch (PDOException $e) {
            echo "Ignorado índice item: " . $e->getMessage() . "\n";
        }
    }

    // 3. Add quotes module permissions if missing
    // Just in case seed was not run, let's run queries to ensure 'quotes' module exists
    $pdo->exec("INSERT IGNORE INTO company_modules (company_id, module_name, enabled) 
                SELECT id, 'quotes', 1 FROM companies");

    echo "Migração concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro catastrófico na migração: " . $e->getMessage() . "\n";
}
