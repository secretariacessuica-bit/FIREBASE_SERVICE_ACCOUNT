<?php
// LIMA Solutions ERP - Marketplace Items API V1
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// Caminho de storage seguro fora do webroot
$storageDir = __DIR__ . '/../../../../private_lima/storage/marketplace/';
if (!file_exists($storageDir)) {
    $storageDir = __DIR__ . '/../../../../private/storage/marketplace/';
}

// Garantir que a pasta existe com permissões adequadas
if (!file_exists($storageDir)) {
    @mkdir($storageDir, 0755, true);
}

// Sub-action: download (Sem expor caminhos físicos)
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download') {
    $photoId = (int)($_GET['photo_id'] ?? 0);
    if (!$photoId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'photo_id inválido.']);
        exit();
    }

    $stmtPhoto = $pdo->prepare("SELECT * FROM marketplace_photos WHERE id = :id LIMIT 1");
    $stmtPhoto->execute(['id' => $photoId]);
    $photo = $stmtPhoto->fetch();

    if (!$photo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Photo introuvable.']);
        exit();
    }

    $filePath = $storageDir . $photo['filename'];
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Fichier introuvable.']);
        exit();
    }

    header('Content-Type: ' . $photo['mime_type']);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . basename($photo['original_name']) . '"');
    readfile($filePath);
    exit();
}

