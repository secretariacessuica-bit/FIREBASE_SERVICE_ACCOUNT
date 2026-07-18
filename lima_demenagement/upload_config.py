import paramiko

host = '6o9v7p.ftp.infomaniak.com'
port = 22
username = '6o9v7p_admin'
password = 'Ces124578.'

config_content = """<?php
// Configurações Privadas da Base de Dados
// PREENCHA OS VALORES ABAIXO COM OS SEUS DADOS DA INFOMANIAK

define('SECURE_DB_HOST', 'localhost'); // Substituir se necessário (ex: cxxxx.myd.infomaniak.com)
define('SECURE_DB_NAME', 'nome_da_base_de_dados');
define('SECURE_DB_USER', 'utilizador_da_base_de_dados');
define('SECURE_DB_PASS', 'sua_senha_aqui');
"""

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=username, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    # Check if private_lima exists, if not create it
    try:
        sftp.stat('sites/private_lima')
    except IOError:
        sftp.mkdir('sites/private_lima')
        
    remote_path = 'sites/private_lima/config.php'
    
    with sftp.file(remote_path, 'w') as f:
        f.write(config_content)
        
    print("config.php uploaded successfully.")
    
    sftp.close()
    transport.close()
except Exception as e:
    print("SFTP failed:", str(e))
