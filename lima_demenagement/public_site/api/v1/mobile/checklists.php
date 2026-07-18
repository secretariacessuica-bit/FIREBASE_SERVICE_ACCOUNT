<?php
// LIMA Solutions ERP - Mobile Checklists Endpoint V1
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
        $stmt = $pdo->prepare("SELECT * FROM project_checklists WHERE project_id = :pid AND company_id = :cid ORDER BY item_name ASC");
        $stmt->execute(['pid' => $projectId, 'cid' => $companyId]);
        $items = $stmt->fetchAll();

        // Se a checklist estiver vazia para o projeto, popular automaticamente com itens padrão
        // baseado nos serviços do orçamento ou inventário padrão para evitar inicializações manuais
        if (empty($items)) {
            $defaultItems = ['Chargement mobilier principal', 'Emballage des objets fragiles', 'Démontage des meubles', 'Nettoyage / Aspirateur', 'Livraison et Remontage'];
            
            $pdo->beginTransaction();
            $ins = $pdo->prepare("INSERT INTO project_checklists (company_id, project_id, item_name, status) VALUES (:cid, :pid, :item, 'Pending')");
            foreach ($defaultItems as $item) {
                $ins->execute(['cid' => $companyId, 'pid' => $projectId, 'item' => $item]);
            }
            $pdo->commit();

            // Refetch
            $stmt->execute(['pid' => $projectId, 'cid' => $companyId]);
            $items = $stmt->fetchAll();
        }

        sendMobileSuccess(['checklist' => $items]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
    }
}

if ($method === 'POST') {
    $requestStart = microtime(true);
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if ($action === 'save') {
        // Salva respostas ou atualiza múltiplos itens
        $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [$input];

        try {
            $pdo->beginTransaction();
            $updated = [];

            $stmt = $pdo->prepare("UPDATE project_checklists 
                SET status = :status, notes = :notes, updated_by = :uid, updated_at = NOW(), client_uuid = :cuuid, created_offline_at = :coffline, sync_status = 'Synced'
                WHERE id = :id AND company_id = :cid");

            foreach ($items as $item) {
                $itemId = (int)($item['id'] ?? 0);
                $status = trim($item['status'] ?? 'Pending');
                $notes = trim($item['notes'] ?? '');
                
                $clientUuid = trim($item['client_uuid'] ?? '');
                $createdOfflineAt = trim($item['created_offline_at'] ?? '');

                if (!$itemId) {
                    continue;
                }

                $stmt->execute([
                    'status' => $status,
                    'notes' => !empty($notes) ? $notes : null,
                    'uid' => $userId,
                    'cuuid' => !empty($clientUuid) ? $clientUuid : null,
                    'coffline' => !empty($createdOfflineAt) ? $createdOfflineAt : null,
                    'id' => $itemId,
                    'cid' => $companyId
                ]);

                $updated[] = [
                    'id' => $itemId,
                    'client_uuid' => $clientUuid
                ];
            }

            $pdo->commit();
            require_once '../../../helpers/ObservabilityHelper.php';
            $firstItem = reset($items) ?: [];
            $firstUuid = $firstItem['client_uuid'] ?? '';
            $firstProj = (int)($firstItem['project_id'] ?? 0);
            ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__) . '?action=save', true, $firstUuid, $firstProj, '', $pdo);
            sendMobileSuccess(['updated' => $updated]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            require_once '../../../helpers/ObservabilityHelper.php';
            $firstItem = reset($items) ?: [];
            $firstUuid = $firstItem['client_uuid'] ?? '';
            $firstProj = (int)($firstItem['project_id'] ?? 0);
            ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__) . '?action=save', false, $firstUuid, $firstProj, $e->getMessage(), $pdo);
            sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    if ($action === 'finalize') {
        $projectId = (int)($input['project_id'] ?? 0);
        if (!$projectId) {
            require_once '../../../helpers/ObservabilityHelper.php';
            ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__) . '?action=finalize', false, '', 0, 'O campo project_id é obrigatório.', $pdo);
            sendMobileError('VALIDATION_ERROR', 'O campo project_id é obrigatório.', 400);
        }

        try {
            // Marca itens pendentes como "Checked" de uma vez
            $stmt = $pdo->prepare("UPDATE project_checklists 
                SET status = 'Checked', updated_by = :uid, updated_at = NOW(), sync_status = 'Synced'
                WHERE project_id = :pid AND company_id = :cid AND status = 'Pending'");
            $stmt->execute(['uid' => $userId, 'pid' => $projectId, 'cid' => $companyId]);

            require_once '../../../helpers/ObservabilityHelper.php';
            ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__) . '?action=finalize', true, '', $projectId, '', $pdo);
            sendMobileSuccess(['message' => 'Checklist finalizada com sucesso.']);
        } catch (Exception $e) {
            require_once '../../../helpers/ObservabilityHelper.php';
            ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__) . '?action=finalize', false, '', $projectId, $e->getMessage(), $pdo);
            sendMobileError('SERVER_ERROR', $e->getMessage(), 500);
        }
    }

    require_once '../../../helpers/ObservabilityHelper.php';
    ObservabilityHelper::logMobileSync($requestStart, basename(__FILE__), false, '', 0, 'Ação inválida.', $pdo);
    sendMobileError('BAD_REQUEST', 'Ação inválida.', 400);
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido.']]);
