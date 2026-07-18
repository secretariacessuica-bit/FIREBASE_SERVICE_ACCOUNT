import paramiko
import sys

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(hostname=host, port=port, username=username, password=password)
    
    remote_command = "php /home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/db/run_uat_tests.php"
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
except Exception as e:
    print("Error:", str(e))
    sys.exit(1)
