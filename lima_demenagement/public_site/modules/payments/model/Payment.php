<?php
// LIMA Solutions ERP - Payments Model
require_once __DIR__ . '/../../../helpers/PdfTemplate.php';

class Payment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all active payments for a company with filters
     */
    public function getAll($companyId, $filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT p.*, i.invoice_number, c.name AS client_name 
                FROM payments p
                JOIN invoices i ON p.invoice_id = i.id
                JOIN clients c ON i.client_id = c.id
                WHERE p.company_id = :company_id AND p.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.payment_number LIKE :search1 
                        OR i.invoice_number LIKE :search2 
                        OR c.name LIKE :search3 
                        OR p.method LIKE :search4 
                        OR p.reference LIKE :search5)";
            $val = '%' . $filters['search'] . '%';
            $params[':search1'] = $val;
            $params[':search2'] = $val;
            $params[':search3'] = $val;
            $params[':search4'] = $val;
            $params[':search5'] = $val;
        }

        if (!empty($filters['invoice_id'])) {
            $sql .= " AND p.invoice_id = :invoice_id";
            $params[':invoice_id'] = (int)$filters['invoice_id'];
        }

        $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get total count of active payments
     */
    public function getTotalCount($companyId, $filters = []) {
        $sql = "SELECT COUNT(*) 
                FROM payments p
                JOIN invoices i ON p.invoice_id = i.id
                JOIN clients c ON i.client_id = c.id
                WHERE p.company_id = :company_id AND p.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.payment_number LIKE :search1 
                        OR i.invoice_number LIKE :search2 
                        OR c.name LIKE :search3 
                        OR p.method LIKE :search4 
                        OR p.reference LIKE :search5)";
            $val = '%' . $filters['search'] . '%';
            $params[':search1'] = $val;
            $params[':search2'] = $val;
            $params[':search3'] = $val;
            $params[':search4'] = $val;
            $params[':search5'] = $val;
        }

        if (!empty($filters['invoice_id'])) {
            $sql .= " AND p.invoice_id = :invoice_id";
            $params[':invoice_id'] = (int)$filters['invoice_id'];
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get single payment by ID
     */
    public function getById($id, $companyId) {
        $sql = "SELECT p.*, i.invoice_number, i.total AS invoice_total, i.currency AS invoice_currency,
                       c.name AS client_name, c.company AS client_company 
                FROM payments p
                JOIN invoices i ON p.invoice_id = i.id
                JOIN clients c ON i.client_id = c.id
                WHERE p.id = :id AND p.company_id = :company_id AND p.deleted_at IS NULL LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create payment and update invoice totals
     */
    public function create($data, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $invoiceId = (int)$data['invoice_id'];
            $amount = (float)$data['amount'];
            $currency = $data['currency'] ?? 'CHF';

            // 1. Validate Amount
            if ($amount <= 0) {
                throw new Exception("Le montant du paiement doit être supérieur à zéro.", 409);
            }

            // 2. Fetch and Lock Invoice
            $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1 FOR UPDATE");
            $stmt->execute(['id' => $invoiceId, 'company_id' => $companyId]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                throw new Exception("Facture introuvable.", 404);
            }

            // 3. Validate Invoice status
            $allowedStatuses = ['Issued', 'Sent', 'Partially Paid', 'Overdue'];
            if (!in_array($invoice['status'], $allowedStatuses)) {
                throw new Exception("Statut de facture non autorisé pour le paiement: " . $invoice['status'], 409);
            }

            // 4. Validate Currency
            if ($currency !== $invoice['currency']) {
                throw new Exception("La devise du paiement (" . $currency . ") doit correspondre à la devise de la facture (" . $invoice['currency'] . ").", 409);
            }

            // 5. Check remaining balance
            $currentBalance = (float)$invoice['balance_due'];
            if (round($amount, 2) > round($currentBalance, 2)) {
                throw new Exception("Le montant du paiement dépasse le solde dû restant (" . number_format($currentBalance, 2) . " " . $currency . ").", 409);
            }

            // 6. Generate Sequential Number
            require_once __DIR__ . '/../../../admin/sequences_helper.php';
            $paymentNumber = generateSequence($companyId, 'PAY', $this->pdo);

            // 7. Insert Payment Row
            $sql = "INSERT INTO payments (
                company_id, invoice_id, payment_number, payment_date, amount, currency, 
                payment_method, reference, transaction_reference, notes, received_by
            ) VALUES (
                :company_id, :invoice_id, :payment_number, :payment_date, :amount, :currency, 
                :payment_method, :reference, :transaction_reference, :notes, :received_by
            )";

            $stmtInsert = $this->pdo->prepare($sql);
            $stmtInsert->execute([
                'company_id' => $companyId,
                'invoice_id' => $invoiceId,
                'payment_number' => $paymentNumber,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $userId
            ]);

            $paymentId = $this->pdo->lastInsertId();

            // 8. Recalculate Invoice totals
            $this->recalculateInvoice($invoiceId, $companyId, $userId);

            $this->pdo->commit();

            // 9. Generate receipt (static html) after committing the payment record
            $this->generateReceipt($paymentId, $companyId);

            return $paymentId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update payment and recalculate invoice
     */
    public function update($id, $data, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Fetch current payment
            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Paiement introuvable.", 404);
            }

            // Check if receipt exists - block changes to: amount, currency, invoice_id, payment_date, payment_method
            if (!empty($existing['receipt_path'])) {
                $isBlockedChanged = false;
                $blockedFields = ['amount', 'currency', 'invoice_id', 'payment_date', 'payment_method'];
                foreach ($blockedFields as $field) {
                    if (isset($data[$field])) {
                        if ($field === 'amount' && round((float)$data['amount'], 2) !== round((float)$existing['amount'], 2)) {
                            $isBlockedChanged = true;
                        } elseif ($field === 'invoice_id' && (int)$data['invoice_id'] !== (int)$existing['invoice_id']) {
                            $isBlockedChanged = true;
                        } elseif ($field !== 'amount' && $field !== 'invoice_id' && $data[$field] !== $existing[$field]) {
                            $isBlockedChanged = true;
                        }
                    }
                }

                if ($isBlockedChanged) {
                    if (!function_exists('logActivity')) {
                        require_once __DIR__ . '/../../../admin/modules_helper.php';
                    }
                    $reqId = bin2hex(random_bytes(16));
                    logActivity($userId, $companyId, 'payments', 'payments', $id, 'Blocked attempt to edit payment ' . $existing['payment_number'] . ' with official receipt', $this->pdo, $existing, $data, $reqId);
                    
                    require_once __DIR__ . '/../../../admin/timeline_helper.php';
                    logEntityEvent($companyId, 'payments', 'payments', $id, 'edit_blocked', $userId, "Tentative bloquée d'édition du paiement " . $existing['payment_number'] . " avec reçu officiel.", $this->pdo);

                    throw new Exception("Modification interdite: ce paiement dispose déjà d'un reçu officiel.", 409);
                }

                // If not blocked, only notes and reference updates are allowed
                $sql = "UPDATE payments SET 
                    reference = :reference,
                    notes = :notes
                    WHERE id = :id AND company_id = :company_id";

                $stmtUpdate = $this->pdo->prepare($sql);
                $stmtUpdate->execute([
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'id' => $id,
                    'company_id' => $companyId
                ]);
            } else {
                $invoiceId = (int)$existing['invoice_id'];
                $amount = (float)$data['amount'];
                $currency = $data['currency'] ?? 'CHF';

                // Validate Amount
                if ($amount <= 0) {
                    throw new Exception("Le montant du paiement doit être supérieur à zéro.", 409);
                }

                // 2. Fetch and Lock Invoice
                $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1 FOR UPDATE");
                $stmt->execute(['id' => $invoiceId, 'company_id' => $companyId]);
                $invoice = $stmt->fetch();

                if (!$invoice) {
                    throw new Exception("Facture introuvable.", 404);
                }

                // Validate Currency
                if ($currency !== $invoice['currency']) {
                    throw new Exception("La devise du paiement doit correspondre à la devise de la facture.", 409);
                }

                // Check remaining balance (excluding this payment's previous amount)
                $otherPaymentsSumStmt = $this->pdo->prepare("SELECT SUM(amount) FROM payments WHERE invoice_id = :invoice_id AND company_id = :company_id AND id != :id AND deleted_at IS NULL");
                $otherPaymentsSumStmt->execute(['invoice_id' => $invoiceId, 'company_id' => $companyId, 'id' => $id]);
                $otherPaymentsSum = (float)($otherPaymentsSumStmt->fetchColumn() ?? 0.00);

                $invoiceTotal = (float)$invoice['total'];
                $maxAllowed = $invoiceTotal - $otherPaymentsSum;

                if (round($amount, 2) > round($maxAllowed, 2)) {
                    throw new Exception("Le montant dépasse le solde restant autorisé (" . number_format($maxAllowed, 2) . " " . $currency . ").", 409);
                }

                // 3. Update Payment Row
                $sql = "UPDATE payments SET 
                    payment_date = :payment_date,
                    amount = :amount,
                    currency = :currency,
                    payment_method = :payment_method,
                    reference = :reference,
                    transaction_reference = :transaction_reference,
                    notes = :notes
                    WHERE id = :id AND company_id = :company_id";

                $stmtUpdate = $this->pdo->prepare($sql);
                $stmtUpdate->execute([
                    'payment_date' => $data['payment_date'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_method' => $data['payment_method'],
                    'reference' => $data['reference'] ?? null,
                    'transaction_reference' => $data['transaction_reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'id' => $id,
                    'company_id' => $companyId
                ]);
            }

            // 4. Recalculate Invoice
            $invoiceId = (int)$existing['invoice_id'];
            $this->recalculateInvoice($invoiceId, $companyId, $userId);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Soft delete payment and update invoice totals
     */
    public function softDelete($id, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Paiement introuvable.", 404);
            }

            // Pagamentos com recibo oficial não devem ser removidos. Retornar 409 Conflict.
            if (!empty($existing['receipt_path'])) {
                if (!function_exists('logActivity')) {
                    require_once __DIR__ . '/../../../admin/modules_helper.php';
                }
                $reqId = bin2hex(random_bytes(16));
                logActivity($userId, $companyId, 'payments', 'payments', $id, 'Blocked attempt to delete payment ' . $existing['payment_number'] . ' with official receipt', $this->pdo, $existing, null, $reqId);
                
                require_once __DIR__ . '/../../../admin/timeline_helper.php';
                logEntityEvent($companyId, 'payments', 'payments', $id, 'delete_blocked', $userId, "Tentative bloquée de suppression du paiement " . $existing['payment_number'] . " com recibo officiel.", $this->pdo);

                throw new Exception("Impossible de supprimer un paiement qui a déjà reçu un reçu officiel.", 409);
            }

            $invoiceId = (int)$existing['invoice_id'];

            // Validate Invoice status isn't cancelled
            $stmtInv = $this->pdo->prepare("SELECT status FROM invoices WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtInv->execute(['id' => $invoiceId, 'company_id' => $companyId]);
            $invStatus = $stmtInv->fetchColumn();
            if ($invStatus === 'Cancelled') {
                throw new Exception("Impossible de modifier ou supprimer un paiement lié à une facture annulée.", 409);
            }

            $stmt = $this->pdo->prepare("UPDATE payments SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id");
            $stmt->execute(['id' => $id, 'company_id' => $companyId]);

            $this->recalculateInvoice($invoiceId, $companyId, $userId);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reverses a payment by creating a negative counterpart record.
     */
    public function reverse($id, $reason, $userId, $companyId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Fetch and Lock payment
            $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1 FOR UPDATE");
            $stmt->execute(['id' => $id, 'company_id' => $companyId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                throw new Exception("Paiement introuvable.", 404);
            }

            // 2. Validate not already reversed
            if (!empty($payment['reversed_at'])) {
                throw new Exception("Ce paiement a déjà été extourné.", 409);
            }

            $invoiceId = (int)$payment['invoice_id'];

            // 3. Fetch and Lock Invoice
            $stmtInv = $this->pdo->prepare("SELECT * FROM invoices WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1 FOR UPDATE");
            $stmtInv->execute(['id' => $invoiceId, 'company_id' => $companyId]);
            $invoice = $stmtInv->fetch();

            if (!$invoice) {
                throw new Exception("Facture introuvable.", 404);
            }

            // 4. Validate Invoice isn't Cancelled
            if ($invoice['status'] === 'Cancelled') {
                throw new Exception("Impossible d'extourner un paiement lié à une facture annulée.", 409);
            }

            // 5. Generate new Sequence payment number (PAY)
            require_once __DIR__ . '/../../../admin/sequences_helper.php';
            $newPaymentNumber = generateSequence($companyId, 'PAY', $this->pdo);

            // 6. Update Original Payment Row
            $stmtUpdateOrig = $this->pdo->prepare("UPDATE payments SET 
                reversed_at = NOW(),
                reversed_by = :userId,
                reversal_reason = :reason
                WHERE id = :id");
            $stmtUpdateOrig->execute([
                'userId' => $userId,
                'reason' => $reason,
                'id' => $id
            ]);

            // 7. Insert Negative Payment Row
            $sqlNegative = "INSERT INTO payments (
                company_id, invoice_id, payment_number, payment_date, amount, currency, 
                payment_method, reference, transaction_reference, notes, received_by, reversal_payment_id
            ) VALUES (
                :company_id, :invoice_id, :payment_number, :payment_date, :amount, :currency, 
                :payment_method, :reference, :transaction_reference, :notes, :received_by, :reversal_payment_id
            )";

            $negativeAmount = -((float)$payment['amount']);
            $stmtInsertNeg = $this->pdo->prepare($sqlNegative);
            $stmtInsertNeg->execute([
                'company_id' => $companyId,
                'invoice_id' => $invoiceId,
                'payment_number' => $newPaymentNumber,
                'payment_date' => date('Y-m-d'),
                'amount' => $negativeAmount,
                'currency' => $payment['currency'],
                'payment_method' => $payment['payment_method'],
                'reference' => 'Contrepartie de ' . $payment['payment_number'],
                'transaction_reference' => $payment['transaction_reference'],
                'notes' => 'Extourne: ' . $reason,
                'received_by' => $userId,
                'reversal_payment_id' => $payment['id']
            ]);

            $reversalId = $this->pdo->lastInsertId();

            // 8. Recalculate Invoice (Paid Amount, Balance Due, status)
            $this->recalculateInvoice($invoiceId, $companyId, $userId);

            // 9. Timeline Logs
            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'payments', 'payments', $id, 'reversed', $userId, "Paiement extourné (Justification: " . $reason . "). Contrepartie: " . $newPaymentNumber, $this->pdo);
            logEntityEvent($companyId, 'payments', 'payments', $reversalId, 'created', $userId, "Contrepartie d'extourne créée: " . $newPaymentNumber . " (" . $negativeAmount . " " . $payment['currency'] . ")", $this->pdo);
            logEntityEvent($companyId, 'invoices', 'invoices', $invoiceId, 'payment_reversed', $userId, "Paiement extourné: " . $payment['payment_number'] . " (" . $payment['amount'] . " " . $payment['currency'] . ").", $this->pdo);

            // 10. Audit Logs using logActivity
            if (!function_exists('logActivity')) {
                require_once __DIR__ . '/../../../admin/modules_helper.php';
            }
            $reqId = bin2hex(random_bytes(16));
            $newReversalData = $this->getById($reversalId, $companyId);
            logActivity($userId, $companyId, 'payments', 'payments', $id, 'Reversed payment ' . $payment['payment_number'], $this->pdo, $payment, $newReversalData, $reqId, $id);

            $this->pdo->commit();
            return $reversalId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Recalculates the paid_amount, balance_due and updates the status of the invoice
     */
    private function recalculateInvoice($invoiceId, $companyId, $userId) {
        // Sum active payments
        $stmtSum = $this->pdo->prepare("SELECT SUM(amount) FROM payments WHERE invoice_id = :invoice_id AND company_id = :company_id AND deleted_at IS NULL");
        $stmtSum->execute(['invoice_id' => $invoiceId, 'company_id' => $companyId]);
        $paidAmount = (float)($stmtSum->fetchColumn() ?? 0.00);

        // Fetch invoice total
        $stmtInv = $this->pdo->prepare("SELECT total, status FROM invoices WHERE id = :id AND company_id = :company_id LIMIT 1");
        $stmtInv->execute(['id' => $invoiceId, 'company_id' => $companyId]);
        $invoice = $stmtInv->fetch();

        if (!$invoice) return;

        $total = (float)$invoice['total'];
        $balanceDue = $total - $paidAmount;
        if ($balanceDue < 0) {
            $balanceDue = 0.00;
        }

        // Determine automatically the new invoice status
        $oldStatus = $invoice['status'];
        $newStatus = $oldStatus;
        
        if ($paidAmount <= 0) {
            $newStatus = 'Sent';
        } elseif ($paidAmount > 0 && $paidAmount < $total) {
            $newStatus = 'Partially Paid';
        } elseif ($paidAmount >= $total) {
            $newStatus = 'Paid';
        }

        // Update Invoice row
        $stmtUpdate = $this->pdo->prepare("UPDATE invoices SET paid_amount = :paid, balance_due = :balance, status = :status WHERE id = :id");
        $stmtUpdate->execute([
            'paid' => $paidAmount,
            'balance' => $balanceDue,
            'status' => $newStatus,
            'id' => $invoiceId
        ]);

        if ($oldStatus !== $newStatus) {
            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'invoices', 'invoices', $invoiceId, strtolower($newStatus), $userId, "Statut de facture mis à jour automatiquement de '$oldStatus' à '$newStatus' suite à une modification de paiement.", $this->pdo);
        }
    }

    /**
     * Generates a static receipt file in private storage
     */
    public function generateReceipt($id, $companyId) {
        $payment = $this->getById($id, $companyId);
        if (!$payment) return null;

        // Ensure we only generate receipt once
        if (!empty($payment['receipt_path'])) {
            return $payment['receipt_path'];
        }

        // Fetch company data
        $stmtComp = $this->pdo->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
        $stmtComp->execute(['id' => $companyId]);
        $company = $stmtComp->fetch();

        $client = [
            'name' => $payment['client_name'],
            'company' => $payment['client_company'],
            'address' => '',
            'postal_code' => '',
            'city' => '',
            'country' => 'Suisse'
        ];

        // Items representing receipt details
        $items = [
            [
                'quantity' => 1.00,
                'unit_price' => $payment['amount'],
                'total' => $payment['amount'],
                'description' => "Paiement reçu pour la facture N° " . $payment['invoice_number'] . " via " . $payment['payment_method']
            ]
        ];

        $totals = [
            'subtotal' => $payment['amount'],
            'discount' => 0.00,
            'vat' => 0.00,
            'total' => $payment['amount']
        ];

        $dateInfo = [
            'issue_date' => date('d.m.Y', strtotime($payment['payment_date']))
        ];

        $html = PdfTemplate::generateHtml($company, $client, $items, $totals, "Reçu de Paiement", $payment['payment_number'], $dateInfo);

        $storageDir = __DIR__ . '/../../../../private_lima/storage/receipts';
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $fileName = $payment['payment_number'] . '_' . time() . '.html';
        $filePath = $storageDir . '/' . $fileName;
        file_put_contents($filePath, $html);

        $receiptPath = 'private_lima/storage/receipts/' . $fileName;

        // Save receipt path
        $stmt = $this->pdo->prepare("UPDATE payments SET receipt_path = :path WHERE id = :id");
        $stmt->execute(['path' => $receiptPath, 'id' => $id]);

        return $receiptPath;
    }
}
