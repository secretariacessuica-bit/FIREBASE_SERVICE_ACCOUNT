import os
from PIL import Image
import numpy as np

# Configurações
ASSETS_DIR = './assets/images/avatar/'
CANVAS_SIZE = 500
TOLERANCE = 10  # Pixels de margem de erro permitidos

def validate_image(file_path, folder_type):
    img = Image.open(file_path).convert('RGBA')
    
    # Valida dimensões físicas da imagem
    width, height = img.size
    if width != CANVAS_SIZE or height != CANVAS_SIZE:
        return False, f"Resolução incorreta: {width}x{height}px (esperado {CANVAS_SIZE}x{CANVAS_SIZE}px)."
        
    data = np.array(img)
    
    # Extrai o canal Alpha (transparência)
    alpha = data[:, :, 3]
    
    # Encontra as coordenadas dos pixels não transparentes
    coords = np.argwhere(alpha > 0)
    
    if coords.size == 0:
        return False, "Imagem completamente transparente."

    # Calcula o centro da imagem desenhada
    y_min, x_min = coords.min(axis=0)
    y_max, x_max = coords.max(axis=0)
    center_y = (y_min + y_max) / 2
    center_x = (x_min + x_max) / 2
    
    # Valida centralização horizontal (eixo X deve estar perto de 250)
    horizontal_tolerance = 12.0 if folder_type in ["pants", "hairs"] else 5.0
    if abs(center_x - CANVAS_SIZE/2) > horizontal_tolerance:
        return False, f"Desalinhado horizontalmente: X em {center_x:.1f}px (esperado 250.0px)."
        
    # Valida faixa vertical esperada com base no tipo de camada (anatomia do Character Design System)
    expected_y = 250.0
    if folder_type == "bases":
        expected_y = 212.0
    elif folder_type == "hairs":
        if "hair_short01" in file_path:
            expected_y = 136.0
        elif "hair_long02_6" in file_path or "hair_long02_7" in file_path:
            expected_y = 143.0
        else:
            expected_y = 112.0
    elif folder_type == "shirts":
        expected_y = 287.0
    elif folder_type == "pants":
        # Calças curtas/shorts têm centro de massa mais abaixo do que calças compridas
        expected_y = 346.0 if "pants_02" in file_path else 289.0
    elif folder_type == "faces":
        expected_y = 218.0
        
    y_diff = abs(center_y - expected_y)
    tolerance_to_use = 15.0 if folder_type in ["faces", "pants"] else TOLERANCE
    if y_diff > tolerance_to_use:
        return False, f"Desalinhado verticalmente: Y em {center_y:.1f}px (esperado {expected_y:.1f}px para {folder_type})."
    
    return True, "OK"

if __name__ == "__main__":
    print("--- Iniciando Validacao de Assets ---")
    if not os.path.exists(ASSETS_DIR):
        print(f"Diretorio {ASSETS_DIR} nao encontrado.")
    else:
        has_files = False
        for root, dirs, files in os.walk(ASSETS_DIR):
            png_files = [f for f in files if f.endswith(".png")]
            if png_files:
                has_files = True
                relative_folder = os.path.relpath(root, ASSETS_DIR)
                print(f"\n[Folder] Pasta: {relative_folder if relative_folder != '.' else 'raiz'}")
                for filename in png_files:
                    path = os.path.join(root, filename)
                    success, message = validate_image(path, relative_folder)
                    status = "[OK]" if success else "[FAIL]"
                    print(f"  {status} {filename}: {message}")
        if not has_files:
            print(f"Nenhum arquivo .png encontrado em {ASSETS_DIR} ou suas subpastas.")
