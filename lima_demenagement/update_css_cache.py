import os
import re

public_site = r'c:\Users\Wande\Documents\ia\lima_demenagement\public_site'

for root, dirs, files in os.walk(public_site):
    for file in files:
        if file.endswith('.html') or file.endswith('.php'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            
            # Find and replace style.css links to add query parameter to break cache
            updated = re.sub(
                r'href=["\'](.*?/style\.css)(\?v=.*?)?["\']',
                r'href="\1?v=2.1"',
                content
            )
            
            if updated != content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(updated)
                print(f"Updated CSS link in: {os.path.relpath(filepath, public_site)}")
