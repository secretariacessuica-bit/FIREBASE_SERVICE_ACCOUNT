<?php
// LIMA Solutions ERP - Phase 10 Leads & CRM Integration Migration
// Idempotent migration: creates crm_leads and simulated_emails tables.
// Run via CLI/SSH only (HTTP access is blocked by db/.htaccess).
//
// Usage: php migrate_v10_leads.php

require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migracao da Fase 10 (crm_leads + simulated_emails)...\n";

try {
    // Check if table crm_leads exists
    $stmtLeads = $pdo->query("SHOW TABLES LIKE 'crm_leads'");
    if (!$stmtLeads->fetch()) {
        $pdo->exec("CREATE TABLE `crm_leads` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(30) DEFAULT NULL,
            `origin_address` VARCHAR(255) DEFAULT NULL,
            `destination_address` VARCHAR(255) DEFAULT NULL,
            `service_date` DATE DEFAULT NULL,
            `volume_m3` DECIMAL(10,2) DEFAULT NULL,
            `status` VARCHAR(50) DEFAULT 'New',
            `notes` TEXT DEFAULT NULL,
            `utm_source` VARCHAR(100) DEFAULT NULL,
            `utm_medium` VARCHAR(100) DEFAULT NULL,
            `utm_campaign` VARCHAR(100) DEFAULT NULL,
            `referer_url` TEXT DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `converted_client_id` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_leads_company` (`company_id`),
            INDEX `idx_leads_status` (`company_id`, `status`),
            INDEX `idx_leads_email` (`company_id`, `email`),
            INDEX `idx_leads_phone` (`company_id`, `phone`),
            FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
            FOREIGN KEY (`converted_client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "[OK] Tabela `crm_leads` criada com sucesso.\n";
    } else {
        echo "[SKIP] Tabela `crm_leads` ja existe.\n";
    }

    // Check if table simulated_emails exists
    $stmtEmails = $pdo->query("SHOW TABLES LIKE 'simulated_emails'");
    if (!$stmtEmails->fetch()) {
        $pdo->exec("CREATE TABLE `simulated_emails` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT NOT NULL,
            `recipient` VARCHAR(150) NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `body` TEXT NOT NULL,
            `headers` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_emails_company` (`company_id`),
            INDEX `idx_emails_recipient` (`recipient`),
            FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "[OK] Tabela `simulated_emails` criada com sucesso.\n";
    } else {
        echo "[SKIP] Tabela `simulated_emails` ja existe.\n";
    }

    echo "\nMigracao da Fase 10 concluida com sucesso!\n";

} catch (Exception $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    exit(1);
}
