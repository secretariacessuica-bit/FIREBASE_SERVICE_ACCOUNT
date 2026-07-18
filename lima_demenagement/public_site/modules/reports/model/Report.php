<?php
// LIMA Solutions ERP - Reports Model
class Report {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Bind only PDO placeholders that exist in the SQL (prevents HY093 errors).
     */
    private function filterParams(array $params, string $sql): array {
        preg_match_all('/:([a-z_]+)/', $sql, $matches);
        $allowed = array_flip(array_map(static fn($name) => ':' . $name, $matches[1] ?? []));
        return array_intersect_key($params, $allowed);
    }

    /**
     * Fetch executive KPIs scoped by company_id and optional filters
     */
    public function getKPIs($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        
        // Date filters for payments
        $payDateFilter = "";
        if (!empty($filters['start_date'])) {
            $payDateFilter .= " AND payment_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $payDateFilter .= " AND payment_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['currency'])) {
            $payDateFilter .= " AND invoice_id IN (SELECT id FROM invoices WHERE company_id = :cid_curr AND currency = :currency)";
            $params[':currency'] = $filters['currency'];
            $params[':cid_curr'] = $companyId;
        }
        if (!empty($filters['client_id'])) {
            $payDateFilter .= " AND invoice_id IN (SELECT id FROM invoices WHERE client_id = :client_id AND company_id = :cid_pay)";
            $params[':client_id'] = $filters['client_id'];
            $params[':cid_pay'] = $companyId;
        }

