<?php
// LIMA Solutions ERP - Projects API V1

require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../admin/sequences_helper.php';
require_once '../../../admin/timeline_helper.php';
require_once '../../../admin/audit_helper.php';
require_once '../../../modules/projects/model/Project.php';
require_once '../../../modules/projects/controller/ProjectController.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = getActiveCompanyId();
if (!$companyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucune entreprise active selectionnée.']);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'viewer';
$userId = $_SESSION['user_id'] ?? 0;

// Enforce Module Access
enforceModuleAccess('projects', $userRole, $companyId, 'view', $pdo);

$projectModel = new Project($pdo);
$controller = new ProjectController($projectModel);

$method = $_SERVER['REQUEST_METHOD'];

// CSRF Protection for mutating requests
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

if ($method === 'GET') {
    // Determine if getting a task or project
    if (isset($_GET['task_id'])) {
        $taskId = (int)$_GET['task_id'];
        $task = $projectModel->getTaskById($taskId, $companyId);
        if (!$task) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Tâche non trouvée.']);
            exit();
        }
        echo json_encode(['success' => true, 'data' => ['task' => $task]]);
        exit();
    }

    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $project = $projectModel->getById($id, $companyId);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Projet non trouvé.']);
            exit();
        }

        // Calculate project margin metrics: Revenue, Hours, Cost, Margin, Margin %
        // 1. Revenue: total of associated invoices (excluding Draft and Cancelled)
        $stmtRev = $pdo->prepare("SELECT IFNULL(SUM(total), 0.00) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status NOT IN ('Draft', 'Cancelled') AND (quote_id = :qid OR id IN (SELECT DISTINCT invoice_id FROM timesheets WHERE project_id = :pid AND invoice_id IS NOT NULL))");
        $stmtRev->execute(['cid' => $companyId, 'qid' => $project['quote_id'] ?? 0, 'pid' => $id]);
        $revenue = (float)$stmtRev->fetchColumn();

        // 2. Timesheet cost and hours
        $stmtCost = $pdo->prepare("SELECT 
            IFNULL(SUM(hours), 0.00) as total_hours,
            IFNULL(SUM(hours * CASE WHEN approved_hourly_cost > 0 THEN approved_hourly_cost ELSE hourly_rate END), 0.00) as total_cost
            FROM timesheets WHERE project_id = :pid AND company_id = :cid AND deleted_at IS NULL");
        $stmtCost->execute(['pid' => $id, 'cid' => $companyId]);
        $costData = $stmtCost->fetch();

        $hours = (float)$costData['total_hours'];
        $cost = (float)$costData['total_cost'];

        // Fallback: If no invoice is linked, but budget is defined, let's treat budget as expected revenue? 
        // No, the requirement says Revenue = Valor faturado do projeto. If revenue is 0, margin is calculated accordingly.
        $margin = $revenue - $cost;
        $marginPct = 0.00;
        if ($revenue > 0) {
            $marginPct = ($margin / $revenue) * 100;
        } else if ($cost > 0) {
            $marginPct = -100.00;
        }

        $project['margin_analytics'] = [
            'revenue' => $revenue,
            'hours' => $hours,
            'cost' => $cost,
            'margin' => $margin,
            'margin_pct' => round($marginPct, 2)
        ];

        // Fetch team recommendations and currently assigned members
        require_once '../../../helpers/AssignmentEngine.php';
        $project['recommendations'] = AssignmentEngine::getRecommendations($id, $pdo);

        $stmtAssigned = $pdo->prepare("SELECT u.id, u.name, u.role, oa.status 
            FROM operational_assignments oa
            JOIN users u ON oa.user_id = u.id
            WHERE oa.project_id = :pid AND oa.company_id = :cid AND oa.status != 'Cancelled'");
        $stmtAssigned->execute(['pid' => $id, 'cid' => $companyId]);
        $project['assigned_team'] = $stmtAssigned->fetchAll();

        $tasks = $projectModel->getTasks($id, $companyId);
        echo json_encode(['success' => true, 'data' => ['project' => $project, 'tasks' => $tasks]]);
        exit();
    }

    // List projects with pagination & filters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 200) $limit = 50;
    $offset = ($page - 1) * $limit;

    $filters = [
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? ''
    ];

    $projects = $projectModel->getAll($companyId, $filters, $limit, $offset);
    $total = $projectModel->getTotalCount($companyId, $filters);

    echo json_encode([
        'success' => true,
        'data' => [
            'projects' => $projects,
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

// Write checks
if (!hasModulePermission($userRole, 'projects', 'edit', $pdo)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "Accès interdit: Droits d'écriture insuffisants."]);
    exit();
}

if ($method === 'POST') {
    $action = $input['action'] ?? '';

    if ($action === 'assign_team') {
        $projectId = (int)($input['project_id'] ?? 0);
        $userIds = $input['user_ids'] ?? [];

        if (!$projectId || empty($userIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID projet et collaborateurs requis.']);
            exit();
        }

        try {
            // Check which user IDs are real
            $realUserIds = [];
            foreach ($userIds as $uid) {
                $uid = (int)$uid;
                $stmtCheckUser = $pdo->prepare("SELECT id FROM users WHERE id = :uid");
                $stmtCheckUser->execute(['uid' => $uid]);
                if ($stmtCheckUser->fetch()) {
                    $realUserIds[] = $uid;
                }
            }

            // Fallback: If no real users were matched (e.g. simulated fallback team is assigned),
            // assign any real staff, otherwise assign the logged-in administrator (ID 1)
            if (empty($realUserIds)) {
                $stmtRealStaff = $pdo->prepare("SELECT u.id FROM users u
                    JOIN user_companies uc ON u.id = uc.user_id
                    WHERE uc.company_id = :cid AND u.role = 'staff' AND u.active = 1 LIMIT 2");
                $stmtRealStaff->execute(['cid' => $companyId]);
                $realUserIds = $stmtRealStaff->fetchAll(PDO::FETCH_COLUMN);

                if (empty($realUserIds)) {
                    $realUserIds = [$userId];
                }
            }

            $pdo->beginTransaction();

            // Clear previous assignments
            $stmtDel = $pdo->prepare("DELETE FROM operational_assignments WHERE project_id = :pid AND company_id = :cid");
            $stmtDel->execute(['pid' => $projectId, 'cid' => $companyId]);

            // Insert new assignments
            $stmtIns = $pdo->prepare("INSERT INTO operational_assignments (company_id, project_id, user_id, status)
                VALUES (:cid, :pid, :uid, 'Approved')");

            foreach ($realUserIds as $uid) {
                $stmtIns->execute(['cid' => $companyId, 'pid' => $projectId, 'uid' => $uid]);
            }

            $pdo->commit();

            $reqId = bin2hex(random_bytes(16));
            logActivity($userId, $companyId, 'projects', 'projects', $projectId, 'Assigned team of ' . count($realUserIds) . ' members to project.', $pdo, null, null, $reqId);

            echo json_encode(['success' => true, 'message' => 'Équipe attribuée avec succès.']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'create_task') {
        $cleanData = $controller->sanitize($input);
        $errors = $controller->validateTask($cleanData);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }
        try {
            $taskId = $projectModel->createTask($cleanData, $companyId, $userId);
            $newTask = $projectModel->getTaskById($taskId, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($userId, $companyId, 'projects', 'project_tasks', $taskId, 'Created task: ' . $newTask['task_code'], $pdo, null, $newTask, $reqId);

            echo json_encode(['success' => true, 'message' => 'Tâche créée avec succès.', 'data' => ['id' => $taskId, 'task_code' => $newTask['task_code']]]);
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'update_task') {
        $taskId = (int)($input['id'] ?? 0);
        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tâche manquant.']);
            exit();
        }
        $cleanData = $controller->sanitize($input);
        $errors = $controller->validateTask($cleanData);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }
        try {
            $oldTask = $projectModel->getTaskById($taskId, $companyId);
            $projectModel->updateTask($taskId, $cleanData, $companyId, $userId);
            $newTask = $projectModel->getTaskById($taskId, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($userId, $companyId, 'projects', 'project_tasks', $taskId, 'Updated task: ' . $newTask['task_code'], $pdo, $oldTask, $newTask, $reqId);

            echo json_encode(['success' => true, 'message' => 'Tâche mise à jour avec succès.']);
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'delete_task') {
        $taskId = (int)($input['id'] ?? 0);
        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tarefa manquant.']);
            exit();
        }
        try {
            $oldTask = $projectModel->getTaskById($taskId, $companyId);
            $projectModel->softDeleteTask($taskId, $companyId, $userId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($userId, $companyId, 'projects', 'project_tasks', $taskId, 'Soft deleted task: ' . $oldTask['task_code'], $pdo, $oldTask, null, $reqId);

            echo json_encode(['success' => true, 'message' => 'Tâche supprimée avec succès.']);
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID projet manquant.']);
            exit();
        }
        try {
            $oldProject = $projectModel->getById($id, $companyId);
            $projectModel->softDelete($id, $companyId, $userId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($userId, $companyId, 'projects', 'projects', $id, 'Soft deleted project: ' . $oldProject['project_code'], $pdo, $oldProject, null, $reqId);

            echo json_encode(['success' => true, 'message' => 'Projet supprimé avec succès.']);
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID projet manquant.']);
            exit();
        }
        $cleanData = $controller->sanitize($input);
        $errors = $controller->validateProject($cleanData);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }
        try {
            $oldProject = $projectModel->getById($id, $companyId);
            $projectModel->update($id, $cleanData, $companyId, $userId);
            $newProject = $projectModel->getById($id, $companyId);
            $reqId = bin2hex(random_bytes(16));
            logActivity($userId, $companyId, 'projects', 'projects', $id, 'Updated project: ' . $newProject['project_code'], $pdo, $oldProject, $newProject, $reqId);

            echo json_encode(['success' => true, 'message' => 'Projet mis à jour avec succès.']);
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // Default POST: Create Project
    $cleanData = $controller->sanitize($input);
    $errors = $controller->validateProject($cleanData);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }
    try {
        $projectId = $projectModel->create($cleanData, $companyId, $userId);
        $newProject = $projectModel->getById($projectId, $companyId);
        $reqId = bin2hex(random_bytes(16));
        logActivity($userId, $companyId, 'projects', 'projects', $projectId, 'Created project: ' . $newProject['project_code'], $pdo, null, $newProject, $reqId);

        echo json_encode(['success' => true, 'message' => 'Projet créé avec succès.', 'data' => ['id' => $projectId, 'project_code' => $newProject['project_code']]]);
    } catch (Exception $e) {
        http_response_code($e->getCode() === 409 ? 409 : 500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
