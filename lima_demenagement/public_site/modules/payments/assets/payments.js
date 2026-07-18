// LIMA Solutions ERP - Payments Module JS Helper

document.addEventListener('DOMContentLoaded', () => {
    const paymentsTableBody = document.getElementById('payments-table-body');
    const searchInput = document.getElementById('payments-search-input');
    const paymentForm = document.getElementById('payments-form');
    const invoiceSelect = document.getElementById('invoice_id');
    const amountInput = document.getElementById('amount');
    const currencyLabel = document.getElementById('currency-label');
    const toast = document.getElementById('toast');

    let csrfToken = '';
    let invoicesList = []; // stores loaded invoices details

    function showToast(message, type = '') {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast show ' + type;
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // Load active session (includes CSRF)
    fetch('../../../api/v1/session.php')
        .then(res => res.json())
        .then(data => {
            if (data.authenticated) {
                csrfToken = data.csrf_token || '';
                
                if (paymentsTableBody) {
                    loadPayments();
                }
                if (paymentForm) {
                    initializeForm();
                }
            } else {
                window.location.href = '../../admin/login.php';
            }
        })
        .catch(err => {
            console.error(err);
        });

    // 1. LIST VIEW FUNCTIONS
    function loadPayments(search = '') {
        let url = '../../../api/v1/payments/payments.php';
        if (search) {
            url += '?search=' + encodeURIComponent(search);
        }

        fetch(url)
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    renderPaymentsTable(resData.data.payments || []);
                } else {
                    showToast(resData.message || 'Erreur lors du chargement.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur de communication.', 'error');
            });
    }

    function renderPaymentsTable(payments) {
        paymentsTableBody.innerHTML = '';
        if (payments.length === 0) {
            paymentsTableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-light); padding: 20px;">Aucun paiement trouvé.</td></tr>`;
            return;
        }

        payments.forEach(p => {
            const tr = document.createElement('tr');
            
            // Check flags
            const hasReceipt = !!p.receipt_path;
            const isReversed = !!p.reversed_at;
            const isNegative = parseFloat(p.amount) < 0;

            // Highlight/styles
            let rowStyle = 'border-bottom: 1px solid var(--border-gray);';
            let labelExtra = '';
            if (isNegative) {
                rowStyle += ' background-color: #fef2f2; color: #b91c1c;';
                labelExtra = ' <span style="background-color: #fecaca; color: #b91c1c; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">CONTRAPARTIDA</span>';
            } else if (isReversed) {
                rowStyle += ' background-color: #f8fafc; color: #94a3b8; text-decoration: line-through;';
                labelExtra = ' <span style="background-color: #e2e8f0; color: #64748b; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">ESTORNADO</span>';
            }
            tr.setAttribute('style', rowStyle);
            
            // Format Date
            const paymentDate = new Date(p.payment_date).toLocaleDateString('fr-CH');
            const amountFormatted = numberFormat(p.amount) + ' ' + p.currency;

            // Format Payment Method Badge
            let methodBadgeClass = 'badge-status-other';
            const methodLower = p.payment_method.toLowerCase();
            if (methodLower.includes('cash')) methodBadgeClass = 'badge-status-cash';
            else if (methodLower.includes('bank') || methodLower.includes('transfer')) methodBadgeClass = 'badge-status-bank';
            else if (methodLower.includes('twint')) methodBadgeClass = 'badge-status-twint';
            else if (methodLower.includes('card')) methodBadgeClass = 'badge-status-card';
            else if (methodLower.includes('qr')) methodBadgeClass = 'badge-status-qr';

            // Actions rendering rules:
            // - Always show receipt
            // - Hide edit/delete when has receipt or is reversed or is negative
            // - Show reverse button only if NOT reversed and NOT negative
            let actionsHtml = `<a href="../../../api/v1/payments/payments.php?id=${p.id}&receipt=1" target="_blank" class="btn-header" style="padding:5px 10px; font-size:12px;" title="Voir le Reçu"><i class="fa-solid fa-file-invoice"></i> Reçu</a>`;
            
            if (!hasReceipt && !isReversed && !isNegative) {
                actionsHtml += `<a href="form.php?id=${p.id}" class="btn-header btn-crm-primary" style="padding:5px 10px; font-size:12px; margin-left:5px;" title="Modifier"><i class="fa-solid fa-pen"></i></a>`;
                actionsHtml += `<button type="button" class="btn-header btn-crm-danger delete-payment-btn" data-id="${p.id}" style="padding:5px 10px; font-size:12px; margin-left:5px;" title="Annuler le paiement"><i class="fa-solid fa-trash"></i></button>`;
            }
            
            if (!isReversed && !isNegative) {
                actionsHtml += `<button type="button" class="btn-header reverse-payment-btn" data-id="${p.id}" data-number="${escapeHtml(p.payment_number)}" style="padding:5px 10px; font-size:12px; margin-left:5px; background-color: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer;" title="Estornar"><i class="fa-solid fa-rotate-left"></i> Estornar</button>`;
            }

            tr.innerHTML = `
                <td style="padding:12px;"><span class="crm-badge-code">${escapeHtml(p.payment_number)}</span>${labelExtra}</td>
                <td style="padding:12px;"><a href="../../invoices/views/profile.php?id=${p.invoice_id}" style="color:var(--primary-teal); text-decoration:none; font-weight:600;">${escapeHtml(p.invoice_number)}</a></td>
                <td style="padding:12px;"><strong>${escapeHtml(p.client_name)}</strong></td>
                <td style="padding:12px;">${paymentDate}</td>
                <td style="padding:12px;"><span class="badge-status ${methodBadgeClass}" style="padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600;">${escapeHtml(p.payment_method)}</span></td>
                <td style="padding:12px;"><strong>${amountFormatted}</strong></td>
                <td class="crm-actions-cell" style="text-align: center; padding:12px;">
                    ${actionsHtml}
                </td>
            `;
            paymentsTableBody.appendChild(tr);
        });

        // Delete handlers
        document.querySelectorAll('.delete-payment-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                if (confirm("Êtes-vous sûr de vouloir supprimer (soft delete) ce paiement ? Le solde de la facture sera recalculé.")) {
                    deletePayment(id);
                }
            });
        });

        // Reversal handlers
        document.querySelectorAll('.reverse-payment-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const num = btn.getAttribute('data-number');
                const reason = prompt(`Saisissez le motif de l'extourne pour le paiement ${num} :`);
                if (reason !== null && reason.trim() !== '') {
                    reversePayment(id, reason.trim());
                } else if (reason !== null) {
                    showToast("Le motif de l'extourne est obligatoire.", "error");
                }
            });
        });
    }

    function reversePayment(id, reason) {
        fetch('../../../api/v1/payments/payments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ action: 'reverse', payment_id: id, reversal_reason: reason, csrf_token: csrfToken })
        })
        .then(async res => {
            const resData = await res.json();
            if (res.ok && resData.success) {
                showToast(resData.message, 'success');
                loadPayments();
            } else {
                showToast(resData.message || "Échec de l'extourne.", 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erreur lors du traitement.', 'error');
        });
    }

    function deletePayment(id) {
        fetch('../../../api/v1/payments/payments.php', {
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
                loadPayments();
            } else {
                showToast(resData.message || 'Échec de la suppression.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erreur lors du traitement.', 'error');
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            loadPayments(searchInput.value.trim());
        }, 300));
    }

    // 2. FORM VIEW FUNCTIONS
    function initializeForm() {
        const selectedInvoiceId = invoiceSelect.getAttribute('data-selected') || '';
        
        // Fetch all active invoices eligible for payment
        fetch('../../../api/v1/invoices/invoices.php')
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    invoicesList = resData.data.invoices || [];
                    
                    invoiceSelect.innerHTML = '<option value="">-- Choisir une facture --</option>';
                    invoicesList.forEach(inv => {
                        // Display option if it's eligible, or if it is currently selected (edit mode)
                        const isEligible = ['Issued', 'Sent', 'Partially Paid', 'Overdue'].includes(inv.status);
                        const isSelected = (inv.id == selectedInvoiceId);

                        if (isEligible || isSelected) {
                            const option = document.createElement('option');
                            option.value = inv.id;
                            
                            const balanceFormatted = numberFormat(inv.balance_due) + ' ' + inv.currency;
                            option.textContent = inv.invoice_number + ' - ' + inv.client_name + ' (Solde: ' + balanceFormatted + ')';
                            if (isSelected) {
                                option.selected = true;
                                if (currencyLabel) currencyLabel.textContent = inv.currency;
                            }
                            invoiceSelect.appendChild(option);
                        }
                    });

                    // Trigger balance autopopulate if creating with a pre-selected invoice ID
                    if (!document.getElementById('payment-id').value && selectedInvoiceId) {
                        const activeInv = invoicesList.find(x => x.id == selectedInvoiceId);
                        if (activeInv) {
                            amountInput.value = parseFloat(activeInv.balance_due).toFixed(2);
                            if (currencyLabel) currencyLabel.textContent = activeInv.currency;
                        }
                    }
                }
            });

        // Dynamically update balance default and currency label when changing invoice select dropdown
        invoiceSelect.addEventListener('change', () => {
            const invoiceId = invoiceSelect.value;
            if (!invoiceId) {
                amountInput.value = '0.00';
                if (currencyLabel) currencyLabel.textContent = 'CHF';
                return;
            }

            const activeInv = invoicesList.find(x => x.id == invoiceId);
            if (activeInv) {
                amountInput.value = parseFloat(activeInv.balance_due).toFixed(2);
                if (currencyLabel) currencyLabel.textContent = activeInv.currency;
            }
        });

        // Submit Form AJAX
        paymentForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const paymentId = document.getElementById('payment-id').value;
            const data = {
                id: paymentId || null,
                invoice_id: parseInt(invoiceSelect.value) || parseInt(document.querySelector('input[name="invoice_id"]')?.value),
                payment_date: document.getElementById('payment_date').value,
                amount: parseFloat(amountInput.value) || 0,
                currency: currencyLabel.textContent,
                payment_method: document.getElementById('payment_method').value,
                reference: document.getElementById('reference').value.trim(),
                transaction_reference: document.getElementById('transaction_reference').value.trim(),
                notes: document.getElementById('notes').value.trim(),
                csrf_token: csrfToken
            };

            const isEdit = !!paymentId;

            fetch('../../../api/v1/payments/payments.php' + (isEdit ? '?id=' + paymentId : ''), {
                method: isEdit ? 'PUT' : 'POST',
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
                    showToast(resData.message || "Erreur lors de l'enregistrement.", 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Erreur lors de la communication.', 'error');
            });
        });
    }

    // Dynamic utilities
    function numberFormat(val) {
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
