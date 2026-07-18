<?php
// LIMA Solutions ERP - CRM Client Profile View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce module access and permissions (Requires at least 'view' permission)
enforceModuleAccess('crm', $userRole, $companyId, 'view', $pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Erreur: ID client invalide.");
}

// Fetch client details securely matching company_id
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
$stmt->execute(['id' => $id, 'company_id' => $companyId]);
$client = $stmt->fetch();

if (!$client) {
    die("Erreur: Client non trouvé ou accès non autorisé.");
}

// Fetch client quotes
$stmtQuotes = $pdo->prepare("SELECT * FROM quotes WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL ORDER BY created_at DESC");
$stmtQuotes->execute(['client_id' => $id, 'company_id' => $companyId]);
$clientQuotes = $stmtQuotes->fetchAll();

// Fetch client invoices
$stmtInvoices = $pdo->prepare("SELECT * FROM invoices WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL ORDER BY created_at DESC");
$stmtInvoices->execute(['client_id' => $id, 'company_id' => $companyId]);
$clientInvoices = $stmtInvoices->fetchAll();

// Calculate total invoiced (excluding Draft & Cancelled)
$stmtTotalInvoiced = $pdo->prepare("SELECT SUM(total) FROM invoices WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL AND status NOT IN ('Draft', 'Cancelled')");
$stmtTotalInvoiced->execute(['client_id' => $id, 'company_id' => $companyId]);
$totalInvoiced = (float)($stmtTotalInvoiced->fetchColumn() ?? 0.00);

