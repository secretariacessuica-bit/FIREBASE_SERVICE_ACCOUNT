import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    try:
        sftp.remove('sites/limasolutions.ch/admin/test_crm_profile.php')
        print("Deleted test_crm_profile.php")
    except Exception as e:
        print("Could not delete test_crm_profile.php:", e)
    
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", e)
