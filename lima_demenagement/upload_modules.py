import paramiko
import os

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

base_local = r'C:\Users\Wande\Documents\ia\lima_demenagement\public_site\modules'
base_remote = 'sites/limasolutions.ch/modules'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    count = 0
    for root, dirs, files in os.walk(base_local):
        for file in files:
            if not file.endswith('.php'):
                continue
            local_path = os.path.join(root, file)
            rel = os.path.relpath(local_path, base_local).replace('\\', '/')
            remote_path = base_remote + '/' + rel
            try:
                sftp.put(local_path, remote_path)
                print(f"Uploaded: {rel}")
                count += 1
            except Exception as e:
                print(f"FAILED: {rel} - {e}")

    sftp.close()
    transport.close()
    print(f"\nDone. {count} files uploaded.")
except Exception as e:
    print("SFTP failed:", str(e))
