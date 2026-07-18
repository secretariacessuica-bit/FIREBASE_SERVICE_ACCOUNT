import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

php_code = """<?php
header('Content-Type: text/plain; charset=utf-8');
require_once dirname(__DIR__, 2) . '/private_lima/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
        SECURE_DB_USER, SECURE_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $companyId = 1;
    $missing = ['payments', 'projects', 'timesheets', 'reports'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO company_modules (company_id, module_name, enabled) VALUES (:cid, :mod, 1)");
    foreach ($missing as $mod) {
        $stmt->execute(['cid' => $companyId, 'mod' => $mod]);
    }

    $rows = $pdo->query("SELECT module_name, enabled FROM company_modules WHERE company_id = 1")->fetchAll(PDO::FETCH_ASSOC);
    echo "Modules for company 1:\\n";
    foreach ($rows as $r) echo " - " . $r['module_name'] . " => " . ($r['enabled'] ? 'ON' : 'OFF') . "\\n";
    echo "\\nDone. Total: " . count($rows) . " modules.\\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    with sftp.file('sites/limasolutions.ch/admin/fix_modules.php', 'w') as f:
        f.write(php_code)
    print("fix_modules.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
