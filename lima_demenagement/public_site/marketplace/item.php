<?php
require_once '../api/v1/config.php';
require_once '../helpers/EmailHelper.php';

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$itemId) {
    header("HTTP/1.1 404 Not Found");
    header("Location: /marketplace/");
    exit();
}

$stmt = $pdo->prepare("SELECT i.*, c.email as seller_email, c.name as seller_name, cat.name as category_name 
    FROM marketplace_items i
    JOIN marketplace_categories cat ON i.category_id = cat.id
    WHERE i.id = :id AND i.status = 'Approved' LIMIT 1");
$stmt->execute(['id' => $itemId]);
$item = $stmt->fetch();

if (!$item) {
    header("HTTP/1.1 404 Not Found");
    header("Location: /marketplace/");
    exit();
}

// Fetch categories for the "Je cherche" alert form
$categories = $pdo->query("SELECT * FROM marketplace_categories ORDER BY name ASC")->fetchAll();

// Fetch photos
$stmtPhotos = $pdo->prepare("SELECT id FROM marketplace_photos WHERE item_id = :item_id");
$stmtPhotos->execute(['item_id' => $itemId]);
$rawPhotos = $stmtPhotos->fetchAll();
$photos = [];
foreach ($rawPhotos as $p) {
    $photos[] = "/api/v1/marketplace/items.php?action=download&photo_id=" . $p['id'];
}
$mainPhoto = count($photos) > 0 ? $photos[0] : 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=600';

// Generate slug for canonical URL
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'item' : $text;
}
$slug = slugify($item['title']);
$canonicalUrl = "https://" . ($_SERVER['HTTP_HOST'] ?? 'limasolutions.ch') . "/marketplace/item/" . $itemId . "-" . $slug;

// SEO fields
$seoTitle = htmlspecialchars($item['title']) . " d'occasion — LIMA Solutions Marketplace";
$seoDesc = htmlspecialchars(mb_strimwidth(strip_tags($item['description']), 0, 160, "..."));

// Condition
$descLower = strtolower($item['description']);
$conditionText = "État usagé";
if (strpos($descLower, 'neuf') !== false || strpos($descLower, 'nouvel') !== false) {
    $conditionText = "Neuf / Comme neuf";
} elseif (strpos($descLower, 'très bon') !== false || strpos($descLower, 'excellent') !== false) {
    $conditionText = "Très bon état";
} elseif (strpos($descLower, 'bon état') !== false) {
    $conditionText = "Bon état";
}

