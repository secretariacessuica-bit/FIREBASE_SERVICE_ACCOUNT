import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

# Fix the missing tables with correct foreign key order
fix_sql = """
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `client_id` INT NOT NULL,
  `quote_id` INT DEFAULT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `billing_batch_id` VARCHAR(64) DEFAULT NULL,
  `status` VARCHAR(30) DEFAULT 'draft',
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `subtotal` DECIMAL(12,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) DEFAULT 0.00,
  `total` DECIMAL(12,2) DEFAULT 0.00,
  `currency` VARCHAR(10) DEFAULT 'CHF',
  `notes` TEXT DEFAULT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  INDEX `idx_inv_billing_batch` (`billing_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,2) DEFAULT 1.00,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `tax_rate` DECIMAL(5,2) DEFAULT 7.70,
  `total` DECIMAL(12,2) NOT NULL,
  `sort_order` INT DEFAULT 0,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `invoice_id` INT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `method` VARCHAR(50) DEFAULT 'bank_transfer',
  `reference` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `timesheets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `project_id` INT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `client_id` INT DEFAULT NULL,
  `work_date` DATE NOT NULL,
  `hours` DECIMAL(5,2) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `hourly_rate` DECIMAL(10,2) DEFAULT 0.00,
  `billable` TINYINT(1) DEFAULT 1,
  `billed` TINYINT(1) DEFAULT 0,
  `billing_batch_id` VARCHAR(64) DEFAULT NULL,
  `invoice_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_ts_billing_batch` (`billing_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
"""

php_exec = f"""<?php
header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__, 2) . '/private_lima/config.php';

try {{
    $pdo = new PDO(
        "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
        SECURE_DB_USER,
        SECURE_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true]
    );

    $sql = <<<'ENDSQL'
{fix_sql}
ENDSQL;

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $ok = 0; $errors = [];
    foreach ($statements as $stmt) {{
        if (empty($stmt)) continue;
        try {{
            $pdo->exec($stmt);
            $ok++;
        }} catch (PDOException $e) {{
            $errors[] = $e->getMessage();
        }}
    }}

    echo "Executed: $ok statements\\n";
    if (!empty($errors)) {{
        foreach ($errors as $err) echo "ERROR: $err\\n";
    }} else {{
        echo "ALL MISSING TABLES CREATED SUCCESSFULLY!\\n";
    }}

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\\nTotal tables: " . count($tables) . "\\n";
    foreach ($tables as $t) echo " - $t\\n";

}} catch (PDOException $e) {{
    echo "Error: " . $e->getMessage();
}}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    remote_path = 'sites/limasolutions.ch/admin/fix_tables.php'
    with sftp.file(remote_path, 'w') as f:
        f.write(php_exec)

    print("fix_tables.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
