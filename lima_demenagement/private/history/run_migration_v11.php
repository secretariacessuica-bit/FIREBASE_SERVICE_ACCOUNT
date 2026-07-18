<?php
// LIMA Solutions ERP - Direct Schema/Table Inserter to bypass include path issues
header('Content-Type: text/plain; charset=utf-8');

$configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__, 2) . '/private/config.php';
}

if (!file_exists($configPath)) {
    echo "Config file not found at: " . $configPath . "\n";
    exit();
}

require_once $configPath;

try {
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected successfully to: " . SECURE_DB_NAME . "\n";

    // Criar tabelas individualmente sem usar transação explícita (uma vez que DDL faz autocommit no MySQL e invalida transações)
    echo "Creating mobile_tokens...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `mobile_tokens` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `token_hash` VARCHAR(64) NOT NULL UNIQUE,
      `device_name` VARCHAR(100) DEFAULT NULL,
      `device_uuid` VARCHAR(100) DEFAULT NULL,
      `last_used_at` DATETIME DEFAULT NULL,
      `expires_at` DATETIME NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `revoked_at` DATETIME DEFAULT NULL,
      INDEX `idx_m_tokens_comp_user` (`company_id`, `user_id`),
      INDEX `idx_m_tokens_hash` (`token_hash`),
      FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "Creating operational_assignments...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `operational_assignments` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `project_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `status` VARCHAR(30) DEFAULT 'Pending',
      `client_uuid` VARCHAR(36) DEFAULT NULL,
      `created_offline_at` DATETIME DEFAULT NULL,
      `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `sync_status` VARCHAR(20) DEFAULT 'Synced',
      INDEX `idx_op_assign_company` (`company_id`),
      INDEX `idx_op_assign_proj_user` (`project_id`, `user_id`),
      FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
      FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "Creating gps_tracking...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `gps_tracking` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `project_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `latitude` DECIMAL(10, 8) NOT NULL,
      `longitude` DECIMAL(11, 8) NOT NULL,
      `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `client_uuid` VARCHAR(36) DEFAULT NULL,
      `created_offline_at` DATETIME DEFAULT NULL,
      `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `sync_status` VARCHAR(20) DEFAULT 'Synced',
      INDEX `idx_gps_company` (`company_id`),
      INDEX `idx_gps_proj_user` (`project_id`, `user_id`),
      INDEX `idx_gps_recorded` (`recorded_at`),
      FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
      FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "Creating project_photos...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `project_photos` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `project_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `photo_type` VARCHAR(30) NOT NULL,
      `filename` VARCHAR(255) NOT NULL,
      `original_name` VARCHAR(255) NOT NULL,
      `mime_type` VARCHAR(100) NOT NULL,
      `size` INT NOT NULL,
      `description` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `client_uuid` VARCHAR(36) DEFAULT NULL,
      `created_offline_at` DATETIME DEFAULT NULL,
      `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `sync_status` VARCHAR(20) DEFAULT 'Synced',
      INDEX `idx_photos_company` (`company_id`),
      INDEX `idx_photos_project` (`project_id`),
      INDEX `idx_photos_user` (`user_id`),
      FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
      FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "Creating project_checklists...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `project_checklists` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `project_id` INT NOT NULL,
      `item_name` VARCHAR(150) NOT NULL,
      `status` VARCHAR(30) DEFAULT 'Pending',
      `notes` TEXT DEFAULT NULL,
      `updated_by` INT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `client_uuid` VARCHAR(36) DEFAULT NULL,
      `created_offline_at` DATETIME DEFAULT NULL,
      `synced_at` DATETIME DEFAULT NULL,
      `sync_status` VARCHAR(20) DEFAULT 'Synced',
      INDEX `idx_chk_company` (`company_id`),
      INDEX `idx_chk_project` (`project_id`),
      FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
      FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "Creating project_signatures...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `project_signatures` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `project_id` INT NOT NULL,
      `client_name` VARCHAR(150) NOT NULL,
      `signature_path` VARCHAR(255) NOT NULL,
      `signed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `client_uuid` VARCHAR(36) DEFAULT NULL,
      `created_offline_at` DATETIME DEFAULT NULL,
      `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `sync_status` VARCHAR(20) DEFAULT 'Synced',
      INDEX `idx_sig_company` (`company_id`),
      INDEX `idx_sig_project` (`project_id`),
      FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
      FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "SUCCESS: Migration V11 tables created successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
