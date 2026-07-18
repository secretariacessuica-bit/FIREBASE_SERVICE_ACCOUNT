<?php
// LIMA Solutions ERP - Modular Dashboard Panel UI
require_once 'auth.php';
require_once __DIR__ . '/../helpers/FinanceHelper.php';

$companyId = getActiveCompanyId();
$quotesStats = [
    'count_this_month' => 0,
    'total_value' => 'CHF 0.00',
    'draft' => 0,
    'sent' => 0,
    'accepted' => 0,
    'rejected' => 0
];

if ($companyId) {
    try {
        // Orçamentos este mês
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE company_id = :cid AND deleted_at IS NULL AND MONTH(issue_date) = MONTH(CURRENT_DATE()) AND YEAR(issue_date) = YEAR(CURRENT_DATE())");
        $stmt->execute(['cid' => $companyId]);
        $quotesStats['count_this_month'] = (int)$stmt->fetchColumn();

        // Valor total
        $stmt = $pdo->prepare("SELECT SUM(total) FROM quotes WHERE company_id = :cid AND deleted_at IS NULL");
        $stmt->execute(['cid' => $companyId]);
        $val = (float)($stmt->fetchColumn() ?? 0.00);
        
        // Fetch company currency to format
        $stmtCurr = $pdo->prepare("SELECT currency FROM companies WHERE id = :cid LIMIT 1");
        $stmtCurr->execute(['cid' => $companyId]);
        $curr = $stmtCurr->fetchColumn() ?: 'CHF';
        
        $quotesStats['total_value'] = $curr . ' ' . FinanceHelper::formatCurrency($val, $curr, 'apostrophe');

        // Counts by status
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM quotes WHERE company_id = :cid AND deleted_at IS NULL GROUP BY status");
        $stmt->execute(['cid' => $companyId]);
        $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $quotesStats['draft'] = (int)($statusCounts['Draft'] ?? 0);
        $quotesStats['sent'] = (int)($statusCounts['Sent'] ?? 0);
        $quotesStats['accepted'] = (int)($statusCounts['Accepted'] ?? 0);
        $quotesStats['rejected'] = (int)($statusCounts['Rejected'] ?? 0);
    } catch (Exception $e) {
        // Fallback silently if table or query fails (e.g. database not migrated yet)
    }
}

$invoicesStats = [
    'count_this_month' => 0,
    'total_value' => 'CHF 0.00',
    'pending' => 0,
    'paid' => 0,
    'overdue' => 0
];

if ($companyId) {
    try {
        // Fetch company currency to format
        $stmtCurr = $pdo->prepare("SELECT currency FROM companies WHERE id = :cid LIMIT 1");
        $stmtCurr->execute(['cid' => $companyId]);
        $curr = $stmtCurr->fetchColumn() ?: 'CHF';

        // 1. Faturas emitidas hoje (Status diferente de Draft e Cancelled, data de hoje)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status NOT IN ('Draft', 'Cancelled') AND issue_date = CURRENT_DATE()");
        $stmt->execute(['cid' => $companyId]);
        $issuedToday = (int)$stmt->fetchColumn();

        // 2. Faturas emitidas este mês (Status diferente de Draft e Cancelled, mês atual)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status NOT IN ('Draft', 'Cancelled') AND MONTH(issue_date) = MONTH(CURRENT_DATE()) AND YEAR(issue_date) = YEAR(CURRENT_DATE())");
        $stmt->execute(['cid' => $companyId]);
        $issuedThisMonth = (int)$stmt->fetchColumn();

        // 3. Faturas canceladas (Status = Cancelled)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status = 'Cancelled'");
        $stmt->execute(['cid' => $companyId]);
        $cancelledCount = (int)$stmt->fetchColumn();

        // 4. Valor total faturado (Soma do total das faturas ativas que não são Draft nem Cancelled)
        $stmt = $pdo->prepare("SELECT SUM(total) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status NOT IN ('Draft', 'Cancelled')");
        $stmt->execute(['cid' => $companyId]);
        $billedValue = (float)($stmt->fetchColumn() ?? 0.00);

        // 5. Saldo pendente (Soma do balance_due das faturas ativas que não são Draft nem Cancelled)
        $stmt = $pdo->prepare("SELECT SUM(balance_due) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status NOT IN ('Draft', 'Cancelled')");
        $stmt->execute(['cid' => $companyId]);
        $pendingBalance = (float)($stmt->fetchColumn() ?? 0.00);

        $invoicesStats = [
            'issued_today' => $issuedToday,
            'issued_this_month' => $issuedThisMonth,
            'cancelled' => $cancelledCount,
            'billed_value' => $curr . ' ' . FinanceHelper::formatCurrency($billedValue, $curr, 'apostrophe'),
            'pending_balance' => $curr . ' ' . FinanceHelper::formatCurrency($pendingBalance, $curr, 'apostrophe')
        ];

        // Payments statistics
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND payment_date = CURRENT_DATE()");
        $stmt->execute(['cid' => $companyId]);
        $recToday = (float)($stmt->fetchColumn() ?? 0.00);

        $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
        $stmt->execute(['cid' => $companyId]);
        $recMonth = (float)($stmt->fetchColumn() ?? 0.00);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status = 'Partially Paid'");
        $stmt->execute(['cid' => $companyId]);
        $partPaidCount = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status = 'Paid'");
        $stmt->execute(['cid' => $companyId]);
        $fullPaidCount = (int)$stmt->fetchColumn();

        // Count today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND payment_date = CURRENT_DATE()");
        $stmt->execute(['cid' => $companyId]);
        $countToday = (int)$stmt->fetchColumn();

        // Count this month
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
        $stmt->execute(['cid' => $companyId]);
        $countMonth = (int)$stmt->fetchColumn();

        // Reversed payments count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND reversed_at IS NOT NULL");
        $stmt->execute(['cid' => $companyId]);
        $reversedCount = (int)$stmt->fetchColumn();

        // Total reversed value
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND reversed_at IS NOT NULL");
        $stmt->execute(['cid' => $companyId]);
        $reversedTotal = (float)($stmt->fetchColumn() ?? 0.00);

        // Net received value (sum including negative amounts)
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE company_id = :cid AND deleted_at IS NULL");
        $stmt->execute(['cid' => $companyId]);
        $netReceived = (float)($stmt->fetchColumn() ?? 0.00);

        // Payments without receipt
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE company_id = :cid AND deleted_at IS NULL AND (receipt_path IS NULL OR receipt_path = '') AND amount > 0");
        $stmt->execute(['cid' => $companyId]);
        $noReceiptCount = (int)$stmt->fetchColumn();

        $paymentsStats = [
            'received_today' => $curr . ' ' . FinanceHelper::formatCurrency($recToday, $curr, 'apostrophe'),
            'received_this_month' => $curr . ' ' . FinanceHelper::formatCurrency($recMonth, $curr, 'apostrophe'),
            'pending_balance' => $curr . ' ' . FinanceHelper::formatCurrency($pendingBalance, $curr, 'apostrophe'),
            'partially_paid_count' => $partPaidCount,
            'fully_paid_count' => $fullPaidCount,
            'count_today' => $countToday,
            'count_month' => $countMonth,
            'reversed_count' => $reversedCount,
            'reversed_total' => $curr . ' ' . FinanceHelper::formatCurrency($reversedTotal, $curr, 'apostrophe'),
            'net_received' => $curr . ' ' . FinanceHelper::formatCurrency($netReceived, $curr, 'apostrophe'),
            'no_receipt_count' => $noReceiptCount
        ];
    } catch (Exception $e) {
        // Fallback silently
    }
} else {
    $paymentsStats = [
        'received_today' => 'CHF 0.00',
        'received_this_month' => 'CHF 0.00',
        'pending_balance' => 'CHF 0.00',
        'partially_paid_count' => 0,
        'fully_paid_count' => 0,
        'count_today' => 0,
        'count_month' => 0,
        'reversed_count' => 0,
        'reversed_total' => 'CHF 0.00',
        'net_received' => 'CHF 0.00',
        'no_receipt_count' => 0
    ];
}

