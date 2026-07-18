import os
import re

public_site = r'c:\Users\Wande\Documents\ia\lima_demenagement\public_site'
pages_to_audit = [
    'index.html',
    'about.html',
    'services.html',
    'coverage.html',
    'faq.html',
    'contact.html',
    'marketplace/index.html',
    'marketplace/item.php',
    'guides/rendre-appartement.html',
    'guides/checklist-demenagement.html',
    'guides/nettoyage-obligatoire.html',
    'guides/reboucher-trous-vis.html',
    'guides/caution-proprietaire.html'
]

results = {
    'broken_links': [],
    'missing_images': [],
    'responsive_issues': [],
    'visual_issues': [],
    'functional_issues': []
}

def check_file_exists(path):
    if not path:
        return True
    
    # Check if directory like /marketplace/
    if path.endswith('/'):
        path = path + 'index.html'
    elif not path.endswith('.html') and not path.endswith('.php') and not path.endswith('.css') and not path.endswith('.js') and not path.endswith('.png') and not path.endswith('.jpg'):
        # It might be a directory or route, let's just assume it maps to an index.php or index.html
        if os.path.isdir(os.path.join(public_site, path)):
            path = path + '/index.html'
            if not os.path.exists(os.path.join(public_site, path)):
                path = path.replace('.html', '.php')
                
    full_path = os.path.normpath(os.path.join(public_site, path))
    return os.path.exists(full_path)

for page in pages_to_audit:
    full_path = os.path.join(public_site, page)
    if not os.path.exists(full_path):
        results['broken_links'].append(f"File not found: {page}")
        continue
        
    with open(full_path, 'r', encoding='utf-8', errors='ignore') as f:
        html = f.read()
        
    # Check viewport
    if '<meta name="viewport"' not in html:
        results['responsive_issues'].append(f"{page} - Missing viewport meta tag")
        
    # Check links
    hrefs = re.findall(r'href=["\'](.*?)["\']', html)
    for href in hrefs:
        if href and not href.startswith(('http', '#', 'tel:', 'mailto:', 'javascript:', '<?php')):
            resolved_path = href
            if resolved_path.startswith('/'):
                resolved_path = resolved_path[1:] # remove leading slash
            else:
                base_dir = os.path.dirname(page)
                resolved_path = os.path.join(base_dir, href)
                
            if '?' in resolved_path: resolved_path = resolved_path.split('?')[0]
            if '#' in resolved_path: resolved_path = resolved_path.split('#')[0]
            
            resolved_path = resolved_path.replace('\\', '/')
            if resolved_path and not check_file_exists(resolved_path):
                results['broken_links'].append(f"{page} - Broken link: {href}")
                
    # Check images
    srcs = re.findall(r'src=["\'](.*?)["\']', html)
    for src in srcs:
        if src and not src.startswith(('http', 'data:', '<?php', '${')):
            resolved_path = src
            if resolved_path.startswith('/'):
                resolved_path = resolved_path[1:] # remove leading slash
            else:
                base_dir = os.path.dirname(page)
                resolved_path = os.path.join(base_dir, src)
                
            if '?' in resolved_path: resolved_path = resolved_path.split('?')[0]
            if resolved_path and not check_file_exists(resolved_path):
                results['missing_images'].append(f"{page} - Missing media/script: {src}")

print("--- RESULTS ---")
for key, values in results.items():
    print(f"\\n{key.upper()}:")
    for v in set(values):
        print(f"  - {v}")
