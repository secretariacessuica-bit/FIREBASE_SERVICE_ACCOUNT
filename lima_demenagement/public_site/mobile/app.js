// LIMA Solutions ERP - Mobile App MVP JS V1
// Arquitetura Offline-First com IndexedDB e sincronizador automático

const DB_NAME = "lima_mobile_db";
const DB_VERSION = 1;

let db;
let syncIntervalId = null;
let activeProjectId = null;
let gpsIntervalId = null;

// Inicialização e configuração do banco IndexedDB
function initDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = function(e) {
      const database = e.target.result;
      
      // Armazena cache de projetos carregados do servidor
      if (!database.objectStoreNames.contains("projects")) {
        database.createObjectStore("projects", { keyPath: "id" });
      }

      // Armazena as checklists dos projetos
      if (!database.objectStoreNames.contains("checklists")) {
        database.createObjectStore("checklists", { keyPath: "project_id" });
      }

      // Fila de sincronização offline (timesheets, gps, fotos, assinaturas, checklists)
      if (!database.objectStoreNames.contains("sync_queue")) {
        database.createObjectStore("sync_queue", { keyPath: "id", autoIncrement: true });
      }
    };

    request.onsuccess = function(e) {
      db = e.target.result;
      resolve(db);
    };

    request.onerror = function(e) {
      reject("IndexedDB failed: " + e.target.errorCode);
    };
  });
}

// Helpers para IndexedDB
function getObjectStore(storeName, mode = "readonly") {
  const transaction = db.transaction(storeName, mode);
  return transaction.objectStore(storeName);
}

function saveLocal(storeName, item) {
  return new Promise((resolve, reject) => {
    const store = getObjectStore(storeName, "readwrite");
    const request = store.put(item);
    request.onsuccess = () => resolve(true);
    request.onerror = (e) => reject(e.target.error);
  });
}

function getLocal(storeName, key) {
  return new Promise((resolve, reject) => {
    const store = getObjectStore(storeName, "readonly");
    const request = store.get(key);
    request.onsuccess = (e) => resolve(e.target.result);
    request.onerror = (e) => reject(e.target.error);
  });
}

function getAllLocal(storeName) {
  return new Promise((resolve, reject) => {
    const store = getObjectStore(storeName, "readonly");
    const request = store.getAll();
    request.onsuccess = (e) => resolve(e.target.result);
    request.onerror = (e) => reject(e.target.error);
  });
}

function deleteLocal(storeName, key) {
  return new Promise((resolve, reject) => {
    const store = getObjectStore(storeName, "readwrite");
    const request = store.delete(key);
    request.onsuccess = () => resolve(true);
    request.onerror = (e) => reject(e.target.error);
  });
}

// -------------------------------------------------------------
// Sincronizador de rede e Fila de Operações Offline
// -------------------------------------------------------------
function addToSyncQueue(type, payload) {
  const item = {
    type: type,
    payload: payload,
    client_uuid: payload.client_uuid || generateUUID(),
    created_offline_at: payload.created_offline_at || new Date().toISOString(),
    status: "Pending",
    error_message: null
  };
  return saveLocal("sync_queue", item).then(() => {
    updateUIQueueCount();
  });
}

function updateUIQueueCount() {
  getAllLocal("sync_queue").then(items => {
    const pending = items.filter(x => x.status === "Pending" || x.status === "Failed").length;
    document.getElementById("sync-queue-count").textContent = "File d'attente: " + pending;
    
    // Atualizar UI detalhada se visível
    const detailBox = document.getElementById("sync-queue-detail-card");
    if (detailBox.style.display !== "none") {
      renderSyncQueueDetailList(items);
    }
  });
}

// Renderizar lista detalhada de itens com erros e status
function renderSyncQueueDetailList(items) {
  const listContainer = document.getElementById("sync-queue-items-list");
  listContainer.innerHTML = "";
  
  if (items.length === 0) {
    listContainer.innerHTML = "<p style='color: var(--text-secondary);'>Fila vazia.</p>";
    return;
  }
  
  items.forEach(item => {
    const div = document.createElement("div");
    div.style.padding = "0.35rem";
    div.style.borderBottom = "1px solid var(--border-color)";
    div.style.display = "flex";
    div.style.justifyContent = "space-between";
    div.style.alignItems = "center";
    
    const errText = item.error_message ? `<span style="color: var(--red-alert);"> - ${escapeHtml(item.error_message)}</span>` : "";
    div.innerHTML = `
      <div>
        <strong>${escapeHtml(item.type.toUpperCase())}</strong> 
        <span style="color: var(--text-secondary);">[${escapeHtml(item.status)}]</span>
        ${errText}
      </div>
    `;
    listContainer.appendChild(div);
  });
}

// Toggle da fila detalhada
document.getElementById("sync-queue-count").addEventListener("click", () => {
  const box = document.getElementById("sync-queue-detail-card");
  if (box.style.display === "none") {
    box.style.display = "flex";
    getAllLocal("sync_queue").then(renderSyncQueueDetailList);
  } else {
    box.style.display = "none";
  }
});

// Forçar Reenvio Manual
document.getElementById("btn-force-sync").addEventListener("click", () => {
  // Resetar status de Failed para Pending para re-tentar
  getAllLocal("sync_queue").then(items => {
    const promises = items.map(item => {
      if (item.status === "Failed" || item.status === "Pending") {
        item.status = "Pending";
        item.error_message = null;
        return saveLocal("sync_queue", item);
      }
      return Promise.resolve();
    });
    Promise.all(promises).then(() => {
      updateUIQueueCount();
      processSyncQueue();
    });
  });
});

// Monitor de rede
window.addEventListener("online", () => {
  const badge = document.getElementById("sync-badge");
  badge.textContent = "Online";
  badge.className = "status-badge online";
  processSyncQueue();
});

window.addEventListener("offline", () => {
  const badge = document.getElementById("sync-badge");
  badge.textContent = "Offline";
  badge.className = "status-badge offline";
});

// Processamento da fila de sincronização
async function processSyncQueue() {
  if (!navigator.onLine) return;
  const token = localStorage.getItem("mobile_auth_token");
  if (!token) return;

  try {
    const queue = await getAllLocal("sync_queue");
    const pending = queue.filter(x => x.status === "Pending" || x.status === "Failed");

    for (const item of pending) {
      item.status = "Processing";
      await saveLocal("sync_queue", item);

      let url = "";
      let options = {
        method: "POST",
        headers: {
          "Authorization": "Bearer " + token,
          "Content-Type": "application/json"
        }
      };

      if (item.type === "timesheet") {
        url = "/api/v1/mobile/timesheets.php";
        options.body = JSON.stringify({
          ...item.payload,
          sync_status: "Synced"
        });
      } else if (item.type === "location") {
        url = "/api/v1/mobile/location.php";
        options.body = JSON.stringify({
          locations: [item.payload] // Envia em lote
        });
      } else if (item.type === "checklist_save") {
        url = "/api/v1/mobile/checklists.php?action=save";
        options.body = JSON.stringify({
          items: item.payload
        });
      } else if (item.type === "signature") {
        url = "/api/v1/mobile/signatures.php";
        options.body = JSON.stringify(item.payload);
      } else if (item.type === "photo") {
        url = "/api/v1/mobile/photos.php";
        // As fotos usam Multipart FormData, então convertemos de volta de base64
        const formData = new FormData();
        formData.append("project_id", item.payload.project_id);
        formData.append("photo_type", item.payload.photo_type);
        formData.append("description", item.payload.description || "");
        formData.append("client_uuid", item.client_uuid);
        formData.append("created_offline_at", item.created_offline_at);

        // Convert base64 back to Blob
        const blob = dataURItoBlob(item.payload.image_base64);
        formData.append("file", blob, "photo_" + item.client_uuid + ".jpg");

        delete options.headers["Content-Type"]; // browser define boundary
        options.body = formData;
      }

      try {
        const res = await fetch(url, options);
        const resJson = await res.json();
        if (resJson.success) {
          await deleteLocal("sync_queue", item.id);
        } else {
          item.status = "Failed";
          item.error_message = resJson.error ? resJson.error.message : "Erreur serveur";
          await saveLocal("sync_queue", item);
        }
      } catch (err) {
        item.status = "Failed";
        item.error_message = "Erreur de réseau";
        await saveLocal("sync_queue", item);
      }
    }

    document.getElementById("last-sync-time").textContent = "Dernière synchro: " + new Date().toLocaleTimeString();
    updateUIQueueCount();
  } catch (e) {
    console.error("Sync process failure:", e);
  }
}

