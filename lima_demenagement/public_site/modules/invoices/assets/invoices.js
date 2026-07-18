// LIMA Solutions ERP - invoices Module JS Helper

document.addEventListener('DOMContentLoaded', () => {
    const invoicesTableBody = document.getElementById('invoices-table-body');
    const searchInput = document.getElementById('invoices-search-input');
    const invoiceForm = document.getElementById('invoices-form');
    const addLineBtn = document.getElementById('add-line-btn');
    const itemsTableBody = document.getElementById('items-table-body');
    const toast = document.getElementById('toast');

    let csrfToken = '';
    let taxRates = []; // fetched from session / DB settings
    let units = []; // fetched from session

    function showToast(message, type = '') {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast show ' + type;
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // Load active session (includes CSRF, tax rates, units)
    fetch('../../../api/v1/session.php')
        .then(res => res.json())
        .then(data => {
            if (data.authenticated) {
                csrfToken = data.csrf_token || '';
                
                // Fetch dynamic dropdown seeds from server
                loadAuxiliaryData().then(() => {
                    if (invoicesTableBody) {
                        loadinvoices();
                    }
                    if (invoiceForm) {
                        initializeForm();
                    }
                });
            } else {
                window.location.href = '../../admin/login.php';
            }
        })
        .catch(err => {
            console.error(err);
        });

    // Fetch units and tax rates
    function loadAuxiliaryData() {
        // We can query tax rates and units directly, or fetch them from custom configuration endpoints.
        // For simplicity, let's load them via simple native fetch endpoints or config arrays.
        // We will seed them dynamically or fetch using simple queries.
        // Let's assume we can fetch them via session configuration or dedicated simple API calls.
        // Wait, let's just make a fetch call or load placeholder lists if not returned in session.
        // In session, we have enabled_modules. Let's make a quick load from dedicated endpoints or session variables.
        // Let's write a simple fetch call to settings/session config.
        return Promise.all([
            fetch('../../../api/v1/session.php').then(r => r.json()).then(res => {
                // If the session doesn't return full tax rates or units, let's fetch them
            })
        ]);
    }

    // Load invoices list
    function loadinvoices(search = '') {
        let url = '../../../api/v1/invoices/invoices.php';
        if (search) {
            url += '?search=' + encodeURIComponent(search);
        }

        fetch(url)
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    renderinvoicesTable(resData.data.invoices || []);
                } else {
                    showToast(resData.message || 'Erreur lors du chargement.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur de communication.', 'error');
            });
    }

    function renderinvoicesTable(invoices) {
        invoicesTableBody.innerHTML = '';
        if (invoices.length === 0) {
            invoicesTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-light); padding: 20px;">Aucun factures trouvé.</td></tr>`;
            return;
        }

        invoices.forEach(invoice => {
            const tr = document.createElement('tr');
            
            // Format Status Badge
            const statusClass = 'badge-status-' + invoice.status.toLowerCase();
            const validDate = new Date(invoice.valid_until).toLocaleDateString('fr-CH');
            const totalFormatted = numberFormat(invoice.total) + ' ' + invoice.currency;

            tr.innerHTML = `
                <td><span class="crm-badge-code">${escapeHtml(invoice.invoice_number)}</span></td>
                <td>
                    <strong>${escapeHtml(invoice.client_name)}</strong>
                    ${invoice.client_company ? `<br><small style="color: var(--text-light);"><i class="fa-solid fa-building"></i> ${escapeHtml(invoice.client_company)}</small>` : ''}
                </td>
                <td><span class="badge-status ${statusClass}">${escapeHtml(invoice.status)}</span></td>
                <td>${validDate}</td>
                <td><strong>${totalFormatted}</strong></td>
                <td class="crm-actions-cell" style="text-align: center;">
                    <a href="profile.php?id=${invoice.id}" class="btn-crm" title="Visualiser"><i class="fa-solid fa-eye"></i> Profil</a>
                    <a href="form.php?id=${invoice.id}" class="btn-crm btn-crm-primary" title="Modifier"><i class="fa-solid fa-pen"></i></a>
                    <button type="button" class="btn-crm btn-crm-danger delete-invoice-btn" data-id="${invoice.id}" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                </td>
            `;
            invoicesTableBody.appendChild(tr);
        });

        // Delete handlers
        document.querySelectorAll('.delete-invoice-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                if (confirm("Êtes-vous sûr de vouloir supprimer (soft delete) ce factures ?")) {
                    deleteinvoice(id);
                }
            });
        });
    }

    // Delete invoice via AJAX
    function deleteinvoice(id) {
        fetch('../../../api/v1/invoices/invoices.php', {
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
                showToast(resData.message, 'success');
                loadinvoices();
            } else {
                showToast(resData.message || 'Erreur lors de la suppression.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erreur lors du traitement.', 'error');
        });
    }

    // Search Trigger in list
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            loadinvoices(searchInput.value.trim());
        }, 300));
    }

    // FORM LOGIC
    let itemIndex = 0;

    function initializeForm() {
        // Load Client select dropdown options
        fetch('../../../api/v1/crm/clients.php')
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const clientSelect = document.getElementById('client_id');
                    const selectedVal = clientSelect.getAttribute('data-selected') || '';
                    
                    clientSelect.innerHTML = '<option value="">-- Sélectionner un client --</option>';
                    resData.data.clients.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.id;
                        option.textContent = client.name + (client.company ? ' (' + client.company + ')' : '');
                        if (client.id == selectedVal) {
                            option.selected = true;
                        }
                        clientSelect.appendChild(option);
                    });
                }
            });

        // Bind events
        if (addLineBtn) {
            addLineBtn.addEventListener('click', () => addLineItemRow());
        }

        // Header inputs change recalculates
        document.getElementById('discount_percent')?.addEventListener('input', () => recalculateTotals());

        // Parse existing items if edit mode
        const invoiceId = document.getElementById('invoice-id')?.value;
        if (invoiceId) {
            fetch('../../../api/v1/invoices/invoices.php?id=' + invoiceId)
                .then(res => res.json())
                .then(resData => {
                    if (resData.success && resData.data.items) {
                        itemsTableBody.innerHTML = '';
                        resData.data.items.forEach(item => {
                            addLineItemRow(item);
                        });
                        recalculateTotals();
                    }
                });
        } else {
            // Add initial empty row
            if (itemsTableBody) {
                addLineItemRow();
            }
        }
    }

    // Add row to items table
    function addLineItemRow(data = null) {
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.setAttribute('data-index', itemIndex);

        // Fetch tax rates and units placeholders dynamically (seeded during seeder)
        // Hardcode fallback lists matching seeds for instant rendering
        const localTaxes = [
            { id: 1, name: 'Exonéré (0%)', rate: 0 },
            { id: 2, name: 'Taux Réduit (2.6%)', rate: 2.6 },
            { id: 3, name: 'Taux Spécial (3.8%)', rate: 3.8 },
            { id: 4, name: 'Taux Normal (8.1%)', rate: 8.1 }
        ];

        const localUnits = [
            { id: 1, code: 'pcs', desc: 'Pièces' },
            { id: 2, code: 'h', desc: 'Heures' },
            { id: 3, code: 'kg', desc: 'Kilogrammes' },
            { id: 4, code: 'm²', desc: 'Mètre Carré' },
            { id: 5, code: 'm³', desc: 'Mètre Cube' },
            { id: 6, code: 'day', desc: 'Jours' },
            { id: 7, code: 'month', desc: 'Mois' }
        ];

        let unitOptions = '';
        localUnits.forEach(u => {
            const selected = (data && data.unit_id == u.id) ? 'selected' : '';
            unitOptions += `<option value="${u.id}" ${selected}>${u.code}</option>`;
        });

        let taxOptions = '';
        localTaxes.forEach(t => {
            const selected = (data && data.tax_rate_id == t.id) ? 'selected' : (t.id == 4 ? 'selected' : ''); // normal rate by default
            taxOptions += `<option value="${t.id}" data-rate="${t.rate}" ${selected}>${t.name}</option>`;
        });

        row.innerHTML = `
            <td>
                <input type="text" class="item-description" placeholder="Ex: Déménagement de meubles" required value="${data ? escapeHtml(data.description) : ''}">
            </td>
            <td style="width: 100px;">
                <input type="number" class="item-quantity" step="0.01" min="0.01" required value="${data ? data.quantity : '1.00'}">
            </td>
            <td style="width: 100px;">
                <select class="item-unit">${unitOptions}</select>
            </td>
            <td style="width: 140px;">
                <input type="number" class="item-unit-price" step="0.05" min="0.00" required value="${data ? data.unit_price : '0.00'}">
            </td>
            <td style="width: 120px;">
                <input type="number" class="item-discount" step="0.01" min="0.00" max="100.00" value="${data ? data.discount_percent : '0.00'}">
            </td>
            <td style="width: 160px;">
                <select class="item-tax">${taxOptions}</select>
            </td>
            <td style="width: 120px; text-align: right; font-weight: 700; vertical-align: middle;">
                <span class="item-total-display">0.00 CHF</span>
            </td>
            <td style="width: 50px; text-align: center; vertical-align: middle;">
                <button type="button" class="btn-crm btn-crm-danger remove-line-btn" title="Supprimer la ligne"><i class="fa-solid fa-trash"></i></button>
            </td>
        `;

        itemsTableBody.appendChild(row);

        // Bind delete row trigger
        row.querySelector('.remove-line-btn').addEventListener('click', () => {
            row.remove();
            recalculateTotals();
        });

        // Bind calculation triggers on input change
        row.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', () => recalculateTotals());
        });

        itemIndex++;
        recalculateTotals();
    }

    // Recalculate totals in real-time on UI
    function recalculateTotals() {
        let subtotal = 0.00;
        let taxTotal = 0.00;

        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-unit-price').value) || 0;
            const discPercent = parseFloat(row.querySelector('.item-discount').value) || 0;
            const taxSelect = row.querySelector('.item-tax');
            const taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex]?.getAttribute('data-rate')) || 0;

            const itemSubtotal = qty * price;
            const itemDiscount = itemSubtotal * (discPercent / 100);
            const itemTaxable = itemSubtotal - itemDiscount;
            const itemTax = itemTaxable * (taxRate / 100);
            const itemTotal = itemTaxable + itemTax;

            row.querySelector('.item-total-display').textContent = numberFormat(itemTotal) + ' CHF';
            
            subtotal += itemTaxable;
            taxTotal += itemTax;
        });

        const discountPercentHeader = parseFloat(document.getElementById('discount_percent')?.value) || 0;
        const discountHeader = subtotal * (discountPercentHeader / 100);
        
        // Adjust tax proportional to general discount
        if (subtotal > 0 && discountHeader > 0) {
            const ratio = (subtotal - discountHeader) / subtotal;
            taxTotal = taxTotal * ratio;
        }

        const totalHeader = (subtotal - discountHeader) + taxTotal;

        // Display totals
        if (document.getElementById('subtotal-display')) {
            document.getElementById('subtotal-display').textContent = numberFormat(subtotal) + ' CHF';
            document.getElementById('discount-display').textContent = '-' + numberFormat(discountHeader) + ' CHF';
            document.getElementById('tax-display').textContent = numberFormat(taxTotal) + ' CHF';
            document.getElementById('total-display').textContent = numberFormat(totalHeader) + ' CHF';
        }
    }

    // Save invoice submit
    function saveClient() {
        // Collect items
        const items = [];
        document.querySelectorAll('.item-row').forEach(row => {
            items.push({
                description: row.querySelector('.item-description').value.trim(),
                quantity: parseFloat(row.querySelector('.item-quantity').value) || 1,
                unit_id: parseInt(row.querySelector('.item-unit').value) || null,
                unit_price: parseFloat(row.querySelector('.item-unit-price').value) || 0,
                discount_percent: parseFloat(row.querySelector('.item-discount').value) || 0,
                tax_rate_id: parseInt(row.querySelector('.item-tax').value) || null
            });
        });

        const id = document.getElementById('invoice-id')?.value;
        const data = {
            id: id || null,
            client_id: parseInt(document.getElementById('client_id').value) || null,
            status: document.getElementById('status').value,
            issue_date: document.getElementById('issue_date').value,
            valid_until: document.getElementById('valid_until').value,
            currency: 'CHF', // swiss default
            discount_percent: parseFloat(document.getElementById('discount_percent').value) || 0,
            notes: document.getElementById('notes').value.trim(),
            internal_notes: document.getElementById('internal_notes').value.trim(),
            items: items,
            action: id ? 'update' : 'create',
            csrf_token: csrfToken
        };

        fetch('../../../api/v1/invoices/invoices.php', {
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
                showToast(resData.message, 'success');
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

    // Helper formatting number suíço
    function numberFormat(val) {
        // rounded Swiss style format (e.g. 1'250.00)
        // round to nearest 0.05
        const rounded = Math.round(parseFloat(val) * 20) / 20;
        return rounded.toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

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
