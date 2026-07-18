<?php
// LIMA Solutions ERP - Marketplace Admin Moderation Panel UI
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
if (!isModuleEnabled('marketplace', $companyId, $pdo)) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modération Marketplace - LIMA Solutions</title>
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

        .moderation-table-container {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .moderation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        .moderation-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-gray);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .moderation-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-gray);
            color: var(--text-dark);
            vertical-align: middle;
        }

        .moderation-table tr:last-child td {
            border-bottom: none;
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

        .status-draft { background-color: #f1f5f9; color: #475569; }
        .status-pending { background-color: #fef3c7; color: #d97706; }
        .status-approved { background-color: #d1fae5; color: #059669; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }
        .status-archived { background-color: #e2e8f0; color: #64748b; }
        .status-sold { background-color: #dbeafe; color: #2563eb; }
        .status-donated { background-color: #f3e8ff; color: #7c3aed; }

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

        .btn-view {
            background-color: var(--bg-light);
            color: var(--text-dark);
            border-color: var(--border-gray);
        }

        .btn-view:hover {
            background-color: #e2e8f0;
        }

        .btn-approve {
            background-color: #10b981;
            color: var(--white);
        }

        .btn-approve:hover {
            background-color: #059669;
        }

        .btn-reject {
            background-color: #ef4444;
            color: var(--white);
        }

        .btn-reject:hover {
            background-color: #dc2626;
        }

        .btn-archive {
            background-color: #6b7280;
            color: var(--white);
        }

        .btn-archive:hover {
            background-color: #4b5563;
        }

        .actions-group {
            display: flex;
            gap: 6px;
        }

        /* Modal Preview and Reject */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            padding: 20px;
        }

        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            max-width: 600px;
            width: 100%;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-gray);
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-teal-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--text-dark);
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-gray);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background-color: #f8fafc;
            border-bottom-left-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }

        /* Preview Details */
        .preview-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .preview-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .preview-value {
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .gallery-item {
            position: relative;
            aspect-ratio: 4/3;
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid var(--border-gray);
            background-color: #f1f5f9;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-teal);
        }

        /* Search & Filter section */
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            background-color: var(--white);
            padding: 16px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-gray);
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-input {
            padding: 8px 12px;
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            outline: none;
            font-size: 13px;
            min-width: 180px;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            outline: none;
            font-size: 13px;
            background-color: var(--white);
        }
    </style>
</head>
<body>
    <!-- Sidebar template wrapper -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-truck-ramp-box"></i>
            <h2>LIMA ERP</h2>
        </div>
        <ul class="sidebar-menu" id="sidebar-menu">
            <li class="sidebar-item">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            Lima Solutions © 2026
        </div>
    </aside>

    <!-- Main Content wrapper -->
    <div class="main-wrapper" style="flex-grow: 1; display: flex; flex-direction: column;">
        <!-- Header template -->
        <header class="main-header" style="background-color: var(--white); border-bottom: 1px solid var(--border-gray); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; height: 60px;">
            <div style="font-weight: 600; font-size: 15px;" id="header-company-name">Marketplace</div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="user-badge" id="user-display-name" style="font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: var(--border-radius); background-color: var(--bg-light);">Admin</span>
                <button id="logout-btn" style="background: none; border: none; font-size: 16px; color: #ef4444; cursor: pointer;" title="Déconnexion"><i class="fa-solid fa-right-from-bracket"></i></button>
            </div>
        </header>

        <main style="flex-grow: 1; padding: 30px; overflow-y: auto;">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fa-solid fa-store" style="color: var(--primary-teal);"></i>
                    Modération du Marketplace
                </h1>
            </div>

            <!-- Filter and Search controls -->
            <div class="filter-bar">
                <input type="text" id="search-input" class="filter-input" placeholder="Rechercher par titre, client...">
                <select id="status-filter" class="filter-select">
                    <option value="">Tous les états</option>
                    <option value="Pending" selected>En attente (Pending)</option>
                    <option value="Approved">Approuvés (Approved)</option>
                    <option value="Rejected">Rejetés (Rejected)</option>
                    <option value="Draft">Draft</option>
                    <option value="Archived">Archivés (Archived)</option>
                    <option value="Sold">Vendus (Sold)</option>
                    <option value="Donated">Donnés (Donated)</option>
                </select>
                <select id="category-filter" class="filter-select">
                    <option value="">Toutes les catégories</option>
                    <option value="Móveis Usados">Móveis Usados</option>
                    <option value="Móveis Seminovos">Móveis Seminovos</option>
                    <option value="Doações">Doações</option>
                </select>
            </div>

            <!-- List table -->
            <div class="moderation-table-container">
                <table class="moderation-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Client</th>
                            <th>Créé le</th>
                            <th>État</th>
                            <th>Intérêts</th>
                            <th style="width: 280px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="items-table-body">
                        <!-- Populated dynamically via JS -->
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-light); padding: 30px;">
                                Chargement des annonces...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Preview Modal -->
    <div class="modal" id="preview-modal">
        <div class="modal-content">
            <div class="modal-header">
                <span id="preview-title">Détails de l'annonce</span>
                <button class="modal-close" onclick="closeModal('preview-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="preview-grid">
                    <div>
                        <div class="preview-label">Photos</div>
                        <div id="preview-gallery" class="gallery-container">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                    <div>
                        <div class="preview-label">Description</div>
                        <div id="preview-description" class="preview-value" style="white-space: pre-wrap;">-</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <div class="preview-label">Prix</div>
                            <div id="preview-price" class="preview-value">-</div>
                        </div>
                        <div>
                            <div class="preview-label">Localisation</div>
                            <div id="preview-location" class="preview-value">-</div>
                        </div>
                    </div>
                    <div id="preview-rejection-section" style="display: none; background-color: #fee2e2; border: 1px solid #fecaca; border-radius: var(--border-radius); padding: 12px;">
                        <div class="preview-label" style="color: #dc2626;">Motif du rejet</div>
                        <div id="preview-rejection-reason" class="preview-value" style="color: #dc2626;">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="preview-modal-footer">
                <button class="btn-action btn-view" onclick="closeModal('preview-modal')">Fermer</button>
            </div>
        </div>
    </div>

    <!-- Reject Reason Modal -->
    <div class="modal" id="reject-modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <span>Motif de Rejet requis</span>
                <button class="modal-close" onclick="closeModal('reject-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reject-form">
                    <input type="hidden" id="reject-item-id">
                    <div class="form-group">
                        <label for="rejection-reason-select">Sélectionner un motif prédéfini</label>
                        <select id="rejection-reason-select" class="form-control" onchange="fillPredefinedReason()">
                            <option value="">-- Autre raison (Saisir ci-dessous) --</option>
                            <option value="Fotos insuficientes">Fotos insuficientes</option>
                            <option value="Descrição incompleta">Descrição incompleta</option>
                            <option value="Item não permitido">Item não permitido</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rejection-reason-input">Motif détaillé (obligatoire)</label>
                        <textarea id="rejection-reason-input" class="form-control" rows="4" required placeholder="Décrivez le motif du rejet de cette annonce..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-action btn-view" onclick="closeModal('reject-modal')">Annuler</button>
                <button type="button" class="btn-action btn-reject" onclick="submitRejection()">Confirmer le Rejet</button>
            </div>
        </div>
    </div>

    <!-- Toast message -->
    <div id="toast" style="display: none; position: fixed; bottom: 20px; right: 20px; background-color: #333; color: #fff; padding: 12px 24px; border-radius: var(--border-radius); z-index: 9999; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);"></div>

    <script>
        let allItems = [];
        let csrfToken = '';

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            if (type === 'error') {
                toast.style.backgroundColor = '#ef4444';
            } else {
                toast.style.backgroundColor = '#10b981';
            }
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function showModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Fetch metadata, user and company validation
        document.addEventListener('DOMContentLoaded', () => {
            fetch('../api/v1/session.php')
                .then(res => res.json())
                .then(data => {
                    if (data.authenticated && ['super_admin', 'admin', 'manager'].includes(data.user.role)) {
                        document.getElementById('user-display-name').textContent = data.user.name + ' (' + data.user.role.toUpperCase() + ')';
                        if (data.active_company) {
                            document.getElementById('header-company-name').textContent = "Marketplace - " + data.active_company.name;
                        }
                        csrfToken = data.csrf_token;
                        
                        // Load sidebar items matching enabled modules
                        const sidebarMenu = document.getElementById('sidebar-menu');
                        data.enabled_modules.forEach(mod => {
                            if (mod === 'staff') {
                                sidebarMenu.innerHTML += `
                                    <li class="sidebar-item">
                                        <a href="staff.php"><i class="fa-solid fa-user-tie"></i> Équipe</a>
                                    </li>
                                `;
                            } else if (mod === 'marketplace') {
                                sidebarMenu.innerHTML += `
                                    <li class="sidebar-item active">
                                        <a href="marketplace.php"><i class="fa-solid fa-store"></i> Marketplace</a>
                                    </li>
                                `;
                            }
                        });

                        // Load items
                        loadItems();
                    } else {
                        window.location.href = 'index.php';
                    }
                })
                .catch(err => {
                    console.error('Session check failed', err);
                    window.location.href = 'index.php';
                });

            // Set up search and filter events
            document.getElementById('search-input').addEventListener('input', applyFilters);
            document.getElementById('status-filter').addEventListener('change', applyFilters);
            document.getElementById('category-filter').addEventListener('change', applyFilters);

            // Logout action
            document.getElementById('logout-btn').addEventListener('click', () => {
                fetch('../api/v1/logout.php')
                    .then(res => res.json())
                    .then(() => {
                        window.location.href = 'login.php';
                    });
            });
        });

        function loadItems() {
            fetch('../api/v1/marketplace/moderate.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        allItems = data.items;
                        applyFilters();
                    } else {
                        showToast(data.message || 'Erreur lors du chargement des annonces.', 'error');
                    }
                })
                .catch(err => {
                    console.error('API Fetch failed', err);
                    showToast('Erreur serveur lors de la récupération.', 'error');
                });
        }

        function applyFilters() {
            const searchQuery = document.getElementById('search-input').value.toLowerCase().trim();
            const statusFilter = document.getElementById('status-filter').value;
            const categoryFilter = document.getElementById('category-filter').value;

            const filtered = allItems.filter(item => {
                const matchesSearch = item.title.toLowerCase().includes(searchQuery) ||
                                      item.client_name.toLowerCase().includes(searchQuery) ||
                                      item.client_email.toLowerCase().includes(searchQuery);
                const matchesStatus = statusFilter === '' || item.status === statusFilter;
                const matchesCategory = categoryFilter === '' || item.category_name === categoryFilter;

                return matchesSearch && matchesStatus && matchesCategory;
            });

            renderTable(filtered);
        }

        function renderTable(items) {
            const tbody = document.getElementById('items-table-body');
            tbody.innerHTML = '';

            if (items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-light); padding: 30px;">
                            Aucune annonce trouvée correspondant aux critères de recherche.
                        </td>
                    </tr>
                `;
                return;
            }

            items.forEach(item => {
                const tr = document.createElement('tr');
                
                // Formatted price or donation
                const priceFormatted = item.price ? parseFloat(item.price).toFixed(2) + ' CHF' : 'Donation (Gratuit)';

                // Formatted status badge
                const statusClass = 'status-' + item.status.toLowerCase();

                // Created date formatting
                const createdDate = new Date(item.created_at).toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });

                // Generate actions buttons based on current status
                let actionButtonsHtml = `<button class="btn-action btn-view" onclick="previewItem(${item.id})" title="Pré-visualiser"><i class="fa-solid fa-eye"></i></button> `;
                
                if (item.status === 'Pending' || item.status === 'Rejected') {
                    actionButtonsHtml += `
                        <button class="btn-action btn-approve" onclick="moderateItem(${item.id}, 'Approve')" title="Approuver"><i class="fa-solid fa-check"></i></button>
                    `;
                }

                if (item.status === 'Pending' || item.status === 'Approved') {
                    actionButtonsHtml += `
                        <button class="btn-action btn-reject" onclick="openRejectModal(${item.id})" title="Rejeter"><i class="fa-solid fa-xmark"></i></button>
                    `;
                }

                if (item.status !== 'Archived') {
                    actionButtonsHtml += `
                        <button class="btn-action btn-archive" onclick="moderateItem(${item.id}, 'Archive')" title="Archiver"><i class="fa-solid fa-box-archive"></i></button>
                    `;
                }

                let badgesHtml = `<span class="status-badge ${statusClass}">${item.status}</span>`;
                if (parseInt(item.request_delivery) === 1) {
                    badgesHtml += ` <span class="status-badge" style="background-color: #e0f2fe; color: #0369a1; margin-top: 4px;" title="Livraison demandée"><i class="fa-solid fa-truck"></i> Livraison</span>`;
                }
                if (parseInt(item.request_storage) === 1) {
                    badgesHtml += ` <span class="status-badge" style="background-color: #fef3c7; color: #b45309; margin-top: 4px;" title="Stockage demandé"><i class="fa-solid fa-warehouse"></i> Stockage</span>`;
                }

                tr.innerHTML = `
                    <td style="font-weight: 600;">${escapeHtml(item.title)}</td>
                    <td>${escapeHtml(item.category_name)}</td>
                    <td>
                        <div style="font-weight: 500;">${escapeHtml(item.client_name)}</div>
                        <div style="font-size: 11px; color: var(--text-light);">${escapeHtml(item.client_email)}</div>
                    </td>
                    <td>${createdDate}</td>
                    <td>
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 4px;">
                            ${badgesHtml}
                        </div>
                    </td>
                    <td style="font-weight: bold; text-align: center;">${item.interests_count}</td>
                    <td style="text-align: right;">
                        <div class="actions-group" style="justify-content: flex-end;">
                            ${actionButtonsHtml}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function previewItem(id) {
            const item = allItems.find(it => it.id === id);
            if (!item) return;

            document.getElementById('preview-title').textContent = escapeHtml(item.title);
            document.getElementById('preview-description').textContent = escapeHtml(item.description);
            document.getElementById('preview-price').textContent = item.price ? parseFloat(item.price).toFixed(2) + ' CHF' : 'Donation / Gratuit';
            document.getElementById('preview-location').textContent = escapeHtml(item.location);

            // Display rejection reason if rejected
            const rejSec = document.getElementById('preview-rejection-section');
            if (item.status === 'Rejected' && item.rejection_reason) {
                rejSec.style.display = 'block';
                document.getElementById('preview-rejection-reason').textContent = escapeHtml(item.rejection_reason);
            } else {
                rejSec.style.display = 'none';
            }

            // Populate Gallery (Safely without physical path leakage)
            const gallery = document.getElementById('preview-gallery');
            gallery.innerHTML = '';
            
            if (item.photos && item.photos.length > 0) {
                item.photos.forEach(photoUrl => {
                    const div = document.createElement('div');
                    div.className = 'gallery-item';
                    div.innerHTML = `<img src="${photoUrl}" alt="Photo de l'item" onclick="window.open('${photoUrl}', '_blank')">`;
                    gallery.appendChild(div);
                });
            } else {
                gallery.innerHTML = '<div style="color: var(--text-light); font-size: 13px; font-style: italic; padding: 10px;">Aucune photo fournie.</div>';
            }

            // Custom footer buttons inside preview modal
            const footer = document.getElementById('preview-modal-footer');
            let footerHtml = `<button class="btn-action btn-view" onclick="closeModal('preview-modal')">Fermer</button>`;
            
            if (item.status === 'Pending' || item.status === 'Rejected') {
                footerHtml += `<button class="btn-action btn-approve" onclick="closeModal('preview-modal'); moderateItem(${item.id}, 'Approve')">Approuver</button>`;
            }
            if (item.status === 'Pending' || item.status === 'Approved') {
                footerHtml += `<button class="btn-action btn-reject" onclick="closeModal('preview-modal'); openRejectModal(${item.id})">Rejeter</button>`;
            }
            footer.innerHTML = footerHtml;

            showModal('preview-modal');
        }

        function moderateItem(id, action, rejectionReason = '') {
            fetch('../api/v1/marketplace/moderate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    item_id: id,
                    action: action,
                    rejection_reason: rejectionReason
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('L\'annonce a été modifiée avec succès.');
                    loadItems();
                } else {
                    showToast(data.message || 'Erreur lors du traitement.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur de communication.', 'error');
            });
        }

        function openRejectModal(id) {
            document.getElementById('reject-item-id').value = id;
            document.getElementById('rejection-reason-select').value = '';
            document.getElementById('rejection-reason-input').value = '';
            showModal('reject-modal');
        }

        function fillPredefinedReason() {
            const select = document.getElementById('rejection-reason-select');
            const textarea = document.getElementById('rejection-reason-input');
            if (select.value) {
                textarea.value = select.value;
            }
        }

        function submitRejection() {
            const id = document.getElementById('reject-item-id').value;
            const reason = document.getElementById('rejection-reason-input').value.trim();

            if (!reason) {
                showToast('Le motif de rejet est requis.', 'error');
                return;
            }

            closeModal('reject-modal');
            moderateItem(id, 'Reject', reason);
        }
    </script>
</body>
</html>
