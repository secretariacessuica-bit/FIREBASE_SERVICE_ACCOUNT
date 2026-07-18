<?php
// LIMA Solutions ERP - Timesheets Model

class Timesheet {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all active timesheets with filters
     */
    public function getAll($companyId, $filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT t.*, p.name AS project_name, p.project_code, tk.title AS task_title, tk.task_code, u.name AS user_name 
                FROM timesheets t
                JOIN projects p ON t.project_id = p.id
                LEFT JOIN project_tasks tk ON t.task_id = tk.id
                JOIN users u ON t.user_id = u.id
                WHERE t.company_id = :company_id AND t.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = :user_id";
            $params[':user_id'] = (int)$filters['user_id'];
        }
        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = :project_id";
            $params[':project_id'] = (int)$filters['project_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND t.work_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND t.work_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $sql .= " ORDER BY t.work_date DESC, t.created_at DESC LIMIT :limit OFFSET :offset";

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
     * Get total count of active timesheets
     */
    public function getTotalCount($companyId, $filters = []) {
        $sql = "SELECT COUNT(*) 
                FROM timesheets t
                JOIN projects p ON t.project_id = p.id
                WHERE t.company_id = :company_id AND t.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = :user_id";
            $params[':user_id'] = (int)$filters['user_id'];
        }
        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = :project_id";
            $params[':project_id'] = (int)$filters['project_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND t.work_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND t.work_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get single timesheet entry
     */
    public function getById($id, $companyId) {
        $sql = "SELECT t.*, p.name AS project_name, p.project_code, tk.title AS task_title, tk.task_code, u.name AS user_name 
                FROM timesheets t
                JOIN projects p ON t.project_id = p.id
                LEFT JOIN project_tasks tk ON t.task_id = tk.id
                JOIN users u ON t.user_id = u.id
                WHERE t.id = :id AND t.company_id = :company_id AND t.deleted_at IS NULL LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create timesheet entry
     */
    public function create($data, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $projectId = (int)$data['project_id'];
            $taskId = !empty($data['task_id']) ? (int)$data['task_id'] : null;

            // 1. Verify Project belongs to Company
            $stmtPrj = $this->pdo->prepare("SELECT id FROM projects WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtPrj->execute(['id' => $projectId, 'company_id' => $companyId]);
            if (!$stmtPrj->fetchColumn()) {
                throw new Exception("Le projet sÃ©lectionnÃ© n'existe pas dans votre entreprise.", 409);
            }

            // 2. Verify Task belongs to Project (if specified)
            if ($taskId) {
                $stmtTask = $this->pdo->prepare("SELECT id FROM project_tasks WHERE id = :id AND project_id = :project_id AND company_id = :company_id LIMIT 1");
                $stmtTask->execute(['id' => $taskId, 'project_id' => $projectId, 'company_id' => $companyId]);
                if (!$stmtTask->fetchColumn()) {
                    throw new Exception("La tÃ¢che sÃ©lectionnÃ©e n'appartient pas au projet.", 409);
                }
            }

            // 3. Automatically calculate hours if start/end times exist
            $hours = (float)($data['hours'] ?? 0.00);
            if (!empty($data['start_time']) && !empty($data['end_time'])) {
                $start = strtotime($data['start_time']);
                $end = strtotime($data['end_time']);
                if ($end > $start) {
                    $hours = ($end - $start) / 3600;
                }
            }

            if ($hours <= 0) {
                throw new Exception("Le nombre d'heures doit Ãªtre supÃ©rieur Ã  zÃ©ro.", 409);
            }

            // 4. Insert row (Default status: Draft)
            $sql = "INSERT INTO timesheets (
                company_id, project_id, task_id, user_id, work_date, 
                hours, billable, hourly_rate, status
            ) VALUES (
                :company_id, :project_id, :task_id, :user_id, :work_date, 
                :hours, :billable, :hourly_rate, 'Draft'
            )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'company_id' => $companyId,
                'project_id' => $projectId,
                'task_id' => $taskId,
                'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : $userId,
                'work_date' => $data['work_date'],
                'hours' => $hours,
                'billable' => isset($data['billable']) ? (int)$data['billable'] : 1,
                'hourly_rate' => $data['hourly_rate'] ?? 0.00
            ]);

            $timesheetId = $this->pdo->lastInsertId();

            // 5. Timeline Event
            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'projects', 'projects', $projectId, 'time_logged', $userId, "Horas registradas no timesheet: " . $hours . " horas de serviÃ§o no projeto.", $this->pdo);

            $this->pdo->commit();
            return $timesheetId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update timesheet entry
     */
    public function update($id, $data, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Apontamento de horas introuvable.", 404);
            }

            // Enforce Immutability rules
            if ($existing['status'] === 'Approved') {
                throw new Exception("Impossible de modifier un timesheet dÃ©jÃ  approuvÃ©.", 409);
            }
            if (!empty($existing['invoice_id']) || !empty($existing['quote_id']) || !empty($existing['locked'])) {
                throw new Exception("Impossible de modifier un timesheet dÃ©jÃ  facturÃ© ou verrouillÃ©.", 409);
            }

            $projectId = (int)$data['project_id'];
            $taskId = !empty($data['task_id']) ? (int)$data['task_id'] : null;

            // Verify Project belongs to Company
            $stmtPrj = $this->pdo->prepare("SELECT id FROM projects WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtPrj->execute(['id' => $projectId, 'company_id' => $companyId]);
            if (!$stmtPrj->fetchColumn()) {
                throw new Exception("Le projet sÃ©lectionnÃ© n'existe pas dans votre entreprise.", 409);
            }

            // Verify Task belongs to Project (if specified)
            if ($taskId) {
                $stmtTask = $this->pdo->prepare("SELECT id FROM project_tasks WHERE id = :id AND project_id = :project_id AND company_id = :company_id LIMIT 1");
                $stmtTask->execute(['id' => $taskId, 'project_id' => $projectId, 'company_id' => $companyId]);
                if (!$stmtTask->fetchColumn()) {
                    throw new Exception("La tÃ¢che sÃ©lectionnÃ©e n'appartient pas au projet.", 409);
                }
            }

            // Automatically calculate hours if start/end times exist
            $hours = (float)($data['hours'] ?? 0.00);
            if (!empty($data['start_time']) && !empty($data['end_time'])) {
                $start = strtotime($data['start_time']);
                $end = strtotime($data['end_time']);
                if ($end > $start) {
                    $hours = ($end - $start) / 3600;
                }
            }

            if ($hours <= 0) {
                throw new Exception("Le nombre d'heures doit Ãªtre supÃ©rieur Ã  zÃ©ro.", 409);
            }

            $sql = "UPDATE timesheets SET 
                project_id = :project_id,
                task_id = :task_id,
                user_id = :user_id,
                work_date = :work_date,
                hours = :hours,
                billable = :billable,
                hourly_rate = :hourly_rate
                WHERE id = :id AND company_id = :company_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'project_id' => $projectId,
                'task_id' => $taskId,
                'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : $userId,
                'work_date' => $data['work_date'],
                'hours' => $hours,
                'billable' => isset($data['billable']) ? (int)$data['billable'] : 1,
                'hourly_rate' => $data['hourly_rate'] ?? 0.00,
                'id' => $id,
                'company_id' => $companyId
            ]);

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
     * Soft delete timesheet entry
     */
    public function softDelete($id, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Apontamento de horas introuvable.", 404);
            }

