import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

php_migrate = """<?php
header('Content-Type: text/plain; charset=utf-8');
$configPath = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';
require_once $configPath;

$pdo = new PDO(
    "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
    SECURE_DB_USER, SECURE_DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$migrations = [
    // invoices missing computed columns
    "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `paid_amount` DECIMAL(12,2) DEFAULT 0.00",
    "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `balance_due` DECIMAL(12,2) DEFAULT 0.00",
    "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `cancelled_at` TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `sent_at` TIMESTAMP NULL DEFAULT NULL",
    
    // clients - check active column
    "ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `active` TINYINT(1) DEFAULT 1",
];

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 70) . "...\\n";
    } catch (PDOException $e) {
        echo "ERR: " . $e->getMessage() . "\\n";
    }
}

// Verify final columns
$cols = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);
echo "\\nFinal invoices columns:\\n " . implode(', ', $cols) . "\\n";

$clientCols = $pdo->query("SHOW COLUMNS FROM clients")->fetchAll(PDO::FETCH_COLUMN);
echo "\\nFinal clients columns:\\n " . implode(', ', $clientCols) . "\\n";
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    with sftp.file('sites/limasolutions.ch/admin/migrate3.php', 'w') as f:
        f.write(php_migrate)
    print("migrate3.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
