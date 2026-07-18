import paramiko
import sys

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

remote_dir = 'sites/limasolutions.ch/db'
local_file = r'C:\Users\Wande\Documents\ia\lima_demenagement\public_site\db\migrate_v10_leads.php'
remote_file = remote_dir + '/migrate_v10_leads.php'

try:
    print("Connecting to SFTP/SSH...")
    # Initialize SSH Client
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(hostname=host, port=port, username=username, password=password)
    
    # Use SFTP to upload the file
    sftp = ssh.open_sftp()
    print(f"Uploading {local_file} -> {remote_file}")
    sftp.put(local_file, remote_file)
    sftp.close()
    print("Upload completed.")

    # Run the php script via SSH command
    remote_command = "php /home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/db/migrate_v10_leads.php"
    print(f"Executing remote command: {remote_command}")
    stdin, stdout, stderr = ssh.exec_command(remote_command)
    
    # Print outputs
    out = stdout.read().decode('utf-8')
    err = stderr.read().decode('utf-8')
    
    print("\n--- STDOUT ---")
    print(out)
    print("--- STDERR ---")
    print(err)
    
    ssh.close()
    print("SSH connection closed.")

except Exception as e:
    print("Failed to run remote migration:", str(e))
    sys.exit(1)