// Listar anúncios do catálogo público (Apenas "Approved")
if ($method === 'GET' && !isset($_GET['scope'])) {
    try {
        $stmt = $pdo->prepare("SELECT i.*, c.name as category_name 
            FROM marketplace_items i
            JOIN marketplace_categories c ON i.category_id = c.id
            WHERE i.status = 'Approved'
            ORDER BY i.created_at DESC");
        $stmt->execute();
        $items = $stmt->fetchAll();

        // Anexar fotos e URLs seguras de download
        foreach ($items as &$it) {
            $stmtPhotos = $pdo->prepare("SELECT id FROM marketplace_photos WHERE item_id = :item_id");
            $stmtPhotos->execute(['item_id' => $it['id']]);
            $rawPhotos = $stmtPhotos->fetchAll();

            $it['photos'] = [];
            foreach ($rawPhotos as $p) {
                $it['photos'][] = "/api/v1/marketplace/items.php?action=download&photo_id=" . $p['id'];
            }
        }

        echo json_encode(['success' => true, 'data' => ['items' => $items]]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Daqui em diante: Requer sessão de cliente autenticado (Portal do Cliente)
if (!isset($_SESSION['client_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé. Veuillez vous connecter.']);
    exit();
}

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

// GET com ?scope=my_items: Listar anúncios do próprio cliente logado
if ($method === 'GET' && isset($_GET['scope']) && $_GET['scope'] === 'my_items') {
    try {
        $stmt = $pdo->prepare("SELECT i.*, c.name as category_name 
            FROM marketplace_items i
            JOIN marketplace_categories c ON i.category_id = c.id
            WHERE i.client_id = :client_id AND i.company_id = :company_id
            ORDER BY i.created_at DESC");
        $stmt->execute(['client_id' => $clientId, 'company_id' => $companyId]);
        $items = $stmt->fetchAll();

        foreach ($items as &$it) {
            $stmtPhotos = $pdo->prepare("SELECT id FROM marketplace_photos WHERE item_id = :item_id");
            $stmtPhotos->execute(['item_id' => $it['id']]);
            $rawPhotos = $stmtPhotos->fetchAll();

            $it['photos'] = [];
            foreach ($rawPhotos as $p) {
                $it['photos'][] = "/api/v1/marketplace/items.php?action=download&photo_id=" . $p['id'];
            }
        }

        echo json_encode(['success' => true, 'data' => ['items' => $items]]);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// POST: Criar novo anúncio (Draft ou Pending)
if ($method === 'POST') {
    // Processamento de multipart/form-data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $location = trim($_POST['location'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Pending'); // Draft, Pending

    $requestDelivery = isset($_POST['request_delivery']) && $_POST['request_delivery'] == 1 ? 1 : 0;
    $requestStorage = isset($_POST['request_storage']) && $_POST['request_storage'] == 1 ? 1 : 0;

    if (empty($title) || empty($description) || empty($location) || !$categoryId) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO marketplace_items (company_id, client_id, category_id, title, description, price, location, status, request_delivery, request_storage) 
            VALUES (:cid, :client_id, :cat_id, :title, :desc, :price, :loc, :status, :req_delivery, :req_storage)");
        $stmt->execute([
            'cid' => $companyId,
            'client_id' => $clientId,
            'cat_id' => $categoryId,
            'title' => $title,
            'desc' => $description,
            'price' => $price,
            'loc' => $location,
            'status' => $status,
            'req_delivery' => $requestDelivery,
            'req_storage' => $requestStorage
        ]);

        $itemId = $pdo->lastInsertId();

        // Upload de fotos associadas
        if (isset($_FILES['photos'])) {
            $files = $_FILES['photos'];
            // Re-organiza array de arquivos se múltiplo
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;

            for ($i = 0; $i < $fileCount; $i++) {
                $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];

                if ($error !== UPLOAD_ERR_OK) continue;

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed)) continue;

                $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
                $dest = $storageDir . $safeFilename;

                if (move_uploaded_file($tmpName, $dest)) {
                    $insPhoto = $pdo->prepare("INSERT INTO marketplace_photos (item_id, filename, original_name, mime_type, size) 
                        VALUES (:item_id, :fname, :oname, :mime, :size)");
                    $insPhoto->execute([
                        'item_id' => $itemId,
                        'fname' => $safeFilename,
                        'oname' => $name,
                        'mime' => $type,
                        'size' => $size
                    ]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Annonce créée avec succès.', 'data' => ['item_id' => (int)$itemId]]);
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// PUT / PATCH: Editar anúncio ou atualizar status (ex: arquivar/reativar)
if ($method === 'PUT' || $method === 'PATCH') {
    // Para PUT com form-data padrão do PHP usaremos json parsing
    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = (int)($input['item_id'] ?? $_GET['item_id'] ?? 0);

    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de l\'annonce manquant.']);
        exit();
    }

    // Verificar se o item de fato pertence ao cliente autenticado
    $chkItem = $pdo->prepare("SELECT id FROM marketplace_items WHERE id = :id AND client_id = :client_id AND company_id = :company_id LIMIT 1");
    $chkItem->execute(['id' => $itemId, 'client_id' => $clientId, 'company_id' => $companyId]);
    if (!$chkItem->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès interdit.']);
        exit();
    }

    try {
        if (isset($input['status'])) {
            // Apenas arquivar/reativar
            $newStatus = trim($input['status']);
            // Regra: se reativar, volta a ser "Pending" para moderação
            if ($newStatus === 'Pending') {
                $stmt = $pdo->prepare("UPDATE marketplace_items SET status = 'Pending', rejection_reason = NULL WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("UPDATE marketplace_items SET status = :status WHERE id = :id");
            }
            $stmt->execute(['status' => $newStatus, 'id' => $itemId]);
        } else {
            // Edição de campos básicos
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $price = isset($input['price']) && $input['price'] !== '' ? (float)$input['price'] : null;
            $location = trim($input['location'] ?? '');
            $categoryId = (int)($input['category_id'] ?? 0);

            if (empty($title) || empty($description) || empty($location) || !$categoryId) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants.']);
                exit();
            }

            // Ao editar, volta para Pending moderação
            $stmt = $pdo->prepare("UPDATE marketplace_items 
                SET category_id = :cat_id, title = :title, description = :desc, price = :price, location = :loc, status = 'Pending', rejection_reason = NULL 
                WHERE id = :id");
            $stmt->execute([
                'cat_id' => $categoryId,
                'title' => $title,
                'desc' => $description,
                'price' => $price,
                'loc' => $location,
                'id' => $itemId
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Annonce mise à jour avec succès.']);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
exit();
