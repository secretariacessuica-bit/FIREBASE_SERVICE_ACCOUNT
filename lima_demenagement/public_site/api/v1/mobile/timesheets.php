<?php
// LIMA Solutions ERP - Mobile Timesheets Endpoint V1
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
    try {
        $stmt = $pdo->prepare("SELECT * FROM timesheets WHERE user_id = :uid AND company_id = :cid AND deleted_at IS NULL ORDER BY work_date DESC, start_time DESC");
        $stmt->execute(['uid' => $userId, 'cid' => $companyId]);
        $timesheets = $stmt->fetchAll();
        sendMobileSuccess(['timesheets' => $timesheets]);
    } catch (Exception $e) {
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $requestStart = microtime(true);
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $projectId = (int)($input['project_id'] ?? 0);
    $workDate = trim($input['work_date'] ?? date('Y-m-d'));
    $startTime = trim($input['start_time'] ?? '');
    $endTime = trim($input['end_time'] ?? '');
    $hours = (float)($input['hours'] ?? 0.00);
    
    // Suporte offline-first
    $clientUuid = trim($input['client_uuid'] ?? '');
    $createdOfflineAt = trim($input['created_offline_at'] ?? '');
    $syncStatus = trim($input['sync_status'] ?? 'Synced');

    if (!$projectId) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, 0, 'O campo project_id é obrigatório.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'O campo project_id é obrigatório.', 400);
    }

    try {
        $hourlyRate = 0.00;
        $hourlyCost = 0.00;

        $stmt = $pdo->prepare("INSERT INTO timesheets (company_id, project_id, user_id, work_date, start_time, end_time, hours, status, hourly_rate, approved_hourly_cost, approved_billable_rate) 
            VALUES (:cid, :pid, :uid, :wdate, :start, :end, :hours, 'Submitted', :hrate, :hcost, :brate)");
        
        if ($hours <= 0 && !empty($startTime) && !empty($endTime)) {
            $diff = strtotime($endTime) - strtotime($startTime);
            if ($diff > 0) {
                $hours = round($diff / 3600, 2);
            }
        }

        $stmt->execute([
            'cid' => $companyId,
            'pid' => $projectId,
            'uid' => $userId,
            'wdate' => $workDate,
            'start' => !empty($startTime) ? $startTime : null,
            'end' => !empty($endTime) ? $endTime : null,
            'hours' => $hours,
            'hrate' => $hourlyRate,
            'hcost' => $hourlyCost,
            'brate' => $hourlyRate
        ]);

        $timesheetId = $pdo->lastInsertId();

        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), true, $clientUuid, $projectId, '', $pdo);

        sendMobileSuccess([
            'timesheet_id' => (int)$timesheetId,
            'client_uuid' => $clientUuid,
            'sync_status' => 'Synced'
        ]);
    } catch (Exception $e) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, $e->getMessage(), $pdo);
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido.']]);
