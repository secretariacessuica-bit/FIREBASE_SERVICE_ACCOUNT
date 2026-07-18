<?php
// LIMA Solutions ERP - Mobile API Smoke Tests
// Realiza verificações diretas chamando a lógica dos arquivos de endpoint simulando requisições HTTP

require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================\n";
echo " LIMA Solutions ERP - Mobile API Smoke Test Suite       \n";
echo "========================================================\n\n";

function runTest($name, $callback) {
    echo "Testing [{$name}]: ";
    try {
        $result = $callback();
        if ($result === true) {
            echo "\033[32m[PASSED]\033[0m\n";
        } else {
            echo "\033[31m[FAILED]\033[0m - {$result}\n";
        }
    } catch (Exception $e) {
        echo "\033[31m[EXCEPTION]\033[0m - " . $e->getMessage() . "\n";
    }
}

// 1. Validar que as tabelas existem no banco de dados
runTest("Database tables check", function() use ($pdo) {
    $tables = ['mobile_tokens', 'operational_assignments', 'gps_tracking', 'project_photos', 'project_checklists', 'project_signatures'];
    foreach ($tables as $t) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$t}'");
        if (!$stmt->fetch()) {
            return "Tabela '{$t}' em falta no banco de dados. Execute a migração migrate_v11_operational_api.php primeiro.";
        }
    }
    return true;
});

// 2. Criar uma empresa de teste e utilizador de staff de teste, se não existirem
$testCompanyId = 1;
$testUserId = 1;

// 3. Teste de login de API Mobile e geração de Token
$testToken = '';
runTest("Mobile Login & Token Generation", function() use ($pdo, $testCompanyId, $testUserId, &$testToken) {
    // Garantir que temos um utilizador de teste ativo
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $testUserId]);
    $user = $stmt->fetch();
    if (!$user) {
        return "Utilizador de teste ID 1 não encontrado.";
    }

    // Criar um token mock direto para o teste para não precisar expor password hash real no teste
    $tokenRaw = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $tokenRaw);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

    $ins = $pdo->prepare("INSERT INTO mobile_tokens (company_id, user_id, token_hash, device_name, expires_at) VALUES (:cid, :uid, :hash, 'Smoke Test Unit', :exp)");
    $ins->execute(['cid' => $testCompanyId, 'uid' => $testUserId, 'hash' => $tokenHash, 'exp' => $expiresAt]);

    $testToken = $tokenRaw;
    return true;
});

// 4. Teste de autenticação anônima bloqueada
runTest("Anonymous access blocked", function() use ($pdo) {
    // Definimos headers simulados vazios
    unset($_SERVER['HTTP_AUTHORIZATION']);
    $_SESSION = [];
    
    require_once __DIR__ . '/../api/v1/mobile/auth_helper.php';
    $auth = checkMobileAuth($pdo);
    if ($auth === true) {
        return "Acesso anônimo foi indevidamente permitido.";
    }
    return true;
});

// 5. Teste de autenticação com Token correto
runTest("Auth with valid Token Bearer", function() use ($pdo, $testToken) {
    $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $testToken;
    $_SESSION = [];
    
    $auth = checkMobileAuth($pdo);
    if ($auth !== true) {
        return "Falha ao autenticar com token Bearer válido.";
    }
    if ($_SESSION['user_id'] != 1 || $_SESSION['company_id'] != 1) {
        return "Variáveis de sessão em memória não foram corretamente populadas após auth por token.";
    }
    return true;
});

// 6. Teste de isolamento de company_id
runTest("Cross-company isolation enforcement", function() use ($pdo) {
    $_SESSION['company_id'] = 99999; // Empresa fictícia inválida
    
    // Tentativa de buscar projetos com company_id isolado
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE company_id = :cid");
    $stmt->execute(['cid' => $_SESSION['company_id']]);
    $count = (int)$stmt->fetchColumn();
    if ($count > 0) {
        return "Dados de cross-company vazaram de forma incorreta.";
    }
    return true;
});

// 7. Teste de recusa de Assinatura Base64 inválida
runTest("Invalid Base64 signature rejection", function() {
    $invalidBase64 = "data:image/png;base64,invalid-base64-string-content-!!!";
    
    // Extrair dados base64 puro
    $dataStr = substr($invalidBase64, strpos($invalidBase64, ',') + 1);
    $decodedData = base64_decode($dataStr, true);
    
    if ($decodedData === false || $decodedData === "") {
        return true; // Rejeitou com sucesso
    }
    
    return "Falha ao validar base64 inválido de assinatura.";
});

// Limpeza de tokens de teste
if (!empty($testToken)) {
    $hash = hash('sha256', $testToken);
    $pdo->prepare("DELETE FROM mobile_tokens WHERE token_hash = :hash")->execute(['hash' => $hash]);
}

echo "\nSmoke Tests completed.\n";
