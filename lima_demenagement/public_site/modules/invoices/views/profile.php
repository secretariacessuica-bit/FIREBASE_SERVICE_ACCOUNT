<?php
// LIMA Solutions ERP - invoices Profile View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce module access (Requires at least 'view' permission)
enforceModuleAccess('invoices', $userRole, $companyId, 'view', $pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Erreur: ID de factures invalide.");
}

// Fetch invoice securely checking company_id
$stmt = $pdo->prepare("SELECT q.*, c.name AS client_name, c.company AS client_company, c.phone AS client_phone, c.email AS client_email 
    FROM invoices q
    JOIN clients c ON q.client_id = c.id
    WHERE q.id = :id AND q.company_id = :company_id AND q.deleted_at IS NULL LIMIT 1");
$stmt->execute(['id' => $id, 'company_id' => $companyId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Erreur: factures non trouvé ou accès non autorisé.");
}

// Fetch items
$stmtItems = $pdo->prepare("SELECT qi.*, u.code AS unit_code, t.name AS tax_name, t.rate AS tax_rate 
    FROM invoice_items qi
    LEFT JOIN units u ON qi.unit_id = u.id
    LEFT JOIN tax_rates t ON qi.tax_rate_id = t.id
    WHERE qi.invoice_id = :invoice_id AND qi.company_id = :company_id 
    ORDER BY qi.position ASC");
$stmtItems->execute(['invoice_id' => $id, 'company_id' => $companyId]);
$items = $stmtItems->fetchAll();

// Fetch timeline events for this invoice
$stmtTimeline = $pdo->prepare("SELECT et.*, u.name AS user_name 
    FROM entity_timeline et
    JOIN users u ON et.user_id = u.id
    WHERE et.company_id = :company_id AND et.entity = 'invoices' AND et.entity_id = :entity_id
    ORDER BY et.created_at ASC");
$stmtTimeline->execute(['company_id' => $companyId, 'entity_id' => $id]);
$timelineEvents = $stmtTimeline->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>factures <?php echo htmlspecialchars($invoice['invoice_number']); ?> - LIMA Solutions</title>
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
            <li class="sidebar-item">
                <a href="../../quotes/views/list.php"><i class="fa-solid fa-calculator"></i> Orçamentos</a>
            </li>
            <li class="sidebar-item active" id="menu-invoices">
                <a href="list.php"><i class="fa-solid fa-file-invoice-dollar"></i> Factures</a>
            </li>
            <li class="sidebar-item" id="menu-payments" style="display: none;">
                <a href="../../payments/views/list.php"><i class="fa-solid fa-wallet"></i> Pagamentos</a>
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
        <main class="invoices-container">
            <!-- Header Row -->
            <div class="crm-header-row">
                <div class="crm-title-section">
                    <h2>Facture de Vente: <?php echo htmlspecialchars($invoice['invoice_number']); ?></h2>
                    <p>Suivi et modification de la facture.</p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <!-- Action Buttons -->
                    <button type="button" class="btn-crm btn-crm-primary" onclick="window.open('../../../api/v1/invoices/invoices.php?id=<?php echo $invoice['id']; ?>&pdf=1', '_blank')">
                        <i class="fa-solid fa-file-pdf"></i> Ouvrir PDF
                    </button>

                    <!-- Edit options if edit is allowed -->
                    <div id="edit-btn-container" style="display: none; align-items: center; gap: 8px;">
                        <?php if (in_array($invoice['status'], ['Draft', 'Overdue'])): ?>
                            <a href="form.php?id=<?php echo $invoice['id']; ?>" class="btn-crm"><i class="fa-solid fa-pen"></i> Modifier</a>
                        <?php endif; ?>
                        <select class="company-selector" id="status-switcher" style="padding: 9px 12px; font-size: 12px;">
                            <option value="Draft" <?php echo $invoice['status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Issued" <?php echo $invoice['status'] === 'Issued' ? 'selected' : ''; ?>>Issued / Émise</option>
                            <option value="Sent" <?php echo $invoice['status'] === 'Sent' ? 'selected' : ''; ?>>Sent / Envoyée</option>
                            <option value="Paid" <?php echo $invoice['status'] === 'Paid' ? 'selected' : ''; ?>>Paid / Payée</option>
                            <option value="Partially Paid" <?php echo $invoice['status'] === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid / Part. Payée</option>
                            <option value="Cancelled" <?php echo $invoice['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled / Annulée</option>
                            <option value="Overdue" <?php echo $invoice['status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue / En retard</option>
                        </select>
                    </div>

                    <!-- Add Payment real button -->
                    <?php if (in_array($invoice['status'], ['Issued', 'Sent', 'Partially Paid', 'Overdue'])): ?>
                        <a href="../../payments/views/form.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn-crm" style="background-color: var(--primary-teal-light); color: var(--primary-teal-dark); border-color: var(--primary-teal); text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-wallet"></i> Enregistrer Paiement
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Layout Grid -->
            <div class="crm-profile-layout">
                <!-- Sidebar details -->
                <div class="crm-profile-sidebar">
                    <div class="profile-avatar-card">
                        <span class="badge-status badge-status-<?php echo strtolower($invoice['status']); ?>"><?php echo htmlspecialchars($invoice['status']); ?></span>
                        <h3 class="profile-name" style="margin-top: 12px;"><?php echo htmlspecialchars($invoice['invoice_number']); ?></h3>
                        <p class="profile-code">Total: <?php echo number_format($invoice['total'], 2); ?> <?php echo $invoice['currency']; ?></p>
                    </div>

                    <!-- Client card details -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-user"></i> Client destinaire</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 14px;">
                            <div><strong>Nom / Prénom:</strong> <br><?php echo htmlspecialchars($invoice['client_name']); ?></div>
                            <?php if (!empty($invoice['client_company'])): ?>
                                <div><strong>Entreprise:</strong> <br><?php echo htmlspecialchars($invoice['client_company']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($invoice['client_phone'])): ?>
                                <div><strong>Téléphone:</strong> <br><?php echo htmlspecialchars($invoice['client_phone']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($invoice['client_email'])): ?>
                                <div><strong>Email:</strong> <br><?php echo htmlspecialchars($invoice['client_email']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Meta dates -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-calendar"></i> Dates importantes</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 14px;">
                            <div><strong>Date d'émission:</strong> <br><?php echo date('d.m.Y', strtotime($invoice['issue_date'])); ?></div>
                            <div><strong>Date d'échéance:</strong> <br><?php echo date('d.m.Y', strtotime($invoice['due_date'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Main profile elements -->
                <div class="crm-profile-main">
                    <!-- Items breakdown -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-list-ol"></i> Prestations incluses</h3>
                        <div style="overflow-x: auto;">
                            <table class="crm-table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th style="width: 80px; text-align: center;">Qté</th>
                                        <th style="width: 100px; text-align: right;">P.U.</th>
                                        <th style="width: 80px; text-align: center;">Rem. (%)</th>
                                        <th style="width: 120px; text-align: right;">Total HT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                                            <td style="text-align: center;"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_code'] ?? 'pcs'); ?></td>
                                            <td style="text-align: right;"><?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td style="text-align: center;"><?php echo number_format($item['discount_percent'], 2); ?>%</td>
                                            <td style="text-align: right; font-weight: 600;"><?php echo number_format($item['subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary box -->
                        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                            <table style="width: 300px; font-size: 14px;">
                                <tr style="border-bottom: 1px solid var(--border-gray);">
                                    <td style="padding: 6px 0;">Sous-Total HT:</td>
                                    <td style="text-align: right; padding: 6px 0;"><?php echo number_format($invoice['subtotal'], 2); ?> CHF</td>
                                </tr>
                                <?php if ($invoice['discount_amount'] > 0): ?>
                                    <tr style="border-bottom: 1px solid var(--border-gray);">
                                        <td style="padding: 6px 0; color: #ef4444;">Remise générale (<?php echo number_format($invoice['discount_percent'], 2); ?>%):</td>
                                        <td style="text-align: right; padding: 6px 0; color: #ef4444;">- <?php echo number_format($invoice['discount_amount'], 2); ?> CHF</td>
                                    </tr>
                                <?php endif; ?>
                                <tr style="border-bottom: 1px solid var(--border-gray);">
                                    <td style="padding: 6px 0;">Total TVA:</td>
                                    <td style="text-align: right; padding: 6px 0;"><?php echo number_format($invoice['tax_total'], 2); ?> CHF</td>
                                </tr>
                                <tr style="font-weight: 700; font-size: 16px; color: var(--primary-teal-dark);">
                                    <td style="padding: 10px 0;">Total Général:</td>
                                    <td style="text-align: right; padding: 10px 0;"><?php echo number_format($invoice['total'], 2); ?> CHF</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Cancellation Reason -->
                    <?php if (!empty($invoice['cancellation_reason'])): ?>
                        <div class="crm-form-section" style="border-left: 4px solid #ef4444; background-color: #fef2f2;">
                            <h3 class="crm-form-section-title" style="color: #991b1b;"><i class="fa-solid fa-ban"></i> Motif d'annulation</h3>
                            <p style="font-size: 14px; white-space: pre-wrap; color: #991b1b; padding: 12px; font-weight: 500;"><?php echo htmlspecialchars($invoice['cancellation_reason']); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Notes -->
                    <?php if (!empty($invoice['notes'])): ?>
                        <div class="crm-form-section">
                            <h3 class="crm-form-section-title"><i class="fa-solid fa-message"></i> Notes d'accompagnement</h3>
                            <p style="font-size: 14px; white-space: pre-wrap; color: var(--text-dark); background-color: var(--bg-light); padding: 12px; border-radius: var(--border-radius); border: 1px solid var(--border-gray);"><?php echo htmlspecialchars($invoice['notes']); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Timeline -->
                    <div class="crm-form-section">
                        <h3 class="crm-form-section-title"><i class="fa-solid fa-clock-rotate-left"></i> Timeline d'activité</h3>
                        <div style="font-size: 13px; display: flex; flex-direction: column; gap: 12px;">
                            <?php if (empty($timelineEvents)): ?>
                                <p style="color: var(--text-light);">Aucun événement à afficher.</p>
                            <?php else: ?>
                                <?php foreach ($timelineEvents as $event): ?>
                                    <div style="border-left: 2px solid var(--primary-teal); padding-left: 14px; position: relative;">
                                        <div style="font-weight: 700; color: var(--primary-teal-dark);"><?php echo htmlspecialchars($event['description']); ?></div>
                                        <div style="font-size: 11px; color: var(--text-light);">Par: <?php echo htmlspecialchars($event['user_name']); ?> - <?php echo date('d.m.Y H:i', strtotime($event['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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

        // Check active modules and permissions dynamically for sidebar links
        fetch('../../../api/v1/session.php')
            .then(res => res.json())
            .then(data => {
                if (data.authenticated) {
                    csrfToken = data.csrf_token || '';
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

                    // Dynamic access check for Edit tools
                    let hasEditPermission = false;
                    if (data.user.role === 'super_admin') {
                        hasEditPermission = true;
                    } else {
                        const perm = data.permissions.find(p => p.module_name === 'invoices') || data.permissions.find(p => p.module_name === 'invoices');
                        hasEditPermission = perm && parseInt(perm.can_edit) === 1;
                    }

                    if (hasEditPermission) {
                        document.getElementById('edit-btn-container').style.display = 'flex';
                        initializeStatusSwitcher();
                    }
                } else {
                    window.location.href = '../../admin/login.php';
                }
            })
            .catch(err => {
                console.error(err);
                window.location.href = '../../admin/login.php';
            });

        function initializeStatusSwitcher() {
            const switcher = document.getElementById('status-switcher');
            const toast = document.getElementById('toast');

            function showToast(message, type = '') {
                toast.textContent = message;
                toast.className = 'toast show ' + type;
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }

            let previousStatus = switcher.value;
            switcher.addEventListener('change', () => {
                const newStatus = switcher.value;
                let cancellationReason = null;
                
                if (newStatus === 'Cancelled') {
                    const reason = prompt("Veuillez saisir le motif d'annulation de cette facture :");
                    if (reason === null) {
                        switcher.value = previousStatus;
                        return;
                    }
                    if (reason.trim() === '') {
                        alert("Le motif d'annulation est obligatoire.");
                        switcher.value = previousStatus;
                        return;
                    }
                    cancellationReason = reason.trim();
                }

                fetch('../../../api/v1/invoices/invoices.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        action: 'status',
                        id: <?php echo $invoice['id']; ?>,
                        status: newStatus,
                        cancellation_reason: cancellationReason,
                        csrf_token: csrfToken
                    })
                })
                .then(res => res.json())
                .then(resData => {
                    if (resData.success) {
                        showToast(resData.message, 'success');
                        previousStatus = newStatus;
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast(resData.message || 'Erreur lors de la modification.', 'error');
                        switcher.value = previousStatus;
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Erreur lors du traitement.', 'error');
                    switcher.value = previousStatus;
                });
            });
        }
    </script>
</body>
</html>
