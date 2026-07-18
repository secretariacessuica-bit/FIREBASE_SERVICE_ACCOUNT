import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    print("Connecting to SFTP...")
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    # Write a script to execute the migration locally on the remote server
    migration_runner = f"""<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../private_lima/config.php';

try {{
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Running migration query...\\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `marketplace_reservations` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `item_id` INT NOT NULL,
      `client_id` INT DEFAULT NULL,
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
    echo "Migration completed successfully! Table 'marketplace_reservations' created.\\n";
}} catch (Exception $e) {{
    echo "Migration Failed: " . $e->getMessage() . "\\n";
}}
"""
    
    print("Writing remote migration execution runner...")
    with sftp.open('sites/limasolutions.ch/run_remote_migration.php', 'w') as f:
        f.write(migration_runner)
        
    sftp.close()
    transport.close()
    print("SFTP operations complete.")

except Exception as e:
    print("SFTP failed:", str(e))
