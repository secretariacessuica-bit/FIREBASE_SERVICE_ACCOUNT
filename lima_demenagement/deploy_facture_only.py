import paramiko
import os

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'
remote_base = 'sites/limasolutions.ch'

files_to_deploy = [
    ('facture/index.html', 'facture/index.html'),
    ('facture/app.js',     'facture/app.js'),
    ('facture/style.css',  'facture/style.css'),
]

local_root = r'C:\Users\Wande\Documents\ia\lima_demenagement\public_site'

try:
    print("Connecting to SFTP...")
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    for remote_rel, local_rel in files_to_deploy:
        local_path  = os.path.join(local_root, local_rel.replace('/', os.sep))
        remote_path = f"{remote_base}/{remote_rel}"
        print(f"Uploading {local_rel} ...")
        sftp.put(local_path, remote_path)
        print("  OK")

    sftp.close()
    transport.close()
    print("\nDeploy completed!")

except Exception as e:
    print("SFTP error:", str(e))
