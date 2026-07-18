<?php
// LIMA Solutions ERP - Operations Observability Administrative Dashboard
require_once 'auth.php';
require_once 'modules_helper.php';
require_once __DIR__ . '/../helpers/ObservabilityHelper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Secure restriction: only super_admin or admin
if (!in_array($userRole, ['super_admin', 'admin'])) {
    header('Location: index.php');
    exit();
}

// ─── Test Event Generator Hook ────────────────────────────────────────────────
$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_event') {
    $testCat = trim($_POST['category'] ?? 'SMTP_SUCCESS');
    $testSev = trim($_POST['severity'] ?? 'INFO');
    $testMsg = trim($_POST['message'] ?? 'Observability test event');

    // Safe logging without actual service interruption
    ObservabilityHelper::log($testMsg, $testCat, $testSev, [
        'triggered_by' => $_SESSION['user_id'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ], $pdo);

    // If active users triggered, update it
    if ($testCat === 'FAILED_LOGIN') {
        // Just log triggers
    }
    
    header('Location: observability.php?success=test_logged');
    exit();
}

if (isset($_GET['success']) && $_GET['success'] === 'test_logged') {
    $successMsg = "Événement de test enregistré avec succès dans le fichier journal.";
}

// ─── Fetch Database Metrics & Real-time Counts ────────────────────────────────
$metrics = ObservabilityHelper::getTodayMetrics($pdo);

// Update Active Users count dynamically based on the last 24h activity
ObservabilityHelper::updateActiveUsers($pdo);
$metrics = ObservabilityHelper::getTodayMetrics($pdo); // refetch updated

// Active Projects Count
$stmtPrj = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE company_id = :cid AND deleted_at IS NULL AND status IN ('Active', 'Planning', 'On Hold')");
$stmtPrj->execute(['cid' => $companyId]);
$activeProjectsCount = (int)$stmtPrj->fetchColumn();

// Uploads Count (Photos + Signatures)
$stmtPhotos = $pdo->prepare("SELECT COUNT(*) FROM project_photos WHERE company_id = :cid");
$stmtPhotos->execute(['cid' => $companyId]);
$totalPhotos = (int)$stmtPhotos->fetchColumn();

$stmtSigs = $pdo->prepare("SELECT COUNT(*) FROM project_signatures WHERE company_id = :cid");
$stmtSigs->execute(['cid' => $companyId]);
$totalSigs = (int)$stmtSigs->fetchColumn();
$totalUploads = $totalPhotos + $totalSigs;

// Pending Syncs (Timesheets, Checklists, GPS tracking, Photos, Signatures)
$stmtTsPending = $pdo->prepare("SELECT COUNT(*) FROM timesheets WHERE company_id = :cid AND sync_status = 'Pending'");
$stmtTsPending->execute(['cid' => $companyId]);
$tsPending = (int)$stmtTsPending->fetchColumn();

$stmtCheckPending = $pdo->prepare("SELECT COUNT(*) FROM project_checklists WHERE company_id = :cid AND sync_status = 'Pending'");
$stmtCheckPending->execute(['cid' => $companyId]);
$checkPending = (int)$stmtCheckPending->fetchColumn();

$stmtGpsPending = $pdo->prepare("SELECT COUNT(*) FROM gps_tracking WHERE company_id = :cid AND sync_status = 'Pending'");
$stmtGpsPending->execute(['cid' => $companyId]);
$gpsPending = (int)$stmtGpsPending->fetchColumn();

$stmtPhPending = $pdo->prepare("SELECT COUNT(*) FROM project_photos WHERE company_id = :cid AND sync_status = 'Pending'");
$stmtPhPending->execute(['cid' => $companyId]);
$phPending = (int)$stmtPhPending->fetchColumn();

$stmtSigPending = $pdo->prepare("SELECT COUNT(*) FROM project_signatures WHERE company_id = :cid AND sync_status = 'Pending'");
$stmtSigPending->execute(['cid' => $companyId]);
$sigPending = (int)$stmtSigPending->fetchColumn();

$pendingSyncsCount = $tsPending + $checkPending + $gpsPending + $phPending + $sigPending;

// Marketplace Stats
$mktStats = [
    'Pending' => 0,
    'Approved' => 0,
    'Rejected' => 0,
    'Sold' => 0,
    'Donated' => 0
];
try {
    $stmtMkt = $pdo->prepare("SELECT status, COUNT(*) as count FROM marketplace_items WHERE company_id = :cid GROUP BY status");
    $stmtMkt->execute(['cid' => $companyId]);
    $mktRows = $stmtMkt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($mktRows as $status => $count) {
        $mktStats[$status] = (int)$count;
    }
} catch (Exception $e) {
    // Ignore if marketplace tables not populated
}

