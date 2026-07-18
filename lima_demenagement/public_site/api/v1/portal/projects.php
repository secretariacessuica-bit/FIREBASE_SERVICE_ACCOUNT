<?php
// LIMA Solutions ERP - Client Portal Premium Projects tracking API V1
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Session authorization verification
if (!isset($_SESSION['client_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé. Veuillez vous connecter.']);
    exit();
}

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

// 2. Validate input and fetch Project details verifying ownership
$projectId = (int)($_GET['project_id'] ?? 0);
if (!$projectId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le champ project_id est requis.']);
    exit();
}

// Fetch project to ensure it belongs to authenticated client
$stmtProj = $pdo->prepare("SELECT * FROM projects WHERE id = :id AND client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1");
$stmtProj->execute(['id' => $projectId, 'client_id' => $clientId, 'company_id' => $companyId]);
$project = $stmtProj->fetch();

if (!$project) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès interdit ou projet inexistant.']);
    exit();
}

$action = $_GET['action'] ?? '';

// Direct authenticated downloads for photos and signatures
$storageDirPhotos = __DIR__ . '/../../../../private_lima/storage/project_photos/';
if (!file_exists($storageDirPhotos)) {
    $storageDirPhotos = __DIR__ . '/../../../../private/storage/project_photos/';
}

$storageDirSignatures = __DIR__ . '/../../../../private_lima/storage/project_signatures/';
if (!file_exists($storageDirSignatures)) {
    $storageDirSignatures = __DIR__ . '/../../../../private/storage/project_signatures/';
}

// Sub-action: download_photo (indirect authenticated download)
if ($action === 'download_photo') {
    $photoId = (int)($_GET['photo_id'] ?? 0);
    if (!$photoId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'photo_id invalide.']);
        exit();
    }
    
    $stmtPhoto = $pdo->prepare("SELECT * FROM project_photos WHERE id = :id AND project_id = :pid AND company_id = :cid LIMIT 1");
    $stmtPhoto->execute(['id' => $photoId, 'pid' => $projectId, 'cid' => $companyId]);
    $photo = $stmtPhoto->fetch();

    if (!$photo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Photo introuvable ou non autorisée.']);
        exit();
    }

    $filePath = $storageDirPhotos . $photo['filename'];
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Fichier introuvable.']);
        exit();
    }

    header('Content-Type: ' . $photo['mime_type']);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . basename($photo['original_name']) . '"');
    readfile($filePath);
    exit();
}

// Sub-action: download_signature (indirect authenticated download)
if ($action === 'download_signature') {
    $sigId = (int)($_GET['sig_id'] ?? 0);
    if (!$sigId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'sig_id invalide.']);
        exit();
    }
    
    $stmtSig = $pdo->prepare("SELECT * FROM project_signatures WHERE id = :id AND project_id = :pid AND company_id = :cid LIMIT 1");
    $stmtSig->execute(['id' => $sigId, 'pid' => $projectId, 'cid' => $companyId]);
    $sig = $stmtSig->fetch();

    if (!$sig) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Signature introuvable ou non autorisée.']);
        exit();
    }

    $filePath = $storageDirSignatures . $sig['signature_path'];
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Fichier de signature introuvable.']);
        exit();
    }

    header('Content-Type: image/png');
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="signature_' . $sigId . '.png"');
    readfile($filePath);
    exit();
}

