import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    local_path = r'C:\Users\Wande\Documents\ia\lima_demenagement\db_diagnostic.php'
    remote_path = 'sites/limasolutions.ch/admin/db_diagnostic.php'
    
    sftp.put(local_path, remote_path)
    print("Diagnostic file uploaded successfully.")
    
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
