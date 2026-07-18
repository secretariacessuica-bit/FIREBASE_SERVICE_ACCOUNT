<?php
// LIMA Solutions ERP - Payments Form View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce module write permission
enforceModuleAccess('payments', $userRole, $companyId, 'edit', $pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$invoiceIdUrl = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
$payment = null;

if ($id > 0) {
    // Edit mode
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = :id AND company_id = :cid AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['id' => $id, 'cid' => $companyId]);
    $payment = $stmt->fetch();
    if (!$payment) {
        die("Erreur: Paiement non trouvé.");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id > 0 ? 'Modifier le Paiement' : 'Enregistrer um Paiement'; ?> - LIMA Solutions</title>
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input, .form-group select, .form-group textarea {
            padding: 10px 12px;
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            outline: none;
            font-family: inherit;
            font-size: 14px;
            background-color: var(--white);
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary-teal);
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
    </aside>

    <!-- Right Content Panel -->
    <div class="main-wrapper">
        <header class="navbar">
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <a href="list.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <main class="payments-container" style="padding: 30px; max-width: 800px; width:100%; margin: 0 auto;">
            <div style="margin-bottom: 25px;">
                <h2 style="font-size:24px; font-weight:700; color:var(--primary-teal-dark);"><?php echo $id > 0 ? 'Modifier le Paiement' : 'Enregistrer um Paiement'; ?></h2>
                <p style="color:var(--text-light); font-size:14px; margin-top:5px;">Remplissez le formulaire ci-dessous pour <?php echo $id > 0 ? 'modifier le' : 'créer un'; ?> règlement.</p>
            </div>

            <div class="payments-card" style="background:var(--white); border: 1px solid var(--border-gray); border-radius: var(--border-radius); padding:30px;">
                <form id="payments-form">
                    <input type="hidden" id="payment-id" value="<?php echo $id; ?>">
                    
                    <div class="form-grid">
                        <!-- Invoice Selection -->
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="invoice_id">Sélectionner la Facture</label>
                            <select id="invoice_id" required <?php echo ($id > 0 || $invoiceIdUrl > 0) ? 'disabled' : ''; ?> data-selected="<?php echo $payment ? $payment['invoice_id'] : $invoiceIdUrl; ?>">
                                <option value="">-- Chargement des factures --</option>
                            </select>
                            <?php if ($id > 0 || $invoiceIdUrl > 0): ?>
                                <input type="hidden" name="invoice_id" value="<?php echo $payment ? $payment['invoice_id'] : $invoiceIdUrl; ?>">
                            <?php endif; ?>
                        </div>

                        <!-- Date -->
                        <div class="form-group">
                            <label for="payment_date">Date de paiement</label>
                            <input type="date" id="payment_date" required value="<?php echo $payment ? $payment['payment_date'] : date('Y-m-d'); ?>">
                        </div>

                        <!-- Amount -->
                        <div class="form-group">
                            <label for="amount">Montant</label>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="number" id="amount" step="0.05" min="0.05" required style="flex-grow:1;" value="<?php echo $payment ? $payment['amount'] : '0.00'; ?>">
                                <span id="currency-label" style="font-weight:700; font-size:14px; color:var(--text-dark); width: 45px;">CHF</span>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="form-group">
                            <label for="payment_method">Méthode de paiement</label>
                            <select id="payment_method" required>
                                <option value="Cash" <?php echo ($payment && $payment['payment_method'] === 'Cash') ? 'selected' : ''; ?>>Cash / Espèces</option>
                                <option value="Bank Transfer" <?php echo ($payment && $payment['payment_method'] === 'Bank Transfer') ? 'selected' : ''; ?>>Virement bancaire</option>
                                <option value="TWINT" <?php echo ($payment && $payment['payment_method'] === 'TWINT') ? 'selected' : ''; ?>>TWINT</option>
                                <option value="Credit Card" <?php echo ($payment && $payment['payment_method'] === 'Credit Card') ? 'selected' : ''; ?>>Carte de crédit</option>
                                <option value="Debit Card" <?php echo ($payment && $payment['payment_method'] === 'Debit Card') ? 'selected' : ''; ?>>Carte de débit</option>
                                <option value="QR-Bill" <?php echo ($payment && $payment['payment_method'] === 'QR-Bill') ? 'selected' : ''; ?>>QR-Bill</option>
                                <option value="Other" <?php echo ($payment && $payment['payment_method'] === 'Other') ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>

                        <!-- Reference -->
                        <div class="form-group">
                            <label for="reference">Référence client / N° Chèque</label>
                            <input type="text" id="reference" value="<?php echo $payment ? htmlspecialchars($payment['reference']) : ''; ?>" placeholder="Ex: Chèque N° 1245">
                        </div>

                        <!-- Transaction Reference -->
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="transaction_reference">Référence de Transaction Bancaire (ID Stripe/Acquirer)</label>
                            <input type="text" id="transaction_reference" value="<?php echo $payment ? htmlspecialchars($payment['transaction_reference']) : ''; ?>" placeholder="Ex: ch_3MthfvLkdypWAdG20xM3R9kE">
                        </div>

                        <!-- Notes -->
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="notes">Notes administratives</label>
                            <textarea id="notes" rows="3" placeholder="Notes optionnelles visibles en interne..."><?php echo $payment ? htmlspecialchars($payment['notes']) : ''; ?></textarea>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:30px; border-top:1px solid var(--border-gray); padding-top:20px;">
                        <a href="list.php" class="btn-crm" style="text-decoration:none;">Annuler</a>
                        <button type="submit" class="btn-crm btn-crm-primary" style="border:none; cursor:pointer;">Enregistrer le Paiement</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        // Check active session
        let csrfToken = '';
        fetch('../../../api/v1/session.php')
            .then(res => res.json())
            .then(data => {
                if (data.authenticated) {
                    csrfToken = data.csrf_token || '';
                    document.getElementById('user-display-name').textContent = data.user.name;

                    // Color theme
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
                } else {
                    window.location.href = '../../admin/login.php';
                }
            });
    </script>
    <script src="../assets/payments.js"></script>
</body>
</html>
