// LIMA Solutions ERP - CRM Modules JavaScript Helper V1.1 (Hardened)

document.addEventListener('DOMContentLoaded', () => {
    const clientsTableBody = document.getElementById('clients-table-body');
    const searchInput = document.getElementById('crm-search-input');
    const clientForm = document.getElementById('crm-client-form');
    const toast = document.getElementById('toast');
    
    let csrfToken = '';
    let currentPage = 1;
    let limitPerPage = 50;

    // Toast alerts helper
    function showToast(message, type = '') {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast show ' + type;
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // Fetch active session to read CSRF token
    fetch('../../../api/v1/session.php')
        .then(res => res.json())
        .then(data => {
            if (data.authenticated) {
                csrfToken = data.csrf_token || '';
                // Trigger initial client list load if we are on list view
                if (clientsTableBody) {
                    loadClients();
                }
            } else {
                window.location.href = '../../admin/login.php';
            }
        })
        .catch(err => {
            console.error('Session retrieval error:', err);
        });

    // List view hooks
    if (clientsTableBody) {
        // Search trigger with simple debounce
        if (searchInput) {
            searchInput.addEventListener('input', debounce(() => {
                currentPage = 1; // Reset to page 1 on new search query
                const term = searchInput.value.trim();
                loadClients(term);
            }, 300));
        }
    }

    // Form submit hooks
    if (clientForm) {
        clientForm.addEventListener('submit', (e) => {
            e.preventDefault();
            saveClient();
        });
    }

    // Load clients via AJAX
    function loadClients(search = '') {
        // Show loading state
        clientsTableBody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; color: var(--text-light); padding: 40px;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px; color: var(--primary-teal);"></i><br>
                    Recherche en cours...
                </td>
            </tr>
        `;

        let url = `../../../api/v1/crm/clients.php?page=${currentPage}&limit=${limitPerPage}`;
        if (search) {
            url += '&search=' + encodeURIComponent(search);
        }

        fetch(url)
            .then(res => {
                if (res.status === 403) {
                    throw new Error("Accès interdit. Vérifiez vos permissions.");
                }
                return res.json();
            })
            .then(resData => {
                if (resData.success) {
                    // Extract client list depending on format
                    const clients = resData.data.clients || [];
                    renderClientsTable(clients);
                } else {
                    showToast(resData.message || 'Erreur de chargement.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                clientsTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #ef4444; padding: 20px;">Erreur: ${escapeHtml(err.message)}</td></tr>`;
                showToast(err.message || 'Erreur de communication.', 'error');
            });
    }

    // Render client list rows
    function renderClientsTable(clients) {
        clientsTableBody.innerHTML = '';
        if (clients.length === 0) {
            clientsTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-light); padding: 20px;">Aucun client trouvé.</td></tr>`;
            return;
        }

        clients.forEach(client => {
            const tr = document.createElement('tr');
            
            // Format Tags
            let tagsHtml = '';
            if (client.tags) {
                client.tags.split(',').forEach(tag => {
                    if (tag.trim()) {
                        tagsHtml += `<span class="crm-badge-tag">${escapeHtml(tag.trim())}</span>`;
                    }
                });
            }

            tr.innerHTML = `
                <td><span class="crm-badge-code">${escapeHtml(client.customer_code)}</span></td>
                <td>
                    <strong>${escapeHtml(client.name)}</strong>
                    ${client.company ? `<br><small style="color: var(--text-light);"><i class="fa-solid fa-building"></i> ${escapeHtml(client.company)}</small>` : ''}
                </td>
                <td>${escapeHtml(client.city)} (${escapeHtml(client.canton || '-')})</td>
                <td>
                    <i class="fa-solid fa-phone"></i> ${escapeHtml(client.phone || client.mobile || '-')}
                    ${client.email ? `<br><small style="color: var(--text-light);"><i class="fa-solid fa-envelope"></i> ${escapeHtml(client.email)}</small>` : ''}
                </td>
                <td>${tagsHtml}</td>
                <td class="crm-actions-cell" style="text-align: center;">
                    <a href="profile.php?id=${client.id}" class="btn-crm" title="Profil complet"><i class="fa-solid fa-user"></i> Profil</a>
                    <a href="form.php?id=${client.id}" class="btn-crm btn-crm-primary" title="Modifier"><i class="fa-solid fa-pen"></i> Modifier</a>
                    <button type="button" class="btn-crm btn-crm-danger delete-client-btn" data-id="${client.id}" title="Désactiver"><i class="fa-solid fa-trash"></i></button>
                </td>
            `;
            clientsTableBody.appendChild(tr);
        });

        // Attach delete triggers
        document.querySelectorAll('.delete-client-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = btn.getAttribute('data-id');
                if (confirm("Êtes-vous sûr de vouloir désactiver ce client ? (Soft delete)")) {
                    deactivateClient(id);
                }
            });
        });
    }

    // Save Client (Create / Update)
    function saveClient() {
        const id = document.getElementById('client-id')?.value;
        const data = {
            id: id || null,
            customer_code: document.getElementById('customer_code').value.trim(),
            company: document.getElementById('company').value.trim(),
            name: document.getElementById('name').value.trim(),
            contact_person: document.getElementById('contact_person').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            mobile: document.getElementById('mobile').value.trim(),
            whatsapp: document.getElementById('whatsapp').value.trim(),
            email: document.getElementById('email').value.trim(),
            website: document.getElementById('website').value.trim(),
            address: document.getElementById('address').value.trim(),
            postal_code: document.getElementById('postal_code').value.trim(),
            city: document.getElementById('city').value.trim(),
            canton: document.getElementById('canton').value.trim(),
            country: document.getElementById('country').value,
            vat_number: document.getElementById('vat_number').value.trim(),
            preferred_language: document.getElementById('preferred_language').value,
            notes: document.getElementById('notes').value.trim(),
            tags: document.getElementById('tags').value.trim(),
            action: id ? 'update' : 'create',
            csrf_token: csrfToken
        };

        fetch('../../../api/v1/crm/clients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const resData = await res.json();
            if (res.ok && resData.success) {
                showToast(resData.message || 'Opération réussie !', 'success');
                setTimeout(() => {
                    window.location.href = 'list.php';
                }, 1000);
            } else {
                showToast(resData.message || 'Échec de l\'enregistrement.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erreur de communication.', 'error');
        });
    }

    // Soft delete client
    function deactivateClient(id) {
        fetch('../../../api/v1/crm/clients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'delete', id: id, csrf_token: csrfToken })
        })
        .then(async res => {
            const resData = await res.json();
            if (res.ok && resData.success) {
                showToast(resData.message || 'Client désactivé.', 'success');
                // Reload list
                const term = searchInput ? searchInput.value.trim() : '';
                loadClients(term);
            } else {
                showToast(resData.message || 'Échec du traitement.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erreur de communication avec le serveur.', 'error');
        });
    }

    // Debouncer helper
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Helper to escape HTML tags
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
