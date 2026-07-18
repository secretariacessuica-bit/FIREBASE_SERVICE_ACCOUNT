import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

php_migrate = """<?php
header('Content-Type: text/plain; charset=utf-8');
require_once dirname(__DIR__, 4) . '/private_lima/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
        SECURE_DB_USER, SECURE_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $migrations = [
        // invoices missing columns
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `updated_by` INT DEFAULT NULL",
        
        // payments missing columns
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `reversed_at` TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `receipt_path` VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        
        // timesheets missing columns
        "ALTER TABLE `timesheets` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE `timesheets` ADD COLUMN IF NOT EXISTS `status` VARCHAR(30) DEFAULT 'Draft'",
        
        // quotes - already has deleted_at, just checking
        "ALTER TABLE `quotes` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL",
    ];

    $ok = 0; $errors = [];
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
            $ok++;
            echo "OK: $sql\\n";
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
            echo "ERR: " . $e->getMessage() . "\\n";
        }
    }

    echo "\\nCompleted: $ok OK, " . count($errors) . " errors\\n";

} catch (Exception $e) {
    echo "Fatal: " . $e->getMessage();
}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    with sftp.file('sites/limasolutions.ch/api/v1/invoices/fix_cols.php', 'w') as f:
        f.write(php_migrate)
    print("fix_cols.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
