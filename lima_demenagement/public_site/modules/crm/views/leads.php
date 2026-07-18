<?php
// LIMA Solutions ERP - CRM Leads & Pipeline View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

// Enforce module access and permissions (Requires crm module viewing access)
enforceModuleAccess('crm', $userRole, $companyId, 'view', $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - Pipeline Leads - LIMA Solutions</title>
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
            
            /* Pipeline colors */
            --color-new: #94a3b8;
            --color-contacted: #3b82f6;
            --color-visit: #f59e0b;
            --color-proposal: #8b5cf6;
            --color-negotiation: #ec4899;
            --color-won: #10b981;
            --color-lost: #ef4444;
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

        /* CRM Leads Container */
        .crm-container {
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .crm-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .crm-title-section h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .crm-title-section p {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 4px;
        }

        .view-toggle-btns {
            display: inline-flex;
            background-color: var(--border-gray);
            padding: 4px;
            border-radius: var(--border-radius);
        }

        .btn-toggle {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            background: transparent;
            color: var(--text-light);
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-toggle.active {
            background-color: var(--white);
            color: var(--primary-teal);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Pipeline Layout */
        .pipeline-wrapper {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
            overflow-x: auto;
            align-items: start;
            flex-grow: 1;
            min-height: 500px;
        }

        .pipeline-column {
            background-color: #f1f5f9;
            border-radius: var(--border-radius);
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 700px;
            overflow-y: auto;
            border-top: 4px solid var(--color-new);
        }

        .pipeline-column.col-new { border-top-color: var(--color-new); }
        .pipeline-column.col-contacted { border-top-color: var(--color-contacted); }
        .pipeline-column.col-visit { border-top-color: var(--color-visit); }
        .pipeline-column.col-proposal { border-top-color: var(--color-proposal); }
        .pipeline-column.col-negotiation { border-top-color: var(--color-negotiation); }
        .pipeline-column.col-won { border-top-color: var(--color-won); }
        .pipeline-column.col-lost { border-top-color: var(--color-lost); }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-gray);
        }

        .column-header h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .column-header .badge {
            background-color: var(--white);
            color: var(--text-light);
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid var(--border-gray);
        }

        /* Card Lead */
        .lead-card {
            background-color: var(--white);
            border-radius: 6px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-gray);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .lead-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
            border-color: var(--primary-teal);
        }

        .lead-card h5 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .lead-card p.lead-email {
            font-size: 12px;
            color: var(--text-light);
            word-break: break-all;
        }

        .lead-card .lead-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--text-light);
            margin-top: 4px;
            border-top: 1px dotted var(--border-gray);
            padding-top: 6px;
        }

        .lead-card .lead-volume {
            background-color: var(--primary-teal-light);
            color: var(--primary-teal-dark);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* List View */
        .crm-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            border: 1px solid var(--border-gray);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            padding: 20px;
        }

        .crm-table-wrapper {
            overflow-x: auto;
            margin-top: 15px;
        }

        .crm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        .crm-table th, .crm-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-gray);
        }

        .crm-table th {
            background-color: var(--bg-light);
            font-weight: 600;
            color: var(--text-dark);
        }

        .crm-table tbody tr:hover {
            background-color: var(--bg-light);
        }

        /* Badge Statuses */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.new { background-color: #f1f5f9; color: #475569; }
        .status-badge.contacted { background-color: #dbeafe; color: #1d4ed8; }
        .status-badge.visit { background-color: #fef3c7; color: #b45309; }
        .status-badge.proposal { background-color: #f3e8ff; color: #6d28d9; }
        .status-badge.negotiation { background-color: #fce7f3; color: #be185d; }
        .status-badge.won { background-color: #d1fae5; color: #065f46; }
        .status-badge.lost { background-color: #fee2e2; color: #991b1b; }

        /* Modal Styles */
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
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 650px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: modalSlide 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes modalSlide {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--bg-light);
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 20px;
            color: var(--text-light);
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            max-height: 60vh;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-block {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-block span.label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-block span.value {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .notes-block {
            background-color: var(--bg-light);
            padding: 12px;
            border-radius: 6px;
            border: 1px solid var(--border-gray);
            font-size: 13px;
            color: var(--text-dark);
            white-space: pre-wrap;
        }

        .utm-panel {
            background-color: #f8fafc;
            border: 1px dashed var(--border-gray);
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .utm-panel h4 {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .utm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            font-size: 12px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-gray);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background-color: var(--bg-light);
        }

        /* Buttons styles */
        .btn-modal {
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            border: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-modal-secondary {
            background-color: var(--white);
            color: var(--text-dark);
            border: 1px solid var(--border-gray);
        }

        .btn-modal-secondary:hover {
            background-color: var(--border-gray);
        }

        .btn-modal-primary {
            background-color: var(--primary-teal);
            color: var(--white);
        }

        .btn-modal-primary:hover {
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

        .toast.error { background-color: #ef4444; }
        .toast.success { background-color: #10b981; }

        /* Lead Scoring Styles */
        .scoring-dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .scoring-widget {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border-left: 5px solid;
            transition: var(--transition);
        }
        .scoring-widget:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }
        .scoring-widget.priority { border-left-color: #ef4444; }
        .scoring-widget.hot { border-left-color: #f97316; }
        .scoring-widget.warm { border-left-color: #eab308; }
        .scoring-widget.cold { border-left-color: #3b82f6; }
        
        .scoring-widget .widget-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .scoring-widget.priority .widget-icon { background-color: #fee2e2; color: #ef4444; }
        .scoring-widget.hot .widget-icon { background-color: #ffedd5; color: #f97316; }
        .scoring-widget.warm .widget-icon { background-color: #fef9c3; color: #eab308; }
        .scoring-widget.cold .widget-icon { background-color: #dbeafe; color: #3b82f6; }

        .scoring-widget .widget-data {
            display: flex;
            flex-direction: column;
        }
        .scoring-widget .widget-data .count {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }
        .scoring-widget .widget-data .label {
            font-size: 12px;
            color: var(--text-light);
            font-weight: 500;
        }

        .score-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .score-badge.priority { background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; }
        .score-badge.hot { background-color: #ffedd5; color: #f97316; border: 1px solid #fed7aa; }
        .score-badge.warm { background-color: #fef9c3; color: #eab308; border: 1px solid #fef08a; }
        .score-badge.cold { background-color: #dbeafe; color: #3b82f6; border: 1px solid #bfdbfe; }
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
                <a href="/modules/crm/views/list.php"><i class="fa-solid fa-users"></i> Clientes</a>
            </li>
            <li class="sidebar-item active">
                <a href="#"><i class="fa-solid fa-funnel-dollar"></i> Pipeline Leads</a>
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
                <a href="/admin/index.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <!-- Main CRM Leads Container -->
        <main class="crm-container">
            <div class="crm-header-row">
                <div class="crm-title-section">
                    <h2>Pipeline de Ventes (Leads)</h2>
                    <p>Suivez l'état de qualification de vos prospects en temps réel.</p>
                </div>
                <div class="view-toggle-btns">
                    <button class="btn-toggle active" id="toggle-pipeline-btn">
                        <i class="fa-solid fa-table-columns"></i> Pipeline
                    </button>
                    <button class="btn-toggle" id="toggle-list-btn">
                        <i class="fa-solid fa-list"></i> Liste
                    </button>
                </div>
            </div>

            <!-- Dashboard Scoring Widgets -->
            <div class="scoring-dashboard-widgets">
                <div class="scoring-widget priority">
                    <div class="widget-icon"><i class="fa-solid fa-fire-flame-simple"></i></div>
                    <div class="widget-data">
                        <span class="count" id="count-priority">0</span>
                        <span class="label">Priority (76-100)</span>
                    </div>
                </div>
                <div class="scoring-widget hot">
                    <div class="widget-icon"><i class="fa-solid fa-temperature-high"></i></div>
                    <div class="widget-data">
                        <span class="count" id="count-hot">0</span>
                        <span class="label">Hot (51-75)</span>
                    </div>
                </div>
                <div class="scoring-widget warm">
                    <div class="widget-icon"><i class="fa-solid fa-sun"></i></div>
                    <div class="widget-data">
                        <span class="count" id="count-warm">0</span>
                        <span class="label">Warm (26-50)</span>
                    </div>
                </div>
                <div class="scoring-widget cold">
                    <div class="widget-icon"><i class="fa-solid fa-snowflake"></i></div>
                    <div class="widget-data">
                        <span class="count" id="count-cold">0</span>
                        <span class="label">Cold (0-25)</span>
                    </div>
                </div>
            </div>

            <!-- Pipeline Grid View -->
            <div id="pipeline-view" class="pipeline-wrapper">
                <!-- Column 1: New -->
                <div class="pipeline-column col-new" data-status="New">
                    <div class="column-header">
                        <h4>Nouveau / Novo</h4>
                        <span class="badge" id="badge-new">0</span>
                    </div>
                    <div class="column-cards" id="cards-new"></div>
                </div>
                <!-- Column 2: Contacted -->
                <div class="pipeline-column col-contacted" data-status="Contacted">
                    <div class="column-header">
                        <h4>En contact / Em contacto</h4>
                        <span class="badge" id="badge-contacted">0</span>
                    </div>
                    <div class="column-cards" id="cards-contacted"></div>
                </div>
                <!-- Column 3: Visit Scheduled -->
                <div class="pipeline-column col-visit" data-status="Visit Scheduled">
                    <div class="column-header">
                        <h4>Visite planifiée / Visita marcada</h4>
                        <span class="badge" id="badge-visit">0</span>
                    </div>
                    <div class="column-cards" id="cards-visit"></div>
                </div>
                <!-- Column 4: Proposal Sent -->
                <div class="pipeline-column col-proposal" data-status="Proposal Sent">
                    <div class="column-header">
                        <h4>Proposition envoyée / Proposta</h4>
                        <span class="badge" id="badge-proposal">0</span>
                    </div>
                    <div class="column-cards" id="cards-proposal"></div>
                </div>
                <!-- Column 5: Negotiation -->
                <div class="pipeline-column col-negotiation" data-status="Negotiation">
                    <div class="column-header">
                        <h4>Négociation / Negociação</h4>
                        <span class="badge" id="badge-negotiation">0</span>
                    </div>
                    <div class="column-cards" id="cards-negotiation"></div>
                </div>
                <!-- Column 6: Won -->
                <div class="pipeline-column col-won" data-status="Won">
                    <div class="column-header">
                        <h4>Gagné / Ganho</h4>
                        <span class="badge" id="badge-won">0</span>
                    </div>
                    <div class="column-cards" id="cards-won"></div>
                </div>
                <!-- Column 7: Lost -->
                <div class="pipeline-column col-lost" data-status="Lost">
                    <div class="column-header">
                        <h4>Perdu / Perdido</h4>
                        <span class="badge" id="badge-lost">0</span>
                    </div>
                    <div class="column-cards" id="cards-lost"></div>
                </div>
            </div>

            <!-- List View (Table) -->
            <div id="list-view" class="crm-card" style="display: none;">
                <div class="crm-table-wrapper">
                    <table class="crm-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Nom</th>
                                <th>Courriel / Téléphone</th>
                                <th>Volume (m³)</th>
                                <th>Origine / Destination</th>
                                <th>Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="leads-table-body">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-light); padding: 40px;">
                                    Chargement des leads...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Lead Details Modal -->
    <div class="modal" id="lead-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-lead-name">Détails du Prospect</h3>
                <button class="modal-close" id="modal-close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Lead Score Info Box -->
                <div class="score-info-box" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-dark);"><i class="fa-solid fa-star-half-stroke" style="color: var(--primary-teal);"></i> Score &amp; Prioridade Comercial</span>
                        <span class="score-badge" id="modal-lead-score-badge">0 - Cold</span>
                    </div>
                    <div style="font-size: 12px; color: var(--text-light); font-weight: 500; margin-bottom: 8px;">Motifs de la pontuation :</div>
                    <ul id="modal-lead-score-reasons" style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--text-dark); line-height: 1.6;">
                        <!-- Reasons list -->
                    </ul>
                </div>

                <div class="info-grid">
                    <div class="info-block">
                        <span class="label">Date de soumission</span>
                        <span class="value" id="lead-created-at">-</span>
                    </div>
                    <div class="info-block">
                        <span class="label">Statut Actuel</span>
                        <div style="margin-top: 4px;">
                            <span class="status-badge" id="lead-status-badge">New</span>
                        </div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-block">
                        <span class="label">Courriel</span>
                        <span class="value" id="lead-email">-</span>
                    </div>
                    <div class="info-block">
                        <span class="label">Téléphone</span>
                        <span class="value" id="lead-phone">-</span>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-block">
                        <span class="label">Adresse d'Origine</span>
                        <span class="value" id="lead-origin">-</span>
                    </div>
                    <div class="info-block">
                        <span class="label">Adresse de Destination</span>
                        <span class="value" id="lead-destination">-</span>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-block">
                        <span class="label">Date prévue du service</span>
                        <span class="value" id="lead-service-date">-</span>
                    </div>
                    <div class="info-block">
                        <span class="label">Volume estimé</span>
                        <span class="value" id="lead-volume">-</span>
                    </div>
                </div>

                <div class="info-block">
                    <span class="label">Notes / Instructions spéciales</span>
                    <div class="notes-block" id="lead-notes">-</div>
                </div>

                <!-- UTM & Referrer Panel -->
                <div class="utm-panel">
                    <h4>Rastreabilidade UTM & Origem (Marketing)</h4>
                    <div class="utm-grid">
                        <div class="info-block">
                            <span class="label">UTM Source</span>
                            <span class="value" id="lead-utm-source">-</span>
                        </div>
                        <div class="info-block">
                            <span class="label">UTM Medium</span>
                            <span class="value" id="lead-utm-medium">-</span>
                        </div>
                        <div class="info-block">
                            <span class="label">UTM Campaign</span>
                            <span class="value" id="lead-utm-campaign">-</span>
                        </div>
                    </div>
                    <div class="info-block" style="margin-top: 10px;">
                        <span class="label">Referer URL</span>
                        <span class="value" id="lead-referer" style="font-size: 11px; word-break: break-all;">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <!-- Status changer dropdown -->
                <div style="margin-right: auto; display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-light);">CHANGER LE STATUT:</span>
                    <select id="lead-status-select" class="company-selector" style="padding: 6px 12px; font-size: 13px;">
                        <option value="New">Nouveau / Novo</option>
                        <option value="Contacted">En contact / Em contacto</option>
                        <option value="Visit Scheduled">Visite planifiée / Visita marcada</option>
                        <option value="Proposal Sent">Proposition envoyée / Proposta</option>
                        <option value="Negotiation">Négociation / Negociação</option>
                        <option value="Won">Gagné / Ganho</option>
                        <option value="Lost">Perdu / Perdido</option>
                    </select>
                </div>
                
                <button class="btn-modal btn-modal-secondary" id="modal-close-footer-btn">Fermer</button>
                <button class="btn-modal btn-modal-primary" id="btn-convert-lead">
                    <i class="fa-solid fa-user-check"></i> Convertir en Cliente
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div class="toast" id="toast"></div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        let leadsList = [];
        let selectedLead = null;

        // View toggle logic
        const togglePipelineBtn = document.getElementById('toggle-pipeline-btn');
        const toggleListBtn = document.getElementById('toggle-list-btn');
        const pipelineView = document.getElementById('pipeline-view');
        const listView = document.getElementById('list-view');

        togglePipelineBtn.addEventListener('click', () => {
            togglePipelineBtn.classList.add('active');
            toggleListBtn.classList.remove('active');
            pipelineView.style.display = 'grid';
            listView.style.display = 'none';
        });

        toggleListBtn.addEventListener('click', () => {
            toggleListBtn.classList.add('active');
            togglePipelineBtn.classList.remove('active');
            listView.style.display = 'block';
            pipelineView.style.display = 'none';
        });

        // Toast Helper
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Fetch session data & check permissions
        fetch('/api/v1/session.php')
            .then(res => res.json())
            .then(data => {
                if (data.authenticated) {
                    document.getElementById('user-display-name').textContent = data.user.name;
                    
                    if (data.active_company && data.active_company.main_color) {
                        document.documentElement.style.setProperty('--primary-teal', data.active_company.main_color);
                    }

                    if (data.enabled_modules.includes('invoices')) {
                        document.getElementById('menu-invoices').style.display = 'block';
                    }
                    if (data.enabled_modules.includes('settings') && (data.user.role === 'super_admin' || data.user.role === 'admin')) {
                        document.getElementById('menu-settings').style.display = 'block';
                    }

                    // Load Leads
                    loadLeads();
                } else {
                    window.location.href = '/admin/login.php';
                }
            })
            .catch(err => {
                console.error(err);
                window.location.href = '/admin/login.php';
            });

        // Fetch leads from API
        function loadLeads() {
            fetch('/api/v1/leads/leads.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        leadsList = data.leads;
                        renderPipeline();
                        renderTable();
                    } else {
                        showToast(data.message || "Erreur de chargement des leads", 'error');
                    }
                })
                .catch(err => {
                    console.error("Fetch error:", err);
                    showToast("Erreur de connexion avec le serveur.", 'error');
                });
        }

        // Render Kanban Pipeline View
        function renderPipeline() {
            const columns = {
                'New': { count: 0, container: document.getElementById('cards-new'), badge: document.getElementById('badge-new') },
                'Contacted': { count: 0, container: document.getElementById('cards-contacted'), badge: document.getElementById('badge-contacted') },
                'Visit Scheduled': { count: 0, container: document.getElementById('cards-visit'), badge: document.getElementById('badge-visit') },
                'Proposal Sent': { count: 0, container: document.getElementById('cards-proposal'), badge: document.getElementById('badge-proposal') },
                'Negotiation': { count: 0, container: document.getElementById('cards-negotiation'), badge: document.getElementById('badge-negotiation') },
                'Won': { count: 0, container: document.getElementById('cards-won'), badge: document.getElementById('badge-won') },
                'Lost': { count: 0, container: document.getElementById('cards-lost'), badge: document.getElementById('badge-lost') }
            };

            // Clear containers
            Object.keys(columns).forEach(status => {
                columns[status].container.innerHTML = '';
            });

            leadsList.forEach(lead => {
                const status = lead.status || 'New';
                if (!columns[status]) return;

                columns[status].count++;
                
                const card = document.createElement('div');
                card.className = 'lead-card';
                card.onclick = () => openLeadModal(lead);
                
                let volStr = lead.volume_m3 ? `<span class="lead-volume">${parseFloat(lead.volume_m3)} m³</span>` : '';
                let serviceDate = lead.service_date ? lead.service_date : 'À convenir';

                const score = parseInt(lead.lead_score || 0);
                let scoreClass = 'cold';
                let category = 'Cold';
                if (score >= 76) { scoreClass = 'priority'; category = 'Priority'; }
                else if (score >= 51) { scoreClass = 'hot'; category = 'Hot'; }
                else if (score >= 26) { scoreClass = 'warm'; category = 'Warm'; }
                
                const scoreBadge = `<span class="score-badge ${scoreClass}">${score}</span>`;

                card.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                        <h5 style="margin: 0; font-size: 14px;">${lead.name}</h5>
                        ${scoreBadge}
                    </div>
                    <p class="lead-email">${lead.email}</p>
                    <div class="lead-meta">
                        <span><i class="fa-regular fa-calendar"></i> ${serviceDate}</span>
                        ${volStr}
                    </div>
                `;
                columns[status].container.appendChild(card);
            });

            // Update scoring widgets count
            let priorityCount = 0;
            let hotCount = 0;
            let warmCount = 0;
            let coldCount = 0;
            
            leadsList.forEach(lead => {
                const score = parseInt(lead.lead_score || 0);
                if (score >= 76) priorityCount++;
                else if (score >= 51) hotCount++;
                else if (score >= 26) warmCount++;
                else coldCount++;
            });
            
            document.getElementById('count-priority').textContent = priorityCount;
            document.getElementById('count-hot').textContent = hotCount;
            document.getElementById('count-warm').textContent = warmCount;
            document.getElementById('count-cold').textContent = coldCount;

            // Update column counts
            Object.keys(columns).forEach(status => {
                columns[status].badge.textContent = columns[status].count;
                if (columns[status].count === 0) {
                    columns[status].container.innerHTML = `
                        <div style="text-align: center; color: var(--text-light); font-size: 11px; padding: 20px; border: 1px dashed var(--border-gray); border-radius: 6px; background-color: var(--white);">
                            Vide / Vazio
                        </div>
                    `;
                }
            });
        }

        // Render Table list View
        function renderTable() {
            const tableBody = document.getElementById('leads-table-body');
            tableBody.innerHTML = '';

            if (leadsList.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-light); padding: 40px;">Aucune lead trouvée.</td></tr>`;
                return;
            }

            leadsList.forEach(lead => {
                const tr = document.createElement('tr');
                const volStr = lead.volume_m3 ? `${parseFloat(lead.volume_m3)} m³` : '-';
                const dateStr = lead.created_at ? lead.created_at.substring(0, 10) : '-';
                const serviceDate = lead.service_date ? lead.service_date : 'À convenir';
                
                // Canton or address fallback
                const origin = lead.origin_address ? lead.origin_address.substring(0, 30) : '-';
                const dest = lead.destination_address ? lead.destination_address.substring(0, 30) : '-';
                
                // Status badge label translation
                const statusLabels = {
                    'New': 'Nouveau / Novo',
                    'Contacted': 'En contact',
                    'Visit Scheduled': 'Visite planifiée',
                    'Proposal Sent': 'Proposition envoyée',
                    'Negotiation': 'Négociation',
                    'Won': 'Gagné / Ganho',
                    'Lost': 'Perdu / Perdido'
                };
                const score = parseInt(lead.lead_score || 0);
                let scoreClass = 'cold';
                let category = 'Cold';
                if (score >= 76) { scoreClass = 'priority'; category = 'Priority'; }
                else if (score >= 51) { scoreClass = 'hot'; category = 'Hot'; }
                else if (score >= 26) { scoreClass = 'warm'; category = 'Warm'; }
                
                const scoreBadge = `<span class="score-badge ${scoreClass}">${score} - ${category}</span>`;

                tr.innerHTML = `
                    <td>${dateStr}</td>
                    <td><strong>${lead.name}</strong><br>${scoreBadge}</td>
                    <td>${lead.email}<br><span style="font-size: 11px; color: var(--text-light);">${lead.phone || '-'}</span></td>
                    <td>${volStr}</td>
                    <td style="font-size: 12px; color: var(--text-light);">${origin} ➔ ${dest}</td>
                    <td><span class="status-badge ${statusClass}">${label}</span></td>
                    <td style="text-align: center;">
                        <button class="btn-header" onclick="openLeadModalById(${lead.id})">
                            <i class="fa-solid fa-eye"></i> Gérer
                        </button>
                    </td>
                `;
                tableBody.appendChild(tr);
            });
        }

        // Open details modal
        function openLeadModalById(id) {
            const lead = leadsList.find(l => l.id == id);
            if (lead) {
                openLeadModal(lead);
            }
        }

        function openLeadModal(lead) {
            selectedLead = lead;
            
            // Set fields
            document.getElementById('modal-lead-name').textContent = lead.name;
            document.getElementById('lead-created-at').textContent = lead.created_at || '-';
            
            const badge = document.getElementById('lead-status-badge');
            badge.textContent = lead.status;
            badge.className = `status-badge ${lead.status.toLowerCase().replace(' ', '-')}`;
            
            document.getElementById('lead-email').textContent = lead.email;
            document.getElementById('lead-phone').textContent = lead.phone || '-';
            document.getElementById('lead-origin').textContent = lead.origin_address || '-';
            document.getElementById('lead-destination').textContent = lead.destination_address || '-';
            document.getElementById('lead-service-date').textContent = lead.service_date || 'À convenir';
            document.getElementById('lead-volume').textContent = lead.volume_m3 ? parseFloat(lead.volume_m3) + ' m³' : 'À évaluer';
            document.getElementById('lead-notes').textContent = lead.notes || 'Aucune note particulière.';

            // UTM
            document.getElementById('lead-utm-source').textContent = lead.utm_source || '-';
            document.getElementById('lead-utm-medium').textContent = lead.utm_medium || '-';
            document.getElementById('lead-utm-campaign').textContent = lead.utm_campaign || '-';
            document.getElementById('lead-referer').textContent = lead.referer_url || '-';

            // Set dropdown value
            document.getElementById('lead-status-select').value = lead.status;

            // Handle converted state
            const convertBtn = document.getElementById('btn-convert-lead');
            if (lead.converted_client_id) {
                convertBtn.disabled = true;
                convertBtn.innerHTML = `<i class="fa-solid fa-check"></i> Déjà Converti (Client ID: ${lead.converted_client_id})`;
                convertBtn.style.opacity = '0.6';
            } else {
                convertBtn.disabled = false;
                convertBtn.innerHTML = `<i class="fa-solid fa-user-check"></i> Convertir en Cliente`;
                convertBtn.style.opacity = '1';
            }

            // Populate score widget in details modal
            const score = parseInt(lead.lead_score || 0);
            let scoreClass = 'cold';
            let category = 'Cold';
            if (score >= 76) { scoreClass = 'priority'; category = 'Priority'; }
            else if (score >= 51) { scoreClass = 'hot'; category = 'Hot'; }
            else if (score >= 26) { scoreClass = 'warm'; category = 'Warm'; }
            
            const scoreBadgeEl = document.getElementById('modal-lead-score-badge');
            scoreBadgeEl.textContent = `${score} - ${category}`;
            scoreBadgeEl.className = `score-badge ${scoreClass}`;
            
            const reasonsList = document.getElementById('modal-lead-score-reasons');
            reasonsList.innerHTML = '';
            
            let reasons = [];
            if (lead.lead_score_reasons) {
                try {
                    reasons = typeof lead.lead_score_reasons === 'string' ? JSON.parse(lead.lead_score_reasons) : lead.lead_score_reasons;
                } catch (e) {
                    console.error("Failed to parse score reasons:", e);
                }
            }
            
            if (reasons && reasons.length > 0) {
                reasons.forEach(r => {
                    const li = document.createElement('li');
                    li.innerHTML = `<strong style="color: #007a87;">+${r.points}</strong> ${r.text}`;
                    reasonsList.appendChild(li);
                });
            } else {
                reasonsList.innerHTML = `<li style="list-style: none; color: var(--text-light); margin-left: -20px;">Aucun motif enregistré (Score: 0).</li>`;
            }

            // Open Modal
            document.getElementById('lead-modal').classList.add('show');
        }

        // Close Modal logic
        function closeLeadModal() {
            document.getElementById('lead-modal').classList.remove('show');
            selectedLead = null;
        }

        document.getElementById('modal-close-btn').onclick = closeLeadModal;
        document.getElementById('modal-close-footer-btn').onclick = closeLeadModal;
        document.getElementById('lead-modal').onclick = (e) => {
            if (e.target.id === 'lead-modal') closeLeadModal();
        };

        // Status Changer dropdown change handler
        document.getElementById('lead-status-select').onchange = (e) => {
            if (!selectedLead) return;
            const newStatus = e.target.value;
            
            fetch('/api/v1/leads/leads.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    id: selectedLead.id,
                    status: newStatus
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast("Statut du prospect mis à jour.");
                    // Refresh selected lead status in memory and UI
                    selectedLead.status = newStatus;
                    const badge = document.getElementById('lead-status-badge');
                    badge.textContent = newStatus;
                    badge.className = `status-badge ${newStatus.toLowerCase().replace(' ', '-')}`;
                    
                    loadLeads();
                } else {
                    showToast(data.message || "Échec de mise à jour du statut", 'error');
                }
            })
            .catch(err => {
                console.error("PUT Status error:", err);
                showToast("Erreur de connexion.", 'error');
            });
        };

        // Convert Lead to Client handler
        document.getElementById('btn-convert-lead').onclick = () => {
            if (!selectedLead) return;
            
            if (confirm(`Convertir "${selectedLead.name}" en client actif ?`)) {
                // Change UI state to saving
                const convertBtn = document.getElementById('btn-convert-lead');
                convertBtn.disabled = true;
                convertBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Conversion en cours...`;

                fetch('/api/v1/leads/leads.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        id: selectedLead.id,
                        action: 'convert'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || "Lead converti avec succès.");
                        closeLeadModal();
                        loadLeads();
                    } else {
                        showToast(data.message || "Échec de conversion.", 'error');
                        // Restore button
                        convertBtn.disabled = false;
                        convertBtn.innerHTML = `<i class="fa-solid fa-user-check"></i> Convertir en Cliente`;
                    }
                })
                .catch(err => {
                    console.error("Conversion error:", err);
                    showToast("Erreur réseau.", 'error');
                    // Restore button
                    convertBtn.disabled = false;
                    convertBtn.innerHTML = `<i class="fa-solid fa-user-check"></i> Convertir en Cliente`;
                });
            }
        };
    </script>
</body>
</html>
