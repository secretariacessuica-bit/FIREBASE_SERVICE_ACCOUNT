import urllib.request
import json

url = "https://limasolutions.ch/db_inspect.php"

try:
    print(f"Requesting {url}...")
    with urllib.request.urlopen(url) as response:
        html = response.read().decode('utf-8')
        print("Response received:")
        print(html)
except Exception as e:
    print("Error during request:", e)
