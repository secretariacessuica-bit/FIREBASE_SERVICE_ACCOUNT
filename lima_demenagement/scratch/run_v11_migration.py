import urllib.request

url = "https://limasolutions.ch/migrate_v11.php"

try:
    print(f"Triggering Phase 11 database migration at {url}...")
    with urllib.request.urlopen(url, timeout=30) as response:
        html = response.read().decode('utf-8')
        print("\n--- MIGRATION RUNNER OUTPUT ---\n")
        print(html)
except Exception as e:
    print("Migration failed:", e)