// -------------------------------------------------------------
// Gerenciamento de Visualização (Navegação SPA)
// -------------------------------------------------------------
function showView(viewId) {
  document.querySelectorAll(".view").forEach(v => v.classList.remove("active"));
  document.getElementById(viewId).classList.add("active");
  window.scrollTo(0, 0);

  const mobileNav = document.getElementById("mobile-nav");
  if (mobileNav) {
    if (viewId === "view-login") {
      mobileNav.style.display = "none";
    } else {
      mobileNav.style.display = "flex";
    }
  }
  
  if (typeof applyTranslations === "function") {
    applyTranslations();
  }
}

// -------------------------------------------------------------
// Fluxo de Negócio e APIs
// -------------------------------------------------------------

// Login
document.getElementById("btn-login").addEventListener("click", async () => {
  const email = document.getElementById("login-email").value.trim();
  const password = document.getElementById("login-password").value;

  if (!email || !password) {
    alert("Veuillez saisir votre e-mail et mot de passe.");
    return;
  }

  try {
    const res = await fetch("/api/v1/mobile/team.php?action=login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password, device_name: "App Mobile PWA" })
    });
    const data = await res.json();

    if (data.success) {
      localStorage.setItem("mobile_auth_token", data.data.token);
      localStorage.setItem("mobile_user_name", data.data.user.name);
      localStorage.setItem("mobile_user_role", data.data.user.role);
      
      navigateToTab("dashboard");
      loadProjects();
      
      // Iniciar sincronizador periódico
      if (!syncIntervalId) {
        syncIntervalId = setInterval(processSyncQueue, 30000);
      }
    } else {
      alert("Erreur de connexion: " + data.error.message);
    }
  } catch (err) {
    alert("Une erreur s'est produite lors de la connexion. Veuillez vérifier votre réseau.");
  }
});

// Logout
document.getElementById("btn-logout").addEventListener("click", async () => {
  localStorage.removeItem("mobile_auth_token");
  localStorage.removeItem("mobile_user_name");
  localStorage.removeItem("mobile_user_role");
  clearInterval(syncIntervalId);
  clearInterval(gpsIntervalId);
  syncIntervalId = null;
  gpsIntervalId = null;
  stopGeofenceCheck();
  
  // Limpar dados sensíveis do IndexedDB no logout
  try {
    await clearIndexedDBStore("projects");
    await clearIndexedDBStore("checklists");
  } catch (e) {
    console.error("Failed to clear indexedDB on logout", e);
  }
  
  showView("view-login");
});

// Limpar Dados deste Dispositivo (Botão manual)
document.getElementById("btn-clear-device").addEventListener("click", async () => {
  if (confirm("Voulez-vous vraiment effacer TOUTES les données de cet appareil ? Les éléments non synchronisés seront perdus.")) {
    localStorage.clear();
    clearInterval(syncIntervalId);
    clearInterval(gpsIntervalId);
    syncIntervalId = null;
    gpsIntervalId = null;
    
    try {
      await clearIndexedDBStore("projects");
      await clearIndexedDBStore("checklists");
      await clearIndexedDBStore("sync_queue");
      updateUIQueueCount();
    } catch (e) {
      console.error("Failed to clear all indexedDB stores", e);
    }
    
    alert("Toutes les données de l'appareil ont été effacées.");
    showView("view-login");
  }
});

function clearIndexedDBStore(storeName) {
  return new Promise((resolve, reject) => {
    if (!db) return resolve();
    const transaction = db.transaction(storeName, "readwrite");
    const store = transaction.objectStore(storeName);
    const request = store.clear();
    request.onsuccess = () => resolve(true);
    request.onerror = (e) => reject(e.target.error);
  });
}


