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

# Regex to match the entire lang-selector div
lang_regex = r'<!-- Language Selector Dropdown -->\s*<div class="lang-selector".*?</div>\s*</div>'

for page in files_to_modify:
    full_path = os.path.join(public_site, page)
    if not os.path.exists(full_path):
        continue
        
    with open(full_path, 'r', encoding='utf-8') as f:
        html = f.read()
        
    new_html = re.sub(lang_regex, '', html, flags=re.DOTALL)
    
    if new_html != html:
        with open(full_path, 'w', encoding='utf-8') as f:
            f.write(new_html)
        print(f"Removed lang selector from {page}")
    else:
        print(f"Lang selector not found or already removed in {page}")
