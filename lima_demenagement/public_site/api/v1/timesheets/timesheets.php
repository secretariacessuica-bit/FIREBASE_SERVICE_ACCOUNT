<?php
// LIMA Solutions ERP - Timesheets API V1

require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../admin/sequences_helper.php';
require_once '../../../admin/timeline_helper.php';
require_once '../../../admin/audit_helper.php';
require_once '../../../modules/timesheets/model/Timesheet.php';
require_once '../../../modules/timesheets/controller/TimesheetController.php';

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
enforceModuleAccess('timesheets', $userRole, $companyId, 'view', $pdo);

$timesheetModel = new Timesheet($pdo);
$controller = new TimesheetController($timesheetModel);

$method = $_SERVER['REQUEST_METHOD'];

// CSRF Protection for mutating requests
if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    // Debug: Log raw input for troubleshooting
    $debugLog = __DIR__ . '/debug.log';
    @file_put_contents($debugLog, date('c') . " INPUT: " . print_r($input, true) . PHP_EOL, FILE_APPEND);
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
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $entry = $timesheetModel->getById($id, $companyId);
        if (!$entry) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Livre de temps non trouvé.']);
            exit();
        }
        echo json_encode(['success' => true, 'data' => ['timesheet' => $entry]]);
        exit();
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 200) $limit = 50;
    $offset = ($page - 1) * $limit;

    $filters = [
        'user_id' => $_GET['user_id'] ?? '',
        'project_id' => $_GET['project_id'] ?? '',
        'status' => $_GET['status'] ?? '',
        'start_date' => $_GET['start_date'] ?? '',
        'end_date' => $_GET['end_date'] ?? ''
    ];

    // Staff can only view their own timesheets unless they are manager roles
    if ($userRole === 'staff') {
        $filters['user_id'] = $userId;
    }

    $entries = $timesheetModel->getAll($companyId, $filters, $limit, $offset);
    $total = $timesheetModel->getTotalCount($companyId, $filters);

    echo json_encode([
        'success' => true,
        'data' => [
            'timesheets' => $entries,
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
if (!hasModulePermission($userRole, 'timesheets', 'edit', $pdo)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => "Accès interdit: Droits d'écriture insuffisants."]);
    exit();
}

if ($method === 'POST') {
    $action = $input['action'] ?? '';

    // Action: submit
    if ($action === 'submit') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID timesheet manquant.']);
            exit();
        }
        try {
            $timesheetModel->submit($id, $companyId, $userId);
            echo json_encode(['success' => true, 'message' => 'Timesheet soumis avec succès.']);
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // Manager role verification helper
    $isManager = in_array($userRole, ['admin', 'super_admin', 'finance']);

    // Action: approve
    if ($action === 'approve') {
        if (!$isManager) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Accès interdit: Seuls les administrateurs ou responsables financiers peuvent approuver les timesheets."]);
            exit();
        }
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID timesheet manquant.']);
            exit();
        }
        try {
            $timesheetModel->approve($id, $companyId, $userId);
            echo json_encode(['success' => true, 'message' => 'Timesheet approuvé avec succès.']);
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // Action: reject
    if ($action === 'reject') {
        if (!$isManager) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Accès interdit: Seuls les administrateurs ou responsables financiers peuvent rejeter les timesheets."]);
            exit();
        }
        $id = (int)($input['id'] ?? 0);
        $reason = trim($input['rejection_reason'] ?? '');
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID timesheet manquant.']);
            exit();
        }
        if (empty($reason)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Le motif de rejet é obligatoire.']);
            exit();
        }
        try {
            $timesheetModel->reject($id, $reason, $companyId, $userId);
            echo json_encode(['success' => true, 'message' => 'Timesheet rejeté avec succès.']);
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
            echo json_encode(['success' => false, 'message' => 'ID timesheet manquant.']);
            exit();
        }
        try {
            $timesheetModel->softDelete($id, $companyId, $userId);
            echo json_encode(['success' => true, 'message' => 'Timesheet supprimé avec succès.']);
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
            echo json_encode(['success' => false, 'message' => 'ID timesheet manquant.']);
            exit();
        }
        $cleanData = $controller->sanitize($input);
        $errors = $controller->validate($cleanData);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit();
        }
        try {
            $timesheetModel->update($id, $cleanData, $companyId, $userId);
            echo json_encode(['success' => true, 'message' => 'Timesheet mis à jour avec succès.']);
        } catch (Exception $e) {
            http_response_code($e->getCode() === 409 ? 409 : 500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // Default POST: Log timesheet entry
    $cleanData = $controller->sanitize($input);
    $errors = $controller->validate($cleanData);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }
    try {
        $timesheetId = $timesheetModel->create($cleanData, $companyId, $userId);
        echo json_encode(['success' => true, 'message' => 'Heures enregistrées avec succès.', 'data' => ['id' => $timesheetId]]);
    } catch (Exception $e) {
        @file_put_contents($debugLog, date('c') . " EXCEPTION (create): " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code($e->getCode() === 409 ? 409 : 500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
