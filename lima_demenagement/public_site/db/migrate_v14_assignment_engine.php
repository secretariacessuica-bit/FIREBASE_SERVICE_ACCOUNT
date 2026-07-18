<?php
// LIMA Solutions ERP - Migrate V14 - Smart Project Assignment Engine
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: application/json; charset=utf-8');

// Allow only admin or super_admin to execute if session exists
$role = $_SESSION['user_role'] ?? '';
if (!empty($role) && !in_array($role, ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès interdit.']);
    exit();
}

try {
    $queries = [
        "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `address` VARCHAR(255) NULL",
        "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `hourly_cost` DECIMAL(10,2) DEFAULT 0.00"
    ];

    $log = [];
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            $log[] = "Success: $query";
        } catch (PDOException $e) {
            $log[] = "Skipped/Error: " . $e->getMessage() . " on query: $query";
        }
    }

    // Seed mock staff users ONLY if environment is local
    $isLocal = (
        getenv('APP_ENV') === 'local' || 
        (defined('APP_ENV') && APP_ENV === 'local') || 
        ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || 
        ($_SERVER['SERVER_NAME'] ?? '') === 'localhost'
    );

    if ($isLocal) {
        $log[] = "Environment is local. Seeding mock staff users...";
        
        $mockUsers = [
            ['name' => 'Michel Roux', 'email' => 'michel.roux@limasolutions.ch', 'role' => 'staff', 'address' => 'Rue de la Gare 5, 1003 Lausanne', 'cost' => 30.00],
            ['name' => 'Pierre Blanc', 'email' => 'pierre.blanc@limasolutions.ch', 'role' => 'staff', 'address' => 'Avenue du Léman 24, 1005 Lausanne', 'cost' => 28.00],
            ['name' => 'André Moret', 'email' => 'andre.moret@limasolutions.ch', 'role' => 'staff', 'address' => 'Avenue de Cour 60, 1007 Lausanne', 'cost' => 32.00],
            ['name' => 'Marc Gétaz', 'email' => 'marc.getaz@limasolutions.ch', 'role' => 'staff', 'address' => 'Route de Chailly 10, 1012 Lausanne', 'cost' => 29.00],
            ['name' => 'Jean Dupont', 'email' => 'jean.dupont@limasolutions.ch', 'role' => 'staff', 'address' => 'Route de Meyrin 12, 1202 Genève', 'cost' => 35.00],
            ['name' => 'Luc Rochat', 'email' => 'luc.rochat@limasolutions.ch', 'role' => 'staff', 'address' => 'Rue du Simplon 15, 1800 Vevey', 'cost' => 31.00]
        ];

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmtInsert = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, active, address, hourly_cost) 
            VALUES (:name, :email, :hash, :role, 1, :address, :cost)");

        foreach ($mockUsers as $mu) {
            $stmtCheck->execute(['email' => $mu['email']]);
            if (intval($stmtCheck->fetchColumn()) == 0) {
                $stmtInsert->execute([
                    'name' => $mu['name'],
                    'email' => $mu['email'],
                    'hash' => password_hash('lima2026', PASSWORD_DEFAULT),
                    'role' => $mu['role'],
                    'address' => $mu['address'],
                    'cost' => $mu['cost']
                ]);
                $log[] = "Seeded user: {$mu['name']}";
            } else {
                $log[] = "User already exists: {$mu['name']}";
            }
        }
    } else {
        $log[] = "Environment is production/staging. Skipped seeding mock users.";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migration V14 CRM Assignment Engine completed.',
        'log' => $log
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Migration V14 failed: ' . $e->getMessage()]);
}
