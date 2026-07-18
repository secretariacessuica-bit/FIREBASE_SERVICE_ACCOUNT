<?php
header('Content-Type: text/plain; charset=utf-8');

echo "--- DIAGNÓSTICO DO AMBIENTE E BASE DE DADOS ---\n\n";

$dir = __DIR__;
$doc_root = $_SERVER['DOCUMENT_ROOT'] ?? 'N/A';
$calculated_config_path = dirname(__DIR__, 2) . '/private_lima/config.php';

echo "[1] DIRETÓRIOS:\n";
echo "__DIR__: " . $dir . "\n";
echo "DOCUMENT_ROOT: " . $doc_root . "\n";
echo "Caminho Calculado para config.php: " . $calculated_config_path . "\n";

$file_exists = file_exists($calculated_config_path);
echo "file_exists(config.php): " . ($file_exists ? "SIM" : "NÃO") . "\n\n";

if ($file_exists) {
    echo "[2] TESTE DE LIGAÇÃO PDO:\n";
    require_once $calculated_config_path;
    
    if (!defined('SECURE_DB_HOST')) {
        echo "Erro: Constantes SECURE_DB_HOST não definidas no config.php.\n";
        exit;
    }
    
    try {
        $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, $options);
        echo "Estado: PDO OK - Ligação à base de dados bem-sucedida!\n\n";
        
        echo "[3] VERIFICAÇÃO DE TABELAS PRINCIPAIS:\n";
        $tables_to_check = ['companies', 'users', 'user_companies', 'company_modules', 'module_permissions'];
        
        foreach ($tables_to_check as $table) {
            try {
                $stmt = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
                if ($stmt !== false) {
                    echo "- Tabela `$table`: EXISTE\n";
                } else {
                    echo "- Tabela `$table`: FALHOU A CONSULTA\n";
                }
            } catch (PDOException $e) {
                // Ensure no password in error messages although PDO usually masks it unless in trace
                echo "- Tabela `$table`: NÃO EXISTE ou ERRO (" . preg_replace('/(password|senha)=[^;]*/i', '$1=***', $e->getMessage()) . ")\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "Estado: ERRO PDO - " . preg_replace('/(password|senha)=[^;]*/i', '$1=***', $e->getMessage()) . "\n";
    }
} else {
    echo "Como o ficheiro config.php não existe, o teste de base de dados não pode ser realizado.\n";
}
