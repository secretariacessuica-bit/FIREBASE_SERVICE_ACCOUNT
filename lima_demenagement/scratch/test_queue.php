<?php
// Test Waitlist flow
require_once __DIR__ . '/../public_site/api/v1/config.php';
require_once __DIR__ . '/../public_site/helpers/EmailHelper.php';

echo "--- STARTING SYSTEM INTEGRATION TEST FOR RESERVATION QUEUE ---\n\n";

try {
    // 1. Get an approved item
    $item = $pdo->query("SELECT id, title, company_id FROM marketplace_items WHERE status = 'Approved' LIMIT 1")->fetch();
    if (!$item) {
        // Let's create one if none exists
        echo "No approved items found. Creating a test marketplace item.\n";
        
        $client = $pdo->query("SELECT id FROM clients LIMIT 1")->fetch();
        if (!$client) {
            $pdo->exec("INSERT INTO clients (name, email, phone, company_id) VALUES ('Test Client', 'testclient@example.com', '123456', 1)");
            $clientId = $pdo->lastInsertId();
        } else {
            $clientId = $client['id'];
        }
        
        $cat = $pdo->query("SELECT id FROM marketplace_categories LIMIT 1")->fetch();
        if (!$cat) {
            $pdo->exec("INSERT INTO marketplace_categories (name) VALUES ('Meubles')");
            $catId = $pdo->lastInsertId();
        } else {
            $catId = $cat['id'];
        }

        $pdo->exec("INSERT INTO marketplace_items (title, description, price, category_id, client_id, company_id, status) 
                   VALUES ('Canapé Nordique', 'Superbe canapé comme neuf', 120.00, $catId, $clientId, 1, 'Approved')");
        $itemId = $pdo->lastInsertId();
        $item = ['id' => $itemId, 'title' => 'Canapé Nordique', 'company_id' => 1];
    }
    
    $itemId = $item['id'];
    echo "Using Marketplace Item: [ID {$itemId}] {$item['title']}\n\n";

    // Clean any existing reservations for this item to start fresh
    $pdo->prepare("DELETE FROM marketplace_reservations WHERE item_id = ?")->execute([$itemId]);
    echo "Cleaned existing reservations for this item.\n\n";

    // 2. Add first user (Should be Active immediately)
    echo "Step 1: Adding Alice to waitlist...\n";
    $payload1 = [
        'item_id' => $itemId,
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'phone' => '11111111'
    ];
    postReservation($payload1);

    // Verify Alice state
    $res1 = getReservations($itemId);
    printQueue($res1);

    // 3. Add second user (Should be Waiting)
    echo "\nStep 2: Adding Bob to waitlist...\n";
    $payload2 = [
        'item_id' => $itemId,
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'phone' => '22222222'
    ];
    postReservation($payload2);
    
    $res2 = getReservations($itemId);
    printQueue($res2);

    // 4. Cancel Alice (Bob should be promoted to Active)
    $aliceId = $res2[0]['id'];
    echo "\nStep 3: Alice cancels reservation (ID: $aliceId). Bob should be promoted...\n";
    deleteReservation($aliceId);

    $res3 = getReservations($itemId);
    printQueue($res3);

    // Check Simulated Emails
    echo "\nStep 4: Checking simulated emails in database...\n";
    $emails = $pdo->query("SELECT to_email, subject, body FROM simulated_emails ORDER BY id DESC LIMIT 5")->fetchAll();
    foreach ($emails as $em) {
        echo "To: {$em['to_email']}\nSubject: {$em['subject']}\nBody Snippet: " . substr($em['body'], 0, 100) . "...\n---\n";
    }

} catch (Exception $e) {
    echo "TEST ERROR: " . $e->getMessage() . "\n";
}

function postReservation($data) {
    global $pdo;
    $_POST = $data;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    ob_start();
    include __DIR__ . '/../public_site/api/v1/marketplace/reservations.php';
    $output = ob_get_clean();
    echo "Response: $output\n";
}

function getReservations($itemId) {
    global $pdo;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['item_id'] = $itemId;
    ob_start();
    include __DIR__ . '/../public_site/api/v1/marketplace/reservations.php';
    $output = ob_get_clean();
    $data = json_decode($output, true);
    return $data['queue'] ?? [];
}

function deleteReservation($id) {
    global $pdo;
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $_GET['id'] = $id;
    ob_start();
    include __DIR__ . '/../public_site/api/v1/marketplace/reservations.php';
    $output = ob_get_clean();
    echo "Response: $output\n";
}

function printQueue($queue) {
    echo "Current Queue Status:\n";
    if (empty($queue)) {
        echo "  [Empty]\n";
        return;
    }
    foreach ($queue as $q) {
        echo "  Pos #{$q['position']}: {$q['name']} | Status: {$q['status']} | Expires At: {$q['expires_at']}\n";
    }
}
