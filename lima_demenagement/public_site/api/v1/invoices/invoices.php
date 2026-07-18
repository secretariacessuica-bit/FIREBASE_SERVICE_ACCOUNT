<?php
// LIMA Solutions ERP - Invoices API V1

require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../admin/sequences_helper.php';
require_once '../../../admin/timeline_helper.php';
require_once '../../../helpers/PdfTemplate.php';
require_once '../../../helpers/EmailHelper.php';
require_once '../../../modules/invoices/model/Invoice.php';
require_once '../../../modules/invoices/controller/InvoiceController.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = getActiveCompanyId();
if (!$companyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucune entreprise active sélectionnée.']);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce Module Access for 'invoices'
enforceModuleAccess('invoices', $userRole, $companyId, 'view', $pdo);

$invoiceModel = new Invoice($pdo);
$controller = new InvoiceController($invoiceModel);

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
        echo json_encode(['success' => false, 'message' => 'Erreur de sécurité CSRF: Requête rejetée.']);
        exit();
    }
} else {
    $input = $_GET;
}

// GET requests: List, Search, View, PDF
if ($method === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 200) $limit = 50;
    $offset = ($page - 1) * $limit;

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $invoice = $invoiceModel->getById($id, $companyId);
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
            exit();
        }

        // PDF Generation Trigger
        if ((isset($_GET['pdf']) && $_GET['pdf'] == 1) || (isset($_GET['format']) && $_GET['format'] === 'pdf')) {
            header('Content-Type: text/html; charset=utf-8');
            echo $invoiceModel->renderPdf($id, $companyId);
            exit();
        }

        $items = $invoiceModel->getItems($id, $companyId);
        echo json_encode([
            'success' => true,
            'message' => 'Facture chargée.',
            'data' => [
                'invoice' => $invoice,
                'items' => $items
            ]
        ]);
        exit();
    } else {
        $filters = [];
        if (isset($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }
        if (isset($_GET['status'])) {
            $filters['status'] = trim($_GET['status']);
        }

        $invoices = $invoiceModel->getAll($companyId, $filters, $limit, $offset);
        $total = $invoiceModel->getTotalCount($companyId, $filters);
        echo json_encode([
            'success' => true,
            'message' => 'Liste des factures.',
            'data' => [
                'invoices' => $invoices,
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

// Write mutation guards: Must have 'edit' permissions on 'invoices'
if (!hasModulePermission($userRole, 'invoices', 'edit', $pdo)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "Accès interdit: Droits d'écriture insuffisants."]);
    exit();
}

// POST mutation router
if ($method === 'POST') {
    $action = $input['action'] ?? '';

    // Convert Quote to Invoice
    if ($action === 'convert_quote') {
        $quoteId = (int)($input['quote_id'] ?? 0);
        if (!$quoteId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID du devis manquant.']);
            exit();
        }

        try {
            $invoiceId = $invoiceModel->convertFromQuote($quoteId, $companyId, $_SESSION['user_id']);
            if ($invoiceId) {
                $newInvoice = $invoiceModel->getById($invoiceId, $companyId);
                $reqId = bin2hex(random_bytes(16));
                logActivity($_SESSION['user_id'], $companyId, 'invoices', 'invoices', $invoiceId, 'Converted quote ID ' . $quoteId . ' to invoice ' . $newInvoice['invoice_number'], $pdo, null, $newInvoice, $reqId);
                logEntityEvent($companyId, 'invoices', 'invoices', $invoiceId, 'created', $_SESSION['user_id'], "Facture convertie à partir du devis N° " . $quoteId, $pdo);
                
                // Add timeline event on the source quote as well to track flow
                logEntityEvent($companyId, 'quotes', 'quotes', $quoteId, 'converted', $_SESSION['user_id'], "Devis converti en facture: N° " . $newInvoice['invoice_number'], $pdo);

                // Send email notification to client
                $stmtClient = $pdo->prepare("SELECT email FROM clients WHERE id = :id LIMIT 1");
                $stmtClient->execute(['id' => $newInvoice['client_id']]);
                $clientEmail = $stmtClient->fetchColumn();
                if ($clientEmail) {
                    EmailHelper::sendTemplateEmail($companyId, $clientEmail, 'new_invoice_alert', [
                        'client_name' => $newInvoice['client_name'],
                        'invoice_number' => $newInvoice['invoice_number'],
                        'total_amount' => number_format($newInvoice['total'], 2, '.', ''),
                        'balance_due' => number_format($newInvoice['balance_due'], 2, '.', ''),
                        'due_date' => date('d.m.Y', strtotime($newInvoice['due_date'])),
                        'payment_link' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'limasolutions.ch') . '/facture/index.html?id=' . $invoiceId
                    ], $pdo);
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Facture générée avec succès à partir do devis.',
                    'data' => [
                        'id' => $invoiceId,
                        'invoice_number' => $newInvoice['invoice_number']
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Échec de la conversion.']);
            }
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur de conversion: ' . $e->getMessage()]);
        }
        exit();
    }

    // Status alteration
    if ($action === 'status') {
        $id = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        
        $validStatuses = ['Draft', 'Issued', 'Sent', 'Paid', 'Partially Paid', 'Cancelled', 'Overdue'];
        if (!$id || !in_array($status, $validStatuses)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Statut invalide.']);
            exit();
        }

        $oldInvoice = $invoiceModel->getById($id, $companyId);
        if (!$oldInvoice) {
            http_response_code(444);
            echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
            exit();
        }

        $cancellationReason = $input['cancellation_reason'] ?? null;
        try {
            $result = $invoiceModel->changeStatus($id, $status, $companyId, $_SESSION['user_id'], $cancellationReason);
            if ($result) {
                $newInvoice = $invoiceModel->getById($id, $companyId);
                $reqId = bin2hex(random_bytes(16));
                $logMsg = 'Changed invoice status to ' . $status;
                if ($status === 'Cancelled' && !empty($cancellationReason)) {
                    $logMsg .= ' (Reason: ' . $cancellationReason . ')';
                }
                logActivity($_SESSION['user_id'], $companyId, 'invoices', 'invoices', $id, $logMsg, $pdo, $oldInvoice, $newInvoice, $reqId);
                logEntityEvent($companyId, 'invoices', 'invoices', $id, strtolower($status), $_SESSION['user_id'], "Statut modifié: " . $status . ($status === 'Cancelled' && !empty($cancellationReason) ? " - Motif: " . $cancellationReason : ""), $pdo);

                echo json_encode(['success' => true, 'message' => 'Statut de la facture mis à jour.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Échec de la mise à jour du statut.']);
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

    // Cancel invoice (explicit action)
    if ($action === 'cancel') {
        $id = (int)($input['id'] ?? 0);
        $cancellationReason = $input['cancellation_reason'] ?? '';

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID manquant.']);
            exit();
        }

        if (empty(trim($cancellationReason))) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Le motif d\'annulation est obligatoire.']);
            exit();
        }

        $oldInvoice = $invoiceModel->getById($id, $companyId);
        if (!$oldInvoice) {
            http_response_code(444);
            echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
            exit();
        }

        try {
            $result = $invoiceModel->changeStatus($id, 'Cancelled', $companyId, $_SESSION['user_id'], $cancellationReason);
            if ($result) {
                $newInvoice = $invoiceModel->getById($id, $companyId);
                $reqId = bin2hex(random_bytes(16));
                logActivity($_SESSION['user_id'], $companyId, 'invoices', 'invoices', $id, 'Cancelled invoice ID ' . $id . ' (Reason: ' . $cancellationReason . ')', $pdo, $oldInvoice, $newInvoice, $reqId);
                logEntityEvent($companyId, 'invoices', 'invoices', $id, 'cancelled', $_SESSION['user_id'], "Facture annulée - Motif: " . $cancellationReason, $pdo);

                echo json_encode(['success' => true, 'message' => 'Facture annulée avec succès.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Échec de l\'annulation de la facture.']);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // Soft delete
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID manquant.']);
            exit();
        }

        $oldInvoice = $invoiceModel->getById($id, $companyId);
        if (!$oldInvoice) {
            http_response_code(444);
            echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
            exit();
        }

        $result = $invoiceModel->softDelete($id, $companyId, $_SESSION['user_id']);
        if ($result) {
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'invoices', 'invoices', $id, 'Deleted invoice ID ' . $id, $pdo, $oldInvoice, null, $reqId);
            logEntityEvent($companyId, 'invoices', 'invoices', $id, 'deleted', $_SESSION['user_id'], "Facture supprimée (Soft Delete)", $pdo);

            echo json_encode(['success' => true, 'message' => 'Facture supprimée avec succès.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la suppression.']);
        }
        exit();
    }

    // Update invoice (POST fallback)
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID manquant.']);
            exit();
        }

        $cleanData = $controller->sanitize($input);
        $items = $input['items'] ?? [];

        $errors = $controller->validate($cleanData, $items);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }

        $oldInvoice = $invoiceModel->getById($id, $companyId);
        if (!$oldInvoice) {
            http_response_code(444);
            echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
            exit();
        }
        
        $totals = $controller->calculateTotals($items, $cleanData['discount_percent'] ?? 0.00, $companyId, $pdo, $cleanData['currency'] ?? 'CHF');
        $data = array_merge($cleanData, $totals);
        try {
            $result = $invoiceModel->update($id, $data, $totals['items'], $companyId, $_SESSION['user_id']);

            if ($result) {
                $newInvoice = $invoiceModel->getById($id, $companyId);
                $reqId = bin2hex(random_bytes(16));
                logActivity($_SESSION['user_id'], $companyId, 'invoices', 'invoices', $id, 'Updated invoice details', $pdo, $oldInvoice, $newInvoice, $reqId);
                logEntityEvent($companyId, 'invoices', 'invoices', $id, 'updated', $_SESSION['user_id'], "Facture mise à jour par l'utilisateur.", $pdo);

                echo json_encode(['success' => true, 'message' => 'Facture mise à jour com sucesso.']);
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

    // Default: Create Invoice
    $cleanData = $controller->sanitize($input);
    $items = $input['items'] ?? [];

    $errors = $controller->validate($cleanData, $items);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    // Generate INV number
    $invoiceNumber = generateSequence($companyId, 'INV', $pdo);
    
    $totals = $controller->calculateTotals($items, $cleanData['discount_percent'] ?? 0.00, $companyId, $pdo, $cleanData['currency'] ?? 'CHF');

    $data = array_merge($cleanData, $totals);
    $data['invoice_number'] = $invoiceNumber;
    $data['created_by'] = $_SESSION['user_id'];
    $data['company_id'] = $companyId;
    $data['quote_id'] = !empty($cleanData['quote_id']) ? (int)$cleanData['quote_id'] : null;

    try {
        $invoiceId = $invoiceModel->create($data, $totals['items'], $companyId, $_SESSION['user_id']);
        if ($invoiceId) {
            $newInvoice = $invoiceModel->getById($invoiceId, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'invoices', 'invoices', $invoiceId, 'Created new invoice: ' . $invoiceNumber, $pdo, null, $newInvoice, $reqId);
            logEntityEvent($companyId, 'invoices', 'invoices', $invoiceId, 'created', $_SESSION['user_id'], "Facture manuelle créée: N° " . $invoiceNumber, $pdo);

            // Send email notification to client
            $stmtClient = $pdo->prepare("SELECT email FROM clients WHERE id = :id LIMIT 1");
            $stmtClient->execute(['id' => $newInvoice['client_id']]);
            $clientEmail = $stmtClient->fetchColumn();
            if ($clientEmail) {
                EmailHelper::sendTemplateEmail($companyId, $clientEmail, 'new_invoice_alert', [
                    'client_name' => $newInvoice['client_name'],
                    'invoice_number' => $newInvoice['invoice_number'],
                    'total_amount' => number_format($newInvoice['total'], 2, '.', ''),
                    'balance_due' => number_format($newInvoice['balance_due'], 2, '.', ''),
                    'due_date' => date('d.m.Y', strtotime($newInvoice['due_date'])),
                    'payment_link' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'limasolutions.ch') . '/facture/index.html?id=' . $invoiceId
                ], $pdo);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Facture créée avec succès.',
                'data' => [
                    'id' => $invoiceId,
                    'invoice_number' => $invoiceNumber
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Échec de la création.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    }
    exit();
}

// PUT HTTP Method
if ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit();
    }

    $cleanData = $controller->sanitize($input);
    $items = $input['items'] ?? [];

    $errors = $controller->validate($cleanData, $items);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    $oldInvoice = $invoiceModel->getById($id, $companyId);
    if (!$oldInvoice) {
        http_response_code(444);
        echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
        exit();
    }
    
    $totals = $controller->calculateTotals($items, $cleanData['discount_percent'] ?? 0.00, $companyId, $pdo, $cleanData['currency'] ?? 'CHF');
    $data = array_merge($cleanData, $totals);
    try {
        $result = $invoiceModel->update($id, $data, $totals['items'], $companyId, $_SESSION['user_id']);

        if ($result) {
            $newInvoice = $invoiceModel->getById($id, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'invoices', 'invoices', $id, 'Updated invoice details', $pdo, $oldInvoice, $newInvoice, $reqId);
            logEntityEvent($companyId, 'invoices', 'invoices', $id, 'updated', $_SESSION['user_id'], "Facture mise à jour par l'utilisateur.", $pdo);

            echo json_encode(['success' => true, 'message' => 'Facture mise à jour avec succès.']);
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

// DELETE HTTP Method
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit();
    }

    $oldInvoice = $invoiceModel->getById($id, $companyId);
    if (!$oldInvoice) {
        http_response_code(444);
        echo json_encode(['success' => false, 'message' => 'Facture non trouvée.']);
        exit();
    }

    try {
        $result = $invoiceModel->softDelete($id, $companyId, $_SESSION['user_id']);
        if ($result) {
            $reqId = bin2hex(random_bytes(16));
            logActivity($_SESSION['user_id'], $companyId, 'invoices', 'invoices', $id, 'Deleted invoice ID ' . $id, $pdo, $oldInvoice, null, $reqId);
            logEntityEvent($companyId, 'invoices', 'invoices', $id, 'deleted', $_SESSION['user_id'], "Facture supprimée (Soft Delete)", $pdo);

            echo json_encode(['success' => true, 'message' => 'Facture supprimée avec succès.']);
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
