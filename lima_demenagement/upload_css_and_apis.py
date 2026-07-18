import paramiko
import os

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

base_local = r'C:\Users\Wande\Documents\ia\lima_demenagement\public_site'
base_remote = 'sites/limasolutions.ch'

files_to_upload = [
    # New CSS file
    ('admin/css/admin.css', 'admin/css/admin.css'),
    # Updated index.php (null guards)
    ('admin/index.php', 'admin/index.php'),
    # APIs causing 500
    ('api/v1/invoices/invoices.php', 'api/v1/invoices/invoices.php'),
    ('api/v1/payments/payments.php', 'api/v1/payments/payments.php'),
    ('api/v1/reports/reports.php', 'api/v1/reports/reports.php'),
]

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    # Create css directory if needed
    try:
        sftp.stat(base_remote + '/admin/css')
    except IOError:
        sftp.mkdir(base_remote + '/admin/css')
        print("Created /admin/css directory")

    for local_rel, remote_rel in files_to_upload:
        local_path = os.path.join(base_local, local_rel.replace('/', os.sep))
        remote_path = base_remote + '/' + remote_rel
        sftp.put(local_path, remote_path)
        print(f"Uploaded: {remote_rel}")

    sftp.close()
    transport.close()
    print("\nAll files uploaded successfully.")
except Exception as e:
    print("SFTP failed:", str(e))
