import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

php_check = """<?php
header('Content-Type: text/plain; charset=utf-8');
require_once dirname(__DIR__, 4) . '/private_lima/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
        SECURE_DB_USER, SECURE_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    foreach (['invoices', 'payments', 'timesheets', 'quotes'] as $table) {
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        echo "$table columns: " . implode(', ', $cols) . "\\n\\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    with sftp.file('sites/limasolutions.ch/api/v1/invoices/check_cols.php', 'w') as f:
        f.write(php_check)
    print("check_cols.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
