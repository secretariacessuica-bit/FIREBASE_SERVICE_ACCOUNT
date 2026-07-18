<?php
header('Content-Type: text/plain; charset=utf-8');
require_once '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';

$pdo = new PDO(
    'mysql:host=' . SECURE_DB_HOST . ';dbname=' . SECURE_DB_NAME . ';charset=utf8mb4',
    SECURE_DB_USER,
    SECURE_DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

foreach (['payments', 'invoices', 'quotes'] as $table) {
    $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
    echo "$table: " . implode(', ', $cols) . "\n\n";
}
