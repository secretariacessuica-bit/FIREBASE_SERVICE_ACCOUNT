<?php
// LIMA Solutions ERP - Payments List View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce module access (Requires at least 'view' permission)
enforceModuleAccess('payments', $userRole, $companyId, 'view', $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Paiements - LIMA Solutions</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for clean icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main styles styling from dashboard -->
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>
        :root {
            --primary-teal: #007a87;
            --primary-teal-dark: #005a63;
            --primary-teal-light: #e6f2f3;
            --sidebar-bg: #112224;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-gray: #e2e8f0;
            --white: #ffffff;
            --bg-light: #f8fafc;
            --border-radius: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', 'Arial', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--white);
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            flex-shrink: 0;
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
            color: var(--primary-teal);
        }

        .sidebar-brand h2 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
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
            background-color: var(--primary-teal);
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
        }

        /* Main Content wrapper */
        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Navbar Layout */
        .navbar {
            background-color: var(--white);
            border-bottom: 1px solid var(--border-gray);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: auto;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-dark);
        }

        .btn-header {
            background-color: transparent;
            color: var(--text-dark);
            border: 1px solid var(--border-gray);
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-header:hover {
            background-color: var(--bg-light);
            border-color: var(--text-dark);
        }

        /* Toast styles */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background-color: #1e293b;
            color: var(--white);
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-size: 13px;
            z-index: 3000;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-weight: 500;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
        }

        .toast.error {
            background-color: #ef4444;
        }

        .toast.success {
            background-color: #10b981;
        }
    </style>
    <!-- payments custom layouts styles -->
    <link rel="stylesheet" href="../assets/payments.css">
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
            <li class="sidebar-item">
                <a href="../../crm/views/list.php"><i class="fa-solid fa-users"></i> Clientes</a>
            </li>
            <li class="sidebar-item">
                <a href="../../quotes/views/list.php"><i class="fa-solid fa-calculator"></i> Orçamentos</a>
            </li>
            <li class="sidebar-item" id="menu-invoices" style="display: none;">
                <a href="../../invoices/views/list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Factures</a>
            </li>
            <li class="sidebar-item active" id="menu-payments" style="display: none;">
                <a href="list.php"><i class="fa-solid fa-wallet"></i> Pagamentos</a>
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
        <!-- Top Navigation Bar -->
        <header class="navbar">
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <a href="/admin/index.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <!-- Main Container -->
        <main class="payments-container" style="padding: 30px; max-width: 1200px; width:100%; margin: 0 auto;">
            <div class="payments-header-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
                <div class="payments-title-section">
                    <h2 style="font-size:24px; font-weight:700; color:var(--primary-teal-dark);">Gestion des Paiements</h2>
                    <p style="color:var(--text-light); font-size:14px; margin-top:5px;">Enregistrez et suivez les règlements de vos factures clients.</p>
                </div>
                <!-- Action Button if writable -->
                <div id="create-btn-container" style="display: none;">
                    <a href="form.php" class="btn-crm btn-crm-primary" style="padding: 10px 20px; font-size: 14px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-plus"></i> Enregistrer un Paiement
                    </a>
                </div>
            </div>

            <!-- Search and List -->
            <div class="payments-card" style="background:var(--white); border: 1px solid var(--border-gray); border-radius: var(--border-radius); padding:20px;">
                <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                    <div style="position: relative; flex-grow: 1;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <input type="text" id="payments-search-input" class="crm-search-input" placeholder="Rechercher par numéro de paiement, facture, client, méthode..." style="width:100%; padding:10px 10px 10px 40px; border:1px solid var(--border-gray); border-radius:var(--border-radius); outline:none;">
                    </div>
                </div>

                <div class="crm-table-wrapper" style="overflow-x:auto;">
                    <table class="crm-table" style="width:100%; border-collapse:collapse; text-align:left;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border-gray); color:var(--text-light); font-weight:600; font-size:13px;">
                                <th style="padding:12px; width: 130px;">N° Paiement</th>
                                <th style="padding:12px; width: 130px;">N° Facture</th>
                                <th style="padding:12px;">Client</th>
                                <th style="padding:12px;">Date</th>
                                <th style="padding:12px;">Méthode</th>
                                <th style="padding:12px;">Montant</th>
                                <th style="padding:12px; width: 250px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="payments-table-body">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-light); padding: 40px;">
                                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px; color: var(--primary-teal);"></i><br>
                                    Chargement des paiements...
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

    <script>
        // Check active modules and permissions dynamically for sidebar links
        fetch('../../../api/v1/session.php')
            .then(res => res.json())
            .then(data => {
                if (data.authenticated) {
                    document.getElementById('user-display-name').textContent = data.user.name;
                    
                    // Customize main color
                    if (data.active_company && data.active_company.main_color) {
                        document.documentElement.style.setProperty('--primary-teal', data.active_company.main_color);
                    }

                    // Dynamically toggle sidebar links
                    if (data.enabled_modules.includes('invoices')) {
                        document.getElementById('menu-invoices').style.display = 'block';
                    }
                    if (data.enabled_modules.includes('payments') || data.enabled_modules.includes('invoices')) {
                        document.getElementById('menu-payments').style.display = 'block';
                    }
                    if (data.enabled_modules.includes('settings') && (data.user.role === 'super_admin' || data.user.role === 'admin')) {
                        document.getElementById('menu-settings').style.display = 'block';
                    }

                    // Dynamic access check for "Enregistrer" button
                    let hasEditPermission = false;
                    if (data.user.role === 'super_admin') {
                        hasEditPermission = true;
                    } else {
                        const perm = data.permissions.find(p => p.module_name === 'payments') || data.permissions.find(p => p.module_name === 'invoices');
                        hasEditPermission = perm && parseInt(perm.can_edit) === 1;
                    }

                    if (hasEditPermission) {
                        document.getElementById('create-btn-container').style.display = 'block';
                    }
                } else {
                    window.location.href = '../../admin/login.php';
                }
            })
            .catch(err => {
                console.error(err);
                window.location.href = '../../admin/login.php';
            });
    </script>
    <script src="../assets/payments.js"></script>
</body>
</html>
