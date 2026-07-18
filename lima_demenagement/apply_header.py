import os
import re

public_site = r'c:\Users\Wande\Documents\ia\lima_demenagement\public_site'
files_to_modify = [
    'index.html',
    'about.html',
    'services.html',
    'coverage.html',
    'faq.html',
    'contact.html',
    'marketplace/index.html',
    'guides/rendre-appartement.html',
    'guides/checklist-demenagement.html',
    'guides/nettoyage-obligatoire.html',
    'guides/reboucher-trous-vis.html',
    'guides/caution-proprietaire.html'
]

unified_header = """    <!-- Header/Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <a href="/index.html" class="logo">
                <i class="fa-solid fa-cube"></i> LIMA Solutions
            </a>
            
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <nav class="nav-links" id="nav-links">
                <a href="/index.html">Accueil</a>
                <a href="/services.html">Services</a>
                <a href="/marketplace/index.html">Marketplace</a>
                
                <!-- Guides Dropdown -->
                <div class="nav-dropdown" style="position: relative; display: inline-block;">
                    <a href="#" class="nav-dropdown-toggle" onclick="toggleDropdown(event, 'guides-dropdown')">Guides <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i></a>
                    <div id="guides-dropdown" class="dropdown-content" style="display: none; position: absolute; left: 0; background-color: var(--white); min-width: 200px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid var(--border-gray); border-radius: var(--border-radius); z-index: 1010; margin-top: 5px;">
                        <a href="/guides/checklist-demenagement.html" style="padding: 10px 15px; display: block; border-bottom: 1px solid var(--border-gray); color: var(--text-dark); text-decoration: none;">Checklist Déménagement</a>
                        <a href="/guides/rendre-appartement.html" style="padding: 10px 15px; display: block; border-bottom: 1px solid var(--border-gray); color: var(--text-dark); text-decoration: none;">Rendre son appartement</a>
                        <a href="/guides/nettoyage-obligatoire.html" style="padding: 10px 15px; display: block; border-bottom: 1px solid var(--border-gray); color: var(--text-dark); text-decoration: none;">Nettoyage de remise</a>
                        <a href="/guides/reboucher-trous-vis.html" style="padding: 10px 15px; display: block; border-bottom: 1px solid var(--border-gray); color: var(--text-dark); text-decoration: none;">Reboucher les trous</a>
                        <a href="/guides/caution-proprietaire.html" style="padding: 10px 15px; display: block; color: var(--text-dark); text-decoration: none;">Récupérer sa caution</a>
                    </div>
                </div>

                <a href="/about.html">À Propos</a>
                <a href="/faq.html">FAQ</a>
                <a href="/contact.html">Contact</a>
                <a href="/portal/login.php" class="btn-portal-nav"><i class="fa-solid fa-user"></i> Espace Client</a>
                <a href="/contact.html" class="btn-cta-nav">Demander un Devis</a>
                
                <!-- Language Selector Dropdown -->
                <div class="lang-selector" style="position: relative; display: inline-block;">
                    <button class="btn-lang" style="background: none; border: 1px solid var(--border-gray); padding: 6px 12px; border-radius: var(--border-radius); font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; color: var(--text-dark);" onclick="toggleDropdown(event, 'lang-dropdown')">
                        🇫🇷 FR <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                    </button>
                    <div id="lang-dropdown" class="dropdown-content" style="display: none; position: absolute; right: 0; background-color: var(--white); min-width: 120px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid var(--border-gray); border-radius: var(--border-radius); z-index: 1010; margin-top: 5px;">
                        <a href="#" style="color: var(--text-dark); padding: 8px 12px; text-decoration: none; display: block; font-size: 13px; font-weight: 500; opacity: 0.5; pointer-events: none;">🇩🇪 DE (Bientôt)</a>
                        <a href="#" style="color: var(--text-dark); padding: 8px 12px; text-decoration: none; display: block; font-size: 13px; font-weight: 500; opacity: 0.5; pointer-events: none;">🇵🇹 PT (Breve)</a>
                        <a href="#" style="color: var(--text-dark); padding: 8px 12px; text-decoration: none; display: block; font-size: 13px; font-weight: 500; opacity: 0.5; pointer-events: none;">🇬🇧 EN (Soon)</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>"""

