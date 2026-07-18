import paramiko
import os
import urllib.request

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'
base_local = r'c:\Users\Wande\Documents\ia\lima_demenagement\public_site'

uploads = [
    ('modules/reports/model/Report.php', 'modules/reports/model/Report.php'),
]

transport = paramiko.Transport((host, port))
transport.connect(username=username, password=password)
sftp = paramiko.SFTPClient.from_transport(transport)

for local_rel, remote_rel in uploads:
    local_path = os.path.join(base_local, local_rel.replace('/', os.sep))
    remote_path = 'sites/limasolutions.ch/' + remote_rel
    sftp.put(local_path, remote_path)
    print(f'Uploaded: {remote_rel}')

with open(r'c:\Users\Wande\Documents\ia\lima_demenagement\scratch\migrate_prod_sync.php', 'r', encoding='utf-8') as f:
    sftp.file('sites/limasolutions.ch/admin/migrate_prod_sync.php', 'w').write(f.read())
print('Uploaded: admin/migrate_prod_sync.php')

sftp.close()
transport.close()

print('\nRunning migration...')
print(urllib.request.urlopen('https://limasolutions.ch/admin/migrate_prod_sync.php').read().decode())

print('\nColumns after migration:')
print(urllib.request.urlopen('https://limasolutions.ch/admin/chk_pay.php').read().decode())
