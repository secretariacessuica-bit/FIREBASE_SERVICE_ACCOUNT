<?php
// LIMA Solutions ERP - Project Form (New/Edit)
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

enforceModuleAccess('projects', $userRole, $companyId, 'edit', $pdo);

$projectId = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $projectId ? 'Modifier Projet' : 'Nouveau Projet'; ?> - LIMA Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="../assets/projects.css">
    <style>
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
        .form-input {
            padding: 10px;
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 14px;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary-teal);
        }
        .full-width {
            grid-column: span 2;
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
            <li class="sidebar-item active">
                <a href="list.php"><i class="fa-solid fa-diagram-project"></i> Projetos</a>
            </li>
            <li class="sidebar-item">
                <a href="../../timesheets/views/list.php"><i class="fa-solid fa-clock"></i> Timesheets</a>
            </li>
            <li class="sidebar-item" id="menu-invoices" style="display: none;">
                <a href="/facture/index.html"><i class="fa-solid fa-file-invoice-dollar"></i> Factures</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="navbar">
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <a href="list.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Annuler</a>
            </div>
        </header>

        <main class="projects-container">
            <div class="projects-header">
                <div class="projects-title">
                    <h2><?php echo $projectId ? 'Modifier Projet' : 'Créer un Nouveau Projet'; ?></h2>
                    <p>Définissez les paramètres du projet, le budget et le client.</p>
                </div>
            </div>

            <div class="projects-card" style="padding: 30px;">
                <form id="project-form">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="project-name">Nom du projet *</label>
                            <input type="text" id="project-name" name="name" class="form-input" required placeholder="Ex: Déménagement de bureau Zurich">
                        </div>

                        <div class="form-group">
                            <label for="project-client-id">Client associé *</label>
                            <select id="project-client-id" name="client_id" class="form-input" required data-selected="">
                                <option value="">Chargement des clients...</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="project-status">Statut du projet</label>
                            <select id="project-status" name="status" class="form-input">
                                <option value="Planning">Planning</option>
                                <option value="Active">Active</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="project-start-date">Date de début</label>
                            <input type="date" id="project-start-date" name="start_date" class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="project-end-date">Date de fin prévue</label>
                            <input type="date" id="project-end-date" name="end_date" class="form-input">
                        </div>

                        <div class="form-group">
                            <label for="project-estimated-hours">Heures estimées</label>
                            <input type="number" id="project-estimated-hours" name="estimated_hours" class="form-input" step="0.5" value="0.0">
                        </div>

                        <div class="form-group">
                            <label for="project-budget">Budget financier</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" id="project-budget" name="budget" class="form-input" style="flex-grow: 1;" step="0.01" value="0.00">
                                <select id="project-currency" name="currency" class="form-input" style="width: 100px;">
                                    <option value="CHF">CHF</option>
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="project-description">Description et objectifs</label>
                            <textarea id="project-description" name="description" class="form-input" style="height: 120px;" placeholder="Détails additionnels..."></textarea>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                        <div>
                            <?php if ($projectId): ?>
                                <button type="button" id="delete-project-btn" class="btn-header" style="background-color: #ef4444; color: white; border: none;">
                                    <i class="fa-solid fa-trash"></i> Supprimer Projet
                                </button>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="list.php" class="btn-header">Annuler</a>
                            <button type="submit" class="btn-header" style="background-color: var(--primary-teal); color: white; border: none; padding: 10px 24px;">
                                <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <div class="toast" id="toast"></div>

    <script src="../assets/projects.js"></script>
</body>
</html>
