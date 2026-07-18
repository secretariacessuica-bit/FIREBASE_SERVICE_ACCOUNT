<?php
// LIMA Solutions ERP - Process Uncontacted Leads Automation Script
// Can be executed via CLI or manual administrative trigger.
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
require_once __DIR__ . '/../helpers/EmailHelper.php';
require_once __DIR__ . '/timeline_helper.php';
require_once __DIR__ . '/../modules/crm/model/Lead.php';

// Secure: check if CLI or logged in admin
$isCli = (php_sapi_name() === 'cli');
$role = $_SESSION['user_role'] ?? '';
if (!$isCli && !in_array($role, ['admin', 'super_admin'])) {
    http_response_code(403);
    echo "Access denied. Admin role or CLI execution required.\n";
    exit();
}

try {
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected successfully. Identifying leads with no contact for 7 days...\n";

    // Query leads that are not Won/Lost, and either:
    // - last_contacted_at is NULL and created_at is 7+ days ago
    // - last_contacted_at is 7+ days ago
    $sql = "SELECT * FROM crm_leads 
            WHERE status NOT IN ('Won', 'Lost')
              AND (
                (last_contacted_at IS NULL AND created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY))
                OR (last_contacted_at IS NOT NULL AND last_contacted_at <= DATE_SUB(NOW(), INTERVAL 7 DAY))
              )";
    
    $stmt = $pdo->query($sql);
    $leads = $stmt->fetchAll();
    
    $processedCount = 0;
    $reminderCount = 0;
    
    $leadModel = new Lead($pdo);

    foreach ($leads as $lead) {
        $processedCount++;
        $leadId = $lead['id'];
        
        // Check if reminder was already sent in the last 7 days to avoid duplicate alert spam
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM entity_timeline 
            WHERE entity = 'crm_leads' 
              AND entity_id = :lead_id 
              AND action = 'uncontacted_reminder_sent' 
              AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmtCheck->execute(['lead_id' => $leadId]);
        $alreadySent = intval($stmtCheck->fetchColumn()) > 0;
        
        if ($alreadySent) {
            echo "- Lead #{$leadId} ({$lead['name']}): Reminder already sent recently. Skipping.\n";
            continue;
        }

        // Fetch company details
        $stmtCompany = $pdo->prepare("SELECT email FROM companies WHERE id = :cid LIMIT 1");
        $stmtCompany->execute(['cid' => $lead['company_id']]);
        $companyEmail = $stmtCompany->fetchColumn() ?: 'info@limasolutions.ch';
        
        // Determine category and score
        $score = intval($lead['lead_score']);
        $category = $leadModel->getScoreCategory($score);
        
        // Send email alert
        $lastUpdated = !empty($lead['last_contacted_at']) ? $lead['last_contacted_at'] : $lead['created_at'];
        EmailHelper::sendTemplateEmail($lead['company_id'], $companyEmail, 'lead_uncontacted_reminder', [
            'lead_id' => $leadId,
            'name' => $lead['name'],
            'email' => $lead['email'],
            'last_updated' => $lastUpdated,
            'lead_score' => $score,
            'lead_category' => $category
        ], $pdo);
        
        // Log to timeline to prevent spamming it again for another 7 days
        logEntityEvent($lead['company_id'], 'crm', 'crm_leads', $leadId, 'uncontacted_reminder_sent', 1, "Rappel automatique envoyé (Pas de contact depuis 7+ jours).", $pdo);
        
        echo "- Lead #{$leadId} ({$lead['name']}): Reminder email sent successfully to {$companyEmail}.\n";
        $reminderCount++;
    }

    echo "\nProcessing finished.\nTotal leads analyzed: {$processedCount}\nReminders dispatched: {$reminderCount}\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