// Calculate waste savings based on category weight or generic estimation
$wasteSavings = 40;
if (strpos(strtolower($item['category_name']), 'table') !== false || strpos(strtolower($item['category_name']), 'armoire') !== false || strpos(strtolower($item['category_name']), 'lit') !== false) {
    $wasteSavings = 80;
} elseif (strpos(strtolower($item['category_name']), 'canap') !== false) {
    $wasteSavings = 120;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $seoTitle; ?></title>
    <meta name="description" content="<?php echo $seoDesc; ?>">
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>">

    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo $seoTitle; ?>">
    <meta property="og:description" content="<?php echo $seoDesc; ?>">
    <meta property="og:image" content="<?php echo "https://" . ($_SERVER['HTTP_HOST'] ?? 'limasolutions.ch') . $mainPhoto; ?>">
    <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
    <meta property="og:type" content="product">

    <!-- Google Fonts & FontAwesome & Ant Design styling simulation -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="/style.css?v=2.2">

    <style>
        /* Ant Design v5 & Premium SaaS Design Tokens */
        :root {
            --primary-teal: #007C89;
            --primary-teal-light: #E6F2F3;
            --primary-teal-dark: #005A63;
            --secondary-green: #0E8B72;
            --secondary-green-light: #EBF8F5;
            --text-dark: #1F2937;
            --text-muted: #6B7280;
            --bg-light: #F8FAFB;
            --white: #FFFFFF;
            --border-gray: #E5E7EB;
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --green-ok: #10B981;
            --green-light: #ECFDF5;
            --red-alert: #EF4444;
            --red-light: #FEF2F2;
            --shadow-premium: 0 4px 16px rgba(0,0,0,0.08);
            --shadow-hover: 0 10px 25px rgba(0,0,0,0.12);
        }

        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 100px 20px 60px;
        }

        /* Breadcrumb Style */
        .breadcrumb-saas {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        .breadcrumb-saas a {
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-saas a:hover {
            color: var(--primary-teal);
        }

        /* 3-Column Grid Layout */
        .marketplace-saas-grid {
            display: grid;
            grid-template-columns: 4fr 3fr 3fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .marketplace-saas-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Premium Ant Design-like Cards */
        .ant-card-premium {
            background: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: var(--shadow-premium);
            margin-bottom: 24px;
            transition: var(--transition);
            position: relative;
        }

        .ant-card-premium:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .ant-card-premium h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-gray);
            padding-bottom: 12px;
        }

        /* Gallery zoom & details */
        .gallery-container {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--border-gray);
        }

        .main-img-zoom {
            width: 100%;
            height: 420px;
            object-fit: cover;
            transition: transform 0.5s ease;
            cursor: zoom-in;
        }

        .gallery-container:hover .main-img-zoom {
            transform: scale(1.08);
        }

        .photo-counter {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.6);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

        .thumb-list-premium {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .thumb-img-premium {
            width: 76px;
            height: 76px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: var(--transition);
        }

        .thumb-img-premium:hover, .thumb-img-premium.active {
            border-color: var(--primary-teal);
            transform: scale(1.04);
        }

        /* Buttons Premium */
        .ant-btn-primary-saas {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            background-color: var(--primary-teal);
            color: var(--white);
            font-weight: 600;
            font-size: 15px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(0, 124, 137, 0.2);
        }

        .ant-btn-primary-saas:hover {
            background-color: var(--primary-teal-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 124, 137, 0.3);
        }

        .ant-btn-secondary-saas {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            background-color: transparent;
            color: var(--primary-teal);
            font-weight: 600;
            font-size: 15px;
            border: 1px solid var(--primary-teal);
            border-radius: 12px;
            cursor: pointer;
            margin-top: 12px;
            transition: var(--transition);
        }

        .ant-btn-secondary-saas:hover {
            background-color: var(--primary-teal-light);
            color: var(--primary-teal-dark);
        }

        /* Eco Badge Premium */
        .eco-card-saas {
            background-color: var(--green-light);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #065F46;
            padding: 16px;
            border-radius: 12px;
            display: flex;
            gap: 12px;
            font-size: 14px;
            line-height: 1.5;
            margin: 20px 0;
            align-items: flex-start;
        }

        /* Verified Badge info */
        .seller-verified-saas {
            background-color: var(--secondary-green-light);
            border-left: 4px solid var(--secondary-green);
            padding: 16px;
            border-radius: 0 12px 12px 0;
            margin-top: 20px;
        }

        /* Action bar for sharing / copies */
        .action-icon-bar {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            justify-content: flex-end;
        }

        .action-icon-btn {
            background: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-muted);
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }

        .action-icon-btn:hover {
            color: var(--primary-teal);
            border-color: var(--primary-teal);
            transform: scale(1.1);
        }

        /* Tags premium design */
        .popular-tag-saas {
            display: inline-block;
            background: #F3F4F6;
            color: var(--text-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            margin: 4px;
            text-decoration: none;
            transition: var(--transition);
            border: 1px solid var(--border-gray);
            cursor: pointer;
        }

        .popular-tag-saas:hover {
            background: var(--primary-teal-light);
            color: var(--primary-teal);
            border-color: var(--primary-teal);
        }

        /* Sidebar Switch Styles */
        .ant-switch-saas {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
            background-color: #D1D5DB;
            border-radius: 20px;
            cursor: pointer;
            transition: var(--transition);
        }

        .ant-switch-saas::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            background-color: var(--white);
            border-radius: 50%;
            transition: var(--transition);
        }

        .switch-checkbox-saas {
            display: none;
        }

        .switch-checkbox-saas:checked + .ant-switch-saas {
            background-color: var(--primary-teal);
        }

        .switch-checkbox-saas:checked + .ant-switch-saas::after {
            left: 24px;
        }

        /* Bottom Row Styling */
        .bottom-cards-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-top: 40px;
        }

        @media (max-width: 1024px) {
            .bottom-cards-row {
                grid-template-columns: 1fr;
            }
        }

        .btn-add-service-saas {
            background-color: var(--primary-teal-light);
            color: var(--primary-teal);
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-add-service-saas:hover {
            background-color: var(--primary-teal);
            color: var(--white);
        }

        /* Checklist container */
        .checklist-item-saas {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .checklist-item-saas i {
            color: var(--secondary-green);
        }

        /* Steps workflow */
        .steps-container-saas {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 16px 0;
        }

        .step-item-saas {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .step-num-saas {
            background: var(--primary-teal-light);
            color: var(--primary-teal);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Waitlist Table Premium */
        .waitlist-table-premium {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .waitlist-table-premium th {
            text-align: left;
            color: var(--text-muted);
            font-weight: 500;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-gray);
        }

        .waitlist-table-premium td {
            padding: 12px 0;
            border-bottom: 1px solid var(--border-gray);
        }

        /* Modals style */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content-premium {
            background-color: var(--white);
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            padding: 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            position: relative;
            border: 1px solid var(--border-gray);
        }

        .modal-close-saas {
            position: absolute;
            top: 16px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .modal-close-saas:hover {
            color: var(--text-dark);
        }

        /* Form Controls Premium */
        .form-group-premium {
            margin-bottom: 16px;
        }

        .form-group-premium label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-dark);
        }

        .form-group-premium input, .form-group-premium select, .form-group-premium textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-dark);
            background-color: var(--white);
            transition: var(--transition);
        }

        .form-group-premium input:focus, .form-group-premium select:focus, .form-group-premium textarea:focus {
            outline: none;
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 3px rgba(0, 124, 137, 0.1);
        }

        /* Badges status */
        .badge-premium {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .badge-active-saas {
            background-color: var(--green-light);
            color: var(--secondary-green);
        }

        .badge-waiting-saas {
            background-color: #FEF3C7;
            color: #D97706;
        }

        /* Tooltips */
        .tooltip-container {
            position: relative;
            display: inline-block;
            cursor: help;
        }

        .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #1F2937;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 100;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            font-weight: normal;
        }

        .tooltip-container:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>

    <!-- Header/Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <a href="/index.html" class="logo">
                <i class="fa-solid fa-cube"></i> LIMA Solutions
            </a>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Menu">Menu</button>
            <nav class="nav-links" id="nav-links">
                <a href="/index.html">Accueil</a>
                <a href="/services.html">Services</a>
                <a href="/marketplace/index.html">Marketplace</a>
                <a href="/about.html">À Propos</a>
                <a href="/faq.html">FAQ</a>
                <a href="/contact.html">Contact</a>
                <a href="/portal/login.php" class="btn-portal-nav"><i class="fa-solid fa-user"></i> Espace Client</a>
                <a href="/contact.html" class="btn-cta-nav">Demander un Devis</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Breadcrumb & Copy Link Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <nav class="breadcrumb-saas">
                <a href="/marketplace/">Marketplace</a>
                <i class="fa-solid fa-chevron-right" style="font-size: 10px; color: var(--text-muted);"></i>
                <a href="/marketplace/?category=<?php echo urlencode($item['category_name']); ?>"><?php echo htmlspecialchars($item['category_name']); ?></a>
                <i class="fa-solid fa-chevron-right" style="font-size: 10px; color: var(--text-muted);"></i>
                <span style="color: var(--text-dark); font-weight: 500;"><?php echo htmlspecialchars($item['title']); ?></span>
            </nav>

            <div class="action-icon-bar">
                <div class="action-icon-btn tooltip-container" onclick="navigator.clipboard.writeText(window.location.href); alert('Lien copié dans le presse-papier !');">
                    <i class="fa-solid fa-link"></i>
                    <span class="tooltip-text">Copier le lien de l'annonce</span>
                </div>
                <div class="action-icon-btn tooltip-container" onclick="window.open('https://api.whatsapp.com/send?text=' + encodeURIComponent(window.location.href), '_blank');">
                    <i class="fa-solid fa-share-nodes"></i>
                    <span class="tooltip-text">Partager cette annonce</span>
                </div>
            </div>
        </div>

        <!-- 3-Column Layout -->
        <div class="marketplace-saas-grid">
            
            <!-- COLUMN 1: Image Gallery & Badges (40%) -->
            <div>
                <div class="ant-card-premium" style="padding: 12px; overflow: hidden;">
                    <div class="gallery-container">
                        <img id="active-gallery-img" class="main-img-zoom" src="<?php echo $mainPhoto; ?>" alt="Image Principal">
                        <span class="photo-counter" id="photo-counter-lbl">1 / <?php echo max(1, count($photos)); ?></span>
                    </div>
                    <?php if (count($photos) > 1): ?>
                        <div class="thumb-list-premium">
                            <?php foreach ($photos as $index => $ph): ?>
                                <img class="thumb-img-premium <?php echo $index === 0 ? 'active' : ''; ?>" src="<?php echo $ph; ?>" alt="Thumb" onclick="document.getElementById('active-gallery-img').src = this.src; document.querySelectorAll('.thumb-img-premium').forEach(e => e.classList.remove('active')); this.classList.add('active'); document.getElementById('photo-counter-lbl').innerText = '<?php echo ($index + 1) . ' / ' . count($photos); ?>';">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ant-card-premium">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-weight: 600; color: var(--text-dark);">Statut de disponibilité</span>
                        <span class="badge-premium badge-active-saas"><i class="fa-solid fa-circle" style="font-size: 8px; margin-right: 4px;"></i> Disponible</span>
                    </div>
                </div>
            </div>

            <!-- COLUMN 2: Details, Eco calculations & Action reservation (30%) -->
            <div>
                <div class="ant-card-premium">
                    <div style="font-size: 32px; font-weight: 700; color: var(--primary-teal); margin-bottom: 8px;">
                        <?php echo $item['price'] !== null ? number_format($item['price'], 2, '.', '') . " CHF" : "DON"; ?>
                    </div>
                    <h1 style="font-size: 24px; font-weight: 700; margin: 0 0 12px 0; color: var(--text-dark); line-height: 1.2;"><?php echo htmlspecialchars($item['title']); ?></h1>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-map-marker-alt" style="color: var(--primary-teal); width: 16px;"></i>
                            <span><?php echo htmlspecialchars($item['location']); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-tags" style="color: var(--primary-teal); width: 16px;"></i>
                            <span><?php echo htmlspecialchars($item['category_name']); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-star" style="color: var(--primary-teal); width: 16px;"></i>
                            <span><?php echo $conditionText; ?></span>
                        </div>
                    </div>

                    <!-- Eco Card -->
                    <div class="eco-card-saas">
                        <i class="fa-solid fa-leaf" style="font-size: 20px; color: var(--secondary-green); margin-top: 2px;"></i>
                        <div>
                            <strong style="display: block; margin-bottom: 4px;">🌱 En achetant cet article :</strong>
                            <div style="font-size: 13px; display: flex; flex-direction: column; gap: 2px;">
                                <span>• vous évitez <?php echo $wasteSavings; ?> CHF de frais de déchetterie</span>
                                <span>• vous réduisez les déchets</span>
                                <span>• vous favorisez le réemploi</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: var(--text-dark); margin: 0 0 8px 0;">Description</h4>
                        <p style="white-space: pre-line; margin: 0; font-size: 14px; color: #4B5563; line-height: 1.6;"><?php echo htmlspecialchars($item['description']); ?></p>
                    </div>

                    <button class="ant-btn-primary-saas" onclick="openModal(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>', 'reserve')">
                        <i class="fa-solid fa-calendar-check"></i> Réserver cet article
                    </button>
                    <button class="ant-btn-secondary-saas" onclick="alert('Cet objet a été ajouté à vos favoris !');">
                        <i class="fa-solid fa-heart"></i> Sauvegarder
                    </button>
                </div>

                <!-- Verified Badge Card -->
                <div class="ant-card-premium seller-verified-saas">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <i class="fa-solid fa-circle-check" style="font-size: 22px; color: var(--secondary-green); margin-top: 2px;"></i>
                        <div>
                            <h4 style="font-weight: 700; font-size: 14px; margin: 0 0 4px 0; color: var(--text-dark);">✓ Vendeur vérifié</h4>
                            <div style="font-size: 13px; color: var(--text-muted); display: flex; flex-direction: column; gap: 2px;">
                                <span>Client LIMA Solutions</span>
                                <span>Projet déménagement validé</span>
                                <span>Membre depuis 2026</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMN 3: Je cherche alerts sidebar form (30%) -->
            <div>
                <div class="ant-card-premium" style="border-top: 4px solid var(--primary-teal);">
                    <h3><i class="fa-solid fa-bell" style="color: var(--primary-teal);"></i> Je recherche un article</h3>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 16px 0; line-height: 1.5;">
                        Recevez une notification dès qu'un objet correspondant est publié.
                    </p>
                    
                    <form id="sidebar-demand-form">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group-premium">
                            <label>Catégorie</label>
                            <select name="category_id" required>
                                <option value="">Choisir une catégorie...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group-premium">
                            <label>Mots-clés</label>
                            <input type="text" name="keywords" placeholder="Ex: buffet, commode" required>
                        </div>
                        <div class="form-group-premium">
                            <label>Budget Maximum (CHF)</label>
                            <input type="number" name="max_price" placeholder="Ex: 150" required>
                        </div>
                        <div class="form-group-premium">
                            <label>Ville / Région (Optionnel)</label>
                            <input type="text" name="location" placeholder="Ex: Lausanne">
                        </div>
                        <div class="form-group-premium">
                            <label>Notification E-mail *</label>
                            <input type="email" id="demand-notif-email" placeholder="Votre adresse e-mail" required>
                        </div>
                        <button type="submit" class="ant-btn-primary-saas" style="margin-top: 10px;">
                            Créer une alerte
                        </button>
                    </form>
                </div>

                <!-- Popular search tags widget -->
                <div class="ant-card-premium">
                    <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 12px 0;">Exemples Populaires</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        <span class="popular-tag-saas" onclick="fillAlertForm('Mobilier d\'occasion', 'Canapé 3 places')">Canapé 3 places</span>
                        <span class="popular-tag-saas" onclick="fillAlertForm('Mobilier d\'occasion', 'Table à manger')">Table à manger</span>
                        <span class="popular-tag-saas" onclick="fillAlertForm('Mobilier d\'occasion', 'Armoire')">Armoire</span>
                        <span class="popular-tag-saas" onclick="fillAlertForm('Mobilier d\'occasion', 'Lit 160x200')">Lit 160x200</span>
                        <span class="popular-tag-saas" onclick="fillAlertForm('Électroménager', 'Machine à laver')">Machine à laver</span>
                        <span class="popular-tag-saas" onclick="fillAlertForm('Électroménager', 'Réfrigérateur')">Réfrigérateur</span>
                    </div>
                </div>

                <!-- Workflow steps -->
                <div class="ant-card-premium">
                    <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 12px 0;">Comment ça fonctionne</h4>
                    <div class="steps-container-saas">
                        <div class="step-item-saas">
                            <span class="step-num-saas">1</span>
                            <div style="font-size: 13px;">
                                <strong>Créez une alerte</strong><br>
                                Indiquez vos critères de recherche.
                            </div>
                        </div>
                        <div class="step-item-saas">
                            <span class="step-num-saas">2</span>
                            <div style="font-size: 13px;">
                                <strong>Recevez une notification</strong><br>
                                Dès qu'un meuble assorti est mis en vente.
                            </div>
                        </div>
                        <div class="step-item-saas">
                            <span class="step-num-saas">3</span>
                            <div style="font-size: 13px;">
                                <strong>Réservez avant les autres</strong><br>
                                Soyez notifié en temps réel.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- SECOND LINE: 4 Columns (Queue, Addons, Preference switches, Tips) -->
        <div class="bottom-cards-row">
            
            <!-- Card 1: Waitlist status & count down -->
            <div class="ant-card-premium">
                <h3><i class="fa-solid fa-users" style="color: var(--primary-teal);"></i> File d'attente</h3>
                
                <div id="waitlist-alert-box" style="display: none; background-color: var(--secondary-green-light); border: 1px solid rgba(14, 139, 114, 0.2); padding: 12px; border-radius: 8px; margin-bottom: 12px; font-size: 13px; color: #065F46;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong>Você está em primeiro lugar.</strong>
                        <span id="countdown-timer" style="font-family: monospace; font-weight: 700; background: rgba(14, 139, 114, 0.1); padding: 2px 6px; border-radius: 4px;">23h 59m</span>
                    </div>
                </div>

                <table class="waitlist-table-premium">
                    <thead>
                        <tr>
                            <th>Pos.</th>
                            <th>Nom</th>
                            <th style="text-align: right;">Statut</th>
                        </tr>
                    </thead>
                    <tbody id="waitlist-tbody">
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 20px 0;">Chargement...</td>
                        </tr>
                    </tbody>
                </table>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 12px; text-align: right;" id="waitlist-footer-text"></p>
            </div>

            <!-- Card 2: Services Complementaires -->
            <div class="ant-card-premium">
                <h3><i class="fa-solid fa-screwdriver-wrench" style="color: var(--primary-teal);"></i> Services Complémentaires</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">Ajoutez des options de service LIMA :</p>
                
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span><i class="fa-solid fa-truck" style="width: 18px; color: var(--primary-teal);"></i> Transport / Livraison</span>
                        <button class="btn-add-service-saas" onclick="openModal(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>', 'delivery')">Ajouter</button>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span><i class="fa-solid fa-screwdriver" style="width: 18px; color: var(--primary-teal);"></i> Démontage</span>
                        <button class="btn-add-service-saas" onclick="openModal(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>', 'moving')">Ajouter</button>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span><i class="fa-solid fa-warehouse" style="width: 18px; color: var(--primary-teal);"></i> Stockage</span>
                        <button class="btn-add-service-saas" onclick="openModal(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>', 'storage')">Ajouter</button>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span><i class="fa-solid fa-broom" style="width: 18px; color: var(--primary-teal);"></i> Nettoyage</span>
                        <button class="btn-add-service-saas" onclick="openModal(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>', 'cleaning')">Ajouter</button>
                    </div>
                </div>
            </div>

            <!-- Card 3: Notifications -->
            <div class="ant-card-premium">
                <h3><i class="fa-solid fa-sliders" style="color: var(--primary-teal);"></i> Notifications Intelligentes</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">Restez informé en temps réel :</p>
                
                <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa-solid fa-envelope" style="width: 18px; color: var(--primary-teal);"></i> Email</span>
                        <label>
                            <input type="checkbox" class="switch-checkbox-saas" id="notif-email" checked>
                            <span class="ant-switch-saas"></span>
                        </label>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa-solid fa-comments" style="width: 18px; color: var(--primary-teal);"></i> WhatsApp</span>
                        <label>
                            <input type="checkbox" class="switch-checkbox-saas" id="notif-whatsapp">
                            <span class="ant-switch-saas"></span>
                        </label>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa-solid fa-desktop" style="width: 18px; color: var(--primary-teal);"></i> Push Browser</span>
                        <label>
                            <input type="checkbox" class="switch-checkbox-saas" id="notif-push" checked>
                            <span class="ant-switch-saas"></span>
                        </label>
                    </div>
                </div>
                
                <button class="ant-btn-secondary-saas" style="margin-top: 20px; padding: 10px;" onclick="alert('Préférences de notifications enregistrées !');">
                    Gérer mes préférences
                </button>
            </div>

            <!-- Card 4: Tips for better search -->
            <div class="ant-card-premium">
                <h3><i class="fa-solid fa-lightbulb" style="color: var(--primary-teal);"></i> Trouver plus rapidement</h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">Conseils pour votre alerte :</p>
                
                <div class="checklist-item-saas">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>définir budget</span>
                </div>
                <div class="checklist-item-saas">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>indiquer dimensions</span>
                </div>
                <div class="checklist-item-saas">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>choisir région</span>
                </div>
                <div class="checklist-item-saas">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>préciser style</span>
                </div>

                <button class="ant-btn-primary-saas" style="margin-top: 12px;" onclick="document.getElementsByName('category_id')[0].focus();">
                    Créer une alerte
                </button>
            </div>

        </div>
    </main>

    <!-- Unified Modal for Booking & Addons -->
    <div id="interest-modal" class="modal">
        <div class="modal-content-premium">
            <span class="modal-close-saas" onclick="closeModal()">&times;</span>
            <h3 style="margin-bottom: 1.5rem; font-size: 20px;" id="modal-title">Je suis intéressé</h3>
            
            <form id="interest-form">
                <input type="hidden" id="modal-item-id" name="item_id">
                <input type="hidden" id="modal-request-type" name="request_type" value="buyer">
                
                <div class="form-group-premium">
                    <label for="interest-name">Votre nom complet *</label>
                    <input type="text" id="interest-name" name="name" required placeholder="Ex: Jean Dupont">
                </div>
                <div class="form-group-premium">
                    <label for="interest-email">Adresse e-mail *</label>
                    <input type="email" id="interest-email" name="email" required placeholder="Ex: jean.dupont@gmail.com">
                </div>
                <div class="form-group-premium">
                    <label for="interest-phone">Téléphone *</label>
                    <input type="text" id="interest-phone" name="phone" placeholder="Ex: +41 79 123 45 67">
                </div>

                <!-- Conditional Delivery Fields -->
                <div id="delivery-fields" style="display: none;">
                    <div class="form-group-premium">
                        <label for="pickup-city">Ville de départ (Lieu de prise en charge) *</label>
                        <input type="text" id="pickup-city" name="pickup_city" placeholder="Ex: Lausanne">
                    </div>
                    <div class="form-group-premium">
                        <label for="delivery-city">Ville d'arrivée (Lieu de livraison) *</label>
                        <input type="text" id="delivery-city" name="delivery_city" placeholder="Ex: Genève">
                    </div>
                </div>

                <!-- Conditional Storage Fields -->
                <div id="storage-fields" style="display: none;">
                    <div class="form-group-premium">
                        <label for="storage-duration">Durée de stockage estimée *</label>
                        <select id="storage-duration" name="storage_duration">
                            <option value="">Sélectionnez la durée...</option>
                            <option value="Moins de 1 mois">Moins de 1 mois</option>
                            <option value="1 à 3 mois">1 à 3 mois</option>
                            <option value="3 à 6 mois">3 à 6 mois</option>
                            <option value="Plus de 6 mois">Plus de 6 mois</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-premium">
                    <label for="interest-message">Message *</label>
                    <textarea id="interest-message" name="message" rows="3" required></textarea>
                </div>
                
                <button type="submit" class="ant-btn-primary-saas">
                    <i class="fa-solid fa-paper-plane"></i> Soumettre
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('interest-modal');
        const deliveryFields = document.getElementById('delivery-fields');
        const storageFields = document.getElementById('storage-fields');
        const pickupCityInput = document.getElementById('pickup-city');
        const deliveryCityInput = document.getElementById('delivery-city');
        const storageDurationInput = document.getElementById('storage-duration');
        const itemId = <?php echo $itemId; ?>;

        function openModal(id, title, requestType) {
            modal.style.display = 'flex';
            document.getElementById('modal-item-id').value = id;
            document.getElementById('modal-request-type').value = requestType;
            
            deliveryFields.style.display = 'none';
            pickupCityInput.required = false;
            deliveryCityInput.required = false;
            storageFields.style.display = 'none';
            storageDurationInput.required = false;

            if (requestType === 'reserve') {
                document.getElementById('modal-title').innerHTML = `Réserver : <strong>${title}</strong>`;
                document.getElementById('interest-message').value = "Bonjour, je souhaiterais réserver cet article et planifier sa prise en charge.";
            } else if (requestType === 'delivery') {
                document.getElementById('modal-title').innerHTML = `Demande de livraison pour : <strong>${title}</strong>`;
                deliveryFields.style.display = 'block';
                pickupCityInput.required = true;
                deliveryCityInput.required = true;
                document.getElementById('interest-message').value = "Bonjour, je souhaiterais obtenir un devis de transport / livraison pour cet article.";
            } else if (requestType === 'moving') {
                document.getElementById('modal-title').innerHTML = `Demande de démontage : <strong>${title}</strong>`;
                document.getElementById('interest-message').value = "Bonjour, je souhaiterais obtenir un devis pour le démontage de cet article.";
            } else if (requestType === 'storage') {
                document.getElementById('modal-title').innerHTML = `Demande de stockage : <strong>${title}</strong>`;
                storageFields.style.display = 'block';
                storageDurationInput.required = true;
                document.getElementById('interest-message').value = "Bonjour, je souhaiterais stocker cet objet dans vos garde-meubles.";
            } else if (requestType === 'cleaning') {
                document.getElementById('modal-title').innerHTML = `Demande de nettoyage : <strong>${title}</strong>`;
                document.getElementById('interest-message').value = "Bonjour, je souhaiterais obtenir un devis pour un nettoyage de fin de bail.";
            }
        }

        function closeModal() {
            modal.style.display = 'none';
            document.getElementById('interest-form').reset();
        }

        // Fill demand alert form from tags clicks
        function fillAlertForm(categoryName, keywords) {
            const selectEl = document.getElementsByName('category_id')[0];
            for (let i = 0; i < selectEl.options.length; i++) {
                if (selectEl.options[i].text.toLowerCase().includes(categoryName.toLowerCase())) {
                    selectEl.selectedIndex = i;
                    break;
                }
            }
            document.getElementsByName('keywords')[0].value = keywords;
            document.getElementsByName('keywords')[0].focus();
        }

        // Handle Form Submission (Reservations + Lead routing)
        document.getElementById('interest-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const type = document.getElementById('modal-request-type').value;

            if (type === 'reserve') {
                const payload = {
                    item_id: itemId,
                    name: document.getElementById('interest-name').value.trim(),
                    email: document.getElementById('interest-email').value.trim(),
                    phone: document.getElementById('interest-phone').value.trim()
                };

                try {
                    const res = await fetch('/api/v1/marketplace/reservations.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    alert(data.message);
                    closeModal();
                    loadQueue();
                } catch (err) {
                    alert("Erreur lors de la réservation.");
                }
            } else {
                const payload = {
                    item_id: itemId,
                    request_type: type,
                    name: document.getElementById('interest-name').value.trim(),
                    email: document.getElementById('interest-email').value.trim(),
                    phone: document.getElementById('interest-phone').value.trim(),
                    message: document.getElementById('interest-message').value.trim(),
                    pickup_city: pickupCityInput.value.trim(),
                    delivery_city: deliveryCityInput.value.trim(),
                    storage_duration: storageDurationInput.value
                };

                try {
                    const res = await fetch('/api/v1/marketplace/interests.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    alert(data.message);
                    closeModal();
                } catch (err) {
                    alert("Erreur lors de la soumission de la demande.");
                }
            }
        });

        // Load Waitlist Queue
        let timerInterval = null;

        async function loadQueue() {
            try {
                const res = await fetch(`/api/v1/marketplace/reservations.php?item_id=${itemId}`);
                const data = await res.json();
                
                const tbody = document.getElementById('waitlist-tbody');
                const alertBox = document.getElementById('waitlist-alert-box');
                const footerText = document.getElementById('waitlist-footer-text');
                
                tbody.innerHTML = '';
                alertBox.style.display = 'none';
                clearInterval(timerInterval);

                if (data.success && data.queue.length > 0) {
                    let hasActive = false;
                    
                    data.queue.forEach((item, index) => {
                        const row = document.createElement('tr');
                        const isFirst = (index === 0 && item.status === 'active');
                        
                        if (isFirst) {
                            hasActive = true;
                            alertBox.style.display = 'block';
                            startTimer(item.expires_at);
                        }

                        row.innerHTML = `
                            <td style="font-weight:600;">${index + 1}</td>
                            <td>${escapeHtml(item.name)}</td>
                            <td style="text-align: right;">
                                <span class="badge-premium ${isFirst ? 'badge-active-saas' : 'badge-waiting-saas'}">
                                    ${isFirst ? 'Actif' : 'En attente'}
                                </span>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    footerText.innerHTML = `${data.queue.length} personne(s) dans la file d'attente.`;
                } else {
                    tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 20px 0;">Aucune réservation active. Soyez le premier !</td></tr>`;
                    footerText.innerHTML = '';
                }
            } catch (err) {
                console.error("Queue load failed", err);
            }
        }

        function startTimer(expiresAt) {
            const expTime = new Date(expiresAt).getTime();
            const timer = document.getElementById('countdown-timer');

            function updateTime() {
                const now = new Date().getTime();
                const diff = expTime - now;

                if (diff <= 0) {
                    timer.innerHTML = "Expiré";
                    clearInterval(timerInterval);
                    loadQueue();
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                timer.innerHTML = `${hours}h ${minutes}m`;
            }

            updateTime();
            timerInterval = setInterval(updateTime, 60000); // update every minute
        }

        // Sidebar demand form submission
        document.getElementById('sidebar-demand-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());
            payload.email = document.getElementById('demand-notif-email').value.trim();
            payload.expires_in_days = 30; // default expiration days

            try {
                const res = await fetch('/api/v1/portal/marketplace_demands.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                alert(data.message);
                e.target.reset();
            } catch (err) {
                alert("Erreur lors de la création de la demande.");
            }
        });

        function escapeHtml(text) {
            if (!text) return "";
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Init
        loadQueue();
    </script>
</body>
</html>
