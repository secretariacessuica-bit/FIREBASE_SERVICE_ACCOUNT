import os
import re

target_dir = r"c:\Users\Wande\Documents\WarLife_TheSwarm\Unity_Project\Assets\Scripts"
patterns = [
    r"using InsectAscension.Units;",
    r"using InsectAscension.Environment;"
]

def clean_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    new_lines = []
    seen = {p: False for p in patterns}
    modified = False

    for line in lines:
        stripped = line.strip()
        found_pattern = None
        for p in patterns:
            if stripped == p:
                found_pattern = p
                break
        
        if found_pattern:
            if seen[found_pattern]:
                modified = True
                continue # Skip redundant
            else:
                seen[found_pattern] = True
                new_lines.append(line)
        else:
            new_lines.append(line)
    
    if modified:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.writelines(new_lines)
        print(f"Cleaned: {filepath}")

for root, dirs, files in os.walk(target_dir):
    for file in files:
        if file.endswith(".cs"):
            clean_file(os.path.join(root, file))
