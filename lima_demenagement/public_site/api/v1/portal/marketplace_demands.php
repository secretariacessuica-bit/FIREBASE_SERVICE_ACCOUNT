<?php
// LIMA Solutions ERP - Client Portal Marketplace Demands API
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT d.*, c.name as category_name 
                               FROM marketplace_demands d
                               LEFT JOIN marketplace_categories c ON d.category_id = c.id
                               WHERE d.client_id = :cid AND d.company_id = :comp
                               ORDER BY d.created_at DESC");
        $stmt->execute(['cid' => $clientId, 'comp' => $companyId]);
        $demands = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'demands' => $demands]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
    }
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
    $keywords = !empty($data['keywords']) ? trim($data['keywords']) : null;
    $maxPrice = !empty($data['max_price']) ? (float)$data['max_price'] : null;
    $location = !empty($data['location']) ? trim($data['location']) : null;
    
    // Default expiration: 30 days, max 60 days
    $expiresDays = !empty($data['expires_days']) ? (int)$data['expires_days'] : 30;
    if ($expiresDays > 60) $expiresDays = 60;
    if ($expiresDays < 1) $expiresDays = 30;
    
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiresDays days"));

    if (empty($categoryId) && empty($keywords)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Veuillez sélectionner une catégorie ou entrer des mots-clés.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO marketplace_demands (company_id, client_id, category_id, keywords, max_price, location, expires_at)
                               VALUES (:comp, :cid, :cat, :kw, :price, :loc, :exp)");
        $stmt->execute([
            'comp' => $companyId,
            'cid' => $clientId,
            'cat' => $categoryId,
            'kw' => $keywords,
            'price' => $maxPrice,
            'loc' => $location,
            'exp' => $expiresAt
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Demande (Preciso de) créée avec succès.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la demande.']);
    }
    exit();
}

if ($method === 'DELETE') {
    $demandId = $_GET['id'] ?? null;
    if (!$demandId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant.']);
        exit();
    }

    try {
        // Ensure demand belongs to this client
        $stmt = $pdo->prepare("DELETE FROM marketplace_demands WHERE id = :id AND client_id = :cid");
        $stmt->execute(['id' => $demandId, 'cid' => $clientId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Demande supprimée.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Demande introuvable ou non autorisée.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
