import paramiko
import re

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

# Read schema.sql and strip the CREATE DATABASE / USE lines (we're already in the right DB)
with open(r'C:\Users\Wande\Documents\ia\lima_demenagement\public_site\db\schema.sql', 'r', encoding='utf-8') as f:
    schema = f.read()

# Remove CREATE DATABASE and USE statements - we're injecting into existing DB
schema = re.sub(r'CREATE DATABASE.*?;', '', schema, flags=re.IGNORECASE)
schema = re.sub(r'USE `[^`]+`;', '', schema, flags=re.IGNORECASE)

# Upload schema as a PHP executor script
php_exec = f"""<?php
header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__, 2) . '/private_lima/config.php';

try {{
    $pdo = new PDO(
        "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4",
        SECURE_DB_USER,
        SECURE_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    $sql = <<<'SQL'
{schema}
SQL;

    // Split and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $ok = 0;
    $errors = [];
    foreach ($statements as $stmt) {{
        if (empty($stmt)) continue;
        try {{
            $pdo->exec($stmt);
            $ok++;
        }} catch (PDOException $e) {{
            $errors[] = $e->getMessage();
        }}
    }}

    echo "Executed: $ok statements\\n";
    if (!empty($errors)) {{
        echo "Errors (" . count($errors) . "):\\n";
        foreach ($errors as $err) echo " - $err\\n";
    }} else {{
        echo "ALL STATEMENTS EXECUTED SUCCESSFULLY!\\n";
    }}

    // List all tables created
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\\nTables in database:\\n";
    foreach ($tables as $t) echo " - $t\\n";

}} catch (PDOException $e) {{
    echo "Connection error: " . $e->getMessage();
}}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    remote_path = 'sites/limasolutions.ch/admin/run_schema.php'
    with sftp.file(remote_path, 'w') as f:
        f.write(php_exec)

    print("run_schema.php uploaded successfully.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
