<?php
// LIMA Solutions ERP - Timesheets List & Weekly Calendar View
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

enforceModuleAccess('timesheets', $userRole, $companyId, 'view', $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timesheets - LIMA Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="../../projects/assets/projects.css">
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
                <a href="../../projects/views/list.php"><i class="fa-solid fa-diagram-project"></i> Projetos</a>
            </li>
            <li class="sidebar-item active">
                <a href="#"><i class="fa-solid fa-clock"></i> Timesheets</a>
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
                <a href="/admin/index.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <main class="projects-container">
            <div class="projects-header">
                <div class="projects-title">
                    <h2>Calendrier Hebdomadaire des Timesheets</h2>
                    <p>Enregistrez vos heures de travail et suivez vos validations.</p>
                </div>
                <div>
                    <button id="add-hours-btn" class="btn-header" style="background-color: var(--primary-teal); color: white; border: none;">
                        <i class="fa-solid fa-plus"></i> Enregistrer des Heures
                    </button>
                    <a href="billing.php" class="btn-header" style="background-color: #f59e0b; color: white; border: none;"><i class="fa-solid fa-file-invoice-dollar"></i> Facturer Heures</a>
                    <a href="form.php" class="btn-header"><i class="fa-solid fa-file-pen"></i> Formulaire Complet</a>
                </div>
            </div>

            <!-- Weekly Calendar Grid -->
            <div class="projects-card">
                <div class="projects-card-header">
                    <span class="projects-card-title"><i class="fa-solid fa-calendar-week"></i> Cette Semaine</span>
                </div>
                <div class="calendar-view">
                    <div class="calendar-grid-week" id="timesheets-calendar-grid">
                        <!-- Filled dynamically by projects.js -->
                        <div style="grid-column: span 7; text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px; color: var(--primary-teal);"></i><br>
                            Chargement du calendrier...
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Log Timesheet Modal -->
    <div class="projects-modal" id="timesheet-modal">
        <div class="projects-modal-content" style="padding: 24px;">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">Enregistrer des Heures</h3>
            <form id="timesheet-form">
                <input type="hidden" id="ts-id-field" name="id">

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="ts-project-id">Projet *</label>
                            <select id="ts-project-id" name="project_id" class="form-input" required>
                                <option value="">Chargement...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ts-task-id">Tâche associée</label>
                            <select id="ts-task-id" name="task_id" class="form-input">
                                <option value="">-- Sélectionner d'abord un projet --</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="ts-work-date">Date de travail *</label>
                            <input type="date" id="ts-work-date" name="work_date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="ts-hourly-rate">Taux horaire (CHF)</label>
                            <input type="number" id="ts-hourly-rate" name="hourly_rate" class="form-input" step="0.01" value="0.00">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="ts-start-time">Heure de début</label>
                            <input type="time" id="ts-start-time" name="start_time" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="ts-end-time">Heure de fin</label>
                            <input type="time" id="ts-end-time" name="end_time" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="ts-hours">Total Heures (Manuel)</label>
                            <input type="number" id="ts-hours" name="hours" class="form-input" step="0.05" placeholder="Calculé auto ou saisi">
                        </div>
                    </div>

                    <div style="display: flex; gap: 20px; align-items: center; padding: 10px 0;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" name="billable" value="1" checked style="width: 16px; height: 16px; accent-color: var(--primary-teal);">
                            Heures facturables au client
                        </label>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                    <button type="button" class="btn-header" id="close-timesheet-modal">Annuler</button>
                    <button type="submit" class="btn-header" style="background-color: var(--primary-teal); color: white; border: none;">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script src="../../projects/assets/projects.js"></script>
</body>
</html>
