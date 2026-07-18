<?php
require_once __DIR__ . '/../public_site/api/v1/config.php';
try {
    $stmt = $pdo->query("SELECT DATABASE(), VERSION()");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Connected successfully to: " . json_encode($res) . "\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in DB: " . count($tables) . "\n";
    foreach ($tables as $t) {
        echo " - $t\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
