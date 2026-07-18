import paramiko
transport = paramiko.Transport(('6o9v7p.ftp.infomaniak.com', 22))
transport.connect(username='6o9v7p_admin', password='Ces124578.')
sftp = paramiko.SFTPClient.from_transport(transport)
try:
    print('--- ADMIN ---')
    print(sftp.listdir('sites/limasolutions.ch/admin'))
    print('--- LOGS ---')
    print(sftp.listdir('logs'))
except Exception as e:
    print(e)
sftp.close()
transport.close()
