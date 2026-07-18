import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

test_script = """<?php
$path1 = dirname(__DIR__, 2) . '/private_lima/config.php';
$path2 = dirname(__DIR__, 2) . '/private/config.php';

echo "Path 1 (private_lima) exists: " . (file_exists($path1) ? "YES" : "NO") . " (" . realpath($path1) . ")\\n";
echo "Path 2 (private) exists: " . (file_exists($path2) ? "YES" : "NO") . " (" . realpath($path2) . ")\\n";
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    with sftp.file('sites/limasolutions.ch/admin/find_conf.php', 'w') as f:
        f.write(test_script)
    sftp.close()
    transport.close()

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, port, username, password)
    stdin, stdout, stderr = ssh.exec_command("php sites/limasolutions.ch/admin/find_conf.php")
    print(stdout.read().decode())
    
    sftp_conn = ssh.open_sftp()
    sftp_conn.remove('sites/limasolutions.ch/admin/find_conf.php')
    sftp_conn.close()
    ssh.close()
except Exception as e:
    print("Failed:", str(e))
