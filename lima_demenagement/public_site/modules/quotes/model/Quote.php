<?php
// LIMA Solutions ERP - Quotes Model

class Quote {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Gets all active (non-soft-deleted) quotes for a company with filters.
     */
    public function getAll($companyId, $filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT q.*, c.name AS client_name, c.company AS client_company 
                FROM quotes q
                JOIN clients c ON q.client_id = c.id
                WHERE q.company_id = :company_id AND q.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (q.quote_number LIKE :search1 
                        OR c.name LIKE :search2 
                        OR c.company LIKE :search3 
                        OR q.notes LIKE :search4 
                        OR q.status LIKE :search5)";
            $val = '%' . $filters['search'] . '%';
            $params[':search1'] = $val;
            $params[':search2'] = $val;
            $params[':search3'] = $val;
            $params[':search4'] = $val;
            $params[':search5'] = $val;
        }
        if (!empty($filters['status'])) {
            $sql .= " AND q.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY q.created_at DESC LIMIT :limit OFFSET :offset";

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
     * Get total count of active quotes.
     */
    public function getTotalCount($companyId, $filters = []) {
        $sql = "SELECT COUNT(*) 
                FROM quotes q
                JOIN clients c ON q.client_id = c.id
                WHERE q.company_id = :company_id AND q.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (q.quote_number LIKE :search1 
                        OR c.name LIKE :search2 
                        OR c.company LIKE :search3 
                        OR q.notes LIKE :search4 
                        OR q.status LIKE :search5)";
            $val = '%' . $filters['search'] . '%';
            $params[':search1'] = $val;
            $params[':search2'] = $val;
            $params[':search3'] = $val;
            $params[':search4'] = $val;
            $params[':search5'] = $val;
        }
        if (!empty($filters['status'])) {
            $sql .= " AND q.status = :status";
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
     * Gets a single quote by ID and company ID.
     */
    public function getById($id, $companyId) {
        $sql = "SELECT q.*, c.name AS client_name, c.company AS client_company, c.address AS client_address, 
                       c.postal_code AS client_postal_code, c.city AS client_city, c.country AS client_country 
                FROM quotes q
                JOIN clients c ON q.client_id = c.id
                WHERE q.id = :id AND q.company_id = :company_id AND q.deleted_at IS NULL LIMIT 1";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Gets all items belonging to a quote.
     */
    public function getItems($quoteId, $companyId) {
        $sql = "SELECT qi.*, u.code AS unit_code, t.name AS tax_name, t.rate AS tax_rate 
                FROM quote_items qi
                LEFT JOIN units u ON qi.unit_id = u.id
                LEFT JOIN tax_rates t ON qi.tax_rate_id = t.id
                WHERE qi.quote_id = :quote_id AND qi.company_id = :company_id
                ORDER BY qi.position ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':quote_id', $quoteId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Backward compatibility search method.
     */
    public function search($term, $companyId, $limit = 50, $offset = 0) {
        return $this->getAll($companyId, ['search' => $term], $limit, $offset);
    }

    /**
     * Backward compatibility search count method.
     */
    public function getSearchCount($term, $companyId) {
        return $this->getTotalCount($companyId, ['search' => $term]);
    }

    /**
     * Inserts a Quote and its items in a single transaction.
     */
    public function create($data, $items, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // 1. Validate that the client belongs to this company to prevent cross-company references
            $stmtClient = $this->pdo->prepare("SELECT id FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtClient->execute(['id' => $data['client_id'], 'company_id' => $companyId]);
            if (!$stmtClient->fetchColumn()) {
                throw new InvalidArgumentException("Le client sélectionné n'appartient pas à votre entreprise.");
            }

            // 2. Insert main Quote row
            $sqlQuote = "INSERT INTO quotes (
                company_id, client_id, quote_number, status, issue_date, valid_until, 
                currency, subtotal, discount_amount, discount_percent, tax_total, total, 
                notes, internal_notes, created_by
            ) VALUES (
                :company_id, :client_id, :quote_number, :status, :issue_date, :valid_until, 
                :currency, :subtotal, :discount_amount, :discount_percent, :tax_total, :total, 
                :notes, :internal_notes, :created_by
            )";

            $stmtQ = $this->pdo->prepare($sqlQuote);
            $stmtQ->execute([
                'company_id' => $companyId,
                'client_id' => $data['client_id'],
                'quote_number' => $data['quote_number'],
                'status' => $data['status'] ?? 'Draft',
                'issue_date' => $data['issue_date'],
                'valid_until' => $data['valid_until'],
                'currency' => $data['currency'] ?? 'CHF',
                'subtotal' => $data['subtotal'],
                'discount_amount' => $data['discount_amount'],
                'discount_percent' => $data['discount_percent'],
                'tax_total' => $data['tax_total'],
                'total' => $data['total'],
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $userId
            ]);

            $quoteId = $this->pdo->lastInsertId();

            // 3. Insert Items
            $sqlItem = "INSERT INTO quote_items (
                company_id, quote_id, position, description, quantity, 
                unit_id, unit_price, discount_percent, tax_rate_id, 
                subtotal, tax_amount, total
            ) VALUES (
                :company_id, :quote_id, :position, :description, :quantity, 
                :unit_id, :unit_price, :discount_percent, :tax_rate_id, 
                :subtotal, :tax_amount, :total
            )";
            $stmtI = $this->pdo->prepare($sqlItem);

            foreach ($items as $idx => $item) {
                $stmtI->execute([
                    'company_id' => $companyId,
                    'quote_id' => $quoteId,
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
            return $quoteId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Updates an existing Quote and recreates its items in a transaction.
     */
    public function update($id, $data, $items, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Validate that the quote belongs to this company and is not soft deleted
            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new InvalidArgumentException("Devis non trouvé ou non autorisé.");
            }

            // Validate that the client belongs to this company
            $stmtClient = $this->pdo->prepare("SELECT id FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtClient->execute(['id' => $data['client_id'], 'company_id' => $companyId]);
            if (!$stmtClient->fetchColumn()) {
                throw new InvalidArgumentException("Le client sélectionné n'appartient pas à votre entreprise.");
            }

            // 1. Update Quote Main Row
            $sql = "UPDATE quotes SET 
                client_id = :client_id,
                status = :status,
                issue_date = :issue_date,
                valid_until = :valid_until,
                currency = :currency,
                subtotal = :subtotal,
                discount_amount = :discount_amount,
                discount_percent = :discount_percent,
                tax_total = :tax_total,
                total = :total,
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
                'valid_until' => $data['valid_until'],
                'currency' => $data['currency'] ?? 'CHF',
                'subtotal' => $data['subtotal'],
                'discount_amount' => $data['discount_amount'],
                'discount_percent' => $data['discount_percent'],
                'tax_total' => $data['tax_total'],
                'total' => $data['total'],
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null
            ]);

            // 2. Delete old items
            $stmtDel = $this->pdo->prepare("DELETE FROM quote_items WHERE quote_id = :quote_id AND company_id = :company_id");
            $stmtDel->execute(['quote_id' => $id, 'company_id' => $companyId]);

            // 3. Re-insert items
            $sqlItem = "INSERT INTO quote_items (
                company_id, quote_id, position, description, quantity, 
                unit_id, unit_price, discount_percent, tax_rate_id, 
                subtotal, tax_amount, total
            ) VALUES (
                :company_id, :quote_id, :position, :description, :quantity, 
                :unit_id, :unit_price, :discount_percent, :tax_rate_id, 
                :subtotal, :tax_amount, :total
            )";
            $stmtI = $this->pdo->prepare($sqlItem);

            foreach ($items as $idx => $item) {
                $stmtI->execute([
                    'company_id' => $companyId,
                    'quote_id' => $id,
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
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Updates only status of a quote.
     */
    public function changeStatus($id, $status, $companyId, $userId) {
        $validStatuses = ['Draft', 'Sent', 'Accepted', 'Rejected', 'Expired'];
        if (!in_array($status, $validStatuses)) {
            throw new InvalidArgumentException("Statut invalide.");
        }
        $stmt = $this->pdo->prepare("UPDATE quotes SET status = :status WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL");
        return $stmt->execute([
            'status' => $status,
            'id' => $id,
            'company_id' => $companyId
        ]);
    }

    /**
     * Backward compatibility method.
     */
    public function updateStatus($id, $status, $companyId) {
        return $this->changeStatus($id, $status, $companyId, 0);
    }

    /**
     * Performs a Soft Delete by setting deleted_at = NOW()
     */
    public function softDelete($id, $companyId, $userId) {
        $stmt = $this->pdo->prepare("UPDATE quotes SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id");
        return $stmt->execute([
            'id' => $id,
            'company_id' => $companyId
        ]);
    }

    /**
     * Backward compatibility method.
     */
    public function deactivate($id, $companyId) {
        return $this->softDelete($id, $companyId, 0);
    }

    /**
     * Renders visual HTML representation ready for PDF conversion.
     */
    public function renderPdf($id, $companyId) {
        $quote = $this->getById($id, $companyId);
        if (!$quote) {
            throw new InvalidArgumentException("Devis non trouvé.");
        }
        $items = $this->getItems($id, $companyId);

        $stmtComp = $this->pdo->prepare("SELECT * FROM companies WHERE id = :id LIMIT 1");
        $stmtComp->execute(['id' => $companyId]);
        $company = $stmtComp->fetch();

        $dateInfo = [
            'issue_date' => date('d.m.Y', strtotime($quote['issue_date'])),
            'due_date' => date('d.m.Y', strtotime($quote['valid_until']))
        ];
        $totals = [
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['discount_amount'],
            'vat' => $quote['tax_total'],
            'total' => $quote['total']
        ];
        return PdfTemplate::generateHtml($company, $quote, $items, $totals, "Devis d'Offre", $quote['quote_number'], $dateInfo);
    }
}