for page in files_to_modify:
    full_path = os.path.join(public_site, page)
    if not os.path.exists(full_path):
        print(f"Skipping {page}, not found.")
        continue
        
    with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
        html = f.read()

    # Special handling for marketplace/index.html custom style
    if 'marketplace/index.html' in full_path:
        html = re.sub(r'<style>.*?\.header-bar \{.*?\}.*?</style>', '<style>\n        :root {\n            --primary-teal: #007a87;\n            --primary-teal-light: #e6f2f3;\n            --primary-teal-dark: #005a63;\n            --text-dark: #333333;\n            --text-muted: #666666;\n            --bg-light: #f9f9f9;\n            --white: #ffffff;\n            --border-gray: #e0e0e0;\n            --border-radius: 8px;\n            --transition: all 0.3s ease;\n        }\n\n        *\n        {\n            box-sizing: border-box;\n            margin: 0;\n            padding: 0;\n        }\n\n        body {\n            font-family: \'Inter\', sans-serif;\n            background-color: var(--bg-light);\n            color: var(--text-dark);\n            line-height: 1.6;\n        }\n\n        .btn {\n            background-color: var(--primary-teal);\n            color: var(--white);\n            padding: 10px 20px;\n            border: none;\n            border-radius: var(--border-radius);\n            font-weight: 600;\n            cursor: pointer;\n            text-decoration: none;\n            display: inline-flex;\n            align-items: center;\n            gap: 8px;\n            font-size: 14px;\n        }\n\n        .btn:hover {\n            background-color: var(--primary-teal-dark);\n        }\n\n        .container {\n            max-width: 1200px;\n            margin: 2rem auto;\n            padding: 0 1.5rem;\n        }\n\n        .hero {\n            background: linear-gradient(135deg, rgba(0, 122, 135, 0.1), rgba(0, 90, 99, 0.05));\n            border-radius: 12px;\n            padding: 3rem 2rem;\n            text-align: center;\n            margin-bottom: 2rem;\n            border: 1px solid var(--border-gray);\n        }\n\n        .hero h2 {\n            font-size: 28px;\n            font-weight: 700;\n            color: var(--primary-teal-dark);\n            margin-bottom: 0.5rem;\n        }\n\n        .catalog-grid {\n            display: grid;\n            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));\n            gap: 2rem;\n        }\n\n        .product-card {\n            background-color: var(--white);\n            border: 1px solid var(--border-gray);\n            border-radius: var(--border-radius);\n            overflow: hidden;\n            display: flex;\n            flex-direction: column;\n            box-shadow: 0 1px 3px rgba(0,0,0,0.02);\n            transition: var(--transition);\n        }\n\n        .product-card:hover {\n            transform: translateY(-4px);\n            box-shadow: 0 8px 16px rgba(0,0,0,0.05);\n        }\n\n        .product-img {\n            width: 100%;\n            height: 200px;\n            object-fit: cover;\n            border-bottom: 1px solid var(--border-gray);\n        }\n\n        .product-info {\n            padding: 1.25rem;\n            display: flex;\n            flex-direction: column;\n            gap: 0.5rem;\n            flex-grow: 1;\n        }\n\n        .product-price {\n            font-size: 18px;\n            font-weight: 700;\n            color: var(--primary-teal);\n        }\n\n        .product-title {\n            font-size: 16px;\n            font-weight: 600;\n            margin-bottom: 0.25rem;\n        }\n\n        .product-meta {\n            display: flex;\n            justify-content: space-between;\n            align-items: center;\n            font-size: 13px;\n            color: var(--text-muted);\n            margin-top: auto;\n            padding-top: 1rem;\n            border-top: 1px solid var(--border-gray);\n        }\n\n        .badge {\n            background-color: var(--primary-teal-light);\n            color: var(--primary-teal-dark);\n            padding: 2px 8px;\n            border-radius: 12px;\n            font-size: 11px;\n            font-weight: 600;\n            text-transform: uppercase;\n        }\n\n        .empty-state {\n            text-align: center;\n            padding: 4rem 2rem;\n            background-color: var(--white);\n            border: 1px solid var(--border-gray);\n            border-radius: var(--border-radius);\n            color: var(--text-muted);\n        }\n\n        .empty-state i {\n            font-size: 48px;\n            color: var(--border-gray);\n            margin-bottom: 1rem;\n        }\n\n        .filter-btn {\n            padding: 8px 16px;\n            border: 1px solid var(--border-gray);\n            background-color: var(--white);\n            border-radius: 20px;\n            font-size: 14px;\n            cursor: pointer;\n            transition: var(--transition);\n        }\n\n        .filter-btn:hover, .filter-btn.active {\n            background-color: var(--primary-teal);\n            color: var(--white);\n            border-color: var(--primary-teal);\n        }\n    </style>', html, flags=re.DOTALL)
        # Also need to link style.css in marketplace/index.html since it's going to use the unified header
        if '<link rel="stylesheet" href="/style.css">' not in html and '<link rel="stylesheet" href="../style.css">' not in html:
            html = html.replace('</head>', '    <link rel="stylesheet" href="/style.css">\n</head>')
        
    # Replace the existing header
    new_html = re.sub(r'<!-- Header/Navigation -->\s*<header class="navbar">.*?</header>', unified_header, html, flags=re.DOTALL)
    # Some pages might not have <!-- Header/Navigation --> or might use header-bar
    new_html = re.sub(r'<header class="header-bar">.*?</header>', unified_header, new_html, flags=re.DOTALL)
    # Index.html has script logic inside the header block that we must remove, let's just do a clean regex from <header to </header>
    if '<!-- Header/Navigation -->' not in new_html:
        new_html = re.sub(r'<header class="navbar">.*?</header>', unified_header, new_html, flags=re.DOTALL)
    
    # Add app.js if not present
    if 'src="/app.js"' not in new_html and 'src="app.js"' not in new_html:
        new_html = new_html.replace('</body>', '    <script src="/app.js"></script>\n</body>')
    # ensure it uses absolute path if it was relative
    elif 'src="app.js"' in new_html:
        new_html = new_html.replace('src="app.js"', 'src="/app.js"')
        
    if new_html != html:
        with open(full_path, 'w', encoding='utf-8') as f:
            f.write(new_html)
        print(f"Updated {page}")
    else:
        print(f"No changes made to {page} (header pattern might not match)")
