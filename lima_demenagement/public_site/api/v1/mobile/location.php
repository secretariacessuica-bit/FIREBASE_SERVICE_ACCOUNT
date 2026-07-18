<?php
// LIMA Solutions ERP - Mobile Location Tracking Endpoint V1
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
    $projectId = (int)($_GET['project_id'] ?? 0);
    if (!$projectId) {
        sendMobileError('VALIDATION_ERROR', 'O campo project_id é obrigatório.', 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM gps_tracking 
            WHERE project_id = :pid AND company_id = :cid 
            ORDER BY recorded_at DESC LIMIT 200");
        $stmt->execute(['pid' => $projectId, 'cid' => $companyId]);
        $history = $stmt->fetchAll();
        sendMobileSuccess(['history' => $history]);
    } catch (Exception $e) {
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $requestStart = microtime(true);
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    // Suporte a inserções individuais ou em lote (batch upload para offline)
    $batch = isset($input['locations']) && is_array($input['locations']) ? $input['locations'] : [$input];

    try {
        $pdo->beginTransaction();
        $inserted = [];

        $stmt = $pdo->prepare("INSERT INTO gps_tracking (company_id, project_id, user_id, latitude, longitude, recorded_at, client_uuid, created_offline_at, sync_status) 
            VALUES (:cid, :pid, :uid, :lat, :lon, :rec, :cuuid, :coffline, 'Synced')");

        foreach ($batch as $loc) {
            $projectId = (int)($loc['project_id'] ?? 0);
            $lat = (float)($loc['latitude'] ?? 0);
            $lon = (float)($loc['longitude'] ?? 0);
            $recordedAt = trim($loc['recorded_at'] ?? date('Y-m-d H:i:s'));
            
            $clientUuid = trim($loc['client_uuid'] ?? '');
            $createdOfflineAt = trim($loc['created_offline_at'] ?? '');

            if (!$projectId || !$lat || !$lon) {
                continue; // Ignora registo inválido
            }

            $stmt->execute([
                'cid' => $companyId,
                'pid' => $projectId,
                'uid' => $userId,
                'lat' => $lat,
                'lon' => $lon,
                'rec' => $recordedAt,
                'cuuid' => !empty($clientUuid) ? $clientUuid : null,
                'coffline' => !empty($createdOfflineAt) ? $createdOfflineAt : null
            ]);

            $inserted[] = [
                'id' => $pdo->lastInsertId(),
                'client_uuid' => $clientUuid
            ];
        }

        $pdo->commit();
        require_once '../../../helpers/ObservabilityHelper.php';
        $firstLoc = reset($batch) ?: [];
        $firstUuid = $firstLoc['client_uuid'] ?? '';
        $firstProj = (int)($firstLoc['project_id'] ?? 0);
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), true, $firstUuid, $firstProj, '', $pdo);
        sendMobileSuccess(['inserted' => $inserted]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        require_once '../../../helpers/ObservabilityHelper.php';
        $firstLoc = reset($batch) ?: [];
        $firstUuid = $firstLoc['client_uuid'] ?? '';
        $firstProj = (int)($firstLoc['project_id'] ?? 0);
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $firstUuid, $firstProj, $e->getMessage(), $pdo);
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido.']]);
