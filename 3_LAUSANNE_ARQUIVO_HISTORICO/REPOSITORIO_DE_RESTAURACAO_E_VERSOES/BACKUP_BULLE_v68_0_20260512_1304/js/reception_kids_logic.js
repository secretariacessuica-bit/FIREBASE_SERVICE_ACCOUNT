// Ensure db uses the instance injected by parent HTML if missing in scope
// CORE LOGIC v70.3.31 Diamond (Cache Force)
window.db = window.db || (typeof firebase !== 'undefined' ? firebase.firestore() : null);

// [DIAMOND v51.1] IDENTITY RECEIVER
window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'MASTER_BYPASS_SIGNAL') {
        const pin = event.data.pin;
        console.log("💎 Diamond Logic: Sinal de Identidade Recebido. Validando sessão...");
        if (pin === 'Catedral@2025!') {
            localStorage.setItem('current_pin', pin);
            sessionStorage.setItem('master_bypass', 'true');
            // Refresh logic if needed, or just allow future calls
            if (typeof loadAllKidsList === 'function' && document.getElementById('child-search-results')) {
                loadAllKidsList();
            }
        }
    }
});

/* --- CHILD REGISTRATION & CHECK-IN LOGIC --- */

window.switchChildSubMode = function (mode, btn) {
    // UI Toggle
    document.querySelectorAll('#mode-child .segment-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // View Toggle
    document.querySelectorAll('.child-sub-area').forEach(div => div.style.display = 'none');
    document.getElementById('child-sub-' + mode).style.display = 'block';

    if (mode === 'exist') {
        loadAllKidsList();
    }
}

let allKidsCache = [];
let lastKidsFetch = 0;
let checkedInTodayIds = new Set();
let lastAttendanceFetch = 0;

window.loadAllKidsList = async function() {
    const listEl = document.getElementById('child-search-results');
    if (!listEl) return;

    listEl.innerHTML = '<div style="color:#aaa; text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Sincronizando lista...</div>';

    try {
        const now = Date.now();
        const today = new Date();
        today.setHours(0,0,0,0);
        
        // 1. Fetch kids already checked in today (Throttle to 30 seconds)
        if (now - lastAttendanceFetch > 30000 || checkedInTodayIds.size === 0) {
            const attendSnap = await db.collection('attendance')
                .where('timestamp', '>=', firebase.firestore.Timestamp.fromDate(today))
                .get();
            
            checkedInTodayIds = new Set();
            attendSnap.forEach(doc => {
                const data = doc.data();
                if (data.type === 'kids') {
                    if (data.kid_id) checkedInTodayIds.add(data.kid_id);
                    else checkedInTodayIds.add(data.name);
                }
            });
            lastAttendanceFetch = now;
        }

        // 2. Load all kids if cache is empty or stale (10 minutes)
        if (allKidsCache.length === 0 || (now - lastKidsFetch > 600000)) {
            const snap = await db.collection('kids').orderBy('name').get();
            const newList = [];
            snap.forEach(doc => {
                newList.push({ id: doc.id, ...doc.data() });
            });
            allKidsCache = newList;
            lastKidsFetch = now;
        }
        
        renderKidsSelection();
    } catch (err) {
        console.error("Error loading kids:", err);
        listEl.innerHTML = '<div style="color:red; padding:10px;">Erro de conexão. Verifique o sinal.</div>';
    }
}

window.renderKidsSelection = function(filter = '') {
    const listEl = document.getElementById('child-search-results');
    if (!listEl) return;

    const query = filter.toLowerCase().trim();
    // Filter by search query AND exclude kids already checked in today
    const filtered = allKidsCache.filter(k => {
        const matchesSearch = k.name.toLowerCase().includes(query) || (k.parent && k.parent.toLowerCase().includes(query));
        const alreadyIn = checkedInTodayIds.has(k.id) || checkedInTodayIds.has(k.name);
        return matchesSearch && !alreadyIn;
    });

    if (filtered.length === 0) {
        listEl.innerHTML = '<div style="color:#aaa; text-align:center; padding:40px; border: 2px dashed #eee; border-radius: 20px;">' + 
            (query ? 'Nenhuma crianca encontrada para esta busca.' : 'Todas as criancas ja estao na sala! <br><i class="fas fa-check-double" style="margin-top:10px; color:#10b981; font-size:1.5rem;"></i>') + 
            '</div>';
        return;
    }

    listEl.innerHTML = '';
    filtered.forEach(k => {
        const div = document.createElement('div');
        div.className = 'selection-card-kid';
        
        const kidName = k.name || k.child_name || k.nome || 'Sem Nome';
        const parentName = k.parent || k.parent_name || k.responsavel || 'Responsável não informado';

        div.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="flex:1;">
                    <strong style="font-size:1.15rem; display:block; color:#1a1a1a; margin-bottom:4px;">${kidName}</strong>
                    <div style='font-size:0.85rem; color:#666; display:flex; align-items:center; gap:6px;'>
                        <i class="fas fa-user-friends" style="color:#d4af37;"></i>
                        <span>Resp: <b>${parentName}</b></span>
                    </div>
                </div>
                <div style="text-align:right;">
                    <span style="background:rgba(212,175,55,0.15); color:#b45309; padding:6px 10px; border-radius:8px; font-size:0.75rem; font-weight:800; border:1px solid rgba(212,175,55,0.3);">
                        ${k.age_group || '?'}
                    </span>
                </div>
            </div>
        `;
        div.onclick = () => window.submitChildExisting(k);
        listEl.appendChild(div);
    });
}

let parentSearchTimeout;
let isParentMembro = false;

window.searchParentForChild = function (phone) {
    const statusEl = document.getElementById('parent-search-status');
    const btnSubmit = document.getElementById('btn-child-submit');

    // reset downstream fields
    document.getElementById('child-step-2').style.display = 'none';
    document.getElementById('child-step-3').style.display = 'none';
    document.getElementById('child-step-4').style.display = 'none';
    btnSubmit.style.display = 'none';

    isParentMembro = false;
    document.getElementById('child-parent').value = '';

    if (phone.length < 8) {
        statusEl.innerText = 'Digite o WhatsApp completo para buscar...';
        return;
    }

    statusEl.innerText = 'Buscando responsável...';
    clearTimeout(parentSearchTimeout);

    parentSearchTimeout = setTimeout(() => {
        db.collection('people').where('contact', '==', phone).limit(1).get()
            .then(snap => {
                if (!snap.empty) {
                    const p = snap.docs[0].data();
                    document.getElementById('child-parent').value = p.name || p.nome_completo || '';

                    // Check if Membro
                    if (p.baptized === true || (p.type && p.type.toLowerCase().includes('membro'))) {
                        isParentMembro = true;
                        statusEl.innerHTML = `<span style="color:#16a34a;"><i class="fas fa-check-circle"></i> Responsável Encontrado (Membro)</span>`;
                    } else {
                        statusEl.innerHTML = `<span style="color:#2563eb;"><i class="fas fa-check-circle"></i> Responsável Encontrado</span>`;
                    }
                } else {
                    statusEl.innerHTML = `<span style="color:#d97706;"><i class="fas fa-info-circle"></i> Novo Responsável — Prossiga</span>`;
                }

                // Advance to step 2 automatically
                document.getElementById('child-step-2').style.display = 'block';
            })
            .catch(err => {
                console.error(err);
                statusEl.innerText = 'Erro na busca.';
                document.getElementById('child-step-2').style.display = 'block';
            });
    }, 600);
}

window.checkChildStep = function (step) {
    const parent = document.getElementById('child-parent').value;
    const name = document.getElementById('child-name').value;
    const age = document.getElementById('child-age').value;

    if (step >= 2 && name.length >= 2 && parent.length >= 2) {
        document.getElementById('child-step-3').style.display = 'block';
    }

    if (step >= 3 && age) {
        if (isParentMembro) {
            document.getElementById('child-step-4').style.display = 'block';
        }
        document.getElementById('btn-child-submit').style.display = 'block';
    }
}

// 1. SAVE NEW KID + CHECK-IN
window.submitChildNew = function () {
    const phone = document.getElementById('child-phone').value;
    const parent = document.getElementById('child-parent').value;
    const name = document.getElementById('child-name').value;
    const age = document.getElementById('child-age').value;

    const allergies = document.getElementById('child-allergies') ? document.getElementById('child-allergies').value : '';
    const special = document.getElementById('child-special') ? document.getElementById('child-special').value : '';
    const isPresentation = document.getElementById('child-presentation-new') ? document.getElementById('child-presentation-new').checked : false;

    if (!name || !parent || !phone) return alert("WhatsApp do Responsável, Nome do Responsável e da Criança são obrigatórios!");

    const batch = db.batch();

    // A. Save to Permanent 'kids' collection
    const refKid = db.collection('kids').doc();
    batch.set(refKid, {
        name: name,
        search_name: name.toLowerCase(),
        age_group: age,
        parent: parent,
        phone: phone,
        is_parent_member: isParentMembro,
        allergies: allergies,
        special_attention: special,
        timestamp: firebase.firestore.FieldValue.serverTimestamp()
    });

    // B. Save to 'attendance' (Waiting Confirmation)
    let extraDetail = `Faixa: ${age} | Resp: ${parent} (${phone})`;
    if (allergies || special) {
        extraDetail += ` | ⚠️ SEGURANÇA: ${allergies ? 'Alergias: ' + allergies + '. ' : ''}${special ? 'Atenção: ' + special : ''}`;
    }

    const refAtt = db.collection('attendance').doc();
    batch.set(refAtt, {
        name: name,
        type: 'kids',
        detail: (isPresentation ? '[BABY] ' : '') + extraDetail,
        status: 'waiting_kids_confirm', // WAITING FOR KIDS ROOM
        parent_name: parent, // Explicitly save for easier access
        age_group: age,
        allergies: allergies,
        special_attention: special,
        is_presentation: isPresentation,
        timestamp: firebase.firestore.FieldValue.serverTimestamp(),
        origin: 'reception_checkin_kids_new'
    });

    batch.commit().then(() => {
        alert("Cadastro realizado! Aguardando confirmacao na Sala das Criancas.");
        // Clear cache so it reloads on next visit to the list
        allKidsCache = [];
        location.reload();
    }).catch(err => {
        console.error(err);
        alert("Erro: " + err.message);
    });
}

// 2. SEARCH EXISTING KID
let kidSearchTimeout;
window.searchChild = function () {
    clearTimeout(kidSearchTimeout);
    const val = document.getElementById('child-search-input').value.toLowerCase();
    const results = document.getElementById('child-search-results');

    results.innerHTML = '';
    if (val.length < 3) return;

    results.innerHTML = '<div style="color:#aaa;">Buscando...</div>';

    kidSearchTimeout = setTimeout(() => {
        const valTitle = val.charAt(0).toUpperCase() + val.slice(1);

        // Perform two queries to ensure we catch both old (TitleCase 'name') and new (lowercase 'search_name') records
        const q1 = db.collection('kids').where('search_name', '>=', val).where('search_name', '<=', val + '\uf8ff').limit(5).get();
        const q2 = db.collection('kids').where('name', '>=', valTitle).where('name', '<=', valTitle + '\uf8ff').limit(5).get();

        Promise.all([q1, q2]).then(snapshots => {
            results.innerHTML = '';

            // Map to dedup by ID
            const uniqueKids = new Map();
            snapshots.forEach(snap => {
                snap.forEach(doc => {
                    uniqueKids.set(doc.id, doc.data());
                });
            });

            if (uniqueKids.size === 0) {
                results.innerHTML = '<div style="color:#aaa; padding:10px;">Nenhuma criança encontrada.</div>';
            } else {
                uniqueKids.forEach((k, id) => {
                    const div = document.createElement('div');
                    div.style.cssText = "background:rgba(255,255,255,0.05); padding:10px; margin-bottom:5px; border-radius:8px; cursor:pointer;";
                    div.innerHTML = `<strong>${k.name}</strong> <br> <span style='font-size:0.8rem; color:#ccc;'>${k.parent}</span>`;
                    div.onclick = () => window.submitChildExisting(k);
                    results.appendChild(div);
                });
            }
        }).catch(err => {
            results.innerHTML = '<div style="color:red; padding:10px;">Erro ao buscar. Tente novamente.</div>';
            console.error(err);
        });
    }, 500);
}

// 3. CHECK-IN EXISTING KID (High Speed v70.3.31)
window.submitChildExisting = function (kidData) {
    // [V31 FIX] Remove confirm for high-speed reception, but provide visual feedback
    const isPresentation = document.getElementById('child-presentation-exist') ? document.getElementById('child-presentation-exist').checked : false;

    // Add to local 'checked-in' set immediately for instant list update
    checkedInTodayIds.add(kidData.id);
    renderKidsSelection(document.getElementById('child-search-input').value);

    db.collection('attendance').add({
        kid_id: kidData.id,
        name: kidData.name,
        type: 'kids',
        detail: (isPresentation ? '[BABY] ' : '') + `Faixa: ${kidData.age_group} | Resp: ${kidData.parent} (${kidData.phone})`,
        status: 'waiting_kids_confirm',
        parent_name: kidData.parent,
        age_group: kidData.age_group,
        is_presentation: isPresentation,
        timestamp: firebase.firestore.FieldValue.serverTimestamp(),
        origin: 'reception_checkin_kids_exist'
    }).then(() => {
        // Notification Toast already exists in UI
        if(typeof showKidsArrivalToast === 'function') showKidsArrivalToast(kidData.name);
        
        // Reset inputs but don't reload page
        document.getElementById('child-search-input').value = '';
        if (document.getElementById('child-presentation-exist')) document.getElementById('child-presentation-exist').checked = false;
    }).catch(err => {
        console.error("Check-in Error:", err);
        // Rollback local state if failed
        checkedInTodayIds.delete(kidData.id);
        renderKidsSelection();
        alert("Erro ao enviar check-in: " + err.message);
    });
}

// --- RECEPTION LOGIC v3.0 ---
// Debug: Confirm file loaded
// alert("Sistema de Recepção Carregado");

window.switchCheckinMode = function (mode, btn) {
    console.log("Switching mode to:", mode);
    // UI
    document.querySelectorAll('#tab-checkin .segment-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Views
    document.querySelectorAll('#tab-checkin .tab-content-area').forEach(div => div.classList.remove('active'));
    const target = document.getElementById('mode-' + mode);
    if (target) {
        target.classList.add('active');
        // If mode is 'events', load them
        if(mode === 'events') {
            loadSecretariatEvents();
        }
    } else {
        console.error("Target view not found: mode-" + mode);
    }
}

// [NEW] SECRETARIAT EVENTS SYNC v70.3.31
window.loadSecretariatEvents = function() {
    const container = document.getElementById('secretariat-events-container');
    if(!container) return;
    
    container.innerHTML = '<div style="text-align:center; color:#aaa; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Sincronizando com a Secretaria...</div>';

    // Fetch active events from today onwards (YYYY-MM-DD string comparison)
    const todayStr = new Date().toISOString().split('T')[0];
    
    db.collection('events')
      .where('date', '>=', todayStr)
      .limit(10)
      .onSnapshot(snap => {
          container.innerHTML = '';
          if(snap.empty) {
              container.innerHTML = `
                <div style="text-align:center; padding:30px; border: 2px dashed #eee; border-radius:15px; background:#fafafa;">
                    <i class="fas fa-calendar-times" style="font-size:2rem; color:#cbd5e1; margin-bottom:10px;"></i>
                    <p style="color:#94a3b8; font-size:0.9rem; margin:0;">Nenhum evento agendado pela Secretaria para os proximos dias.</p>
                </div>`;
              return;
          }
          
          snap.forEach(doc => {
              const ev = doc.data();
              const dateParts = (ev.date || '').split('-');
              const displayDate = dateParts.length === 3 ? `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}` : (ev.date || 'Sem data');
              
              const card = document.createElement('div');
              card.className = 'selection-card-kid';
              card.style.borderLeft = "4px solid var(--gold-primary)";
              card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <strong style="font-size:1.1rem; color:#1e293b;">${ev.name || ev.title || 'Evento sem Titulo'}</strong>
                        <div style="font-size:0.8rem; color:#64748b; margin-top:3px;">
                            <i class="fas fa-clock" style="margin-right:4px;"></i> ${displayDate} ${ev.startTime ? 'as ' + ev.startTime : ''}
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#cbd5e1;"></i>
                </div>
              `;
              card.onclick = () => alert("Check-in para '" + (ev.name || ev.title || 'este evento') + "' sera ativado assim que a Secretaria abrir as inscricoes.");
              container.appendChild(card);
          });
      });
}