// Project & Timesheet Statistics
$projectStats = [
    'active_projects' => 0,
    'completed_projects' => 0,
    'unassigned_projects' => 0,
    'hours_today' => 0,
    'billable_hours' => 0,
    'pending_hours' => 0,
    'utilization_rate' => '0%'
];
if ($companyId) {
    try {
        // Active projects count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE company_id = :cid AND deleted_at IS NULL AND status IN ('Active', 'Planning', 'On Hold')");
        $stmt->execute(['cid' => $companyId]);
        $projectStats['active_projects'] = (int)$stmt->fetchColumn();

        // Completed projects count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE company_id = :cid AND deleted_at IS NULL AND status = 'Completed'");
        $stmt->execute(['cid' => $companyId]);
        $projectStats['completed_projects'] = (int)$stmt->fetchColumn();

        // Projects without assigned team count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects p 
            WHERE p.company_id = :cid AND p.deleted_at IS NULL 
            AND p.id NOT IN (SELECT DISTINCT project_id FROM operational_assignments)");
        $stmt->execute(['cid' => $companyId]);
        $projectStats['unassigned_projects'] = (int)$stmt->fetchColumn();

        // Hours today
        $stmt = $pdo->prepare("SELECT SUM(hours) FROM timesheets WHERE company_id = :cid AND deleted_at IS NULL AND work_date = CURRENT_DATE()");
        $stmt->execute(['cid' => $companyId]);
        $projectStats['hours_today'] = (float)($stmt->fetchColumn() ?? 0.00);

        // Billable hours
        $stmt = $pdo->prepare("SELECT SUM(hours) FROM timesheets WHERE company_id = :cid AND deleted_at IS NULL AND billable = 1");
        $stmt->execute(['cid' => $companyId]);
        $projectStats['billable_hours'] = (float)($stmt->fetchColumn() ?? 0.00);

        // Pending approval hours (Submitted status)
        $stmt = $pdo->prepare("SELECT SUM(hours) FROM timesheets WHERE company_id = :cid AND deleted_at IS NULL AND status = 'Submitted'");
        $stmt->execute(['cid' => $companyId]);
        $projectStats['pending_hours'] = (float)($stmt->fetchColumn() ?? 0.00);

        // Team utilization rate: billable / total hours
        $stmt = $pdo->prepare("SELECT SUM(hours) FROM timesheets WHERE company_id = :cid AND deleted_at IS NULL");
        $stmt->execute(['cid' => $companyId]);
        $totalHours = (float)($stmt->fetchColumn() ?? 0.00);
        if ($totalHours > 0) {
            $stmt = $pdo->prepare("SELECT SUM(hours) FROM timesheets WHERE company_id = :cid AND deleted_at IS NULL AND billable = 1");
            $stmt->execute(['cid' => $companyId]);
            $billableHours = (float)($stmt->fetchColumn() ?? 0.00);
            $projectStats['utilization_rate'] = round(($billableHours / $totalHours) * 100, 1) . '%';
        }
    } catch (Exception $e) {
        // Fallback silently
    }
}

