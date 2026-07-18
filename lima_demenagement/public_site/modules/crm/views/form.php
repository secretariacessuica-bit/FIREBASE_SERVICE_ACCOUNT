<?php
// LIMA Solutions ERP - CRM Clients Create & Edit Form View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce module access and permissions (Requires 'edit' write permissions)
enforceModuleAccess('crm', $userRole, $companyId, 'edit', $pdo);

$client = null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Edit mode: fetch client from database enforcing company isolation
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
    $stmt->execute(['id' => $id, 'company_id' => $companyId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Erreur: Client non trouvé ou accès non autorisé.");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $client ? 'Modifier' : 'Nouveau'; ?> Client - LIMA Solutions</title>
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
    <!-- CRM layout styling -->
    <link rel="stylesheet" href="../assets/crm.css">
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
                <a href="list.php"><i class="fa-solid fa-users"></i> Clientes</a>
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
        <!-- Top Navigation Bar -->
        <header class="navbar">
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <a href="list.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Annuler</a>
            </div>
        </header>

        <!-- Main CRM Container -->
        <main class="crm-container">
            <div class="crm-header-row">
                <div class="crm-title-section">
                    <h2><?php echo $client ? 'Modifier' : 'Nouveau'; ?> Fiche Client</h2>
                    <p><?php echo $client ? 'Mettre à jour les informations du client.' : 'Enregistrer un nouveau client dans votre ERP.'; ?></p>
                </div>
            </div>

            <!-- Form -->
            <form id="crm-client-form">
                <?php if ($client): ?>
                    <input type="hidden" id="client-id" value="<?php echo (int)$client['id']; ?>">
                <?php endif; ?>

                <div class="crm-form-grid">
                    <!-- Column 1: Identité & Contact -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <!-- Section Identification -->
                        <div class="crm-form-section">
                            <h3 class="crm-form-section-title"><i class="fa-solid fa-address-card"></i> Identification</h3>
                            
                            <div class="crm-form-group">
                                <label for="customer_code">Code client (Généré automatiquement si vide)</label>
                                <input type="text" id="customer_code" placeholder="Ex: CLI-000001" value="<?php echo htmlspecialchars($client['customer_code'] ?? ''); ?>" <?php echo $client ? 'readonly' : ''; ?>>
                            </div>

                            <div class="crm-form-group">
                                <label for="name">Nom complet *</label>
                                <input type="text" id="name" placeholder="Ex: Jean Dupont" required value="<?php echo htmlspecialchars($client['name'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="company">Nom de l'entreprise (optionnel)</label>
                                <input type="text" id="company" placeholder="Ex: LIMA Solutions Sàrl" value="<?php echo htmlspecialchars($client['company'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="contact_person">Pessoa de contacto / Interlocuteur</label>
                                <input type="text" id="contact_person" placeholder="Ex: Mme. Sophie Martin" value="<?php echo htmlspecialchars($client['contact_person'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Section Contacts -->
                        <div class="crm-form-section">
                            <h3 class="crm-form-section-title"><i class="fa-solid fa-phone"></i> Contacts</h3>
                            
                            <div class="crm-form-group">
                                <label for="phone">Téléphone fixe</label>
                                <input type="text" id="phone" placeholder="Ex: 021 123 45 67" value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="mobile">Téléphone mobile / Portable</label>
                                <input type="text" id="mobile" placeholder="Ex: 078 123 45 67" value="<?php echo htmlspecialchars($client['mobile'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="whatsapp">WhatsApp (com código de país)</label>
                                <input type="text" id="whatsapp" placeholder="Ex: +41 78 123 45 67" value="<?php echo htmlspecialchars($client['whatsapp'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="email">Adresse e-mail</label>
                                <input type="email" id="email" placeholder="Ex: client@exemple.ch" value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="website">Site internet</label>
                                <input type="text" id="website" placeholder="Ex: www.exemple.ch" value="<?php echo htmlspecialchars($client['website'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Adresse & Fiscal & Autres -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <!-- Section Adresse -->
                        <div class="crm-form-section">
                            <h3 class="crm-form-section-title"><i class="fa-solid fa-map-location-dot"></i> Adresse</h3>
                            
                            <div class="crm-form-group">
                                <label for="address">Adresse (Rue & N°) *</label>
                                <input type="text" id="address" placeholder="Ex: Rue du Simplon 12" required value="<?php echo htmlspecialchars($client['address'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="postal_code">Code postal *</label>
                                <input type="text" id="postal_code" placeholder="Ex: 1003" required value="<?php echo htmlspecialchars($client['postal_code'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="city">Ville *</label>
                                <input type="text" id="city" placeholder="Ex: Lausanne" required value="<?php echo htmlspecialchars($client['city'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="canton">Canton (optionnel)</label>
                                <input type="text" id="canton" placeholder="Ex: Vaud" value="<?php echo htmlspecialchars($client['canton'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="country">Pays *</label>
                                <select id="country" required>
                                    <option value="Suisse" <?php echo ($client['country'] ?? 'Suisse') === 'Suisse' ? 'selected' : ''; ?>>Suisse</option>
                                    <option value="France" <?php echo ($client['country'] ?? '') === 'France' ? 'selected' : ''; ?>>France</option>
                                    <option value="Portugal" <?php echo ($client['country'] ?? '') === 'Portugal' ? 'selected' : ''; ?>>Portugal</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section Données Fiscales -->
                        <div class="crm-form-section">
                            <h3 class="crm-form-section-title"><i class="fa-solid fa-receipt"></i> Données Commerciales</h3>
                            
                            <div class="crm-form-group">
                                <label for="vat_number">NIF / Numéro de TVA (UID)</label>
                                <input type="text" id="vat_number" placeholder="Ex: CHE-123.456.789 MWST" value="<?php echo htmlspecialchars($client['vat_number'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="preferred_language">Langue préférée</label>
                                <select id="preferred_language">
                                    <option value="FR" <?php echo ($client['preferred_language'] ?? 'FR') === 'FR' ? 'selected' : ''; ?>>Français (FR)</option>
                                    <option value="PT" <?php echo ($client['preferred_language'] ?? '') === 'PT' ? 'selected' : ''; ?>>Português (PT)</option>
                                    <option value="EN" <?php echo ($client['preferred_language'] ?? '') === 'EN' ? 'selected' : ''; ?>>English (EN)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section Notes & Tags -->
                        <div class="crm-form-section">
                            <h3 class="crm-form-section-title"><i class="fa-solid fa-tags"></i> Autres Informations</h3>
                            
                            <div class="crm-form-group">
                                <label for="tags">Tags (séparés par des virgules)</label>
                                <input type="text" id="tags" placeholder="Ex: Déménagement, Régulier, VIP" value="<?php echo htmlspecialchars($client['tags'] ?? ''); ?>">
                            </div>

                            <div class="crm-form-group">
                                <label for="notes">Notes Internes</label>
                                <textarea id="notes" rows="4" placeholder="Saisir des notes de suivi sur le client..."><?php echo htmlspecialchars($client['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <a href="list.php" class="btn-crm" style="padding: 12px 24px; font-size: 14px;">Annuler</a>
                    <button type="submit" class="btn-crm btn-crm-primary" style="padding: 12px 30px; font-size: 14px;">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                    </button>
                </div>
            </form>
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
                    if (data.enabled_modules.includes('settings') && (data.user.role === 'super_admin' || data.user.role === 'admin')) {
                        document.getElementById('menu-settings').style.display = 'block';
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
    <script src="../assets/crm.js"></script>
</body>
</html>
