import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

try:
    print("Connecting to SSH...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(host, port, username, password)

    print("Running migration v16 (Hardening)...")
    stdin, stdout, stderr = ssh.exec_command("php sites/limasolutions.ch/db/migrate_v16_hardening.php")
    print("V16 Output:", stdout.read().decode())
    print("V16 Error:", stderr.read().decode())

    print("Running migration v17 (Stripe)...")
    stdin, stdout, stderr = ssh.exec_command("php sites/limasolutions.ch/db/migrate_v17_stripe.php")
    print("V17 Output:", stdout.read().decode())
    print("V17 Error:", stderr.read().decode())

    ssh.close()
    print("SSH execution completed.")
except Exception as e:
    print("SSH failed:", str(e))
