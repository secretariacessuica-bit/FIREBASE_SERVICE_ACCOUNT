<?php
// LIMA Solutions ERP - Direct Schema/Table Inserter for Smart Project Assignment Engine
header('Content-Type: text/plain; charset=utf-8');

$configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__, 2) . '/private/config.php';
}

if (!file_exists($configPath)) {
    echo "Config file not found at: " . $configPath . "\n";
    exit();
}

require_once $configPath;

try {
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected successfully to: " . SECURE_DB_NAME . "\n";

    $queries = [
        "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `address` VARCHAR(255) NULL",
        "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `hourly_cost` DECIMAL(10,2) DEFAULT 0.00"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "Success: $query\n";
        } catch (PDOException $e) {
            echo "Skipped/Error: " . $e->getMessage() . " on query: $query\n";
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
        echo "Environment is local. Seeding mock staff users...\n";
        
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
                echo "Seeded user: {$mu['name']}\n";
            } else {
                echo "User already exists: {$mu['name']}\n";
            }
        }
    } else {
        echo "Environment is production/staging. Skipped seeding mock users.\n";
    }

    echo "SUCCESS: Smart Project Assignment Engine columns migration completed!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
