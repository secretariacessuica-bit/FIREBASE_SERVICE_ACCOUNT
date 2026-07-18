<?php
// LIMA Solutions ERP - Stripe Create Session API
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config.php';

// Ensure user is logged in (either admin/staff or portal client user)
$userId = $_SESSION['user_id'] ?? null;
$clientUserId = $_SESSION['client_user_id'] ?? null;

if (!$userId && !$clientUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé. Veuillez vous connecter.']);
    exit();
}

// Get active company_id
$companyId = getActiveCompanyId();
if (!$companyId && $clientUserId) {
    // If it's a client user, load their company_id from session
    $companyId = $_SESSION['company_id'] ?? null;
}

if (!$companyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contexte d\'entreprise manquant.']);
    exit();
}

// Parse request body
$input = json_decode(file_get_contents('php://input'), true);
$invoiceId = isset($input['invoice_id']) ? (int)$input['invoice_id'] : null;
$provider = isset($input['provider']) ? trim($input['provider']) : 'stripe';

if (!$invoiceId || $provider !== 'stripe') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides. L\'invoice_id et o provedor devem ser válidos.']);
    exit();
}

try {
    // Load invoice and verify company ownership
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $invoiceId, 'company_id' => $companyId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
        exit();
    }

    $balanceDue = (float)$invoice['balance_due'];
    if ($balanceDue <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cette facture est déjà entièrement payée.']);
        exit();
    }

    // Generate Idempotency Key
    // Unique per company, invoice, and current balance to allow subsequent attempts of new payments but block duplicates for the current balance state.
    $idempotencyKey = hash('sha256', $companyId . '_' . $invoiceId . '_' . $balanceDue);

    // Check if there is an active session for the same idempotency key
    $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE idempotency_key = :key AND status = 'Pending' LIMIT 1");
    $stmt->execute(['key' => $idempotencyKey]);
    $existingTx = $stmt->fetch();

    if ($existingTx) {
        // Return existing checkout URL directly to prevent duplicate charges
        echo json_encode([
            'success' => true,
            'transaction_id' => (int)$existingTx['id'],
            'checkout_url' => $existingTx['provider_session_id'], // In mock mode, this is the mock checkout page URL
            'status' => 'Pending',
            'is_duplicate' => true
        ]);
        exit();
    }

    // Environment and Key check
    $appEnv = defined('APP_ENV') ? APP_ENV : 'production';
    $stripeKey = defined('STRIPE_TEST_SECRET_KEY') ? STRIPE_TEST_SECRET_KEY : '';

    if (empty($stripeKey) || strpos($stripeKey, 'mock') !== false) {
        if ($appEnv === 'production' || $appEnv === 'staging') {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur critique de configuration de paiement: Chaves de API Stripe ausentes ou incorretas.'
            ]);
            exit();
        }
        
        // Local mock mode execution
        $mockSessionId = 'https://limasolutions.ch/portal/invoices.php?mock_checkout=' . $idempotencyKey;
        
        // Save mock transaction
        $stmt = $pdo->prepare("INSERT INTO payment_transactions (company_id, invoice_id, provider, provider_session_id, amount, currency, status, idempotency_key) VALUES (:company_id, :invoice_id, 'stripe', :session_id, :amount, :currency, 'Pending', :key)");
        $stmt->execute([
            'company_id' => $companyId,
            'invoice_id' => $invoiceId,
            'session_id' => $mockSessionId,
            'amount' => $balanceDue,
            'currency' => $invoice['currency'] ?: 'CHF',
            'key' => $idempotencyKey
        ]);
        $newTxId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'transaction_id' => (int)$newTxId,
            'checkout_url' => $mockSessionId,
            'status' => 'Pending',
            'mock' => true
        ]);
        exit();
    }

    // Real Stripe Integration (when keys are configured and not mock)
    // Dynamic curl request to Stripe REST API to avoid external library installation overhead
    $stripeUrl = 'https://api.stripe.com/v1/checkout/sessions';
    $successUrl = $input['success_url'] ?? ('https://limasolutions.ch/portal/invoices.php?status=success&tx_key=' . $idempotencyKey);
    $cancelUrl = $input['cancel_url'] ?? 'https://limasolutions.ch/portal/invoices.php?status=cancelled';

    $data = [
        'payment_method_types[0]' => 'card',
        'payment_method_types[1]' => 'twint',
        'line_items[0][price_data][currency]' => strtolower($invoice['currency'] ?: 'chf'),
        'line_items[0][price_data][product_data][name]' => 'Facture ' . $invoice['invoice_number'],
        'line_items[0][price_data][unit_amount]' => round($balanceDue * 100), // Stripe expects cents/rappen
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => $idempotencyKey,
        'metadata[company_id]' => $companyId,
        'metadata[invoice_id]' => $invoiceId
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $stripeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $stripeKey . ':');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur Stripe API Checkout Session.',
            'details' => json_decode($response, true)
        ]);
        exit();
    }

    $stripeSession = json_decode($response, true);
    $checkoutUrl = $stripeSession['url'];
    $sessionId = $stripeSession['id'];

    // Save transaction
    $stmt = $pdo->prepare("INSERT INTO payment_transactions (company_id, invoice_id, provider, provider_session_id, amount, currency, status, idempotency_key) VALUES (:company_id, :invoice_id, 'stripe', :session_id, :amount, :currency, 'Pending', :key)");
    $stmt->execute([
        'company_id' => $companyId,
        'invoice_id' => $invoiceId,
        'session_id' => $sessionId,
        'amount' => $balanceDue,
        'currency' => $invoice['currency'] ?: 'CHF',
        'key' => $idempotencyKey
    ]);
    $newTxId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'transaction_id' => (int)$newTxId,
        'checkout_url' => $checkoutUrl,
        'status' => 'Pending'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Exception in create-session: ' . $e->getMessage()]);
}
