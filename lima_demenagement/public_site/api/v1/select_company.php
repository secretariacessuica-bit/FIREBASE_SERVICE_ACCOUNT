<?php
// LIMA Solutions ERP - Switch Active Company V1
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$companyId = isset($input['company_id']) ? (int)$input['company_id'] : 0;

if (!$companyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de empresa inválido.']);
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];

    // If not super_admin, check user-company association
    if ($userRole !== 'super_admin') {
        $stmtUC = $pdo->prepare("SELECT COUNT(*) FROM user_companies WHERE user_id = :uid AND company_id = :cid");
        $stmtUC->execute(['uid' => $userId, 'cid' => $companyId]);
        if ($stmtUC->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado para esta empresa.']);
            exit();
        }
    }

    // Verify company is active
    $stmtC = $pdo->prepare("SELECT name FROM companies WHERE id = :id AND active = 1 LIMIT 1");
    $stmtC->execute(['id' => $companyId]);
    $company = $stmtC->fetch();

    if (!$company) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Empresa inativa ou não encontrada.']);
        exit();
    }

    // Override active company scope
    $_SESSION['selected_company_id'] = $companyId;
    $_SESSION['company_name'] = $company['name'];

    // Insert log in Audit Trail
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (:uid, :act, :ip, :ua)");
    $logStmt->execute([
        'uid' => $userId,
        'act' => 'Switch Company to ' . $company['name'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?: 'Unknown'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Empresa ativa alterada com sucesso!',
        'company' => [
            'id' => $companyId,
            'name' => $company['name']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno ao alterar empresa: ' . $e->getMessage()]);
}
exit();
