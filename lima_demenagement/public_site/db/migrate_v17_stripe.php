<?php
// LIMA Solutions ERP - Migrate V17 - Stripe Gateway Tables
// CLI-only execution restriction. Can not be executed via HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'This script must be run only through the SSH/CLI backend workflow.']);
    exit(1);
}

require_once dirname(__DIR__) . '/api/v1/config.php';

try {
    $queries = [
        // 1. payment_transactions table
        "CREATE TABLE IF NOT EXISTS `payment_transactions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT NOT NULL,
            `invoice_id` INT NOT NULL,
            `provider` VARCHAR(30) NOT NULL, -- 'stripe'
            `provider_session_id` VARCHAR(255) NOT NULL, -- Checkout session ID
            `provider_payment_intent` VARCHAR(255) DEFAULT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `currency` VARCHAR(10) DEFAULT 'CHF',
            `status` VARCHAR(30) DEFAULT 'Pending', -- 'Pending', 'Succeeded', 'Failed', 'Expired'
            `error_message` TEXT DEFAULT NULL,
            `idempotency_key` VARCHAR(64) NOT NULL UNIQUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_pay_tx_company_invoice` (`company_id`, `invoice_id`),
            INDEX `idx_pay_tx_provider_session` (`provider`, `provider_session_id`),
            FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
            FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // 2. payment_webhooks table
        "CREATE TABLE IF NOT EXISTS `payment_webhooks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT NOT NULL,
            `provider` VARCHAR(30) NOT NULL,
            `event_id` VARCHAR(150) NOT NULL,
            `payload` LONGTEXT NOT NULL,
            `processed` TINYINT(1) DEFAULT 0,
            `error_details` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_webhook_event` (`provider`, `event_id`),
            INDEX `idx_webhook_company` (`company_id`),
            FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // 3. payment_refunds table
        "CREATE TABLE IF NOT EXISTS `payment_refunds` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT NOT NULL,
            `transaction_id` INT NOT NULL,
            `provider_refund_id` VARCHAR(255) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `reason` TEXT DEFAULT NULL,
            `status` VARCHAR(30) DEFAULT 'Pending', -- 'Pending', 'Succeeded', 'Failed'
            `created_by` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_refund_company_tx` (`company_id`, `transaction_id`),
            FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
            FOREIGN KEY (`transaction_id`) REFERENCES `payment_transactions` (`id`) ON DELETE RESTRICT,
            FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    $log = [];
    foreach ($queries as $query) {
        $pdo->exec($query);
        $log[] = "Success: " . substr($query, 0, 50) . "...";
    }

    echo "Migration V17 Stripe Tables completed successfully.\n";
    foreach ($log as $l) {
        echo " - $l\n";
    }

} catch (Exception $e) {
    echo "Migration V17 failed: " . $e->getMessage() . "\n";
    exit(1);
}