// Visitor Wizard
window.nextStep = function (current) {
    const next = current + 1;
    // Simple validation could go here
    document.getElementById('step-' + current).classList.remove('active');
    document.getElementById('step-' + next).classList.add('active');
}

window.prevStep = function (current) {
    const prev = current - 1;
    document.getElementById('step-' + current).classList.remove('active');
    document.getElementById('step-' + prev).classList.add('active');
}

/**
 * [DIAMOND v51.2.5] Unified Integration Trigger
 * Ensures that any visitor (new or returning) is sent to the integration queue
 * if they are not already being processed.
 */
async function triggerIntegrationWorkflow(personId, name, phone, extraStr, isNew, batch) {
    try {
        console.log("💎 Diamond: Triggering Integration Workflow for", name);
        
        // Check if there's already an active integration process for this person
        // We look for 'pending_integracao' (Acolhimento) or 'em_integracao' (Followup)
        const q1 = db.collection('integracao').where('person_id', '==', personId).where('status', '==', 'pending_integracao').limit(1).get();
        const q2 = db.collection('integracao').where('person_id', '==', personId).where('status', '==', 'em_integracao').limit(1).get();
        
        const [snapPending, snapActive] = await Promise.all([q1, q2]);
        
        if (!snapPending.empty) {
            // Already in Triage/Acolhimento - Just bump the timestamp to top
            console.log("💎 Diamond: Person already in Triage. Bumping timestamp.");
            batch.update(db.collection('integracao').doc(snapPending.docs[0].id), {
                timestamp: firebase.firestore.FieldValue.serverTimestamp(),
                last_seen: firebase.firestore.FieldValue.serverTimestamp(),
                reception_bump: true
            });
        } 
        else if (!snapActive.empty) {
            // Already in Followup - Log the visit in the integration history
            console.log("💎 Diamond: Person already in Active Followup. Logging visit.");
            batch.update(db.collection('integracao').doc(snapActive.docs[0].id), {
                last_visit: firebase.firestore.FieldValue.serverTimestamp(),
                history: firebase.firestore.FieldValue.arrayUnion({
                    action: 'Retorno na Recepcao',
                    notes: 'Visitante retornou e foi registrado na recepção hoje.',
                    timestamp: firebase.firestore.Timestamp.now()
                })
            });
        }
        else {
            // Not in integration flow - Create new record
            console.log("💎 Diamond: Creating new integration record.");
            const refIntegracao = db.collection('integracao').doc();
            batch.set(refIntegracao, {
                person_id: personId,
                name: name,
                contact: phone,
                type: 'visitante',
                status: 'pending_integracao',
                extra: extraStr,
                timestamp: firebase.firestore.FieldValue.serverTimestamp(),
                origin: isNew ? 'reception_new' : 'reception_return',
                is_reappearance: !isNew
            });
        }
    } catch (e) {
        console.error("❌ Integration Trigger Error:", e);
        // We don't throw here to avoid blocking the main attendance check-in
    }
}

