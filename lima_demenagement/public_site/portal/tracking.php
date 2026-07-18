<?php
// LIMA Solutions ERP - Premium Tracking Page
require_once 'auth.php';

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

$projectId = (int)($_GET['project_id'] ?? 0);
if (!$projectId) {
    header('Location: index.php');
    exit();
}

// 1. Fetch & Validate Project ownership
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id AND client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1");
$stmt->execute(['id' => $projectId, 'client_id' => $clientId, 'company_id' => $companyId]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: index.php?error=unauthorized_project');
    exit();
}

// Company Info
$stmtComp = $pdo->prepare("SELECT name, main_color FROM companies WHERE id = :id LIMIT 1");
$stmtComp->execute(['id' => $companyId]);
$company = $stmtComp->fetch();
$companyName = $company['name'] ?? 'LIMA Solutions';
$mainColor = $company['main_color'] ?? '#007a87';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Déménagement - <?php echo htmlspecialchars($companyName); ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: <?php echo $mainColor; ?>;
            --primary-light: #e6f2f3;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-gray: #e2e8f0;
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --green-ok: #10b981;
            --yellow-warning: #f59e0b;
            --red-alert: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar navigation */
        .sidebar {
            width: 260px;
            background-color: #0f172a;
            color: var(--white);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition);
        }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand i {
            font-size: 24px;
            color: var(--primary);
        }

        .sidebar-brand h2 {
            font-size: 18px;
            font-weight: 700;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-grow: 1;
        }

        .sidebar-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .sidebar-item a:hover {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.05);
        }

        .sidebar-item.active a {
            color: var(--white);
            background-color: var(--primary);
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
        }

        /* Main Content area */
        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .navbar {
            background-color: var(--white);
            border-bottom: 1px solid var(--border-gray);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 20px;
            font-weight: 700;
        }

        .content-container {
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .card {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Progress Steps Bar */
        .steps-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 2rem 0;
            padding: 0 1rem;
        }

        .steps-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: var(--border-gray);
            z-index: 1;
            transform: translateY(-50%);
        }

        .steps-line-fill {
            position: absolute;
            top: 50%;
            left: 0;
            height: 4px;
            background-color: var(--primary);
            z-index: 2;
            transform: translateY(-50%);
            transition: width 0.5s ease;
            width: 0%;
        }

        .step-item {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .step-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--white);
            border: 3px solid var(--border-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
            color: var(--text-light);
            transition: var(--transition);
        }

        .step-item.active .step-dot {
            border-color: var(--primary);
            background-color: var(--primary-light);
            color: var(--primary);
        }

        .step-item.completed .step-dot {
            border-color: var(--green-ok);
            background-color: var(--green-ok);
            color: var(--white);
        }

        .step-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-light);
        }

        .step-item.active .step-label {
            color: var(--primary);
        }

        .step-item.completed .step-label {
            color: var(--text-dark);
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin-top: 1rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 7px;
            width: 2px;
            background-color: var(--border-gray);
        }

        .timeline-event {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-event::before {
            content: '';
            position: absolute;
            left: -29px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--white);
            border: 3px solid var(--primary);
        }

        .timeline-icon-spot {
            position: absolute;
            left: -33px;
            top: 0px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 11px;
        }

        .timeline-content {
            background-color: var(--bg-light);
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
        }

        .timeline-time {
            font-size: 11px;
            color: var(--text-light);
            font-weight: 500;
            margin-top: 4px;
            display: block;
        }

        /* Checklist boxes */
        .checklist-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
        }

        .checklist-stat-card {
            background-color: var(--bg-light);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--border-gray);
        }

        .checklist-stat-card h4 {
            font-size: 20px;
            font-weight: bold;
        }

        .checklist-stat-card p {
            font-size: 11px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 2px;
        }

        /* Photo Grid */
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 1rem;
        }

        .photo-card {
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            overflow: hidden;
            background-color: var(--bg-light);
            display: flex;
            flex-direction: column;
        }

        .photo-thumb {
            width: 100%;
            height: 100px;
            object-fit: cover;
            cursor: pointer;
        }

        .photo-info {
            padding: 8px;
            font-size: 11px;
            color: var(--text-light);
        }

        /* Modal image preview */
        .img-modal {
            display: none;
            position: fixed;
            z-index: 999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
        }

        .img-modal-content {
            max-width: 90%;
            max-height: 80%;
            border-radius: 8px;
        }

        .img-modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        /* Grid */
        .tracking-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 992px) {
            .tracking-grid {
                grid-template-columns: 2fr 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Left Sidebar Menu -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-cube"></i>
            <h2><?php echo htmlspecialchars($companyName); ?></h2>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
            </li>
            <li class="sidebar-item">
                <a href="quotes.php"><i class="fa-solid fa-file-signature"></i> Mes Devis</a>
            </li>
            <li class="sidebar-item">
                <a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Mes Factures</a>
            </li>
            <li class="sidebar-item">
                <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            </li>
            <li class="sidebar-item">
                <a href="marketplace.php"><i class="fa-solid fa-store"></i> Marketplace</a>
            </li>
            <li class="sidebar-item" style="margin-top: auto;">
                <a href="logout.php" style="color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <!-- Right Content Area -->
    <div class="main-wrapper">
        <header class="navbar">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <a href="index.php" style="color: var(--text-light); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i></a>
                <h1>Suivi en temps réel</h1>
            </div>
            <div style="font-family: monospace; font-size: 0.85rem; padding: 0.25rem 0.5rem; background-color: var(--primary-light); color: var(--primary); border-radius: 6px; font-weight: bold;">
                <?php echo htmlspecialchars($project['project_code']); ?>
            </div>
        </header>

        <main class="content-container">
            <!-- Step Progress Card -->
            <div class="card">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--primary);">État d'avancement de votre déménagement</h3>
                <div class="steps-container">
                    <div class="steps-line"></div>
                    <div id="steps-fill" class="steps-line-fill"></div>
                    
                    <div class="step-item" id="step-planned">
                        <div class="step-dot">1</div>
                        <span class="step-label">Planifié</span>
                    </div>
                    <div class="step-item" id="step-inprogress">
                        <div class="step-dot">2</div>
                        <span class="step-label">En cours</span>
                    </div>
                    <div class="step-item" id="step-transit">
                        <div class="step-dot">3</div>
                        <span class="step-label">En Transit</span>
                    </div>
                    <div class="step-item" id="step-completed">
                        <div class="step-dot">4</div>
                        <span class="step-label">Livré</span>
                    </div>
                </div>
            </div>

            <div class="tracking-grid">
                <!-- Left Column -->
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    
                    <!-- Inventory Checklist -->
                    <div class="card">
                        <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-clipboard-list" style="color: var(--primary); margin-right: 0.5rem;"></i> Inventaire des biens</h3>
                        <div class="checklist-stats">
                            <div class="checklist-stat-card">
                                <h4 id="chk-total">-</h4>
                                <p>Total items</p>
                            </div>
                            <div class="checklist-stat-card" style="border-left: 3px solid var(--yellow-warning);">
                                <h4 id="chk-pending">-</h4>
                                <p>En attente</p>
                            </div>
                            <div class="checklist-stat-card" style="border-left: 3px solid var(--green-ok);">
                                <h4 id="chk-checked">-</h4>
                                <p>Conformes</p>
                            </div>
                            <div class="checklist-stat-card" style="border-left: 3px solid var(--red-alert);">
                                <h4 id="chk-damaged">-</h4>
                                <p>Signalés</p>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Gallery -->
                    <div class="card">
                        <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-images" style="color: var(--primary); margin-right: 0.5rem;"></i> Photos du dossier (Pre-move / Post-move)</h3>
                        <div id="photos-container" class="photo-gallery">
                            <!-- Dynamic images -->
                        </div>
                    </div>

                    <!-- Signatures -->
                    <div class="card" id="signature-card" style="display: none;">
                        <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-signature" style="color: var(--primary); margin-right: 0.5rem;"></i> Preuve de livraison (Signé)</h3>
                        <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; background-color: var(--bg-light); padding: 12px; border-radius: 8px;">
                            <img id="signature-img" src="" alt="Assinatura do Cliente" style="max-height: 80px; background-color: white; border: 1px solid var(--border-gray); padding: 4px; border-radius: 4px;">
                            <div>
                                <p><strong>Signataire :</strong> <span id="signature-name">-</span></p>
                                <p style="font-size: 0.8rem; color: var(--text-light);">Signé le : <span id="signature-date">-</span></p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column (Timeline) -->
                <div>
                    <div class="card" style="position: sticky; top: 20px;">
                        <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary); margin-right: 0.5rem;"></i> Historique opérationnel</h3>
                        <div class="timeline" id="timeline-container">
                            <!-- Dynamic timeline events -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Image preview modal -->
    <div id="image-modal" class="img-modal">
        <span class="img-modal-close" id="modal-close">&times;</span>
        <img class="img-modal-content" id="modal-img" src="">
    </div>

    <script>
        const projectId = <?php echo $projectId; ?>;

        async function fetchTrackingData() {
            try {
                // 1. Fetch tracking status
                const resTrack = await fetch(`/api/v1/portal/projects.php?project_id=${projectId}&action=tracking`);
                const dataTrack = await resTrack.json();
                if (dataTrack.success) {
                    updateStatusSteps(dataTrack.data.current_status);
                }

                // 2. Fetch checklists
                const resChk = await fetch(`/api/v1/portal/projects.php?project_id=${projectId}&action=checklist`);
                const dataChk = await resChk.json();
                if (dataChk.success) {
                    document.getElementById('chk-total').textContent = dataChk.data.stats.total;
                    document.getElementById('chk-pending').textContent = dataChk.data.stats.pending;
                    document.getElementById('chk-checked').textContent = dataChk.data.stats.checked;
                    document.getElementById('chk-damaged').textContent = dataChk.data.stats.damaged;
                }

                // 3. Fetch photos
                const resPhotos = await fetch(`/api/v1/portal/projects.php?project_id=${projectId}&action=photos`);
                const dataPhotos = await resPhotos.json();
                if (dataPhotos.success) {
                    renderPhotos(dataPhotos.data.photos);
                }

                // 4. Fetch signatures
                const resSig = await fetch(`/api/v1/portal/projects.php?project_id=${projectId}&action=signature`);
                const dataSig = await resSig.json();
                if (dataSig.success && dataSig.data.signature_found) {
                    document.getElementById('signature-card').style.display = 'block';
                    document.getElementById('signature-img').src = dataSig.data.download_url;
                    document.getElementById('signature-name').textContent = dataSig.data.client_name;
                    
                    const sDate = new Date(dataSig.data.signed_at);
                    document.getElementById('signature-date').textContent = sDate.toLocaleString('fr-CH');
                }

                // 5. Fetch timeline events
                const resTimeline = await fetch(`/api/v1/portal/projects.php?project_id=${projectId}&action=timeline`);
                const dataTimeline = await resTimeline.json();
                if (dataTimeline.success) {
                    renderTimeline(dataTimeline.data.events);
                }
            } catch (err) {
                console.error("Failed loading tracking info", err);
            }
        }

        function updateStatusSteps(status) {
            const steps = ['planned', 'inprogress', 'transit', 'completed'];
            const stepElems = {
                'planned': document.getElementById('step-planned'),
                'inprogress': document.getElementById('step-inprogress'),
                'transit': document.getElementById('step-transit'),
                'completed': document.getElementById('step-completed')
            };

            // Clear classes
            steps.forEach(s => {
                stepElems[s].className = 'step-item';
            });

            let fillWidth = '0%';
            if (status === 'Planeado') {
                stepElems['planned'].className = 'step-item active';
                fillWidth = '0%';
            } else if (status === 'Em Curso') {
                stepElems['planned'].className = 'step-item completed';
                stepElems['inprogress'].className = 'step-item active';
                fillWidth = '33%';
            } else if (status === 'Em Trânsito') {
                stepElems['planned'].className = 'step-item completed';
                stepElems['inprogress'].className = 'step-item completed';
                stepElems['transit'].className = 'step-item active';
                fillWidth = '66%';
            } else if (status === 'Concluído') {
                stepElems['planned'].className = 'step-item completed';
                stepElems['inprogress'].className = 'step-item completed';
                stepElems['transit'].className = 'step-item completed';
                stepElems['completed'].className = 'step-item completed';
                fillWidth = '100%';
            }

            document.getElementById('steps-fill').style.width = fillWidth;
        }

        function renderPhotos(photos) {
            const container = document.getElementById('photos-container');
            container.innerHTML = '';
            
            if (photos.length === 0) {
                container.innerHTML = '<p style="grid-column: 1/-1; color: var(--text-light); text-align: center; padding: 2rem 0;">Aucune photo disponible pour ce dossier.</p>';
                return;
            }

            photos.forEach(p => {
                const card = document.createElement('div');
                card.className = 'photo-card';
                
                const typeLabel = p.photo_type === 'pre_move' ? 'Avant' : (p.photo_type === 'post_move' ? 'Après' : 'Incident');
                
                card.innerHTML = `
                    <img class="photo-thumb" src="${p.download_url}" alt="Photo" onclick="openPreview('${p.download_url}')">
                    <div class="photo-info">
                        <strong>${typeLabel}</strong>
                        <p style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="${p.description || ''}">${p.description || '-'}</p>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function renderTimeline(events) {
            const container = document.getElementById('timeline-container');
            container.innerHTML = '';

            if (events.length === 0) {
                container.innerHTML = '<p style="color: var(--text-light); padding: 1rem 0;">Aucun évènement enregistré.</p>';
                return;
            }

            events.forEach(e => {
                const div = document.createElement('div');
                div.className = 'timeline-event';
                
                const evDate = new Date(e.date);
                
                div.innerHTML = `
                    <div class="timeline-icon-spot">
                        <i class="fa-solid ${e.icon || 'fa-info'}"></i>
                    </div>
                    <div class="timeline-content">
                        <strong style="display: block; font-size: 13px; font-weight: 700;">${e.title}</strong>
                        <p style="font-size: 12px; color: var(--text-light); margin-top: 2px;">${e.description}</p>
                        <span class="timeline-time">${evDate.toLocaleString('fr-CH')}</span>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        // Image preview modal helpers
        const modal = document.getElementById('image-modal');
        const modalImg = document.getElementById('modal-img');
        const modalClose = document.getElementById('modal-close');

        function openPreview(url) {
            modal.style.display = 'flex';
            modalImg.src = url;
        }

        modalClose.onclick = function() {
            modal.style.display = 'none';
        }

        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Init
        fetchTrackingData();
        // Refresh every 1 minute
        setInterval(fetchTrackingData, 60000);
    </script>
</body>
</html>
