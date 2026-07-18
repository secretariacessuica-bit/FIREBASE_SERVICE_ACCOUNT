<?php
// LIMA Solutions ERP - Mobile Signatures Endpoint V1
require_once '../config.php';
require_once 'auth_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!checkMobileAuth($pdo)) {
    sendMobileError('UNAUTHORIZED', 'Sessão expirada ou token inválido.');
}

$userId = $_SESSION['user_id'];
$companyId = $_SESSION['company_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Caminho de storage seguro fora do webroot
$storageDir = __DIR__ . '/../../../../private_lima/storage/project_signatures/';
if (!file_exists($storageDir)) {
    $storageDir = __DIR__ . '/../../../../private/storage/project_signatures/';
}

// Garantir que a pasta existe com permissões adequadas
if (!file_exists($storageDir)) {
    @mkdir($storageDir, 0755, true);
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'download') {
        $sigId = (int)($_GET['id'] ?? 0);
        if (!$sigId) {
            sendMobileError('VALIDATION_ERROR', 'ID de assinatura inválido.', 400);
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM project_signatures WHERE id = :id AND company_id = :cid LIMIT 1");
            $stmt->execute(['id' => $sigId, 'cid' => $companyId]);
            $sig = $stmt->fetch();

            if (!$sig) {
                sendMobileError('NOT_FOUND', 'Assinatura não encontrada.', 404);
            }

            $filePath = $storageDir . $sig['signature_path'];
            if (!file_exists($filePath)) {
                sendMobileError('NOT_FOUND', 'Arquivo físico não encontrado no servidor seguro.', 404);
            }

            header('Content-Type: image/png');
            header('Content-Length: ' . filesize($filePath));
            header('Content-Disposition: inline; filename="signature_' . $sigId . '.png"');
            readfile($filePath);
            exit();
        } catch (Exception $e) {
            sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    $projectId = (int)($_GET['project_id'] ?? 0);
    if (!$projectId) {
        sendMobileError('VALIDATION_ERROR', 'O campo project_id é obrigatório.', 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT id, project_id, client_name, signed_at, client_uuid, sync_status 
            FROM project_signatures 
            WHERE project_id = :pid AND company_id = :cid 
            ORDER BY signed_at DESC");
        $stmt->execute(['pid' => $projectId, 'cid' => $companyId]);
        $sigs = $stmt->fetchAll();

        foreach ($sigs as &$s) {
            $s['download_url'] = "/api/v1/mobile/signatures.php?action=download&id=" . $s['id'];
        }

        sendMobileSuccess(['signatures' => $sigs]);
    } catch (Exception $e) {
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $requestStart = microtime(true);
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $projectId = (int)($input['project_id'] ?? 0);
    $clientName = trim($input['client_name'] ?? '');
    $signatureBase64 = trim($input['signature_data'] ?? ''); // data:image/png;base64,...
    
    // Suporte offline-first
    $clientUuid = trim($input['client_uuid'] ?? '');
    $createdOfflineAt = trim($input['created_offline_at'] ?? '');

    if (!$projectId || empty($clientName) || empty($signatureBase64)) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'Os campos project_id, client_name e signature_data são obrigatórios.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'Os campos project_id, client_name e signature_data são obrigatórios.', 400);
    }

    // Validar formato base64 de imagem
    if (strpos($signatureBase64, 'data:image/png;base64,') !== 0) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'Formato da assinatura deve ser um Data URI Base64 válido.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'O formato da assinatura deve ser um Data URI Base64 válido contendo uma imagem PNG.', 400);
    }

    // Extrair dados base64 puro
    $dataStr = substr($signatureBase64, strpos($signatureBase64, ',') + 1);
    $decodedData = base64_decode($dataStr, true);
    if ($decodedData === false) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'Falha ao decodificar a string Base64 da assinatura.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'Falha ao decodificar a string Base64 da assinatura.', 400);
    }

    // Gerar nome de arquivo interno único
    $safeFilename = bin2hex(random_bytes(16)) . '.png';
    $destination = $storageDir . $safeFilename;

    if (file_put_contents($destination, $decodedData) === false) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'Falha ao gravar arquivo de assinatura digital.', $pdo);
        sendMobileError('SERVER_ERROR', 'Falha ao gravar arquivo de assinatura digital no storage seguro.', 500);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO project_signatures (company_id, project_id, client_name, signature_path, client_uuid, created_offline_at, sync_status) 
            VALUES (:cid, :pid, :name, :path, :cuuid, :coffline, 'Synced')");
        $stmt->execute([
            'cid' => $companyId,
            'pid' => $projectId,
            'name' => $clientName,
            'path' => $safeFilename,
            'cuuid' => !empty($clientUuid) ? $clientUuid : null,
            'coffline' => !empty($createdOfflineAt) ? $createdOfflineAt : null
        ]);

        $signatureId = $pdo->lastInsertId();

        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), true, $clientUuid, $projectId, '', $pdo);

        sendMobileSuccess([
            'signature_id' => (int)$signatureId,
            'client_uuid' => $clientUuid,
            'download_url' => "/api/v1/mobile/signatures.php?action=download&id=" . $signatureId
        ]);
    } catch (Exception $e) {
        @unlink($destination); // Limpar arquivo órfão
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, $e->getMessage(), $pdo);
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido.']]);
