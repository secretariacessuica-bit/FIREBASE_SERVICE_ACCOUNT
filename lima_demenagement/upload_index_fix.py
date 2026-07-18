import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    # Upload fixed index.php
    sftp.put(
        r'C:\Users\Wande\Documents\ia\lima_demenagement\public_site\admin\index.php',
        'sites/limasolutions.ch/admin/index.php'
    )
    print("index.php uploaded.")

    # Cleanup temp files
    for f in ['sites/limasolutions.ch/admin/fix_modules.php']:
        try:
            sftp.remove(f)
            print(f"Deleted {f}")
        except:
            pass

    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