// Fetch client payments
$stmtPayments = $pdo->prepare("SELECT p.*, i.invoice_number FROM payments p 
    JOIN invoices i ON p.invoice_id = i.id 
    WHERE i.client_id = :client_id AND p.company_id = :company_id AND p.deleted_at IS NULL AND p.reversed_at IS NULL ORDER BY p.payment_date DESC");
$stmtPayments->execute(['client_id' => $id, 'company_id' => $companyId]);
$clientPayments = $stmtPayments->fetchAll();

// Extract initials for avatar placeholder
$words = explode(' ', $client['name']);
$initials = '';
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Client - <?php echo htmlspecialchars($client['name']); ?> - LIMA Solutions</title>
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
                <a href="list.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <!-- Main CRM Container -->
        <main class="crm-container">
            <!-- Header Row -->
            <div class="crm-header-row">
                <div class="crm-title-section">
                    <h2>Fiche Client: <?php echo htmlspecialchars($client['name']); ?></h2>
                    <p>Profil complet et historique des transactions du client.</p>
                </div>
                <div id="edit-btn-container" style="display: none;">
                    <a href="form.php?id=<?php echo $client['id']; ?>" class="btn-crm btn-crm-primary" style="padding: 10px 20px; font-size: 14px;">
                        <i class="fa-solid fa-pen"></i> Modifier Fiche
                    </a>
                </div>
            </div>

            <!-- Profile Layout Grid -->
            <div class="crm-profile-layout">
                <!-- Sidebar Info Cards -->
                <div class="crm-profile-sidebar">
                    <!-- Avatar Card -->
                    <div class="profile-avatar-card">
                        <div class="profile-avatar"><?php echo $initials; ?></div>
                        <h3 class="profile-name"><?php echo htmlspecialchars($client['name']); ?></h3>
                        <p class="profile-code"><?php echo htmlspecialchars($client['customer_code']); ?></p>
                        
                        <?php if (!empty($client['tags'])): ?>
                            <div style="margin-top: 15px; display: flex; flex-wrap: wrap; justify-content: center; gap: 4px;">
                                <?php foreach(explode(',', $client['tags']) as $tag): ?>
                                    <span class="crm-badge-tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Details Box -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-circle-info"></i> Coordonnées</h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                            <?php if (!empty($client['company'])): ?>
                                <div><strong style="color: var(--text-light);">Entreprise:</strong> <br><?php echo htmlspecialchars($client['company']); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($client['contact_person'])): ?>
                                <div><strong style="color: var(--text-light);">Interlocuteur:</strong> <br><?php echo htmlspecialchars($client['contact_person']); ?></div>
                            <?php endif; ?>

                            <div>
                                <strong style="color: var(--text-light);">Téléphones:</strong> <br>
                                <?php if (!empty($client['phone'])): ?>
                                    <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($client['phone']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($client['mobile'])): ?>
                                    <i class="fa-solid fa-mobile"></i> <?php echo htmlspecialchars($client['mobile']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($client['whatsapp'])): ?>
                                    <i class="fa-brands fa-whatsapp" style="color: #25d366;"></i> <?php echo htmlspecialchars($client['whatsapp']); ?>
                                <?php endif; ?>
                                <?php if (empty($client['phone']) && empty($client['mobile']) && empty($client['whatsapp'])): ?>
                                    -
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($client['email'])): ?>
                                <div><strong style="color: var(--text-light);">E-mail:</strong> <br><i class="fa-solid fa-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" style="color: var(--primary-teal); text-decoration: none;"><?php echo htmlspecialchars($client['email']); ?></a></div>
                            <?php endif; ?>

                            <?php if (!empty($client['website'])): ?>
                                <div><strong style="color: var(--text-light);">Site internet:</strong> <br><i class="fa-solid fa-globe"></i> <a href="http://<?php echo htmlspecialchars($client['website']); ?>" target="_blank" style="color: var(--primary-teal); text-decoration: none;"><?php echo htmlspecialchars($client['website']); ?></a></div>
                            <?php endif; ?>

                            <div>
                                <strong style="color: var(--text-light);">Adresse de facturation:</strong> <br>
                                <?php echo htmlspecialchars($client['address']); ?><br>
                                <?php echo htmlspecialchars($client['postal_code']); ?> <?php echo htmlspecialchars($client['city']); ?><br>
                                <?php echo htmlspecialchars($client['canton'] ?? ''); ?> (<?php echo htmlspecialchars($client['country']); ?>)
                            </div>

                            <?php if (!empty($client['vat_number'])): ?>
                                <div><strong style="color: var(--text-light);">NIF / UID (TVA):</strong> <br><?php echo htmlspecialchars($client['vat_number']); ?></div>
                            <?php endif; ?>

                            <div><strong style="color: var(--text-light);">Langue préférée:</strong> <br><?php echo $client['preferred_language'] === 'FR' ? 'Français' : ($client['preferred_language'] === 'PT' ? 'Português' : 'English'); ?></div>
                        </div>
                    </div>

                    <!-- Portal Access Box -->
                    <div class="crm-form-section" id="portal-access-section" style="margin-top: 20px;">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-key"></i> Accès Portail</h3>
                        
                        <div id="portal-loading" style="text-align: center; padding: 15px; color: var(--text-light);">
                            <i class="fa-solid fa-spinner fa-spin"></i> Chargement...
                        </div>
                        
                        <div id="portal-not-created" style="display: none;">
                            <p style="font-size: 13px; color: var(--text-light); margin-bottom: 12px;">Ce client n'a pas encore d'accès au portail.</p>
                            <button id="btn-show-create-portal" class="btn-crm btn-crm-primary" style="width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-user-plus"></i> Créer un accès
                            </button>
                            
                            <form id="form-create-portal" style="display: none; margin-top: 15px; flex-direction: column; gap: 10px;">
                                <div class="crm-form-group" style="margin-bottom: 10px;">
                                    <label>Nom d'utilisateur</label>
                                    <input type="text" id="portal-create-name" value="<?php echo htmlspecialchars($client['contact_person'] ?: $client['name']); ?>" required>
                                </div>
                                <div class="crm-form-group" style="margin-bottom: 10px;">
                                    <label>E-mail de connexion</label>
                                    <input type="email" id="portal-create-email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                                </div>
                                <div class="crm-form-group" style="margin-bottom: 10px;">
                                    <label>Mot de passe temporaire</label>
                                    <div style="display: flex; gap: 6px;">
                                        <input type="text" id="portal-create-password" required>
                                        <button type="button" id="btn-gen-create-password" class="btn-crm" style="padding: 0 10px;" title="Générer"><i class="fa-solid fa-random"></i></button>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 6px; margin-top: 5px;">
                                    <button type="submit" class="btn-crm btn-crm-primary" style="flex: 1; justify-content: center;">Enregistrer</button>
                                    <button type="button" id="btn-cancel-create-portal" class="btn-crm" style="flex: 1; justify-content: center;">Annuler</button>
                                </div>
                            </form>
                        </div>
                        
                        <div id="portal-created" style="display: none; font-size: 13px; display: flex; flex-direction: column; gap: 12px;">
                            <div>
                                <strong style="color: var(--text-light);">Nom:</strong> <span id="portal-user-name">-</span>
                            </div>
                            <div>
                                <strong style="color: var(--text-light);">E-mail:</strong> <span id="portal-user-email">-</span>
                            </div>
                            <div>
                                <strong style="color: var(--text-light);">Statut:</strong> <span id="portal-user-status">-</span>
                            </div>
                            <div>
                                <strong style="color: var(--text-light);">Dernière connexion:</strong> <span id="portal-user-last-login">-</span>
                            </div>
                            <div style="display: flex; gap: 6px; margin-top: 5px;">
                                <button type="button" id="btn-toggle-portal" class="btn-crm" style="flex: 1; justify-content: center;">
                                    <i class="fa-solid fa-ban"></i> Désactiver
                                </button>
                                <button type="button" id="btn-show-reset-portal" class="btn-crm btn-crm-primary" style="flex: 1; justify-content: center;">
                                    <i class="fa-solid fa-key"></i> Réinitialiser
                                </button>
                            </div>
                            
                            <form id="form-reset-portal" style="display: none; margin-top: 10px; flex-direction: column; gap: 10px; border-top: 1px solid var(--border-gray); padding-top: 10px;">
                                <div class="crm-form-group" style="margin-bottom: 10px;">
                                    <label>Nouveau mot de passe temporaire</label>
                                    <div style="display: flex; gap: 6px;">
                                        <input type="text" id="portal-reset-password" required>
                                        <button type="button" id="btn-gen-reset-password" class="btn-crm" style="padding: 0 10px;" title="Générer"><i class="fa-solid fa-random"></i></button>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 6px;">
                                    <button type="submit" class="btn-crm btn-crm-primary" style="flex: 1; justify-content: center;">Confirmer</button>
                                    <button type="button" id="btn-cancel-reset-portal" class="btn-crm" style="flex: 1; justify-content: center;">Annuler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Main Section Tabular lists -->
                <div class="crm-profile-main">
                    <!-- Direct Client Chat Box -->
                    <div class="crm-form-section" style="margin-bottom: 20px;">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-comments"></i> Discussion Directe Client</h3>
                        
                        <div id="admin-chat-messages" style="height: 250px; overflow-y: auto; background-color: var(--bg-light); border: 1px solid var(--border-gray); border-radius: var(--border-radius); padding: 15px; display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px;">
                            <div style="text-align: center; color: var(--text-light); padding-top: 50px;">
                                <i class="fa-solid fa-spinner fa-spin"></i> Chargement des messages...
                            </div>
                        </div>
                        
                        <form id="admin-form-chat" style="display: flex; gap: 8px;">
                            <input type="text" id="admin-chat-input" class="crm-input" style="flex-grow: 1; padding: 10px; border: 1px solid var(--border-gray); border-radius: var(--border-radius); font-size: 14px;" placeholder="Écrire un message..." required autocomplete="off">
                            <button type="submit" class="btn-crm btn-crm-primary" style="padding: 10px 15px;"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                    </div>

                    <!-- Internal Notes -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-pen-nib"></i> Notes internes</h3>
                        <p style="font-size: 14px; line-height: 1.6; white-space: pre-wrap; color: var(--text-dark); background-color: var(--bg-light); padding: 15px; border-radius: var(--border-radius); border: 1px solid var(--border-gray);"><?php echo !empty($client['notes']) ? htmlspecialchars($client['notes']) : 'Aucune note enregistrée sur ce client.'; ?></p>
                    </div>

                    <!-- Total Invoiced Box -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-chart-line"></i> Total facturé</h3>
                        <div style="font-size: 28px; font-weight: 700; color: var(--primary-teal-dark); padding: 10px 0;">
                            <?php echo number_format($totalInvoiced, 2); ?> CHF
                        </div>
                    </div>

                    <!-- Invoices History -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-file-invoice-dollar"></i> Historique des Factures</h3>
                        <?php if (empty($clientInvoices)): ?>
                            <div class="placeholder-box">
                                <i class="fa-solid fa-receipt"></i>
                                <p style="font-size: 14px; font-weight: 500;">Aucune facture liée.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="crm-table">
                                    <thead>
                                        <tr>
                                            <th>Numéro</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th style="text-align: right;">Total</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientInvoices as $inv): ?>
                                            <tr>
                                                <td><span class="crm-badge-code"><?php echo htmlspecialchars($inv['invoice_number']); ?></span></td>
                                                <td><?php echo date('d.m.Y', strtotime($inv['issue_date'])); ?></td>
                                                <td><span class="badge-status badge-status-<?php echo strtolower($inv['status']); ?>"><?php echo htmlspecialchars($inv['status']); ?></span></td>
                                                <td style="text-align: right; font-weight: 600;"><?php echo number_format($inv['total'], 2); ?> <?php echo htmlspecialchars($inv['currency']); ?></td>
                                                <td style="text-align: center;">
                                                    <a href="../../invoices/views/profile.php?id=<?php echo $inv['id']; ?>" class="btn-crm" style="font-size: 11px; padding: 5px 10px;"><i class="fa-solid fa-eye"></i> Voir</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quotes History -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-calculator"></i> Historique des Devis / Orçamentos</h3>
                        <?php if (empty($clientQuotes)): ?>
                            <div class="placeholder-box">
                                <i class="fa-solid fa-file-signature"></i>
                                <p style="font-size: 14px; font-weight: 500;">Aucun devis lié.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="crm-table">
                                    <thead>
                                        <tr>
                                            <th>Numéro</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th style="text-align: right;">Total</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientQuotes as $qt): ?>
                                            <tr>
                                                <td><span class="crm-badge-code"><?php echo htmlspecialchars($qt['quote_number']); ?></span></td>
                                                <td><?php echo date('d.m.Y', strtotime($qt['issue_date'])); ?></td>
                                                <td><span class="badge-status badge-status-<?php echo strtolower($qt['status']); ?>"><?php echo htmlspecialchars($qt['status']); ?></span></td>
                                                <td style="text-align: right; font-weight: 600;"><?php echo number_format($qt['total'], 2); ?> <?php echo htmlspecialchars($qt['currency']); ?></td>
                                                <td style="text-align: center;">
                                                    <a href="../../quotes/views/profile.php?id=<?php echo $qt['id']; ?>" class="btn-crm" style="font-size: 11px; padding: 5px 10px;"><i class="fa-solid fa-eye"></i> Voir</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Payments History -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-wallet"></i> Historique des Paiements</h3>
                        <?php if (empty($clientPayments)): ?>
                            <div class="placeholder-box">
                                <i class="fa-solid fa-money-bill-transfer"></i>
                                <p style="font-size: 14px; font-weight: 500;">Aucun paiement enregistré.</p>
                            </div>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="crm-table">
                                    <thead>
                                        <tr>
                                            <th>Réf Paiement</th>
                                            <th>Facture</th>
                                            <th>Date</th>
                                            <th>Méthode</th>
                                            <th style="text-align: right;">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientPayments as $pay): ?>
                                            <tr>
                                                <td><span class="crm-badge-code"><?php echo htmlspecialchars($pay['payment_number']); ?></span></td>
                                                <td><span class="crm-badge-code"><?php echo htmlspecialchars($pay['invoice_number']); ?></span></td>
                                                <td><?php echo date('d.m.Y', strtotime($pay['payment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                                <td style="text-align: right; font-weight: 600; color: #10b981;"><?php echo number_format($pay['amount'], 2); ?> <?php echo htmlspecialchars($pay['currency']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Timeline de atividades Placeholder -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-clock-rotate-left"></i> Timeline d'Activités (Placeholder)</h3>
                        <div style="font-size: 13px; color: var(--text-dark); display: flex; flex-direction: column; gap: 12px; margin-top: 10px;">
                            <div style="border-left: 2px solid var(--primary-teal); padding-left: 14px; position: relative;">
                                <div style="font-weight: 700; color: var(--primary-teal-dark);">Création du profil client</div>
                                <div style="font-size: 11px; color: var(--text-light);">Par: Système - Date de création du dossier</div>
                            </div>
                            <div style="border-left: 2px solid var(--border-gray); padding-left: 14px; position: relative;">
                                <div style="font-weight: 600; color: var(--text-light);">Aucune interaction récente</div>
                                <div style="font-size: 11px; color: var(--text-light);">L'intégration des e-mails, devis et appels sera configurée ultérieurement.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Notification Toast -->
    <div class="toast" id="toast"></div>

    <script>
        let csrfToken = '';
        const clientId = <?php echo $id; ?>;
        const toast = document.getElementById('toast');

        function showToast(message, type = '') {
            toast.textContent = message;
            toast.className = 'toast show ' + type;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function generatePassword() {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
            let pwd = '';
            for (let i = 0; i < 10; i++) {
                pwd += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return pwd;
        }

        function loadPortalUser() {
            document.getElementById('portal-loading').style.display = 'block';
            document.getElementById('portal-not-created').style.display = 'none';
            document.getElementById('portal-created').style.display = 'none';

            fetch(`../../../api/v1/crm/clients.php?action=get_client_user&client_id=${clientId}`)
                .then(res => res.json())
                .then(res => {
                    document.getElementById('portal-loading').style.display = 'none';
                    if (res.success && res.data && res.data.client_user) {
                        const u = res.data.client_user;
                        document.getElementById('portal-created').style.display = 'flex';
                        document.getElementById('portal-user-name').textContent = u.name;
                        document.getElementById('portal-user-email').textContent = u.email;
                        
                        const statusSpan = document.getElementById('portal-user-status');
                        const toggleBtn = document.getElementById('btn-toggle-portal');
                        if (parseInt(u.active) === 1) {
                            statusSpan.innerHTML = '<span class="badge-status badge-status-paid" style="background-color: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 11px;">Actif</span>';
                            toggleBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Désactiver';
                            toggleBtn.className = 'btn-crm btn-crm-danger';
                            toggleBtn.onclick = () => togglePortalAccess(0);
                        } else {
                            statusSpan.innerHTML = '<span class="badge-status badge-status-cancelled" style="background-color: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 11px;">Inactif</span>';
                            toggleBtn.innerHTML = '<i class="fa-solid fa-check"></i> Activer';
                            toggleBtn.className = 'btn-crm btn-crm-primary';
                            toggleBtn.onclick = () => togglePortalAccess(1);
                        }

                        document.getElementById('portal-user-last-login').textContent = u.last_login ? u.last_login : 'Jamais connecté';
                    } else {
                        document.getElementById('portal-not-created').style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Error loading portal user:', err);
                    document.getElementById('portal-loading').textContent = 'Erreur de chargement.';
                });
        }

        function togglePortalAccess(active) {
            fetch('../../../api/v1/crm/clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'toggle_client_user',
                    client_id: clientId,
                    active: active,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showToast(res.message, 'success');
                    loadPortalUser();
                } else {
                    showToast(res.message || 'Erreur.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur réseau.', 'error');
            });
        }

        // Check active modules and permissions dynamically for sidebar links
        fetch('../../../api/v1/session.php')
            .then(res => res.json())
            .then(data => {
                if (data.authenticated) {
                    document.getElementById('user-display-name').textContent = data.user.name;
                    csrfToken = data.csrf_token || '';
                    
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

                    // Dynamic access check for "Modifier Fiche" button
                    let hasEditPermission = false;
                    if (data.user.role === 'super_admin') {
                        hasEditPermission = true;
                    } else {
                        const perm = data.permissions.find(p => p.module_name === 'crm');
                        hasEditPermission = perm && parseInt(perm.can_edit) === 1;
                    }

                    if (hasEditPermission) {
                        document.getElementById('edit-btn-container').style.display = 'block';
                    }

                    // Load client portal user details if authenticated
                    loadPortalUser();
                } else {
                    window.location.href = '../../admin/login.php';
                }
            })
            .catch(err => {
                console.error(err);
                window.location.href = '../../admin/login.php';
            });

        // Set up event listeners for creation form
        document.getElementById('btn-show-create-portal').addEventListener('click', () => {
            document.getElementById('btn-show-create-portal').style.display = 'none';
            document.getElementById('form-create-portal').style.display = 'flex';
            document.getElementById('portal-create-password').value = generatePassword();
        });
        document.getElementById('btn-cancel-create-portal').addEventListener('click', () => {
            document.getElementById('btn-show-create-portal').style.display = 'block';
            document.getElementById('form-create-portal').style.display = 'none';
        });
        document.getElementById('btn-gen-create-password').addEventListener('click', () => {
            document.getElementById('portal-create-password').value = generatePassword();
        });

        // Set up event listeners for reset form
        document.getElementById('btn-show-reset-portal').addEventListener('click', () => {
            document.getElementById('form-reset-portal').style.display = 'flex';
            document.getElementById('portal-reset-password').value = generatePassword();
        });
        document.getElementById('btn-cancel-reset-portal').addEventListener('click', () => {
            document.getElementById('form-reset-portal').style.display = 'none';
        });
        document.getElementById('btn-gen-reset-password').addEventListener('click', () => {
            document.getElementById('portal-reset-password').value = generatePassword();
        });

        // Form submission for creation
        document.getElementById('form-create-portal').addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('portal-create-name').value;
            const email = document.getElementById('portal-create-email').value;
            const password = document.getElementById('portal-create-password').value;

            fetch('../../../api/v1/crm/clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'save_client_user',
                    client_id: clientId,
                    name: name,
                    email: email,
                    password: password,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showToast(res.message, 'success');
                    document.getElementById('form-create-portal').reset();
                    document.getElementById('form-create-portal').style.display = 'none';
                    document.getElementById('btn-show-create-portal').style.display = 'block';
                    loadPortalUser();
                } else {
                    showToast(res.message || 'Erreur lors de la création.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur réseau.', 'error');
            });
        });

        // Form submission for reset
        document.getElementById('form-reset-portal').addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('portal-user-name').textContent;
            const email = document.getElementById('portal-user-email').textContent;
            const password = document.getElementById('portal-reset-password').value;

            fetch('../../../api/v1/crm/clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'save_client_user',
                    client_id: clientId,
                    name: name,
                    email: email,
                    password: password,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    showToast(res.message, 'success');
                    document.getElementById('form-reset-portal').reset();
                    document.getElementById('form-reset-portal').style.display = 'none';
                    loadPortalUser();
                } else {
                    showToast(res.message || 'Erreur lors de la réinitialisation.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur réseau.', 'error');
            });
        });

        // Administrative direct chat logic
        const adminChatMessages = document.getElementById('admin-chat-messages');
        const adminChatInput = document.getElementById('admin-chat-input');
        let lastAdminMessageCount = 0;

        function loadAdminMessages() {
            fetch(`../../../api/v1/crm/clients.php?action=get_client_messages&client_id=${clientId}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data && res.data.messages) {
                        const msgs = res.data.messages;
                        if (msgs.length === 0) {
                            adminChatMessages.innerHTML = '<div style="text-align: center; color: var(--text-light); padding-top: 50px;">Aucun message échangé.</div>';
                            lastAdminMessageCount = 0;
                            return;
                        }

                        let html = '';
                        msgs.forEach(m => {
                            const isStaff = m.sender_type === 'staff';
                            const alignment = isStaff ? 'flex-end' : 'flex-start';
                            const bgColor = isStaff ? 'var(--primary-teal-light)' : 'var(--white)';
                            const borderStyle = isStaff ? 'none' : '1px solid var(--border-gray)';
                            const label = isStaff ? 'Moi' : 'Client';
                            const date = new Date(m.created_at);
                            const formattedTime = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) + ' ' + date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });

                            html += `
                                <div style="display: flex; justify-content: ${alignment}; width: 100%;">
                                    <div style="max-width: 70%; background-color: ${bgColor}; border: ${borderStyle}; padding: 10px 14px; border-radius: 12px; font-size: 13px; line-height: 1.4; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                        <div style="white-space: pre-wrap;">${escapeHtml(m.message)}</div>
                                        <div style="font-size: 9px; color: var(--text-light); margin-top: 4px; text-align: right;">
                                            <strong>${label}</strong> - ${formattedTime}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        adminChatMessages.innerHTML = html;

                        if (msgs.length > lastAdminMessageCount) {
                            adminChatMessages.scrollTop = adminChatMessages.scrollHeight;
                            lastAdminMessageCount = msgs.length;
                        }
                    }
                })
                .catch(err => {
                    console.error('Error fetching admin messages:', err);
                });
        }

        document.getElementById('admin-form-chat').addEventListener('submit', (e) => {
            e.preventDefault();
            const message = adminChatInput.value.trim();
            if (!message) return;

            adminChatInput.disabled = true;

            fetch('../../../api/v1/crm/clients.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'send_client_message',
                    client_id: clientId,
                    message: message,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(res => {
                adminChatInput.disabled = false;
                if (res.success) {
                    adminChatInput.value = '';
                    loadAdminMessages();
                } else {
                    showToast(res.message || 'Erreur lors de l\'envoi.', 'error');
                }
            })
            .catch(err => {
                adminChatInput.disabled = false;
                console.error(err);
                showToast('Erreur réseau.', 'error');
            });
        });

        // Initialize admin chat load and polling
        loadAdminMessages();
        setInterval(loadAdminMessages, 5000);
    </script>
</body>
</html>
