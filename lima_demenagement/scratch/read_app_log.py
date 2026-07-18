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
    
    # Read the log file from the private directory
    path = 'sites/private_lima/logs/application.log'
    print(f"Reading {path}:")
    try:
        with sftp.open(path, 'r') as f:
            lines = f.readlines()
            # Show last 10 lines
            for line in lines[-10:]:
                print(line.strip())
    except Exception as e:
        print("Error reading log:", e)
        
    sftp.close()
    transport.close()
    print("SFTP operations complete.")

except Exception as e:
    print("SFTP failed:", str(e))