        // Date filters for invoices
        $invDateFilter = "";
        if (!empty($filters['start_date'])) {
            $invDateFilter .= " AND issue_date >= :start_date_inv";
            $params[':start_date_inv'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $invDateFilter .= " AND issue_date <= :end_date_inv";
            $params[':end_date_inv'] = $filters['end_date'];
        }
        if (!empty($filters['currency'])) {
            $invDateFilter .= " AND currency = :currency_inv";
            $params[':currency_inv'] = $filters['currency'];
        }
        if (!empty($filters['client_id'])) {
            $invDateFilter .= " AND client_id = :client_id_inv";
            $params[':client_id_inv'] = $filters['client_id'];
        }

        // Date filters for quotes
        $qDateFilter = "";
        if (!empty($filters['start_date'])) {
            $qDateFilter .= " AND issue_date >= :start_date_q";
            $params[':start_date_q'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $qDateFilter .= " AND issue_date <= :end_date_q";
            $params[':end_date_q'] = $filters['end_date'];
        }
        if (!empty($filters['currency'])) {
            $qDateFilter .= " AND currency = :currency_q";
            $params[':currency_q'] = $filters['currency'];
        }
        if (!empty($filters['client_id'])) {
            $qDateFilter .= " AND client_id = :client_id_q";
            $params[':client_id_q'] = $filters['client_id'];
        }

        // 1. Receita (Hoje, Mês, Ano)
        // Note: we fetch the sums directly
        $sqlToday = "SELECT SUM(amount) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND payment_date = CURRENT_DATE() $payDateFilter";
        $stmt = $this->pdo->prepare($sqlToday);
        $stmt->execute($this->filterParams($params, $sqlToday));
        $revenueToday = (float)($stmt->fetchColumn() ?? 0.00);

        $sqlMonth = "SELECT SUM(amount) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE()) $payDateFilter";
        $stmt = $this->pdo->prepare($sqlMonth);
        $stmt->execute($this->filterParams($params, $sqlMonth));
        $revenueMonth = (float)($stmt->fetchColumn() ?? 0.00);

        $sqlYear = "SELECT SUM(amount) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND YEAR(payment_date) = YEAR(CURRENT_DATE()) $payDateFilter";
        $stmt = $this->pdo->prepare($sqlYear);
        $stmt->execute($this->filterParams($params, $sqlYear));
        $revenueYear = (float)($stmt->fetchColumn() ?? 0.00);

        // 2. Facturação (Total faturado, recebido, pendente, estornado)
        $sqlBilled = "SELECT SUM(total) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status != 'Draft' AND status != 'Cancelled' $invDateFilter";
        $stmt = $this->pdo->prepare($sqlBilled);
        $stmt->execute($this->filterParams($params, $sqlBilled));
        $totalBilled = (float)($stmt->fetchColumn() ?? 0.00);

        $sqlReceived = "SELECT SUM(paid_amount) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status != 'Draft' AND status != 'Cancelled' $invDateFilter";
        $stmt = $this->pdo->prepare($sqlReceived);
        $stmt->execute($this->filterParams($params, $sqlReceived));
        $totalReceived = (float)($stmt->fetchColumn() ?? 0.00);

        $sqlPending = "SELECT SUM(balance_due) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status != 'Draft' AND status != 'Cancelled' $invDateFilter";
        $stmt = $this->pdo->prepare($sqlPending);
        $stmt->execute($this->filterParams($params, $sqlPending));
        $totalPending = (float)($stmt->fetchColumn() ?? 0.00);

        $sqlReversed = "SELECT SUM(amount) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND amount < 0 $payDateFilter";
        $stmt = $this->pdo->prepare($sqlReversed);
        $stmt->execute($this->filterParams($params, $sqlReversed));
        $totalReversed = abs((float)($stmt->fetchColumn() ?? 0.00));

        // 3. Operacional (Clientes ativos, novos clientes, faturas emitidas, orçamentos emitidos)
        $sqlClients = "SELECT COUNT(*) FROM clients WHERE company_id = :cid AND active = 1";
        $stmt = $this->pdo->prepare($sqlClients);
        $stmt->execute([':cid' => $companyId]);
        $clientsActive = (int)$stmt->fetchColumn();

        $sqlNewClients = "SELECT COUNT(*) FROM clients WHERE company_id = :cid AND active = 1 AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $stmt = $this->pdo->prepare($sqlNewClients);
        $stmt->execute([':cid' => $companyId]);
        $clientsNew = (int)$stmt->fetchColumn();

        $sqlInvoicesCount = "SELECT COUNT(*) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status != 'Draft' AND status != 'Cancelled' $invDateFilter";
        $stmt = $this->pdo->prepare($sqlInvoicesCount);
        $stmt->execute($this->filterParams($params, $sqlInvoicesCount));
        $invoicesCount = (int)$stmt->fetchColumn();

        $sqlQuotesCount = "SELECT COUNT(*) FROM quotes WHERE company_id = :cid AND deleted_at IS NULL AND status != 'Draft' $qDateFilter";
        $stmt = $this->pdo->prepare($sqlQuotesCount);
        $stmt->execute($this->filterParams($params, $sqlQuotesCount));
        $quotesCount = (int)$stmt->fetchColumn();

        // 4. Comercial (Conversion, Ticket Médio, LTV Médio)
        $sqlConvertedQuotes = "SELECT COUNT(DISTINCT quote_id) FROM invoices WHERE company_id = :cid AND quote_id IS NOT NULL AND deleted_at IS NULL $invDateFilter";
        $stmt = $this->pdo->prepare($sqlConvertedQuotes);
        $stmt->execute($this->filterParams($params, $sqlConvertedQuotes));
        $convertedQuotes = (int)$stmt->fetchColumn();

        $conversionRate = $quotesCount > 0 ? round(($convertedQuotes / $quotesCount) * 100, 2) : 0.00;

        $sqlTicket = "SELECT AVG(total) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status != 'Draft' AND status != 'Cancelled' $invDateFilter";
        $stmt = $this->pdo->prepare($sqlTicket);
        $stmt->execute($this->filterParams($params, $sqlTicket));
        $ticketAverage = (float)($stmt->fetchColumn() ?? 0.00);

        // LTV Médio = Total Recebido / Total Clientes Ativos
        $ltvAverage = $clientsActive > 0 ? ($totalReceived / $clientsActive) : 0.00;

        return [
            'revenue_today' => $revenueToday,
            'revenue_month' => $revenueMonth,
            'revenue_year' => $revenueYear,
            'total_billed' => $totalBilled,
            'total_received' => $totalReceived,
            'total_pending' => $totalPending,
            'total_reversed' => $totalReversed,
            'clients_active' => $clientsActive,
            'clients_new' => $clientsNew,
            'invoices_count' => $invoicesCount,
            'quotes_count' => $quotesCount,
            'conversion_rate' => $conversionRate,
            'ticket_average' => $ticketAverage,
            'ltv_average' => $ltvAverage
        ];
    }

    /**
     * Cash flow report grouped by day/week/month/year
     */
    public function getCashFlow($companyId, $groupType = 'month', $filters = []) {
        $params = [':cid' => $companyId];
        $dateFilter = "";

        if (!empty($filters['start_date'])) {
            $dateFilter .= " AND payment_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $dateFilter .= " AND payment_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['currency'])) {
            $dateFilter .= " AND invoice_id IN (SELECT id FROM invoices WHERE company_id = :cid AND currency = :currency)";
            $params[':currency'] = $filters['currency'];
        }
        if (!empty($filters['client_id'])) {
            $dateFilter .= " AND invoice_id IN (SELECT id FROM invoices WHERE client_id = :client_id AND company_id = :cid)";
            $params[':client_id'] = (int)$filters['client_id'];
        }

        // Group SQL selection
        $groupSelect = "DATE_FORMAT(payment_date, '%Y-%m')";
        if ($groupType === 'day') {
            $groupSelect = "DATE_FORMAT(payment_date, '%Y-%m-%d')";
        } elseif ($groupType === 'week') {
            $groupSelect = "CONCAT(YEAR(payment_date), '-W', WEEK(payment_date))";
        } elseif ($groupType === 'year') {
            $groupSelect = "DATE_FORMAT(payment_date, '%Y')";
        }

        $sql = "SELECT 
                    $groupSelect AS period,
                    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) AS cash_in,
                    SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) AS cash_out,
                    SUM(amount) AS net_cash
                FROM payments
                WHERE company_id = :cid AND deleted_at IS NULL $dateFilter
                GROUP BY period
                ORDER BY period ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->filterParams($params, $sql));
        return $stmt->fetchAll();
    }

    /**
     * Accounts Receivable listing
     */
    public function getReceivables($companyId, $filters = [], $limit = 50, $offset = 0) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['client_id'])) {
            $sqlFilters .= " AND i.client_id = :client_id";
            $params[':client_id'] = (int)$filters['client_id'];
        }
        if (!empty($filters['currency'])) {
            $sqlFilters .= " AND i.currency = :currency";
            $params[':currency'] = $filters['currency'];
        }

        // Days overdue filter indicator
        $overdueType = $filters['overdue_type'] ?? '';
        if ($overdueType === 'overdue') {
            $sqlFilters .= " AND i.due_date < CURRENT_DATE()";
        } elseif ($overdueType === 'today') {
            $sqlFilters .= " AND i.due_date = CURRENT_DATE()";
        } elseif ($overdueType === '7_days') {
            $sqlFilters .= " AND i.due_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)";
        } elseif ($overdueType === '30_days') {
            $sqlFilters .= " AND i.due_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)";
        }

        $sql = "SELECT 
                    i.id,
                    i.invoice_number,
                    i.due_date,
                    i.total,
                    i.paid_amount,
                    i.balance_due,
                    i.currency,
                    c.name AS client_name,
                    c.company AS client_company,
                    CASE WHEN i.due_date < CURRENT_DATE() THEN DATEDIFF(CURRENT_DATE(), i.due_date) ELSE 0 END AS days_overdue
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                WHERE i.company_id = :cid AND i.deleted_at IS NULL AND i.balance_due > 0 AND i.status != 'Draft' AND i.status != 'Cancelled' $sqlFilters
                ORDER BY days_overdue DESC, i.due_date ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count for receivables list pagination
     */
    public function getReceivablesCount($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['client_id'])) {
            $sqlFilters .= " AND i.client_id = :client_id";
            $params[':client_id'] = (int)$filters['client_id'];
        }
        if (!empty($filters['currency'])) {
            $sqlFilters .= " AND i.currency = :currency";
            $params[':currency'] = $filters['currency'];
        }

        $overdueType = $filters['overdue_type'] ?? '';
        if ($overdueType === 'overdue') {
            $sqlFilters .= " AND i.due_date < CURRENT_DATE()";
        } elseif ($overdueType === 'today') {
            $sqlFilters .= " AND i.due_date = CURRENT_DATE()";
        } elseif ($overdueType === '7_days') {
            $sqlFilters .= " AND i.due_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)";
        } elseif ($overdueType === '30_days') {
            $sqlFilters .= " AND i.due_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)";
        }

        $sql = "SELECT COUNT(*) FROM invoices i WHERE i.company_id = :cid AND i.deleted_at IS NULL AND i.balance_due > 0 AND i.status != 'Draft' AND i.status != 'Cancelled' $sqlFilters";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Receivables summary KPIs
     */
    public function getReceivablesSummary($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['client_id'])) {
            $sqlFilters .= " AND client_id = :client_id";
            $params[':client_id'] = (int)$filters['client_id'];
        }
        if (!empty($filters['currency'])) {
            $sqlFilters .= " AND currency = :currency";
            $params[':currency'] = $filters['currency'];
        }

        $sql = "SELECT 
                    SUM(CASE WHEN due_date < CURRENT_DATE() THEN balance_due ELSE 0 END) AS overdue_total,
                    SUM(CASE WHEN due_date = CURRENT_DATE() THEN balance_due ELSE 0 END) AS due_today,
                    SUM(CASE WHEN due_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) THEN balance_due ELSE 0 END) AS due_7_days,
                    SUM(CASE WHEN due_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) THEN balance_due ELSE 0 END) AS due_30_days,
                    SUM(balance_due) AS total_receivable
                FROM invoices
                WHERE company_id = :cid AND deleted_at IS NULL AND balance_due > 0 AND status != 'Draft' AND status != 'Cancelled' $sqlFilters";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * IVA / Taxes report grouped by rate, period, client
     */
    public function getTaxReport($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['start_date'])) {
            $sqlFilters .= " AND i.issue_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sqlFilters .= " AND i.issue_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['currency'])) {
            $sqlFilters .= " AND i.currency = :currency";
            $params[':currency'] = $filters['currency'];
        }

        // 1. Grouped by tax rate (using actual column tax_rate as decimal, no tax_rate_id FK)
        $sqlByRate = "SELECT 
                        CONCAT(ii.tax_rate, '%') AS tax_name,
                        ii.tax_rate AS tax_rate,
                        SUM(ii.quantity * ii.unit_price) AS subtotal,
                        SUM(ii.total - (ii.quantity * ii.unit_price)) AS tax_amount,
                        SUM(ii.total) AS total
                     FROM invoice_items ii
                     JOIN invoices i ON ii.invoice_id = i.id
                     WHERE i.company_id = :cid AND i.deleted_at IS NULL AND i.status != 'Draft' AND i.status != 'Cancelled' $sqlFilters
                     GROUP BY ii.tax_rate
                     ORDER BY ii.tax_rate DESC";
        $stmt = $this->pdo->prepare($sqlByRate);
        $stmt->execute($params);
        $byRate = $stmt->fetchAll();

        // 2. Grouped by client (use tax_amount column which exists)
        $sqlByClient = "SELECT 
                            c.name AS client_name,
                            c.company AS client_company,
                            SUM(i.subtotal) AS subtotal,
                            SUM(i.tax_amount) AS tax_amount,
                            SUM(i.total) AS total
                        FROM invoices i
                        JOIN clients c ON i.client_id = c.id
                        WHERE i.company_id = :cid AND i.deleted_at IS NULL AND i.status != 'Draft' AND i.status != 'Cancelled' $sqlFilters
                        GROUP BY c.id
                        ORDER BY tax_amount DESC
                        LIMIT 20";
        $stmt = $this->pdo->prepare($sqlByClient);
        $stmt->execute($params);
        $byClient = $stmt->fetchAll();

        // 3. Grouped by monthly period (use tax_amount column which exists)
        $sqlByPeriod = "SELECT 
                            DATE_FORMAT(i.issue_date, '%Y-%m') AS period,
                            SUM(i.subtotal) AS subtotal,
                            SUM(i.tax_amount) AS tax_amount,
                            SUM(i.total) AS total
                        FROM invoices i
                        WHERE i.company_id = :cid AND i.deleted_at IS NULL AND i.status != 'Draft' AND i.status != 'Cancelled' $sqlFilters
                        GROUP BY period
                        ORDER BY period ASC";
        $stmt = $this->pdo->prepare($sqlByPeriod);
        $stmt->execute($params);
        $byPeriod = $stmt->fetchAll();

        return [
            'by_rate' => $byRate,
            'by_client' => $byClient,
            'by_period' => $byPeriod
        ];
    }

    /**
     * Customers report & analytics
     */
    public function getCustomersReport($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['start_date'])) {
            $sqlFilters .= " AND i.issue_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sqlFilters .= " AND i.issue_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $orderBy = "ltv DESC";
        $sort = $filters['sort'] ?? '';
        if ($sort === 'billed') $orderBy = "total_billed DESC";
        elseif ($sort === 'invoices') $orderBy = "invoices_count DESC";

        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.company,
                    c.active,
                    COUNT(DISTINCT i.id) AS invoices_count,
                    COUNT(DISTINCT q.id) AS quotes_count,
                    SUM(i.total) AS total_billed,
                    SUM(i.paid_amount) AS ltv
                FROM clients c
                LEFT JOIN invoices i ON c.id = i.client_id AND i.deleted_at IS NULL AND i.status != 'Cancelled' AND i.status != 'Draft' $sqlFilters
                LEFT JOIN quotes q ON c.id = q.client_id AND q.deleted_at IS NULL AND q.status != 'Draft'
                WHERE c.company_id = :cid
                GROUP BY c.id
                ORDER BY $orderBy";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Quotes conversions & status report
     */
    public function getQuotesReport($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['start_date'])) {
            $sqlFilters .= " AND q.issue_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sqlFilters .= " AND q.issue_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['client_id'])) {
            $sqlFilters .= " AND q.client_id = :client_id";
            $params[':client_id'] = (int)$filters['client_id'];
        }

        // 1. Group by status
        $sqlByStatus = "SELECT 
                            q.status,
                            COUNT(*) AS quantity,
                            SUM(q.total) AS total
                        FROM quotes q
                        WHERE q.company_id = :cid AND q.deleted_at IS NULL $sqlFilters
                        GROUP BY q.status";
        $stmt = $this->pdo->prepare($sqlByStatus);
        $stmt->execute($params);
        $byStatus = $stmt->fetchAll();

        // 2. Acceptance rate & conversion metrics
        $sqlMetrics = "SELECT 
                            COUNT(*) AS total_count,
                            SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) AS accepted_count,
                            SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count,
                            SUM(CASE WHEN status = 'Expired' THEN 1 ELSE 0 END) AS expired_count
                        FROM quotes q
                        WHERE q.company_id = :cid AND q.deleted_at IS NULL $sqlFilters";
        $stmt = $this->pdo->prepare($sqlMetrics);
        $stmt->execute($params);
        $metrics = $stmt->fetch();

        // Total converted (quotes with an invoice created from them)
        $sqlConverted = "SELECT COUNT(DISTINCT i.quote_id) 
                         FROM invoices i
                         JOIN quotes q ON i.quote_id = q.id
                         WHERE i.company_id = :cid AND i.quote_id IS NOT NULL AND i.deleted_at IS NULL $sqlFilters";
        $stmt = $this->pdo->prepare($sqlConverted);
        $stmt->execute($params);
        $convertedCount = (int)$stmt->fetchColumn();

        // Acceptance rate
        $totalQuotes = (int)($metrics['total_count'] ?? 0);
        $acceptedCount = (int)($metrics['accepted_count'] ?? 0);
        $acceptanceRate = $totalQuotes > 0 ? round(($acceptedCount / $totalQuotes) * 100, 2) : 0.00;
        $conversionRate = $totalQuotes > 0 ? round(($convertedCount / $totalQuotes) * 100, 2) : 0.00;

        // Average time to convert (Accepted quotes to Invoiced in days)
        // Calculated by DATEDIFF between quote accepted or issue date and invoice issue date
        $sqlAvgTime = "SELECT AVG(DATEDIFF(i.issue_date, q.issue_date)) 
                       FROM invoices i 
                       JOIN quotes q ON i.quote_id = q.id 
                       WHERE i.company_id = :cid AND i.deleted_at IS NULL AND q.deleted_at IS NULL $sqlFilters";
        $stmt = $this->pdo->prepare($sqlAvgTime);
        $stmt->execute($params);
        $avgTimeDays = round((float)($stmt->fetchColumn() ?? 0.00), 1);

        return [
            'by_status' => $byStatus,
            'total_count' => $totalQuotes,
            'accepted_count' => $acceptedCount,
            'converted_count' => $convertedCount,
            'acceptance_rate' => $acceptanceRate,
            'conversion_rate' => $conversionRate,
            'avg_time_to_invoice' => $avgTimeDays
        ];
    }

    /**
     * Payments stats by method
     */
    public function getPaymentsReport($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['start_date'])) {
            $sqlFilters .= " AND p.payment_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sqlFilters .= " AND p.payment_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $methodCol = $this->paymentMethodColumn();
        $sql = "SELECT 
                    $methodCol AS payment_method,
                    COUNT(*) AS quantity,
                    SUM(CASE WHEN p.amount > 0 THEN p.amount ELSE 0 END) AS gross_amount,
                    SUM(CASE WHEN p.amount < 0 THEN p.amount ELSE 0 END) AS reversals,
                    SUM(p.amount) AS net_amount
                FROM payments p
                WHERE p.company_id = :cid AND p.deleted_at IS NULL $sqlFilters
                GROUP BY $methodCol";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Hours by project report
     */
    public function getHoursByProject($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['start_date'])) {
            $sqlFilters .= " AND t.work_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sqlFilters .= " AND t.work_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $sql = "SELECT 
                    p.project_code,
                    p.name AS project_name,
                    SUM(t.hours) AS total_hours,
                    SUM(CASE WHEN t.billable = 1 THEN t.hours ELSE 0 END) AS billable_hours,
                    SUM(CASE WHEN t.status = 'Approved' THEN t.hours ELSE 0 END) AS approved_hours,
                    SUM(CASE WHEN t.status = 'Rejected' THEN t.hours ELSE 0 END) AS rejected_hours
                FROM timesheets t
                JOIN projects p ON t.project_id = p.id
                WHERE t.company_id = :cid AND t.deleted_at IS NULL $sqlFilters
                GROUP BY p.id
                ORDER BY total_hours DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Hours by collaborator (worker) report
     */
    public function getHoursByWorker($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['start_date'])) {
            $sqlFilters .= " AND t.work_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sqlFilters .= " AND t.work_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $sql = "SELECT 
                    u.name AS worker_name,
                    SUM(t.hours) AS total_hours,
                    SUM(CASE WHEN t.billable = 1 THEN t.hours ELSE 0 END) AS billable_hours,
                    SUM(CASE WHEN t.status = 'Approved' THEN t.hours ELSE 0 END) AS approved_hours,
                    SUM(CASE WHEN t.status = 'Rejected' THEN t.hours ELSE 0 END) AS rejected_hours
                FROM timesheets t
                JOIN users u ON t.user_id = u.id
                WHERE t.company_id = :cid AND t.deleted_at IS NULL $sqlFilters
                GROUP BY u.id
                ORDER BY total_hours DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Estimated vs Realized hours report
     */
    public function getEstimatedVsRealized($companyId) {
        $sql = "SELECT 
                    p.project_code,
                    p.name AS project_name,
                    p.estimated_hours AS estimated,
                    IFNULL(SUM(t.hours), 0.00) AS realized,
                    (p.estimated_hours - IFNULL(SUM(t.hours), 0.00)) AS variance
                FROM projects p
                LEFT JOIN timesheets t ON p.id = t.project_id AND t.deleted_at IS NULL
                WHERE p.company_id = :cid AND p.deleted_at IS NULL
                GROUP BY p.id
                ORDER BY p.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cid' => $companyId]);
        return $stmt->fetchAll();
    }

    /**
     * Project profitability report (Estimated budget vs Cost realized)
     */
    public function getProjectProfitability($companyId) {
        $sql = "SELECT 
                    p.project_code,
                    p.name AS project_name,
                    p.budget,
                    p.currency,
                    IFNULL(SUM(t.hours * t.hourly_rate), 0.00) AS total_cost,
                    (p.budget - IFNULL(SUM(t.hours * t.hourly_rate), 0.00)) AS net_profit
                FROM projects p
                LEFT JOIN timesheets t ON p.id = t.project_id AND t.deleted_at IS NULL
                WHERE p.company_id = :cid AND p.deleted_at IS NULL
                GROUP BY p.id
                ORDER BY net_profit DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cid' => $companyId]);
        return $stmt->fetchAll();
    }

    /**
     * Financial reconciliation report (Phase 10.1)
     * Reconciles approved hours, invoiced hours, pending hours, revenue and margin.
     * Uses approved_billable_rate and approved_hourly_cost snapshots.
     */
    public function getReconciliation($companyId, $filters = []) {
        $params = [':cid' => $companyId];
        $sqlFilters = "";

        if (!empty($filters['start_date'])) {
            $sqlFilters .= " AND t.work_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sqlFilters .= " AND t.work_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['project_id'])) {
            $sqlFilters .= " AND t.project_id = :project_id";
            $params[':project_id'] = $filters['project_id'];
        }

        $sql = "SELECT 
                    p.project_code,
                    p.name AS project_name,
                    p.currency,
                    -- Approved Hours
                    SUM(CASE WHEN t.status = 'Approved' OR t.invoice_id IS NOT NULL THEN t.hours ELSE 0 END) AS approved_hours,
                    -- Invoiced Hours
                    SUM(CASE WHEN t.invoice_id IS NOT NULL THEN t.hours ELSE 0 END) AS invoiced_hours,
                    -- Pending Hours
                    SUM(CASE WHEN t.status = 'Approved' AND t.invoice_id IS NULL THEN t.hours ELSE 0 END) AS pending_hours,
                    -- Revenue (Invoiced)
                    SUM(CASE WHEN t.invoice_id IS NOT NULL THEN t.hours * t.approved_billable_rate ELSE 0 END) AS invoiced_revenue,
                    -- Potential Revenue (Pending)
                    SUM(CASE WHEN t.status = 'Approved' AND t.invoice_id IS NULL THEN t.hours * t.approved_billable_rate ELSE 0 END) AS pending_revenue,
                    -- Total Revenue (Invoiced + Pending)
                    SUM(CASE WHEN t.status = 'Approved' OR t.invoice_id IS NOT NULL THEN t.hours * t.approved_billable_rate ELSE 0 END) AS total_revenue,
                    -- Total Cost (using approved_hourly_cost)
                    SUM(CASE WHEN t.status = 'Approved' OR t.invoice_id IS NOT NULL THEN t.hours * t.approved_hourly_cost ELSE 0 END) AS total_cost,
                    -- Margin (Total Revenue - Total Cost)
                    SUM(CASE WHEN t.status = 'Approved' OR t.invoice_id IS NOT NULL THEN (t.hours * t.approved_billable_rate) - (t.hours * t.approved_hourly_cost) ELSE 0 END) AS margin
                FROM timesheets t
                JOIN projects p ON t.project_id = p.id
                WHERE t.company_id = :cid AND t.deleted_at IS NULL $sqlFilters
                GROUP BY p.id
                ORDER BY total_revenue DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Detect payment method column name (legacy `method` vs `payment_method`).
     */
    private function paymentMethodColumn(): string {
        static $column = null;
        if ($column === null) {
            $cols = $this->pdo->query('SHOW COLUMNS FROM payments')->fetchAll(PDO::FETCH_COLUMN);
            $column = in_array('payment_method', $cols, true) ? 'p.payment_method' : 'p.method';
        }
        return $column;
    }
}


