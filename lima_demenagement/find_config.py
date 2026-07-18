import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    print("\nListing directories in /home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/ :")
    try:
        for f in sftp.listdir('/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima'):
            print(f"  {f}")
    except Exception as e:
        print(f"  Error: {e}")

    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", e)
