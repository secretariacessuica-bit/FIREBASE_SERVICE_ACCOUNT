<?php
// LIMA Solutions ERP - Projects Model

class Project {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all active projects for a company with filters
     */
    public function getAll($companyId, $filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT p.*, c.name AS client_name, c.company AS client_company 
                FROM projects p
                JOIN clients c ON p.client_id = c.id
                WHERE p.company_id = :company_id AND p.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search1 
                        OR p.project_code LIKE :search2 
                        OR c.name LIKE :search3 
                        OR p.status LIKE :search4)";
            $val = '%' . $filters['search'] . '%';
            $params[':search1'] = $val;
            $params[':search2'] = $val;
            $params[':search3'] = $val;
            $params[':search4'] = $val;
        }
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

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
     * Get total count of active projects
     */
    public function getTotalCount($companyId, $filters = []) {
        $sql = "SELECT COUNT(*) 
                FROM projects p
                JOIN clients c ON p.client_id = c.id
                WHERE p.company_id = :company_id AND p.deleted_at IS NULL";

        $params = [':company_id' => $companyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search1 
                        OR p.project_code LIKE :search2 
                        OR c.name LIKE :search3 
                        OR p.status LIKE :search4)";
            $val = '%' . $filters['search'] . '%';
            $params[':search1'] = $val;
            $params[':search2'] = $val;
            $params[':search3'] = $val;
            $params[':search4'] = $val;
        }
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
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
     * Get single project by ID
     */
    public function getById($id, $companyId) {
        $sql = "SELECT p.*, c.name AS client_name, c.company AS client_company 
                FROM projects p
                JOIN clients c ON p.client_id = c.id
                WHERE p.id = :id AND p.company_id = :company_id AND p.deleted_at IS NULL LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create project in a transaction
     */
    public function create($data, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $clientId = (int)$data['client_id'];

            // 1. Validate Client Company Scope
            $stmtClient = $this->pdo->prepare("SELECT id FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtClient->execute(['id' => $clientId, 'company_id' => $companyId]);
            if (!$stmtClient->fetchColumn()) {
                throw new Exception("Le client sélectionné n'appartient pas à votre entreprise.", 409);
            }

            // 2. Generate Sequential Code
            require_once __DIR__ . '/../../../admin/sequences_helper.php';
            $projectCode = generateSequence($companyId, 'PRJ', $this->pdo);

            // 3. Insert Row
            $sql = "INSERT INTO projects (
                company_id, client_id, project_code, name, description, status, 
                start_date, end_date, estimated_hours, budget, currency, created_by
            ) VALUES (
                :company_id, :client_id, :project_code, :name, :description, :status, 
                :start_date, :end_date, :estimated_hours, :budget, :currency, :created_by
            )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'company_id' => $companyId,
                'client_id' => $clientId,
                'project_code' => $projectCode,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'Planning',
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'estimated_hours' => $data['estimated_hours'] ?? 0.00,
                'budget' => $data['budget'] ?? 0.00,
                'currency' => $data['currency'] ?? 'CHF',
                'created_by' => $userId
            ]);

            $projectId = $this->pdo->lastInsertId();

            // 4. Log entity timeline event
            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'projects', 'projects', $projectId, 'created', $userId, "Projet créé: " . $projectCode . " - " . $data['name'], $this->pdo);

            $this->pdo->commit();
            return $projectId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update project
     */
    public function update($id, $data, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Projet introuvable.", 404);
            }

            $clientId = (int)$data['client_id'];

            // Validate Client Company Scope
            $stmtClient = $this->pdo->prepare("SELECT id FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
            $stmtClient->execute(['id' => $clientId, 'company_id' => $companyId]);
            if (!$stmtClient->fetchColumn()) {
                throw new Exception("Le client sélectionné n'appartient pas à votre entreprise.", 409);
            }

            $sql = "UPDATE projects SET 
                client_id = :client_id,
                name = :name,
                description = :description,
                status = :status,
                start_date = :start_date,
                end_date = :end_date,
                estimated_hours = :estimated_hours,
                budget = :budget,
                currency = :currency
                WHERE id = :id AND company_id = :company_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'client_id' => $clientId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'estimated_hours' => $data['estimated_hours'] ?? 0.00,
                'budget' => $data['budget'] ?? 0.00,
                'currency' => $data['currency'] ?? 'CHF',
                'id' => $id,
                'company_id' => $companyId
            ]);

            // Timeline status log
            if ($existing['status'] !== $data['status']) {
                require_once __DIR__ . '/../../../admin/timeline_helper.php';
                logEntityEvent($companyId, 'projects', 'projects', $id, 'status_changed', $userId, "Projet " . $existing['project_code'] . " mis à jour vers le statut: " . $data['status'], $this->pdo);
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
     * Soft delete project
     */
    public function softDelete($id, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getById($id, $companyId);
            if (!$existing) {
                throw new Exception("Projet introuvable.", 404);
            }

            $stmt = $this->pdo->prepare("UPDATE projects SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id");
            $stmt->execute(['id' => $id, 'company_id' => $companyId]);

            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'projects', 'projects', $id, 'deleted', $userId, "Projet supprimé logiquement: " . $existing['project_code'], $this->pdo);

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
    // TASK LAYER METHODS
    // ==========================================

    /**
     * Get tasks for a project
     */
    public function getTasks($projectId, $companyId, $filters = []) {
        $sql = "SELECT t.*, u.name AS assigned_user_name 
                FROM project_tasks t
                LEFT JOIN users u ON t.assigned_user_id = u.id
                WHERE t.project_id = :project_id AND t.company_id = :company_id AND t.deleted_at IS NULL";
        
        $params = [':project_id' => $projectId, ':company_id' => $companyId];

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY t.created_at ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get single task by ID
     */
    public function getTaskById($id, $companyId) {
        $sql = "SELECT t.*, p.name AS project_name, p.project_code, u.name AS assigned_user_name 
                FROM project_tasks t
                JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_user_id = u.id
                WHERE t.id = :id AND t.company_id = :company_id AND t.deleted_at IS NULL LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create project task
     */
    public function createTask($data, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $projectId = (int)$data['project_id'];

            // 1. Verify Project belongs to Company
            $project = $this->getById($projectId, $companyId);
            if (!$project) {
                throw new Exception("Projet introuvable.", 404);
            }

            // 2. Generate Sequential Code using TASK sequence prefix
            require_once __DIR__ . '/../../../admin/sequences_helper.php';
            $taskCode = generateSequence($companyId, 'TASK', $this->pdo);

            // 3. Insert task row
            $sql = "INSERT INTO project_tasks (
                company_id, project_id, assigned_user_id, task_code, title, description, 
                priority, status, due_date, estimated_hours
            ) VALUES (
                :company_id, :project_id, :assigned_user_id, :task_code, :title, :description, 
                :priority, :status, :due_date, :estimated_hours
            )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'company_id' => $companyId,
                'project_id' => $projectId,
                'assigned_user_id' => $data['assigned_user_id'] ? (int)$data['assigned_user_id'] : null,
                'task_code' => $taskCode,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'Medium',
                'status' => $data['status'] ?? 'Todo',
                'due_date' => $data['due_date'] ?? null,
                'estimated_hours' => $data['estimated_hours'] ?? 0.00
            ]);

            $taskId = $this->pdo->lastInsertId();

            // 4. Log entity timeline event
            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'projects', 'projects', $projectId, 'task_created', $userId, "Tâche créée: " . $taskCode . " - " . $data['title'] . " dans " . $project['project_code'], $this->pdo);

            $this->pdo->commit();
            return $taskId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update project task
     */
    public function updateTask($id, $data, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getTaskById($id, $companyId);
            if (!$existing) {
                throw new Exception("Tâche introuvable.", 404);
            }

            $sql = "UPDATE project_tasks SET 
                assigned_user_id = :assigned_user_id,
                title = :title,
                description = :description,
                priority = :priority,
                status = :status,
                due_date = :due_date,
                estimated_hours = :estimated_hours
                WHERE id = :id AND company_id = :company_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'assigned_user_id' => $data['assigned_user_id'] ? (int)$data['assigned_user_id'] : null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'status' => $data['status'],
                'due_date' => $data['due_date'] ?? null,
                'estimated_hours' => $data['estimated_hours'] ?? 0.00,
                'id' => $id,
                'company_id' => $companyId
            ]);

            // Log status transitions on timeline
            if ($existing['status'] !== $data['status']) {
                require_once __DIR__ . '/../../../admin/timeline_helper.php';
                logEntityEvent($companyId, 'projects', 'projects', $existing['project_id'], 'task_updated', $userId, "Tâche " . $existing['task_code'] . " mise à jour vers le statut: " . $data['status'], $this->pdo);
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
     * Soft delete project task
     */
    public function softDeleteTask($id, $companyId, $userId) {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->getTaskById($id, $companyId);
            if (!$existing) {
                throw new Exception("Tâche introuvable.", 404);
            }

            $stmt = $this->pdo->prepare("UPDATE project_tasks SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id");
            $stmt->execute(['id' => $id, 'company_id' => $companyId]);

            require_once __DIR__ . '/../../../admin/timeline_helper.php';
            logEntityEvent($companyId, 'projects', 'projects', $existing['project_id'], 'task_deleted', $userId, "Tâche supprimée logiquement: " . $existing['task_code'], $this->pdo);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
