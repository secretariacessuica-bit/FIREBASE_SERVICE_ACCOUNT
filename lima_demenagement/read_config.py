import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    with sftp.file('sites/private_lima/config.php', 'r') as f:
        content = f.read().decode('utf-8')
        # Mask password before printing
        import re
        content = re.sub(r"('SECURE_DB_PASS',\s*')[^']+(')", r"\1***\2", content)
        print(content)
        
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
