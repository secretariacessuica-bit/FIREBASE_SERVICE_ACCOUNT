<?php
// LIMA Solutions Platform - Database Seeder
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Check if any user already exists to block execution
    $stmtCheck = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmtCheck->fetchColumn();

    if ($userCount > 0) {
        http_response_code(403);
        echo "Acesso negado: O banco de dados já possui usuários cadastrados. O arquivo de sementeira não pode ser executado.\n";
        
        // As a security precaution, delete this file anyway if users exist
        @unlink(__FILE__);
        echo "Este arquivo de sementeira (seed.php) foi autodeletado por motivos de segurança.\n";
        exit();
    }

    $email = 'admin@limasolutions.ch';
    $password = 'lima2026';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, 'admin')");
    $stmt->execute([
        'name' => 'Administrador LIMA',
        'email' => $email,
        'hash' => $hash
    ]);

    echo "Sucesso!\n";
    echo "Banco de dados inicializado.\n";
    echo "Administrador padrão criado com sucesso:\n";
    echo "Email: " . $email . "\n";
    echo "Senha: " . $password . "\n\n";
    echo "ATENÇÃO: Este arquivo de sementeira (seed.php) acaba de se autodeletar por motivos de segurança.\n";

    // Self-delete
    @unlink(__FILE__);

} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao semear o banco: " . $e->getMessage();
}
