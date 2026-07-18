<?php
// LIMA Solutions ERP - Payments API V1

require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../admin/timeline_helper.php';
require_once '../../../helpers/EmailHelper.php';
require_once '../../../modules/payments/model/Payment.php';
require_once '../../../modules/payments/controller/PaymentController.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = getActiveCompanyId();
if (!$companyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucune entreprise active sélectionnée.']);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce Module Access for 'payments' (will fallback to invoices if disabled)
enforceModuleAccess('payments', $userRole, $companyId, 'view', $pdo);

$paymentModel = new Payment($pdo);
$controller = new PaymentController($paymentModel);

$method = $_SERVER['REQUEST_METHOD'];

// Standardized CSRF Protection Check for mutating requests
if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $clientCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionCsrfToken = $_SESSION['csrf_token'] ?? '';

    if (empty($sessionCsrfToken) || empty($clientCsrfToken) || !hash_equals($sessionCsrfToken, $clientCsrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Erreur de segurança CSRF: Requête rejetée.']);
        exit();
    }
} else {
    $input = $_GET;
}

// GET Route (List, Search, View Receipt)
if ($method === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 200) $limit = 50;
    $offset = ($page - 1) * $limit;

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $payment = $paymentModel->getById($id, $companyId);
        if (!$payment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Paiement non trouvé.']);
            exit();
        }

        // Check if receipt PDF (HTML format) is requested
        if (isset($_GET['receipt']) && $_GET['receipt'] == 1) {
            $reqId = bin2hex(random_bytes(16));
            $auditData = [
                'user_id' => $_SESSION['user_id'],
                'invoice_id' => $payment['invoice_id'],
                'payment_id' => $id,
                'ip' => $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            logActivity($_SESSION['user_id'], $companyId, 'payments', 'payments', $id, 'Viewed receipt for payment ' . $payment['payment_number'], $pdo, null, $auditData, $reqId);
            logEntityEvent($companyId, 'payments', 'payments', $id, 'receipt_viewed', $_SESSION['user_id'], "Recibo do pagamento " . $payment['payment_number'] . " consultado pelo usuário.", $pdo);

            if (!empty($payment['receipt_path'])) {
                $filePath = __DIR__ . '/../../../../' . $payment['receipt_path'];
                if (file_exists($filePath)) {
                    header('Content-Type: text/html; charset=utf-8');
                    echo file_get_contents($filePath);
                    exit();
                }
            }
            // Generate on the fly if file missing
            $receiptPath = $paymentModel->generateReceipt($id, $companyId);
            if ($receiptPath) {
                header('Content-Type: text/html; charset=utf-8');
                echo file_get_contents(__DIR__ . '/../../../../' . $receiptPath);
                exit();
            } else {
                http_response_code(500);
                echo "Erreur lors de la génération du reçu.";
                exit();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Paiement chargé.',
            'data' => $payment
        ]);
        exit();
    } else {
        $filters = [];
        if (isset($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }
        if (isset($_GET['invoice_id'])) {
            $filters['invoice_id'] = (int)$_GET['invoice_id'];
        }

        $payments = $paymentModel->getAll($companyId, $filters, $limit, $offset);
        $total = $paymentModel->getTotalCount($companyId, $filters);

        echo json_encode([
            'success' => true,
            'message' => 'Liste des paiements.',
            'data' => [
                'payments' => $payments,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total_records' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]
        ]);
        exit();
    }
}

// Write mutations require 'edit' permissions
if (!hasModulePermission($userRole, 'payments', 'edit', $pdo)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "Accès interdit: Droits d'écriture insuffisants."]);
    exit();
}