// Main Actions
switch ($action) {
    case 'tracking':
        // Determine live status: Planeado -> Em Curso -> Em Trânsito -> Concluído
        $status = 'Planeado'; // default
        
        // Check if there is an active timesheet start log
        $stmtTS = $pdo->prepare("SELECT * FROM timesheets WHERE project_id = :pid AND company_id = :cid AND end_time IS NULL ORDER BY start_time DESC LIMIT 1");
        $stmtTS->execute(['pid' => $projectId, 'cid' => $companyId]);
        $activeTimesheet = $stmtTS->fetch();

        if ($project['status'] === 'Completed') {
            $status = 'Concluído';
        } elseif ($activeTimesheet) {
            // Check if any tracking GPS location was captured recently (in last 2 hours) to suggest transit
            $stmtGPS = $pdo->prepare("SELECT count(*) FROM gps_tracking WHERE project_id = :pid AND company_id = :cid AND created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)");
            $stmtGPS->execute(['pid' => $projectId, 'cid' => $companyId]);
            $recentGps = (int)$stmtGPS->fetchColumn();

            if ($recentGps > 0) {
                $status = 'Em Trânsito';
            } else {
                $status = 'Em Curso';
            }
        } elseif ($project['status'] === 'In Progress') {
            $status = 'Em Curso';
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'project_code' => $project['project_code'],
                'project_name' => $project['name'],
                'current_status' => $status,
                'project_system_status' => $project['status']
            ]
        ]);
        break;

    case 'checklist':
        // Fetch checklists from project_checklists
        $stmtCheck = $pdo->prepare("SELECT id, item_name, status, notes, updated_at FROM project_checklists WHERE project_id = :pid AND company_id = :cid ORDER BY item_name ASC");
        $stmtCheck->execute(['pid' => $projectId, 'cid' => $companyId]);
        $items = $stmtCheck->fetchAll();

        $stats = [
            'total' => count($items),
            'pending' => 0,
            'checked' => 0,
            'damaged' => 0,
            'missing' => 0
        ];

        foreach ($items as $it) {
            switch ($it['status']) {
                case 'Pending': $stats['pending']++; break;
                case 'Checked': $stats['checked']++; break;
                case 'Damaged': $stats['damaged']++; break;
                case 'Missing': $stats['missing']++; break;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'items' => $items
            ]
        ]);
        break;

    case 'photos':
        // Fetch photos from project_photos
        // Hide absolute filenames, only expose safe download urls & descriptions
        $stmtPhotos = $pdo->prepare("SELECT id, photo_type, description, created_at FROM project_photos WHERE project_id = :pid AND company_id = :cid ORDER BY created_at DESC");
        $stmtPhotos->execute(['pid' => $projectId, 'cid' => $companyId]);
        $rawPhotos = $stmtPhotos->fetchAll();

        $photos = [];
        foreach ($rawPhotos as $p) {
            // Privacy rule: skip incident photos unless explicitly allowed (e.g. description provided or explicitly approved)
            if ($p['photo_type'] === 'incident' && empty($p['description'])) {
                continue;
            }
            $photos[] = [
                'id' => $p['id'],
                'photo_type' => $p['photo_type'],
                'description' => $p['description'] ?? '',
                'created_at' => $p['created_at'],
                'download_url' => "/api/v1/portal/projects.php?project_id=" . $projectId . "&action=download_photo&photo_id=" . $p['id']
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'photos' => $photos
            ]
        ]);
        break;

    case 'signature':
        // Fetch signature from project_signatures
        $stmtSig = $pdo->prepare("SELECT id, client_name, signed_at FROM project_signatures WHERE project_id = :pid AND company_id = :cid ORDER BY signed_at DESC LIMIT 1");
        $stmtSig->execute(['pid' => $projectId, 'cid' => $companyId]);
        $sig = $stmtSig->fetch();

        if ($sig) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'signature_found' => true,
                    'client_name' => $sig['client_name'],
                    'signed_at' => $sig['signed_at'],
                    'download_url' => "/api/v1/portal/projects.php?project_id=" . $projectId . "&action=download_signature&sig_id=" . $sig['id']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => [
                    'signature_found' => false
                ]
            ]);
        }
        break;

    case 'timeline':
        // Construct timeline list: Sort by date
        $events = [];

        // 1. Creation event
        if (!empty($project['created_at'])) {
            $events[] = [
                'title' => 'Projet créé',
                'description' => 'La planification de votre déménagement a commencé.',
                'date' => $project['created_at'],
                'icon' => 'fa-plus'
            ];
        }

        // 2. Quote acceptance (if quote is linked or accepted)
        $stmtQuote = $pdo->prepare("SELECT accepted_at FROM quotes WHERE client_id = :client_id AND company_id = :cid AND deleted_at IS NULL AND status = 'Accepted' ORDER BY accepted_at ASC LIMIT 1");
        $stmtQuote->execute(['client_id' => $clientId, 'cid' => $companyId]);
        $quoteDate = $stmtQuote->fetchColumn();
        if ($quoteDate) {
            $events[] = [
                'title' => 'Devis accepté',
                'description' => 'Vous avez validé notre proposition commerciale.',
                'date' => $quoteDate,
                'icon' => 'fa-check-double'
            ];
        }

        // 3. Timesheet Start (Service Initiated)
        $stmtTSStart = $pdo->prepare("SELECT start_time, work_date FROM timesheets WHERE project_id = :pid AND company_id = :cid ORDER BY start_time ASC LIMIT 1");
        $stmtTSStart->execute(['pid' => $projectId, 'cid' => $companyId]);
        $tsStart = $stmtTSStart->fetch();
        if ($tsStart) {
            $events[] = [
                'title' => 'Service démarré',
                'description' => 'Notre équipe a entamé le chargement et les opérations de terrain.',
                'date' => $tsStart['work_date'] . ' ' . $tsStart['start_time'],
                'icon' => 'fa-truck-loading'
            ];
        }

        // 4. In Transit (if active timesheet is running and locations exist)
        $stmtLoc = $pdo->prepare("SELECT created_at FROM gps_tracking WHERE project_id = :pid AND company_id = :cid ORDER BY created_at ASC LIMIT 1");
        $stmtLoc->execute(['pid' => $projectId, 'cid' => $companyId]);
        $firstLoc = $stmtLoc->fetchColumn();
        if ($firstLoc) {
            $events[] = [
                'title' => 'En transit',
                'description' => 'Le camion est en route vers l\'adresse de livraison.',
                'date' => $firstLoc,
                'icon' => 'fa-truck'
            ];
        }

        // 5. Timesheet End (Service Completed)
        $stmtTSEnd = $pdo->prepare("SELECT end_time, work_date FROM timesheets WHERE project_id = :pid AND company_id = :cid AND end_time IS NOT NULL ORDER BY end_time DESC LIMIT 1");
        $stmtTSEnd->execute(['pid' => $projectId, 'cid' => $companyId]);
        $tsEnd = $stmtTSEnd->fetch();
        if ($tsEnd) {
            $events[] = [
                'title' => 'Service complété',
                'description' => 'Notre équipe a achevé les opérations de déchargement sur le terrain.',
                'date' => $tsEnd['work_date'] . ' ' . $tsEnd['end_time'],
                'icon' => 'fa-circle-check'
            ];
        }

        // 6. Signature Collected
        $stmtSigTimeline = $pdo->prepare("SELECT signed_at, client_name FROM project_signatures WHERE project_id = :pid AND company_id = :cid ORDER BY signed_at DESC LIMIT 1");
        $stmtSigTimeline->execute(['pid' => $projectId, 'cid' => $companyId]);
        $sigTime = $stmtSigTimeline->fetch();
        if ($sigTime) {
            $events[] = [
                'title' => 'Décharge signée',
                'description' => 'Signature de livraison confirmée par ' . htmlspecialchars($sigTime['client_name']) . '.',
                'date' => $sigTime['signed_at'],
                'icon' => 'fa-signature'
            ];
        }

        // 7. Invoices issued
        $stmtInvs = $pdo->prepare("SELECT created_at, invoice_number, total FROM invoices WHERE client_id = :client_id AND company_id = :cid AND deleted_at IS NULL ORDER BY created_at ASC");
        $stmtInvs->execute(['client_id' => $clientId, 'cid' => $companyId]);
        $invs = $stmtInvs->fetchAll();
        foreach ($invs as $inv) {
            $events[] = [
                'title' => 'Facture émise',
                'description' => 'Facture ' . htmlspecialchars($inv['invoice_number']) . ' générée pour un montant de ' . number_format($inv['total'], 2) . ' CHF.',
                'date' => $inv['created_at'],
                'icon' => 'fa-file-invoice-dollar'
            ];
        }

        // 8. Payments received
        $stmtPays = $pdo->prepare("SELECT p.payment_date, p.amount, i.invoice_number FROM payments p JOIN invoices i ON p.invoice_id = i.id WHERE i.client_id = :client_id AND p.company_id = :cid AND p.deleted_at IS NULL ORDER BY p.payment_date ASC");
        $stmtPays->execute(['client_id' => $clientId, 'cid' => $companyId]);
        $pays = $stmtPays->fetchAll();
        foreach ($pays as $pay) {
            $events[] = [
                'title' => 'Paiement reçu',
                'description' => 'Montant de ' . number_format($pay['amount'], 2) . ' CHF réglé sur la facture ' . htmlspecialchars($pay['invoice_number']) . '.',
                'date' => $pay['payment_date'] . ' 12:00:00', // approximation if time is not recorded
                'icon' => 'fa-receipt'
            ];
        }

        // Sort events chronologically
        usort($events, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        echo json_encode([
            'success' => true,
            'data' => [
                'events' => $events
            ]
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non supportée ou invalide.']);
        break;
}
