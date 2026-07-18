<?php
// LIMA Solutions ERP - Phase 7 Migration Script (Payments Module)
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migração da Fase 7 (Módulo de Pagamentos)...\n";

try {
    // 1. Alter Table payments to match requested structure
    $alterations = [
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `company_id` INT NOT NULL AFTER `id`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `payment_number` VARCHAR(50) NOT NULL AFTER `company_id`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `currency` VARCHAR(10) DEFAULT 'CHF' AFTER `amount`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(50) NOT NULL AFTER `currency`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `transaction_reference` VARCHAR(100) DEFAULT NULL AFTER `reference`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `received_by` INT DEFAULT NULL AFTER `notes`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `receipt_path` VARCHAR(255) DEFAULT NULL AFTER `received_by`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME DEFAULT NULL AFTER `updated_at`"
    ];

    foreach ($alterations as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso: $query\n";
        } catch (PDOException $e) {
            echo "Aviso/Informação na query: " . $e->getMessage() . " (pode já existir)\n";
        }
    }

    // Drop old method column if it exists and copy data to payment_method if needed
    try {
        $pdo->exec("UPDATE `payments` SET `payment_method` = `method` WHERE `payment_method` IS NULL OR `payment_method` = ''");
        $pdo->exec("ALTER TABLE `payments` DROP COLUMN `method`");
        echo "Coluna `method` migrada para `payment_method` e removida.\n";
    } catch (Exception $e) {
        echo "Aviso ao tratar coluna `method`: " . $e->getMessage() . "\n";
    }

    // 2. Add Constraints, Foreign Keys and Indices
    $constraints = [
        "ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_payments_company_id` (`company_id`)",
        "ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_payments_invoice_id` (`invoice_id`)",
        "ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_payments_payment_date` (`payment_date`)",
        "ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_payments_payment_number` (`payment_number`)",
        "ALTER TABLE `payments` ADD INDEX IF NOT EXISTS `idx_payments_deleted_at` (`deleted_at`)",
        "ALTER TABLE `payments` ADD UNIQUE KEY `idx_comp_pay_num` (`company_id`, `payment_number`)"
    ];

    foreach ($constraints as $query) {
        try {
            $pdo->exec($query);
            echo "Sucesso: $query\n";
        } catch (PDOException $e) {
            echo "Aviso/Informação na query de índices: " . $e->getMessage() . "\n";
        }
    }

    // 3. Register module seeds
    // Enable module 'payments' for all existing companies
    $companies = $pdo->query("SELECT id FROM companies")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($companies as $cid) {
        try {
            $stmt = $pdo->prepare("INSERT INTO company_modules (company_id, module_name, enabled) VALUES (:cid, 'payments', 1) ON DUPLICATE KEY UPDATE enabled = 1");
            $stmt->execute(['cid' => $cid]);
            echo "Módulo 'payments' ativado para empresa ID $cid.\n";
        } catch (Exception $e) {
            echo "Erro ao ativar módulo para empresa $cid: " . $e->getMessage() . "\n";
        }
    }

    // Set roles permissions for payments
    $permissions = [
        ['super_admin', 1, 1],
        ['admin', 1, 1],
        ['finance', 1, 1],
        ['staff', 1, 0],
        ['viewer', 1, 0]
    ];
    foreach ($permissions as $p) {
        try {
            $stmt = $pdo->prepare("INSERT INTO module_permissions (role, module_name, can_view, can_edit) VALUES (:role, 'payments', :cv, :ce) ON DUPLICATE KEY UPDATE can_view = :cv, can_edit = :ce");
            $stmt->execute(['role' => $p[0], 'cv' => $p[1], 'ce' => $p[2]]);
            echo "Permissões de 'payments' configuradas para o papel {$p[0]}.\n";
        } catch (Exception $e) {
            echo "Erro ao inserir permissão para papel {$p[0]}: " . $e->getMessage() . "\n";
        }
    }

    echo "Migração do Módulo de Pagamentos (Fase 7) concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro catastrófico na migração: " . $e->getMessage() . "\n";
}