window.submitVisitor = async function () {
    const name = document.getElementById('vis-name').value;
    const rawPhone = document.getElementById('vis-phone').value.trim();
    const ddi = document.getElementById('vis-ddi') ? document.getElementById('vis-ddi').value : '+41';
    const phone = rawPhone ? (ddi + " " + rawPhone) : "";
    const isChristian = document.getElementById('vis-christian').value;
    const church = document.getElementById('vis-church').value;
    const invited = document.getElementById('vis-invited-by').value;

    if (!name) return alert("O Nome completo é obrigatório!");

    // Disable button to prevent multiple submissions
    const btn = event ? event.target : document.activeElement;
    const originalText = btn ? btn.innerText : 'Salvar';
    if (btn && btn.tagName === 'BUTTON') {
        btn.disabled = true;
        btn.innerText = 'Carregando...';
        btn.style.opacity = '0.7';
    }

    try {
        // 1. Get Receptionist Info
        const acolhedor = localStorage.getItem('acolhedor_name') || 'Equipe';

        // 1b. Check if Person Exists
        let querySnap = { empty: true };
        if (phone) {
            querySnap = await db.collection('people').where('contact', '==', phone).limit(1).get();
        }

        let personId;
        let pData = null;
        let isNewPerson = true;

        if (!querySnap.empty) {
            const docSnap = querySnap.docs[0];
            personId = docSnap.id;
            pData = docSnap.data();
            isNewPerson = false;
        } else {
            // Fallback search by exact name just in case phone had different formatting
            const nameSnap = await db.collection('people').where('name', '==', name).limit(1).get();
            if (!nameSnap.empty) {
                const docSnap = nameSnap.docs[0];
                personId = docSnap.id;
                pData = docSnap.data();
                isNewPerson = false;
            }
        }

        const batch = db.batch();

        // 2. Determine type and attendance count
        let newAttendanceCount = 1;
        let personType = 'visitante';
        let needsUpdate = false;

        if (isNewPerson) {
            personId = db.collection('people').doc().id;
            const refPerson = db.collection('people').doc(personId);
            batch.set(refPerson, {
                name: name,
                contact: phone || null,
                type: 'visitante',
                is_christian: isChristian === 'sim',
                origin_church: church,
                invited_by: invited,
                attendance_count: 1,
                acolhedor_responsavel: acolhedor,
                created_at: firebase.firestore.FieldValue.serverTimestamp()
            });
        } else {
            // Existing Person
            newAttendanceCount = (pData.attendance_count || 1) + 1;
            personType = (pData.type && pData.type.toLowerCase() === 'visitante' && newAttendanceCount >= 3) ? 'congregado' : (pData.type || 'visitante');

            // Mark for data update if they just transitioned
            if (pData.type !== 'congregado' && personType === 'congregado') {
                needsUpdate = true;
            }

            const refPerson = db.collection('people').doc(personId);
            batch.update(refPerson, {
                attendance_count: newAttendanceCount,
                type: personType,
                needs_update: needsUpdate,
                acolhedor_responsavel: acolhedor,
                last_visit: firebase.firestore.FieldValue.serverTimestamp()
            });
        }

        const visitReason = document.getElementById('vis-reason') ? document.getElementById('vis-reason').value : 'culto';

        // Build extra info for Altar parser
        let extraStr = '';
        if (isChristian === 'sim') {
            extraStr += `Evangélico: Sim ${church ? `(${church})` : ''} | `;
        } else {
            extraStr += `Não Evangélico | `;
        }
        if (invited) {
            extraStr += `Convidado por: ${invited}`;
        }
        // append visit count to give speaker context
        extraStr += ` | Visita #${newAttendanceCount} | Acolhedor: ${acolhedor}`;

        // 3. Add to Attendance (For Altar View)
        const refAtt = db.collection('attendance').doc();
        batch.set(refAtt, {
            person_id: personId,
            name: name,
            type: personType,
            status: 'present',
            extra: extraStr,
            detail: isNewPerson ? 'Primeira Visita' : `Visita #${newAttendanceCount}`,
            timestamp: firebase.firestore.FieldValue.serverTimestamp(),
            origin: isNewPerson ? 'reception_visitor_new' : 'reception_visitor_returning'
        });

        // 4. Send to Integration Team (Unified v51.2.5)
        if (visitReason === 'culto') {
            await triggerIntegrationWorkflow(personId, name, phone, extraStr, isNewPerson, batch);
        } else {
            // [RULE v63.2] - Event visitors don't enter integration, but log for Secretariat
            const refSecretariat = db.collection('pending').doc();
            batch.set(refSecretariat, {
                type: 'visitante_evento',
                name: name,
                contact: phone,
                extra: `Registro via Recepção (Apenas Evento). Extra: ${extraStr}`,
                status: 'pending',
                timestamp: firebase.firestore.FieldValue.serverTimestamp()
            });
        }

        await batch.commit();

        if (needsUpdate) {
            alert(`Parabens! Esta e a 3a visita de ${name}. Ele(a) agora e um CONGREGADO!`);
        } else {
            alert("Check-in Registrado com Sucesso!");
        }
        location.reload();

    } catch (err) {
        console.error(err);
        alert("Erro: " + err.message);
        if (btn && btn.tagName === 'BUTTON') {
            btn.disabled = false;
            btn.innerText = originalText;
            btn.style.opacity = '1';
        }
    }
}