// POST Method (Create & Actions)
if ($method === 'POST') {
    $action = $input['action'] ?? '';

    // Action: Refund (Placeholder)
    if ($action === 'refund') {
        echo json_encode([
            'success' => true,
            'message' => 'Remboursement traité avec succès (Placeholder).'
        ]);
        exit();
    }

    // Action: Reverse (Estornar)
    if ($action === 'reverse') {
        $paymentId = isset($input['payment_id']) ? (int)$input['payment_id'] : 0;
        $reason = isset($input['reversal_reason']) ? trim(strip_tags($input['reversal_reason'])) : '';

        if ($paymentId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de paiement invalide.']);
            exit();
        }

        if (empty($reason)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La raison de l\'extourne est obligatoire.']);
            exit();
        }

        try {
            $reversalId = $paymentModel->reverse($paymentId, $reason, $_SESSION['user_id'], $companyId);
            
            if ($reversalId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Paiement extourné avec succès.',
                    'data' => [
                        'reversal_payment_id' => $reversalId
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Échec de l\'extourne.']);
            }
        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                http_response_code(409);
            } else {
                http_response_code(500);
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // Default Action: Create Payment
    $cleanData = $controller->sanitize($input);
    $errors = $controller->validate($cleanData);

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    try {
        $paymentId = $paymentModel->create($cleanData, $companyId, $_SESSION['user_id']);
        if ($paymentId) {
            $newPayment = $paymentModel->getById($paymentId, $companyId);
            $reqId = bin2hex(random_bytes(16));

            // Log activity and timeline events
            logActivity($_SESSION['user_id'], $companyId, 'payments', 'payments', $paymentId, 'Created payment: ' . $newPayment['payment_number'], $pdo, null, $newPayment, $reqId);
            logEntityEvent($companyId, 'payments', 'payments', $paymentId, 'created', $_SESSION['user_id'], "Paiement enregistré: " . $newPayment['payment_number'] . " (" . $newPayment['amount'] . " " . $newPayment['currency'] . ")", $pdo);
            logEntityEvent($companyId, 'invoices', 'invoices', $cleanData['invoice_id'], 'payment_added', $_SESSION['user_id'], "Paiement de " . $newPayment['amount'] . " " . $newPayment['currency'] . " ajouté (N° " . $newPayment['payment_number'] . ").", $pdo);

            // Send email confirmation to the client
            $stmtInv = $pdo->prepare("SELECT i.invoice_number, i.balance_due, c.name, c.email FROM invoices i JOIN clients c ON i.client_id = c.id WHERE i.id = :id LIMIT 1");
            $stmtInv->execute(['id' => $cleanData['invoice_id']]);
            $invInfo = $stmtInv->fetch();
            if ($invInfo && !empty($invInfo['email'])) {
                EmailHelper::sendTemplateEmail($companyId, $invInfo['email'], 'payment_received_alert', [
                    'client_name' => $invInfo['name'],
                    'invoice_number' => $invInfo['invoice_number'],
                    'amount_paid' => number_format($newPayment['amount'], 2, '.', ''),
                    'balance_due' => number_format($invInfo['balance_due'], 2, '.', '')
                ], $pdo);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Paiement créé avec succès.',
                'data' => [
                    'id' => $paymentId,
                    'payment_number' => $newPayment['payment_number']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la création du paiement.']);
        }
    } catch (Exception $e) {
        if ($e->getCode() === 409) {
            http_response_code(409);
        } else {
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// PUT Method (Update)
if ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit();
    }

    $cleanData = $controller->sanitize($input);
    $errors = $controller->validate($cleanData);

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    $oldPayment = $paymentModel->getById($id, $companyId);
    if (!$oldPayment) {
        http_response_code(444);
        echo json_encode(['success' => false, 'message' => 'Paiement non trouvé.']);
        exit();
    }

    try {
        $result = $paymentModel->update($id, $cleanData, $companyId, $_SESSION['user_id']);
        if ($result) {
            $newPayment = $paymentModel->getById($id, $companyId);
            $reqId = bin2hex(random_bytes(16));

            logActivity($_SESSION['user_id'], $companyId, 'payments', 'payments', $id, 'Updated payment ' . $newPayment['payment_number'], $pdo, $oldPayment, $newPayment, $reqId);
            logEntityEvent($companyId, 'payments', 'payments', $id, 'updated', $_SESSION['user_id'], "Paiement mis à jour: " . $newPayment['payment_number'], $pdo);

            echo json_encode(['success' => true, 'message' => 'Paiement mis à jour avec succès.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la mise à jour.']);
        }
    } catch (Exception $e) {
        if ($e->getCode() === 409) {
            http_response_code(409);
        } else {
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// DELETE Method (Soft Delete)
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit();
    }

    $oldPayment = $paymentModel->getById($id, $companyId);
    if (!$oldPayment) {
        http_response_code(444);
        echo json_encode(['success' => false, 'message' => 'Paiement non trouvé.']);
        exit();
    }

    try {
        $result = $paymentModel->softDelete($id, $companyId, $_SESSION['user_id']);
        if ($result) {
            $reqId = bin2hex(random_bytes(16));

            logActivity($_SESSION['user_id'], $companyId, 'payments', 'payments', $id, 'Soft deleted payment ' . $oldPayment['payment_number'], $pdo, $oldPayment, null, $reqId);
            logEntityEvent($companyId, 'payments', 'payments', $id, 'deleted', $_SESSION['user_id'], "Paiement annulé/removido logicamente: " . $oldPayment['payment_number'], $pdo);
            logEntityEvent($companyId, 'invoices', 'invoices', $oldPayment['invoice_id'], 'payment_removed', $_SESSION['user_id'], "Paiement de " . $oldPayment['amount'] . " " . $oldPayment['currency'] . " annulé (N° " . $oldPayment['payment_number'] . ").", $pdo);

            echo json_encode(['success' => true, 'message' => 'Paiement supprimé avec succès.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la suppression.']);
        }
    } catch (Exception $e) {
        if ($e->getCode() === 409) {
            http_response_code(409);
        } else {
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
