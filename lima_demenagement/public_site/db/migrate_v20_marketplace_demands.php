<?php
// LIMA Solutions ERP - Database Migration v20
// Adds Marketplace Demands (Preciso de) tables

require_once __DIR__ . '/../api/config.php';

echo "<h1>Migration v20 - Marketplace Demands</h1>";

try {
    // 1. Marketplace Demands Table
    $sql1 = "CREATE TABLE IF NOT EXISTS `marketplace_demands` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `company_id` INT NOT NULL,
      `client_id` INT NOT NULL,
      `category_id` INT DEFAULT NULL,
      `keywords` VARCHAR(255) DEFAULT NULL,
      `max_price` DECIMAL(10,2) DEFAULT NULL,
      `location` VARCHAR(255) DEFAULT NULL,
      `notify_email` TINYINT(1) DEFAULT 1,
      `status` ENUM('active', 'inactive') DEFAULT 'active',
      `expires_at` TIMESTAMP NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_m_demands_company` (`company_id`),
      INDEX `idx_m_demands_client` (`client_id`),
      FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`category_id`) REFERENCES `marketplace_categories` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql1);
    echo "<p>Table 'marketplace_demands' ensured.</p>";

    // 2. Marketplace Demand Matches Table
    $sql2 = "CREATE TABLE IF NOT EXISTS `marketplace_demand_matches` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `demand_id` INT NOT NULL,
      `item_id` INT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY `uniq_demand_item` (`demand_id`, `item_id`),
      FOREIGN KEY (`demand_id`) REFERENCES `marketplace_demands` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql2);
    echo "<p>Table 'marketplace_demand_matches' ensured.</p>";

    echo "<p><b>Migration v20 completed successfully!</b></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Migration failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