// Carregar Lista de Projetos Atribuídos
async function loadProjects() {
  const token = localStorage.getItem("mobile_auth_token");
  if (!token) return;

  const container = document.getElementById("projects-list-container");
  container.innerHTML = "<p style='color: var(--text-secondary); text-align: center;'>Chargement des services...</p>";

  // Populate Welcome Panel (Guarded dynamically)
  const welcomeNameEl = document.getElementById("welcome-name");
  if (welcomeNameEl) {
    const userName = localStorage.getItem("mobile_user_name") || "Opérateur";
    welcomeNameEl.innerHTML = `<span class="i18n-text" data-i18n="welcome_prefix">${t("welcome_prefix")}</span>${userName}`;
  }
  
  const now = new Date();
  const locale = currentLang === 'pt' ? 'pt-BR' : 'fr-FR';
  
  const welcomeDateEl = document.getElementById("welcome-date");
  if (welcomeDateEl) {
    welcomeDateEl.textContent = now.toLocaleDateString(locale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
  }
  
  // Dynamic clock
  const timeSpan = document.getElementById("welcome-time");
  if (timeSpan) {
    timeSpan.textContent = now.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' });
    const timeInterval = setInterval(() => {
      const el = document.getElementById("welcome-time");
      if (!el) {
        clearInterval(timeInterval);
        return;
      }
      el.textContent = new Date().toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' });
    }, 1000);
  }

  let assignments = [];

  // Tenta carregar online
  try {
    const res = await fetch("/api/v1/mobile/team.php?action=assignments", {
      headers: { "Authorization": "Bearer " + token }
    });
    const data = await res.json();

    if (data.success) {
      assignments = data.data.assignments || [];
      for (const p of assignments) {
        // Grava no IndexedDB cache local
        await saveLocal("projects", p);
      }
    }
  } catch (e) {
    console.log("Offline mode - loading projects from cache");
    assignments = await getAllLocal("projects");
  }

  container.innerHTML = "";
  if (assignments.length === 0) {
    renderEmptyState(container);
    
    // Reset Today Summary
    const elAssigned = document.getElementById("summary-assigned");
    if (elAssigned) elAssigned.textContent = "0";
    const elCompleted = document.getElementById("summary-completed");
    if (elCompleted) elCompleted.textContent = "0";
    const elHours = document.getElementById("summary-hours");
    if (elHours) elHours.textContent = "0.0h";
    
    // Prochaine mission block -> show no mission, hide placeholders and button
    const nsb = document.getElementById("next-service-box");
    if (nsb) nsb.style.display = "flex";
    const nsc = document.getElementById("next-service-content");
    if (nsc) {
      nsc.innerHTML = `
        <p style="font-size: 1rem; font-weight: 700; color: var(--text-secondary); text-align: center; margin: 1rem 0;" class="i18n-text" data-i18n="no_mission_scheduled">
          ${t('no_mission_scheduled')}
        </p>
      `;
    }
    const bsn = document.getElementById("btn-start-next");
    if (bsn) bsn.style.display = "none";
    const qab = document.getElementById("quick-actions-box");
    if (qab) qab.style.display = "none";
  } else {
    // We have assignments!
    assignments.forEach(renderProjectCard);

    // Calculate metrics
    const totalAssigned = assignments.length;
    const completedServices = assignments.filter(p => p.status === "Terminé" || p.status === "Completed" || p.status === "Signature Recue").length;
    
    // Fetch work logs to calculate hours worked today
    let hoursWorked = 0.0;
    try {
      const syncItems = await getAllLocal("sync_queue");
      const timesheets = syncItems.filter(item => item.type === "timesheet");
      timesheets.forEach(ts => {
        if (ts.payload.start_time && ts.payload.end_time) {
          const start = new Date(`2026-06-20T${ts.payload.start_time}`);
          const end = new Date(`2026-06-20T${ts.payload.end_time}`);
          hoursWorked += Math.max(0, (end - start) / 3600000);
        }
      });
    } catch (e) {
      console.warn("Could not calculate offline hours:", e);
    }

    const pct = totalAssigned > 0 ? Math.round((completedServices / totalAssigned) * 100) : 0;
    const elWT = document.getElementById("progress-worked-time");
    if (elWT) elWT.textContent = hoursWorked.toFixed(1) + "h";
    const elMR = document.getElementById("progress-missions-ratio");
    if (elMR) elMR.textContent = `${completedServices}/${totalAssigned}`;
    const elPB = document.getElementById("dashboard-progress-bar");
    if (elPB) elPB.style.width = pct + "%";

    // Operator Status Card Premium Style updates based on state
    const isCurrentlyWorking = localStorage.getItem("ts_start_time") !== null;
    const statusBox = document.getElementById("operator-status-box");
    const statusTitle = document.getElementById("status-title");
    const statusSubtitle = document.getElementById("status-subtitle");
    const statusIcon = document.getElementById("status-icon");
    const statusIconWrapper = document.getElementById("status-icon-wrapper");
    
    if (isCurrentlyWorking) {
      statusBox.style.background = "linear-gradient(135deg, #0b3c50, #051e2d)";
      if (statusTitle) statusTitle.textContent = t('status_on_mission');
      if (statusSubtitle) statusSubtitle.textContent = t('status_on_mission_sub');
      if (statusIcon) statusIcon.className = "fa-solid fa-truck-fast";
      if (statusIconWrapper) statusIconWrapper.style.color = "#007a87";
    } else {
      const savedStatus = localStorage.getItem("operator_status") || "Available";
      if (savedStatus === "Available") {
        statusBox.style.background = "linear-gradient(135deg, #0b3c50, #051e2d)";
        if (statusTitle) statusTitle.textContent = t('status_available');
        if (statusSubtitle) statusSubtitle.textContent = t('status_available_sub');
        if (statusIcon) statusIcon.className = "fa-solid fa-check";
        if (statusIconWrapper) statusIconWrapper.style.color = "#007a87";
      } else if (savedStatus === "Pause") {
        statusBox.style.background = "linear-gradient(135deg, #F59E0B, #D97706)";
        if (statusTitle) statusTitle.textContent = t('status_pause');
        if (statusSubtitle) statusSubtitle.textContent = t('status_pause_sub');
        if (statusIcon) statusIcon.className = "fa-solid fa-coffee";
        if (statusIconWrapper) statusIconWrapper.style.color = "#F59E0B";
      } else {
        statusBox.style.background = "linear-gradient(135deg, #64748B, #475569)";
        if (statusTitle) statusTitle.textContent = t('status_offline');
        if (statusSubtitle) statusSubtitle.textContent = t('status_offline_sub');
        if (statusIcon) statusIcon.className = "fa-solid fa-moon";
        if (statusIconWrapper) statusIconWrapper.style.color = "#64748B";
      }
    }

    // Next assigned service logic
    const nextService = assignments.find(p => p.status !== "Terminé" && p.status !== "Completed" && p.status !== "Signature Recue");
    
    // Bind current mission slate card
    const activeProjId = localStorage.getItem("active_project_id");
    const currentMission = assignments.find(p => p.id === Number(activeProjId)) || nextService;
    
    if (currentMission) {
      const amb = document.getElementById("active-mission-box"); if (amb) amb.style.display = "flex";
      const ama = document.getElementById("active-mission-actions"); if (ama) ama.style.display = "grid";
      const amc = document.getElementById("active-mission-client"); if (amc) amc.textContent = currentMission.project_type || currentMission.name || t('default_mission_type');
      const amadd = document.getElementById("active-mission-address"); if (amadd) amadd.innerHTML = '<i class="fa-solid fa-location-dot" style="margin-right: 6px;"></i> ' + escapeHtml(currentMission.description || currentMission.client_address || t('address_unspecified'));
      const amt = document.getElementById("active-mission-time"); if (amt) amt.innerHTML = '<i class="fa-solid fa-clock" style="margin-right: 6px;"></i> ' + escapeHtml(currentMission.start_date || t('today'));
      const amcode = document.getElementById("active-mission-code"); if (amcode) amcode.innerHTML = '<i class="fa-solid fa-user" style="margin-right: 6px;"></i> ' + t('client_prefix') + escapeHtml(currentMission.client_name || "Client");
      
      const bdsm = document.getElementById("btn-dashboard-start-mission");
      if (bdsm) bdsm.onclick = () => {
        openProjectDetail(currentMission.id);
      };
      const bag = document.getElementById("btn-active-gps");
      if (bag) bag.onclick = () => {
        openProjectDetail(currentMission.id).then(() => {
          document.getElementById("btn-route-gps").click();
        });
      };
      const bac = document.getElementById("btn-active-call");
      if (bac) bac.onclick = () => {
        if (currentMission.client_phone) {
          window.location.href = "tel:" + currentMission.client_phone;
        } else {
          alert("Numéro de téléphone non renseigné pour ce client.");
        }
      };
    } else {
      const amb2 = document.getElementById("active-mission-box"); if (amb2) amb2.style.display = "none";
      const ama2 = document.getElementById("active-mission-actions"); if (ama2) ama2.style.display = "none";
    }

    if (nextService) {
      const nsb2 = document.getElementById("next-service-box"); if (nsb2) nsb2.style.display = "flex";
      const nt = document.getElementById("next-time"); if (nt) nt.textContent = nextService.start_date ? nextService.start_date.split(" - ")[0] : t('today');
      const nc = document.getElementById("next-client"); if (nc) nc.textContent = nextService.project_type || nextService.name || t('default_mission_type');
      const nadd = document.getElementById("next-address"); if (nadd) nadd.textContent = nextService.description || nextService.client_address || t('address_unspecified');
      
      const nsb3 = document.getElementById("next-service-box");
      if (nsb3) nsb3.onclick = () => {
        openProjectDetail(nextService.id);
      };

      // Show and configure Quick Actions
      const qab2 = document.getElementById("quick-actions-box"); if (qab2) qab2.style.display = "flex";
      
      const bqc = document.getElementById("btn-quick-checklist");
      if (bqc) bqc.onclick = () => {
        openProjectDetail(nextService.id).then(() => {
          navigateToTab("checklist");
        });
      };
      
      const bqp = document.getElementById("btn-quick-photos");
      if (bqp) bqp.onclick = () => {
        openProjectDetail(nextService.id).then(() => {
          navigateToTab("photos");
        });
      };

      const bqi = document.getElementById("btn-quick-incidents");
      if (bqi) bqi.onclick = () => {
        openProjectDetail(nextService.id).then(() => {
          showView("view-incidents");
        });
      };

      const bqs = document.getElementById("btn-quick-signature");
      if (bqs) bqs.onclick = () => {
        openProjectDetail(nextService.id).then(() => {
          showView("view-signature");
        });
      };
    } else {
      const nsb4 = document.getElementById("next-service-box"); if (nsb4) nsb4.style.display = "none";
      const qab3 = document.getElementById("quick-actions-box"); if (qab3) qab3.style.display = "none";
    }
  }
}

function renderEmptyState(container) {
  container.innerHTML = `
    <div class="empty-state-card" style="margin: 0; padding: 1.5rem 1rem;">
      <i class="fa-solid fa-list-check" style="font-size: 2.5rem; color: var(--teal-primary); margin-bottom: 0.5rem;"></i>
      <h3 data-i18n="empty_state_title">Aucun service attribué</h3>
      <p style="font-size: 0.9rem; margin-bottom: 1.5rem; color: var(--text-secondary);" data-i18n="empty_state_desc">Votre planning est vide pour le moment.</p>
      
      <div style="width: 100%; display: flex; flex-direction: column; gap: 0.5rem;">
        <button class="secondary" style="width: 100%; justify-content: flex-start; font-size: 0.9rem; padding: 0.75rem 1rem;" onclick="alert(t('schedule_offline'))">
          <i class="fa-regular fa-calendar-days" style="margin-right: 8px; color: var(--teal-primary);"></i> <span class="i18n-text" data-i18n="view_schedule">Consulter mon planning</span>
        </button>
        <button class="secondary" style="width: 100%; justify-content: flex-start; font-size: 0.9rem; padding: 0.75rem 1rem;" onclick="alert(t('history_empty'))">
          <i class="fa-solid fa-receipt" style="margin-right: 8px; color: var(--teal-primary);"></i> <span class="i18n-text" data-i18n="view_history">Voir mon histórico</span>
        </button>
        <button class="secondary" style="width: 100%; justify-content: flex-start; font-size: 0.9rem; padding: 0.75rem 1rem;" id="btn-empty-sync">
          <i class="fa-solid fa-arrows-rotate" style="margin-right: 8px; color: var(--teal-primary);"></i> <span class="i18n-text" data-i18n="verify_sync">Vérifier la synchronisation</span>
        </button>
      </div>

      <button id="btn-refresh-empty" style="width: 100%; margin-top: 1.5rem; padding: 0.75rem;" data-i18n="refresh_btn">
        Actualiser
      </button>
    </div>
  `;
  document.getElementById("btn-refresh-empty").addEventListener("click", loadProjects);
  document.getElementById("btn-empty-sync").addEventListener("click", () => {
    processSyncQueue().then(() => alert(t("sync_verified")));
  });
  applyTranslations();
}

function renderProjectCard(p) {
  const container = document.getElementById("projects-list-container");
  const div = document.createElement("div");
  div.style.cursor = "pointer";
  
  const clientName = escapeHtml(p.client_name || p.name || 'Client Inconnu');
  const projectCode = escapeHtml(p.project_code || '-');
  const address = escapeHtml(p.description || p.client_address || 'Adresse non spécifiée');
  const dateStr = escapeHtml(p.start_date || '-');
  const statusStr = escapeHtml(p.status || 'Pendente');

  // Extract times dynamically if available
  let startTime = "09:30";
  let endTime = "12:30";
  if (p.start_date && p.start_date.includes("-")) {
    const parts = p.start_date.split("-");
    const startPart = parts[0].trim().split(" ").pop();
    const endPart = parts[1].trim().split(" ").shift();
    if (startPart && startPart.includes(":")) startTime = startPart;
    if (endPart && endPart.includes(":")) endTime = endPart;
  } else if (p.start_time) {
    startTime = p.start_time.substring(0, 5);
    if (p.end_time) endTime = p.end_time.substring(0, 5);
  }

  // Highlight today's or first mission as active-today
  const isActiveToday = (p.status === "Em Andamento" || p.status === "En cours" || p.status === "Iniciada" || container.children.length === 0);
  if (isActiveToday) {
    div.className = "project-card active-today";
  } else {
    div.className = "project-card";
  }

  div.innerHTML = `
    <div class="project-card-layout">
      <div class="project-card-time">
        <span>${startTime}</span>
        <span>${endTime}</span>
      </div>
      <div class="project-card-details-col">
        <h3>${escapeHtml(p.project_type || p.name || 'Limpeza Pós-Obra')}</h3>
        <p><i class="fa-solid fa-location-dot"></i> ${address}</p>
        <p><i class="fa-solid fa-user"></i> ${clientName}</p>
        <span class="status-badge" style="background-color: ${isActiveToday ? 'rgba(255,255,255,0.2)' : '#FEF3C7'}; color: ${isActiveToday ? '#FFFFFF' : '#D97706'}; font-size: 0.72rem; padding: 2px 8px; border-radius: 6px; width: max-content; margin-top: 4px; border: none;">${statusStr}</span>
      </div>
    </div>
    <div class="project-card-footer-link btn-view-detail">
      <span>Ver detalhes</span>
      <i class="fa-solid fa-chevron-right"></i>
    </div>
  `;
  
  div.querySelector(".btn-view-detail").addEventListener("click", () => {
    openProjectDetail(p.id);
  });
  container.appendChild(div);
  applyTranslations();
}

// Detalhes do Projeto Operacional
async function openProjectDetail(projectId) {
  activeProjectId = projectId;
  showView("view-project-detail");

  const token = localStorage.getItem("mobile_auth_token");

  // Reset geofence check state
  stopGeofenceCheck();
  geofenceTargets = null;
  geofenceAlerted = false;
  geofenceClosed = false;
  const banner = document.getElementById("geofence-alert-banner");
  if (banner) banner.style.display = "none";

  // Check active timesheet status
  const activeStart = localStorage.getItem("ts_start_time");
  const activeProj = localStorage.getItem("active_project_id");
  if (activeStart && activeProj === String(projectId)) {
    btnStart.disabled = true;
    btnEnd.disabled = false;
  } else {
    btnStart.disabled = false;
    btnEnd.disabled = true;
  }

  // Limpa canvas de assinatura
  signaturePadClear();

  // Reset inputs de foto
  document.getElementById("photo-desc").value = "";

  // Tenta puxar online
  try {
    const res = await fetch("/api/v1/mobile/projects.php?id=" + projectId, {
      headers: { "Authorization": "Bearer " + token }
    });
    const data = await res.json();
    if (data.success) {
      const p = data.data.project;
      renderProjectDetail(p);
      geofenceTargets = p.geofence_targets || null;
      loadChecklist(projectId);
      if (!btnStart.disabled) {
        startGeofenceCheck();
      }
      return;
    }
  } catch (err) {
    console.log("Loading single project detail from cache");
  }

  // Fallback cache local IndexedDB
  const p = await getLocal("projects", projectId);
  if (p) {
    renderProjectDetail(p);
    geofenceTargets = p.geofence_targets || null;
    loadChecklist(projectId);
    if (!btnStart.disabled) {
      startGeofenceCheck();
    }
  } else {
    alert("Détails non disponibles hors ligne pour ce projet.");
  }
}

function renderProjectDetail(p) {
  document.getElementById("detail-title").textContent = "Service " + p.project_code;
  document.getElementById("client-name").textContent = p.client_name || "Nom du Client";
  document.getElementById("project-code").textContent = "Code: " + p.project_code;
  
  const l_orig = currentLang === 'pt' ? 'Origem:' : 'Origine:';
  const l_dest = currentLang === 'pt' ? 'Destino:' : 'Destination:';
  const l_date = currentLang === 'pt' ? 'Data:' : 'Date:';
  const l_phone = currentLang === 'pt' ? 'Telefone:' : 'Téléphone:';
  
  document.getElementById("origin-address").innerHTML = "<strong>" + l_orig + "</strong> " + escapeHtml(p.description || "-");
  document.getElementById("destination-address").innerHTML = "<strong>" + l_dest + "</strong> " + escapeHtml(p.client_address || "-");
  document.getElementById("service-date").innerHTML = "<strong>" + l_date + "</strong> " + escapeHtml(p.start_date || "-");
  document.getElementById("client-phone").innerHTML = "<strong>" + l_phone + "</strong> <a href='tel:" + escapeHtml(p.client_phone || "") + "' style='color: var(--teal-primary);'>" + escapeHtml(p.client_phone || "-") + "</a>";
}

// -------------------------------------------------------------
// Timesheets Mobile & Check-in/Check-out
// -------------------------------------------------------------
const btnStart = document.getElementById("btn-ts-start");
const btnEnd = document.getElementById("btn-ts-end");

btnStart.addEventListener("click", () => {
  const workDate = new Date().toISOString().split('T')[0];
  const startTime = new Date().toTimeString().split(' ')[0];

  const payload = {
    project_id: activeProjectId,
    work_date: workDate,
    start_time: startTime,
    client_uuid: generateUUID(),
    created_offline_at: new Date().toISOString()
  };

  addToSyncQueue("timesheet", payload);
  btnStart.disabled = true;
  btnEnd.disabled = false;

  // Track running timesheet status globally
  localStorage.setItem("ts_start_time", new Date().toISOString());
  localStorage.setItem("active_project_id", activeProjectId);
  
  // Hide geofence alert banner and stop check
  const banner = document.getElementById("geofence-alert-banner");
  if (banner) banner.style.display = "none";
  stopGeofenceCheck();

  alert("Service Démarré. Les données serão sincronizadas.");
  
  // Ativa localização GPS periódica se ativado
  startGpsTracking();
});

btnEnd.addEventListener("click", () => {
  const workDate = new Date().toISOString().split('T')[0];
  const endTime = new Date().toTimeString().split(' ')[0];

  const payload = {
    project_id: activeProjectId,
    work_date: workDate,
    end_time: endTime,
    client_uuid: generateUUID(),
    created_offline_at: new Date().toISOString()
  };

  addToSyncQueue("timesheet", payload);
  btnStart.disabled = false;
  btnEnd.disabled = true;

  // Clear running timesheet status
  localStorage.removeItem("ts_start_time");
  localStorage.removeItem("active_project_id");
  
  // Stop geofence check
  stopGeofenceCheck();

  alert("Service Terminé. Les données serão sincronizadas.");
  
  // Desativa localização GPS periódica
  stopGpsTracking();
});

// -------------------------------------------------------------
// Geofencing Check-in Alerts
// -------------------------------------------------------------
let geofenceTargets = null;
let geofenceAlerted = false;
let geofenceClosed = false;
let geofenceIntervalId = null;

function calculateHaversine(lat1, lon1, lat2, lon2) {
  const R = 6371000; // Radius of the earth in m
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c; // Distance in meters
}

function checkProximityForGeofence() {
  if (btnStart.disabled || geofenceClosed || !geofenceTargets || !activeProjectId) {
    stopGeofenceCheck();
    return;
  }

  if (!("geolocation" in navigator)) return;

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      
      let closeToOrigin = false;
      let closeToDest = false;
      let usingNpa = false;
      let minDistance = Infinity;

      // 1. Check Origin
      if (geofenceTargets.origin && geofenceTargets.origin.coords) {
        const dist = calculateHaversine(
          lat, lng, 
          geofenceTargets.origin.coords.latitude, 
          geofenceTargets.origin.coords.longitude
        );
        minDistance = Math.min(minDistance, dist);
        const threshold = geofenceTargets.origin.coords.is_approximate ? 1500 : 100;
        if (dist <= threshold) {
          closeToOrigin = true;
          if (geofenceTargets.origin.coords.is_approximate) usingNpa = true;
        }
      }

      // 2. Check Destination
      if (geofenceTargets.destination && geofenceTargets.destination.coords) {
        const dist = calculateHaversine(
          lat, lng, 
          geofenceTargets.destination.coords.latitude, 
          geofenceTargets.destination.coords.longitude
        );
        minDistance = Math.min(minDistance, dist);
        const threshold = geofenceTargets.destination.coords.is_approximate ? 1500 : 100;
        if (dist <= threshold) {
          closeToDest = true;
          if (geofenceTargets.destination.coords.is_approximate) usingNpa = true;
        }
      }

      if (closeToOrigin || closeToDest) {
        if (!geofenceAlerted) {
          geofenceAlerted = true;
          const banner = document.getElementById("geofence-alert-banner");
          const msg = document.getElementById("geofence-alert-message");
          const sub = document.getElementById("geofence-alert-sub");
          
          if (banner && msg && sub) {
            msg.textContent = "Está próximo do serviço. Deseja iniciar o check-in?";
            if (usingNpa) {
              sub.textContent = "Nota: Proximidade estimada por código postal (NPA aproximado).";
            } else {
              sub.textContent = "Proximidade confirmada por GPS (" + Math.round(minDistance) + "m).";
            }
            banner.style.display = "flex";
          }
        }
      } else {
        const banner = document.getElementById("geofence-alert-banner");
        if (banner) banner.style.display = "none";
      }
    },
    (error) => {
      console.warn("Geofence proximity check failed:", error);
    },
    { enableHighAccuracy: true, timeout: 8000 }
  );
}

