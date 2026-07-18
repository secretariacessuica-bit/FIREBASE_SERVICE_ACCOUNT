<?php
// LIMA Solutions ERP - Database Migration v21
// Adds Marketplace Reservations (Waitlist Queue) tables

require_once __DIR__ . '/../api/config.php';

echo "<h1>Migration v21 - Marketplace Reservations Queue</h1>";

try {
    // 1. Marketplace Reservations Queue Table
    $sql = "CREATE TABLE IF NOT EXISTS `marketplace_reservations` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `item_id` INT NOT NULL,
      `client_id` INT DEFAULT NULL, -- Null if guest
      `name` VARCHAR(150) NOT NULL,
      `email` VARCHAR(150) NOT NULL,
      `phone` VARCHAR(30) DEFAULT NULL,
      `position` INT NOT NULL,
      `status` ENUM('active', 'waiting', 'expired', 'completed') DEFAULT 'waiting',
      `expires_at` TIMESTAMP NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_m_res_item` (`item_id`),
      INDEX `idx_m_res_email` (`email`),
      FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "<p>Table 'marketplace_reservations' ensured.</p>";

    echo "<p><b>Migration v21 completed successfully!</b></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Migration failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
