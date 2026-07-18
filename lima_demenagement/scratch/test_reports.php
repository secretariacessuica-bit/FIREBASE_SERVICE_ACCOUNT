<?php
header('Content-Type: text/plain; charset=utf-8');
require_once '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';
require_once __DIR__ . '/../modules/reports/model/Report.php';

$pdo = new PDO(
    'mysql:host=' . SECURE_DB_HOST . ';dbname=' . SECURE_DB_NAME . ';charset=utf8mb4',
    SECURE_DB_USER,
    SECURE_DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$companyId = (int)$pdo->query('SELECT id FROM companies LIMIT 1')->fetchColumn();
$report = new Report($pdo);

$filters = [
    'start_date' => '2026-01-01',
    'end_date' => '2026-12-31',
    'currency' => 'CHF',
];

echo "Company: $companyId\n\n";

try {
    $kpis = $report->getKPIs($companyId, $filters);
    echo "KPIs OK: revenue_month=" . $kpis['revenue_month'] . ", total_billed=" . $kpis['total_billed'] . "\n";
} catch (Throwable $e) {
    echo "KPIs FAIL: " . $e->getMessage() . "\n";
}

try {
    $cash = $report->getCashFlow($companyId, 'month', $filters);
    echo "CashFlow OK: rows=" . count($cash) . "\n";
} catch (Throwable $e) {
    echo "CashFlow FAIL: " . $e->getMessage() . "\n";
}

try {
    $payments = $report->getPaymentsReport($companyId, $filters);
    echo "PaymentsReport OK: rows=" . count($payments) . "\n";
} catch (Throwable $e) {
    echo "PaymentsReport FAIL: " . $e->getMessage() . "\n";
}

try {
    $tax = $report->getTaxReport($companyId, $filters);
    echo "TaxReport OK\n";
} catch (Throwable $e) {
    echo "TaxReport FAIL: " . $e->getMessage() . "\n";
}

echo "\nAll report smoke tests finished.\n";
