<?php
// LIMA Solutions ERP - Stripe Webhook API Handler
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';
require_once dirname(dirname(__DIR__)) . '/modules/payments/model/Payment.php';

// Verify Stripe Signature helper
function verifyStripeSignature($payload, $header, $secret) {
    if (empty($header) || empty($secret)) return false;
    
    $pairs = explode(',', $header);
    $timestamp = null;
    $signatures = [];
    foreach ($pairs as $pair) {
        $parts = explode('=', $pair, 2);
        if (count($parts) === 2) {
            if (trim($parts[0]) === 't') {
                $timestamp = trim($parts[1]);
            } elseif (trim($parts[0]) === 'v1') {
                $signatures[] = trim($parts[1]);
            }
        }
    }
    
    if (!$timestamp || empty($signatures)) {
        return false;
    }
    
    // Replay attack protection: limit signature timestamp to 5 minutes (300 seconds)
    if (abs(time() - $timestamp) > 300) {
        return false;
    }
    
    $signedPayload = $timestamp . '.' . $payload;
    $computedSig = hash_hmac('sha256', $signedPayload, $secret);
    
    foreach ($signatures as $sig) {
        if (hash_equals($sig, $computedSig)) {
            return true;
        }
    }
    return false;
}

try {
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    $appEnv = defined('APP_ENV') ? APP_ENV : 'production';
    $webhookSecret = defined('STRIPE_TEST_WEBHOOK_SECRET') ? STRIPE_TEST_WEBHOOK_SECRET : '';

    // Check for developer mock parameter in local env
    $isMock = false;
    $mockKey = isset($_GET['mock_checkout']) ? trim($_GET['mock_checkout']) : '';

    if ($appEnv === 'local' && !empty($mockKey)) {
        $isMock = true;
    }

    if (!$isMock) {
        // Enforce Stripe signature validation
        if (empty($sigHeader) || empty($webhookSecret) || strpos($webhookSecret, 'mock') !== false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Signature de webhook Stripe ou secret manquant.']);
            exit();
        }

        if (!verifyStripeSignature($payload, $sigHeader, $webhookSecret)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Signature Stripe invalide. Replay protection active.']);
            exit();
        }
    }

    // Parse payload
    if ($isMock) {
        // Mock payload structure
        $event = [
            'id' => 'evt_mock_' . uniqid(),
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'client_reference_id' => $mockKey,
                    'payment_intent' => 'pi_mock_' . bin2hex(random_bytes(8))
                ]
            ]
        ];
    } else {
        $event = json_decode($payload, true);
    }

    if (empty($event['type']) || empty($event['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Événement invalide.']);
        exit();
    }

    $eventId = $event['id'];
    $eventType = $event['type'];

    // 1. Deduplicate Event
    $stmt = $pdo->prepare("SELECT * FROM payment_webhooks WHERE event_id = :event_id LIMIT 1");
    $stmt->execute(['event_id' => $eventId]);
    $existingWebhook = $stmt->fetch();

    if ($existingWebhook) {
        echo json_encode(['success' => true, 'message' => 'Webhook déjà traité (déduplication active).']);
        exit();
    }

    // Process event types
    if ($eventType === 'checkout.session.completed') {
        $session = $event['data']['object'];
        $idempotencyKey = $session['client_reference_id'] ?? '';
        $paymentIntent = $session['payment_intent'] ?? '';

        if (empty($idempotencyKey)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'client_reference_id (idempotency key) manquant.']);
            exit();
        }

        // Fetch transaction
        $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE idempotency_key = :key LIMIT 1 FOR UPDATE");
        $stmt->execute(['key' => $idempotencyKey]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Transaction introuvable pour la clé d\'idempotence fornecida.']);
            exit();
        }

        $companyId = (int)$transaction['company_id'];
        $invoiceId = (int)$transaction['invoice_id'];

        if ($transaction['status'] === 'Succeeded') {
            // Already reconciled
            // Log webhook log as processed
            $stmt = $pdo->prepare("INSERT INTO payment_webhooks (company_id, provider, event_id, payload, processed) VALUES (:company_id, 'stripe', :event_id, :payload, 1)");
            $stmt->execute([
                'company_id' => $companyId,
                'event_id' => $eventId,
                'payload' => json_encode($event)
            ]);

            echo json_encode(['success' => true, 'message' => 'Paiement déjà réconcilié.']);
            exit();
        }

        // Insert webhook log as unprocessed first to register receipt attempt
        $stmt = $pdo->prepare("INSERT INTO payment_webhooks (company_id, provider, event_id, payload, processed) VALUES (:company_id, 'stripe', :event_id, :payload, 0)");
        $stmt->execute([
            'company_id' => $companyId,
            'event_id' => $eventId,
            'payload' => json_encode($event)
        ]);
        $webhookLogId = $pdo->lastInsertId();

        // 2. Accounting Integration (Call the exact payment creation model rules)
        $paymentModel = new Payment($pdo);
        
        $paymentData = [
            'invoice_id' => $invoiceId,
            'payment_date' => date('Y-m-d'),
            'amount' => (float)$transaction['amount'],
            'currency' => $transaction['currency'] ?: 'CHF',
            'payment_method' => 'Stripe/TWINT',
            'reference' => 'Stripe Checkout Online',
            'transaction_reference' => $transaction['provider_session_id'],
            'notes' => 'Paiement en ligne Stripe. Intent: ' . $paymentIntent
        ];

        // System/Admin fallback user id (1 or first user found)
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE active = 1 LIMIT 1");
        $userStmt->execute();
        $adminUser = $userStmt->fetch();
        $userId = $adminUser ? (int)$adminUser['id'] : 1;

        // Perform transactional payment insertion & invoice recalculation
        $paymentId = $paymentModel->create($paymentData, $companyId, $userId);

        // Update payment transaction record
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'Succeeded', provider_payment_intent = :intent WHERE id = :id");
        $stmt->execute([
            'intent' => $paymentIntent,
            'id' => $transaction['id']
        ]);

        // Mark webhook as processed
        $stmt = $pdo->prepare("UPDATE payment_webhooks SET processed = 1 WHERE id = :id");
        $stmt->execute(['id' => $webhookLogId]);

        // 3. Write activity logs & entity timeline entries
        if (function_exists('logActivity')) {
            logActivity($userId, $companyId, 'payments', 'payment_transactions', $transaction['id'], 'Online payment succeeded via Stripe Checkout', $pdo, $transaction, array_merge($transaction, ['status' => 'Succeeded']), 'webhook_' . $eventId);
        }

        $timelineSql = "INSERT INTO entity_timeline (company_id, entity_type, entity_id, event_type, description, created_by) VALUES (:company_id, 'invoice', :invoice_id, 'payment_succeeded', :description, :created_by)";
        $stmtTimeline = $pdo->prepare($timelineSql);
        $stmtTimeline->execute([
            'company_id' => $companyId,
            'invoice_id' => $invoiceId,
            'description' => 'Paiement en ligne de CHF ' . number_format($transaction['amount'], 2) . ' reçu avec succès via Stripe Checkout.',
            'created_by' => $userId
        ]);

        echo json_encode(['success' => true, 'message' => 'Paiement réconcilié et traité avec succès.', 'payment_id' => $paymentId]);
        exit();

    } else {
        // Other Stripe events (ignored but recorded as processed for safety)
        $companyStmt = $pdo->prepare("SELECT id FROM companies LIMIT 1");
        $companyStmt->execute();
        $company = $companyStmt->fetch();
        $companyId = $company ? (int)$company['id'] : 1;

        $stmt = $pdo->prepare("INSERT INTO payment_webhooks (company_id, provider, event_id, payload, processed, error_details) VALUES (:company_id, 'stripe', :event_id, :payload, 1, 'Event ignored (unsupported type)')");
        $stmt->execute([
            'company_id' => $companyId,
            'event_id' => $eventId,
            'payload' => json_encode($event)
        ]);

        echo json_encode(['success' => true, 'message' => 'Événement non pris en charge (enregistré sans action).']);
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Exception webhook Stripe: ' . $e->getMessage()]);
}
