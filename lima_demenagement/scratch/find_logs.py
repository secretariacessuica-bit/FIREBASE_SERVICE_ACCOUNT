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
    
    # Read the error log from the remote server
    # The PHP error logs are usually in sites/limasolutions.ch/error_log or similar
    # Let's inspect the files in limasolutions.ch root folder first to locate the log file
    print("Files in limasolutions.ch:")
    for f in sftp.listdir('sites/limasolutions.ch'):
        if 'log' in f.lower() or 'err' in f.lower():
            print(" -", f)
            
    sftp.close()
    transport.close()
    print("SFTP operations complete.")

except Exception as e:
    print("SFTP failed:", str(e))
