import paramiko
import sys

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

php_code = """<?php
header('Content-Type: text/plain; charset=utf-8');
require_once dirname(__DIR__, 2) . '/private_lima/config.php';

try {
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Add ip_address column to crm_leads
    $pdo->exec("ALTER TABLE crm_leads ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER referer_url");
    echo "Column `ip_address` added to `crm_leads` successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
"""

try:
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(hostname=host, port=port, username=username, password=password)
    
    sftp = ssh.open_sftp()
    remote_path = 'sites/limasolutions.ch/db/add_ip_col.php'
    with sftp.file(remote_path, 'w') as f:
        f.write(php_code)
    sftp.close()
    
    stdin, stdout, stderr = ssh.exec_command("php /home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/db/add_ip_col.php")
    print("STDOUT:", stdout.read().decode('utf-8'))
    print("STDERR:", stderr.read().decode('utf-8'))
    
    # Delete the script
    sftp = ssh.open_sftp()
    sftp.remove(remote_path)
    sftp.close()
    
    ssh.close()
except Exception as e:
    print("Error:", str(e))
