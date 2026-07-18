import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

test_script = """<?php
$configPath = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';
$content = file_get_contents($configPath);
if (strpos($content, 'STRIPE') !== false) {
    echo "STRIPE exists in config.php!\\n";
    // Print lines containing STRIPE
    $lines = explode("\\n", $content);
    foreach ($lines as $line) {
        if (strpos($line, 'STRIPE') !== false) {
            echo "Line: " . trim($line) . "\\n";
        }
    }
} else {
    echo "STRIPE does NOT exist in config.php.\\n";
}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    with sftp.file('sites/limasolutions.ch/admin/search_conf.php', 'w') as f:
        f.write(test_script)
    sftp.close()
    transport.close()

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, port, username, password)
    stdin, stdout, stderr = ssh.exec_command("php sites/limasolutions.ch/admin/search_conf.php")
    print(stdout.read().decode())
    
    sftp_conn = ssh.open_sftp()
    sftp_conn.remove('sites/limasolutions.ch/admin/search_conf.php')
    sftp_conn.close()
    ssh.close()
except Exception as e:
    print("Failed:", str(e))
