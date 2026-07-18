<?php
// LIMA Solutions ERP - Executive Dashboard BI Report View
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
    <title>Tableau de Bord BI - LIMA Solutions</title>
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
            <li class="sidebar-item active">
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
            <li class="sidebar-item">
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
                        <h2 style="font-size:22px; font-weight:700; color:var(--primary-teal-dark);">Tableau de Bord BI Exécutif</h2>
                        <p style="color:var(--text-light); font-size:13px; margin-top:3px;">Indicateurs consolidés de performance, finances et ventes.</p>
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
                        <label for="filter-client">Client</label>
                        <select id="filter-client" class="filter-select">
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-currency">Devise</label>
                        <select id="filter-currency" class="filter-select">
                            <option value="">-- Toutes --</option>
                            <option value="CHF">CHF</option>
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button id="btn-apply-filters" class="btn-crm btn-crm-primary" style="padding:10px 18px;"><i class="fa-solid fa-filter"></i> Filtrer</button>
                        <button id="btn-reset-filters" class="btn-header" style="padding:10px 14px;"><i class="fa-solid fa-rotate"></i></button>
                    </div>
                </div>
            </div>

            <!-- KPIs Executive Grid -->
            <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; color: var(--text-light); margin-bottom: 12px; letter-spacing: 0.5px;">Indicateurs Clés de Performance (KPIs)</h3>
            <div class="kpi-grid">
                <!-- Revenue Today -->
                <div class="report-card kpi-card revenue">
                    <span class="kpi-label">Revenu aujourd'hui</span>
                    <div class="kpi-value" id="val-revenue-today">CHF 0.00</div>
                    <span style="font-size:11px; color:#10b981;"><i class="fa-solid fa-calendar-day"></i> Reçu aujourd'hui</span>
                </div>
                <!-- Revenue Month -->
                <div class="report-card kpi-card revenue">
                    <span class="kpi-label">Revenu ce mois</span>
                    <div class="kpi-value" id="val-revenue-month">CHF 0.00</div>
                    <span style="font-size:11px; color:#007a87;"><i class="fa-solid fa-calendar-days"></i> Reçu ce mois</span>
                </div>
                <!-- Revenue Year -->
                <div class="report-card kpi-card revenue">
                    <span class="kpi-label">Revenu cette année</span>
                    <div class="kpi-value" id="val-revenue-year">CHF 0.00</div>
                    <span style="font-size:11px; color:#8b5cf6;"><i class="fa-solid fa-calendar"></i> Reçu cette année</span>
                </div>
                <!-- Total Billed -->
                <div class="report-card kpi-card billed">
                    <span class="kpi-label">Total facturé</span>
                    <div class="kpi-value" id="val-billed">CHF 0.00</div>
                    <span style="font-size:11px; color:var(--text-light);">Factures actives</span>
                </div>
            </div>

            <div class="kpi-grid">
                <!-- Total Received -->
                <div class="report-card kpi-card billed">
                    <span class="kpi-label">Total encaissé</span>
                    <div class="kpi-value" id="val-received">CHF 0.00</div>
                    <span style="font-size:11px; color:#10b981;"><i class="fa-solid fa-circle-check"></i> Règlements effectués</span>
                </div>
                <!-- Pending Balance -->
                <div class="report-card kpi-card receivables">
                    <span class="kpi-label">Saldo pendente</span>
                    <div class="kpi-value" id="val-pending">CHF 0.00</div>
                    <span style="font-size:11px; color:#f59e0b;"><i class="fa-solid fa-clock"></i> Comptes à recevoir</span>
                </div>
                <!-- Total Reversed -->
                <div class="report-card kpi-card receivables">
                    <span class="kpi-label">Total extourné</span>
                    <div class="kpi-value" id="val-reversed">CHF 0.00</div>
                    <span style="font-size:11px; color:#ef4444;"><i class="fa-solid fa-rotate-left"></i> Règlements annulés</span>
                </div>
                <!-- Ticket Average -->
                <div class="report-card kpi-card quotes">
                    <span class="kpi-label">Ticket moyen</span>
                    <div class="kpi-value" id="val-ticket">CHF 0.00</div>
                    <span style="font-size:11px; color:var(--text-light);">Moyenne par facture</span>
                </div>
            </div>

            <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                <!-- Clients -->
                <div class="report-card kpi-card" style="padding:16px;">
                    <span class="kpi-label">Clients Actifs</span>
                    <div class="kpi-value" id="val-clients" style="font-size:22px;">0</div>
                    <span style="font-size:10px; color:var(--text-light);"><i class="fa-solid fa-users"></i> Dans la base</span>
                </div>
                <!-- New Clients -->
                <div class="report-card kpi-card" style="padding:16px;">
                    <span class="kpi-label">Nouveaux Clients</span>
                    <div class="kpi-value" id="val-new-clients" style="font-size:22px;">0</div>
                    <span style="font-size:10px; color:#10b981;"><i class="fa-solid fa-arrow-trend-up"></i> Créés ce mois</span>
                </div>
                <!-- Invoices count -->
                <div class="report-card kpi-card" style="padding:16px;">
                    <span class="kpi-label">Factures émises</span>
                    <div class="kpi-value" id="val-invoices" style="font-size:22px;">0</div>
                    <span style="font-size:10px; color:var(--text-light);"><i class="fa-solid fa-file-invoice"></i> Hors brouillon</span>
                </div>
                <!-- Quotes count -->
                <div class="report-card kpi-card" style="padding:16px;">
                    <span class="kpi-label">Devis émis</span>
                    <div class="kpi-value" id="val-quotes" style="font-size:22px;">0</div>
                    <span style="font-size:10px; color:var(--text-light);"><i class="fa-solid fa-calculator"></i> Hors brouillon</span>
                </div>
                <!-- Conversion Rate -->
                <div class="report-card kpi-card" style="padding:16px;">
                    <span class="kpi-label">Taux Conversion</span>
                    <div class="kpi-value" id="val-conversion" style="font-size:22px;">0.00 %</div>
                    <span style="font-size:10px; color:#10b981;"><i class="fa-solid fa-percent"></i> Devis convertis</span>
                </div>
                <!-- LTV Average -->
                <div class="report-card kpi-card" style="padding:16px;">
                    <span class="kpi-label">LTV Moyen</span>
                    <div class="kpi-value" id="val-ltv" style="font-size:22px;">CHF 0.00</div>
                    <span style="font-size:10px; color:var(--text-light);">Lifetime Value moyen</span>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid" style="margin-top: 25px;">
                <!-- Chart 1: Devis breakdown -->
                <div class="report-card">
                    <h4 class="chart-card-title">
                        <i class="fa-solid fa-chart-pie" style="color:var(--primary-teal);"></i>
                        Répartition et Taux d'Acceptation des Devis (Quotes)
                    </h4>
                    <div class="chart-wrapper">
                        <canvas id="chart-quotes"></canvas>
                    </div>
                    <div style="display:flex; justify-content:space-around; align-items:center; margin-top:20px; font-size:12px; border-top:1px solid var(--border-gray); padding-top:15px;">
                        <div style="text-align:center;">
                            <span style="color:var(--text-light); font-weight:500;">Taux d'acceptation</span>
                            <div id="q-acceptance-rate" style="font-size:16px; font-weight:700; color:#10b981; margin-top:5px;">0.00 %</div>
                        </div>
                        <div style="text-align:center;">
                            <span style="color:var(--text-light); font-weight:500;">Taux de conversion</span>
                            <div id="q-conversion-rate" style="font-size:16px; font-weight:700; color:#007a87; margin-top:5px;">0.00 %</div>
                        </div>
                        <div style="text-align:center;">
                            <span style="color:var(--text-light); font-weight:500;">Délai facturation</span>
                            <div id="q-conversion-time" style="font-size:16px; font-weight:700; color:#f59e0b; margin-top:5px;">0 jours</div>
                        </div>
                    </div>
                </div>

                <!-- Chart 2: Payments breakdown -->
                <div class="report-card">
                    <h4 class="chart-card-title">
                        <i class="fa-solid fa-credit-card" style="color:var(--primary-teal);"></i>
                        Répartition des Règlements par Méthode de Paiement
                    </h4>
                    <div class="chart-wrapper">
                        <canvas id="chart-payments"></canvas>
                    </div>
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
