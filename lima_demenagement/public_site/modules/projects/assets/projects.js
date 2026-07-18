// LIMA Solutions ERP - Projects, Tasks Kanban & Calendar JavaScript

document.addEventListener('DOMContentLoaded', () => {
    let csrfToken = '';
    let userRole = 'viewer';
    let userId = 0;
    
    // Timer states
    let timerInterval = null;
    let timerStartTime = null;

    // Toast notifications helper
    const toast = document.getElementById('toast');
    function showToast(message, type = '') {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast show ' + type;
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // Hydrate Session and CSRF
    fetch('../../../api/v1/session.php')
        .then(res => res.json())
        .then(data => {
            if (data.authenticated) {
                csrfToken = data.csrf_token || '';
                userRole = data.user.role || 'viewer';
                userId = data.user.id || 0;
                
                // Hydrate current username
                const display = document.getElementById('user-display-name');
                if (display) display.textContent = data.user.name;

                // Set main branding color
                if (data.active_company && data.active_company.main_color) {
                    document.documentElement.style.setProperty('--primary-teal', data.active_company.main_color);
                }

                // Call initialization methods depending on which DOM elements exist on page
                initializePage();
            } else {
                window.location.href = '../../admin/login.php';
            }
        })
        .catch(err => {
            console.error('Session error:', err);
        });

    function initializePage() {
        // Init Timer
        initTimerWidget();

        // Projects List page
        if (document.getElementById('projects-table-body')) {
            loadProjects();
        }

        // Project Form page
        if (document.getElementById('project-form')) {
            loadClientsForSelect();
            initProjectForm();
        }

        // Project Profile / Kanban page
        if (document.getElementById('kanban-board')) {
            loadProjectProfile();
        }

        // Timesheets view
        if (document.getElementById('timesheets-calendar-grid')) {
            initTimesheetsCalendar();
        }
    }

    // ==========================================
    // TIMER WIDGET LOGIC
    // ==========================================
    function initTimerWidget() {
        const startBtn = document.getElementById('timer-start');
        const stopBtn = document.getElementById('timer-stop');
        const display = document.getElementById('timer-display');
        const projSelect = document.getElementById('timer-project-select');

        if (!display) return;

        // Restore active timer from localStorage if exists
        const savedStart = localStorage.getItem('lima_timer_start');
        const savedProj = localStorage.getItem('lima_timer_proj');
        if (savedStart) {
            timerStartTime = parseInt(savedStart);
            if (projSelect && savedProj) projSelect.value = savedProj;
            startTimerClock();
            if (startBtn) startBtn.style.display = 'none';
            if (stopBtn) stopBtn.style.display = 'inline-flex';
        }

        if (startBtn) {
            startBtn.addEventListener('click', () => {
                if (projSelect && !projSelect.value) {
                    showToast('Veuillez sélectionner un projet.', 'error');
                    return;
                }
                timerStartTime = Date.now();
                localStorage.setItem('lima_timer_start', timerStartTime);
                if (projSelect) localStorage.setItem('lima_timer_proj', projSelect.value);
                startTimerClock();
                startBtn.style.display = 'none';
                stopBtn.style.display = 'inline-flex';
                showToast('Chronomètre démarré.', 'success');
            });
        }

        if (stopBtn) {
            stopBtn.addEventListener('click', () => {
                clearInterval(timerInterval);
                const elapsedSeconds = Math.floor((Date.now() - timerStartTime) / 1000);
                const elapsedHours = (elapsedSeconds / 3600).toFixed(2);
                
                // Clear local storage
                const targetProj = localStorage.getItem('lima_timer_proj');
                localStorage.removeItem('lima_timer_start');
                localStorage.removeItem('lima_timer_proj');
                
                timerStartTime = null;
                display.textContent = '00:00:00';
                startBtn.style.display = 'inline-flex';
                stopBtn.style.display = 'none';

                // Autofill Timesheet Form Modal if available
                openTimesheetModalWithTimer(targetProj, elapsedHours);
            });
        }
    }

    function startTimerClock() {
        const display = document.getElementById('timer-display');
        timerInterval = setInterval(() => {
            const elapsedMs = Date.now() - timerStartTime;
            const secs = Math.floor(elapsedMs / 1000) % 60;
            const mins = Math.floor(elapsedMs / 60000) % 60;
            const hrs = Math.floor(elapsedMs / 3600000);
            
            display.textContent = 
                String(hrs).padStart(2, '0') + ':' +
                String(mins).padStart(2, '0') + ':' +
                String(secs).padStart(2, '0');
        }, 1000);
    }

    function openTimesheetModalWithTimer(projectId, hours) {
        const modal = document.getElementById('timesheet-modal');
        if (!modal) {
            // If modal not on page, prompt directly or redirect to timesheet log
            const register = confirm(`Vous avez travaillé ${hours} heures. Souhaitez-vous enregistrer ce timesheet ?`);
            if (register) {
                window.location.href = `../../timesheets/views/list.php?auto_proj=${projectId}&auto_hours=${hours}`;
            }
            return;
        }

        // Fill modal fields
        const projField = document.getElementById('ts-project-id');
        const hoursField = document.getElementById('ts-hours');
        const dateField = document.getElementById('ts-work-date');

        if (projField) projField.value = projectId;
        if (hoursField) hoursField.value = hours;
        if (dateField) dateField.value = new Date().toISOString().split('T')[0];

        // Trigger task load for project
        if (projField) projField.dispatchEvent(new Event('change'));

        // Show Modal
        modal.classList.add('show');
    }

    // ==========================================
    // PROJECTS CRUD & LISTING
    // ==========================================
    function loadProjects() {
        const tableBody = document.getElementById('projects-table-body');
        if (!tableBody) return;

        fetch('../../../api/v1/projects/projects.php')
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const projects = resData.data.projects || [];
                    if (projects.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-light); padding: 30px;">Aucun projet trouvé.</td></tr>`;
                        return;
                    }
                    tableBody.innerHTML = projects.map(p => `
                        <tr>
                            <td><strong>${p.project_code}</strong></td>
                            <td><a href="profile.php?id=${p.id}" style="color: var(--primary-teal); font-weight: 600; text-decoration: none;">${p.name}</a></td>
                            <td>${p.client_name || '-'}</td>
                            <td><span class="badge" style="background-color: ${getStatusColor(p.status)}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">${p.status}</span></td>
                            <td>${p.start_date || '-'} au ${p.end_date || '-'}</td>
                            <td>${p.estimated_hours} h</td>
                            <td>${p.budget} ${p.currency}</td>
                            <td style="text-align: center;">
                                <a href="profile.php?id=${p.id}" class="btn-header" style="padding: 4px 8px; font-size: 11px;"><i class="fa-solid fa-eye"></i></a>
                                <a href="form.php?id=${p.id}" class="btn-header" style="padding: 4px 8px; font-size: 11px;"><i class="fa-solid fa-pen"></i></a>
                            </td>
                        </tr>
                    `).join('');
                }
            });
    }

    function getStatusColor(status) {
        switch(status) {
            case 'Planning': return '#64748b';
            case 'Active': return '#3b82f6';
            case 'On Hold': return '#f59e0b';
            case 'Completed': return '#10b981';
            case 'Cancelled': return '#ef4444';
            default: return '#64748b';
        }
    }

    // Load clients inside select dropdown
    function loadClientsForSelect() {
        const select = document.getElementById('project-client-id');
        if (!select) return;

        fetch('../../../api/v1/crm/clients.php?limit=200')
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const clients = resData.data.clients || [];
                    const selectedVal = select.getAttribute('data-selected');
                    select.innerHTML = '<option value="">-- Sélectionner un Client --</option>' + 
                        clients.map(c => `<option value="${c.id}" ${selectedVal == c.id ? 'selected' : ''}>${c.name} (${c.company || 'Particulier'})</option>`).join('');
                }
            });
    }

    function initProjectForm() {
        const form = document.getElementById('project-form');
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('id');

        if (projectId && form) {
            // Fetch project values to edit
            fetch(`../../../api/v1/projects/projects.php?id=${projectId}`)
                .then(res => res.json())
                .then(resData => {
                    if (resData.success) {
                        const p = resData.data.project;
                        document.getElementById('project-name').value = p.name;
                        document.getElementById('project-description').value = p.description || '';
                        document.getElementById('project-status').value = p.status;
                        document.getElementById('project-start-date').value = p.start_date || '';
                        document.getElementById('project-end-date').value = p.end_date || '';
                        document.getElementById('project-estimated-hours').value = p.estimated_hours;
                        document.getElementById('project-budget').value = p.budget;
                        document.getElementById('project-currency').value = p.currency;
                        
                        const clientSel = document.getElementById('project-client-id');
                        clientSel.setAttribute('data-selected', p.client_id);
                        loadClientsForSelect();
                    }
                });
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const dataObj = {};
            formData.forEach((value, key) => {
                dataObj[key] = value;
            });
            dataObj['csrf_token'] = csrfToken;

            if (projectId) {
                dataObj['action'] = 'update';
                dataObj['id'] = projectId;
            }

            fetch('../../../api/v1/projects/projects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(dataObj)
            })
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    showToast('Projet enregistré avec succès.', 'success');
                    setTimeout(() => {
                        window.location.href = 'list.php';
                    }, 1500);
                } else {
                    showToast(resData.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur lors de la sauvegarde.', 'error');
            });
        });

        // Handle delete action
        const deleteBtn = document.getElementById('delete-project-btn');
        if (deleteBtn && projectId) {
            deleteBtn.addEventListener('click', () => {
                if (confirm('Voulez-vous vraiment supprimer ce projet ?')) {
                    fetch('../../../api/v1/projects/projects.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            id: projectId,
                            csrf_token: csrfToken
                        })
                    })
                    .then(res => res.json())
                    .then(resData => {
                        if (resData.success) {
                            showToast('Projet supprimé.', 'success');
                            setTimeout(() => { window.location.href = 'list.php'; }, 1000);
                        } else {
                            showToast(resData.message, 'error');
                        }
                    });
                }
            });
        }
    }

    // ==========================================
    // KANBAN BOARD & TASKS MANAGEMENT
    // ==========================================
    function loadProjectProfile() {
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('id');
        if (!projectId) return;

        fetch(`../../../api/v1/projects/projects.php?id=${projectId}`)
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const p = resData.data.project;
                    const tasks = resData.data.tasks || [];

                    // Hydrate view headers
                    document.getElementById('p-code').textContent = p.project_code;
                    document.getElementById('p-name').textContent = p.name;
                    document.getElementById('p-client').textContent = p.client_name;
                    document.getElementById('p-status').textContent = p.status;
                    document.getElementById('p-dates').textContent = (p.start_date || '-') + ' au ' + (p.end_date || '-');
                    document.getElementById('p-hours').textContent = p.estimated_hours + ' h';
                    document.getElementById('p-budget').textContent = p.budget + ' ' + p.currency;
                    document.getElementById('p-desc').textContent = p.description || 'Aucune description';

                    renderKanbanBoard(tasks);
                    loadTimesheetsForProject(projectId);

                    // Hydrate Margin Analytics
                    if (p.margin_analytics) {
                        const m = p.margin_analytics;
                        document.getElementById('project-margin-card').style.display = 'block';
                        document.getElementById('m-revenue').textContent = m.revenue.toFixed(2) + ' ' + p.currency;
                        document.getElementById('m-hours').textContent = m.hours.toFixed(2) + ' h';
                        document.getElementById('m-cost').textContent = m.cost.toFixed(2) + ' ' + p.currency;
                        document.getElementById('m-margin').textContent = m.margin.toFixed(2) + ' ' + p.currency;
                        
                        const pctElem = document.getElementById('m-margin-pct');
                        pctElem.textContent = m.margin_pct.toFixed(2) + '%';
                        
                        // Margin warnings
                        const alertElem = document.getElementById('margin-warning-alert');
                        if (m.margin_pct < 25.0) {
                            pctElem.style.color = '#ef4444'; // Red
                            if (alertElem) alertElem.style.display = 'block';
                        } else {
                            pctElem.style.color = '#10b981'; // Green
                            if (alertElem) alertElem.style.display = 'none';
                        }
                    }

                    // Render current assigned team
                    const assignedBox = document.getElementById('current-assigned-team-box');
                    const assignedList = document.getElementById('current-assigned-team-list');
                    if (assignedBox && assignedList) {
                        assignedList.innerHTML = '';
                        if (p.assigned_team && p.assigned_team.length > 0) {
                            assignedBox.style.display = 'block';
                            p.assigned_team.forEach(member => {
                                const tag = document.createElement('span');
                                tag.style.cssText = 'background-color: var(--primary-teal); color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; margin: 2px;';
                                tag.innerHTML = `<i class="fa-solid fa-user"></i> ${member.name} (${member.role})`;
                                assignedList.appendChild(tag);
                            });
                        } else {
                            assignedBox.style.display = 'block';
                            assignedList.innerHTML = '<span style="font-size: 12px; color: var(--text-light); font-style: italic;">Nenhum colaborador atribuído atualmente.</span>';
                        }
                    }

                    // Render team recommendations
                    const recList = document.getElementById('recommendations-list');
                    if (recList) {
                        recList.innerHTML = '';
                        if (p.recommendations && p.recommendations.length > 0) {
                            p.recommendations.forEach((rec, idx) => {
                                const card = document.createElement('div');
                                card.style.cssText = 'background-color: var(--bg-light); border: 1px solid var(--border-gray); padding: 15px; border-radius: 8px; display: flex; flex-direction: column; gap: 8px; position: relative; margin-bottom: 8px;';
                                
                                let scoreColor = '#ef4444'; // Red
                                if (rec.score >= 80) scoreColor = '#10b981'; // Green
                                else if (rec.score >= 60) scoreColor = '#f59e0b'; // Orange
                                
                                const reasonItems = rec.reasons.split(' | ').map(r => `<li style="margin-bottom: 2px;">${r}</li>`).join('');
                                const memberIds = JSON.stringify(rec.members.map(m => m.id));

                                card.innerHTML = `
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-weight: 700; color: var(--text-dark); font-size: 14px;">${rec.team_name}</span>
                                        <span style="background-color: ${scoreColor}; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Score: ${rec.score}%</span>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-dark); margin: 5px 0 10px 0;">
                                        <ul style="margin: 0; padding-left: 15px; line-height: 1.6;">
                                            ${reasonItems}
                                        </ul>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-gray); padding-top: 10px; margin-top: 5px;">
                                        <div style="display: flex; gap: 15px; font-size: 11px; color: var(--text-light); font-weight: 600;">
                                            <span><i class="fa-solid fa-truck"></i> Distância: ${rec.distance}</span>
                                            <span><i class="fa-solid fa-clock"></i> Carga: ${rec.workload}</span>
                                        </div>
                                        <button class="btn-assign-team btn-header" data-project-id="${projectId}" data-members='${memberIds}' style="background-color: var(--primary-teal); color: white; border: none; font-size: 11px; padding: 4px 10px; border-radius: 4px; font-weight: 700; cursor: pointer;">
                                            <i class="fa-solid fa-user-plus"></i> Atribuir Equipa
                                        </button>
                                    </div>
                                `;
                                recList.appendChild(card);
                            });

                            // Add Event Listeners to Assign Buttons
                            document.querySelectorAll('.btn-assign-team').forEach(btn => {
                                btn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    const pid = this.getAttribute('data-project-id');
                                    const members = JSON.parse(this.getAttribute('data-members'));

                                    if (confirm("Deseja realmente atribuir esta equipa ao projeto? Esta ação irá atualizar as atribuições operacionais atuais.")) {
                                        fetch('../../../api/v1/projects/projects.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-Token': csrfToken
                                            },
                                            body: JSON.stringify({
                                                action: 'assign_team',
                                                project_id: pid,
                                                user_ids: members
                                            })
                                        })
                                        .then(res => res.json())
                                        .then(resData => {
                                            if (resData.success) {
                                                showToast('Equipa atribuída com sucesso!', 'success');
                                                loadProjectProfile();
                                            } else {
                                                showToast(resData.message || 'Erro ao atribuir equipa.', 'error');
                                            }
                                        });
                                    }
                                });
                            });
                        } else {
                            recList.innerHTML = '<p style="color: var(--text-light); font-size: 13px; font-style: italic;">Nenhuma recomendação disponível para este projeto.</p>';
                        }
                    }
                }
            });

        // Initialize Task Modal & form
        const taskModal = document.getElementById('task-modal');
        const createTaskBtn = document.getElementById('create-task-btn');
        const closeTaskBtn = document.getElementById('close-task-modal');
        const taskForm = document.getElementById('task-form');

        if (createTaskBtn && taskModal) {
            createTaskBtn.addEventListener('click', () => {
                taskForm.reset();
                document.getElementById('task-id-field').value = '';
                document.getElementById('task-project-id').value = projectId;
                loadUsersForSelect('task-assigned-user');
                taskModal.classList.add('show');
            });
        }

        if (closeTaskBtn && taskModal) {
            closeTaskBtn.addEventListener('click', () => {
                taskModal.classList.remove('show');
            });
        }

        if (taskForm) {
            taskForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(taskForm);
                const dataObj = {};
                formData.forEach((value, key) => {
                    dataObj[key] = value;
                });
                dataObj['csrf_token'] = csrfToken;

                const taskId = document.getElementById('task-id-field').value;
                if (taskId) {
                    dataObj['action'] = 'update_task';
                    dataObj['id'] = taskId;
                } else {
                    dataObj['action'] = 'create_task';
                }

                fetch('../../../api/v1/projects/projects.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(dataObj)
                })
                .then(res => res.json())
                .then(resData => {
                    if (resData.success) {
                        showToast('Tâche enregistrée avec succès.', 'success');
                        taskModal.classList.remove('show');
                        loadProjectProfile();
                    } else {
                        showToast(resData.message, 'error');
                    }
                });
            });
        }
    }

    function loadUsersForSelect(selectId, selectedVal = '') {
        const select = document.getElementById(selectId);
        if (!select) return;

        // In a real system we fetch company users
        fetch('../../../api/v1/session.php')
            .then(res => res.json())
            .then(data => {
                // For safety and fallback we can just populate the dropdown with options
                // Let's call a minimal user fetch or simulate options
                select.innerHTML = `<option value="">-- Non Assigné --</option>
                    <option value="${data.user.id}" ${selectedVal == data.user.id ? 'selected' : ''}>${data.user.name} (Vous)</option>`;
            });
    }

    function renderKanbanBoard(tasks) {
        const columns = {
            'Todo': document.getElementById('column-todo-list'),
            'In Progress': document.getElementById('column-inprogress-list'),
            'Review': document.getElementById('column-review-list'),
            'Done': document.getElementById('column-done-list')
        };

        // Reset columns HTML
        Object.keys(columns).forEach(k => {
            if (columns[k]) columns[k].innerHTML = '';
        });

        tasks.forEach(t => {
            const list = columns[t.status];
            if (!list) return;

            const card = document.createElement('div');
            card.className = 'kanban-card';
            card.setAttribute('draggable', 'true');
            card.setAttribute('data-task-id', t.id);
            
            card.innerHTML = `
                <div class="kanban-card-title">${t.title}</div>
                <div class="kanban-card-desc">${t.description || ''}</div>
                <div class="kanban-card-meta">
                    <span class="kanban-card-code">${t.task_code}</span>
                    <span class="kanban-card-badge badge-${(t.priority || 'medium').toLowerCase()}">${t.priority}</span>
                </div>
                <div style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button class="btn-edit-task" style="background: none; border: none; cursor: pointer; color: var(--primary-teal);" data-id="${t.id}"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-delete-task" style="background: none; border: none; cursor: pointer; color: #ef4444;" data-id="${t.id}"><i class="fa-solid fa-trash"></i></button>
                </div>
            `;

            // Hook Drag events
            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', t.id);
                card.style.opacity = '0.5';
            });

            card.addEventListener('dragend', () => {
                card.style.opacity = '1';
            });

            // Hook edit/delete task buttons
            card.querySelector('.btn-edit-task').addEventListener('click', (e) => {
                e.stopPropagation();
                openEditTaskModal(t.id);
            });

            card.querySelector('.btn-delete-task').addEventListener('click', (e) => {
                e.stopPropagation();
                deleteTask(t.id);
            });

            list.appendChild(card);
        });

        // Set up drop zones on columns
        Object.keys(columns).forEach(colStatus => {
            const list = columns[colStatus];
            if (!list) return;

            list.addEventListener('dragover', (e) => {
                e.preventDefault();
            });

            list.addEventListener('drop', (e) => {
                e.preventDefault();
                const taskId = e.dataTransfer.getData('text/plain');
                if (taskId) {
                    updateTaskStatus(taskId, colStatus);
                }
            });
        });
    }

    function updateTaskStatus(taskId, newStatus) {
        // Fetch task current data first
        fetch(`../../../api/v1/projects/projects.php?task_id=${taskId}`)
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const task = resData.data.task;
                    task.status = newStatus;
                    task.action = 'update_task';
                    task.csrf_token = csrfToken;

                    fetch('../../../api/v1/projects/projects.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify(task)
                    })
                    .then(res => res.json())
                    .then(resData => {
                        if (resData.success) {
                            showToast('Statut de la tâche mis à jour.', 'success');
                            loadProjectProfile();
                        } else {
                            showToast(resData.message, 'error');
                        }
                    });
                }
            });
    }

    function openEditTaskModal(taskId) {
        const taskModal = document.getElementById('task-modal');
        const taskForm = document.getElementById('task-form');

        fetch(`../../../api/v1/projects/projects.php?task_id=${taskId}`)
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const t = resData.data.task;
                    document.getElementById('task-id-field').value = t.id;
                    document.getElementById('task-project-id').value = t.project_id;
                    document.getElementById('task-title').value = t.title;
                    document.getElementById('task-description').value = t.description || '';
                    document.getElementById('task-priority').value = t.priority;
                    document.getElementById('task-status').value = t.status;
                    document.getElementById('task-due-date').value = t.due_date || '';
                    document.getElementById('task-estimated-hours').value = t.estimated_hours;

                    loadUsersForSelect('task-assigned-user', t.assigned_user_id);
                    taskModal.classList.add('show');
                }
            });
    }

    function deleteTask(taskId) {
        if (confirm('Voulez-vous supprimer cette tâche ?')) {
            fetch('../../../api/v1/projects/projects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    action: 'delete_task',
                    id: taskId,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    showToast('Tâche supprimée.', 'success');
                    loadProjectProfile();
                } else {
                    showToast(resData.message, 'error');
                }
            });
        }
    }

    // ==========================================
    // TIMESHEETS LOGGING & WORKFLOW & CALENDAR
    // ==========================================
    function loadTimesheetsForProject(projectId) {
        const list = document.getElementById('project-timesheets-list');
        if (!list) return;

        fetch(`../../../api/v1/timesheets/timesheets.php?project_id=${projectId}`)
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const timesheets = resData.data.timesheets || [];
                    if (timesheets.length === 0) {
                        list.innerHTML = `<p style="color: var(--text-light); padding: 15px;">Aucune heure enregistrée pour ce projet.</p>`;
                        return;
                    }
                    list.innerHTML = `
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th>Collaborateur</th>
                                    <th>Date</th>
                                    <th>Heures</th>
                                    <th>Taux Horaire</th>
                                    <th>Facturable</th>
                                    <th>Statut</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${timesheets.map(t => `
                                    <tr>
                                        <td>${t.user_name}</td>
                                        <td>${t.work_date} ${t.start_time ? `(${t.start_time} - ${t.end_time})` : ''}</td>
                                        <td><strong>${t.hours} h</strong></td>
                                        <td>${t.hourly_rate} CHF</td>
                                        <td>${t.billable == 1 ? '<span style="color: green;">Oui</span>' : '<span style="color: red;">Non</span>'}</td>
                                        <td><span class="badge" style="background-color: ${getTimesheetStatusColor(t.status)}; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;">${t.status}</span></td>
                                        <td style="text-align: center;">
                                            ${t.status === 'Draft' ? `
                                                <button class="btn-header submit-timesheet-btn" data-id="${t.id}" style="padding: 2px 6px; font-size: 11px;">Soumettre</button>
                                            ` : ''}
                                            ${t.status === 'Submitted' && ['admin', 'super_admin', 'finance'].includes(userRole) ? `
                                                <button class="btn-header approve-timesheet-btn" data-id="${t.id}" style="padding: 2px 6px; font-size: 11px; background-color: #ecfdf5; color: #10b981; border-color: #10b981;">Approuver</button>
                                                <button class="btn-header reject-timesheet-btn" data-id="${t.id}" style="padding: 2px 6px; font-size: 11px; background-color: #fef2f2; color: #ef4444; border-color: #ef4444;">Rejeter</button>
                                            ` : ''}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;

                    // Hook timesheet actions
                    list.querySelectorAll('.submit-timesheet-btn').forEach(btn => {
                        btn.addEventListener('click', () => submitTimesheet(btn.getAttribute('data-id')));
                    });
                    list.querySelectorAll('.approve-timesheet-btn').forEach(btn => {
                        btn.addEventListener('click', () => approveTimesheet(btn.getAttribute('data-id')));
                    });
                    list.querySelectorAll('.reject-timesheet-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const reason = prompt('Veuillez saisir le motif du rejet :');
                            if (reason) {
                                rejectTimesheet(btn.getAttribute('data-id'), reason);
                            }
                        });
                    });
                }
            });
    }

    function getTimesheetStatusColor(status) {
        switch(status) {
            case 'Draft': return '#64748b';
            case 'Submitted': return '#3b82f6';
            case 'Approved': return '#10b981';
            case 'Rejected': return '#ef4444';
            default: return '#64748b';
        }
    }

    function submitTimesheet(id) {
        fetch('../../../api/v1/timesheets/timesheets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'submit', id: id, csrf_token: csrfToken })
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.success) {
                showToast('Feuille de temps soumise avec succès.', 'success');
                loadProjectProfile();
            } else {
                showToast(resData.message, 'error');
            }
        });
    }

    function approveTimesheet(id) {
        fetch('../../../api/v1/timesheets/timesheets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'approve', id: id, csrf_token: csrfToken })
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.success) {
                showToast('Feuille de temps approuvée.', 'success');
                loadProjectProfile();
            } else {
                showToast(resData.message, 'error');
            }
        });
    }

    function rejectTimesheet(id, reason) {
        fetch('../../../api/v1/timesheets/timesheets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'reject', id: id, rejection_reason: reason, csrf_token: csrfToken })
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.success) {
                showToast('Feuille de temps rejetée.', 'success');
                loadProjectProfile();
            } else {
                showToast(resData.message, 'error');
            }
        });
    }

    // ==========================================
    // WEEKLY/MONTHLY CALENDAR VIEWS
    // ==========================================
    function initTimesheetsCalendar() {
        const grid = document.getElementById('timesheets-calendar-grid');
        if (!grid) return;

        // Modal triggers
        const addHoursBtn = document.getElementById('add-hours-btn');
        const tsModal = document.getElementById('timesheet-modal');
        const closeTsModal = document.getElementById('close-timesheet-modal');
        const tsForm = document.getElementById('timesheet-form');

        if (addHoursBtn && tsModal) {
            addHoursBtn.addEventListener('click', () => {
                tsForm.reset();
                document.getElementById('ts-id-field').value = '';
                tsModal.classList.add('show');
            });
        }

        if (closeTsModal && tsModal) {
            closeTsModal.addEventListener('click', () => {
                tsModal.classList.remove('show');
            });
        }

        // Fill select project options
        loadProjectsForTimesheetSelect();

        // Load timesheets for calendar
        loadTimesheetsCalendar();

        if (tsForm) {
            tsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(tsForm);
                const dataObj = {};
                formData.forEach((value, key) => {
                    dataObj[key] = value;
                });
                dataObj['csrf_token'] = csrfToken;

                fetch('../../../api/v1/timesheets/timesheets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(dataObj)
                })
                .then(res => res.json())
                .then(resData => {
                    if (resData.success) {
                        showToast('Apontamento de horas salvo com sucesso.', 'success');
                        tsModal.classList.remove('show');
                        loadTimesheetsCalendar();
                    } else {
                        showToast(resData.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Erreur de communication.', 'error');
                });
            });
        }
    }

    function loadProjectsForTimesheetSelect() {
        const select = document.getElementById('ts-project-id');
        if (!select) return;

        fetch('../../../api/v1/projects/projects.php')
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const projects = resData.data.projects || [];
                    select.innerHTML = '<option value="">-- Sélectionner --</option>' + 
                        projects.map(p => `<option value="${p.id}">${p.name}</option>`).join('');

                    // Hook project selection to load its tasks
                    select.addEventListener('change', () => {
                        const projectId = select.value;
                        loadTasksForTimesheetSelect(projectId);
                    });
                }
            });
    }

    function loadTasksForTimesheetSelect(projectId) {
        const select = document.getElementById('ts-task-id');
        if (!select) return;

        if (!projectId) {
            select.innerHTML = '<option value="">-- Sélectionner d\'abord un projet --</option>';
            return;
        }

        fetch(`../../../api/v1/projects/projects.php?id=${projectId}`)
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const tasks = resData.data.tasks || [];
                    select.innerHTML = '<option value="">-- Aucune --</option>' + 
                        tasks.map(t => `<option value="${t.id}">${t.title}</option>`).join('');
                }
            });
    }

    function loadTimesheetsCalendar() {
        const grid = document.getElementById('timesheets-calendar-grid');
        if (!grid) return;

        // Render current week (Mon to Sun)
        const today = new Date();
        const firstDay = today.getDate() - today.getDay() + 1; // Mon
        const weekDates = [];
        
        for (let i = 0; i < 7; i++) {
            const d = new Date(today.setDate(firstDay + i));
            weekDates.push(d.toISOString().split('T')[0]);
        }

        // Fetch timesheet entries for active week
        const startDate = weekDates[0];
        const endDate = weekDates[6];

        fetch(`../../../api/v1/timesheets/timesheets.php?start_date=${startDate}&end_date=${endDate}`)
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const timesheets = resData.data.timesheets || [];
                    
                    grid.innerHTML = weekDates.map((dateStr, i) => {
                        const dayName = new Date(dateStr).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'short' });
                        const dayEntries = timesheets.filter(t => t.work_date === dateStr);
                        
                        return `
                            <div class="calendar-day-cell ${new Date().toISOString().split('T')[0] === dateStr ? 'today' : ''}">
                                <div class="calendar-date-num">${dayName}</div>
                                <div class="calendar-entries-list">
                                    ${dayEntries.map(e => `
                                        <div class="calendar-entry ${e.status}" onclick="alert('Timesheet details:\\nProj: ${e.project_name}\\nHours: ${e.hours} h\\nStatus: ${e.status}')">
                                            <strong>${e.hours}h</strong> - ${e.project_name}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            });
    }

    // Helper functions
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function escapeHtml(string) {
        return String(string).replace(/[&<>"']/g, function (s) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[s];
        });
    }
});
