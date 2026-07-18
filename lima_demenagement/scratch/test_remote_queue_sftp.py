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
    
    # 1. Write the remote waitlist tester code
    test_runner = f"""<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../private_lima/config.php';
require_once __DIR__ . '/helpers/EmailHelper.php';

echo "--- STARTING SYSTEM INTEGRATION TEST FOR RESERVATION QUEUE ---\\n\\n";

try {{
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. Get an approved item
    $item = $pdo->query("SELECT id, title, company_id FROM marketplace_items WHERE status = 'Approved' LIMIT 1")->fetch();
    if (!$item) {{
        echo "No approved items found. Creating a test marketplace item.\\n";
        
        $client = $pdo->query("SELECT id FROM clients LIMIT 1")->fetch();
        if (!$client) {{
            $pdo->exec("INSERT INTO clients (name, email, phone, company_id) VALUES ('Test Client', 'testclient@example.com', '123456', 1)");
            $clientId = $pdo->lastInsertId();
        }} else {{
            $clientId = $client['id'];
        }}
        
        $cat = $pdo->query("SELECT id FROM marketplace_categories LIMIT 1")->fetch();
        if (!$cat) {{
            $pdo->exec("INSERT INTO marketplace_categories (name) VALUES ('Meubles')");
            $catId = $pdo->lastInsertId();
        }} else {{
            $catId = $cat['id'];
        }}

        $pdo->exec("INSERT INTO marketplace_items (title, description, price, category_id, client_id, company_id, status) 
                   VALUES ('Canapé Nordique', 'Superbe canapé comme neuf', 120.00, $catId, $clientId, 1, 'Approved')");
        $itemId = $pdo->lastInsertId();
        $item = ['id' => $itemId, 'title' => 'Canapé Nordique', 'company_id' => 1];
    }}
    
    $itemId = $item['id'];
    echo "Using Marketplace Item: [ID {{$itemId}}] {{$item['title']}}\\n\\n";

    // Clean any existing reservations for this item to start fresh
    $pdo->prepare("DELETE FROM marketplace_reservations WHERE item_id = ?")->execute([$itemId]);
    echo "Cleaned existing reservations for this item.\\n\\n";

    // Helper functions inside script since they run in-request context
    function run_api($method, $params) {{
        global $pdo, $itemId;
        
        // Setup superglobals to mock requests
        $_SERVER['REQUEST_METHOD'] = $method;
        if ($method === 'POST') {{
            $_POST = $params;
            $GLOBALS['input'] = $params;
        }} elseif ($method === 'GET') {{
            $_GET = $params;
        }}
        
        // Capture API output
        ob_start();
        include __DIR__ . '/api/v1/marketplace/reservations.php';
        $res = ob_get_clean();
        
        // Reset superglobals
        $_POST = [];
        $_GET = [];
        $GLOBALS['input'] = [];
        
        return $res;
    }}

    // 2. Add first user (Should be Active immediately)
    echo "Step 1: Adding Alice to waitlist...\\n";
    $payload1 = [
        'item_id' => $itemId,
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'phone' => '11111111'
    ];
    $resp1 = run_api('POST', $payload1);
    echo "API Response: $resp1\\n";

    // Verify Alice state
    $respQueue = json_decode(run_api('GET', ['item_id' => $itemId]), true);
    printQueue($respQueue['queue'] ?? []);

    // 3. Add second user (Should be Waiting)
    echo "\\nStep 2: Adding Bob to waitlist...\\n";
    $payload2 = [
        'item_id' => $itemId,
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'phone' => '22222222'
    ];
    $resp2 = run_api('POST', $payload2);
    echo "API Response: $resp2\\n";
    
    $respQueue = json_decode(run_api('GET', ['item_id' => $itemId]), true);
    printQueue($respQueue['queue'] ?? []);

    // 4. Cancel Alice (Bob should be promoted to Active)
    $aliceId = $respQueue['queue'][0]['id'];
    echo "\\nStep 3: Alice cancels reservation (ID: $aliceId). Bob should be promoted...\\n";
    
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $_GET['id'] = $aliceId;
    ob_start();
    include __DIR__ . '/api/v1/marketplace/reservations.php';
    $respDelete = ob_get_clean();
    $_GET = [];
    
    echo "API Response: $respDelete\\n";

    $respQueue = json_decode(run_api('GET', ['item_id' => $itemId]), true);
    printQueue($respQueue['queue'] ?? []);

    // Check Simulated Emails
    echo "\\nStep 4: Checking simulated emails in database...\\n";
    $emails = $pdo->query("SELECT to_email, subject, body FROM simulated_emails ORDER BY id DESC LIMIT 2")->fetchAll();
    foreach ($emails as $em) {{
        echo "To: {{$em['to_email']}}\\nSubject: {{$em['subject']}}\\nBody Snippet: " . substr(str_replace("\\n", " ", $em['body']), 0, 100) . "...\\n---\\n";
    }}

}} catch (Exception $e) {{
    echo "TEST ERROR: " . $e->getMessage() . "\\n";
}}

function printQueue($queue) {{
    echo "Current Queue Status:\\n";
    if (empty($queue)) {{
        echo "  [Empty]\\n";
        return;
    }}
    foreach ($queue as $q) {{
        echo "  Pos #{{$q['position']}}: {{$q['name']}} | Status: {{$q['status']}} | Expires At: {{$q['expires_at']}}\\n";
    }}
}}
"""
    
    print("Writing remote queue verification script...")
    with sftp.open('sites/limasolutions.ch/test_remote_queue.php', 'w') as f:
        f.write(test_runner)
        
    sftp.close()
    transport.close()
    print("SFTP operations complete.")

except Exception as e:
    print("SFTP failed:", str(e))
