<?php
// LIMA Solutions ERP - Projects List View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

enforceModuleAccess('projects', $userRole, $companyId, 'view', $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projets - LIMA Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="../assets/projects.css">
</head>
<body>

    <!-- Left Sidebar Menu -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-layer-group"></i>
            <h2>LIMA ERP</h2>
        </div>
        <ul class="sidebar-menu" id="sidebar-menu">
            <li class="sidebar-item">
                <a href="/admin/index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </li>
            <li class="sidebar-item active">
                <a href="#"><i class="fa-solid fa-diagram-project"></i> Projetos</a>
            </li>
            <li class="sidebar-item">
                <a href="../../timesheets/views/list.php"><i class="fa-solid fa-clock"></i> Timesheets</a>
            </li>
            <li class="sidebar-item" id="menu-invoices" style="display: none;">
                <a href="/facture/index.html"><i class="fa-solid fa-file-invoice-dollar"></i> Factures</a>
            </li>
            <li class="sidebar-item" id="menu-settings" style="display: none;">
                <a href="/admin/index.php#settings-link"><i class="fa-solid fa-sliders"></i> Configuration</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <!-- Right Content Panel -->
    <div class="main-wrapper">
        <header class="navbar">
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <a href="/admin/index.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <main class="projects-container">
            <div class="projects-header">
                <div class="projects-title">
                    <h2>Gestion des Projets</h2>
                    <p>Visualisez et organisez les projets de l'entreprise.</p>
                </div>
                <div>
                    <a href="form.php" class="btn-header" style="background-color: var(--primary-teal); color: white; border: none;">
                        <i class="fa-solid fa-plus"></i> Nouveau Projet
                    </a>
                </div>
            </div>

            <!-- Start/Stop Timer Widget Embedded -->
            <div class="projects-card" style="padding: 20px;">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">Enregistrement Rapide de Temps</h3>
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="flex-grow: 1;">
                        <select id="timer-project-select" class="btn-header" style="width: 100%; text-align: left;">
                            <option value="">-- Sélectionner un Projet pour Chronométrer --</option>
                        </select>
                    </div>
                    <div class="timer-clock" id="timer-display" style="font-size: 24px; font-weight: 700; color: var(--text-dark);">00:00:00</div>
                    <div>
                        <button id="timer-start" class="timer-btn timer-btn-start" style="padding: 10px 20px;"><i class="fa-solid fa-play"></i> Démarrer</button>
                        <button id="timer-stop" class="timer-btn timer-btn-stop" style="display: none; padding: 10px 20px;"><i class="fa-solid fa-stop"></i> Arrêter</button>
                    </div>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="projects-card">
                <div class="projects-card-header">
                    <span class="projects-card-title">Liste des Projets</span>
                </div>
                <div class="crm-table-wrapper" style="overflow-x: auto;">
                    <table class="crm-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--bg-light); border-bottom: 1px solid var(--border-gray);">
                                <th style="padding: 12px; text-align: left;">Code</th>
                                <th style="padding: 12px; text-align: left;">Nom</th>
                                <th style="padding: 12px; text-align: left;">Client</th>
                                <th style="padding: 12px; text-align: left;">Statut</th>
                                <th style="padding: 12px; text-align: left;">Période</th>
                                <th style="padding: 12px; text-align: left;">Heures Estimées</th>
                                <th style="padding: 12px; text-align: left;">Budget</th>
                                <th style="padding: 12px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="projects-table-body">
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-light); padding: 40px;">
                                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px; color: var(--primary-teal);"></i><br>
                                    Chargement des projets...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        // Load projects in timer dropdown list
        fetch('../../../api/v1/projects/projects.php')
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const sel = document.getElementById('timer-project-select');
                    if (sel) {
                        const projects = resData.data.projects || [];
                        sel.innerHTML += projects.map(p => `<option value="${p.id}">${p.name} [${p.project_code}]</option>`).join('');
                    }
                }
            });

        // Dynamically show/hide menus
        fetch('../../../api/v1/session.php')
            .then(res => res.json())
            .then(data => {
                if (data.authenticated) {
                    if (data.enabled_modules.includes('invoices')) {
                        document.getElementById('menu-invoices').style.display = 'block';
                    }
                    if (data.enabled_modules.includes('settings') && (data.user.role === 'super_admin' || data.user.role === 'admin')) {
                        document.getElementById('menu-settings').style.display = 'block';
                    }
                }
            });
    </script>
    <script src="../assets/projects.js"></script>
</body>
</html>