// Marketplace Leads/Interests Generated
$mktLeadsCount = 0;
try {
    $stmtMktLeads = $pdo->prepare("SELECT COUNT(*) FROM marketplace_interests mi JOIN marketplace_items i ON mi.item_id = i.id WHERE i.company_id = :cid");
    $stmtMktLeads->execute(['cid' => $companyId]);
    $mktLeadsCount = (int)$stmtMktLeads->fetchColumn();
} catch (Exception $e) {
    // Ignore
}

// ─── Alert Threshold Validation ───────────────────────────────────────────────
// >10 critical/error lines / hour
$recentLogsForAlerts = ObservabilityHelper::getRecentLogs(200);
$criticalErrorsLastHour = 0;
$oneHourAgo = time() - 3600;
foreach ($recentLogsForAlerts as $line) {
    // Expected format: [timestamp] [SEVERITY] CATEGORY: Message
    if (preg_match('/^\[([^\]]+)\]\s+\[(CRITICAL|ERROR)\]\s+([A-Z0-9_]+):/i', $line, $matches)) {
        $logTime = strtotime($matches[1]);
        if ($logTime >= $oneHourAgo) {
            $criticalErrorsLastHour++;
        }
    }
}

$alertCriticalErrors = $criticalErrorsLastHour > 10;
$alertSyncFailures = $metrics['mobile_sync_failures'] > 20;
$alertSmtpFailures = $metrics['consecutive_smtp_failures'] > 5;
$alertMarketplacePending = ($mktStats['Pending'] ?? 0) > 50;

$anyActiveAlerts = $alertCriticalErrors || $alertSyncFailures || $alertSmtpFailures || $alertMarketplacePending;

