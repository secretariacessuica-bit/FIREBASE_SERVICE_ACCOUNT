import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    print("Connecting to SFTP...")
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    # Let's clean up all three temporary testing files we created on the remote server
    # 1. test_remote_db.php
    # 2. run_remote_migration.php
    # 3. test_remote_queue.php
    
    files = ['test_remote_db.php', 'run_remote_migration.php', 'test_remote_queue.php']
    for f in files:
        try:
            sftp.remove(f"sites/limasolutions.ch/{f}")
            print(f"Removed temporary remote file: {f}")
        except Exception as e:
            print(f"Failed to remove {f}: {e}")
            
    sftp.close()
    transport.close()
    print("Cleanup complete.")

except Exception as e:
    print("SFTP failed:", str(e))
