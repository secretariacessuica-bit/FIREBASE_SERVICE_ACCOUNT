<?php
// LIMA Solutions ERP - Mobile Photos Endpoint V1
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
$storageDir = __DIR__ . '/../../../../private_lima/storage/project_photos/';
if (!file_exists($storageDir)) {
    $storageDir = __DIR__ . '/../../../../private/storage/project_photos/';
}

// Garantir que a pasta existe com permissões adequadas
if (!file_exists($storageDir)) {
    @mkdir($storageDir, 0755, true);
}

if ($method === 'GET') {
    // 1. Download de foto seguro sem expor path físico
    $action = $_GET['action'] ?? '';
    if ($action === 'download') {
        $photoId = (int)($_GET['id'] ?? 0);
        if (!$photoId) {
            sendMobileError('VALIDATION_ERROR', 'ID de foto inválido.', 400);
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM project_photos WHERE id = :id AND company_id = :cid LIMIT 1");
            $stmt->execute(['id' => $photoId, 'cid' => $companyId]);
            $photo = $stmt->fetch();

            if (!$photo) {
                sendMobileError('NOT_FOUND', 'Foto não encontrada.', 404);
            }

            $filePath = $storageDir . $photo['filename'];
            if (!file_exists($filePath)) {
                sendMobileError('NOT_FOUND', 'Arquivo físico não encontrado no servidor seguro.', 404);
            }

            // Enviar headers corretos de exibição/download
            header('Content-Type: ' . $photo['mime_type']);
            header('Content-Length: ' . filesize($filePath));
            header('Content-Disposition: inline; filename="' . basename($photo['original_name']) . '"');
            readfile($filePath);
            exit();
        } catch (Exception $e) {
            sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    // 2. Consulta de lista de fotos por projeto
    $projectId = (int)($_GET['project_id'] ?? 0);
    if (!$projectId) {
        sendMobileError('VALIDATION_ERROR', 'O campo project_id é obrigatório.', 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT id, project_id, user_id, photo_type, original_name, mime_type, size, description, created_at, client_uuid, sync_status 
            FROM project_photos 
            WHERE project_id = :pid AND company_id = :cid 
            ORDER BY created_at DESC");
        $stmt->execute(['pid' => $projectId, 'cid' => $companyId]);
        $photos = $stmt->fetchAll();

        // Adicionar URLs indiretas de download seguro
        foreach ($photos as &$p) {
            $p['download_url'] = "/api/v1/mobile/photos.php?action=download&id=" . $p['id'];
        }

        sendMobileSuccess(['photos' => $photos]);
    } catch (Exception $e) {
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $requestStart = microtime(true);
    // Multipart upload
    $projectId = (int)($_POST['project_id'] ?? 0);
    $photoType = trim($_POST['photo_type'] ?? 'pre_move'); // pre_move, post_move, incident
    $description = trim($_POST['description'] ?? '');
    
    // Suporte offline-first
    $clientUuid = trim($_POST['client_uuid'] ?? '');
    $createdOfflineAt = trim($_POST['created_offline_at'] ?? '');

    if (!$projectId) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, 0, 'O campo project_id é obrigatório.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'O campo project_id é obrigatório.', 400);
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'Nenhum arquivo enviado ou erro no upload.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'Nenhum arquivo enviado ou ocorreu um erro no upload.', 400);
    }

    $file = $_FILES['file'];
    
    // 1. Validar tamanho máximo permitido (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'O arquivo excede o limite máximo de 5MB.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'O arquivo excede o limite máximo de 5MB.', 400);
    }

    // 2. Validar extensão
    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts)) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'Extensão de arquivo inválida.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'Extensão de arquivo inválida. Apenas JPG, PNG e WEBP são permitidos.', 400);
    }

    // 3. Validar MIME real do arquivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($realMime, $allowedMimes)) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'MIME type inválido detectado.', $pdo);
        sendMobileError('VALIDATION_ERROR', 'MIME type inválido detectado.', 400);
    }

    // 4. Gerar nome físico interno aleatório e seguro
    $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $storageDir . $safeFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, 'Erro ao mover o arquivo para o armazenamento seguro.', $pdo);
        sendMobileError('SERVER_ERROR', 'Erro ao mover o arquivo para o armazenamento seguro.', 500);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO project_photos (company_id, project_id, user_id, photo_type, filename, original_name, mime_type, size, description, client_uuid, created_offline_at, sync_status) 
            VALUES (:cid, :pid, :uid, :type, :fname, :oname, :mime, :size, :desc, :cuuid, :coffline, 'Synced')");
        $stmt->execute([
            'cid' => $companyId,
            'pid' => $projectId,
            'uid' => $userId,
            'type' => $photoType,
            'fname' => $safeFilename,
            'oname' => $originalName,
            'mime' => $realMime,
            'size' => $file['size'],
            'desc' => !empty($description) ? $description : null,
            'cuuid' => !empty($clientUuid) ? $clientUuid : null,
            'coffline' => !empty($createdOfflineAt) ? $createdOfflineAt : null
        ]);

        $photoId = $pdo->lastInsertId();

        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), true, $clientUuid, $projectId, '', $pdo);

        sendMobileSuccess([
            'photo_id' => (int)$photoId,
            'client_uuid' => $clientUuid,
            'download_url' => "/api/v1/mobile/photos.php?action=download&id=" . $photoId
        ]);
    } catch (Exception $e) {
        @unlink($destination); // Deletar arquivo órfão
        require_once '../../../helpers/ObservabilityHelper.php';
        ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, $clientUuid, $projectId, $e->getMessage(), $pdo);
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido.']]);
