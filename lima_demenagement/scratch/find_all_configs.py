import paramiko
import os

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

def find_files(sftp, remote_dir):
    try:
        for entry in sftp.listdir_attr(remote_dir):
            path = remote_dir + '/' + entry.filename
            if entry.st_mode & 0o040000: # Directory
                find_files(sftp, path)
            else:
                if 'config.php' in entry.filename:
                    print(f"Found: {path} (Size: {entry.st_size})")
    except Exception as e:
        pass

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    print("Searching for config.php files...")
    find_files(sftp, '.')
    sftp.close()
    transport.close()
except Exception as e:
    print("Failed:", str(e))
