<?php
// LIMA Solutions ERP - Timesheets Billing Conversion Dashboard
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';

$companyId = getActiveCompanyId();
$userRole = $_SESSION['user_role'] ?? 'viewer';

enforceModuleAccess('timesheets', $userRole, $companyId, 'edit', $pdo);

// Fetch unbilled approved timesheets
$sql = "SELECT t.*, p.name AS project_name, p.project_code, u.name AS user_name, tk.title AS task_title 
        FROM timesheets t 
        JOIN projects p ON t.project_id = p.id 
        LEFT JOIN project_tasks tk ON t.task_id = tk.id 
        JOIN users u ON t.user_id = u.id 
        WHERE t.company_id = :company_id 
          AND t.status = 'Approved' 
          AND t.invoice_id IS NULL 
          AND t.locked = 1 
          AND t.deleted_at IS NULL 
        ORDER BY t.work_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['company_id' => $companyId]);
$unbilled = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturation des Horas - LIMA Solutions</title>
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
                <a href="list.php"><i class="fa-solid fa-clock"></i> Timesheets</a>
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
                <a href="list.php" class="btn-header"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
        </header>

        <main class="projects-container">
            <div class="projects-header">
                <div class="projects-title">
                    <h2>Facturation des Horas Approuvées</h2>
                    <p>Convertissez les heures approuvées en lignes de facture.</p>
                </div>
            </div>

            <div class="projects-card" style="padding: 20px; margin-bottom: 25px;">
                <div style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="flex-grow: 1;">
                        <label for="billing-group-by" style="font-size: 13px; font-weight: 600; color: var(--text-dark);">Option de Groupement</label>
                        <select id="billing-group-by" class="form-input" style="width: 100%; margin-top: 6px;">
                            <option value="detailed">Détaillé (Une ligne par timesheet)</option>
                            <option value="project">Groupé par Projet</option>
                            <option value="collaborator">Groupé par Collaborateur</option>
                            <option value="date">Groupé par Date</option>
                            <option value="consolidated">Consolidé (Une seule ligne globale)</option>
                        </select>
                    </div>
                    <div>
                        <button id="generate-invoice-btn" class="btn-header" style="background-color: var(--primary-teal); color: white; border: none; padding: 10px 24px;">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Générer la Facture
                        </button>
                    </div>
                </div>
            </div>

            <!-- Timesheets Table -->
            <div class="projects-card">
                <div class="projects-card-header">
                    <span class="projects-card-title"><i class="fa-solid fa-clock"></i> Apontamentos pendentes de faturação</span>
                </div>
                <div class="crm-table-wrapper" style="overflow-x: auto;">
                    <table class="crm-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--bg-light); border-bottom: 1px solid var(--border-gray);">
                                <th style="padding: 12px; width: 40px; text-align: center;"><input type="checkbox" id="select-all-checkbox"></th>
                                <th style="padding: 12px; text-align: left;">Date</th>
                                <th style="padding: 12px; text-align: left;">Projet</th>
                                <th style="padding: 12px; text-align: left;">Tâche</th>
                                <th style="padding: 12px; text-align: left;">Collaborateur</th>
                                <th style="padding: 12px; text-align: left;">Heures</th>
                                <th style="padding: 12px; text-align: left;">Taux Horaire</th>
                                <th style="padding: 12px; text-align: left;">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($unbilled)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-light); padding: 40px;">
                                        Aucun timesheet approuvé en attente de facturation.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($unbilled as $u): ?>
                                    <tr>
                                        <td style="text-align: center;"><input type="checkbox" class="ts-checkbox" value="<?php echo $u['id']; ?>"></td>
                                        <td><?php echo date('d.m.Y', strtotime($u['work_date'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($u['project_code']); ?></strong> - <?php echo htmlspecialchars($u['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($u['task_title'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($u['user_name']); ?></td>
                                        <td><strong><?php echo number_format($u['hours'], 2); ?> h</strong></td>
                                        <td><?php echo number_format($u['approved_billable_rate'], 2); ?> CHF</td>
                                        <td><strong><?php echo number_format($u['hours'] * $u['approved_billable_rate'], 2); ?> CHF</strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let csrfToken = '';
            const selectAll = document.getElementById('select-all-checkbox');
            const checkboxes = document.querySelectorAll('.ts-checkbox');
            const generateBtn = document.getElementById('generate-invoice-btn');
            const toast = document.getElementById('toast');

            function showToast(message, type = '') {
                toast.textContent = message;
                toast.className = 'toast show ' + type;
                setTimeout(() => { toast.classList.remove('show'); }, 3000);
            }

            // Fetch active session to obtain CSRF token
            fetch('../../../api/v1/session.php')
                .then(res => res.json())
                .then(data => {
                    if (data.authenticated) {
                        csrfToken = data.csrf_token || '';
                        document.getElementById('user-display-name').textContent = data.user.name;
                    } else {
                        window.location.href = '../../admin/login.php';
                    }
                });

            // Select All toggling
            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                });
            }

            // Convert to Invoice call
            if (generateBtn) {
                generateBtn.addEventListener('click', () => {
                    const selectedIds = [];
                    checkboxes.forEach(cb => {
                        if (cb.checked) selectedIds.push(parseInt(cb.value));
                    });

                    if (selectedIds.length === 0) {
                        showToast('Veuillez sélectionner au moins un timesheet.', 'error');
                        return;
                    }

                    const groupBy = document.getElementById('billing-group-by').value;

                    generateBtn.disabled = true;
                    generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Génération en cours...';

                    fetch('../../../api/v1/timesheets/billing.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({
                            timesheet_ids: selectedIds,
                            group_by: groupBy,
                            csrf_token: csrfToken
                        })
                    })
                    .then(res => {
                        if (res.status === 409) {
                            return res.json().then(data => { throw new Error(data.message); });
                        }
                        return res.json();
                    })
                    .then(resData => {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = '<i class="fa-solid fa-file-invoice-dollar"></i> Générer la Facture';

                        if (resData.success) {
                            showToast('Facture générée avec succès ! Redirection...', 'success');
                            setTimeout(() => {
                                window.location.href = resData.data.redirect_url;
                            }, 1500);
                        } else {
                            showToast(resData.message, 'error');
                        }
                    })
                    .catch(err => {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = '<i class="fa-solid fa-file-invoice-dollar"></i> Générer la Facture';
                        console.error(err);
                        showToast(err.message || 'Erreur de communication.', 'error');
                    });
                });
            }
        });
    </script>
</body>
</html>
