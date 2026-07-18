import paramiko
import urllib.request

php = """<?php
header('Content-Type: text/plain; charset=utf-8');
require_once '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';
$pdo = new PDO('mysql:host='.SECURE_DB_HOST.';dbname='.SECURE_DB_NAME.';charset=utf8mb4', SECURE_DB_USER, SECURE_DB_PASS);
foreach (['payments', 'invoices', 'quotes', 'invoice_items'] as $table) {
    $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
    echo "$table: " . implode(', ', $cols) . "\\n\\n";
}
"""

transport = paramiko.Transport(('6o9v7p.ftp.infomaniak.com', 22))
transport.connect(username='6o9v7p_admin', password='Ces124578.')
sftp = paramiko.SFTPClient.from_transport(transport)
with sftp.file('sites/limasolutions.ch/admin/chk_pay.php', 'w') as f:
    f.write(php)
sftp.close()
transport.close()

print(urllib.request.urlopen('https://limasolutions.ch/admin/chk_pay.php').read().decode())
