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

for page in files_to_modify:
    full_path = os.path.join(public_site, page)
    if not os.path.exists(full_path):
        continue
        
    with open(full_path, 'r', encoding='utf-8') as f:
        html = f.read()
        
    # Replace the icon with 'Menu'
    new_html = html.replace('<i class="fa-solid fa-bars"></i>', 'Menu')
    
    if new_html != html:
        with open(full_path, 'w', encoding='utf-8') as f:
            f.write(new_html)
        print(f"Replaced bars icon with Menu in {page}")
    else:
        print(f"Icon not found in {page}")
