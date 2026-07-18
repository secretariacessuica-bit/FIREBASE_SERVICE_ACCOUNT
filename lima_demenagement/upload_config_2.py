import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

config_content = """<?php
define('SECURE_DB_HOST', '6o9v7p.myd.infomaniak.com'); 
define('SECURE_DB_NAME', '6o9v7p_erp');
define('SECURE_DB_USER', '6o9v7p_erp');
define('SECURE_DB_PASS', 'Ces124578.');
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    remote_path = 'sites/private_lima/config.php'
    
    with sftp.file(remote_path, 'w') as f:
        f.write(config_content)
        
    print("config.php updated with 6o9v7p_erp.")
    
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
