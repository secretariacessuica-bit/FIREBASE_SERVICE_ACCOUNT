<?php
// LIMA Solutions ERP - Client Portal Invoices View
require_once 'auth.php';

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

// Fetch client invoices
$stmtInvoices = $pdo->prepare("SELECT * FROM invoices WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL ORDER BY created_at DESC");
$stmtInvoices->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$clientInvoices = $stmtInvoices->fetchAll();

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
    <title>Mes Factures - <?php echo htmlspecialchars($companyName); ?></title>
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
            transition: var(--transition);
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

        .portal-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .portal-table th {
            color: var(--text-light);
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border-gray);
            font-size: 12px;
            text-transform: uppercase;
        }

        .portal-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-gray);
        }

        .badge-status {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-draft { background-color: #f1f5f9; color: #475569; }
        .badge-sent { background-color: #fef3c7; color: #d97706; }
        .badge-partially_paid { background-color: #dbeafe; color: #1d4ed8; }
        .badge-paid { background-color: #d1fae5; color: #065f46; }
        .badge-cancelled { background-color: #fee2e2; color: #b91c1c; }
        .badge-overdue { background-color: #fee2e2; color: #b91c1c; font-weight: 700; }

        .btn-crm {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-gray);
            cursor: pointer;
            background-color: var(--white);
            color: var(--text-dark);
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }

        .btn-crm:hover {
            background-color: var(--bg-light);
        }

        .btn-pay {
            background-color: #10b981 !important;
            color: #ffffff !important;
            border-color: #059669 !important;
            margin-left: 6px;
        }

        .btn-pay:hover {
            background-color: #059669 !important;
        }

        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background-color: #334155;
            color: #white;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transform: translateY(20px);
            transition: var(--transition);
            z-index: 10000;
            pointer-events: none;
            color: #ffffff;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.success {
            background-color: #10b981;
        }

        .toast.error {
            background-color: #ef4444;
        }

        .placeholder-box {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .placeholder-box i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
    </style>

</head>
<body>

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
            <li class="sidebar-item active">
                <a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Mes Factures</a>
            </li>
            <li class="sidebar-item">
                <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            </li>
            <li class="sidebar-item">
                <a href="guide.php"><i class="fa-solid fa-circle-question"></i> Guide d'utilisation</a>
            </li>
            <li class="sidebar-item">
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

    <div class="main-wrapper">
        <header class="navbar">
            <h1>Mes Factures / Faturas</h1>
        </header>

        <main class="content-container">
            <div class="panel-card">
                <?php if (empty($clientInvoices)): ?>
                    <div class="placeholder-box">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <p>Aucune facture disponible pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date d'émission</th>
                                    <th>Date d'échéance</th>
                                    <th>Statut</th>
                                    <th style="text-align: right;">Total</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientInvoices as $inv): ?>
                                    <tr>
                                        <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                        <td><?php echo date('d.m.Y', strtotime($inv['issue_date'])); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($inv['due_date'])); ?></td>
                                        <td>
                                            <?php 
                                                $isOverdue = (strtotime($inv['due_date']) < time() && $inv['status'] !== 'Paid' && $inv['status'] !== 'Cancelled');
                                                if ($isOverdue) {
                                                    echo '<span class="badge-status badge-overdue">En retard</span>';
                                                } else {
                                                    echo '<span class="badge-status badge-' . strtolower(str_replace(' ', '_', $inv['status'])) . '">' . htmlspecialchars($inv['status']) . '</span>';
                                                }
                                            ?>
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            <?php echo number_format($inv['total'], 2); ?> <?php echo htmlspecialchars($inv['currency']); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="/api/v1/portal/invoices.php?id=<?php echo $inv['id']; ?>&pdf=1" target="_blank" class="btn-crm">
                                                <i class="fa-solid fa-file-pdf"></i> PDF
                                            </a>
                                            <?php if ((float)$inv['balance_due'] > 0 && !in_array($inv['status'], ['Draft', 'Cancelled'])): ?>
                                                <button class="btn-crm btn-pay" onclick="payInvoice(<?php echo $inv['id']; ?>, event)">
                                                    <i class="fa-solid fa-credit-card"></i> Pagar Online
                                                </button>
                                            <?php endif; ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <div class="toast" id="toast"></div>

    <script>
        function showToast(message, type = '') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast show ' + type;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 5000);
        }

        function payInvoice(invoiceId, event) {
            const btn = event ? event.target.closest('button') : null;
            if (btn) btn.disabled = true;

            showToast('Redirection vers Stripe Checkout...', 'info');

            fetch('../api/v1/payments/create-session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ invoice_id: invoiceId, provider: 'stripe' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    showToast(data.message || 'Erreur lors de la création de la session.', 'error');
                    if (btn) btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur de connection.', 'error');
                if (btn) btn.disabled = false;
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Handle mock simulation in local development env
            const mockCheckout = urlParams.get('mock_checkout');
            if (mockCheckout) {
                if (confirm("Simulate Stripe Mock Payment Checkout for this session? (Local Dev only)")) {
                    fetch('../api/v1/payments/webhook.php?mock_checkout=' + mockCheckout, {
                        method: 'POST'
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'invoices.php?status=success';
                        } else {
                            alert("Simulation error: " + data.message);
                        }
                    });
                }
            }

            // Show Toast notifications based on query strings
            const status = urlParams.get('status');
            if (status === 'success') {
                showToast('Votre paiement a été traité avec succès ! Merci.', 'success');
            } else if (status === 'cancelled') {
                showToast('Le paiement a été annulé.', 'error');
            }
        });
    </script>
</body>
</html>