// --- VISITOR RETURNING LOGIC ---
window.switchVisitorSubMode = function (mode, btn) {
    // UI Toggle
    document.querySelectorAll('#mode-visitor .segment-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // View Toggle
    document.querySelectorAll('.visitor-sub-area').forEach(div => div.style.display = 'none');
    document.getElementById('visitor-sub-' + mode).style.display = 'block';
}

let visitorSearchTimeout;
window.searchReturningVisitor = function () {
    clearTimeout(visitorSearchTimeout);
    const val = document.getElementById('visitor-search-input').value.toLowerCase().trim();
    const results = document.getElementById('visitor-search-results');

    if (val.length < 3) {
        results.innerHTML = '<div style="color:#aaa; text-align:center; padding:20px; font-style: italic;"><i class="fas fa-search" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.5;"></i>Use a barra acima para pesquisar (Mínimo 3 letras... )</div>';
        return;
    }

    results.innerHTML = '<div style="color:#666; text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; color: var(--gold-primary); margin-bottom:10px;"></i><br>Buscando visitantes...</div>';

    visitorSearchTimeout = setTimeout(() => {
        const valTitle = val.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        const valUpper = val.toUpperCase();

        const q1 = db.collection('people').where('name', '>=', valTitle).where('name', '<=', valTitle + '\uf8ff').limit(15).get();
        const q2 = db.collection('people').where('search_name', '>=', val).where('search_name', '<=', val + '\uf8ff').limit(15).get();
        const q3 = db.collection('people').where('name', '>=', valUpper).where('name', '<=', valUpper + '\uf8ff').limit(5).get();

        Promise.all([q1, q2, q3]).then(snapshots => {
            results.innerHTML = '';

            const uniquePeople = new Map();
            snapshots.forEach(snap => {
                if (!snap || !snap.forEach) return;
                snap.forEach(doc => {
                    const data = doc.data();
                    const type = (data.type || '').toLowerCase();
                    if (type.includes('visitante') || type.includes('congrega')) {
                        uniquePeople.set(doc.id, data);
                    }
                });
            });

            if (uniquePeople.size === 0) {
                results.innerHTML = '<div style="color:#854d0e; background:#fefce8; padding:15px; border-radius:12px; border:1px solid #fef08a; text-align:center;"><i class="fas fa-exclamation-circle" style="margin-bottom:8px; font-size:1.2rem;"></i><br>Nenhum visitante encontrado com esse nome.<br><button onclick="switchVisitorSubMode(\'new\', document.querySelector(\'#mode-visitor .segment-btn\'))" class="btn-large btn-outline" style="margin-top:10px; border-color:#ca8a04; color:#a16207;">Criar Novo Cadastro</button></div>';
            } else {
                uniquePeople.forEach((p, id) => {
                    const div = document.createElement('div');
                    div.style.cssText = "background:#fff; border:1px solid #e2e8f0; padding:15px; margin-bottom:10px; border-radius:12px; cursor:pointer; color:#333; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 5px rgba(0,0,0,0.02); transition: 0.2s;";
                    div.onmouseover = () => div.style.borderColor = "#d4af37";
                    div.onmouseout = () => div.style.borderColor = "#e2e8f0";

                    const count = p.attendance_count || 1;
                    const isCong = (p.type || '').toLowerCase().includes('congrega');
                    const badgeStr = isCong ? `<span style="background:#f3e8ff; color:#7e22ce; padding:4px 10px; border-radius:8px; font-size:0.75rem; font-weight:800; border:1px solid #e9d5ff;">Congregado</span>` : `<span style="background:#fefce8; color:#a16207; padding:4px 10px; border-radius:8px; font-size:0.75rem; font-weight:800; border:1px solid #fef08a;">Visita #${count}</span>`;

                    div.innerHTML = `<div style="flex:1;"><strong style="font-size:1.1rem; display:block; margin-bottom:2px;">${p.name}</strong> <span style='font-size:0.85rem; color:#64748b;'><i class="fas fa-phone-alt" style="font-size:0.7rem; margin-right:4px;"></i>${p.contact || p.phone || p.whatsapp || 'Sem Contato'}</span></div> <div style="margin-left:10px;">${badgeStr}</div>`;

                    div.onclick = () => window.submitReturningVisitor(id, p);
                    results.appendChild(div);
                });
            }
        }).catch(err => {
            results.innerHTML = '<div style="color:#b91c1c; background:#fee2e2; padding:15px; border-radius:12px; text-align:center;">Erro ao buscar. Verifique a conexão.</div>';
            console.error(err);
        });
    }, 600);
}

window.submitReturningVisitor = async function (personId, pData) {
    if (!confirm(`Confirmar check-in de retorno para ${pData.name}?`)) return;

    try {
        const batch = db.batch();

        let newCount = (pData.attendance_count || 1) + 1;
        let personType = (pData.type === 'visitante' && newCount >= 3) ? 'congregado' : (pData.type || 'visitante');
        let needsUpdate = false;

        if (pData.type !== 'congregado' && personType === 'congregado') {
            needsUpdate = true;
        }

        const refPerson = db.collection('people').doc(personId);
        batch.update(refPerson, {
            attendance_count: newCount,
            type: personType,
            needs_update: needsUpdate,
            last_visit: firebase.firestore.FieldValue.serverTimestamp()
        });

        // 3. Add to Attendance
        let extraStr = `Visita #${newCount}`;
        if (pData.invited_by) extraStr += ` | Convidado: ${pData.invited_by}`;

        const refAtt = db.collection('attendance').doc();
        batch.set(refAtt, {
            person_id: personId,
            name: pData.name,
            type: personType,
            status: 'present',
            extra: extraStr,
            detail: `Visita #${newCount}`,
            timestamp: firebase.firestore.FieldValue.serverTimestamp(),
            origin: 'reception_visitor_returning'
        });

        // 4. Trigger Integration Workflow for Returning Visitor (Fix v51.2.5)
        if (personType === 'visitante' || personType === 'congregado') {
            await triggerIntegrationWorkflow(personId, pData.name, pData.contact || pData.phone || '', extraStr, false, batch);
        }

        await batch.commit();

        if (needsUpdate) {
            alert(`Parabens! Esta e a 3a visita de ${pData.name}. Ele(a) foi promovido a CONGREGADO automaticamente!`);
        } else {
            alert(`Bem-vindo de volta! Check-in da Visita #${newCount} realizado.`);
        }

        document.getElementById('visitor-search-input').value = '';
        document.getElementById('visitor-search-results').innerHTML = '';
        location.reload();

    } catch (err) {
        console.error(err);
        alert("Erro ao salvar retorno: " + err.message);
    }
}
// Member Search (Stub for now, implementation missing in provided code but likely simple)
let memberSearchTimeout;
window.searchMember = function () {
    clearTimeout(memberSearchTimeout);
    const val = document.getElementById('member-search').value.toLowerCase();
    const results = document.getElementById('search-results');

    if (val.length < 3) {
        results.style.display = 'none';
        return;
    }

    memberSearchTimeout = setTimeout(() => {
        db.collection('membros').where('nome_completo', '>=', val).where('nome_completo', '<=', val + '\uf8ff').limit(5).get()
            .then(snap => {
                results.innerHTML = '';
                results.style.display = 'block';
                if (snap.empty) {
                    results.innerHTML = '<div style="padding:10px; color:#999;">Nenhum membro encontrado.</div>';
                } else {
                    snap.forEach(doc => {
                        const m = doc.data();
                        const div = document.createElement('div');
                        div.style.cssText = "padding:10px; border-bottom:1px solid #eee; cursor:pointer;";
                        div.innerHTML = `<b>${m.nome_completo}</b>`;
                        div.onclick = () => window.confirmMemberPresence(doc.id, m.nome_completo);
                        results.appendChild(div);
                    });
                }
            });
    }, 500);
}

window.confirmMemberPresence = function (id, name) {
    if (!confirm("Confirmar presença de " + name + "?")) return;
    db.collection('attendance').add({
        person_id: id,
        name: name,
        type: 'membro',
        status: 'present',
        timestamp: firebase.firestore.FieldValue.serverTimestamp(),
        origin: 'reception_member_search'
    }).then(() => {
        alert("Presenca Confirmada!");
        document.getElementById('member-search').value = '';
        document.getElementById('search-results').style.display = 'none';
    });
}

window.submitConvert = function () {
    const name = document.getElementById('convert-name').value;
    const phone = document.getElementById('convert-phone').value;
    const type = document.getElementById('convert-type').value;

    if (!name) return alert("Nome obrigatório");

    db.collection('decisions').add({
        name: name,
        contact: phone,
        type: type, // aceitou / reconciliou
        timestamp: firebase.firestore.FieldValue.serverTimestamp(),
        origin: 'reception_convert'
    }).then(() => {
        alert("Gloria a Deus! Decisao registrada.");
        location.reload();
    });
}

// --- KIDS ARRIVAL NOTIFICATION (REALTIME) ---
let kidsListenerStarted = false;
let alreadyNotifiedKids = new Set();

window.loadMuralKids = window.initReceptionMonitor = function() {
    if (kidsListenerStarted) return;
    const todayStart = new Date();
    todayStart.setHours(0,0,0,0);

    db.collection('attendance')
        .where('timestamp', '>=', firebase.firestore.Timestamp.fromDate(todayStart))
        .onSnapshot(snap => {
            if (!kidsListenerStarted) {
                kidsListenerStarted = true;
                const muralRoot = document.getElementById('mural-kids-container-root');
                if(muralRoot) muralRoot.innerHTML = '';
                
                snap.docs.forEach(d => {
                    const data = d.data();
                    // Filter in memory v70.3.31
                    if (data.type === 'kids' && (data.status === 'confirmed' || data.status === 'present') && data.timestamp && data.timestamp.toDate() >= todayStart) {
                        alreadyNotifiedKids.add(d.id);
                        appendKidToMural(d.id, data);
                    }
                });
                checkMuralEmpty();
                return;
            }

            snap.docChanges().forEach(change => {
                const data = change.doc.data();
                const id = change.doc.id;
                
                if (change.type === 'modified' || change.type === 'added') {
                    // Filter in memory v70.3.31
                    if (data.type === 'kids' && (data.status === 'confirmed' || data.status === 'present') && !alreadyNotifiedKids.has(id)) {
                        if (data.timestamp && data.timestamp.toDate() >= todayStart) {
                            alreadyNotifiedKids.add(id);
                            showKidsArrivalToast(data.name || 'Criança');
                            appendKidToMural(id, data);
                        }
                    }
                }
            });
        });
};

window.appendKidToMural = function (id, data) {
    const container = document.getElementById('mural-kids-container-root');
    if (!container) return;
    
    // Remove empty placeholder
    const empty = container.querySelector('div[style*="font-style:italic"]');
    if (empty) empty.remove();

    const div = document.createElement('div');
    // Premium Antigravity Card
    div.style.cssText = "background:#fff; border:1px solid rgba(16, 185, 129, 0.3); padding:15px; border-radius:12px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 4px 15px rgba(16, 185, 129, 0.05); margin-bottom:10px;";
    div.innerHTML = `
        <div style="flex:1;">
            <strong style="font-size:1.15rem; display:block; color:#1a1a1a; margin-bottom:4px;">${data.name || 'Criança'}</strong>
            <div style='font-size:0.85rem; color:#666;'>Resp: <b>${data.parent_name || data.parent || 'Não informado'}</b></div>
        </div>
        <div style="text-align:right;">
            <span style="background:rgba(16, 185, 129, 0.1); color:#059669; border:1px solid rgba(16, 185, 129, 0.2); padding:6px 12px; border-radius:20px; font-size:0.8rem; font-weight:800; display:inline-block;">
                <i class="fas fa-check-circle" style="margin-right:2px;"></i> ${data.age_group || 'Sala'}
            </span>
            <div style="font-size:0.7rem; color:#999; margin-top:5px;">Entregue</div>
        </div>
    `;
    // Prepend so newest is on top
    container.insertBefore(div, container.firstChild);
}

function checkMuralEmpty() {
    const container = document.getElementById('mural-kids-container');
    if (container && container.children.length === 0) {
        container.innerHTML = '<div style="text-align:center; color:#aaa; font-style:italic; padding:20px;"><i class="fas fa-child" style="font-size:2rem; color:#ddd; display:block; margin-bottom:10px;"></i>Nenhuma criança confirmada na sala ainda hoje.</div>';
    }
}

window.showKidsArrivalToast = function(kidName) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'kids-toast';
    toast.innerHTML = `<span><strong>${kidName}</strong> chegou na Sala Kids!</span>`;
    
    container.appendChild(toast);

    // Audio cue for reception
    try {
        const audio = new Audio('https://actions.google.com/sounds/v1/water/water_drop.ogg');
        audio.volume = 0.5;
        audio.play().catch(e=>console.log('Audio autoplay blocked'));
    } catch(err) {}

    // Remove from DOM after animation finishes (approx 5s)
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5500);
}