// Recent 50 Logs
$recentLogs = ObservabilityHelper::getRecentLogs(50);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Observabilité - LIMA Solutions</title>
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark-card: #0f172a;
            --border-color: #e2e8f0;
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-headings: 'Outfit', sans-serif;
        }

        body {
            font-family: var(--font-main);
            background-color: #f8fafc;
            color: #1e293b;
        }

        .header-section {
            background: linear-gradient(135deg, #1e1b4b 0%, #311042 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-family: var(--font-headings);
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title p {
            margin: 0;
            opacity: 0.8;
            font-size: 14px;
        }

        .alert-banner {
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            animation: pulse-border 2s infinite;
        }

        @keyframes pulse-border {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .alert-banner-danger {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #991b1b;
        }

        .alert-banner-success {
            background-color: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
            animation: none;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .metric-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08);
        }

        .card-header-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .bg-indigo { background-color: #e0e7ff; color: #4338ca; }
        .bg-emerald { background-color: #d1fae5; color: #047857; }
        .bg-rose { background-color: #fee2e2; color: #b91c1c; }
        .bg-amber { background-color: #fef3c7; color: #b45309; }

        .card-title {
            font-family: var(--font-headings);
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 15px 0;
            color: #0f172a;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #f1f5f9;
        }

        .metric-row:last-child {
            border-bottom: none;
        }

        .metric-label {
            font-size: 13px;
            color: #64748b;
        }

        .metric-value {
            font-weight: 600;
            font-size: 15px;
            color: #0f172a;
        }

        .metric-value.badge {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 9999px;
        }

        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }

        .log-section {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .log-viewer-wrapper {
            background-color: #0f172a;
            border-radius: 12px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }

        .log-line {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 6px;
            white-space: pre-wrap;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 6px;
        }

        .log-line:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .log-severity-critical { color: #f87171; font-weight: bold; }
        .log-severity-error { color: #fca5a5; }
        .log-severity-warning { color: #fcd34d; }
        .log-severity-info { color: #38bdf8; }

        .test-event-form {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .btn-submit {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background-color: #4338ca;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: inherit;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div style="padding: 20px; max-width: 1280px; margin: 0 auto;">

        <!-- Header -->
        <div class="header-section">
            <div class="header-title">
                <h1><i class="fa-solid fa-chart-line"></i> Operations Observability</h1>
                <p>Statut opérationnel en temps réel de LIMA Solutions</p>
            </div>
            <div>
                <a href="index.php" class="btn-submit" style="background-color: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); text-decoration: none; color: white;">
                    <i class="fa-solid fa-arrow-left"></i> Retour au Dashboard
                </a>
            </div>
        </div>

        <!-- Success Toast -->
        <?php if ($successMsg): ?>
            <div style="background-color: #ecfdf5; border: 1px solid #d1fae5; color: #065f46; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <!-- Alert Panel -->
        <?php if ($anyActiveAlerts): ?>
            <div class="alert-banner alert-banner-danger">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 24px; margin-top: 2px;"></i>
                <div>
                    <strong style="font-size: 16px; display: block; margin-bottom: 5px;">Alertes Système Actives !</strong>
                    <ul style="margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.5;">
                        <?php if ($alertCriticalErrors): ?>
                            <li><strong>Erreurs Critiques :</strong> &gt;10 erreurs critiques/fatales au cours de la dernière heure (Actuel : <?php echo $criticalErrorsLastHour; ?>).</li>
                        <?php endif; ?>
                        <?php if ($alertSyncFailures): ?>
                            <li><strong>Échecs de Synchronisation :</strong> &gt;20 échecs de synchronisation mobile aujourd'hui (Actuel : <?php echo $metrics['mobile_sync_failures']; ?>).</li>
                        <?php endif; ?>
                        <?php if ($alertSmtpFailures): ?>
                            <li><strong>Échecs SMTP Consécutifs :</strong> &gt;5 échecs consécutifs d'envoi d'e-mails (Actuel : <?php echo $metrics['consecutive_smtp_failures']; ?>).</li>
                        <?php endif; ?>
                        <?php if ($alertMarketplacePending): ?>
                            <li><strong>Marché en attente :</strong> &gt;50 articles en attente de modération (Actuel : <?php echo $mktStats['Pending']; ?>).</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="alert-banner alert-banner-success">
                <i class="fa-solid fa-circle-check" style="font-size: 24px;"></i>
                <div>
                    <strong>Tous os sistemas operam normalmente.</strong> Nenhuma regra de alerta foi violada.
                </div>
            </div>
        <?php endif; ?>

        <!-- Cards Metrics Grid -->
        <div class="grid-container">

            <!-- Card: Application Health -->
            <div class="metric-card">
                <div class="card-header-icon bg-indigo">
                    <i class="fa-solid fa-server"></i>
                </div>
                <h3 class="card-title">Application Health</h3>
                <div class="metric-row">
                    <span class="metric-label">Active Users (24h)</span>
                    <span class="metric-value"><?php echo $metrics['active_users']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Failed Logins (24h)</span>
                    <span class="metric-value <?php echo $metrics['failed_logins'] > 0 ? 'badge badge-warning' : ''; ?>"><?php echo $metrics['failed_logins']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">API Errors / Exceptions</span>
                    <span class="metric-value <?php echo $metrics['api_errors'] > 0 ? 'badge badge-danger' : ''; ?>"><?php echo $metrics['api_errors']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Emails Sent (SMTP Success)</span>
                    <span class="metric-value"><?php echo $metrics['smtp_success']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Email Failures (SMTP Fail)</span>
                    <span class="metric-value <?php echo $metrics['smtp_failures'] > 0 ? 'badge badge-danger' : ''; ?>"><?php echo $metrics['smtp_failures']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Active Projects</span>
                    <span class="metric-value"><?php echo $activeProjectsCount; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Uploads (Photos & Signatures)</span>
                    <span class="metric-value"><?php echo $totalUploads; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Pending Mobile Syncs</span>
                    <span class="metric-value <?php echo $pendingSyncsCount > 0 ? 'badge badge-warning' : ''; ?>"><?php echo $pendingSyncsCount; ?></span>
                </div>
            </div>

            <!-- Card: Mobile Health -->
            <div class="metric-card">
                <div class="card-header-icon bg-emerald">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </div>
                <h3 class="card-title">Mobile Health</h3>
                <div class="metric-row">
                    <span class="metric-label">Total Syncs Today</span>
                    <span class="metric-value"><?php echo ($metrics['mobile_sync_success'] + $metrics['mobile_sync_failures']); ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Failed Syncs</span>
                    <span class="metric-value <?php echo $metrics['mobile_sync_failures'] > 0 ? 'badge badge-danger' : ''; ?>"><?php echo $metrics['mobile_sync_failures']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Average Sync Time</span>
                    <span class="metric-value"><?php echo $metrics['avg_sync_time']; ?>s</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Active Devices</span>
                    <span class="metric-value"><?php echo $metrics['active_devices']; ?></span>
                </div>
            </div>

            <!-- Card: Marketplace Health -->
            <div class="metric-card">
                <div class="card-header-icon bg-amber">
                    <i class="fa-solid fa-store"></i>
                </div>
                <h3 class="card-title">Marketplace Health</h3>
                <div class="metric-row">
                    <span class="metric-label">Pending items</span>
                    <span class="metric-value <?php echo $mktStats['Pending'] > 0 ? 'badge badge-warning' : ''; ?>"><?php echo $mktStats['Pending']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Approved items</span>
                    <span class="metric-value"><?php echo $mktStats['Approved']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Rejected items</span>
                    <span class="metric-value"><?php echo $mktStats['Rejected']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Sold items</span>
                    <span class="metric-value"><?php echo $mktStats['Sold']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Donated items</span>
                    <span class="metric-value"><?php echo $mktStats['Donated']; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Leads Generated</span>
                    <span class="metric-value"><?php echo $mktLeadsCount; ?></span>
                </div>
            </div>

            <!-- Card: Backup & Disaster Recovery -->
            <div class="metric-card">
                <div class="card-header-icon bg-rose">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="card-title">Backup & Disaster Recovery</h3>
                <?php $backupStatus = ObservabilityHelper::getBackupStatus(); ?>
                <div class="metric-row">
                    <span class="metric-label">Last Backup</span>
                    <span class="metric-value"><?php echo htmlspecialchars($backupStatus['last_backup']); ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Backup Status</span>
                    <?php
                    $statusClass = 'badge badge-warning';
                    if (strcasecmp($backupStatus['status'], 'Success') === 0 || strcasecmp($backupStatus['status'], 'OK') === 0) {
                        $statusClass = 'badge badge-success';
                    } elseif (strcasecmp($backupStatus['status'], 'Fail') === 0 || strcasecmp($backupStatus['status'], 'Error') === 0 || strcasecmp($backupStatus['status'], 'Stale') === 0) {
                        $statusClass = 'badge badge-danger';
                    }
                    ?>
                    <span class="metric-value <?php echo $statusClass; ?>" <?php echo $backupStatus['status'] === 'Stale' ? 'style="background-color: #ef4444; color: white; font-weight: bold; padding: 2px 8px; border-radius: 4px;"' : ''; ?>><?php echo htmlspecialchars($backupStatus['status']); ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Backup Age</span>
                    <span class="metric-value"><?php echo htmlspecialchars($backupStatus['age_hours']); ?>h</span>
                </div>
                <div style="margin-top: 15px; padding: 10px; background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 11px; color: #64748b; line-height: 1.3; display: block;">
                        <i class="fa-solid fa-triangle-exclamation" style="color: var(--warning);"></i> 
                        <strong>Note:</strong> <?php echo htmlspecialchars($backupStatus['info'] ?? 'Indicateur de statut temporaire.'); ?>
                    </span>
                </div>
            </div>

        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

            <!-- Logs Viewer -->
            <div class="log-section">
                <h3 class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fa-solid fa-file-invoice"></i> Recent Events (application.log)</span>
                    <span style="font-size: 12px; color: #64748b; font-weight: normal;">Dernières 50 lignes</span>
                </h3>
                <div class="log-viewer-wrapper">
                    <?php if (empty($recentLogs)): ?>
                        <div style="color: #64748b; text-align: center; padding: 20px; font-family: monospace;">Aucun log trouvé.</div>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $log): ?>
                            <?php
                            $class = 'log-severity-info';
                            if (strpos($log, '[CRITICAL]') !== false) {
                                $class = 'log-severity-critical';
                            } elseif (strpos($log, '[ERROR]') !== false) {
                                $class = 'log-severity-error';
                            } elseif (strpos($log, '[WARNING]') !== false) {
                                $class = 'log-severity-warning';
                            }
                            ?>
                            <div class="log-line <?php echo $class; ?>"><?php echo htmlspecialchars($log); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Test Logger Hook Component -->
            <div class="test-event-form">
                <h3 class="card-title"><i class="fa-solid fa-flask"></i> Générateur d'événements de test</h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.4;">
                    Générez des logs de test sécurisés pour valider le déclenchement des alertes sans perturber la production.
                </p>
                <form method="POST" action="observability.php">
                    <input type="hidden" name="action" value="test_event">

                    <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 5px;">Catégorie</label>
                    <select name="category" class="form-control">
                        <option value="SMTP_SUCCESS">SMTP_SUCCESS (Info)</option>
                        <option value="SMTP_FAIL">SMTP_FAIL (Erreur/Warning)</option>
                        <option value="FAILED_LOGIN">FAILED_LOGIN (Warning)</option>
                        <option value="SECURITY">SECURITY (Warning/Critique)</option>
                        <option value="MOBILE_SYNC_SUCCESS">MOBILE_SYNC_SUCCESS (Info)</option>
                        <option value="MOBILE_SYNC_FAIL">MOBILE_SYNC_FAIL (Erreur)</option>
                        <option value="API_ERROR">API_ERROR (Erreur)</option>
                        <option value="EXCEPTION">EXCEPTION (Critique)</option>
                    </select>

                    <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 5px;">Gravité (Severity)</label>
                    <select name="severity" class="form-control">
                        <option value="INFO">INFO</option>
                        <option value="WARNING">WARNING</option>
                        <option value="ERROR">ERROR</option>
                        <option value="CRITICAL">CRITICAL</option>
                    </select>

                    <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 5px;">Message</label>
                    <input type="text" name="message" value="Evénement de test contrôlé" class="form-control" required>

                    <button type="submit" class="btn-submit" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-plus"></i> Générer le Log
                    </button>
                </form>
            </div>

        </div>

    </div>
</body>
</html>
