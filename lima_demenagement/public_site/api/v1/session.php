<?php
// LIMA Solutions ERP - Session Checker V1
require_once 'config.php';

// Send security headers for this API endpoint.
sendSecurityHeaders();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => false,
        'message'       => 'Sessão inativa.'
    ]);
    exit();
}

try {
    $userId = $_SESSION['user_id'];

    // Check if user is still active and valid
    $stmtUser = $pdo->prepare("SELECT id, name, email, role, active FROM users WHERE id = :id LIMIT 1");
    $stmtUser->execute(['id' => $userId]);
    $user = $stmtUser->fetch();

    if (!$user || $user['active'] == 0) {
        $_SESSION = [];
        session_destroy();
        echo json_encode([
            'authenticated' => false,
            'message'       => 'Utilizador inativo ou não encontrado.'
        ]);
        exit();
    }

    $activeCompanyId = getActiveCompanyId();
    $companyDetails  = null;

    if ($activeCompanyId) {
        $stmtComp = $pdo->prepare("SELECT * FROM companies WHERE id = :id AND active = 1 LIMIT 1");
        $stmtComp->execute(['id' => $activeCompanyId]);
        $companyDetails = $stmtComp->fetch();
    }

    // Load list of companies for super_admins (company switcher dropdown)
    $allCompanies = [];
    if ($user['role'] === 'super_admin') {
        $stmtAll      = $pdo->query("SELECT id, name, legal_name, active FROM companies WHERE active = 1 ORDER BY name ASC");
        $allCompanies = $stmtAll->fetchAll();
    } else {
        $stmtUserComp = $pdo->prepare(
            "SELECT c.id, c.name, c.legal_name
             FROM companies c
             JOIN user_companies uc ON c.id = uc.company_id
             WHERE uc.user_id = :user_id AND c.active = 1"
        );
        $stmtUserComp->execute(['user_id' => $userId]);
        $allCompanies = $stmtUserComp->fetchAll();
    }

    // Fetch active modules for this company
    $enabledModules = [];
    if ($activeCompanyId) {
        $stmtMod = $pdo->prepare("SELECT module_name FROM company_modules WHERE company_id = :cid AND enabled = 1");
        $stmtMod->execute(['cid' => $activeCompanyId]);
        $enabledModules = $stmtMod->fetchAll(PDO::FETCH_COLUMN);
    }

    // Fetch role permissions
    $stmtPerms      = $pdo->prepare("SELECT module_name, can_view, can_edit FROM module_permissions WHERE role = :role");
    $stmtPerms->execute(['role' => $user['role']]);
    $rolePermissions = $stmtPerms->fetchAll();

    echo json_encode([
        'authenticated'   => true,
        'user'            => [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ],
        'active_company_id' => $activeCompanyId,
        'active_company'    => $companyDetails,
        'companies'         => $allCompanies,
        'enabled_modules'   => $enabledModules,
        'permissions'       => $rolePermissions,
        'csrf_token'        => $_SESSION['csrf_token'] ?? null,
    ]);

} catch (Exception $e) {
    // Log internally; never expose SQL or stack trace to the client.
    error_log('[LIMA][session] Error processing session state: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'authenticated' => false,
        'message'       => 'Erro interno ao processar sessão. Por favor, tente novamente.'
    ]);
}
exit();
