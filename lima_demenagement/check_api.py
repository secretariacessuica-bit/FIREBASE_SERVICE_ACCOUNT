import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    # Check api/v1 subfolders on server
    for sub in ['invoices', 'payments', 'reports', 'timesheets', 'projects', 'quotes', 'crm']:
        path = f'sites/limasolutions.ch/api/v1/{sub}'
        try:
            files = sftp.listdir(path)
            print(f"{path}: {files}")
        except Exception as e:
            print(f"{path}: NOT FOUND - {e}")

    # Check .htaccess in api/
    try:
        with sftp.file('sites/limasolutions.ch/api/.htaccess', 'r') as f:
            print("\napi/.htaccess content:")
            print(f.read().decode('utf-8'))
    except Exception as e:
        print(f"\napi/.htaccess: {e}")

    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
