import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

test_script = """<?php
$configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
if (!file_exists($configPath)) {
    echo "private_lima/config.php does not exist!\\n";
    exit(1);
}
require_once $configPath;
echo "STRIPE_TEST_SECRET_KEY status: " . (defined('STRIPE_TEST_SECRET_KEY') ? "Defined (Starts with: " . substr(STRIPE_TEST_SECRET_KEY, 0, 8) . "...)" : "Not defined") . "\\n";
echo "STRIPE_TEST_WEBHOOK_SECRET status: " . (defined('STRIPE_TEST_WEBHOOK_SECRET') ? "Defined (Starts with: " . substr(STRIPE_TEST_WEBHOOK_SECRET, 0, 9) . "...)" : "Not defined") . "\\n";
echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : "Not defined") . "\\n";
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    with sftp.file('sites/limasolutions.ch/admin/chk_conf.php', 'w') as f:
        f.write(test_script)
    sftp.close()
    transport.close()

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, port, username, password)
    stdin, stdout, stderr = ssh.exec_command("php sites/limasolutions.ch/admin/chk_conf.php")
    print(stdout.read().decode())
    
    sftp_conn = ssh.open_sftp()
    sftp_conn.remove('sites/limasolutions.ch/admin/chk_conf.php')
    sftp_conn.close()
    ssh.close()
except Exception as e:
    print("Failed:", str(e))
