<?php
// LIMA Solutions ERP - Project Profile (Details, Tasks & Timesheets)
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

enforceModuleAccess('projects', $userRole, $companyId, 'view', $pdo);

$projectId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$projectId) {
    header('Location: list.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Projet - LIMA Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="../assets/projects.css">
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
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="navbar">
            <div class="user-menu">
                <span class="user-name" id="user-display-name">...</span>
                <a href="list.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Liste des projets</a>
            </div>
        </header>

        <main class="projects-container">
            <!-- Project Header Details -->
            <div class="projects-card" style="padding: 24px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <span id="p-code" style="font-weight: 700; font-family: monospace; background-color: var(--bg-light); padding: 4px 8px; border-radius: 4px; font-size: 13px;">-</span>
                        <h2 id="p-name" style="font-size: 22px; font-weight: 700; margin-top: 8px; color: var(--text-dark);">-</h2>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="form.php?id=<?php echo $projectId; ?>" class="btn-header"><i class="fa-solid fa-pen"></i> Modifier</a>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; border-top: 1px solid var(--border-gray); padding-top: 20px;">
                    <div>
                        <div style="font-size: 12px; color: var(--text-light); font-weight: 600; text-transform: uppercase;">Client</div>
                        <div id="p-client" style="font-size: 14px; font-weight: 600; margin-top: 4px;">-</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-light); font-weight: 600; text-transform: uppercase;">Statut</div>
                        <div id="p-status" style="font-size: 14px; font-weight: 600; margin-top: 4px;">-</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-light); font-weight: 600; text-transform: uppercase;">Période</div>
                        <div id="p-dates" style="font-size: 14px; font-weight: 600; margin-top: 4px;">-</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-light); font-weight: 600; text-transform: uppercase;">Estimé</div>
                        <div id="p-hours" style="font-size: 14px; font-weight: 600; margin-top: 4px;">-</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-light); font-weight: 600; text-transform: uppercase;">Budget</div>
                        <div id="p-budget" style="font-size: 14px; font-weight: 600; margin-top: 4px;">-</div>
                    </div>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-gray);">
                    <div style="font-size: 12px; color: var(--text-light); font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">Description</div>
                    <p id="p-desc" style="font-size: 14px; color: var(--text-dark); line-height: 1.5;">-</p>
                </div>

                <!-- Margin Operational Analytics Widget -->
                <div id="project-margin-card" style="margin-top: 20px; padding: 20px; border-top: 1px solid var(--border-gray); background-color: var(--bg-light); border-radius: var(--border-radius); display: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-chart-pie" style="color: var(--primary-teal);"></i>
                            Analyse de Marge Opérationnelle
                        </h4>
                        <div id="margin-warning-alert" style="display: none; background-color: #fee2e2; border: 1px solid #fecaca; color: #dc2626; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Attention: Marge faible (&lt; 25%)
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px;">
                        <div style="background-color: var(--white); padding: 12px; border-radius: 6px; border: 1px solid var(--border-gray);">
                            <span style="font-size: 11px; color: var(--text-light); font-weight: 600;">Receita (Facturé)</span>
                            <div id="m-revenue" style="font-size: 16px; font-weight: 700; margin-top: 4px; color: var(--text-dark);">-</div>
                        </div>
                        <div style="background-color: var(--white); padding: 12px; border-radius: 6px; border: 1px solid var(--border-gray);">
                            <span style="font-size: 11px; color: var(--text-light); font-weight: 600;">Heures Travaillées</span>
                            <div id="m-hours" style="font-size: 16px; font-weight: 700; margin-top: 4px; color: var(--text-dark);">-</div>
                        </div>
                        <div style="background-color: var(--white); padding: 12px; border-radius: 6px; border: 1px solid var(--border-gray);">
                            <span style="font-size: 11px; color: var(--text-light); font-weight: 600;">Custo Mão de Obra</span>
                            <div id="m-cost" style="font-size: 16px; font-weight: 700; margin-top: 4px; color: var(--text-dark);">-</div>
                        </div>
                        <div style="background-color: var(--white); padding: 12px; border-radius: 6px; border: 1px solid var(--border-gray);">
                            <span style="font-size: 11px; color: var(--text-light); font-weight: 600;">Margem Bruta</span>
                            <div id="m-margin" style="font-size: 16px; font-weight: 700; margin-top: 4px; color: var(--text-dark);">-</div>
                        </div>
                        <div style="background-color: var(--white); padding: 12px; border-radius: 6px; border: 1px solid var(--border-gray);">
                            <span style="font-size: 11px; color: var(--text-light); font-weight: 600;">Margem %</span>
                            <div id="m-margin-pct" style="font-size: 16px; font-weight: 700; margin-top: 4px; color: var(--text-dark);">-</div>
                        </div>
                    </div>
                </div>

                <!-- Team Suggestions & Recommendation Engine Widget -->
                <div id="project-team-recommendations-card" style="margin-top: 20px; padding: 20px; border-top: 1px solid var(--border-gray); background-color: var(--white); border-radius: var(--border-radius);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-people-group" style="color: var(--primary-teal);"></i>
                            Sugestões de Equipa
                        </h4>
                        <span style="font-size: 11px; background-color: var(--bg-light); color: var(--text-light); padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                            <i class="fa-solid fa-map-pin"></i> Distância por NPA (Aproximação)
                        </span>
                    </div>

                    <!-- Current Assigned Team -->
                    <div id="current-assigned-team-box" style="margin-bottom: 15px; padding: 12px; border-radius: 6px; border: 1px solid var(--border-gray); background-color: var(--bg-light); display: none;">
                        <span style="font-size: 11px; color: var(--text-light); font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 6px;">Equipa Atribuída Atualmente:</span>
                        <div id="current-assigned-team-list" style="display: flex; gap: 8px; flex-wrap: wrap;"></div>
                    </div>

                    <!-- Recommendation List -->
                    <div id="recommendations-list" style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- Dynamically populated via projects.js -->
                    </div>
                </div>
            </div>

            <!-- Kanban Tasks section -->
            <div class="projects-card">
                <div class="projects-card-header">
                    <span class="projects-card-title"><i class="fa-solid fa-list-check"></i> Tableau Kanban des Tâches</span>
                    <button id="create-task-btn" class="btn-header" style="background-color: var(--primary-teal); color: white; border: none; font-size: 12px; padding: 6px 12px;">
                        <i class="fa-solid fa-plus"></i> Ajouter une Tâche
                    </button>
                </div>

                <div class="kanban-board" id="kanban-board">
                    <!-- Column Todo -->
                    <div class="kanban-column">
                        <div class="kanban-column-header kanban-column-todo">À Faire (Todo)</div>
                        <div class="kanban-cards-list" id="column-todo-list"></div>
                    </div>
                    <!-- Column In Progress -->
                    <div class="kanban-column">
                        <div class="kanban-column-header kanban-column-inprogress">En Cours</div>
                        <div class="kanban-cards-list" id="column-inprogress-list"></div>
                    </div>
                    <!-- Column Review -->
                    <div class="kanban-column">
                        <div class="kanban-column-header kanban-column-review">À Valider (Review)</div>
                        <div class="kanban-cards-list" id="column-review-list"></div>
                    </div>
                    <!-- Column Done -->
                    <div class="kanban-column">
                        <div class="kanban-column-header kanban-column-done">Terminé (Done)</div>
                        <div class="kanban-cards-list" id="column-done-list"></div>
                    </div>
                </div>
            </div>

            <!-- Timesheets Logged for Project -->
            <div class="projects-card">
                <div class="projects-card-header">
                    <span class="projects-card-title"><i class="fa-solid fa-clock"></i> Heures Enregistrées</span>
                </div>
                <div id="project-timesheets-list">
                    <p style="color: var(--text-light); padding: 15px;">Chargement des timesheets...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Task Create/Edit Modal -->
    <div class="projects-modal" id="task-modal">
        <div class="projects-modal-content" style="padding: 24px;">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark);">Détails de la Tâche</h3>
            <form id="task-form">
                <input type="hidden" id="task-id-field" name="id">
                <input type="hidden" id="task-project-id" name="project_id">

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div class="form-group">
                        <label for="task-title">Titre de la tâche *</label>
                        <input type="text" id="task-title" name="title" class="form-input" required placeholder="Ex: Peinture de la cuisine">
                    </div>

                    <div class="form-group">
                        <label for="task-assigned-user">Assigner à</label>
                        <select id="task-assigned-user" name="assigned_user_id" class="form-input">
                            <option value="">Chargement...</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="task-priority">Priorité</label>
                            <select id="task-priority" name="priority" class="form-input">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="task-status">Statut</label>
                            <select id="task-status" name="status" class="form-input">
                                <option value="Todo">Todo</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Review">Review</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="task-due-date">Date d'échéance</label>
                            <input type="date" id="task-due-date" name="due_date" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="task-estimated-hours">Heures estimées</label>
                            <input type="number" id="task-estimated-hours" name="estimated_hours" class="form-input" step="0.5" value="0.0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="task-description">Description de la tâche</label>
                        <textarea id="task-description" name="description" class="form-input" style="height: 80px;" placeholder="Tâches à réaliser..."></textarea>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                    <button type="button" class="btn-header" id="close-task-modal">Annuler</button>
                    <button type="submit" class="btn-header" style="background-color: var(--primary-teal); color: white; border: none;">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script src="../assets/projects.js"></script>
</body>
</html>
