<?php
// LIMA Solutions ERP - Timesheets Billing Conversion API

require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../modules/timesheets/model/Timesheet.php';

header('Content-Type: application/json; charset=utf-8');

$companyId = getActiveCompanyId();
if (!$companyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucune entreprise active sélectionnée.']);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'viewer';
$userId = $_SESSION['user_id'] ?? 0;

// Enforce module access & permission
enforceModuleAccess('timesheets', $userRole, $companyId, 'edit', $pdo);

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée. Seul POST est accepté.']);
    exit();
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// CSRF check
$clientCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
$sessionCsrfToken = $_SESSION['csrf_token'] ?? '';

if (empty($sessionCsrfToken) || empty($clientCsrfToken) || !hash_equals($sessionCsrfToken, $clientCsrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Erreur de sécurité CSRF: Requête rejetée.']);
    exit();
}

$timesheetIds = $input['timesheet_ids'] ?? [];
$groupBy = $input['group_by'] ?? 'detailed';

if (empty($timesheetIds) || !is_array($timesheetIds)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Veuillez sélectionner au moins un timesheet.']);
    exit();
}

// Convert IDs to integers
$timesheetIds = array_map('intval', $timesheetIds);

try {
    $timesheetModel = new Timesheet($pdo);
    $result = $timesheetModel->convertToInvoice($timesheetIds, $companyId, $userId, ['group_by' => $groupBy]);

    echo json_encode([
        'success' => true,
        'message' => 'Facture générée avec succès.',
        'data' => [
            'invoice_id' => $result['invoice_id'],
            'invoice_number' => $result['invoice_number'],
            'billing_batch_id' => $result['billing_batch_id'],
            'redirect_url' => '../../invoices/views/list.php'
        ]
    ]);
    exit();

} catch (Exception $e) {
    $code = $e->getCode();
    if (!in_array($code, [400, 401, 403, 409, 422])) {
        $code = 500;
    }
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}
