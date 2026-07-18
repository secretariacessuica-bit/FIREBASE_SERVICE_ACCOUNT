import paramiko
import os

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'
remote_dir = 'sites/limasolutions.ch'
local_dir = r'C:\Users\Wande\Documents\ia\lima_demenagement\public_site'

try:
    print("Connecting to SFTP...")
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    print("1. Cleaning up broken files...")
    # Delete files with backslashes
    try:
        for f in sftp.listdir(remote_dir):
            if '\\' in f or f == 'public_site.zip':
                try:
                    sftp.remove(remote_dir + '/' + f)
                    print(f"Deleted: {f}")
                except Exception as e:
                    print(f"Failed to delete {f}: {e}")
    except Exception as e:
        print(f"Error listing {remote_dir}: {e}")

    print("\n2. Uploading fresh files from public_site...")
    # Change to target directory
    sftp.chdir(remote_dir)
    base_remote = sftp.getcwd()
    
    for root, dirs, files in os.walk(local_dir):
        rel_path = os.path.relpath(root, local_dir).replace('\\', '/')
        if rel_path == '.':
            remote_path = base_remote
        else:
            remote_path = base_remote + '/' + rel_path
            
        try:
            sftp.stat(remote_path)
        except IOError:
            print(f"Creating directory: {remote_path}")
            sftp.mkdir(remote_path)

        for file in files:
            local_file = os.path.join(root, file)
            remote_file = remote_path + '/' + file
            print(f"Uploading: {rel_path}/{file}")
            sftp.put(local_file, remote_file)

    sftp.close()
    transport.close()
    print("\nDEPLOYMENT COMPLETED SUCCESSFULLY!")

except Exception as e:
    print("SFTP failed:", str(e))
