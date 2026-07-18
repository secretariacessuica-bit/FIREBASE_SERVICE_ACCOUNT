<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');
$privateConfigPath = __DIR__ . '/../../../private_lima/config.php';
if (!file_exists($privateConfigPath)) {
    $privateConfigPath = __DIR__ . '/../../../private/config.php';
}
require_once $privateConfigPath;

try {
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    
    // 1. Check timesheets table columns
    echo "=== TIMESHEETS COLUMNS ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM timesheets");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo $col['Field'] . " | " . $col['Type'] . " | Null:" . $col['Null'] . " | Default:" . $col['Default'] . "\n";
    }
    
    echo "\n=== TEST INSERT ===\n";
    require_once '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/modules/timesheets/model/Timesheet.php';
    $model = new Timesheet($pdo);
    
    // Get first existing project ID
    $prj = $pdo->query("SELECT id FROM projects WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
    echo "Project ID: $prj\n";
    
    $data = [
        'project_id' => $prj,
        'task_id' => null,
        'work_date' => '2026-06-17',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'hours' => '',
        'billable' => '1',
        'hourly_rate' => '0',
        'description' => 'Test',
        'user_id' => '1'
    ];
    
    $id = $model->create($data, 1, 1);
    echo "OK: Timesheet ID $id";
} catch (Exception $e) {
    echo "ERRO: [" . $e->getCode() . "] " . $e->getMessage();
}
