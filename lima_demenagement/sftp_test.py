import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    path = 'sites/private_lima/config.php'
    print(f"Checking {path}:")
    try:
        stat = sftp.stat(path)
        print("File exists, size:", stat.st_size)
    except Exception as e:
        print("File not found or error:", e)

    try:
        print("Files in private_lima:")
        for f in sftp.listdir('sites/private_lima'):
            print(" -", f)
    except Exception as e:
        pass

    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
