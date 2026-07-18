import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

# Read smoke test script
with open(r'c:\Users\Wande\Documents\ia\lima_demenagement\public_site\db\run_mobile_api_smoke_tests.php', 'r', encoding='utf-8') as f:
    smoke_content = f.read()

# Upload smoke test script to admin/
try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    remote_path = 'sites/limasolutions.ch/admin/run_mobile_api_smoke_tests.php'
    with sftp.file(remote_path, 'w') as f:
        f.write(smoke_content)

    print("run_mobile_api_smoke_tests.php uploaded successfully to admin/")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
