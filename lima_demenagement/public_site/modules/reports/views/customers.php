<?php
// LIMA Solutions ERP - Customer Analytics View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce module access
enforceModuleAccess('reports', $userRole, $companyId, 'view', $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyse Clients - LIMA Solutions</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for clean icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main styles styling from dashboard -->
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="../assets/reports.css">
</head>
<body>

    <!-- Left Sidebar Menu -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-layer-group"></i>
            <h2>LIMA ERP</h2>
        </div>
        <ul class="sidebar-menu" id="sidebar-menu">
            <li class="sidebar-menu-header" style="padding:10px 16px; font-size:11px; color:rgba(255,255,255,0.4); text-transform:uppercase; font-weight:700; letter-spacing:0.5px;">Navigation</li>
            <li class="sidebar-item">
                <a href="/admin/index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </li>
            <li class="sidebar-item">
                <a href="../../crm/views/list.php"><i class="fa-solid fa-users"></i> Clientes</a>
            </li>
            <li class="sidebar-item">
                <a href="../../quotes/views/list.php"><i class="fa-solid fa-calculator"></i> Orçamentos</a>
            </li>
            <li class="sidebar-item">
                <a href="../../invoices/views/list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Factures</a>
            </li>
            <li class="sidebar-item">
                <a href="../../payments/views/list.php"><i class="fa-solid fa-wallet"></i> Pagamentos</a>
            </li>
            
            <li class="sidebar-menu-header" style="padding:15px 16px 10px 16px; font-size:11px; color:rgba(255,255,255,0.4); text-transform:uppercase; font-weight:700; letter-spacing:0.5px;">Rapports & BI</li>
            <li class="sidebar-item">
                <a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> T.B. Exécutif</a>
            </li>
            <li class="sidebar-item">
                <a href="cashflow.php"><i class="fa-solid fa-chart-simple"></i> Flux de Trésorerie</a>
            </li>
            <li class="sidebar-item">
                <a href="financial.php"><i class="fa-solid fa-file-invoice"></i> Comptes Clients</a>
            </li>
            <li class="sidebar-item">
                <a href="tax.php"><i class="fa-solid fa-landmark"></i> TVA & Déclaration</a>
            </li>
            <li class="sidebar-item active">
                <a href="customers.php"><i class="fa-solid fa-users-viewfinder"></i> Analyse Clients</a>
            </li>
            <li class="sidebar-item">
                <a href="operational.php"><i class="fa-solid fa-business-time"></i> Projets & Temps</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <!-- Right Content Panel -->
    <div class="main-wrapper">
        <!-- Top Navigation Bar -->
        <header class="navbar">
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <a href="/admin/index.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <!-- Main Container -->
        <main class="reports-container" style="padding: 30px; max-width: 1200px; width:100%; margin: 0 auto;">
            
            <!-- Filter Header -->
            <div class="report-header-section">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
                    <div>
                        <h2 style="font-size:22px; font-weight:700; color:var(--primary-teal-dark);">Analyse et Récurence des Clients</h2>
                        <p style="color:var(--text-light); font-size:13px; margin-top:3px;">Consultez les clients à forte valeur (LTV) et les indicateurs opérationnels.</p>
                    </div>
                    <!-- Export Actions -->
                    <div style="display:flex; gap:10px;">
                        <button id="export-csv" class="btn-export"><i class="fa-solid fa-file-csv"></i> CSV</button>
                        <button id="export-xlsx" class="btn-export btn-export-xlsx"><i class="fa-solid fa-file-excel"></i> Excel</button>
                        <button id="export-pdf" class="btn-export btn-export-pdf"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filter-start-date">Date de début</label>
                        <input type="date" id="filter-start-date" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label for="filter-end-date">Date de fin</label>
                        <input type="date" id="filter-end-date" class="filter-input">
                    </div>
                    <div class="filter-group">
                        <label for="filter-sort">Trier par</label>
                        <select id="filter-sort" class="filter-select">
                            <option value="ltv" selected>Valeur de cycle de vie (LTV / Encaissé)</option>
                            <option value="billed">Total Facturé</option>
                            <option value="invoices">Volume de factures</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button id="btn-apply-filters" class="btn-crm btn-crm-primary" style="padding:10px 18px;"><i class="fa-solid fa-filter"></i> Filtrer</button>
                        <button id="btn-reset-filters" class="btn-header" style="padding:10px 14px;"><i class="fa-solid fa-rotate"></i></button>
                    </div>
                </div>
            </div>

            <!-- Charts and List Layout -->
            <div class="charts-grid" style="grid-template-columns: 1fr; margin-bottom: 25px;">
                <div class="report-card">
                    <h4 class="chart-card-title">
                        <i class="fa-solid fa-chart-bar" style="color:var(--primary-teal);"></i>
                        Top Clients (Faturamento vs LTV)
                    </h4>
                    <div class="chart-wrapper" style="height: 280px;">
                        <canvas id="chart-customers"></canvas>
                    </div>
                </div>
            </div>

            <div class="report-card">
                <h4 class="chart-card-title" style="margin-bottom: 15px;">
                    <i class="fa-solid fa-list-ol" style="color:var(--primary-teal);"></i>
                    Classement Comparatif des Clients
                </h4>
                <div class="crm-table-wrapper" style="overflow-x:auto;">
                    <table class="crm-table" style="width:100%; border-collapse:collapse; text-align:left;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border-gray); color:var(--text-light); font-weight:600; font-size:13px;">
                                <th style="padding:12px;">Client</th>
                                <th style="padding:12px; width: 180px;">Total Facturé</th>
                                <th style="padding:12px; width: 180px;">Paiements Reçus (LTV)</th>
                                <th style="padding:12px; width: 140px; text-align:center;">N° Factures</th>
                                <th style="padding:12px; width: 140px; text-align:center;">N° Devis</th>
                                <th style="padding:12px; width: 130px; text-align:center;">Statut</th>
                            </tr>
                        </thead>
                        <tbody id="customers-tbody">
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-light); padding: 40px;">
                                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px; color: var(--primary-teal);"></i><br>
                                    Analyse des clients en cours...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Notification Toast -->
    <div class="toast" id="toast"></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        fetch('../../../api/v1/session.php')
            .then(res => res.json())
            .then(data => {
                if (data.authenticated) {
                    document.getElementById('user-display-name').textContent = data.user.name;
                } else {
                    window.location.href = '../../admin/login.php';
                }
            });
    </script>
    <script src="../assets/reports.js"></script>
</body>
</html>
