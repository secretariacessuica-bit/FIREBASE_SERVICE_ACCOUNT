<?php
// LIMA Solutions ERP - Invoices Model

class Invoice {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Gets all active (non-soft-deleted) invoices for a company with filters.
     */
    public function getAll($companyId, $filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT i.*, c.name AS client_name, c.company AS client_company 
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                WHERE i.company_id = :company_id AND i.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (i.invoice_number LIKE :search1 
                        OR c.name LIKE :search2 
                        OR c.company LIKE :search3 
                        OR i.notes LIKE :search4 
                        OR i.status LIKE :search5)";
            $val = '%' . $filters['search'] . '%';
            $params[':search1'] = $val;
            $params[':search2'] = $val;
            $params[':search3'] = $val;
            $params[':search4'] = $val;
            $params[':search5'] = $val;
        }
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY i.created_at DESC LIMIT :limit OFFSET :offset";

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
     * Get total count of active invoices.
     */
    public function getTotalCount($companyId, $filters = []) {
        $sql = "SELECT COUNT(*) 
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                WHERE i.company_id = :company_id AND i.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (i.invoice_number LIKE :search1 
                        OR c.name LIKE :search2 
                        OR c.company LIKE :search3 
                        OR i.notes LIKE :search4 
                        OR i.status LIKE :search5)";
            $val = '%' . $filters['search'] . '%';
            $params[':search1'] = $val;
            $params[':search2'] = $val;
            $params[':search3'] = $val;
            $params[':search4'] = $val;
            $params[':search5'] = $val;
        }
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = :status";
            $params[':status'] = $filters['status'];
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Gets a single invoice by ID and company ID.
     */
    public function getById($id, $companyId) {
        $sql = "SELECT i.*, c.name AS client_name, c.company AS client_company, c.address AS client_address, 
                       c.postal_code AS client_postal_code, c.city AS client_city, c.country AS client_country 
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                WHERE i.id = :id AND i.company_id = :company_id AND i.deleted_at IS NULL LIMIT 1";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Gets all items belonging to an invoice.
     */
    public function getItems($invoiceId, $companyId) {
        $sql = "SELECT ii.*, u.code AS unit_code, t.name AS tax_name, t.rate AS tax_rate 
                FROM invoice_items ii
                LEFT JOIN units u ON ii.unit_id = u.id
                LEFT JOIN tax_rates t ON ii.tax_rate_id = t.id
                WHERE ii.invoice_id = :invoice_id AND ii.company_id = :company_id
                ORDER BY ii.position ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Inserts an Invoice and its items in a single transaction.
     */
    public function create($data, $items, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Validate that the client belongs to this company
            $stmtClient = $this->pdo->prepare("SELECT id FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtClient->execute(['id' => $data['client_id'], 'company_id' => $companyId]);
            if (!$stmtClient->fetchColumn()) {
                throw new InvalidArgumentException("Le client sélectionné n'appartient pas à votre entreprise.");
            }

            // 2. Insert main Invoice row
            $sqlInvoice = "INSERT INTO invoices (
                company_id, client_id, quote_id, invoice_number, status, issue_date, due_date, 
                currency, subtotal, discount_amount, discount_percent, tax_total, total, 
                paid_amount, balance_due, notes, internal_notes, created_by
            ) VALUES (
                :company_id, :client_id, :quote_id, :invoice_number, :status, :issue_date, :due_date, 
                :currency, :subtotal, :discount_amount, :discount_percent, :tax_total, :total, 
                :paid_amount, :balance_due, :notes, :internal_notes, :created_by
            )";

            $stmtI = $this->pdo->prepare($sqlInvoice);
            $stmtI->execute([
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'quote_id' => $data['quote_id'] ?? null,
                'invoice_number' => $data['invoice_number'],
                'status' => $data['status'] ?? 'Draft',
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'currency' => $data['currency'] ?? 'CHF',
                'subtotal' => $data['subtotal'],
                'discount_amount' => $data['discount_amount'],
                'discount_percent' => $data['discount_percent'],
                'tax_total' => $data['tax_total'],
                'total' => $data['total'],
                'paid_amount' => $data['paid_amount'] ?? 0.00,
                'balance_due' => $data['balance_due'],
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $userId
            ]);

            $invoiceId = $this->pdo->lastInsertId();

            // 3. Insert Items
            $sqlItem = "INSERT INTO invoice_items (
                company_id, invoice_id, position, description, quantity, 
                unit_id, unit_price, discount_percent, tax_rate_id, 
                subtotal, tax_amount, total
            ) VALUES (
                :company_id, :invoice_id, :position, :description, :quantity, 
                :unit_id, :unit_price, :discount_percent, :tax_rate_id, 
                :subtotal, :tax_amount, :total
            )";
            $stmtItem = $this->pdo->prepare($sqlItem);

            foreach ($items as $idx => $item) {
                $stmtItem->execute([
                    'company_id' => $companyId,
                    'invoice_id' => $invoiceId,
                    'position' => $idx + 1,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_id' => !empty($item['unit_id']) ? $item['unit_id'] : null,
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'] ?? 0.00,
                    'tax_rate_id' => !empty($item['tax_rate_id']) ? $item['tax_rate_id'] : null,
                    'subtotal' => $item['subtotal'],
                    'tax_amount' => $item['tax_amount'],
                    'total' => $item['total']
                ]);
            }

            $this->pdo->commit();

            // Check if status is immediately Issued/Sent to trigger fiscal seal
            $initialStatus = $data['status'] ?? 'Draft';
            if (in_array($initialStatus, ['Issued', 'Sent'])) {
                $this->sealFiscalDocument($invoiceId, $initialStatus, $companyId, $userId);
            }

            return $invoiceId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Updates an existing Invoice and recreates its items in a transaction.
     */
    public function update($id, $data, $items, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Validate that the invoice belongs to this company
            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new InvalidArgumentException("Facture non trouvée ou non autorisée.");
            }

            // Check fiscal locking status
            $lockedStatuses = ['Issued', 'Sent', 'Paid', 'Partially Paid', 'Cancelled'];
            if (in_array($existing['status'], $lockedStatuses)) {
                // Determine if any core field was altered
                $isDifferent = false;
                if ((int)$data['client_id'] !== (int)$existing['client_id'] ||
                    (float)$data['total'] !== (float)$existing['total'] ||
                    (float)$data['subtotal'] !== (float)$existing['subtotal'] ||
                    $data['issue_date'] !== $existing['issue_date'] ||
                    $data['due_date'] !== $existing['due_date'] ||
                    $data['currency'] !== $existing['currency']) {
                    $isDifferent = true;
                }

                if (!$isDifferent) {
                    $existingItems = $this->getItems($id, $companyId);
                    if (count($items) !== count($existingItems)) {
                        $isDifferent = true;
                    } else {
                        foreach ($items as $idx => $item) {
                            if ($item['description'] !== $existingItems[$idx]['description'] ||
                                (float)$item['quantity'] !== (float)$existingItems[$idx]['quantity'] ||
                                (float)$item['unit_price'] !== (float)$existingItems[$idx]['unit_price']) {
                                $isDifferent = true;
                                break;
                            }
                        }
                    }
                }

                if ($isDifferent) {
                    throw new Exception("Modification interdite: Facture verrouillée fiscalement.", 409);
                }

                // Core fields identical, only update notes
                $sql = "UPDATE invoices SET notes = :notes, internal_notes = :internal_notes WHERE id = :id AND company_id = :company_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'notes' => $data['notes'] ?? null,
                    'internal_notes' => $data['internal_notes'] ?? null,
                    'id' => $id,
                    'company_id' => $companyId
                ]);

                $this->pdo->commit();
                return true;
            }