            // Enforce Immutability rules
            if ($existing['status'] === 'Approved') {
                throw new Exception("Impossible de supprimer un timesheet dÃ©jÃ  approuvÃ©.", 409);
            }
            if (!empty($existing['invoice_id']) || !empty($existing['quote_id']) || !empty($existing['locked'])) {
                throw new Exception("Impossible de supprimer un timesheet dÃ©jÃ  facturÃ© ou verrouillÃ©.", 409);
            }

            $stmt = $this->pdo->prepare("UPDATE timesheets SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id");
            $stmt->execute(['id' => $id, 'company_id' => $companyId]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ==========================================
    // WORKFLOW TRANSITION METHODS
    // ==========================================

    /**
     * Submit timesheet for approval
     */
    public function submit($id, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Apontamento de horas introuvable.", 404);
            }

            if ($existing['status'] !== 'Draft' && $existing['status'] !== 'Rejected') {
                throw new Exception("Statut non Ã©ligible pour soumission.", 409);
            }

            $stmt = $this->pdo->prepare("UPDATE timesheets SET status = 'Submitted', submitted_at = NOW() WHERE id = :id AND company_id = :company_id");
            $stmt->execute(['id' => $id, 'company_id' => $companyId]);

            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'projects', 'projects', $existing['project_id'], 'time_submitted', $userId, "Feuille de temps soumise pour validation: " . $existing['hours'] . " heures par " . $existing['user_name'], $this->pdo);

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
     * Approve timesheet (Manager only)
     */
    public function approve($id, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Apontamento de horas introuvable.", 404);
            }