function startGeofenceCheck() {
  if (geofenceIntervalId) clearInterval(geofenceIntervalId);
  if (btnStart.disabled || geofenceClosed || !geofenceTargets) return;

  checkProximityForGeofence();
  geofenceIntervalId = setInterval(checkProximityForGeofence, 15000); // Check every 15 seconds
}

function stopGeofenceCheck() {
  if (geofenceIntervalId) {
    clearInterval(geofenceIntervalId);
    geofenceIntervalId = null;
  }
}

// -------------------------------------------------------------
// GPS Tracking
// -------------------------------------------------------------
let isGpsActive = false;

function startGpsTracking() {
  if (isGpsActive) return;
  
  if (!("geolocation" in navigator)) {
    alert("La géolocalisation n'est pas supportée par votre navigateur.");
    return;
  }

  // Verificar permissão / Solicitar consentimento inicial
  navigator.geolocation.getCurrentPosition(
    (position) => {
      // Sucesso na primeira leitura
      isGpsActive = true;
      updateGpsUI(true);
      
      // Agenda captura periódica
      gpsIntervalId = setInterval(captureAndSendLocation, 15000);
      captureAndSendLocation(); // primeira leitura imediata
    },
    (error) => {
      let msg = "Accès GPS refusé ou non disponible.";
      if (error.code === error.PERMISSION_DENIED) {
        msg = "Permission GPS refusée. Veuillez activer la localisation dans les réglages de votre navigateur.";
      }
      alert(msg);
      updateGpsUI(false);
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

function stopGpsTracking() {
  if (!isGpsActive) return;
  isGpsActive = false;
  if (gpsIntervalId) {
    clearInterval(gpsIntervalId);
    gpsIntervalId = null;
  }
  updateGpsUI(false);
}

function updateGpsUI(active) {
  const dot = document.getElementById("gps-indicator");
  const label = document.getElementById("gps-toggle-label");
  if (active) {
    dot.className = "gps-dot active";
    dot.setAttribute("title", "GPS Actif");
    if (label) label.textContent = "ON";
  } else {
    dot.className = "gps-dot inactive";
    dot.setAttribute("title", "GPS Inactif");
    if (label) label.textContent = "OFF";
  }
}

// Botão GPS Toggle manual
const btnGpsToggle = document.getElementById("btn-gps-toggle");
if (btnGpsToggle) {
  btnGpsToggle.addEventListener("click", () => {
    if (isGpsActive) {
      stopGpsTracking();
    } else {
      startGpsTracking();
    }
  });
}

function captureAndSendLocation() {
  if (!isGpsActive || !activeProjectId) return;

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const payload = {
        project_id: activeProjectId,
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy || null,
        captured_at: new Date().toISOString()
      };
      
      addToSyncQueue("location", payload);
    },
    (error) => {
      console.warn("GPS tracking error:", error);
    },
    { enableHighAccuracy: true, timeout: 8000 }
  );
}

// -------------------------------------------------------------
// Checklist & Services
// -------------------------------------------------------------
async function loadChecklist(projectId) {
  const container = document.getElementById("checklist-items-container");
  container.innerHTML = "<p style='padding: 1rem; color: var(--text-secondary); text-align: center;'>Chargement de la checklist...</p>";

  const token = localStorage.getItem("mobile_auth_token");

  try {
    const res = await fetch("/api/v1/mobile/checklists.php?project_id=" + projectId, {
      headers: { "Authorization": "Bearer " + token }
    });
    const data = await res.json();
    if (data.success) {
      // Salvar no IndexedDB
      await saveLocal("checklists", { project_id: projectId, items: data.data.checklist });
      renderChecklist(data.data.checklist);
      return;
    }
  } catch (err) {
    console.log("Checklist loaded from local cache");
  }

  // Cache IndexedDB
  const cached = await getLocal("checklists", projectId);
  if (cached) {
    renderChecklist(cached.items);
  } else {
    container.innerHTML = "<p style='padding: 1rem; color: var(--text-secondary); text-align: center;'>Non disponible hors ligne.</p>";
  }
}

function renderChecklist(items) {
  const container = document.getElementById("checklist-items-container");
  container.innerHTML = "";
  if (items.length === 0) {
    container.innerHTML = "<p style='padding: 1rem; color: var(--text-secondary); text-align: center;'>Aucun item.</p>";
    return;
  }

  items.forEach(item => {
    const div = document.createElement("div");
    div.className = "checklist-item";
    div.innerHTML = `
      <label>${escapeHtml(item.item_name)}</label>
      <select class="select-item-status" data-id="${item.id}">
        <option value="Pending" ${item.status === 'Pending' ? 'selected' : ''}>En attente</option>
        <option value="Checked" ${item.status === 'Checked' ? 'selected' : ''}>Conforme</option>
        <option value="Damaged" ${item.status === 'Damaged' ? 'selected' : ''}>Endommagé</option>
        <option value="Missing" ${item.status === 'Missing' ? 'selected' : ''}>Manquant</option>
      </select>
    `;
    container.appendChild(div);
  });

  // Track select status changes
  const updateChecklistProgress = () => {
    const selects = container.querySelectorAll(".select-item-status");
    const total = selects.length;
    if (total === 0) return;
    const completed = Array.from(selects).filter(sel => sel.value !== "Pending").length;
    const percent = Math.round((completed / total) * 100);
    
    document.getElementById("checklist-progress-text").textContent = `${completed} / ${total} concluídos`;
    document.getElementById("checklist-progress-percent").textContent = `${percent}%`;
    document.getElementById("checklist-progress-bar").style.width = `${percent}%`;
  };

  container.addEventListener("change", (e) => {
    if (e.target.classList.contains("select-item-status")) {
      updateChecklistProgress();
    }
  });

  updateChecklistProgress();
}

// Salvar Checklist Modificada
document.getElementById("btn-checklist-save").addEventListener("click", async () => {
  const selects = document.querySelectorAll(".select-item-status");
  const payload = [];

  selects.forEach(sel => {
    payload.push({
      id: parseInt(sel.dataset.id),
      status: sel.value,
      client_uuid: generateUUID(),
      created_offline_at: new Date().toISOString()
    });
  });

  // Atualizar cache local IndexedDB para manter persistência no offline imediato
  const cached = await getLocal("checklists", activeProjectId);
  if (cached) {
    cached.items.forEach(item => {
      const match = payload.find(x => x.id === item.id);
      if (match) item.status = match.status;
    });
    await saveLocal("checklists", cached);
  }

  await addToSyncQueue("checklist_save", payload);
  alert("Modifications enregistrées. Sincronisation en arrière-plan.");
  showView("view-incidents");
});

// -------------------------------------------------------------
// Captura de Fotos de Bens & Incidents Flow
// -------------------------------------------------------------
const fileInput = document.getElementById("camera-file-input");
const btnCapture = document.getElementById("btn-capture-photo");
const btnGalleryAddPhoto = document.getElementById("btn-gallery-add-photo");

let photoUploadContext = "incident"; // 'incident' or 'gallery'
let tempIncidentPhotoBase64 = null;
let selectedPriority = "Medium";
let activeGalleryTab = "post_move";

// Trigger photo capture
if (btnCapture) {
  btnCapture.addEventListener("click", () => {
    photoUploadContext = "incident";
    fileInput.click();
  });
}

if (btnGalleryAddPhoto) {
  btnGalleryAddPhoto.addEventListener("click", () => {
    photoUploadContext = "gallery";
    fileInput.click();
  });
}

// Priority Selector buttons for incidents
document.querySelectorAll("#priority-selector .priority-btn").forEach(btn => {
  btn.addEventListener("click", (e) => {
    e.preventDefault();
    document.querySelectorAll("#priority-selector .priority-btn").forEach(b => {
      b.classList.remove("active");
      b.classList.add("secondary");
      b.style.backgroundColor = "";
      b.style.color = "";
    });
    btn.classList.remove("secondary");
    btn.classList.add("active");
    selectedPriority = btn.dataset.priority;
    if (selectedPriority === "Low") {
      btn.style.backgroundColor = "var(--teal-primary)";
      btn.style.color = "white";
    } else if (selectedPriority === "Medium") {
      btn.style.backgroundColor = "#f59e0b";
      btn.style.color = "white";
    } else if (selectedPriority === "High") {
      btn.style.backgroundColor = "var(--red-alert)";
      btn.style.color = "white";
    }
  });
});

// Image file input change handler
fileInput.addEventListener("change", (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(evt) {
    const img = new Image();
    img.onload = function() {
      // Resize to max 1280px width
      let width = img.width;
      let height = img.height;
      const maxWidth = 1280;

      if (width > maxWidth) {
        height = Math.round((height * maxWidth) / width);
        width = maxWidth;
      }

      const tempCanvas = document.createElement("canvas");
      tempCanvas.width = width;
      tempCanvas.height = height;
      const tempCtx = tempCanvas.getContext("2d");
      tempCtx.drawImage(img, 0, 0, width, height);

      const compressedBase64 = tempCanvas.toDataURL("image/jpeg", 0.7);

      if (photoUploadContext === "incident") {
        tempIncidentPhotoBase64 = compressedBase64;
        const container = document.getElementById("uploaded-photos-container");
        container.innerHTML = `
          <div style="position: relative; width: 80px; height: 80px;">
            <img src="${compressedBase64}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
            <button id="btn-delete-incident-photo" style="position: absolute; top: -5px; right: -5px; background: var(--red-alert); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 10px; display: flex; align-items: center; justify-content: center; padding: 0; cursor: pointer;">&times;</button>
          </div>
        `;
        document.getElementById("btn-delete-incident-photo").onclick = (ev) => {
          ev.preventDefault();
          tempIncidentPhotoBase64 = null;
          container.innerHTML = "";
        };
      } else {
        // Gallery mode
        const payload = {
          project_id: activeProjectId,
          photo_type: activeGalleryTab,
          description: "Photo de la mission",
          image_base64: compressedBase64,
          client_uuid: generateUUID(),
          created_offline_at: new Date().toISOString()
        };
        addToSyncQueue("photo", payload).then(() => {
          alert("Photo ajoutée aux galeries de la mission.");
          renderPhotosGallery();
        });
      }
    };
    img.src = evt.target.result;
  };
  reader.readAsDataURL(file);
});

// Incidents form buttons
const btnSubmitIncident = document.getElementById("btn-submit-incident");
if (btnSubmitIncident) {
  btnSubmitIncident.addEventListener("click", () => {
    const desc = document.getElementById("photo-desc").value.trim();
    if (!tempIncidentPhotoBase64) {
      alert("Veuillez ajouter une photo de l'occurrence.");
      return;
    }

    const payload = {
      project_id: activeProjectId,
      photo_type: "incident",
      description: `[Priorité: ${selectedPriority}] ${desc || "Aucune description"}`,
      image_base64: tempIncidentPhotoBase64,
      client_uuid: generateUUID(),
      created_offline_at: new Date().toISOString()
    };

    addToSyncQueue("photo", payload).then(() => {
      alert("Occurrence enregistrée avec succès.");
      // Clear form
      document.getElementById("photo-desc").value = "";
      tempIncidentPhotoBase64 = null;
      document.getElementById("uploaded-photos-container").innerHTML = "";
      showView("view-photos");
      renderPhotosGallery();
    });
  });
}

const btnSkipIncident = document.getElementById("btn-skip-incident");
if (btnSkipIncident) {
  btnSkipIncident.addEventListener("click", () => {
    // Clear form
    document.getElementById("photo-desc").value = "";
    tempIncidentPhotoBase64 = null;
    const container = document.getElementById("uploaded-photos-container");
    if (container) container.innerHTML = "";
    showView("view-photos");
    renderPhotosGallery();
  });
}

// -------------------------------------------------------------
// Photos Gallery Segmented Tabs
// -------------------------------------------------------------
document.querySelectorAll("#photos-segmented-tabs .segmented-tab").forEach(tab => {
  tab.addEventListener("click", (e) => {
    e.preventDefault();
    document.querySelectorAll("#photos-segmented-tabs .segmented-tab").forEach(t => {
      t.classList.remove("active");
      t.classList.add("secondary");
      t.style.backgroundColor = "";
      t.style.color = "";
    });
    tab.classList.remove("secondary");
    tab.classList.add("active");
    tab.style.backgroundColor = "var(--teal-primary)";
    tab.style.color = "white";
    activeGalleryTab = tab.dataset.tab;
    renderPhotosGallery();
  });
});

async function renderPhotosGallery() {
  const grid = document.getElementById("gallery-photos-grid");
  if (!grid) return;
  grid.innerHTML = "";

  const syncItems = await getAllLocal("sync_queue");
  const projectPhotos = syncItems.filter(item => 
    item.type === "photo" && 
    item.payload.project_id === activeProjectId && 
    item.payload.photo_type === activeGalleryTab
  );

  if (projectPhotos.length === 0) {
    grid.innerHTML = "<p style='grid-column: span 3; text-align: center; color: var(--text-secondary); font-size: 0.85rem; padding: 1rem 0;'>Aucune photo dans cette catégorie.</p>";
    return;
  }

  projectPhotos.forEach(item => {
    const div = document.createElement("div");
    div.style.position = "relative";
    div.style.width = "100%";
    div.style.paddingTop = "100%"; // 1:1 ratio
    div.style.borderRadius = "8px";
    div.style.overflow = "hidden";
    div.style.border = "1px solid var(--border-color)";

    const img = document.createElement("img");
    img.src = item.payload.image_base64;
    img.style.position = "absolute";
    img.style.top = "0";
    img.style.left = "0";
    img.style.width = "100%";
    img.style.height = "100%";
    img.style.objectFit = "cover";

    const delBtn = document.createElement("button");
    delBtn.innerHTML = "&times;";
    delBtn.style.position = "absolute";
    delBtn.style.top = "4px";
    delBtn.style.right = "4px";
    delBtn.style.backgroundColor = "rgba(239, 68, 68, 0.9)";
    delBtn.style.color = "white";
    delBtn.style.border = "none";
    delBtn.style.borderRadius = "50%";
    delBtn.style.width = "20px";
    delBtn.style.height = "20px";
    delBtn.style.display = "flex";
    delBtn.style.alignItems = "center";
    delBtn.style.justifyContent = "center";
    delBtn.style.cursor = "pointer";
    delBtn.style.fontSize = "12px";
    delBtn.onclick = (e) => {
      e.preventDefault();
      deleteLocal("sync_queue", item.id).then(() => {
        updateUIQueueCount();
        renderPhotosGallery();
      });
    };

    div.appendChild(img);
    div.appendChild(delBtn);
    grid.appendChild(div);
  });
}

const btnPhotosContinue = document.getElementById("btn-photos-continue");
if (btnPhotosContinue) {
  btnPhotosContinue.addEventListener("click", () => {
    showView("view-signature");
    setTimeout(resizeCanvas, 100);
  });
}

// -------------------------------------------------------------
// Signature Canvas & Submit
// -------------------------------------------------------------
const canvas = document.getElementById("signature-canvas");
const ctx = canvas.getContext("2d");
let drawing = false;

function resizeCanvas() {
  if (!canvas) return;
  canvas.width = canvas.parentElement.clientWidth;
  canvas.height = canvas.parentElement.clientHeight;
  ctx.strokeStyle = "#111827";
  ctx.lineWidth = 3;
}

window.addEventListener("resize", resizeCanvas);

// Touch support
canvas.addEventListener("touchstart", (e) => {
  drawing = true;
  const touch = e.touches[0];
  const rect = canvas.getBoundingClientRect();
  ctx.beginPath();
  ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
  e.preventDefault();
});

canvas.addEventListener("touchmove", (e) => {
  if (!drawing) return;
  const touch = e.touches[0];
  const rect = canvas.getBoundingClientRect();
  ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
  ctx.stroke();
  e.preventDefault();
});

canvas.addEventListener("touchend", () => {
  drawing = false;
});

// Mouse support
canvas.addEventListener("mousedown", (e) => {
  drawing = true;
  const rect = canvas.getBoundingClientRect();
  ctx.beginPath();
  ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
});

canvas.addEventListener("mousemove", (e) => {
  if (!drawing) return;
  const rect = canvas.getBoundingClientRect();
  ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
  ctx.stroke();
});

canvas.addEventListener("mouseup", () => {
  drawing = false;
});

function signaturePadClear() {
  if (ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
}

document.getElementById("btn-signature-clear").addEventListener("click", signaturePadClear);

document.getElementById("btn-signature-submit").addEventListener("click", () => {
  const clientName = document.getElementById("signature-client-name").value.trim();
  if (emptyStringCheck(clientName)) {
    alert("Veuillez saisir le nome completo do client.");
    return;
  }

  const dataURL = canvas.toDataURL("image/png");
  if (dataURL === "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" || dataURL.length < 1000) {
    alert("Veuillez dessiner la signature du client.");
    return;
  }

  const payload = {
    project_id: activeProjectId,
    client_name: clientName,
    signature_data: dataURL,
    client_uuid: generateUUID(),
    created_offline_at: new Date().toISOString()
  };

  addToSyncQueue("signature", payload).then(() => {
    alert("Livraison confirmée. Assinatura salva.");
    signaturePadClear();
    showView("view-summary");
    renderSummaryScreen();
  });
});

// -------------------------------------------------------------
// Summary Screen logic
// -------------------------------------------------------------
async function renderSummaryScreen() {
  const proj = await getLocal("projects", activeProjectId);
  if (proj) {
    document.getElementById("summary-mission-title").textContent = proj.client_name || proj.name || "Mudança Residencial";
  }

  // Checklist completed status
  const checklist = await getLocal("checklists", activeProjectId);
  const checklistConcluded = document.getElementById("summary-checklist-concluded");
  if (checklist && checklist.items) {
    const total = checklist.items.length;
    const completed = checklist.items.filter(x => x.status !== "Pending").length;
    checklistConcluded.textContent = `${completed} / ${total} concluídos`;
  } else {
    checklistConcluded.textContent = "0 concluídos";
  }

  // Incidents and Photos status from queue
  const syncItems = await getAllLocal("sync_queue");
  const incidents = syncItems.filter(item => 
    item.type === "photo" && 
    item.payload.project_id === activeProjectId && 
    item.payload.photo_type === "incident"
  );
  document.getElementById("summary-incidents-count").textContent = `${incidents.length} registrada(s)`;

  const photos = syncItems.filter(item => 
    item.type === "photo" && 
    item.payload.project_id === activeProjectId && 
    item.payload.photo_type !== "incident"
  );
  document.getElementById("summary-photos-count").textContent = `${photos.length} fotos enviadas`;

  // Start & duration times
  const activeStart = localStorage.getItem("ts_start_time");
  if (activeStart) {
    const startObj = new Date(activeStart);
    const endObj = new Date();
    document.getElementById("summary-start-time").textContent = startObj.toLocaleTimeString("fr-CH", { hour: '2-digit', minute: '2-digit' }) + " " + startObj.toLocaleDateString("fr-CH");
    document.getElementById("summary-end-time").textContent = endObj.toLocaleTimeString("fr-CH", { hour: '2-digit', minute: '2-digit' }) + " " + endObj.toLocaleDateString("fr-CH");

    const diffMs = endObj - startObj;
    const diffSecs = Math.floor(diffMs / 1000);
    const hrs = Math.floor(diffSecs / 3600);
    const mins = Math.floor((diffSecs % 3600) / 60);
    document.getElementById("summary-total-time").textContent = `${hrs}h ${mins}m`;
  }
}

// Finish mission
const btnFinishMission = document.getElementById("btn-finish-mission");
if (btnFinishMission) {
  btnFinishMission.addEventListener("click", () => {
    btnEnd.click(); // Trigger end timesheet logic
    navigateToTab("dashboard");
  });
}

// -------------------------------------------------------------
// Timesheet timer loop & global controls
// -------------------------------------------------------------
let timerIntervalId = null;

function startTimerLoop() {
  if (timerIntervalId) clearInterval(timerIntervalId);
  timerIntervalId = setInterval(updateTimesheetTimer, 1000);
  updateTimesheetTimer();
}

function stopTimerLoop() {
  if (timerIntervalId) {
    clearInterval(timerIntervalId);
    timerIntervalId = null;
  }
}

function updateTimesheetTimer() {
  const activeStart = localStorage.getItem("ts_start_time");
  const workedDisplay = document.getElementById("timesheet-worked-time");
  const toggleBtn = document.getElementById("btn-timesheet-toggle");
  
  if (activeStart) {
    const elapsedMs = new Date() - new Date(activeStart);
    const totalSecs = Math.floor(elapsedMs / 1000);
    const hrs = Math.floor(totalSecs / 3600);
    const mins = Math.floor((totalSecs % 3600) / 60);
    const secs = totalSecs % 60;
    
    if (workedDisplay) {
      workedDisplay.textContent = `${hrs}h ${String(mins).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`;
    }
    if (toggleBtn) {
      toggleBtn.innerHTML = '<i class="fa-solid fa-square" style="margin-right: 8px;"></i> ' + t('stop_time_btn_ts');
      toggleBtn.style.backgroundColor = "var(--red-alert)";
    }
    
    const startObj = new Date(activeStart);
    document.getElementById("timesheet-start-display").textContent = startObj.toLocaleTimeString("fr-CH", { hour: '2-digit', minute: '2-digit' });
    document.getElementById("timesheet-current-display").textContent = new Date().toLocaleTimeString("fr-CH", { hour: '2-digit', minute: '2-digit' });
  } else {
    if (workedDisplay) {
      workedDisplay.textContent = "0h 00m";
    }
    if (toggleBtn) {
      toggleBtn.innerHTML = '<i class="fa-solid fa-play" style="margin-right: 8px;"></i> ' + t('start_time_btn');
      toggleBtn.style.backgroundColor = "var(--teal-primary)";
    }
    document.getElementById("timesheet-start-display").textContent = "--:--";
    document.getElementById("timesheet-current-display").textContent = "--:--";
  }
}

async function renderTimesheetTimeline() {
  const container = document.getElementById("timesheet-records-timeline");
  if (!container) return;
  container.innerHTML = "";

  const syncItems = await getAllLocal("sync_queue");
  const timesheets = syncItems.filter(item => item.type === "timesheet");
  
  const activeStart = localStorage.getItem("ts_start_time");
  const activeProj = localStorage.getItem("active_project_id");

  let records = [];

  if (activeStart) {
    let projName = "Mission";
    if (activeProj) {
      const pObj = await getLocal("projects", parseInt(activeProj));
      if (pObj) projName = pObj.client_name || pObj.name || "Mission";
    }
    records.push({
      title: projName,
      time: new Date(activeStart).toLocaleTimeString("fr-CH", { hour: '2-digit', minute: '2-digit' }) + " - En cours",
      status: "running"
    });
  }

  timesheets.forEach(ts => {
    const timeStr = ts.payload.start_time ? `${ts.payload.start_time.substring(0, 5)} (Début)` : `${ts.payload.end_time.substring(0, 5)} (Fin)`;
    records.push({
      title: `Mission #${ts.payload.project_id}`,
      time: timeStr,
      status: ts.status
    });
  });

  if (records.length === 0) {
    container.innerHTML = `
      <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; padding: 1.5rem 0; text-align: center;">
        <i class="fa-solid fa-list-check" style="font-size: 3rem; color: #CBD5E1;"></i>
        <h4 style="font-weight: 700; color: var(--text-primary); margin: 0;" data-i18n="no_record_title">${t('no_record_title')}</h4>
        <p style="font-size: 0.82rem; color: var(--text-secondary); margin: 0; max-width: 200px; line-height: 1.4;" data-i18n="no_record_desc">${t('no_record_desc')}</p>
      </div>
    `;
    return;
  }

  records.forEach(rec => {
    const div = document.createElement("div");
    div.style.display = "flex";
    div.style.gap = "0.75rem";
    div.style.alignItems = "center";
    div.style.fontSize = "0.85rem";
    
    let dotColor = "var(--text-secondary)";
    if (rec.status === "running") dotColor = "var(--green-ok)";
    else if (rec.status === "Pending") dotColor = "var(--teal-primary)";

    div.innerHTML = `
      <span style="width: 8px; height: 8px; border-radius: 50%; background-color: ${dotColor}; display: inline-block; flex-shrink: 0;"></span>
      <div style="flex: 1;">
        <p style="margin: 0; font-weight: 600; color: var(--text-primary);">${escapeHtml(rec.title)}</p>
        <p style="margin: 0; font-size: 0.75rem; color: var(--text-secondary);">${escapeHtml(rec.time)}</p>
      </div>
    `;
    container.appendChild(div);
  });
}

// Timesheet toggle click handler
const btnTimesheetToggle = document.getElementById("btn-timesheet-toggle");
if (btnTimesheetToggle) {
  btnTimesheetToggle.addEventListener("click", () => {
    const activeStart = localStorage.getItem("ts_start_time");
    if (activeStart) {
      btnEnd.click(); // End running timesheet
      updateTimesheetTimer();
      renderTimesheetTimeline();
    } else {
      if (activeProjectId) {
        btnStart.click(); // Start active project timesheet
        updateTimesheetTimer();
        renderTimesheetTimeline();
      } else {
        alert("Veuillez d'abord sélectionner une mission dans 'Missões'.");
        navigateToTab("missions");
      }
    }
  });
}

// -------------------------------------------------------------
// Back-button to list
// -------------------------------------------------------------
document.getElementById("btn-back-projects").addEventListener("click", () => {
  stopGeofenceCheck();
  showView("view-missions");
  loadProjects();
});

// GPS details toggle routes
const btnRouteGps = document.getElementById("btn-route-gps");
if (btnRouteGps) {
  btnRouteGps.addEventListener("click", () => {
    if (!activeProjectId) return;
    getLocal("projects", activeProjectId).then(p => {
      if (p) {
        const dest = p.client_address || p.description || "";
        if (dest) {
          window.open("https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(dest), "_blank");
        } else {
          alert("Adresse non disponible.");
        }
      }
    });
  });
}

const btnStartMission = document.getElementById("btn-start-mission");
if (btnStartMission) {
  btnStartMission.addEventListener("click", () => {
    btnStart.click();
    showView("view-checklist");
  });
}

// -------------------------------------------------------------
// Geofencing UI Banner Handlers
// -------------------------------------------------------------
const btnGeofenceCheckin = document.getElementById("btn-geofence-checkin");
if (btnGeofenceCheckin) {
  btnGeofenceCheckin.addEventListener("click", () => {
    const banner = document.getElementById("geofence-alert-banner");
    if (banner) banner.style.display = "none";
    geofenceClosed = true;
    stopGeofenceCheck();
    btnStart.click(); // start timesheet
    showView("view-checklist");
  });
}

const btnCloseGeofenceAlert = document.getElementById("btn-close-geofence-alert");
if (btnCloseGeofenceAlert) {
  btnCloseGeofenceAlert.addEventListener("click", () => {
    const banner = document.getElementById("geofence-alert-banner");
    if (banner) banner.style.display = "none";
    geofenceClosed = true;
    stopGeofenceCheck();
  });
}

// Helpers Utilitários
function generateUUID() {
  return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
    (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
  );
}

function dataURItoBlob(dataURI) {
  const byteString = atob(dataURI.split(',')[1]);
  const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
  const ab = new ArrayBuffer(byteString.length);
  const ia = new Uint8Array(ab);
  for (let i = 0; i < byteString.length; i++) {
    ia[i] = byteString.charCodeAt(i);
  }
  return new Blob([ab], {type: mimeString});
}

function escapeHtml(text) {
  if (!text) return "";
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

function emptyStringCheck(str) {
  return !str || str.trim().length === 0;
}

// -------------------------------------------------------------
// Bottom Navigation Tab Routing (SPA)
// -------------------------------------------------------------
function navigateToTab(target) {
  // Toggle bottom active class
  document.querySelectorAll("#mobile-nav .nav-item").forEach(x => {
    if (x.getAttribute("data-target") === target) {
      x.classList.add("active");
    } else {
      x.classList.remove("active");
    }
  });

  stopTimerLoop();

  if (target === "dashboard") {
    showView("view-dashboard");
    loadProjects();
  } else if (target === "missions") {
    showView("view-missions");
    loadProjects();
  } else if (target === "timesheet") {
    showView("view-timesheet");
    startTimerLoop();
    renderTimesheetTimeline();
  } else if (target === "profile") {
    showView("view-profile");
    // Populate profile names
    const userName = localStorage.getItem("mobile_user_name") || "Carlos Silva";
    const userRole = localStorage.getItem("mobile_user_role") || "Chauffeur / Opérateur";
    document.getElementById("profile-operator-name").textContent = userName;
    document.getElementById("profile-operator-role").textContent = userRole;
    updateUIQueueCount();
  }
}

// Bind Bottom Navigation Clicks
document.querySelectorAll("#mobile-nav .nav-item").forEach(item => {
  item.addEventListener("click", (e) => {
    e.preventDefault();
    const target = item.getAttribute("data-target");
    navigateToTab(target);
  });
});

// -------------------------------------------------------------
// Inicialização do APP
// -------------------------------------------------------------
initDB().then(() => {
  // Initialize status selector click listeners
  document.querySelectorAll("#operator-status-selector .status-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.querySelectorAll("#operator-status-selector .status-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      localStorage.setItem("operator_status", btn.getAttribute("data-status"));
    });
  });
  // Load status from local storage
  const savedStatus = localStorage.getItem("operator_status");
  if (savedStatus) {
    document.querySelectorAll("#operator-status-selector .status-btn").forEach(btn => {
      if (btn.getAttribute("data-status") === savedStatus) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });
  }

  const token = localStorage.getItem("mobile_auth_token");
  if (token) {
    navigateToTab("dashboard");
    loadProjects();
    // Iniciar sincronizador periódico
    syncIntervalId = setInterval(processSyncQueue, 30000);
  } else {
    showView("view-login");
  }
  updateUIQueueCount();
});

window.updateDynamicTranslations = function() {
  loadProjects();
  loadTimesheet();
};
