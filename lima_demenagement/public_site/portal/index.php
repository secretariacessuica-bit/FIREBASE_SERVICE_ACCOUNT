<?php
// LIMA Solutions ERP - Client Portal Dashboard
require_once 'auth.php';

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

// 1. Fetch Client Details
$stmtClient = $pdo->prepare("SELECT * FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
$stmtClient->execute(['id' => $clientId, 'company_id' => $companyId]);
$client = $stmtClient->fetch();

if (!$client) {
    session_unset();
    session_destroy();
    header('Location: /portal/login.php?error=invalid_client');
    exit();
}

// 2. Fetch Active Projects
$stmtProj = $pdo->prepare("SELECT * FROM projects WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL AND status NOT IN ('Completed', 'Cancelled') ORDER BY created_at DESC");
$stmtProj->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$activeProjects = $stmtProj->fetchAll();

// 3. Fetch Summary Metrics
// Outstanding invoices
$stmtInv = $pdo->prepare("SELECT COUNT(*) as cnt, SUM(total) as tot FROM invoices WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL AND status IN ('Sent', 'Partially Paid')");
$stmtInv->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$invStats = $stmtInv->fetch();
$unpaidCount = (int)($invStats['cnt'] ?? 0);
$unpaidTotal = (float)($invStats['tot'] ?? 0.0);

// Pending quotes
$stmtQuotes = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL AND status = 'Sent'");
$stmtQuotes->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$pendingQuotesCount = (int)$stmtQuotes->fetchColumn();

// Unread messages
$stmtMsg = $pdo->prepare("SELECT COUNT(*) FROM client_messages WHERE client_id = :client_id AND company_id = :company_id AND sender_type = 'staff' AND read_at IS NULL");
$stmtMsg->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$unreadMessages = (int)$stmtMsg->fetchColumn();

// Company Info
$stmtComp = $pdo->prepare("SELECT name, main_color FROM companies WHERE id = :id LIMIT 1");
$stmtComp->execute(['id' => $companyId]);
$company = $stmtComp->fetch();
$companyName = $company['name'] ?? 'LIMA Solutions';
$mainColor = $company['main_color'] ?? '#007a87';

// 4. Fetch Marketplace Metrics (Active sales, Active donations, Total interests received)
$stmtMktActiveSales = $pdo->prepare("SELECT COUNT(*) FROM marketplace_items WHERE client_id = :client_id AND company_id = :company_id AND status = 'Approved' AND price IS NOT NULL");
$stmtMktActiveSales->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$activeSalesCount = (int)$stmtMktActiveSales->fetchColumn();

$stmtMktActiveDonations = $pdo->prepare("SELECT COUNT(*) FROM marketplace_items WHERE client_id = :client_id AND company_id = :company_id AND status = 'Approved' AND price IS NULL");
$stmtMktActiveDonations->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$activeDonationsCount = (int)$stmtMktActiveDonations->fetchColumn();

$stmtMktInterests = $pdo->prepare("SELECT COUNT(*) FROM marketplace_interests mi JOIN marketplace_items i ON mi.item_id = i.id WHERE i.client_id = :client_id AND i.company_id = :company_id");
$stmtMktInterests->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$interestsReceivedCount = (int)$stmtMktInterests->fetchColumn();

$stmtMktLeads = $pdo->prepare("SELECT COUNT(*) FROM crm_leads WHERE company_id = :company_id AND utm_source = 'Marketplace'");
$stmtMktLeads->execute(['company_id' => $companyId]);
$mktLeadsCount = (int)$stmtMktLeads->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Client - <?php echo htmlspecialchars($companyName); ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: <?php echo $mainColor; ?>;
            --primary-light: #e6f2f3;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-gray: #e2e8f0;
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar navigation */
        .sidebar {
            width: 260px;
            background-color: #0f172a;
            color: var(--white);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition);
        }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand i {
            font-size: 24px;
            color: var(--primary);
        }

        .sidebar-brand h2 {
            font-size: 18px;
            font-weight: 700;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-grow: 1;
        }

        .sidebar-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .sidebar-item a:hover {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.05);
        }

        .sidebar-item.active a {
            color: var(--white);
            background-color: var(--primary);
        }

        .sidebar-item .badge {
            margin-left: auto;
            background-color: #ef4444;
            color: var(--white);
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 20px;
            font-weight: 700;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
        }

        /* Main Content area */
        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .navbar {
            background-color: var(--white);
            border-bottom: 1px solid var(--border-gray);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 20px;
            font-weight: 700;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-info span {
            font-size: 14px;
            font-weight: 600;
        }

        .user-info small {
            font-size: 11px;
            color: var(--text-light);
        }

        /* Content spacing */
        .content-container {
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        /* Welcome Card */
        .welcome-card {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .welcome-title h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .welcome-title p {
            color: var(--text-light);
            font-size: 14px;
        }

        /* Metrics grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .metric-card {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: var(--transition);
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
        }

        .icon-teal { background-color: #e6f2f3; color: #007a87; }
        .icon-blue { background-color: #eff6ff; color: #3b82f6; }
        .icon-yellow { background-color: #fef3c7; color: #d97706; }
        .icon-red { background-color: #fee2e2; color: #ef4444; }

        .metric-details h3 {
            font-size: 24px;
            font-weight: 700;
        }

        .metric-details p {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Grid section */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 2fr 1fr;
            }
        }

        .panel-card {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-gray);
            padding-bottom: 12px;
        }

        .panel-header h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .panel-header a {
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .panel-header a:hover {
            text-decoration: underline;
        }

        /* Table design */
        .portal-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .portal-table th {
            color: var(--text-light);
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-gray);
            font-size: 12px;
            text-transform: uppercase;
        }

        .portal-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-gray);
        }

        /* Badges status */
        .badge-status {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-planning { background-color: #f1f5f9; color: #475569; }
        .badge-in_progress { background-color: #eff6ff; color: #2563eb; }
        .badge-completed { background-color: #d1fae5; color: #065f46; }
        .badge-cancelled { background-color: #fee2e2; color: #b91c1c; }

        .placeholder-box {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }

        .placeholder-box i {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: 0.3;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 14px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dotted var(--border-gray);
            padding-bottom: 8px;
        }

        .info-item strong {
            color: var(--text-light);
            font-weight: 500;
        }

        .info-item span {
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Left Sidebar Menu -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-cube"></i>
            <h2><?php echo htmlspecialchars($companyName); ?></h2>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item active">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
            </li>
            <li class="sidebar-item">
                <a href="quotes.php"><i class="fa-solid fa-file-signature"></i> Mes Devis</a>
            </li>
            <li class="sidebar-item">
                <a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Mes Factures</a>
            </li>
            <li class="sidebar-item">
                <a href="messages.php">
                    <i class="fa-solid fa-envelope"></i> Messages
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="guide.php"><i class="fa-solid fa-circle-question"></i> Guide d'utilisation</a>
            </li>
            <li class="sidebar-item">
                <a href="marketplace.php"><i class="fa-solid fa-store"></i> Marketplace</a>
            </li>
            <li class="sidebar-item" style="margin-top: auto;">
                <a href="logout.php" style="color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <!-- Right Content Area -->
    <div class="main-wrapper">
        <header class="navbar">
            <h1>Tableau de bord</h1>
            <div class="user-menu">
                <div class="user-avatar">
                    <?php 
                        $words = explode(' ', $client['name']);
                        $initials = '';
                        foreach ($words as $w) {
                            $initials .= strtoupper(substr($w, 0, 1));
                        }
                        echo htmlspecialchars(substr($initials, 0, 2));
                    ?>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($client['name']); ?></span>
                    <small>Portail Client</small>
                </div>
            </div>
        </header>

        <main class="content-container">
            <!-- Welcome Header -->
            <div class="welcome-card">
                <div class="welcome-title">
                    <h2>Ravi de vous revoir, <?php echo htmlspecialchars($_SESSION['client_user_name'] ?? $client['contact_person'] ?? $client['name']); ?> !</h2>
                    <p>Voici l'état actuel de votre dossier et de vos interactions avec notre équipe.</p>
                </div>
                <i class="fa-solid fa-handshake" style="font-size: 48px; color: var(--primary); opacity: 0.15;"></i>
            </div>

            <!-- Metrics grid -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon icon-teal">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <div class="metric-details">
                        <h3><?php echo count($activeProjects); ?></h3>
                        <p>Projets actifs</p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-yellow">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <div class="metric-details">
                        <h3><?php echo $pendingQuotesCount; ?></h3>
                        <p>Devis à valider</p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-red">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <div class="metric-details">
                        <h3><?php echo number_format($unpaidTotal, 2); ?> <span style="font-size: 13px;">CHF</span></h3>
                        <p>Solde restant du</p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-blue">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div class="metric-details">
                        <h3><?php echo $unreadMessages; ?></h3>
                        <p>Nouveaux messages</p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-teal">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div class="metric-details">
                        <h3><?php echo $activeSalesCount; ?></h3>
                        <p>Annonces de vente</p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-yellow">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                    <div class="metric-details">
                        <h3><?php echo $activeDonationsCount; ?></h3>
                        <p>Doações ativas</p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-red">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <div class="metric-details">
                        <h3><?php echo $interestsReceivedCount; ?></h3>
                        <p>Intérêts générés</p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon icon-teal">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="metric-details">
                        <h3><?php echo $mktLeadsCount; ?></h3>
                        <p>Leads Marketplace</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid split -->
            <div class="dashboard-grid">
                <!-- Left panel: Active Projects -->
                <div class="panel-card">
                    <div class="panel-header">
                        <h3>Projets en cours</h3>
                        <i class="fa-solid fa-list-check" style="color: var(--text-light);"></i>
                    </div>

                    <?php if (empty($activeProjects)): ?>
                        <div class="placeholder-box">
                            <i class="fa-solid fa-folder-open"></i>
                            <p>Aucun projet actif pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="portal-table">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Nom du projet</th>
                                        <th>Date de début</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeProjects as $p): ?>
                                        <tr>
                                            <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($p['project_code']); ?></strong></td>
                                            <td>
                                                <a href="tracking.php?project_id=<?php echo $p['id']; ?>" style="color: var(--text-dark); font-weight: 600; text-decoration: none;">
                                                    <?php echo htmlspecialchars($p['name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo $p['start_date'] ? date('d.m.Y', strtotime($p['start_date'])) : '-'; ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                                    <span class="badge-status badge-<?php echo strtolower($p['status']); ?>">
                                                        <?php echo htmlspecialchars($p['status']); ?>
                                                    </span>
                                                    <a href="tracking.php?project_id=<?php echo $p['id']; ?>" class="badge-status" style="background-color: var(--primary); color: white; text-decoration: none;">
                                                        <i class="fa-solid fa-map-location-dot"></i> Suivre
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right panel: Customer coordinates -->
                <div class="panel-card">
                    <div class="panel-header">
                        <h3>Fiche client</h3>
                        <i class="fa-solid fa-circle-info" style="color: var(--text-light);"></i>
                    </div>
                    <div class="info-list">
                        <div class="info-item">
                            <strong>Code Client :</strong>
                            <span><?php echo htmlspecialchars($client['customer_code']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Nom/Raison Sociale :</strong>
                            <span><?php echo htmlspecialchars($client['name']); ?></span>
                        </div>
                        <?php if (!empty($client['email'])): ?>
                            <div class="info-item">
                                <strong>E-mail :</strong>
                                <span style="font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($client['email']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($client['phone'])): ?>
                            <div class="info-item">
                                <strong>Téléphone :</strong>
                                <span><?php echo htmlspecialchars($client['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($client['mobile'])): ?>
                            <div class="info-item">
                                <strong>Mobile :</strong>
                                <span><?php echo htmlspecialchars($client['mobile']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="info-item" style="border-bottom: none; flex-direction: column; gap: 4px;">
                            <strong>Adresse :</strong>
                            <span style="font-weight: 500; font-size: 13px; line-height: 1.4;">
                                <?php echo htmlspecialchars($client['address']); ?><br>
                                <?php echo htmlspecialchars($client['postal_code']); ?> <?php echo htmlspecialchars($client['city']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
