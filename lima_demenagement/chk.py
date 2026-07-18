import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

# Check actual invoices columns and payments columns
php_check = """<?php
header('Content-Type: text/plain; charset=utf-8');
$configPath = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';
require_once $configPath;

$pdo = new PDO(
    "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
    SECURE_DB_USER, SECURE_DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$invCols = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);
echo "invoices: " . implode(', ', $invCols) . "\\n\\n";

// Check what's missing for reports
foreach (['paid_amount', 'cancelled_at', 'sent_at'] as $col) {
    echo "invoices.$col: " . (in_array($col, $invCols) ? 'OK' : 'MISSING') . "\\n";
}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    with sftp.file('sites/limasolutions.ch/admin/chk.php', 'w') as f:
        f.write(php_check)
    print("chk.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
