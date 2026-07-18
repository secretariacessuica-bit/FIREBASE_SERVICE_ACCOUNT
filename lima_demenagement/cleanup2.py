import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

temp_files = [
    'sites/limasolutions.ch/api/v1/invoices/debug_invoices.php',
    'sites/limasolutions.ch/api/v1/invoices/check_cols.php',
    'sites/limasolutions.ch/api/v1/invoices/fix_cols.php',
]

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    for f in temp_files:
        try:
            sftp.remove(f)
            print(f"Deleted: {f}")
        except:
            print(f"Not found (skip): {f}")

    print("Cleanup done.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
