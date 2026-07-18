<?php
// Scratch script to apply approved database indexes
require_once __DIR__ . '/../public_site/api/v1/config.php';

echo "Applying indexes...\n";
$queries = [
    "CREATE INDEX idx_leads_dashboard ON crm_leads(company_id, status, created_at)",
    "CREATE INDEX idx_projects_kanban ON projects(company_id, start_date, status)",
    "CREATE INDEX idx_timesheets_mobile ON timesheets(company_id, user_id, status, work_date)"
];

foreach ($queries as $q) {
    try {
        $pdo->exec($q);
        echo "Successfully executed: $q\n";
    } catch (PDOException $e) {
        echo "Error or already exists: " . $e->getMessage() . "\n";
    }
}
echo "Done!\n";
