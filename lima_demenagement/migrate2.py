import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

php_migrate = """<?php
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

$configPath = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';
require_once $configPath;

try {
    $pdo = new PDO(
        "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
        SECURE_DB_USER, SECURE_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $migrations = [
        // timesheets missing columns
        "ALTER TABLE `timesheets` ADD COLUMN IF NOT EXISTS `task_id` INT DEFAULT NULL",
        "ALTER TABLE `timesheets` ADD COLUMN IF NOT EXISTS `locked` TINYINT(1) DEFAULT 0",
        
        // projects missing column
        "ALTER TABLE `projects` ADD COLUMN IF NOT EXISTS `hourly_rate` DECIMAL(10,2) DEFAULT 0.00",
        
        // audit_log table (needed by audit_helper.php)
        "CREATE TABLE IF NOT EXISTS `audit_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `company_id` INT DEFAULT NULL,
            `user_id` INT DEFAULT NULL,
            `action` VARCHAR(100) NOT NULL,
            `entity_type` VARCHAR(50) DEFAULT NULL,
            `entity_id` INT DEFAULT NULL,
            `details` TEXT DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
            echo "OK: " . substr($sql, 0, 60) . "...\\n";
        } catch (PDOException $e) {
            echo "ERR: " . $e->getMessage() . "\\n";
        }
    }

    // Verify
    $tsCol = $pdo->query("SHOW COLUMNS FROM timesheets")->fetchAll(PDO::FETCH_COLUMN);
    echo "\\ntimesheets.task_id: " . (in_array('task_id', $tsCol) ? 'OK' : 'STILL MISSING') . "\\n";
    echo "timesheets.locked: " . (in_array('locked', $tsCol) ? 'OK' : 'STILL MISSING') . "\\n";

    $prCol = $pdo->query("SHOW COLUMNS FROM projects")->fetchAll(PDO::FETCH_COLUMN);
    echo "projects.hourly_rate: " . (in_array('hourly_rate', $prCol) ? 'OK' : 'STILL MISSING') . "\\n";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'audit_log'")->fetchAll(PDO::FETCH_COLUMN);
    echo "audit_log table: " . (count($tables) > 0 ? 'OK' : 'STILL MISSING') . "\\n";

} catch (Throwable $e) {
    echo "FATAL: " . $e->getMessage();
}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    with sftp.file('sites/limasolutions.ch/admin/migrate2.php', 'w') as f:
        f.write(php_migrate)
    print("migrate2.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
