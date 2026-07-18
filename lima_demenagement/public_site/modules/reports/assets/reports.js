// LIMA Solutions ERP - Reports assets JS Helper
document.addEventListener('DOMContentLoaded', () => {
    // UI Elements
    const startDateInput = document.getElementById('filter-start-date');
    const endDateInput = document.getElementById('filter-end-date');
    const clientSelect = document.getElementById('filter-client');
    const currencySelect = document.getElementById('filter-currency');
    const filterBtn = document.getElementById('btn-apply-filters');
    const resetBtn = document.getElementById('btn-reset-filters');
    
    // Export Elements
    const exportCsvBtn = document.getElementById('export-csv');
    const exportXlsxBtn = document.getElementById('export-xlsx');
    const exportPdfBtn = document.getElementById('export-pdf');

    // Chart instances storage
    let charts = {};

    let csrfToken = '';
    let currentCompanyCurrency = 'CHF';

    // 1. Initialize filters and load page data
    fetch('../../../api/v1/session.php')
        .then(res => res.json())
        .then(data => {
            if (data.authenticated) {
                csrfToken = data.csrf_token || '';
                if (data.active_company) {
                    currentCompanyCurrency = data.active_company.default_currency || 'CHF';
                    if (data.active_company.main_color) {
                        document.documentElement.style.setProperty('--primary-teal', data.active_company.main_color);
                    }
                }
                
                // Set default dates (start of year to today)
                const today = new Date();
                const startOfYear = new Date(today.getFullYear(), 0, 1);
                
                if (startDateInput && !startDateInput.value) {
                    startDateInput.value = startOfYear.toISOString().split('T')[0];
                }
                if (endDateInput && !endDateInput.value) {
                    endDateInput.value = today.toISOString().split('T')[0];
                }

                loadClientsFilter();
                loadReportData();
            } else {
                window.location.href = '../../admin/login.php';
            }
        });

    // Load active clients filter
    function loadClientsFilter() {
        if (!clientSelect) return;
        fetch('../../../api/v1/crm/clients.php')
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    const clients = resData.data.clients || [];
                    clientSelect.innerHTML = '<option value="">-- Tous les clients --</option>';
                    clients.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name + (c.company ? ' (' + c.company + ')' : '');
                        clientSelect.appendChild(opt);
                    });
                }
            });
    }

    // Load active report page
    function loadReportData() {
        const pageType = getActivePageType();
        if (pageType === 'dashboard') {
            loadDashboardReport();
        } else if (pageType === 'cashflow') {
            loadCashFlowReport();
        } else if (pageType === 'financial') {
            loadFinancialReport();
        } else if (pageType === 'tax') {
            loadTaxReport();
        } else if (pageType === 'customers') {
            loadCustomersReport();
        }
    }

    // Get page type from path
    function getActivePageType() {
        const path = window.location.pathname;
        if (path.includes('dashboard.php')) return 'dashboard';
        if (path.includes('cashflow.php')) return 'cashflow';
        if (path.includes('financial.php')) return 'financial';
        if (path.includes('tax.php')) return 'tax';
        if (path.includes('customers.php')) return 'customers';
        return 'dashboard';
    }

    // Format query string with filters
    function getFilterQueryString(action) {
        let query = `action=${action}`;
        if (startDateInput && startDateInput.value) query += `&start_date=${startDateInput.value}`;
        if (endDateInput && endDateInput.value) query += `&end_date=${endDateInput.value}`;
        if (clientSelect && clientSelect.value) query += `&client_id=${clientSelect.value}`;
        if (currencySelect && currencySelect.value) query += `&currency=${currencySelect.value}`;
        
        // Context specific filters
        const overdueSelect = document.getElementById('filter-overdue');
        if (overdueSelect && overdueSelect.value) query += `&overdue_type=${overdueSelect.value}`;

        const groupSelect = document.getElementById('filter-group-type');
        if (groupSelect && groupSelect.value) query += `&group_type=${groupSelect.value}`;

        const sortSelect = document.getElementById('filter-sort');
        if (sortSelect && sortSelect.value) query += `&sort=${sortSelect.value}`;

        return query;
    }

    // Apply & reset filters
    if (filterBtn) {
        filterBtn.addEventListener('click', () => {
            loadReportData();
        });
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (startDateInput) {
                const today = new Date();
                startDateInput.value = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            }
            if (endDateInput) endDateInput.value = new Date().toISOString().split('T')[0];
            if (clientSelect) clientSelect.value = '';
            if (currencySelect) currencySelect.value = '';
            
            const overdueSelect = document.getElementById('filter-overdue');
            if (overdueSelect) overdueSelect.value = '';
            const groupSelect = document.getElementById('filter-group-type');
            if (groupSelect) groupSelect.value = 'month';
            const sortSelect = document.getElementById('filter-sort');
            if (sortSelect) sortSelect.value = '';

            loadReportData();
        });
    }

    // Bind exports
    if (exportCsvBtn) exportCsvBtn.addEventListener('click', (e) => triggerExport(e, 'csv'));
    if (exportXlsxBtn) exportXlsxBtn.addEventListener('click', (e) => triggerExport(e, 'xlsx'));
    if (exportPdfBtn) exportPdfBtn.addEventListener('click', (e) => triggerExport(e, 'pdf'));

    function triggerExport(e, format) {
        e.preventDefault();
        const action = getExportAction();
        const query = getFilterQueryString(action) + `&export=${format}`;
        window.open(`../../../api/v1/reports/reports.php?${query}`, '_blank');
    }

    function getExportAction() {
        const type = getActivePageType();
        if (type === 'dashboard') return 'kpis';
        return type; // matches receivables (under financial.php), tax, cashflow, customers actions
    }

    // 2. SPECIFIC REPORTS LOADING ENGINE

    // Executive Dashboard / BI
    function loadDashboardReport() {
        fetch(`../../../api/v1/reports/reports.php?${getFilterQueryString('kpis')}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data;
                    document.getElementById('val-revenue-today').textContent = formatVal(data.revenue_today);
                    document.getElementById('val-revenue-month').textContent = formatVal(data.revenue_month);
                    document.getElementById('val-revenue-year').textContent = formatVal(data.revenue_year);
                    document.getElementById('val-billed').textContent = formatVal(data.total_billed);
                    document.getElementById('val-received').textContent = formatVal(data.total_received);
                    document.getElementById('val-pending').textContent = formatVal(data.total_pending);
                    document.getElementById('val-reversed').textContent = formatVal(data.total_reversed);
                    document.getElementById('val-clients').textContent = data.clients_active;
                    document.getElementById('val-new-clients').textContent = data.clients_new;
                    document.getElementById('val-invoices').textContent = data.invoices_count;
                    document.getElementById('val-quotes').textContent = data.quotes_count;
                    document.getElementById('val-conversion').textContent = data.conversion_rate + ' %';
                    document.getElementById('val-ticket').textContent = formatVal(data.ticket_average);
                    document.getElementById('val-ltv').textContent = formatVal(data.ltv_average);

                    // Load secondary graphics data (Quotes and Payments status charts)
                    loadDashboardCharts();
                }
            });
    }

    function loadDashboardCharts() {
        // Load quotes status distribution chart
        fetch(`../../../api/v1/reports/reports.php?${getFilterQueryString('quotes')}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data;
                    const labels = [];
                    const values = [];
                    (data.by_status || []).forEach(row => {
                        labels.push(row.status);
                        values.push(row.quantity);
                    });
                    renderPieChart('chart-quotes', labels, values, 'Volume de Devis', ['#6b7280', '#007a87', '#10b981', '#ef4444', '#f59e0b']);
                    
                    document.getElementById('q-acceptance-rate').textContent = data.acceptance_rate + ' %';
                    document.getElementById('q-conversion-rate').textContent = data.conversion_rate + ' %';
                    document.getElementById('q-conversion-time').textContent = data.avg_time_to_invoice + ' jours';
                }
            });

        // Load payments by method chart
        fetch(`../../../api/v1/reports/reports.php?${getFilterQueryString('payments')}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data || [];
                    const labels = [];
                    const values = [];
                    data.forEach(row => {
                        labels.push(row.payment_method);
                        values.push(parseFloat(row.net_amount));
                    });
                    renderPieChart('chart-payments', labels, values, 'Valor Líquido', ['#10b981', '#007a87', '#8b5cf6', '#3b82f6', '#ec4899', '#f59e0b', '#6b7280']);
                }
            });
    }

    // Cash Flow
    function loadCashFlowReport() {
        fetch(`../../../api/v1/reports/reports.php?${getFilterQueryString('cashflow')}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data || [];
                    const tbody = document.getElementById('cashflow-table-body');
                    tbody.innerHTML = '';
                    
                    if (data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 30px;">Aucune transaction de flux trouvée.</td></tr>`;
                        return;
                    }

                    const labels = [];
                    const cashIn = [];
                    const cashOut = [];
                    const netCash = [];

                    data.forEach(row => {
                        labels.push(row.period);
                        cashIn.push(parseFloat(row.cash_in));
                        cashOut.push(Math.abs(parseFloat(row.cash_out))); // keep positive in chart
                        netCash.push(parseFloat(row.net_cash));

                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--border-gray)';
                        tr.innerHTML = `
                            <td style="padding:12px;"><strong>${escapeHtml(row.period)}</strong></td>
                            <td style="padding:12px; color:#15803d;">+ ${formatVal(row.cash_in)}</td>
                            <td style="padding:12px; color:#b91c1c;">- ${formatVal(Math.abs(row.cash_out))}</td>
                            <td style="padding:12px; font-weight:700; color:${parseFloat(row.net_cash) >= 0 ? '#15803d':'#b91c1c'};">${formatVal(row.net_cash)}</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    renderCashFlowChart(labels, cashIn, cashOut, netCash);
                }
            });
    }

    // Financial / Accounts Receivable
    let currentPage = 1;
    function loadFinancialReport(page = 1) {
        currentPage = page;
        const query = getFilterQueryString('receivables') + `&page=${page}&limit=15`;
        fetch(`../../../api/v1/reports/reports.php?${query}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data;
                    const summary = data.summary;
                    document.getElementById('rec-total').textContent = formatVal(summary.total_receivable);
                    document.getElementById('rec-overdue').textContent = formatVal(summary.overdue_total);
                    document.getElementById('rec-today').textContent = formatVal(summary.due_today);
                    document.getElementById('rec-7-days').textContent = formatVal(summary.due_7_days);
                    document.getElementById('rec-30-days').textContent = formatVal(summary.due_30_days);

                    // Table items
                    const tbody = document.getElementById('receivables-table-body');
                    tbody.innerHTML = '';
                    const list = data.list || [];
                    if (list.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 30px;">Aucune facture ouverte trouvée.</td></tr>`;
                        return;
                    }

                    list.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--border-gray)';
                        
                        let overdueStyle = "color: var(--text-light);";
                        if (row.days_overdue > 0) {
                            overdueStyle = "color: #b91c1c; font-weight: 700; background-color: #fef2f2; padding: 2px 6px; border-radius: 4px;";
                        }

                        tr.innerHTML = `
                            <td style="padding:12px;"><a href="../../invoices/views/profile.php?id=${row.id}" style="color:var(--primary-teal); text-decoration:none; font-weight:600;">${escapeHtml(row.invoice_number)}</a></td>
                            <td style="padding:12px;"><strong>${escapeHtml(row.client_name)}</strong></td>
                            <td style="padding:12px;">${new Date(row.due_date).toLocaleDateString('fr-CH')}</td>
                            <td style="padding:12px;">${formatVal(row.total, row.currency)}</td>
                            <td style="padding:12px; color:#15803d;">${formatVal(row.paid_amount, row.currency)}</td>
                            <td style="padding:12px; font-weight:700;">${formatVal(row.balance_due, row.currency)}</td>
                            <td style="padding:12px;"><span style="${overdueStyle}">${row.days_overdue} jours</span></td>
                        `;
                        tbody.appendChild(tr);
                    });

                    renderPagination('receivables-pagination', data.pagination, loadFinancialReport);
                }
            });
    }

    // Taxes / IVA
    function loadTaxReport() {
        fetch(`../../../api/v1/reports/reports.php?${getFilterQueryString('tax')}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data;
                    
                    // Rates breakdown
                    const tbodyRate = document.getElementById('tax-rates-tbody');
                    tbodyRate.innerHTML = '';
                    const byRate = data.by_rate || [];
                    byRate.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--border-gray)';
                        tr.innerHTML = `
                            <td style="padding:12px;"><strong>${escapeHtml(row.tax_name)} (${row.tax_rate}%)</strong></td>
                            <td style="padding:12px;">${formatVal(row.subtotal)}</td>
                            <td style="padding:12px; font-weight:700; color:var(--primary-teal);">${formatVal(row.tax_amount)}</td>
                            <td style="padding:12px;">${formatVal(row.total)}</td>
                        `;
                        tbodyRate.appendChild(tr);
                    });

                    // Periods breakdown
                    const tbodyPeriod = document.getElementById('tax-periods-tbody');
                    tbodyPeriod.innerHTML = '';
                    const byPeriod = data.by_period || [];
                    const chartLabels = [];
                    const chartValues = [];
                    
                    byPeriod.forEach(row => {
                        chartLabels.push(row.period);
                        chartValues.push(parseFloat(row.tax_amount));

                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--border-gray)';
                        tr.innerHTML = `
                            <td style="padding:12px;"><strong>${escapeHtml(row.period)}</strong></td>
                            <td style="padding:12px;">${formatVal(row.subtotal)}</td>
                            <td style="padding:12px; font-weight:700; color:var(--primary-teal);">${formatVal(row.tax_amount)}</td>
                            <td style="padding:12px;">${formatVal(row.total)}</td>
                        `;
                        tbodyPeriod.appendChild(tr);
                    });

                    renderLineChart('chart-tax-period', chartLabels, chartValues, 'TVA collectée');
                }
            });
    }

    // Customers Report
    function loadCustomersReport() {
        fetch(`../../../api/v1/reports/reports.php?${getFilterQueryString('customers')}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data || [];
                    const tbody = document.getElementById('customers-tbody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 30px;">Aucun client trouvé.</td></tr>`;
                        return;
                    }

                    const chartLabels = [];
                    const chartLtv = [];
                    const chartBilled = [];

                    // Render top 10 in chart
                    const chartSlice = data.slice(0, 8);
                    chartSlice.forEach(row => {
                        chartLabels.push(row.name);
                        chartLtv.push(parseFloat(row.ltv || 0));
                        chartBilled.push(parseFloat(row.total_billed || 0));
                    });

                    data.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--border-gray)';
                        tr.innerHTML = `
                            <td style="padding:12px;"><strong>${escapeHtml(row.name)}</strong>${row.company ? '<br><small style="color:var(--text-light);">' + escapeHtml(row.company) + '</small>' : ''}</td>
                            <td style="padding:12px;">${formatVal(row.total_billed || 0)}</td>
                            <td style="padding:12px; color:#15803d; font-weight:700;">${formatVal(row.ltv || 0)}</td>
                            <td style="padding:12px; text-align:center;">${row.invoices_count}</td>
                            <td style="padding:12px; text-align:center;">${row.quotes_count}</td>
                            <td style="padding:12px; text-align:center;"><span style="background-color:${row.active == 1 ? '#dcfce7':'#f1f5f9'}; color:${row.active == 1 ? '#15803d':'#64748b'}; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600;">${row.active == 1 ? 'Actif':'Inactif'}</span></td>
                        `;
                        tbody.appendChild(tr);
                    });

                    renderCustomersChart(chartLabels, chartBilled, chartLtv);
                }
            });
    }

    // 3. GRAPHICS DRAWING HELPER FUNCTIONS USING CHART.JS

    function renderPieChart(canvasId, labels, data, datasetLabel, colors) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (charts[canvasId]) charts[canvasId].destroy();

        charts[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel,
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: { family: 'Outfit', size: 12 }
                        }
                    }
                }
            }
        });
    }

    function renderLineChart(canvasId, labels, data, datasetLabel) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (charts[canvasId]) charts[canvasId].destroy();

        const themeColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-teal').trim() || '#007a87';

        charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel,
                    data: data,
                    borderColor: themeColor,
                    backgroundColor: themeColor + '1a', // 10% opacity
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function renderCashFlowChart(labels, inData, outData, netData) {
        const canvasId = 'chart-cashflow';
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (charts[canvasId]) charts[canvasId].destroy();

        charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Entrées',
                        data: inData,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    },
                    {
                        label: 'Sorties',
                        data: outData,
                        backgroundColor: '#ef4444',
                        borderRadius: 4
                    },
                    {
                        label: 'Solde Net',
                        data: netData,
                        type: 'line',
                        borderColor: '#007a87',
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function renderCustomersChart(labels, billed, ltv) {
        const canvasId = 'chart-customers';
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;

        if (charts[canvasId]) charts[canvasId].destroy();

        charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Faturamento Total',
                        data: billed,
                        backgroundColor: '#007a87',
                        borderRadius: 4
                    },
                    {
                        label: 'LTV (Valor Pago)',
                        data: ltv,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 4. GENERAL TABULAR UTILITIES

    function formatVal(val, curr = '') {
        const parsed = parseFloat(val);
        if (isNaN(parsed)) return '0.00 ' + (curr || currentCompanyCurrency);
        const symbol = curr || currentCompanyCurrency;
        return parsed.toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + symbol;
    }

    function renderPagination(elemId, pagInfo, clickCallback) {
        const pagContainer = document.getElementById(elemId);
        if (!pagContainer) return;
        pagContainer.innerHTML = '';

        if (!pagInfo || pagInfo.total_pages <= 1) return;

        const maxVisible = 5;
        let startPage = Math.max(1, pagInfo.page - Math.floor(maxVisible / 2));
        let endPage = Math.min(pagInfo.total_pages, startPage + maxVisible - 1);

        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        // Previous
        if (pagInfo.page > 1) {
            const btn = document.createElement('button');
            btn.className = 'btn-header';
            btn.style.padding = '5px 10px';
            btn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
            btn.addEventListener('click', () => clickCallback(pagInfo.page - 1));
            pagContainer.appendChild(btn);
        }

        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.className = i === pagInfo.page ? 'btn-crm btn-crm-primary' : 'btn-header';
            btn.style.padding = '5px 12px';
            btn.textContent = i;
            btn.addEventListener('click', () => clickCallback(i));
            pagContainer.appendChild(btn);
        }

        // Next
        if (pagInfo.page < pagInfo.total_pages) {
            const btn = document.createElement('button');
            btn.className = 'btn-header';
            btn.style.padding = '5px 10px';
            btn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
            btn.addEventListener('click', () => clickCallback(pagInfo.page + 1));
            pagContainer.appendChild(btn);
        }
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
