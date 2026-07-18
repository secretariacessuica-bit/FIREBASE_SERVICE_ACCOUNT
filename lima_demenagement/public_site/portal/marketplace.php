<?php
// LIMA Solutions ERP - Client Portal Marketplace Management
require_once 'auth.php';

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

// Fetch categories for forms
$stmtCats = $pdo->query("SELECT * FROM marketplace_categories ORDER BY id ASC");
$categories = $stmtCats->fetchAll();

// Company Info
$stmtComp = $pdo->prepare("SELECT name, main_color FROM companies WHERE id = :id LIMIT 1");
$stmtComp->execute(['id' => $companyId]);
$company = $stmtComp->fetch();
$companyName = $company['name'] ?? 'LIMA Solutions';
$mainColor = $company['main_color'] ?? '#007a87';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace Marketplace - <?php echo htmlspecialchars($companyName); ?></title>
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
            --green-ok: #10b981;
            --red-alert: #ef4444;
            --yellow-warning: #f59e0b;
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
        }

        .sidebar-item a:hover {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.05);
        }

        .sidebar-item.active a {
            color: var(--white);
            background-color: var(--primary);
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

        .content-container {
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
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

        /* Form Controls */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 1rem;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-light);
        }

        .form-group input, .form-group textarea, .form-group select {
            padding: 10px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            background-color: var(--white);
        }

        .btn {
            background-color: var(--primary);
            color: var(--white);
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn.secondary {
            background-color: transparent;
            border: 1px solid var(--border-gray);
            color: var(--text-light);
        }

        .grid-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 768px) {
            .grid-layout {
                grid-template-columns: 1fr 2fr;
            }
        }

        /* Items list */
        .items-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .item-card {
            background-color: var(--bg-light);
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .item-card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--border-gray);
        }

        .item-card-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .item-card-details h4 {
            font-size: 15px;
            font-weight: 700;
        }

        .item-card-details p {
            font-size: 12px;
            color: var(--text-light);
        }

        .badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-pending { background-color: #fef3c7; color: #d97706; }
        .badge-approved { background-color: #d1fae5; color: #065f46; }
        .badge-rejected { background-color: #fee2e2; color: #b91c1c; }
        .badge-archived { background-color: #f1f5f9; color: #475569; }
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
            <li class="sidebar-item">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
            </li>
            <li class="sidebar-item">
                <a href="quotes.php"><i class="fa-solid fa-file-signature"></i> Mes Devis</a>
            </li>
            <li class="sidebar-item">
                <a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Mes Factures</a>
            </li>
            <li class="sidebar-item">
                <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            </li>
            <li class="sidebar-item active">
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
            <h1>Mes Annonces Marketplace</h1>
            <a href="/marketplace/" class="btn secondary" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Voir le Catalogue Public</a>
        </header>

        <main class="content-container">
            <div class="grid-layout">
                <!-- Create announcement Form -->
                <div class="panel-card">
                    <div class="panel-header">
                        <h3>Publier un objet</h3>
                        <i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i>
                    </div>
                    <form id="create-item-form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="item-title">Titre de l'annonce *</label>
                            <input type="text" id="item-title" name="title" placeholder="Ex: Table en chêne" required>
                        </div>
                        <div class="form-group">
                            <label for="item-category">Catégorie *</label>
                            <select id="item-category" name="category_id" required>
                                <option value="">Choisir...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="item-price">Prix (CHF) - Laisser vide pour Don *</label>
                            <input type="number" id="item-price" name="price" min="0" step="1" placeholder="Ex: 150 (vide = Don)">
                        </div>
                        <div class="form-group">
                            <label for="item-location">Lieu de collecte (Adresse/Ville) *</label>
                            <input type="text" id="item-location" name="location" placeholder="Ex: Bulle, Fribourg" required>
                        </div>
                        <div class="form-group">
                            <label for="item-description">Description *</label>
                            <textarea id="item-description" name="description" rows="4" placeholder="État du meuble, dimensions, etc..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="item-photos">Photos de l'objet *</label>
                            <input type="file" id="item-photos" name="photos[]" accept="image/*" multiple required>
                        </div>
                        <div class="form-group" style="flex-direction: row; align-items: center; gap: 8px;">
                            <input type="checkbox" id="request-delivery" name="request_delivery" value="1" style="width: auto; cursor: pointer;">
                            <label for="request-delivery" style="cursor: pointer; margin-bottom: 0;">Je souhaite recevoir une offre de livraison par LIMA</label>
                        </div>
                        <div class="form-group" style="flex-direction: row; align-items: center; gap: 8px; margin-bottom: 1.5rem;">
                            <input type="checkbox" id="request-storage" name="request_storage" value="1" style="width: auto; cursor: pointer;">
                            <label for="request-storage" style="cursor: pointer; margin-bottom: 0;">Je souhaite recevoir une offre de stockage par LIMA</label>
                        </div>
                        <button type="submit" class="btn" style="width: 100%; justify-content: center;"><i class="fa-solid fa-paper-plane"></i> Soumettre pour modération</button>
                    </form>
                </div>

                <!-- Active / Listed Items -->
                <div class="panel-card">
                    <div class="panel-header">
                        <h3>Mes objets publiés</h3>
                        <i class="fa-solid fa-boxes-stacked" style="color: var(--text-light);"></i>
                    </div>
                    <div class="items-list" id="listed-items-container">
                        <p style="color: var(--text-light); text-align: center; padding: 2rem 0;">Chargement de vos annonces...</p>
                    </div>
                </div>
            </div>

            <!-- Demands Section -->
            <div class="grid-layout" style="margin-top: 40px;">
                <!-- Create Demand Form -->
                <div class="panel-card">
                    <div class="panel-header">
                        <h3>Je cherche (Preciso de)</h3>
                        <i class="fa-solid fa-bell" style="color: var(--primary);"></i>
                    </div>
                    <p style="font-size: 13px; color: var(--text-light); margin-bottom: 16px;">
                        Créez une alerte pour être notifié par e-mail lorsqu'un objet correspondant à votre recherche est publié.
                    </p>
                    <form id="create-demand-form">
                        <div class="form-group">
                            <label for="demand-category">Catégorie</label>
                            <select id="demand-category" name="category_id">
                                <option value="">Toutes catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="demand-keywords">Mots-clés</label>
                            <input type="text" id="demand-keywords" name="keywords" placeholder="Ex: Canapé, table, chaise">
                        </div>
                        <div class="form-group">
                            <label for="demand-price">Prix Maximum (CHF)</label>
                            <input type="number" step="0.05" id="demand-price" name="max_price" placeholder="Ex: 200">
                        </div>
                        <div class="form-group">
                            <label for="demand-location">Lieu (Optionnel)</label>
                            <input type="text" id="demand-location" name="location" placeholder="Ex: Lausanne">
                        </div>
                        <div class="form-group">
                            <label for="demand-expires">Expiration (Jours)</label>
                            <select id="demand-expires" name="expires_days">
                                <option value="15">15 jours</option>
                                <option value="30" selected>30 jours</option>
                                <option value="60">60 jours</option>
                            </select>
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn"><i class="fa-solid fa-plus"></i> Créer l'alerte</button>
                        </div>
                    </form>
                </div>

                <!-- Active Demands -->
                <div class="panel-card">
                    <div class="panel-header">
                        <h3>Mes alertes actives</h3>
                        <i class="fa-solid fa-list-check" style="color: var(--text-light);"></i>
                    </div>
                    <div class="items-list" id="demands-container">
                        <p style="color: var(--text-light); text-align: center; padding: 2rem 0;">Chargement...</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        async function loadMyItems() {
            const container = document.getElementById('listed-items-container');
            container.innerHTML = '';

            try {
                const res = await fetch('/api/v1/marketplace/items.php?scope=my_items');
                const data = await res.json();
                
                if (data.success && data.data.items.length > 0) {
                    data.data.items.forEach(item => {
                        const card = document.createElement('div');
                        card.className = 'item-card';

                        const priceLabel = item.price !== null ? `${parseFloat(item.price).toFixed(2)} CHF` : '<span style="color: var(--green-ok); font-weight: 700;">DON</span>';
                        const mainPhoto = item.photos.length > 0 ? item.photos[0] : 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=200';
                        const badgeClass = `badge badge-${item.status.toLowerCase()}`;
                        
                        let actionBtn = '';
                        if (item.status === 'Approved' || item.status === 'Pending') {
                            actionBtn = `<button class="btn secondary" style="padding: 4px 8px; font-size: 11px;" onclick="updateItemStatus(${item.id}, 'Archived')"><i class="fa-solid fa-box-archive"></i> Archiver</button>`;
                        } else if (item.status === 'Archived') {
                            actionBtn = `<button class="btn secondary" style="padding: 4px 8px; font-size: 11px;" onclick="updateItemStatus(${item.id}, 'Pending')"><i class="fa-solid fa-rotate-left"></i> Réactiver</button>`;
                        }

                        let rejectText = '';
                        if (item.status === 'Rejected' && item.rejection_reason) {
                            rejectText = `<p style="color: var(--red-alert); font-weight: 600;">Raison du rejet: ${escapeHtml(item.rejection_reason)}</p>`;
                        }

                        card.innerHTML = `
                            <img src="${mainPhoto}" alt="Photo de l'objet">
                            <div class="item-card-details">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <h4>${escapeHtml(item.title)}</h4>
                                    <span class="${badgeClass}">${item.status}</span>
                                </div>
                                <p><strong>Prix :</strong> ${priceLabel} | <strong>Catégorie :</strong> ${escapeHtml(item.category_name)}</p>
                                <p><strong>Lieu :</strong> ${escapeHtml(item.location)}</p>
                                ${rejectText}
                            </div>
                            <div>
                                ${actionBtn}
                            </div>
                        `;
                        container.appendChild(card);
                    });
                } else {
                    container.innerHTML = '<p style="color: var(--text-light); text-align: center; padding: 3rem 0;">Aucun objet publié pour le moment.</p>';
                }
            } catch (err) {
                console.error("Failed to load listed items", err);
                container.innerHTML = '<p style="color: var(--red-alert); text-align: center; padding: 2rem 0;">Erreur lors du chargement.</p>';
            }
        }

        // Form Submit Create Announcement
        document.getElementById('create-item-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const res = await fetch('/api/v1/marketplace/items.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    e.target.reset();
                    loadMyItems();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert("Une erreur s'est produite lors de la publication.");
            }
        });

        async function updateItemStatus(itemId, newStatus) {
            try {
                const res = await fetch('/api/v1/marketplace/items.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: itemId, status: newStatus })
                });
                const data = await res.json();
                if (data.success) {
                    loadMyItems();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert("Erreur lors de la mise à jour de l'annonce.");
            }
        }

        function escapeHtml(text) {
            if (!text) return "";
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // --- Demands (Preciso de) Logic ---
        async function loadMyDemands() {
            const container = document.getElementById('demands-container');
            container.innerHTML = '';
            
            try {
                const res = await fetch('/api/v1/portal/marketplace_demands.php');
                const data = await res.json();
                
                if (data.success && data.demands.length > 0) {
                    data.demands.forEach(demand => {
                        const card = document.createElement('div');
                        card.className = 'item-card';
                        
                        let details = [];
                        if (demand.category_name) details.push(`Cat: ${escapeHtml(demand.category_name)}`);
                        if (demand.keywords) details.push(`Mots-clés: ${escapeHtml(demand.keywords)}`);
                        if (demand.max_price) details.push(`Max: ${parseFloat(demand.max_price).toFixed(2)} CHF`);
                        if (demand.location) details.push(`Lieu: ${escapeHtml(demand.location)}`);
                        
                        const expireDate = new Date(demand.expires_at).toLocaleDateString('fr-CH');

                        card.innerHTML = `
                            <div class="item-card-details">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <h4>${details.length > 0 ? details[0] : 'Recherche globale'}</h4>
                                        <p>${details.slice(1).join(' | ')}</p>
                                        <p style="font-size: 11px; margin-top: 4px;">Expire le: ${expireDate}</p>
                                    </div>
                                    <button class="btn secondary" style="padding: 4px 8px; font-size: 11px; color: var(--red-alert); border-color: var(--red-alert);" onclick="deleteDemand(${demand.id})"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                        `;
                        container.appendChild(card);
                    });
                } else {
                    container.innerHTML = '<p style="color: var(--text-light); text-align: center; padding: 2rem 0;">Aucune alerte active.</p>';
                }
            } catch (err) {
                container.innerHTML = '<p style="color: var(--red-alert); text-align: center; padding: 2rem 0;">Erreur lors du chargement.</p>';
            }
        }

        document.getElementById('create-demand-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const jsonData = Object.fromEntries(formData.entries());
            
            try {
                const res = await fetch('/api/v1/portal/marketplace_demands.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(jsonData)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    e.target.reset();
                    loadMyDemands();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert("Une erreur s'est produite lors de la création.");
            }
        });

        async function deleteDemand(id) {
            if (!confirm('Supprimer cette alerte ?')) return;
            try {
                const res = await fetch(`/api/v1/portal/marketplace_demands.php?id=${id}`, { method: 'DELETE' });
                const data = await res.json();
                if (data.success) {
                    loadMyDemands();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Erreur lors de la suppression.');
            }
        }

        // Init
        loadMyItems();
        loadMyDemands();
    </script>
</body>
</html>
