<?php
// LIMA Solutions ERP - Operational Projects & Timesheets BI View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

enforceModuleAccess('reports', $userRole, $companyId, 'view', $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyses Opérationnelles - LIMA Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="../../projects/views/list.php"><i class="fa-solid fa-diagram-project"></i> Projetos</a>
            </li>
            <li class="sidebar-item">
                <a href="../../timesheets/views/list.php"><i class="fa-solid fa-clock"></i> Timesheets</a>
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
            <li class="sidebar-item">
                <a href="customers.php"><i class="fa-solid fa-users-viewfinder"></i> Analyse Clients</a>
            </li>
            <li class="sidebar-item active">
                <a href="operational.php"><i class="fa-solid fa-business-time"></i> Projets & Temps</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="navbar">
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <a href="/admin/index.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <main class="reports-container" style="padding: 30px; max-width: 1400px; width:100%; margin: 0 auto;">
            
            <div class="report-header-section" style="margin-bottom: 30px;">
                <h2 style="font-size:22px; font-weight:700; color:var(--primary-teal-dark);">Rapports Opérationnels (Projets & Timesheets)</h2>
                <p style="color:var(--text-light); font-size:13px; margin-top:3px;">Analysez le temps alloué par projet, l'activité de vos collaborateurs, l'avancement théorique vs réel et la rentabilité financière.</p>
            </div>

            <!-- Date Range Filters -->
            <div class="projects-card" style="padding: 20px; margin-bottom: 30px;">
                <form id="filter-form" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label style="font-size: 12px; font-weight: 600; color: var(--text-light);">Date de début</label>
                        <input type="date" id="filter-start-date" name="start_date" class="form-input" style="width: 100%;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label style="font-size: 12px; font-weight: 600; color: var(--text-light);">Date de fin</label>
                        <input type="date" id="filter-end-date" name="end_date" class="form-input" style="width: 100%;">
                    </div>
                    <button type="submit" class="btn-header" style="background-color: var(--primary-teal); color: white; border: none; padding: 10px 20px;">
                        <i class="fa-solid fa-filter"></i> Filtrer
                    </button>
                </form>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                
                <!-- Hours by Project -->
                <div class="projects-card">
                    <div class="projects-card-header">
                        <span class="projects-card-title"><i class="fa-solid fa-diagram-project"></i> Heures par Projet</span>
                    </div>
                    <div class="crm-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th>Projet</th>
                                    <th>Total Heures</th>
                                    <th>Facturables</th>
                                    <th>Approuvées</th>
                                </tr>
                            </thead>
                            <tbody id="hours-by-project-tbody">
                                <tr><td colspan="4" style="text-align: center; color: var(--text-light); padding: 20px;">Filtrer pour charger...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Hours by Collaborator -->
                <div class="projects-card">
                    <div class="projects-card-header">
                        <span class="projects-card-title"><i class="fa-solid fa-users"></i> Heures par Collaborateur</span>
                    </div>
                    <div class="crm-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th>Collaborateur</th>
                                    <th>Total Heures</th>
                                    <th>Facturables</th>
                                    <th>Approuvées</th>
                                </tr>
                            </thead>
                            <tbody id="hours-by-worker-tbody">
                                <tr><td colspan="4" style="text-align: center; color: var(--text-light); padding: 20px;">Filtrer pour charger...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Estimated vs Realized -->
                <div class="projects-card">
                    <div class="projects-card-header">
                        <span class="projects-card-title"><i class="fa-solid fa-gauge-high"></i> Heures Estimées vs Réalisées</span>
                    </div>
                    <div class="crm-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th>Projet</th>
                                    <th>Budget Heures (Est.)</th>
                                    <th>Réalisé (Total Log)</th>
                                    <th>Écart (Variance)</th>
                                </tr>
                            </thead>
                            <tbody id="estimated-vs-realized-tbody">
                                <tr><td colspan="4" style="text-align: center; color: var(--text-light); padding: 20px;">Chargement...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Project Profitability -->
                <div class="projects-card">
                    <div class="projects-card-header">
                        <span class="projects-card-title"><i class="fa-solid fa-sack-dollar"></i> Rentabilité des Projets</span>
                    </div>
                    <div class="crm-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th>Projet</th>
                                    <th>Budget Financier</th>
                                    <th>Coût Interne (Log)</th>
                                    <th>Bénéfice Net</th>
                                </tr>
                            </thead>
                            <tbody id="project-profitability-tbody">
                                <tr><td colspan="4" style="text-align: center; color: var(--text-light); padding: 20px;">Chargement...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterForm = document.getElementById('filter-form');
            
            // Hydrate display name
            fetch('../../../api/v1/session.php')
                .then(res => res.json())
                .then(data => {
                    if (data.authenticated) {
                        document.getElementById('user-display-name').textContent = data.user.name;
                        
                        // Load initial reports
                        loadReports();
                    } else {
                        window.location.href = '../../../admin/login.php';
                    }
                });

            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadReports();
            });

            function loadReports() {
                const start = document.getElementById('filter-start-date').value;
                const end = document.getElementById('filter-end-date').value;
                let dateParams = `&start_date=${start}&end_date=${end}`;

                // 1. Hours by Project
                fetch(`../../../api/v1/reports/reports.php?action=hours_by_project${dateParams}`)
                    .then(res => res.json())
                    .then(resData => {
                        const tbody = document.getElementById('hours-by-project-tbody');
                        if (resData.success) {
                            const data = resData.data || [];
                            if (data.length === 0) {
                                tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--text-light); padding: 20px;">Aucune donnée.</td></tr>`;
                                return;
                            }
                            tbody.innerHTML = data.map(r => `
                                <tr>
                                    <td><strong>${r.project_code}</strong> - ${r.project_name}</td>
                                    <td>${parseFloat(r.total_hours).toFixed(2)} h</td>
                                    <td>${parseFloat(r.billable_hours).toFixed(2)} h</td>
                                    <td><span style="color: #10b981; font-weight: 600;">${parseFloat(r.approved_hours).toFixed(2)} h</span></td>
                                </tr>
                            `).join('');
                        }
                    });

                // 2. Hours by Worker
                fetch(`../../../api/v1/reports/reports.php?action=hours_by_worker${dateParams}`)
                    .then(res => res.json())
                    .then(resData => {
                        const tbody = document.getElementById('hours-by-worker-tbody');
                        if (resData.success) {
                            const data = resData.data || [];
                            if (data.length === 0) {
                                tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--text-light); padding: 20px;">Aucune donnée.</td></tr>`;
                                return;
                            }
                            tbody.innerHTML = data.map(r => `
                                <tr>
                                    <td><strong>${r.worker_name}</strong></td>
                                    <td>${parseFloat(r.total_hours).toFixed(2)} h</td>
                                    <td>${parseFloat(r.billable_hours).toFixed(2)} h</td>
                                    <td><span style="color: #10b981; font-weight: 600;">${parseFloat(r.approved_hours).toFixed(2)} h</span></td>
                                </tr>
                            `).join('');
                        }
                    });

                // 3. Estimated vs Realized
                fetch(`../../../api/v1/reports/reports.php?action=estimated_vs_realized`)
                    .then(res => res.json())
                    .then(resData => {
                        const tbody = document.getElementById('estimated-vs-realized-tbody');
                        if (resData.success) {
                            const data = resData.data || [];
                            if (data.length === 0) {
                                tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--text-light); padding: 20px;">Aucune donnée.</td></tr>`;
                                return;
                            }
                            tbody.innerHTML = data.map(r => {
                                const variance = parseFloat(r.variance);
                                const color = variance < 0 ? '#ef4444' : '#10b981';
                                return `
                                    <tr>
                                        <td><strong>${r.project_code}</strong> - ${r.project_name}</td>
                                        <td>${parseFloat(r.estimated).toFixed(2)} h</td>
                                        <td>${parseFloat(r.realized).toFixed(2)} h</td>
                                        <td><span style="color: ${color}; font-weight: 600;">${variance.toFixed(2)} h</span></td>
                                    </tr>
                                `;
                            }).join('');
                        }
                    });

                // 4. Project Profitability
                fetch(`../../../api/v1/reports/reports.php?action=project_profitability`)
                    .then(res => res.json())
                    .then(resData => {
                        const tbody = document.getElementById('project-profitability-tbody');
                        if (resData.success) {
                            const data = resData.data || [];
                            if (data.length === 0) {
                                tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--text-light); padding: 20px;">Aucune donnée.</td></tr>`;
                                return;
                            }
                            tbody.innerHTML = data.map(r => {
                                const net = parseFloat(r.net_profit);
                                const color = net < 0 ? '#ef4444' : '#10b981';
                                return `
                                    <tr>
                                        <td><strong>${r.project_code}</strong> - ${r.project_name}</td>
                                        <td>${parseFloat(r.budget).toFixed(2)} ${r.currency}</td>
                                        <td>${parseFloat(r.total_cost).toFixed(2)} ${r.currency}</td>
                                        <td><span style="color: ${color}; font-weight: 600;">${net.toFixed(2)} ${r.currency}</span></td>
                                    </tr>
                                `;
                            }).join('');
                        }
                    });
            }
        });
    </script>
</body>
</html>