            // Validate that the client belongs to this company
            $stmtClient = $this->pdo->prepare("SELECT id FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtClient->execute(['id' => $data['client_id'], 'company_id' => $companyId]);
            if (!$stmtClient->fetchColumn()) {
                throw new InvalidArgumentException("Le client sélectionné n'appartient pas à votre entreprise.");
            }

            // 1. Update Invoice Main Row
            $sql = "UPDATE invoices SET 
                client_id = :client_id,
                status = :status,
                issue_date = :issue_date,
                due_date = :due_date,
                currency = :currency,
                subtotal = :subtotal,
                discount_amount = :discount_amount,
                discount_percent = :discount_percent,
                tax_total = :tax_total,
                total = :total,
                paid_amount = :paid_amount,
                balance_due = :balance_due,
                notes = :notes,
                internal_notes = :internal_notes
                WHERE id = :id AND company_id = :company_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'status' => $data['status'] ?? 'Draft',
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'currency' => $data['currency'] ?? 'CHF',
                'subtotal' => $data['subtotal'],
                'discount_amount' => $data['discount_amount'],
                'discount_percent' => $data['discount_percent'],
                'tax_total' => $data['tax_total'],
                'total' => $data['total'],
                'paid_amount' => $data['paid_amount'] ?? 0.00,
                'balance_due' => $data['balance_due'],
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null
            ]);

            // 2. Delete old items
            $stmtDel = $this->pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = :invoice_id AND company_id = :company_id");
            $stmtDel->execute(['invoice_id' => $id, 'company_id' => $companyId]);

            // 3. Re-insert items
            $sqlItem = "INSERT INTO invoice_items (
                company_id, invoice_id, position, description, quantity, 
                unit_id, unit_price, discount_percent, tax_rate_id, 
                subtotal, tax_amount, total
            ) VALUES (
                :company_id, :invoice_id, :position, :description, :quantity, 
                :unit_id, :unit_price, :discount_percent, :tax_rate_id, 
                :subtotal, :tax_amount, :total
            )";
            $stmtI = $this->pdo->prepare($sqlItem);

            foreach ($items as $idx => $item) {
                $stmtI->execute([
                    'company_id' => $companyId,
                    'invoice_id' => $id,
                    'position' => $idx + 1,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_id' => !empty($item['unit_id']) ? $item['unit_id'] : null,
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'] ?? 0.00,
                    'tax_rate_id' => !empty($item['tax_rate_id']) ? $item['tax_rate_id'] : null,
                    'subtotal' => $item['subtotal'],
                    'tax_amount' => $item['tax_amount'],
                    'total' => $item['total']
                ]);
            }

            $this->pdo->commit();

            // Se mudou para Issued/Sent, sela a fatura
            $newStatus = $data['status'] ?? 'Draft';
            if (in_array($newStatus, ['Issued', 'Sent'])) {
                $this->sealFiscalDocument($id, $newStatus, $companyId, $userId);
            }

            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Converts a Quote into an Invoice in a thread-safe transaction.
     */
    public function convertFromQuote($quoteId, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Fetch Quote
            $stmtQ = $this->pdo->prepare("SELECT * FROM quotes WHERE id = :id AND company_id = :cid AND deleted_at IS NULL LIMIT 1");
            $stmtQ->execute(['id' => $quoteId, 'cid' => $companyId]);
            $quote = $stmtQ->fetch();

            if (!$quote) {
                throw new InvalidArgumentException("Le devis sélectionné est introuvable ou n'appartient pas à cette entreprise.");
            }

            // 2. Check if Quote is already converted to an active Invoice
            $stmtCheck = $this->pdo->prepare("SELECT id FROM invoices WHERE quote_id = :qid AND deleted_at IS NULL LIMIT 1");
            $stmtCheck->execute(['qid' => $quoteId]);
            if ($stmtCheck->fetchColumn()) {
                throw new InvalidArgumentException("Ce devis a déjà été converti en facture.");
            }

            // 3. Generate Sequence for Invoice: INV-XXXXXX
            $invoiceNumber = generateSequence($companyId, 'INV', $this->pdo);

            // 4. Create Invoice
            $sqlInvoice = "INSERT INTO invoices (
                company_id, client_id, quote_id, invoice_number, status, issue_date, due_date, 
                currency, subtotal, discount_amount, discount_percent, tax_total, total, 
                paid_amount, balance_due, notes, internal_notes, created_by
            ) VALUES (
                :company_id, :client_id, :quote_id, :invoice_number, :status, :issue_date, :due_date, 
                :currency, :subtotal, :discount_amount, :discount_percent, :tax_total, :total, 
                :paid_amount, :balance_due, :notes, :internal_notes, :created_by
            )";

            $issueDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+30 days'));

            $stmtI = $this->pdo->prepare($sqlInvoice);
            $stmtI->execute([
                'company_id' => $companyId,
                'client_id' => $quote['client_id'],
                'quote_id' => $quoteId,
                'invoice_number' => $invoiceNumber,
                'status' => 'Draft',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'currency' => $quote['currency'] ?? 'CHF',
                'subtotal' => $quote['subtotal'],
                'discount_amount' => $quote['discount_amount'],
                'discount_percent' => $quote['discount_percent'],
                'tax_total' => $quote['tax_total'],
                'total' => $quote['total'],
                'paid_amount' => 0.00,
                'balance_due' => $quote['total'],
                'notes' => $quote['notes'],
                'internal_notes' => "Converti à partir du devis N° " . $quote['quote_number'],
                'created_by' => $userId
            ]);

            $invoiceId = $this->pdo->lastInsertId();

            // 5. Fetch items from Quote
            $stmtQI = $this->pdo->prepare("SELECT * FROM quote_items WHERE quote_id = :qid AND company_id = :cid ORDER BY position ASC");
            $stmtQI->execute(['qid' => $quoteId, 'cid' => $companyId]);
            $quoteItems = $stmtQI->fetchAll();

            // 6. Insert items to Invoice
            $sqlItem = "INSERT INTO invoice_items (
                company_id, invoice_id, position, description, quantity, 
                unit_id, unit_price, discount_percent, tax_rate_id, 
                subtotal, tax_amount, total
            ) VALUES (
                :company_id, :invoice_id, :position, :description, :quantity, 
                :unit_id, :unit_price, :discount_percent, :tax_rate_id, 
                :subtotal, :tax_amount, :total
            )";
            $stmtItem = $this->pdo->prepare($sqlItem);

            foreach ($quoteItems as $item) {
                $stmtItem->execute([
                    'company_id' => $companyId,
                    'invoice_id' => $invoiceId,
                    'position' => $item['position'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'],
                    'tax_rate_id' => $item['tax_rate_id'],
                    'subtotal' => $item['subtotal'],
                    'tax_amount' => $item['tax_amount'],
                    'total' => $item['total']
                ]);
            }

            $this->pdo->commit();
            return $invoiceId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Updates only status of an invoice.
     */
    public function changeStatus($id, $status, $companyId, $userId, $cancellationReason = null) {
        $validStatuses = ['Draft', 'Issued', 'Sent', 'Paid', 'Partially Paid', 'Cancelled', 'Overdue'];
        if (!in_array($status, $validStatuses)) {
            throw new InvalidArgumentException("Statut invalide.");
        }

        $existing = $this->getById($id, $companyId);
        if (!$existing) {
            throw new InvalidArgumentException("Facture introuvable.");
        }

        // If status is Cancelled, prevent any future status updates
        if ($existing['status'] === 'Cancelled') {
            throw new Exception("Modification interdite: Facture annulée et verrouillée.", 409);
        }

        // Handle Status mutation
        if ($status === 'Cancelled') {
            $stmt = $this->pdo->prepare("UPDATE invoices SET status = :status, cancellation_reason = :reason WHERE id = :id AND company_id = :company_id");
            return $stmt->execute([
                'status' => $status,
                'reason' => $cancellationReason,
                'id' => $id,
                'company_id' => $companyId
            ]);
        }

        $stmt = $this->pdo->prepare("UPDATE invoices SET status = :status WHERE id = :id AND company_id = :company_id");
        $res = $stmt->execute([
            'status' => $status,
            'id' => $id,
            'company_id' => $companyId
        ]);

        if ($res && in_array($status, ['Issued', 'Sent'])) {
            $this->sealFiscalDocument($id, $status, $companyId, $userId);
        }

        return $res;
    }

    /**
     * Generates immutable signature, snapshot and static HTML for official invoice.
     */
    private function sealFiscalDocument($id, $status, $companyId, $userId) {
        $existing = $this->getById($id, $companyId);
        if (!$existing) return;

        // Ensure we only seal once
        if (!empty($existing['pdf_path'])) return;

        // 1. Generate Document Hash
        $hashData = $existing['invoice_number'] . '|' . $companyId . '|' . $existing['client_id'] . '|' . $existing['total'] . '|' . $existing['issue_date'];
        $hash = hash('sha256', $hashData);

        // 2. Generate Fiscal Snapshot
        $stmtComp = $this->pdo->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
        $stmtComp->execute(['id' => $companyId]);
        $company = $stmtComp->fetch();

        $client = [
            'name' => $existing['client_name'],
            'company' => $existing['client_company'],
            'address' => $existing['client_address'],
            'postal_code' => $existing['client_postal_code'],
            'city' => $existing['client_city'],
            'country' => $existing['client_country']
        ];

        $items = $this->getItems($id, $companyId);

        $snapshot = [
            'company' => $company,
            'client' => $client,
            'items' => $items,
            'totals' => [
                'subtotal' => $existing['subtotal'],
                'discount_amount' => $existing['discount_amount'],
                'discount_percent' => $existing['discount_percent'],
                'tax_total' => $existing['tax_total'],
                'total' => $existing['total']
            ]
        ];
        $snapshotJson = json_encode($snapshot);

        // 3. Generate Static HTML File in private storage
        $dateInfo = [
            'issue_date' => date('d.m.Y', strtotime($existing['issue_date'])),
            'due_date' => date('d.m.Y', strtotime($existing['due_date']))
        ];
        $totals = [
            'subtotal' => $existing['subtotal'],
            'discount' => $existing['discount_amount'],
            'vat' => $existing['tax_total'],
            'total' => $existing['total']
        ];
        
        $html = PdfTemplate::generateHtml($company, $existing, $items, $totals, "Facture de Vente", $existing['invoice_number'], $dateInfo);

        $storageDir = __DIR__ . '/../../../../private_lima/storage/invoices';
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $fileName = $existing['invoice_number'] . '_' . time() . '.html';
        $filePath = $storageDir . '/' . $fileName;
        file_put_contents($filePath, $html);

        $pdfPath = 'private_lima/storage/invoices/' . $fileName;

        // 4. Save to Database
        $stmt = $this->pdo->prepare("UPDATE invoices SET document_hash = :hash, fiscal_snapshot = :snapshot, pdf_path = :pdf_path WHERE id = :id");
        $stmt->execute([
            'hash' => $hash,
            'snapshot' => $snapshotJson,
            'pdf_path' => $pdfPath,
            'id' => $id
        ]);
    }

    /**
     * Performs a Soft Delete by setting deleted_at = NOW(). Prevent delete on issued invoices.
     */
    public function softDelete($id, $companyId, $userId) {
        $existing = $this->getById($id, $companyId);
        if (!$existing) {
            throw new InvalidArgumentException("Facture introuvable.");
        }

        // Issued/Sent/Paid invoices cannot be soft deleted to preserve audit trails
        $protectedStatuses = ['Issued', 'Sent', 'Paid', 'Partially Paid', 'Cancelled'];
        if (in_array($existing['status'], $protectedStatuses)) {
            throw new Exception("Suppression interdite: Cette facture fait partie des registres fiscaux officiels.", 409);
        }

        $this->pdo->beginTransaction();
        try {
            // Unlock timesheets associated with this draft invoice so they can be billed again
            $stmtUnlock = $this->pdo->prepare("UPDATE timesheets 
                                               SET invoice_id = NULL, invoiced_at = NULL, locked = 0, billing_batch_id = NULL 
                                               WHERE invoice_id = :invoice_id AND company_id = :company_id");
            $stmtUnlock->execute([
                'invoice_id' => $id,
                'company_id' => $companyId
            ]);

            $stmt = $this->pdo->prepare("UPDATE invoices SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id");
            $res = $stmt->execute([
                'id' => $id,
                'company_id' => $companyId
            ]);

            $this->pdo->commit();
            return $res;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Renders visual HTML representation. Reads static html file if sealed, otherwise renders dynamically.
     */
    public function renderPdf($id, $companyId, $forceDynamic = false) {
        $invoice = $this->getById($id, $companyId);
        if (!$invoice) {
            throw new InvalidArgumentException("Facture non trouvée.");
        }

        // If static PDF file is stored and exists, return static file contents
        if (!$forceDynamic && !empty($invoice['pdf_path'])) {
            $filePath = __DIR__ . '/../../../../' . $invoice['pdf_path'];
            if (file_exists($filePath)) {
                return file_get_contents($filePath);
            }
        }

        // Else, dynamically render template (used for Draft preview or first generation)
        $items = $this->getItems($id, $companyId);

        $stmtComp = $this->pdo->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
        $stmtComp->execute(['id' => $companyId]);
        $company = $stmtComp->fetch();

        $dateInfo = [
            'issue_date' => date('d.m.Y', strtotime($invoice['issue_date'])),
            'due_date' => date('d.m.Y', strtotime($invoice['due_date']))
        ];
        $totals = [
            'subtotal' => $invoice['subtotal'],
            'discount' => $invoice['discount_amount'],
            'vat' => $invoice['tax_total'],
            'total' => $invoice['total']
        ];
        return PdfTemplate::generateHtml($company, $invoice, $items, $totals, "Facture de Vente", $invoice['invoice_number'], $dateInfo);
    }
}