// --- UTILITIES & NAVIGATION (Restored for v48.6) ---
window.setTab = function(id) {
    console.log("Setting tab:", id);
    document.querySelectorAll('.tab-section').forEach(el => el.style.display = 'none');
    const target = document.getElementById('tab-' + id);
    if (target) {
        target.style.display = 'block';
    } else {
        console.warn("Tab target not found: tab-" + id);
    }

    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    const navItem = document.getElementById('nav-' + id);
    if (navItem) navItem.classList.add('active');
}

window.toggleSub = function(id, el) {
    const target = document.getElementById(id);
    if (target) target.style.display = el.checked ? 'block' : 'none';
}

window.addLivePerson = function() {
    const list = document.getElementById('live-names-list');
    if (!list) return;
    const count = list.children.length + 1;
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'live-name-input';
    input.placeholder = `Nome da Pessoa ${count}...`;
    list.appendChild(input);
    input.focus();
}

window.submitScheduleFull = async function() {
    const name = document.getElementById('sched-name').value;
    const contact = document.getElementById('sched-contact').value;

    if (!name || !contact) return alert("Nome e Contato obrigatórios");

    const pending = [];
    if (document.getElementById('sched-visit').checked) {
        const date = document.getElementById('sched-visit-date').value;
        pending.push({ type: 'Receber Visita', obs: `Data: ${date}` });
    }
    if (document.getElementById('sched-group').checked) {
        const grp = document.getElementById('sched-group-opt').value;
        pending.push({ type: 'Entrar em Grupo', obs: grp });
    }
    if (document.getElementById('sched-baby').checked) {
        const bn = document.getElementById('sched-baby-name').value;
        pending.push({ type: 'Agendamento Bebê', obs: bn });
    }
    if (document.getElementById('sched-baptism').checked) {
        pending.push({ type: 'Agendamento Batismo', obs: 'Solicitado' });
    }

    if (pending.length === 0) return alert("Selecione pelo menos uma opção de agendamento.");

    try {
        const batch = db.batch();
        pending.forEach(p => {
            const ref = db.collection('pending').doc();
            batch.set(ref, {
                type: p.type, 
                obs: p.obs || '', 
                person_name: name, 
                contact: contact, 
                status: 'pending', 
                timestamp: firebase.firestore.FieldValue.serverTimestamp()
            });
        });
        await batch.commit();
        alert("✅ Agendamentos enviados com sucesso!");
        location.reload();
    } catch(err) {
        console.error("Error submitting schedule:", err);
        alert("Erro ao enviar agendamento.");
    }
}
