import paramiko
import time

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    stat = sftp.stat('sites/private_lima/config.php')
    print("Size:", stat.st_size)
    print("Last modified:", time.ctime(stat.st_mtime))
    sftp.close()
    transport.close()
except Exception as e:
    print("Failed:", str(e))
