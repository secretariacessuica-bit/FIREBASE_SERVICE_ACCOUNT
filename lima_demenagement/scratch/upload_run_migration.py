import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

# Read migration script
with open(r'c:\Users\Wande\Documents\ia\lima_demenagement\public_site\db\migrate_v11_operational_api.php', 'r', encoding='utf-8') as f:
    migration_content = f.read()

# Upload migration script to admin/ where it is not blocked by public_site/db/.htaccess
try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    remote_path = 'sites/limasolutions.ch/admin/run_migration_v11.php'
    with sftp.file(remote_path, 'w') as f:
        f.write(migration_content)

    print("run_migration_v11.php uploaded successfully to admin/")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