            if ($existing['status'] !== 'Submitted') {
                throw new Exception("Statut non Ã©ligible pour approbation (doit Ãªtre Submitted).", 409);
            }

            $stmt = $this->pdo->prepare("UPDATE timesheets SET 
                status = 'Approved', 
                approved_at = NOW(), 
                approved_by = :approved_by, 
                approved_hourly_cost = :approved_hourly_cost,
                approved_billable_rate = :approved_billable_rate,
                locked = 1,
                rejected_at = NULL, 
                rejection_reason = NULL 
                WHERE id = :id AND company_id = :company_id");
            $stmt->execute([
                'approved_by' => $userId,
                'approved_hourly_cost' => $existing['hourly_rate'],
                'approved_billable_rate' => $existing['hourly_rate'],
                'id' => $id,
                'company_id' => $companyId
            ]);

            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'projects', 'projects', $existing['project_id'], 'time_approved', $userId, "Feuille de temps de " . $existing['hours'] . " heures approuvÃ©e par le gestionnaire.", $this->pdo);

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
     * Reject timesheet (Manager only)
     */
    public function reject($id, $reason, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Apontamento de horas introuvable.", 404);
            }

            if ($existing['status'] !== 'Submitted') {
                throw new Exception("Statut non Ã©ligible pour rejet (doit Ãªtre Submitted).", 409);
            }

            if (empty($reason)) {
                throw new Exception("Le motif de rejet est obligatoire.", 409);
            }

