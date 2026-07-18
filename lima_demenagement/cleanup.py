import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

files_to_remove = [
    'sites/limasolutions.ch/admin/db_diagnostic.php',
    'sites/limasolutions.ch/admin/run_schema.php',
    'sites/limasolutions.ch/admin/fix_tables.php',
]

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    for f in files_to_remove:
        try:
            sftp.remove(f)
            print(f"Deleted: {f}")
        except Exception as e:
            print(f"Could not delete {f}: {e}")
    
    sftp.close()
    transport.close()
    print("Cleanup complete.")
except Exception as e:
    print("SFTP failed:", str(e))
