import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    print("Files in web root:", sftp.listdir('sites/limasolutions.ch'))
    sftp.close()
    transport.close()
except Exception as e:
    print("Failed:", str(e))
