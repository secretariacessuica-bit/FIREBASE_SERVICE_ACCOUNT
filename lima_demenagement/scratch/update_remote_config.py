import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

secret_key = "sk_test_51TkCMxGfvPr8qvMjrB2Mzrd9dHWvbvR9HWKR167u5t6zxu8qjet4PXhLk3Zi6C9h2vVbDszrgKkbG42VrlBdx1rd00HEE5IIlI"

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    # Read remote config
    remote_path = 'sites/private_lima/config.php'
    with sftp.file(remote_path, 'r') as f:
        content = f.read().decode('utf-8')
        
    if 'STRIPE_TEST_SECRET_KEY' not in content:
        # Append Stripe configuration
        config_addition = f"""

// Stripe Test Mode Keys (Configured via API)
define('STRIPE_TEST_SECRET_KEY', '{secret_key}');
define('STRIPE_TEST_WEBHOOK_SECRET', 'whsec_placeholder_replace_me');
define('APP_ENV', 'staging');
"""
        content += config_addition
        with sftp.file(remote_path, 'w') as f:
            f.write(content)
        print("Remote config.php updated successfully.")
    else:
        print("Stripe keys already present in remote config.")
        
    sftp.close()
    transport.close()
except Exception as e:
    print("Failed:", str(e))
