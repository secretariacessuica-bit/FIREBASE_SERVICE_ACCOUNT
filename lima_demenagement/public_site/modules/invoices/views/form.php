<?php
// LIMA Solutions ERP - invoices Create & Edit Form View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce module access and permissions (Requires 'edit' write permissions)
enforceModuleAccess('invoices', $userRole, $companyId, 'edit', $pdo);

$invoice = null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Edit mode: fetch invoice from database enforcing company isolation
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id, 'company_id' => $companyId]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        die("Erreur: factures non trouvé ou accès non autorisé.");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $invoice ? 'Modifier' : 'Nouveau'; ?> factures - LIMA Solutions</title>
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
    <!-- invoices style sheet -->
    <link rel="stylesheet" href="../assets/invoices.css">
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
            <li class="sidebar-item active">
                <a href="list.php"><i class="fa-solid fa-calculator"></i> Orçamentos</a>
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

        <!-- Main Form Container -->
        <main class="invoices-container">
            <div class="invoices-header-row">
                <div class="invoices-title-section">
                    <h2><?php echo $invoice ? 'Modifier' : 'Nouveau'; ?> Orçamento / factures</h2>
                    <p><?php echo $invoice ? 'Mettre à jour les informations du factures.' : 'Rédiger une offre commerciale.'; ?></p>
                </div>
            </div>

            <form id="invoices-form">
                <?php if ($invoice): ?>
                    <input type="hidden" id="invoice-id" value="<?php echo (int)$invoice['id']; ?>">
                <?php endif; ?>

                <!-- Row 1: Client & General Info -->
                <div class="invoices-card" style="margin-bottom: 24px;">
                    <h3 class="crm-form-section-title"><i class="fa-solid fa-file-signature"></i> Informations Générales</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div class="crm-form-group">
                            <label for="client_id">Client *</label>
                            <select id="client_id" required data-selected="<?php echo htmlspecialchars($invoice['client_id'] ?? ''); ?>">
                                <option value="">-- Chargement des clients... --</option>
                            </select>
                        </div>
                        <div class="crm-form-group">
                            <label for="status">Statut *</label>
                            <select id="status" required>
                                <option value="Draft" <?php echo ($invoice['status'] ?? '') === 'Draft' ? 'selected' : ''; ?>>Draft / Brouillon</option>
                                <option value="Sent" <?php echo ($invoice['status'] ?? '') === 'Sent' ? 'selected' : ''; ?>>Sent / Envoyé</option>
                                <option value="Accepted" <?php echo ($invoice['status'] ?? '') === 'Accepted' ? 'selected' : ''; ?>>Accepted / Accepté</option>
                                <option value="Rejected" <?php echo ($invoice['status'] ?? '') === 'Rejected' ? 'selected' : ''; ?>>Rejected / Refusé</option>
                                <option value="Expired" <?php echo ($invoice['status'] ?? '') === 'Expired' ? 'selected' : ''; ?>>Expired / Expiré</option>
                            </select>
                        </div>
                        <div class="crm-form-group">
                            <label for="issue_date">Date d'émission *</label>
                            <input type="date" id="issue_date" required value="<?php echo htmlspecialchars($invoice['issue_date'] ?? date('Y-m-d')); ?>">
                        </div>
                        <div class="crm-form-group">
                            <label for="valid_until">Valable jusqu'au *</label>
                            <input type="date" id="valid_until" required value="<?php echo htmlspecialchars($invoice['valid_until'] ?? date('Y-m-d', strtotime('+30 days'))); ?>">
                        </div>
                    </div>
                </div>

                <!-- Row 2: Items Table -->
                <div class="invoices-card" style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-gray); padding-bottom: 10px; margin-bottom: 15px;">
                        <h3 style="font-size: 15px; font-weight: 700; color: var(--primary-teal-dark); margin: 0;"><i class="fa-solid fa-list-ol"></i> Lignes de factures</h3>
                        <button type="button" class="btn-crm btn-crm-primary" id="add-line-btn"><i class="fa-solid fa-plus"></i> Ajouter une ligne</button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Description de la prestation *</th>
                                    <th style="width: 100px; text-align: center;">Qté</th>
                                    <th style="width: 100px; text-align: center;">Unité</th>
                                    <th style="width: 140px; text-align: right;">Prix Unitaire</th>
                                    <th style="width: 120px; text-align: center;">Remise (%)</th>
                                    <th style="width: 160px; text-align: center;">TVA / Taxe</th>
                                    <th style="width: 120px; text-align: right;">Total HT/TTC</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="items-table-body">
                                <!-- Appended dynamically via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Recalculated Summary -->
                    <div class="summary-container">
                        <table class="summary-table">
                            <tr>
                                <td>Sous-Total:</td>
                                <td style="text-align: right;" id="subtotal-display">0.00 CHF</td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Remise générale (%)</td>
                                <td style="text-align: right; width: 150px;">
                                    <input type="number" id="discount_percent" step="0.01" min="0.00" max="100.00" style="width: 80px; padding: 6px; border: 1px solid var(--border-gray); border-radius: var(--border-radius); text-align: right;" value="<?php echo htmlspecialchars($invoice['discount_percent'] ?? '0.00'); ?>">
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-gray);">
                                <td>Montant Remise:</td>
                                <td style="text-align: right;" id="discount-display">0.00 CHF</td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-gray);">
                                <td>Total TVA:</td>
                                <td style="text-align: right;" id="tax-display">0.00 CHF</td>
                            </tr>
                            <tr class="total-row">
                                <td>Total Général:</td>
                                <td style="text-align: right;" id="total-display">0.00 CHF</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Row 3: Notes -->
                <div class="invoices-card" style="margin-bottom: 24px;">
                    <h3 class="crm-form-section-title"><i class="fa-solid fa-message"></i> Notes & Conditions</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="crm-form-group">
                            <label for="notes">Notes publiques (affichées sur le PDF)</label>
                            <textarea id="notes" rows="4" placeholder="Ex: Conditions de paiement, modalités de livraison..."><?php echo htmlspecialchars($invoice['notes'] ?? ''); ?></textarea>
                        </div>
                        <div class="crm-form-group">
                            <label for="internal_notes">Notes internes (confidentiel)</label>
                            <textarea id="internal_notes" rows="4" placeholder="Saisir des notes internes uniquement visibles par le staff..."><?php echo htmlspecialchars($invoice['internal_notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit buttons -->
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <a href="list.php" class="btn-crm" style="padding: 12px 24px; font-size: 14px;">Annuler</a>
                    <button type="submit" class="btn-crm btn-crm-primary" style="padding: 12px 30px; font-size: 14px;">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer l'offre
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
    <script src="../assets/invoices.js"></script>
</body>
</html>