            $stmt = $this->pdo->prepare("UPDATE timesheets SET status = 'Rejected', rejected_at = NOW(), rejection_reason = :reason, approved_at = NULL, approved_by = NULL WHERE id = :id AND company_id = :company_id");
            $stmt->execute([
                'reason' => $reason,
                'id' => $id,
                'company_id' => $companyId
            ]);

            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'projects', 'projects', $existing['project_id'], 'time_rejected', $userId, "Feuille de temps rejetÃ©e par le gestionnaire (Motif: " . $reason . ").", $this->pdo);

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
     * Convert approved timesheets to an Invoice.
     *
     * Implements SELECT ... FOR UPDATE inside the transaction to prevent
     * concurrent double-billing of the same timesheets (Phase 10.1).
     *
     * @param  array  $timesheetIds  List of timesheet IDs to convert (integers)
     * @param  int    $companyId     Active company scope
     * @param  int    $userId        User performing the operation
     * @param  array  $options       ['group_by' => 'detailed'|'project'|'collaborator'|'date'|'consolidated']
     * @return int    The created invoice ID
     * @throws Exception  Code 409 â€“ already invoiced / conflict
     *                    Code 422 â€“ empty selection / ineligible input
     */
    public function convertToInvoice($timesheetIds, $companyId, $userId, $options = []) {
        // â”€â”€ Input validation (422 = bad input before touching DB) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        if (empty($timesheetIds) || !is_array($timesheetIds)) {
            throw new Exception("Aucun timesheet selectionne.", 422);
        }
        $timesheetIds = array_values(array_unique(array_map('intval', $timesheetIds)));
        if (empty($timesheetIds)) {
            throw new Exception("Identifiants de timesheets invalides.", 422);
        }

        try {
            $this->pdo->beginTransaction();

            // â”€â”€ 1. SELECT FOR UPDATE â€“ locks rows for the duration of the TX â”€â”€
            // This prevents concurrent billing of the same timesheets.
            $placeholders = implode(',', array_fill(0, count($timesheetIds), '?'));
            $sqlLock = "SELECT t.id, t.status, t.invoice_id, t.locked,
                               t.hours, t.billable, t.work_date,
                               t.approved_hourly_cost, t.approved_billable_rate,
                               t.user_id, t.project_id, t.task_id,
                               p.name AS project_name, p.project_code,
                               p.client_id, p.currency AS project_currency,
                               tk.title AS task_title, u.name AS user_name
                        FROM timesheets t
                        JOIN projects p ON t.project_id = p.id
                        LEFT JOIN project_tasks tk ON t.task_id = tk.id
                        JOIN users u ON t.user_id = u.id
                        WHERE t.id IN ($placeholders)
                          AND t.company_id = ?
                          AND t.deleted_at IS NULL
                        FOR UPDATE";

            $stmt = $this->pdo->prepare($sqlLock);
            $stmt->execute(array_merge($timesheetIds, [$companyId]));
            $timesheets = $stmt->fetchAll();

            // â”€â”€ 2. Verify all requested IDs were found â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            if (count($timesheets) !== count($timesheetIds)) {
                throw new Exception("Certains timesheets sont introuvables ou ont ete supprimes.", 422);
            }

            // â”€â”€ 3. Validate each timesheet's eligibility â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $clientId         = null;
            $currency         = null;
            $uniqueProjectIds = [];

            foreach ($timesheets as $t) {
                // Already invoiced â†’ hard conflict
                if (!empty($t['invoice_id'])) {
                    throw new Exception(
                        "Un ou plusieurs timesheets sont deja lies a une facture existante.",
                        409
                    );
                }
                // Already locked but not yet invoiced = data integrity issue â†’ conflict
                if ($t['locked'] == 1 && empty($t['invoice_id'])) {
                    // Locked at approval time is allowed (locked=1 is set on approve)
                    // Only reject if truly already billed
                }
                // Must be Approved
                if ($t['status'] !== 'Approved') {
                    throw new Exception(
                        "Seuls les timesheets avec le statut Approved peuvent etre factures.",
                        409
                    );
                }

                // Client homogeneity
                if ($clientId === null) {
                    $clientId = $t['client_id'];
                } elseif ($clientId != $t['client_id']) {
                    throw new Exception(
                        "Tous les timesheets selectionnes doivent appartenir au meme client.",
                        409
                    );
                }

                // Currency homogeneity
                if ($currency === null) {
                    $currency = $t['project_currency'];
                } elseif ($currency !== $t['project_currency']) {
                    throw new Exception(
                        "Tous les timesheets selectionnes doivent avoir la meme devise ($currency).",
                        409
                    );
                }

                $uniqueProjectIds[] = $t['project_id'];
            }

            $uniqueProjectIds = array_unique($uniqueProjectIds);

            // â”€â”€ 4. Generate unique billing batch ID for this operation â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $billingBatchId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // â”€â”€ 5. Group timesheet items per chosen mode â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $groupBy      = $options['group_by'] ?? 'detailed';
            $groupedItems = [];

            foreach ($timesheets as $t) {
                $rate  = (float)$t['approved_billable_rate'];
                $hours = (float)$t['hours'];

                if (isset($t['billable']) && $t['billable'] == 0) {
                    continue; // Skip non-billable entries
                }

                switch ($groupBy) {
                    case 'project':
                        $key  = $t['project_id'] . '_' . sprintf("%.2f", $rate);
                        $desc = "Prestations pour le projet [" . $t['project_code'] . "] - " . $t['project_name'];
                        break;
                    case 'collaborator':
                        $key  = $t['user_id'] . '_' . sprintf("%.2f", $rate);
                        $desc = "Prestations par " . $t['user_name'];
                        break;
                    case 'date':
                        $key  = $t['work_date'] . '_' . sprintf("%.2f", $rate);
                        $desc = "Prestations du " . date('d.m.Y', strtotime($t['work_date']));
                        break;
                    case 'consolidated':
                        $key  = 'consolidated_' . sprintf("%.2f", $rate);
                        $desc = "Consolidation des prestations de service";
                        break;
                    case 'detailed':
                    default:
                        $key      = $t['id'];
                        $taskDesc = !empty($t['task_title']) ? " - Tache: " . $t['task_title'] : "";
                        $desc     = "[" . $t['project_code'] . "]" . $taskDesc
                                  . " - Date: " . date('d.m.Y', strtotime($t['work_date']))
                                  . " - Collaborateur: " . $t['user_name']
                                  . " (" . $hours . " h)";
                        break;
                }

                if (!isset($groupedItems[$key])) {
                    $groupedItems[$key] = [
                        'description' => $desc,
                        'quantity'    => 0.00,
                        'unit_price'  => $rate
                    ];
                }
                $groupedItems[$key]['quantity'] += $hours;
            }

            // No billable hours found is a user-input problem â†’ 422
            if (empty($groupedItems)) {
                throw new Exception("Aucune heure facturable trouvee dans la selection.", 422);
            }

            // â”€â”€ 6. Calculate invoice totals â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $subtotal = 0.00;
            foreach ($groupedItems as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $total      = $subtotal;
            $balanceDue = $total;

            // â”€â”€ 7. Generate Invoice sequence number â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            require_once __DIR__ . '/../../../admin/sequences_helper.php';
            $invoiceNumber = generateSequence($companyId, 'INV', $this->pdo);

            $issueDate = date('Y-m-d');
            $dueDate   = date('Y-m-d', strtotime('+30 days'));

            // â”€â”€ 8. Insert Invoice row (with billing_batch_id) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $sqlInvoice = "INSERT INTO invoices (
                company_id, client_id, invoice_number, status, issue_date, due_date,
                currency, subtotal, discount_amount, discount_percent, tax_total, total,
                paid_amount, balance_due, created_by, billing_batch_id
            ) VALUES (
                :company_id, :client_id, :invoice_number, 'Draft', :issue_date, :due_date,
                :currency, :subtotal, 0.00, 0.00, 0.00, :total,
                0.00, :balance_due, :created_by, :billing_batch_id
            )";

            $stmtInvoice = $this->pdo->prepare($sqlInvoice);
            $stmtInvoice->execute([
                'company_id'       => $companyId,
                'client_id'        => $clientId,
                'invoice_number'   => $invoiceNumber,
                'issue_date'       => $issueDate,
                'due_date'         => $dueDate,
                'currency'         => $currency,
                'subtotal'         => $subtotal,
                'total'            => $total,
                'balance_due'      => $balanceDue,
                'created_by'       => $userId,
                'billing_batch_id' => $billingBatchId,
            ]);

            $invoiceId = $this->pdo->lastInsertId();

            // â”€â”€ 9. Insert Invoice Items â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $sqlItem     = "INSERT INTO invoice_items (
                company_id, invoice_id, position, description, quantity,
                unit_price, discount_percent, subtotal, tax_amount, total
            ) VALUES (
                :company_id, :invoice_id, :position, :description, :quantity,
                :unit_price, 0.00, :subtotal, 0.00, :total
            )";
            $stmtItem    = $this->pdo->prepare($sqlItem);
            $position    = 1;

            foreach ($groupedItems as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $stmtItem->execute([
                    'company_id'  => $companyId,
                    'invoice_id'  => $invoiceId,
                    'position'    => $position++,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'subtotal'    => $itemSubtotal,
                    'total'       => $itemSubtotal,
                ]);
            }

            // â”€â”€ 10. Lock timesheets (invoice_id, invoiced_at, locked, batch) â”€â”€
            $sqlUpdateTs = "UPDATE timesheets
                            SET invoice_id        = ?,
                                invoiced_at       = NOW(),
                                locked            = 1,
                                billing_batch_id  = ?
                            WHERE id = ? AND company_id = ?";
            $stmtUpdate  = $this->pdo->prepare($sqlUpdateTs);

            foreach ($timesheetIds as $tid) {
                $stmtUpdate->execute([$invoiceId, $billingBatchId, $tid, $companyId]);
            }

            // â”€â”€ 11. Timeline + Audit â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            require_once __DIR__ . '/../../../admin/audit_helper.php';

            logEntityEvent(
                $companyId, 'invoices', 'invoices', $invoiceId,
                'created_from_timesheets', $userId,
                "Facture $invoiceNumber generee a partir de " . count($timesheetIds) . " timesheet(s). Lot: $billingBatchId",
                $this->pdo
            );

            foreach ($uniqueProjectIds as $pid) {
                logEntityEvent(
                    $companyId, 'projects', 'projects', $pid,
                    'timesheets_invoiced', $userId,
                    "Heures approuvees converties en facture $invoiceNumber (lot: $billingBatchId)",
                    $this->pdo
                );
            }

            logAuditEvent(
                $companyId, $userId,
                'billing_conversion', 'timesheets', $invoiceId,
                json_encode([
                    'invoice_number'    => $invoiceNumber,
                    'invoice_id'        => $invoiceId,
                    'billing_batch_id'  => $billingBatchId,
                    'timesheet_ids'     => $timesheetIds,
                    'total'             => $total,
                    'currency'          => $currency,
                ]),
                $this->pdo
            );

            $this->pdo->commit();

            return [
                'invoice_id'       => $invoiceId,
                'invoice_number'   => $invoiceNumber,
                'billing_batch_id' => $billingBatchId,
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
