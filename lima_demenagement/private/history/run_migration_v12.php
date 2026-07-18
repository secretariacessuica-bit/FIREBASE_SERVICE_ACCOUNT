<?php
// LIMA Solutions ERP - Direct Schema/Table Inserter for Marketplace MVP
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

    echo "Creating marketplace_categories...\n";
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
        echo "Categories seeded successfully.\n";
    }

    echo "Creating marketplace_items...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `marketplace_items` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `client_id` INT NOT NULL,
      `category_id` INT NOT NULL,
      `title` VARCHAR(150) NOT NULL,
      `description` TEXT NOT NULL,
      `price` DECIMAL(10,2) DEFAULT NULL,
      `location` VARCHAR(150) NOT NULL,
      `status` VARCHAR(30) DEFAULT 'Pending',
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

    echo "Creating marketplace_photos...\n";
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

    echo "Creating marketplace_interests...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `marketplace_interests` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `item_id` INT NOT NULL,
      `client_id` INT DEFAULT NULL,
      `name` VARCHAR(150) NOT NULL,
      `email` VARCHAR(150) NOT NULL,
      `phone` VARCHAR(30) DEFAULT NULL,
      `message` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_m_interests_item` (`item_id`),
      FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "SUCCESS: Marketplace tables created successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
