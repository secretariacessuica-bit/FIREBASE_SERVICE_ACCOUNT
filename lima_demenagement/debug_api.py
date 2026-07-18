import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
ftp_username = '6o9v7p_admin'
ftp_password = 'Ces124578.'

# A temporary debug wrapper to catch errors from invoices API
php_debug = """<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

// Simulate what invoices.php does
try {
    require_once '../../../admin/auth.php';
    echo "auth.php OK\\n";
    
    require_once '../../../admin/modules_helper.php';
    echo "modules_helper.php OK\\n";
    
    require_once '../../../admin/sequences_helper.php';
    echo "sequences_helper.php OK\\n";
    
    require_once '../../../admin/timeline_helper.php';
    echo "timeline_helper.php OK\\n";
    
    require_once '../../../helpers/PdfTemplate.php';
    echo "PdfTemplate.php OK\\n";
    
    require_once '../../../modules/invoices/model/Invoice.php';
    echo "Invoice model OK\\n";
    
    require_once '../../../modules/invoices/controller/InvoiceController.php';
    echo "InvoiceController OK\\n";
    
    echo "\\nAll dependencies loaded successfully!\\n";
    
    // Check invoices table columns
    $cols = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);
    echo "\\nInvoices table columns:\\n";
    foreach ($cols as $c) echo " - $c\\n";
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\\n";
    echo "FILE: " . $e->getFile() . "\\n";
    echo "LINE: " . $e->getLine() . "\\n";
}
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=ftp_username, password=ftp_password)
    sftp = paramiko.SFTPClient.from_transport(transport)

    with sftp.file('sites/limasolutions.ch/api/v1/invoices/debug_invoices.php', 'w') as f:
        f.write(php_debug)
    print("debug_invoices.php uploaded.")
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
