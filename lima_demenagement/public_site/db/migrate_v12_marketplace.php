<?php
// LIMA Solutions ERP - Migrate V12 - Marketplace MVP Tables
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
    // 1. marketplace_categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS `marketplace_categories` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Seed categories if empty
    $chk = $pdo->query("SELECT COUNT(*) FROM marketplace_categories")->fetchColumn();
    if ($chk == 0) {
        $pdo->exec("INSERT INTO marketplace_categories (name) VALUES 
          ('Móveis Usados'),
          ('Móveis Seminovos'),
          ('Doações')");
    }

    // 2. marketplace_items
    $pdo->exec("CREATE TABLE IF NOT EXISTS `marketplace_items` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `client_id` INT NOT NULL,
      `category_id` INT NOT NULL,
      `title` VARCHAR(150) NOT NULL,
      `description` TEXT NOT NULL,
      `price` DECIMAL(10,2) DEFAULT NULL, -- NULL indicates donation
      `location` VARCHAR(150) NOT NULL,
      `status` VARCHAR(30) DEFAULT 'Pending', -- Draft, Pending, Approved, Rejected, Archived
      `rejection_reason` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_m_items_company` (`company_id`),
      INDEX `idx_m_items_client` (`client_id`),
      INDEX `idx_m_items_status` (`status`),
      FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
      FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`category_id`) REFERENCES `marketplace_categories` (`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 3. marketplace_photos
    $pdo->exec("CREATE TABLE IF NOT EXISTS `marketplace_photos` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `item_id` INT NOT NULL,
      `filename` VARCHAR(255) NOT NULL,
      `original_name` VARCHAR(255) NOT NULL,
      `mime_type` VARCHAR(100) NOT NULL,
      `size` INT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_m_photos_item` (`item_id`),
      FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 4. marketplace_interests
    $pdo->exec("CREATE TABLE IF NOT EXISTS `marketplace_interests` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `item_id` INT NOT NULL,
      `client_id` INT DEFAULT NULL, -- Null if non-authenticated public user
      `name` VARCHAR(150) NOT NULL,
      `email` VARCHAR(150) NOT NULL,
      `phone` VARCHAR(30) DEFAULT NULL,
      `message` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_m_interests_item` (`item_id`),
      FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo json_encode(['success' => true, 'message' => 'Migration V12 completed successfully. Categories seeded.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Migration failed: ' . $e->getMessage()]);
}
