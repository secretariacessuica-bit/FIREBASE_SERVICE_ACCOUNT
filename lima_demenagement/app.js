document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const servicesTbody = document.getElementById('services-tbody');
    const addRowBtn = document.getElementById('add-row-btn');
    const grandTotalDisplay = document.getElementById('grand-total-display');
    const saveBtn = document.getElementById('save-btn');
    const clearAllBtn = document.getElementById('clear-all-btn');
    const pdfBtn = document.getElementById('pdf-btn');
    const signatureCanvas = document.getElementById('signature-canvas');
    const clearSigBtn = document.getElementById('clear-sig-btn');
    const sigDateInput = document.getElementById('sig-date-input');
    const payeToggle = document.getElementById('paye-toggle');
    const historyList = document.getElementById('history-list');
    const toast = document.getElementById('toast');

    // Input fields
    const invoiceNumber = document.getElementById('invoice-number');
    const invoiceDate = document.getElementById('invoice-date');
    const customerName = document.getElementById('customer-name');
    const customerAddress = document.getElementById('customer-address');
    const customerPhone = document.getElementById('customer-phone');
    const customerEmail = document.getElementById('customer-email');
    const sigNameInput = document.getElementById('sig-name-input');

    // Signature Drawing Logic
    const ctx = signatureCanvas.getContext('2d');
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    // Helper to format date as DD.MM.YYYY
    function formatDateDDMMYYYY(date) {
        const dd = String(date.getDate()).padStart(2, '0');
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const yyyy = date.getFullYear();
        return `${dd}.${mm}.${yyyy}`;
    }

    // Initialize date inputs
    const today = new Date();
    sigDateInput.value = formatDateDDMMYYYY(today);
    invoiceDate.value = formatDateDDMMYYYY(today);

    // Set canvas dimensions
    function resizeCanvas() {
        signatureCanvas.width = signatureCanvas.clientWidth;
        signatureCanvas.height = signatureCanvas.clientHeight;
        
        ctx.strokeStyle = '#000000';
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.lineWidth = 2.0;
    }

    window.addEventListener('resize', resizeCanvas);
    setTimeout(resizeCanvas, 200);

    // Drawing functions
    function startDrawing(e) {
        isDrawing = true;
        [lastX, lastY] = getCoordinates(e);
    }

    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const [x, y] = getCoordinates(e);

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(x, y);
        ctx.stroke();
        [lastX, lastY] = [x, y];
    }

    function stopDrawing() {
        isDrawing = false;
    }

    function getCoordinates(e) {
        const rect = signatureCanvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return [
            clientX - rect.left,
            clientY - rect.top
        ];
    }

    // Drawing Listeners
    signatureCanvas.addEventListener('mousedown', startDrawing);
    signatureCanvas.addEventListener('mousemove', draw);
    signatureCanvas.addEventListener('mouseup', stopDrawing);
    signatureCanvas.addEventListener('mouseout', stopDrawing);

    signatureCanvas.addEventListener('touchstart', startDrawing, { passive: false });
    signatureCanvas.addEventListener('touchmove', draw, { passive: false });
    signatureCanvas.addEventListener('touchend', stopDrawing);

    clearSigBtn.addEventListener('click', () => {
        ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        showToast('Assinatura limpa.');
    });

    // Swiss Format for Currency: e.g. CHF 1'760.00
    function formatCHF(val) {
        const parts = val.toFixed(2).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, "'");
        return `CHF ${parts.join('.')}`;
    }

    // Dynamic Services Table
    function createServiceRow(description = '', quantity = '', price = '') {
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td>
                <input type="text" class="desc-input" placeholder="Descrição do serviço..." value="${description}">
            </td>
            <td>
                <input type="number" class="num-input qty-input" min="1" step="any" placeholder="0" value="${quantity}">
            </td>
            <td>
                <input type="number" class="num-input price-input" min="0" step="any" placeholder="0.00" value="${price}">
            </td>
            <td class="row-total-cell" style="text-align: right; font-weight: bold;">CHF 0.00</td>
            <td class="no-print" style="text-align: center; width: 45px; border-right: none;">
                <button type="button" class="delete-row-btn" title="Remover linha">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;

        const qtyInput = tr.querySelector('.qty-input');
        const priceInput = tr.querySelector('.price-input');
        const descInput = tr.querySelector('.desc-input');

        [qtyInput, priceInput, descInput].forEach(input => {
            input.addEventListener('input', calculateTotals);
        });

        tr.querySelector('.delete-row-btn').addEventListener('click', () => {
            tr.remove();
            calculateTotals();
            showToast('Linha removida.');
        });

        servicesTbody.appendChild(tr);
        calculateTotals();
    }

    // Official Swiss QR Code SPC 0200 generation
    function updateSwissQRCode(amount) {
        const qrCanvas = document.getElementById('qr-code-canvas');
        if (!qrCanvas) return;

        // Strip spaces from IBAN
        const creditorIBAN = 'CH0800767000Z54883120'.replace(/\s+/g, '');
        const creditorName = 'LIMA DE JESUS WEBERSON';
        const creditorAddr1 = 'Renens – Lausanne';
        const creditorAddr2 = 'Suisse';
        const creditorCountry = 'CH';

        // Debtor details
        const debtorName = customerName.value.trim();
        const debtorAddrRaw = customerAddress.value.trim();

        let debtorType = '';
        let debtorLine1 = '';
        let debtorLine2 = '';
        let debtorCountry = '';

        if (debtorName) {
            debtorType = 'K'; // Combined format
            debtorCountry = 'CH';
            const addressParts = debtorAddrRaw.split(',');
            debtorLine1 = addressParts[0] ? addressParts[0].trim() : 'Suisse';
            debtorLine2 = addressParts[1] ? addressParts.slice(1).join(',').trim() : 'CH';
        }

        const invNo = invoiceNumber.value.trim() || '33';
        const message = `Facture N° ${invNo}`;
        const refType = 'NON';
        const reference = '';

        // Formatted amount (up to 2 decimals, empty if zero/invalid)
        const formattedAmount = (amount > 0) ? amount.toFixed(2) : '';

        // 31 standard SPC fields structure
        const spcFields = [
            'SPC',              // 1. QRType
            '0200',             // 2. Version
            '1',                // 3. Coding (1 = UTF-8)
            creditorIBAN,       // 4. IBAN
            'K',                // 5. Creditor Address Type
            creditorName,       // 6. Creditor Name
            creditorAddr1,      // 7. Creditor Address Line 1
            creditorAddr2,      // 8. Creditor Address Line 2
            '',                 // 9. (empty for combined)
            '',                 // 10. (empty for combined)
            creditorCountry,    // 11. Creditor Country
            '', '', '', '', '', '', '', // 12-18. Ultimate Creditor (7 empty lines)
            formattedAmount,    // 19. Amount
            'CHF',              // 20. Currency
            debtorType,         // 21. Ultimate Debtor Type
            debtorName,         // 22. Debtor Name
            debtorLine1,        // 23. Debtor Line 1
            debtorLine2,        // 24. Debtor Line 2
            '',                 // 25. (empty)
            '',                 // 26. (empty)
            debtorCountry,      // 27. Debtor Country
            refType,            // 28. Reference Type
            reference,          // 29. Reference
            message,            // 30. Unstructured message
            'EPD'               // 31. Trailer EPD
        ];

        const payload = spcFields.join('\r\n');

        if (typeof QRCode !== 'undefined') {
            QRCode.toCanvas(qrCanvas, payload, {
                width: 110,
                margin: 0,
                color: {
                    dark: '#000000',
                    light: '#ffffff'
                },
                errorCorrectionLevel: 'M' // Required error correction level
            }, function(error) {
                if (error) {
                    console.error('QR Code Error:', error);
                    return;
                }

                // Draw Swiss cross on top of generated QR Canvas
                const ctxQR = qrCanvas.getContext('2d');
                const W = qrCanvas.width;
                
                // 1. White outer square (quiet zone wrapper)
                const wOuter = W * 0.18;
                ctxQR.fillStyle = '#ffffff';
                ctxQR.fillRect(W/2 - wOuter/2, W/2 - wOuter/2, wOuter, wOuter);

                // 2. Black inner square
                const wInner = W * 0.152;
                ctxQR.fillStyle = '#000000';
                ctxQR.fillRect(W/2 - wInner/2, W/2 - wInner/2, wInner, wInner);

                // 3. White cross
                ctxQR.fillStyle = '#ffffff';
                const vBarWidth = wInner * 0.24;
                const vBarHeight = wInner * 0.62;
                ctxQR.fillRect(W/2 - vBarWidth/2, W/2 - vBarHeight/2, vBarWidth, vBarHeight);

                const hBarWidth = wInner * 0.62;
                const hBarHeight = wInner * 0.24;
                ctxQR.fillRect(W/2 - hBarWidth/2, W/2 - hBarHeight/2, hBarWidth, hBarHeight);
            });
        }
    }

    function calculateTotals() {
        let grandTotal = 0;
        const rows = servicesTbody.querySelectorAll('tr');
        let hasItems = false;

        rows.forEach(row => {
            const qtyVal = row.querySelector('.qty-input').value;
            const priceVal = row.querySelector('.price-input').value;
            
            if (qtyVal !== '' || priceVal !== '') {
                hasItems = true;
            }

            const qty = parseFloat(qtyVal) || 0;
            const price = parseFloat(priceVal) || 0;
            const rowTotal = qty * price;
            grandTotal += rowTotal;

            row.querySelector('.row-total-cell').textContent = formatCHF(rowTotal);
        });

        if (!hasItems && grandTotal === 0) {
            grandTotalDisplay.textContent = 'CHF 0.00';
        } else {
            if (payeToggle.checked) {
                const parts = grandTotal.toFixed(2).split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, "'");
                grandTotalDisplay.textContent = `Payé ce jour ${parts.join('.')} fr`;
            } else {
                grandTotalDisplay.textContent = formatCHF(grandTotal);
            }
        }

        // Dynamically update the Swiss QR Code with current grand total
        updateSwissQRCode(grandTotal);
    }

    // Attach listeners for dynamic QR elements to recalculate in real-time
    [invoiceNumber, customerName, customerAddress].forEach(input => {
        input.addEventListener('input', () => {
            calculateTotals();
        });
    });

    addRowBtn.addEventListener('click', () => {
        createServiceRow('', '', '');
        showToast('Nova linha adicionada.');
    });

    payeToggle.addEventListener('change', calculateTotals);

    // Initial state: Start empty to allow typing from scratch
    createServiceRow('', '', '');

    // Toast Notification helper
    function showToast(message) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Save Registration logic
    saveBtn.addEventListener('click', () => {
        const clientName = customerName.value.trim();
        if (!clientName) {
            showToast('Informe o Nome/Sociedade do cliente.');
            customerName.focus();
            return;
        }

        const services = [];
        servicesTbody.querySelectorAll('tr').forEach(row => {
            services.push({
                description: row.querySelector('.desc-input').value,
                quantity: row.querySelector('.qty-input').value,
                price: row.querySelector('.price-input').value
            });
        });

        const registration = {
            id: Date.now().toString(),
            invoiceNumber: invoiceNumber.value,
            invoiceDate: invoiceDate.value,
            customerName: clientName,
            customerAddress: customerAddress.value,
            customerPhone: customerPhone.value,
            customerEmail: customerEmail.value,
            services: services,
            sigName: sigNameInput.value,
            sigDate: sigDateInput.value,
            payeChecked: payeToggle.checked,
            signatureImage: signatureCanvas.toDataURL()
        };

        let saved = localStorage.getItem('lima_registrations');
        saved = saved ? JSON.parse(saved) : [];
        saved.push(registration);
        localStorage.setItem('lima_registrations', JSON.stringify(saved));

        showToast('Fatura salva com sucesso!');
        loadHistory();
    });

    // Load History list
    function loadHistory() {
        let saved = localStorage.getItem('lima_registrations');
        saved = saved ? JSON.parse(saved) : [];

        if (saved.length === 0) {
            historyList.innerHTML = '<p class="empty-msg">Nenhum cadastro salvo localmente.</p>';
            return;
        }

        historyList.innerHTML = '';
        saved.forEach(item => {
            const div = document.createElement('div');
            div.className = 'history-item';
            
            const total = item.services.reduce((acc, curr) => {
                const q = parseFloat(curr.quantity) || 0;
                const p = parseFloat(curr.price) || 0;
                return acc + (q * p);
            }, 0);

            div.innerHTML = `
                <div class="history-item-info">
                    <div class="history-item-name">${item.customerName}</div>
                    <div class="history-item-date">N° ${item.invoiceNumber} • ${formatCHF(total)}</div>
                </div>
                <button type="button" class="delete-history-btn" title="Excluir">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            `;

            div.querySelector('.history-item-info').addEventListener('click', () => {
                loadRegistration(item);
            });

            div.querySelector('.delete-history-btn').addEventListener('click', (e) => {
                e.stopPropagation();
                deleteHistoryItem(item.id);
            });

            historyList.appendChild(div);
        });
    }

    function deleteHistoryItem(id) {
        let saved = localStorage.getItem('lima_registrations');
        if (saved) {
            saved = JSON.parse(saved).filter(item => item.id !== id);
            localStorage.setItem('lima_registrations', JSON.stringify(saved));
            showToast('Fatura excluída.');
            loadHistory();
        }
    }

    function loadRegistration(item) {
        invoiceNumber.value = item.invoiceNumber || '';
        invoiceDate.value = item.invoiceDate || '';
        customerName.value = item.customerName || '';
        customerAddress.value = item.customerAddress || '';
        customerPhone.value = item.customerPhone || '';
        customerEmail.value = item.customerEmail || '';
        sigNameInput.value = item.sigName || '';
        sigDateInput.value = item.sigDate || '';
        payeToggle.checked = item.payeChecked || false;

        // Restore services
        servicesTbody.innerHTML = '';
        if (item.services && item.services.length > 0) {
            item.services.forEach(srv => {
                createServiceRow(srv.description, srv.quantity, srv.price);
            });
        } else {
            createServiceRow('', '', '');
        }

        // Restore signature
        ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        if (item.signatureImage) {
            const img = new Image();
            img.onload = function() {
                ctx.drawImage(img, 0, 0);
            };
            img.src = item.signatureImage;
        }

        showToast('Fatura carregada.');
    }

    // Clear All
    clearAllBtn.addEventListener('click', () => {
        invoiceNumber.value = '';
        invoiceDate.value = formatDateDDMMYYYY(new Date());
        customerName.value = '';
        customerAddress.value = '';
        customerPhone.value = '';
        customerEmail.value = '';
        sigNameInput.value = '';
        sigDateInput.value = formatDateDDMMYYYY(new Date());
        payeToggle.checked = false;
        
        ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        servicesTbody.innerHTML = '';
        createServiceRow('', '', '');
        
        showToast('Campos limpos.');
    });

    // PDF Generation
    pdfBtn.addEventListener('click', () => {
        showToast('Gerando PDF...');
        
        document.body.classList.add('is-printing');
        
        const element = document.getElementById('invoice-document');
        
        const opt = {
            margin:       10,
            filename:     `facture_lima_${customerName.value.replace(/[^a-z0-9]/gi, '_').toLowerCase() || 'invoice'}.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2.5, useCORS: true, letterRendering: true, scrollY: 0 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            document.body.classList.remove('is-printing');
            showToast('PDF gerado com sucesso!');
        }).catch(err => {
            document.body.classList.remove('is-printing');
            console.error(err);
            showToast('Fallback: Abrindo janela de impressão...');
            window.print();
        });
    });

    loadHistory();
});
