<?php
// LIMA Solutions ERP - Mobile Projects Endpoint V1
require_once '../config.php';
require_once 'auth_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!checkMobileAuth($pdo)) {
    sendMobileError('UNAUTHORIZED', 'Sessão expirada ou token inválido.');
}

$userId = $_SESSION['user_id'];
$companyId = $_SESSION['company_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $projectId = (int)$_GET['id'];
        try {
            // Detalhe de projeto com moradas vindas de clientes ou orçamentos associados
            $stmt = $pdo->prepare("SELECT p.*, c.name AS client_name, c.phone AS client_phone, c.mobile AS client_mobile,
                c.address AS client_address, c.city AS client_city, c.canton AS client_canton, c.postal_code AS client_postal_code
                FROM projects p
                JOIN clients c ON p.client_id = c.id
                WHERE p.id = :id AND p.company_id = :cid AND p.deleted_at IS NULL LIMIT 1");
            $stmt->execute(['id' => $projectId, 'cid' => $companyId]);
            $project = $stmt->fetch();

            if (!$project) {
                sendMobileError('NOT_FOUND', 'Serviço operacional não encontrado.', 404);
            }

            // Buscar tarefas adicionais associadas
            $stmtTasks = $pdo->prepare("SELECT * FROM project_tasks WHERE project_id = :pid AND company_id = :cid AND deleted_at IS NULL");
            $stmtTasks->execute(['pid' => $projectId, 'cid' => $companyId]);
            $tasks = $stmtTasks->fetchAll();

            // Include geofence metadata targets
            require_once '../../../helpers/GeofenceHelper.php';
            $project['geofence_targets'] = GeofenceHelper::getGeofenceTargets($project, $project);

            sendMobileSuccess([
                'project' => $project,
                'tasks' => $tasks
            ]);
        } catch (Exception $e) {
            sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
        }
    } else {
        // Listar serviços atribuídos
        try {
            $stmt = $pdo->prepare("SELECT p.*, c.name AS client_name, oa.status AS assignment_status 
                FROM projects p
                JOIN clients c ON p.client_id = c.id
                JOIN operational_assignments oa ON p.id = oa.project_id
                WHERE oa.user_id = :uid AND p.company_id = :cid AND p.deleted_at IS NULL
                ORDER BY p.start_date ASC");
            $stmt->execute(['uid' => $userId, 'cid' => $companyId]);
            $projects = $stmt->fetchAll();
            sendMobileSuccess(['projects' => $projects]);
        } catch (Exception $e) {
            sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
        }
    }
}

if ($method === 'POST') {
    // Atualiza estado operacional do projeto
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $projectId = (int)($input['project_id'] ?? 0);
    $status = trim($input['status'] ?? '');

    if (!$projectId || empty($status)) {
        sendMobileError('VALIDATION_ERROR', 'Os campos project_id e status são obrigatórios.', 400);
    }

    try {
        // Validar atribuição operacional
        $stmtCheck = $pdo->prepare("SELECT id FROM operational_assignments WHERE project_id = :pid AND user_id = :uid AND company_id = :cid LIMIT 1");
        $stmtCheck->execute(['pid' => $projectId, 'uid' => $userId, 'cid' => $companyId]);
        if (!$stmtCheck->fetch()) {
            sendMobileError('FORBIDDEN', 'Não tem permissão para alterar o estado deste projeto.', 403);
        }

        // Atualizar status do projeto
        $up = $pdo->prepare("UPDATE projects SET status = :status, updated_at = NOW() WHERE id = :id AND company_id = :cid");
        $up->execute(['status' => $status, 'id' => $projectId, 'cid' => $companyId]);

        // Registrar no Entity Timeline
        $timelineStmt = $pdo->prepare("INSERT INTO entity_timeline (company_id, module, entity, entity_id, action, user_id, description) 
            VALUES (:cid, 'projects', 'projects', :pid, 'StatusUpdatedMobile', :uid, :desc)");
        $timelineStmt->execute([
            'cid' => $companyId,
            'pid' => $projectId,
            'uid' => $userId,
            'desc' => "Status updated via Mobile App to: " . $status
        ]);

        sendMobileSuccess(['message' => 'Estado do projeto atualizado com sucesso.']);
    } catch (Exception $e) {
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido.']]);
