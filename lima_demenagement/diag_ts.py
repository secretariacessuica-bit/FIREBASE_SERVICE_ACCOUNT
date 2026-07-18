import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

php_diag = """<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$configPath = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';
require_once $configPath;

try {
    $pdo = new PDO(
        "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
        SECURE_DB_USER, SECURE_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    foreach (['timesheets', 'projects', 'project_tasks'] as $table) {
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        echo "$table: " . implode(', ', $cols) . "\\n\\n";
    }

    $tsCol = $pdo->query("SHOW COLUMNS FROM timesheets")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['task_id', 'locked', 'status', 'deleted_at', 'invoice_id'] as $col) {
        echo "timesheets.$col: " . (in_array($col, $tsCol) ? 'OK' : 'MISSING') . "\\n";
    }
    echo "\\n";

    $prCol = $pdo->query("SHOW COLUMNS FROM projects")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['project_code', 'deleted_at', 'status', 'client_id', 'hourly_rate'] as $col) {
        echo "projects.$col: " . (in_array($col, $prCol) ? 'OK' : 'MISSING') . "\\n";
    }

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " in " . basename($e->getFile()) . ":" . $e->getLine();
}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    with sftp.file('sites/limasolutions.ch/admin/diag2.php', 'w') as f:
        f.write(php_diag)
    print("diag2.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
