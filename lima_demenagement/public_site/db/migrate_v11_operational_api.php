<?php
// LIMA Solutions ERP - Migrate V11 - Operational Mobile API Tables
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: application/json; charset=utf-8');

// Permite apenas admin ou super_admin executar migrações se houver sessão ativa
$role = $_SESSION['user_role'] ?? '';
if (!empty($role) && !in_array($role, ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès interdit.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. mobile_tokens
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

    // 2. operational_assignments
    $pdo->exec("CREATE TABLE IF NOT EXISTS `operational_assignments` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `project_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `status` VARCHAR(30) DEFAULT 'Pending', -- Pending, Active, Completed, Cancelled
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

    // 3. gps_tracking
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

    // 4. project_photos
    $pdo->exec("CREATE TABLE IF NOT EXISTS `project_photos` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `project_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `photo_type` VARCHAR(30) NOT NULL, -- pre_move, post_move, incident
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

    // 5. project_checklists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `project_checklists` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `project_id` INT NOT NULL,
      `item_name` VARCHAR(150) NOT NULL,
      `status` VARCHAR(30) DEFAULT 'Pending', -- Pending, Checked, Damaged, Missing
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

    // 6. project_signatures
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

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Migration V11 completed successfully.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Migration failed: ' . $e->getMessage()]);
}
