<?php
// LIMA Solutions ERP - Staff/Workforce Management Admin UI
require_once 'auth.php';
require_once 'modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Secure restriction: only super_admin, admin, manager
if (!in_array($userRole, ['super_admin', 'admin', 'manager'])) {
    header('Location: index.php');
    exit();
}

// Enforce module activation for the company
if (!isModuleEnabled('staff', $companyId, $pdo)) {
    header('Location: index.php');
    exit();
}

// ─── API HANDLER (AJAX POST/PUT/PATCH/GET) ───────────────────────────────────
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    // CSRF Protection for write actions
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $clientCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        $sessionCsrfToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionCsrfToken) || !hash_equals($sessionCsrfToken, $clientCsrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Erreur de sécurité CSRF.']);
            exit();
        }
    }

    try {
        if ($action === 'list') {
            // Fetch users with role 'staff' mapped to this company
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.email, u.phone, u.role, u.active, u.address, u.postal_code, u.hourly_cost
                FROM users u
                JOIN user_companies uc ON u.id = uc.user_id
                WHERE uc.company_id = :cid AND u.role = 'staff'
                ORDER BY u.name ASC
            ");
            $stmt->execute(['cid' => $companyId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $users]);
            exit();
        }

        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? null);
            $role = trim($_POST['role'] ?? 'staff');
            $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
            $address = trim($_POST['address'] ?? null);
            $postalCode = trim($_POST['postal_code'] ?? null);
            $hourlyCost = (float)($_POST['hourly_cost'] ?? 0.00);
            $password = trim($_POST['password'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                throw new Exception("Le nom, l'e-mail et le mot de passe sont obligatoires.");
            }

            // Check if email already exists
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmtCheck->execute(['email' => $email]);
            if ($stmtCheck->fetch()) {
                throw new Exception("Cet e-mail est déjà utilisé.");
            }

            $pdo->beginTransaction();

            // Insert into users
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password_hash, role, active, postal_code, address, hourly_cost)
                VALUES (:name, :email, :phone, :hash, :role, :active, :postal_code, :address, :hourly_cost)
            ");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'hash' => $hash,
                'role' => $role,
                'active' => $active,
                'postal_code' => $postalCode,
                'address' => $address,
                'hourly_cost' => $hourlyCost
            ]);
            $userId = $pdo->lastInsertId();

            // Map to company
            $stmtMap = $pdo->prepare("INSERT INTO user_companies (user_id, company_id) VALUES (:uid, :cid)");
            $stmtMap->execute(['uid' => $userId, 'cid' => $companyId]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Collaborateur créé avec succès.']);
            exit();
        }

        if ($action === 'update') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? null);
            $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
            $address = trim($_POST['address'] ?? null);
            $postalCode = trim($_POST['postal_code'] ?? null);
            $hourlyCost = (float)($_POST['hourly_cost'] ?? 0.00);

            if (empty($name) || empty($email) || !$userId) {
                throw new Exception("Paramètres invalides.");
            }

            // Verify company association for security
            $stmtVerify = $pdo->prepare("SELECT user_id FROM user_companies WHERE user_id = :uid AND company_id = :cid LIMIT 1");
            $stmtVerify->execute(['uid' => $userId, 'cid' => $companyId]);
            if (!$stmtVerify->fetch()) {
                throw new Exception("Accès interdit.");
            }

            // Check if email already used by another user
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $stmtCheck->execute(['email' => $email, 'id' => $userId]);
            if ($stmtCheck->fetch()) {
                throw new Exception("Cet e-mail est déjà utilisé.");
            }

            $stmt = $pdo->prepare("
                UPDATE users
                SET name = :name, email = :email, phone = :phone, active = :active, address = :address, postal_code = :postal_code, hourly_cost = :hourly_cost
                WHERE id = :id
            ");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'active' => $active,
                'address' => $address,
                'postal_code' => $postalCode,
                'hourly_cost' => $hourlyCost,
                'id' => $userId
            ]);

            echo json_encode(['success' => true, 'message' => 'Collaborateur mis à jour.']);
            exit();
        }

        if ($action === 'toggle_active') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $active = (int)($_POST['active'] ?? 0);

            if (!$userId) {
                throw new Exception("Identifiant manquant.");
            }

            $stmtVerify = $pdo->prepare("SELECT user_id FROM user_companies WHERE user_id = :uid AND company_id = :cid LIMIT 1");
            $stmtVerify->execute(['uid' => $userId, 'cid' => $companyId]);
            if (!$stmtVerify->fetch()) {
                throw new Exception("Accès interdit.");
            }

            $stmt = $pdo->prepare("UPDATE users SET active = :active WHERE id = :id");
            $stmt->execute(['active' => $active, 'id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'Statut mis à jour.']);
            exit();
        }

        if ($action === 'reset_password') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $password = trim($_POST['password'] ?? '');

            if (!$userId || empty($password)) {
                throw new Exception("Mot de passe obligatoire.");
            }

            $stmtVerify = $pdo->prepare("SELECT user_id FROM user_companies WHERE user_id = :uid AND company_id = :cid LIMIT 1");
            $stmtVerify->execute(['uid' => $userId, 'cid' => $companyId]);
            if (!$stmtVerify->fetch()) {
                throw new Exception("Accès interdit.");
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $stmt->execute(['hash' => $hash, 'id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'Mot de passe réinitialisé avec succès.']);
            exit();
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de l'Équipe - LIMA Solutions</title>
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main admin styles -->
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-container {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            overflow: hidden;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }
        .custom-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-gray);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .custom-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-gray);
            color: var(--text-dark);
            vertical-align: middle;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background-color: #d1fae5; color: #059669; }
        .status-inactive { background-color: #fee2e2; color: #dc2626; }
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--border-radius);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: var(--transition);
            text-decoration: none;
        }
        .btn-primary {
            background-color: #007a87;
            color: var(--white);
        }
        .btn-primary:hover {
            background-color: #005f69;
        }
        .btn-secondary {
            background-color: var(--bg-light);
            color: var(--text-dark);
            border-color: var(--border-gray);
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 500px;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 18px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        .form-control {
            padding: 10px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: #007a87;
        }
    </style>
</head>
<body>

    <!-- Admin Wrapper -->
    <div class="admin-container" style="display: flex; min-height: 100vh;">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-cube"></i>
                <h2 id="header-company-name">LIMA Solutions</h2>
            </div>
            <ul class="sidebar-menu" id="sidebar-menu">
                <li class="sidebar-item">
                    <a href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                </li>
            </ul>
            <div class="sidebar-footer" style="padding: 20px; border-top: 1px solid rgba(255, 255, 255, 0.05); font-size: 12px; color: rgba(255, 255, 255, 0.4); text-align: center;">
                <span id="user-display-name" style="display: block; margin-bottom: 10px;">Chargement...</span>
                <button class="logout-btn" id="logout-btn" style="background: none; border: none; color: #ef4444; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 12px;"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</button>
            </div>
        </aside>

        <!-- Main Workspace -->
        <div class="main-wrapper" style="flex-grow: 1; display: flex; flex-direction: column; min-width: 0;">
            <!-- Top Navbar -->
            <nav class="navbar" style="background-color: var(--white); border-bottom: 1px solid var(--border-gray); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 10;">
                <div class="navbar-brand-section">
                    <span style="font-weight: 700; font-size: 16px; color: var(--text-dark);">ERP Administration</span>
                </div>
                <div class="user-menu" style="display: flex; align-items: center; gap: 15px;">
                    <span class="user-name" id="navbar-user-display" style="font-weight: 600; font-size: 14px; color: var(--text-dark);"></span>
                </div>
            </nav>

            <main class="dashboard-container" style="padding: 30px; max-width: 1200px; width: 100%; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">
                <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h1 class="page-title" style="font-size: 24px; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-user-tie"></i> Équipe & Collaborateurs</h1>
                    <button class="btn-action btn-primary" onclick="openCreateModal()"><i class="fa-solid fa-user-plus"></i> Nouveau Collaborateur</button>
                </div>

            <!-- Table of users -->
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Téléphone</th>
                            <th>NPA / Adresse</th>
                            <th>Coût Horaire</th>
                            <th>Statut</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staff-table-body">
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-light); padding: 30px;">
                                Chargement des collaborateurs...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal" id="staff-modal">
        <div class="modal-content">
            <div class="modal-header">
                <span id="modal-title">Créer un Collaborateur</span>
                <button class="modal-close" onclick="closeModal('staff-modal')">&times;</button>
            </div>
            <form id="staff-form">
                <input type="hidden" name="csrf_token" id="csrf-token-field">
                <input type="hidden" name="user_id" id="form-user-id">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="f-name">Nom complet *</label>
                        <input type="text" id="f-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="f-email">E-mail *</label>
                        <input type="email" id="f-email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="f-phone">Téléphone</label>
                        <input type="text" id="f-phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group" id="pwd-field-group">
                        <label for="f-password">Mot de passe *</label>
                        <input type="password" id="f-password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="f-hourly">Coût Horaire (CHF/h)</label>
                        <input type="number" id="f-hourly" name="hourly_cost" step="0.01" min="0" class="form-control" value="0.00">
                    </div>
                    <div class="form-group">
                        <label for="f-npa">NPA (Code Postal)</label>
                        <input type="text" id="f-npa" name="postal_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="f-active">Statut</label>
                        <select id="f-active" name="active" class="form-control">
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="f-address">Adresse complète</label>
                        <input type="text" id="f-address" name="address" class="form-control">
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn-action btn-secondary" onclick="closeModal('staff-modal')">Annuler</button>
                    <button type="submit" class="btn-action btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div class="modal" id="pwd-modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <span>Réinitialiser le mot de passe</span>
                <button class="modal-close" onclick="closeModal('pwd-modal')">&times;</button>
            </div>
            <form id="pwd-form">
                <input type="hidden" name="user_id" id="pwd-user-id">
                <div class="form-group">
                    <label for="new-password">Nouveau mot de passe *</label>
                    <input type="password" id="new-password" class="form-control" required minlength="4">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn-action btn-secondary" onclick="closeModal('pwd-modal')">Annuler</button>
                    <button type="submit" class="btn-action btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast message -->
    <div id="toast" style="display: none; position: fixed; bottom: 20px; right: 20px; background-color: #333; color: #fff; padding: 12px 24px; border-radius: var(--border-radius); z-index: 9999; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);"></div>

    <script>
        let csrfToken = '';

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            toast.style.backgroundColor = type === 'error' ? '#ef4444' : '#10b981';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }

        function showModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Check session
            fetch('../api/v1/session.php')
                .then(res => res.json())
                .then(data => {
                    if (data.authenticated && ['super_admin', 'admin', 'manager'].includes(data.user.role)) {
                        document.getElementById('user-display-name').textContent = data.user.name + ' (' + data.user.role.toUpperCase() + ')';
                        document.getElementById('navbar-user-display').textContent = data.user.name + ' (' + data.user.role.toUpperCase() + ')';
                        if (data.active_company) {
                            document.getElementById('header-company-name').textContent = data.active_company.name;
                        }
                        csrfToken = data.csrf_token;
                        document.getElementById('csrf-token-field').value = csrfToken;

                        // Load sidebar items matching dashboard
                        const sidebarMenu = document.getElementById('sidebar-menu');
                        sidebarMenu.innerHTML = `
                            <li class="sidebar-item">
                                <a href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                            </li>
                        `;

                        const moduleDefinitions = {
                            'crm': { title: 'Clientes', icon: 'fa-users', path: '../modules/crm/views/list.php' },
                            'crm_leads': { title: 'Pipeline Leads', icon: 'fa-funnel-dollar', path: '../modules/crm/views/leads.php' },
                            'marketplace': { title: 'Marketplace', icon: 'fa-store', path: 'marketplace.php' },
                            'staff': { title: 'Colaboradores / Équipe', icon: 'fa-user-tie', path: 'staff.php' },
                            'projects': { title: 'Projetos', icon: 'fa-diagram-project', path: '../modules/projects/views/list.php' },
                            'timesheets': { title: 'Timesheets', icon: 'fa-clock', path: '../modules/timesheets/views/list.php' },
                            'invoices': { title: 'Factures', icon: 'fa-file-invoice-dollar', path: '../modules/invoices/views/list.php' },
                            'quotes': { title: 'Orçamentos', icon: 'fa-calculator', path: '../modules/quotes/views/list.php' },
                            'payments': { title: 'Pagamentos', icon: 'fa-wallet', path: '../modules/payments/views/list.php' },
                            'calendar': { title: 'Agenda', icon: 'fa-calendar-days', path: '#calendar-link' },
                            'reports': { title: 'Relatórios', icon: 'fa-chart-line', path: '../modules/reports/views/dashboard.php' },
                            'settings': { title: 'Configuration', icon: 'fa-sliders', path: '#settings-link' }
                        };

                        Object.keys(moduleDefinitions).forEach(modName => {
                            let isEnabled = false;
                            if (modName === 'crm_leads') {
                                isEnabled = data.enabled_modules.includes('crm');
                            } else {
                                isEnabled = data.enabled_modules.includes(modName) || (modName === 'quotes' && data.enabled_modules.includes('invoices'));
                            }

                            let isPermitted = false;
                            if (data.user.role === 'super_admin') {
                                isPermitted = true;
                            } else {
                                const targetMod = (modName === 'crm_leads') ? 'crm' : modName;
                                const perm = data.permissions.find(p => p.module_name === targetMod || (targetMod === 'quotes' && p.module_name === 'invoices'));
                                isPermitted = perm && parseInt(perm.can_view) === 1;
                            }

                            if (isEnabled && isPermitted) {
                                const def = moduleDefinitions[modName];
                                const li = document.createElement('li');
                                li.className = 'sidebar-item' + (modName === 'staff' ? ' active' : '');
                                li.innerHTML = `<a href="${def.path}"><i class="fa-solid ${def.icon}"></i> ${def.title}</a>`;
                                sidebarMenu.appendChild(li);
                            }
                        });

                        loadStaff();
                    } else {
                        window.location.href = 'index.php';
                    }
                });

            // Logout
            document.getElementById('logout-btn').addEventListener('click', () => {
                fetch('../api/v1/logout.php').then(() => window.location.href = 'login.php');
            });

            // Handle Save
            document.getElementById('staff-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const isEdit = !!document.getElementById('form-user-id').value;
                const action = isEdit ? 'update' : 'create';

                try {
                    const res = await fetch(`staff.php?action=${action}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': csrfToken },
                        body: formData
                    });
                    const result = await res.json();
                    if (result.success) {
                        showToast(result.message);
                        closeModal('staff-modal');
                        loadStaff();
                    } else {
                        showToast(result.message, 'error');
                    }
                } catch (err) {
                    showToast('Erreur serveur.', 'error');
                }
            });

            // Handle Password Reset Submit
            document.getElementById('pwd-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const userId = document.getElementById('pwd-user-id').value;
                const password = document.getElementById('new-password').value;

                try {
                    const res = await fetch('staff.php?action=reset_password', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-Token': csrfToken
                        },
                        body: `user_id=${userId}&password=${encodeURIComponent(password)}&csrf_token=${csrfToken}`
                    });
                    const result = await res.json();
                    if (result.success) {
                        showToast(result.message);
                        closeModal('pwd-modal');
                    } else {
                        showToast(result.message, 'error');
                    }
                } catch (err) {
                    showToast('Erreur serveur.', 'error');
                }
            });
        });

        let staffList = [];

        async function loadStaff() {
            try {
                const res = await fetch('staff.php?action=list');
                const result = await res.json();
                if (result.success) {
                    staffList = result.data;
                    renderTable(staffList);
                }
            } catch (err) {
                console.error(err);
            }
        }

        function renderTable(users) {
            const tbody = document.getElementById('staff-table-body');
            tbody.innerHTML = '';

            if (users.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-light);">Aucun collaborateur trouvé.</td></tr>`;
                return;
            }

            users.forEach(u => {
                const tr = document.createElement('tr');
                const statusBadge = u.active == 1 ? '<span class="status-badge status-active">Actif</span>' : '<span class="status-badge status-inactive">Inactif</span>';
                
                tr.innerHTML = `
                    <td style="font-weight: 600;">${escapeHtml(u.name)}</td>
                    <td>${escapeHtml(u.email)}</td>
                    <td>${escapeHtml(u.phone || '-')}</td>
                    <td>${escapeHtml((u.postal_code ? u.postal_code + ' ' : '') + (u.address || '-'))}</td>
                    <td>${parseFloat(u.hourly_cost).toFixed(2)} CHF/h</td>
                    <td>${statusBadge}</td>
                    <td style="text-align: right;">
                        <div class="actions-group" style="justify-content: flex-end; gap: 8px;">
                            <button class="btn-action btn-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="openEditModal(${u.id})"><i class="fa-solid fa-pen"></i> Modifier</button>
                            <button class="btn-action btn-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="openPwdModal(${u.id})"><i class="fa-solid fa-key"></i> Clé</button>
                            <button class="btn-action btn-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="toggleActive(${u.id}, ${u.active == 1 ? 0 : 1})">
                                ${u.active == 1 ? '<i class="fa-solid fa-user-slash"></i> Désactiver' : '<i class="fa-solid fa-user-check"></i> Activer'}
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function openCreateModal() {
            document.getElementById('modal-title').textContent = "Créer un Collaborateur";
            document.getElementById('form-user-id').value = '';
            document.getElementById('staff-form').reset();
            document.getElementById('pwd-field-group').style.display = 'flex';
            document.getElementById('f-password').required = true;
            document.getElementById('f-email').disabled = false;
            showModal('staff-modal');
        }

        function openEditModal(id) {
            const u = staffList.find(x => x.id === id);
            if (!u) return;

            document.getElementById('modal-title').textContent = "Modifier le Collaborateur";
            document.getElementById('form-user-id').value = u.id;
            document.getElementById('f-name').value = u.name;
            document.getElementById('f-email').value = u.email;
            document.getElementById('f-email').disabled = true; // Email unique lock
            document.getElementById('f-phone').value = u.phone || '';
            document.getElementById('f-hourly').value = u.hourly_cost;
            document.getElementById('f-npa').value = u.postal_code || '';
            document.getElementById('f-active').value = u.active;
            document.getElementById('f-address').value = u.address || '';

            document.getElementById('pwd-field-group').style.display = 'none';
            document.getElementById('f-password').required = false;

            showModal('staff-modal');
        }

        function openPwdModal(id) {
            document.getElementById('pwd-user-id').value = id;
            document.getElementById('new-password').value = '';
            showModal('pwd-modal');
        }

        async function toggleActive(id, newStatus) {
            try {
                const res = await fetch('staff.php?action=toggle_active', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': csrfToken
                    },
                    body: `user_id=${id}&active=${newStatus}&csrf_token=${csrfToken}`
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message);
                    loadStaff();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Erreur.', 'error');
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
