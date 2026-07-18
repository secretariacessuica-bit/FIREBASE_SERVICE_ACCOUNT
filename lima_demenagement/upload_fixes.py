import paramiko
import os

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

base_local = r'C:\Users\Wande\Documents\ia\lima_demenagement\public_site'

# Files to upload
uploads = [
    # CSP fix
    ('api/v1/config.php', 'api/v1/config.php'),
    # Module files (billing already has correct absolute paths)
    ('modules/timesheets/views/billing.php', 'modules/timesheets/views/billing.php'),
    ('modules/timesheets/views/list.php', 'modules/timesheets/views/list.php'),
    ('modules/projects/views/list.php', 'modules/projects/views/list.php'),
    ('modules/projects/views/form.php', 'modules/projects/views/form.php'),
]

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    # Create minimal favicon.ico (1x1 transparent pixel ICO)
    # This is a valid minimal ICO file (1x1 pixel)
    ico_bytes = bytes([
        0x00,0x00,0x01,0x00,0x01,0x00,0x10,0x10,
        0x00,0x00,0x01,0x00,0x20,0x00,0x68,0x04,
        0x00,0x00,0x16,0x00,0x00,0x00,0x28,0x00,
        0x00,0x00,0x10,0x00,0x00,0x00,0x20,0x00,
        0x00,0x00,0x01,0x00,0x20,0x00,0x00,0x00,
        0x00,0x00,0x40,0x04,0x00,0x00,0x00,0x00,
        0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,
        0x00,0x00,0x00,0x00,0x00,0x00
    ] + [0x00] * 1088)

    # Upload favicon
    with sftp.file('sites/limasolutions.ch/favicon.ico', 'wb') as f:
        f.write(ico_bytes)
    print("Uploaded: favicon.ico")

    # Upload other files
    for local_rel, remote_rel in uploads:
        local_path = os.path.join(base_local, local_rel.replace('/', os.sep))
        remote_path = 'sites/limasolutions.ch/' + remote_rel
        if os.path.exists(local_path):
            sftp.put(local_path, remote_path)
            print(f"Uploaded: {remote_rel}")
        else:
            print(f"NOT FOUND locally: {local_path}")

    # Cleanup temp diagnostic files
    for tmp in ['sites/limasolutions.ch/admin/diag2.php', 'sites/limasolutions.ch/admin/migrate2.php']:
        try:
            sftp.remove(tmp)
            print(f"Deleted: {tmp}")
        except:
            pass

    sftp.close()
    transport.close()
    print("\nAll done.")
except Exception as e:
    print("SFTP failed:", str(e))
