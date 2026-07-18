import urllib.request
import json

url = "https://limasolutions.ch/db_cols.php"

try:
    print(f"Requesting {url}...")
    with urllib.request.urlopen(url) as response:
        html = response.read().decode('utf-8')
        res_json = json.loads(html)
        print(json.dumps(res_json, indent=4, ensure_ascii=False))
except Exception as e:
    print("Error during request:", e)
