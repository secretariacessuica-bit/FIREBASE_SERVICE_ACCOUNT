<?php
// LIMA Solutions ERP - Client Portal Quotes View
require_once 'auth.php';

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

// Fetch client quotes
$stmtQuotes = $pdo->prepare("SELECT * FROM quotes WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL ORDER BY created_at DESC");
$stmtQuotes->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$clientQuotes = $stmtQuotes->fetchAll();

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
    <title>Mes Devis - <?php echo htmlspecialchars($companyName); ?></title>
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
        .badge-accepted { background-color: #d1fae5; color: #065f46; }
        .badge-rejected { background-color: #fee2e2; color: #b91c1c; }
        .badge-expired { background-color: #f1f5f9; color: #94a3b8; }

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

        .btn-crm-primary {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .btn-crm-primary:hover {
            opacity: 0.9;
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

        /* Toast notification styling */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #334155;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.success { background-color: #10b981; }
        .toast.error { background-color: #ef4444; }
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
            <li class="sidebar-item active">
                <a href="quotes.php"><i class="fa-solid fa-file-signature"></i> Mes Devis</a>
            </li>
            <li class="sidebar-item">
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
            <h1>Mes Devis / Orçamentos</h1>
        </header>

        <main class="content-container">
            <div class="panel-card">
                <?php if (empty($clientQuotes)): ?>
                    <div class="placeholder-box">
                        <i class="fa-solid fa-file-signature"></i>
                        <p>Aucun devis disponible pour le moment.</p>
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
                                <?php foreach ($clientQuotes as $q): ?>
                                    <tr>
                                        <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($q['quote_number']); ?></strong></td>
                                        <td><?php echo date('d.m.Y', strtotime($q['issue_date'])); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($q['expiry_date'])); ?></td>
                                        <td>
                                            <span class="badge-status badge-<?php echo strtolower($q['status']); ?>">
                                                <?php echo htmlspecialchars($q['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            <?php echo number_format($q['total'], 2); ?> <?php echo htmlspecialchars($q['currency']); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 8px; justify-content: center;">
                                                <a href="/api/v1/portal/quotes.php?id=<?php echo $q['id']; ?>&pdf=1" target="_blank" class="btn-crm">
                                                    <i class="fa-solid fa-file-pdf"></i> PDF
                                                </a>
                                                <?php if ($q['status'] === 'Sent'): ?>
                                                    <button type="button" class="btn-crm btn-crm-primary" onclick="acceptQuote(<?php echo $q['id']; ?>)">
                                                        <i class="fa-solid fa-check"></i> Accepter
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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

    <!-- Notification Toast -->
    <div id="toast" class="toast"></div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        const toast = document.getElementById('toast');

        function showToast(message, type = '') {
            toast.textContent = message;
            toast.className = 'toast show ' + type;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function acceptQuote(id) {
            if (!confirm("Êtes-vous sûr de vouloir accepter et signer ce devis ?")) {
                return;
            }

            fetch('/api/v1/portal/quotes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'accept',
                    id: id,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(data.message || "Erreur lors de l'acceptation.", 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast("Erreur de connexion.", 'error');
            });
        }
    </script>
</body>
</html>