// Marketplace Statistics
$marketplaceStats = [
    'pending' => 0,
    'approved' => 0,
    'sold' => 0,
    'donated' => 0,
    'interests' => 0
];
if ($companyId) {
    try {
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM marketplace_items WHERE company_id = :cid GROUP BY status");
        $stmt->execute(['cid' => $companyId]);
        $mktStatusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $marketplaceStats['pending'] = (int)($mktStatusCounts['Pending'] ?? 0);
        $marketplaceStats['approved'] = (int)($mktStatusCounts['Approved'] ?? 0);
        $marketplaceStats['sold'] = (int)($mktStatusCounts['Sold'] ?? 0);
        $marketplaceStats['donated'] = (int)($mktStatusCounts['Donated'] ?? 0);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM marketplace_interests mi JOIN marketplace_items i ON mi.item_id = i.id WHERE i.company_id = :cid");
        $stmt->execute(['cid' => $companyId]);
        $marketplaceStats['interests'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // Fallback silently
    }
}

// Project Margin Analytics Calculations
$marginStats = [
    'avg_margin_pct' => 0.00,
    'best_project_name' => '-',
    'best_project_pct' => 0.00,
    'worst_project_name' => '-',
    'worst_project_pct' => 0.00,
    'critical_projects_count' => 0
];

if ($companyId) {
    try {
        // 1. Fetch all projects with their total revenue and labor cost
        // We'll calculate labor cost using approved_hourly_cost (falling back to hourly_rate)
        // and revenue as total invoices (excluding Draft & Cancelled)
        $stmtProjMargin = $pdo->prepare("SELECT 
            p.id, p.name, p.project_code,
            -- Revenue
            IFNULL((SELECT SUM(total) FROM invoices WHERE company_id = :cid AND deleted_at IS NULL AND status NOT IN ('Draft', 'Cancelled') AND (quote_id = p.quote_id OR id IN (SELECT DISTINCT invoice_id FROM timesheets WHERE project_id = p.id AND invoice_id IS NOT NULL))), 0.00) AS revenue,
            -- Cost
            IFNULL((SELECT SUM(hours * CASE WHEN approved_hourly_cost > 0 THEN approved_hourly_cost ELSE hourly_rate END) FROM timesheets WHERE project_id = p.id AND company_id = :cid AND deleted_at IS NULL), 0.00) AS cost
            FROM projects p
            WHERE p.company_id = :cid AND p.deleted_at IS NULL");
        $stmtProjMargin->execute(['cid' => $companyId]);
        $projMargins = $stmtProjMargin->fetchAll();

        $totalRevenue = 0.00;
        $totalCost = 0.00;
        $marginsList = [];
        $criticalCount = 0;

        foreach ($projMargins as $pm) {
            $rev = (float)$pm['revenue'];
            $cost = (float)$pm['cost'];
            $margin = $rev - $cost;
            $marginPct = 0.00;

            if ($rev > 0) {
                $marginPct = ($margin / $rev) * 100;
            } else if ($cost > 0) {
                $marginPct = -100.00;
            }

            $marginsList[] = [
                'name' => $pm['name'] . ' [' . $pm['project_code'] . ']',
                'pct' => $marginPct
            ];

            if ($marginPct < 25.0) {
                $criticalCount++;
            }

            $totalRevenue += $rev;
            $totalCost += $cost;
        }

        // Calculate Average Margin %
        if ($totalRevenue > 0) {
            $marginStats['avg_margin_pct'] = (($totalRevenue - $totalCost) / $totalRevenue) * 100;
        } else if ($totalCost > 0) {
            $marginStats['avg_margin_pct'] = -100.00;
        }

        $marginStats['critical_projects_count'] = $criticalCount;

        // Sort to find best and worst
        if (!empty($marginsList)) {
            usort($marginsList, function($a, $b) {
                return $b['pct'] <=> $a['pct'];
            });
            $marginStats['best_project_name'] = $marginsList[0]['name'];
            $marginStats['best_project_pct'] = $marginsList[0]['pct'];

            $worst = end($marginsList);
            $marginStats['worst_project_name'] = $worst['name'];
            $marginStats['worst_project_pct'] = $worst['pct'];
        }
    } catch (Exception $e) {
        // Fallback silently
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LIMA Solutions</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="family">
    <!-- FontAwesome for clean icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main styles styling -->
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
            min-width: 0; /* Prevents overflow inside flex child */
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

        .navbar-brand-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .company-selector-container {
            display: none;
            align-items: center;
            gap: 8px;
        }

        .company-selector-container span {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
        }

        .company-selector {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid var(--border-gray);
            padding: 6px 12px;
            border-radius: var(--border-radius);
            outline: none;
            font-size: 13px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }

        .company-selector:focus {
            border-color: var(--primary-teal);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
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
        }

        .btn-header:hover {
            background-color: var(--bg-light);
            border-color: var(--text-dark);
        }

        .btn-logout {
            background-color: #ffebee;
            color: #c62828;
            border-color: #ffcdd2;
        }

        .btn-logout:hover {
            background-color: #c62828;
            color: var(--white);
            border-color: #c62828;
        }

        /* Container Layout */
        .dashboard-container {
            padding: 30px;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .welcome-card {
            background-color: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-gray);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            position: relative;
        }

        .welcome-card h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-teal-dark);
            margin-bottom: 8px;
        }

        .welcome-card p {
            color: var(--text-light);
            font-size: 15px;
            max-width: 700px;
        }

        .badge {
            background-color: var(--primary-teal-light);
            color: var(--primary-teal-dark);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 15px;
        }

        /* Widgets Grid */
        .widgets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .widget-card {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            transition: var(--transition);
        }

        .widget-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .widget-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-title i {
            color: var(--primary-teal);
            font-size: 18px;
        }

        .widget-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 10px 0;
        }

        .widget-footer-link {
            font-size: 13px;
            color: var(--primary-teal);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: auto;
        }

        .widget-footer-link:hover {
            color: var(--primary-teal-dark);
        }

        /* Company Settings Box */
        .company-info-panel {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .company-info-panel h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-teal-dark);
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-gray);
            padding-bottom: 10px;
        }

        .company-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            font-size: 14px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-item span.label {
            color: var(--text-light);
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span.value {
            color: var(--text-dark);
            font-weight: 600;
        }

        /* Modal styling for Change Password */
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
        }

        .modal-content {
            background-color: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            max-width: 400px;
            width: 100%;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            margin-bottom: 20px;
            color: var(--primary-teal-dark);
            font-weight: 700;
            font-size: 18px;
        }

        .modal-form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .modal-form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-light);
        }

        .modal-form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            outline: none;
            font-family: inherit;
            font-size: 14px;
            transition: var(--transition);
        }

        .modal-form-group input:focus {
            border-color: var(--primary-teal);
        }

        .modal-btns {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-modal {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-modal-cancel {
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .btn-modal-submit {
            background-color: var(--primary-teal);
            color: var(--white);
        }

        .btn-modal-submit:hover {
            background-color: var(--primary-teal-dark);
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
            <!-- Dynamically populated based on active modules & permissions -->
            <li class="sidebar-item active">
                <a href="#"><i class="fa-solid fa-gauge"></i> Dashboard</a>
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
            <div class="navbar-brand-section">
                <div class="company-selector-container" id="company-selector-container">
                    <span><i class="fa-solid fa-building"></i> Entreprise Active:</span>
                    <select class="company-selector" id="company-selector">
                        <!-- Dynamically populated -->
                    </select>
                </div>
            </div>
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <button type="button" class="btn-header" id="change-pwd-btn">
                    <i class="fa-solid fa-key"></i> Modifier Mot de passe
                </button>
                <button type="button" class="btn-header btn-logout" id="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
                </button>
            </div>
        </header>

        <!-- Dashboard Content Area -->
        <main class="dashboard-container">
            <div class="welcome-card">
                <h2>Tableau de Bord Administratif</h2>
                <div class="badge" id="user-role-badge">...</div>
                <p>Bienvenue dans votre espace modulaire ERP. Ci-dessous vous trouverez les indicateurs de performance clés et les fonctionnalités configurés pour votre entreprise.</p>
            <!-- Quotes Statistics Section -->
            <div id="quotes-stats-section" style="display: none; margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px; color: var(--text-dark);">
                    <i class="fa-solid fa-chart-pie" style="color: var(--primary-teal); margin-right: 8px;"></i>
                    Statistiques des Devis (Quotes)
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <!-- 1. Ce mois -->
                    <div class="widget-card" style="padding: 16px; gap: 8px;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Devis ce mois</span>
                        <div class="widget-value" id="q-stat-month" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 2. Valeur Totale -->
                    <div class="widget-card" style="padding: 16px; gap: 8px;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Valeur totale</span>
                        <div class="widget-value" id="q-stat-total" style="font-size: 24px; margin: 5px 0;">CHF 0.00</div>
                    </div>
                    <!-- 3. Draft -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #94a3b8;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Brouillon (Draft)</span>
                        <div class="widget-value" id="q-stat-draft" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 4. Sent -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #3b82f6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Envoyé (Sent)</span>
                        <div class="widget-value" id="q-stat-sent" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 5. Accepted -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #10b981;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Accepté (Accepted)</span>
                        <div class="widget-value" id="q-stat-accepted" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 6. Rejected -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #ef4444;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Refusé (Rejected)</span>
                        <div class="widget-value" id="q-stat-rejected" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                </div>
            </div>

            <!-- Invoices Statistics Section -->
            <div id="invoices-stats-section" style="display: none; margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px; color: var(--text-dark);">
                    <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary-teal); margin-right: 8px;"></i>
                    Statistiques des Factures (Invoices)
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <!-- 1. Émises aujourd'hui -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #3b82f6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Émises aujourd'hui</span>
                        <div class="widget-value" id="i-stat-today" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 2. Émises ce mois -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #007a87;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Émises ce mois</span>
                        <div class="widget-value" id="i-stat-month" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 3. Annulées -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #ef4444;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Annulées (Cancelled)</span>
                        <div class="widget-value" id="i-stat-cancelled" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 4. Valeur Facturée -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #10b981;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Valeur facturée</span>
                        <div class="widget-value" id="i-stat-total" style="font-size: 24px; margin: 5px 0;">CHF 0.00</div>
                    </div>
                    <!-- 5. Solde en suspens -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #f59e0b;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Saldo pendente</span>
                        <div class="widget-value" id="i-stat-pending" style="font-size: 24px; margin: 5px 0;">CHF 0.00</div>
                    </div>
                </div>
            </div>

            <!-- Payments Statistics Section -->
            <div id="payments-stats-section" style="display: none; margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px; color: var(--text-dark);">
                    <i class="fa-solid fa-wallet" style="color: var(--primary-teal); margin-right: 8px;"></i>
                    Statistiques des Règlements (Payments)
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <!-- 1. Reçu aujourd'hui -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #10b981;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Reçu aujourd'hui</span>
                        <div class="widget-value" id="p-stat-today" style="font-size: 24px; margin: 5px 0;">CHF 0.00</div>
                    </div>
                    <!-- 2. Reçu ce mois -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #007a87;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Reçu ce mois</span>
                        <div class="widget-value" id="p-stat-month" style="font-size: 24px; margin: 5px 0;">CHF 0.00</div>
                    </div>
                    <!-- 3. Líquido recebido -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #8b5cf6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Líquido Recebido</span>
                        <div class="widget-value" id="p-stat-net-received" style="font-size: 24px; margin: 5px 0;">CHF 0.00</div>
                    </div>
                    <!-- 4. Saldo pendente -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #f59e0b;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Saldo pendente</span>
                        <div class="widget-value" id="p-stat-pending" style="font-size: 24px; margin: 5px 0;">CHF 0.00</div>
                    </div>
                    <!-- 5. Emitidos hoje (Qtd) -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #ec4899;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Règlements émis aujourd'hui</span>
                        <div class="widget-value" id="p-stat-count-today" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 6. Emitidos este mês (Qtd) -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #14b8a6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Règlements émis ce mois</span>
                        <div class="widget-value" id="p-stat-count-month" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 7. Pagamentos estornados -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #ef4444;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Règlements extournés</span>
                        <div class="widget-value" id="p-stat-reversed-count" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 8. Valor total estornado -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #f43f5e;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Total extourné</span>
                        <div class="widget-value" id="p-stat-reversed-total" style="font-size: 24px; margin: 5px 0;">CHF 0.00</div>
                    </div>
                    <!-- 9. Sem recibo -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #6b7280;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Sans reçu officiel</span>
                        <div class="widget-value" id="p-stat-no-receipt" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 10. Partiellement payées -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #3b82f6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Partiellement payées</span>
                        <div class="widget-value" id="p-stat-partially" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 11. Totalement payées -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #10b981;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Totalement payées</span>
                        <div class="widget-value" id="p-stat-paid" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                </div>
            </div>

            <!-- Projects & Timesheets Statistics Section -->
            <div id="projects-stats-section" style="display: none; margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px; color: var(--text-dark);">
                    <i class="fa-solid fa-diagram-project" style="color: var(--primary-teal); margin-right: 8px;"></i>
                    Statistiques des Projets & Temps (Projects & Timesheets)
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <!-- 1. Projetos Ativos -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #3b82f6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Projets actifs</span>
                        <div class="widget-value" id="pr-stat-active" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- Projetos Sem Equipa -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #ef4444;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Projets sans équipe attribuée</span>
                        <div class="widget-value" id="pr-stat-unassigned" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 2. Projetos Concluídos -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #10b981;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Projets terminés</span>
                        <div class="widget-value" id="pr-stat-completed" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 3. Horas hoje -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #007a87;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Heures enregistrées aujourd'hui</span>
                        <div class="widget-value" id="pr-stat-hours-today" style="font-size: 24px; margin: 5px 0;">0.00 h</div>
                    </div>
                    <!-- 4. Horas faturáveis -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #f59e0b;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Heures facturables</span>
                        <div class="widget-value" id="pr-stat-hours-billable" style="font-size: 24px; margin: 5px 0;">0.00 h</div>
                    </div>
                    <!-- 5. Horas pendentes de aprovação -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #8b5cf6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Heures en attente d'approbation</span>
                        <div class="widget-value" id="pr-stat-hours-pending" style="font-size: 24px; margin: 5px 0;">0.00 h</div>
                    </div>
                    <!-- 6. Taxa de utilização da equipa -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #ec4899;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Taux d'utilisation équipe</span>
                        <div class="widget-value" id="pr-stat-utilization" style="font-size: 24px; margin: 5px 0;">0%</div>
                    </div>
                </div>
            </div>

            <!-- Project Margin Analytics Section -->
            <div id="project-margin-stats-section" style="display: none; margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px; color: var(--text-dark);">
                    <i class="fa-solid fa-chart-line" style="color: var(--primary-teal); margin-right: 8px;"></i>
                    Marge Opérationnelle des Projets
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <!-- 1. Margem Média -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #007a87;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Marge Moyenne</span>
                        <div class="widget-value" id="mrg-stat-avg" style="font-size: 24px; margin: 5px 0;">0.00%</div>
                    </div>
                    <!-- 2. Melhor Projeto -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #10b981;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Meilleur Projet (Marge %)</span>
                        <div class="widget-value" id="mrg-stat-best" style="font-size: 14px; font-weight: 700; margin: 5px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">-</div>
                    </div>
                    <!-- 3. Pior Projeto -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #ef4444;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Pire Projet (Marge %)</span>
                        <div class="widget-value" id="mrg-stat-worst" style="font-size: 14px; font-weight: 700; margin: 5px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">-</div>
                    </div>
                    <!-- 4. Projetos Críticos (< 25%) -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #f59e0b;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Projets Critiques (&lt; 25%)</span>
                        <div class="widget-value" id="mrg-stat-critical" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                </div>
            </div>

            <!-- Marketplace Statistics Section -->
            <div id="marketplace-stats-section" style="display: none; margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px; color: var(--text-dark);">
                    <i class="fa-solid fa-store" style="color: var(--primary-teal); margin-right: 8px;"></i>
                    Statistiques du Marketplace
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                    <!-- 1. Pending -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #f59e0b;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">En attente (Pending)</span>
                        <div class="widget-value" id="mkt-stat-pending" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 2. Approved -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #10b981;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Approuvés (Approved)</span>
                        <div class="widget-value" id="mkt-stat-approved" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 3. Sold -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #3b82f6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Vendus (Sold)</span>
                        <div class="widget-value" id="mkt-stat-sold" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 4. Donated -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #8b5cf6;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Donnés (Donated)</span>
                        <div class="widget-value" id="mkt-stat-donated" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                    <!-- 5. Interests -->
                    <div class="widget-card" style="padding: 16px; gap: 8px; border-left: 4px solid #ec4899;">
                        <span style="font-size: 12px; color: var(--text-light); font-weight: 600;">Intérêts Manifestés</span>
                        <div class="widget-value" id="mkt-stat-interests" style="font-size: 24px; margin: 5px 0;">0</div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Widgets Section -->
            <div class="widgets-grid" id="widgets-grid">
                <!-- Dynamically generated depending on modules enabled -->
            </div>

            <!-- Active Company details -->
            <div class="company-info-panel" id="company-info-panel" style="display: none;">
                <h3 id="active-company-name">Détails de l'entreprise</h3>
                <div class="company-info-grid">
                    <div class="info-item">
                        <span class="label">Raison sociale</span>
                        <span class="value" id="comp-legal-name">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">NIF / TVA</span>
                        <span class="value" id="comp-vat">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">IBAN</span>
                        <span class="value" id="comp-iban">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">BIC</span>
                        <span class="value" id="comp-bic">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Téléphone</span>
                        <span class="value" id="comp-phone">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Email</span>
                        <span class="value" id="comp-email">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Adresse</span>
                        <span class="value" id="comp-address">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Devise</span>
                        <span class="value" id="comp-currency">-</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="pwd-modal">
        <div class="modal-content">
            <div class="modal-header">Modifier le mot de passe</div>
            <form id="change-pwd-form">
                <div class="modal-form-group">
                    <label for="current-pwd">Mot de passe actuel</label>
                    <input type="password" id="current-pwd" required>
                </div>
                <div class="modal-form-group">
                    <label for="new-pwd">Nouveau mot de passe</label>
                    <input type="password" id="new-pwd" required minlength="6">
                </div>
                <div class="modal-form-group">
                    <label for="confirm-pwd">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm-pwd" required minlength="6">
                </div>
                <div class="modal-btns">
                    <button type="button" class="btn-modal btn-modal-cancel" id="cancel-pwd-btn">Annuler</button>
                    <button type="submit" class="btn-modal btn-modal-submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="toast" id="toast"></div>

    <script>
        const quotesStats = <?php echo json_encode($quotesStats ?? ['count_this_month'=>0,'total_value'=>'CHF 0.00','draft'=>0,'sent'=>0,'accepted'=>0,'rejected'=>0]); ?>;
        const invoicesStats = <?php echo json_encode($invoicesStats ?? ['issued_today'=>0,'issued_this_month'=>0,'cancelled'=>0,'billed_value'=>'CHF 0.00','pending_balance'=>'CHF 0.00']); ?>;
        const paymentsStats = <?php echo json_encode($paymentsStats ?? ['received_today'=>'CHF 0.00','received_this_month'=>'CHF 0.00','pending_balance'=>'CHF 0.00','partially_paid_count'=>0,'fully_paid_count'=>0,'count_today'=>0,'count_month'=>0,'reversed_count'=>0,'reversed_total'=>'CHF 0.00','net_received'=>'CHF 0.00','no_receipt_count'=>0]); ?>;
        const projectStats = <?php echo json_encode($projectStats ?? ['active_projects'=>0,'completed_projects'=>0,'unassigned_projects'=>0,'hours_today'=>0,'billable_hours'=>0,'pending_hours'=>0,'utilization_rate'=>'0%']); ?>;
        const marketplaceStats = <?php echo json_encode($marketplaceStats ?? ['pending'=>0,'approved'=>0,'sold'=>0,'donated'=>0,'interests'=>0]); ?>;
        const marginStats = <?php echo json_encode($marginStats ?? ['avg_margin_pct'=>0.0,'best_project_name'=>'-','best_project_pct'=>0.0,'worst_project_name'=>'-','worst_project_pct'=>0.0,'critical_projects_count'=>0]); ?>;
        document.addEventListener('DOMContentLoaded', () => {
            const pwdModal = document.getElementById('pwd-modal');
            const changePwdBtn = document.getElementById('change-pwd-btn');
            const cancelPwdBtn = document.getElementById('cancel-pwd-btn');
            const changePwdForm = document.getElementById('change-pwd-form');
            const toast = document.getElementById('toast');

            function showToast(message, type = '') {
                toast.textContent = message;
                toast.className = 'toast show ' + type;
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }

            // Session validation script - Runs immediately
            fetch('../api/v1/session.php')
                .then(res => res.json())
                .then(data => {
                    if (data.authenticated) {
                        document.getElementById('user-display-name').textContent = data.user.name;
                        document.getElementById('user-role-badge').textContent = 'Rôle: ' + data.user.role.toUpperCase();
                        
                        // Active company details
                        if (data.active_company) {
                            document.getElementById('company-info-panel').style.display = 'block';
                            document.getElementById('active-company-name').textContent = "Entreprise active: " + data.active_company.name;
                            document.getElementById('comp-legal-name').textContent = data.active_company.legal_name || '-';
                            document.getElementById('comp-vat').textContent = data.active_company.vat_number || '-';
                            document.getElementById('comp-iban').textContent = data.active_company.iban || '-';
                            document.getElementById('comp-bic').textContent = data.active_company.bic || '-';
                            document.getElementById('comp-phone').textContent = data.active_company.phone || '-';
                            document.getElementById('comp-email').textContent = data.active_company.email || '-';
                            document.getElementById('comp-address').textContent = data.active_company.address || '-';
                            document.getElementById('comp-currency').textContent = data.active_company.currency || 'CHF';

                            // Customize primary color from company settings if exists
                            if (data.active_company.main_color) {
                                document.documentElement.style.setProperty('--primary-teal', data.active_company.main_color);
                            }
                        }

                        // Render sidebar menu items dynamically based on enabled modules and user permissions
                        renderSidebarAndWidgets(data.enabled_modules, data.permissions, data.user.role);

                        // Populate company selector if user is super_admin or has multiple companies
                        if (data.companies && data.companies.length > 0) {
                            const selectorContainer = document.getElementById('company-selector-container');
                            const selector = document.getElementById('company-selector');
                            selectorContainer.style.display = 'flex';
                            
                            selector.innerHTML = '';
                            data.companies.forEach(company => {
                                const option = document.createElement('option');
                                option.value = company.id;
                                option.textContent = company.name;
                                if (company.id == data.active_company_id) {
                                    option.selected = true;
                                }
                                selector.appendChild(option);
                            });

                            selector.addEventListener('change', () => {
                                const newCompanyId = selector.value;
                                fetch('../api/v1/select_company.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({ company_id: newCompanyId })
                                })
                                .then(res => res.json())
                                .then(resData => {
                                    if (resData.success) {
                                        showToast('Entreprise modifiée : ' + resData.company.name, 'success');
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1000);
                                    } else {
                                        showToast(resData.message || 'Erreur lors du changement.', 'error');
                                    }
                                })
                                .catch(err => {
                                    console.error('Error changing company:', err);
                                    showToast('Erreur de communication.', 'error');
                                });
                            });
                        }
                    } else {
                        window.location.href = 'login.php';
                    }
                })
                .catch(err => {
                    console.error('Session check failure:', err);
                    window.location.href = 'login.php';
                });

            // Function to dynamically build sidebar links and dashboard widgets
            function renderSidebarAndWidgets(enabledModules, permissions, role) {
                const sidebarMenu = document.getElementById('sidebar-menu');
                const widgetsGrid = document.getElementById('widgets-grid');
                
                // Definition of modules properties and display options
                const moduleDefinitions = {
                    'crm': { title: 'Clientes', icon: 'fa-users', path: '../modules/crm/views/list.php', widgetText: 'Visualiser les fiches clients et historique.', btnText: 'Gérer Clients' },
                    'crm_leads': { title: 'Pipeline Leads', icon: 'fa-funnel-dollar', path: '../modules/crm/views/leads.php', widgetText: 'Gérer les prospects, pipeline commercial et conversion.', btnText: 'Gérer Leads' },
                    'marketplace': { title: 'Marketplace', icon: 'fa-store', path: 'marketplace.php', widgetText: 'Modérer les annonces de meubles d\'occasion et demandes de clients.', btnText: 'Modérer Marketplace' },
                    'staff': { title: 'Colaboradores / Équipe', icon: 'fa-user-tie', path: 'staff.php', widgetText: 'Gérer les collaborateurs et les chauffeurs.', btnText: 'Gérer Équipe' },
                    'projects': { title: 'Projetos', icon: 'fa-diagram-project', path: '../modules/projects/views/list.php', widgetText: 'Gestion des projets, Kanban des tâches et affectation.', btnText: 'Gérer Projets' },
                    'timesheets': { title: 'Timesheets', icon: 'fa-clock', path: '../modules/timesheets/views/list.php', widgetText: 'Saisie des heures, calendrier d\'équipe et validation.', btnText: 'Gérer Heures' },
                    'invoices': { title: 'Factures', icon: 'fa-file-invoice-dollar', path: '../modules/invoices/views/list.php', widgetText: 'Gérer et générer des factures professionnelles.', btnText: 'Gérer Factures' },
                    'quotes': { title: 'Orçamentos', icon: 'fa-calculator', path: '../modules/quotes/views/list.php', widgetText: 'Simulateur d\'offres et devis.', btnText: 'Gérer Devis' },
                    'payments': { title: 'Pagamentos', icon: 'fa-wallet', path: '../modules/payments/views/list.php', widgetText: 'Enregistrement de transactions reçues.', btnText: 'Gérer Paiements' },
                    'calendar': { title: 'Agenda', icon: 'fa-calendar-days', path: '#calendar-link', widgetText: 'Planifier les déménagements et transports.', btnText: 'Voir Calendrier' },
                    'reports': { title: 'Relatórios', icon: 'fa-chart-line', path: '../modules/reports/views/dashboard.php', widgetText: 'Indicateurs financiers mensuels et annuels.', btnText: 'Voir Rapports' },
                    'settings': { title: 'Configuration', icon: 'fa-sliders', path: '#settings-link', widgetText: 'Gérer les coordonnées et données d\'entreprise.', btnText: 'Configuration' }
                };

                // Clear dynamic items, keeping only Dashboard core
                sidebarMenu.innerHTML = `
                    <li class="sidebar-item active">
                        <a href="#"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                    </li>
                `;
                widgetsGrid.innerHTML = '';

                // Toggle Quotes Statistics Section
                const hasQuotesAccess = enabledModules.includes('quotes') || enabledModules.includes('invoices');
                const quotesStatsSection = document.getElementById('quotes-stats-section');
                if (quotesStatsSection) {
                    if (hasQuotesAccess) {
                        quotesStatsSection.style.display = 'block';
                        document.getElementById('q-stat-month').textContent = quotesStats.count_this_month;
                        document.getElementById('q-stat-total').textContent = quotesStats.total_value;
                        document.getElementById('q-stat-draft').textContent = quotesStats.draft;
                        document.getElementById('q-stat-sent').textContent = quotesStats.sent;
                        document.getElementById('q-stat-accepted').textContent = quotesStats.accepted;
                        document.getElementById('q-stat-rejected').textContent = quotesStats.rejected;
                    } else {
                        quotesStatsSection.style.display = 'none';
                    }
                }

                // Toggle Invoices Statistics Section
                const hasInvoicesAccess = enabledModules.includes('invoices');
                const invoicesStatsSection = document.getElementById('invoices-stats-section');
                if (invoicesStatsSection) {
                    if (hasInvoicesAccess) {
                        invoicesStatsSection.style.display = 'block';
                        document.getElementById('i-stat-today').textContent = invoicesStats.issued_today;
                        document.getElementById('i-stat-month').textContent = invoicesStats.issued_this_month;
                        document.getElementById('i-stat-cancelled').textContent = invoicesStats.cancelled;
                        document.getElementById('i-stat-total').textContent = invoicesStats.billed_value;
                        document.getElementById('i-stat-pending').textContent = invoicesStats.pending_balance;
                    } else {
                        invoicesStatsSection.style.display = 'none';
                    }
                }

                // Toggle Payments Statistics Section
                const hasPaymentsAccess = enabledModules.includes('payments') || enabledModules.includes('invoices');
                const paymentsStatsSection = document.getElementById('payments-stats-section');
                if (paymentsStatsSection) {
                    if (hasPaymentsAccess) {
                        paymentsStatsSection.style.display = 'block';
                        document.getElementById('p-stat-today').textContent = paymentsStats.received_today;
                        document.getElementById('p-stat-month').textContent = paymentsStats.received_this_month;
                        document.getElementById('p-stat-pending').textContent = paymentsStats.pending_balance;
                        document.getElementById('p-stat-partially').textContent = paymentsStats.partially_paid_count;
                        document.getElementById('p-stat-paid').textContent = paymentsStats.fully_paid_count;
                        document.getElementById('p-stat-net-received').textContent = paymentsStats.net_received;
                        document.getElementById('p-stat-count-today').textContent = paymentsStats.count_today;
                        document.getElementById('p-stat-count-month').textContent = paymentsStats.count_month;
                        document.getElementById('p-stat-reversed-count').textContent = paymentsStats.reversed_count;
                        document.getElementById('p-stat-reversed-total').textContent = paymentsStats.reversed_total;
                        document.getElementById('p-stat-no-receipt').textContent = paymentsStats.no_receipt_count;
                    } else {
                        paymentsStatsSection.style.display = 'none';
                    }
                }

                // Toggle Projects & Timesheets Statistics Section
                const hasProjectsAccess = enabledModules.includes('projects') || enabledModules.includes('timesheets');
                const projectsStatsSection = document.getElementById('projects-stats-section');
                if (projectsStatsSection) {
                    if (hasProjectsAccess) {
                        projectsStatsSection.style.display = 'block';
                        document.getElementById('pr-stat-active').textContent = projectStats.active_projects;
                        document.getElementById('pr-stat-unassigned').textContent = projectStats.unassigned_projects;
                        document.getElementById('pr-stat-completed').textContent = projectStats.completed_projects;
                        document.getElementById('pr-stat-hours-today').textContent = projectStats.hours_today.toFixed(2) + ' h';
                        document.getElementById('pr-stat-hours-billable').textContent = projectStats.billable_hours.toFixed(2) + ' h';
                        document.getElementById('pr-stat-hours-pending').textContent = projectStats.pending_hours.toFixed(2) + ' h';
                        document.getElementById('pr-stat-utilization').textContent = projectStats.utilization_rate;
                    } else {
                        projectsStatsSection.style.display = 'none';
                    }
                }

                // Toggle Project Margin Analytics Statistics Section
                const marginStatsSection = document.getElementById('project-margin-stats-section');
                if (marginStatsSection) {
                    if (enabledModules.includes('projects') || enabledModules.includes('timesheets')) {
                        marginStatsSection.style.display = 'block';
                        document.getElementById('mrg-stat-avg').textContent = marginStats.avg_margin_pct.toFixed(2) + '%';
                        
                        const bestElem = document.getElementById('mrg-stat-best');
                        bestElem.textContent = marginStats.best_project_name;
                        bestElem.title = marginStats.best_project_name + ' (' + marginStats.best_project_pct.toFixed(2) + '%)';
                        
                        const worstElem = document.getElementById('mrg-stat-worst');
                        worstElem.textContent = marginStats.worst_project_name;
                        worstElem.title = marginStats.worst_project_name + ' (' + marginStats.worst_project_pct.toFixed(2) + '%)';
                        
                        document.getElementById('mrg-stat-critical').textContent = marginStats.critical_projects_count;
                    } else {
                        marginStatsSection.style.display = 'none';
                    }
                }

                // Toggle Marketplace Statistics Section
                const hasMarketplaceAccess = enabledModules.includes('marketplace');
                const marketplaceStatsSection = document.getElementById('marketplace-stats-section');
                if (marketplaceStatsSection) {
                    if (hasMarketplaceAccess) {
                        marketplaceStatsSection.style.display = 'block';
                        document.getElementById('mkt-stat-pending').textContent = marketplaceStats.pending;
                        document.getElementById('mkt-stat-approved').textContent = marketplaceStats.approved;
                        document.getElementById('mkt-stat-sold').textContent = marketplaceStats.sold;
                        document.getElementById('mkt-stat-donated').textContent = marketplaceStats.donated;
                        document.getElementById('mkt-stat-interests').textContent = marketplaceStats.interests;
                    } else {
                        marketplaceStatsSection.style.display = 'none';
                    }
                }

                // Iterate over all definitions and render if enabled and permitted
                Object.keys(moduleDefinitions).forEach(modName => {
                    // Check if module is enabled in active company modules list
                    let isEnabled = false;
                    if (modName === 'crm_leads') {
                        isEnabled = enabledModules.includes('crm');
                    } else {
                        isEnabled = enabledModules.includes(modName) || (modName === 'quotes' && enabledModules.includes('invoices'));
                    }
                    
                    // Check if role is authorized to view
                    let isPermitted = false;
                    if (role === 'super_admin') {
                        isPermitted = true;
                    } else {
                        const targetMod = (modName === 'crm_leads') ? 'crm' : modName;
                        const perm = permissions.find(p => p.module_name === targetMod || (targetMod === 'quotes' && p.module_name === 'invoices'));
                        isPermitted = perm && parseInt(perm.can_view) === 1;
                    }

                    if (isEnabled && isPermitted) {
                        const def = moduleDefinitions[modName];
                        
                        // 1. Add to sidebar menu list
                        const li = document.createElement('li');
                        li.className = 'sidebar-item';
                        li.innerHTML = `<a href="${def.path}"><i class="fa-solid ${def.icon}"></i> ${def.title}</a>`;
                        sidebarMenu.appendChild(li);

                        // 2. Add as active card widget on dashboard panel
                        const widget = document.createElement('div');
                        widget.className = 'widget-card';
                        widget.innerHTML = `
                            <div class="widget-header">
                                <span class="widget-title"><i class="fa-solid ${def.icon}"></i> ${def.title}</span>
                            </div>
                            <p style="font-size: 14px; color: var(--text-light); margin: 10px 0;">${def.widgetText}</p>
                            <a href="${def.path}" class="widget-footer-link">${def.btnText} <i class="fa-solid fa-arrow-right"></i></a>
                        `;
                        widgetsGrid.appendChild(widget);
                    }
                });
            }

            // Logout Action
            document.getElementById('logout-btn').addEventListener('click', () => {
                fetch('../api/v1/logout.php')
                    .then(res => res.json())
                    .then(data => {
                        window.location.href = 'login.php';
                    })
                    .catch(err => {
                        console.error('Logout error:', err);
                        window.location.href = 'login.php';
                    });
            });

            // Modal Toggles
            changePwdBtn.addEventListener('click', () => {
                pwdModal.style.display = 'flex';
            });

            cancelPwdBtn.addEventListener('click', () => {
                pwdModal.style.display = 'none';
                changePwdForm.reset();
            });

            // Change Password Submit
            changePwdForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const current_password = document.getElementById('current-pwd').value;
                const new_password = document.getElementById('new-pwd').value;
                const confirm_password = document.getElementById('confirm-pwd').value;

                if (new_password !== confirm_password) {
                    showToast('Les nouveaux mots de passe ne correspondent pas.', 'error');
                    return;
                }

                fetch('../api/v1/change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ current_password, new_password })
                })
                .then(async res => {
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showToast('Mot de passe mis à jour avec succès !', 'success');
                        pwdModal.style.display = 'none';
                        changePwdForm.reset();
                    } else {
                        showToast(data.message || 'Échec de la modification.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Password change error:', err);
                    showToast('Erreur lors de la communication avec le serveur.', 'error');
                });
            });
        });
    </script>
</body>
</html>
