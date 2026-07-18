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
    
    # 1. Read existing config.php to extract DB connection info
    config_data = ""
    with sftp.open('sites/private_lima/config.php', 'r') as f:
        config_data = f.read().decode('utf-8')
    
    # 2. Write a temporary PHP test runner on the remote server
    test_runner = f"""<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../private_lima/config.php';
// We copy the exact DB credentials
echo "DB Host: " . SECURE_DB_HOST . "\\n";
try {{
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connection successful!\\n";
    
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables:\\n";
    foreach ($tables as $t) {{
        echo " - $t\\n";
    }}
}} catch (Exception $e) {{
    echo "DB Connection Error: " . $e->getMessage() . "\\n";
}}
"""
    
    # Write to a public file so we can invoke it via HTTP request
    # Since we can write in site root or public_site
    # Remote site root is 'sites/limasolutions.ch'
    print("Writing remote test runner...")
    with sftp.open('sites/limasolutions.ch/test_remote_db.php', 'w') as f:
        f.write(test_runner)
        
    sftp.close()
    transport.close()
    print("SFTP operations complete.")

except Exception as e:
    print("SFTP failed:", str(e))
