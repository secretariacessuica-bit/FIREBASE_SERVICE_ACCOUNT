# LIMA Solutions ERP – Deployment Guide

> **Ambiente de referência**: Infomaniak (PHP 8.1+, Apache, MySQL 8.0)  
> Compatível com qualquer ambiente cPanel padrão.

---

## 1. Requisitos Mínimos

| Componente | Versão mínima |
|---|---|
| PHP | 8.1+ |
| MySQL / MariaDB | 8.0+ / 10.6+ |
| Apache | 2.4+ (com `mod_rewrite`) |
| Extensões PHP | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` |
| HTTPS / SSL | Obrigatório em produção |

---

## 2. Estrutura de Diretórios Obrigatória

O diretório `private/` **deve ficar fora da raiz pública** (`public_html`). Isso garante que as credenciais da base de dados nunca sejam acessíveis via HTTP.

```
/home/<utilizador>/
├── private/                   ← FORA do public_html (credenciais seguras)
│   ├── config.php             ← Credenciais da base de dados
│   ├── storage/               ← Uploads e ficheiros gerados
│   └── logs/                  ← Logs de aplicação (opcional)
│
└── public_html/               ← Raiz pública do servidor
    └── (ou subpasta no Infomaniak, ex: public_html/erp/)
        ├── admin/
        ├── api/
        ├── assets/
        ├── db/
        ├── facture/
        ├── helpers/
        ├── modules/
        ├── index.html
        └── style.css
```

> **Nota Infomaniak**: Em contas partilhadas, `public_html` é a raiz pública. O diretório `private/` deve ser criado um nível acima, em `/home/<user>/private/`.

---

## 3. Passo a Passo de Deploy

### 3.1 Criar a Base de Dados

1. Aceder ao **painel Infomaniak** → Hosting → MySQL
2. Criar um novo banco de dados: `lima_solutions` (charset: `utf8mb4`, collation: `utf8mb4_unicode_ci`)
3. Criar um utilizador MySQL dedicado (não usar `root`)
4. Atribuir **todos os privilégios** ao utilizador na base `lima_solutions`
5. Anotar: `host`, `nome_bd`, `utilizador`, `senha`

### 3.2 Upload dos Ficheiros

**Via FTP / SFTP (recomendado: FileZilla ou WinSCP):**

```
Servidor FTP: ftp.infomaniak.com (ou o indicado no painel)
Porta:        21 (FTP) ou 22 (SFTP)
Login:        utilizador Infomaniak
```

**Estrutura de upload:**

```
→ /home/<user>/private/config.php         (credenciais)
→ /home/<user>/public_html/               (conteúdo de public_site/)
```

> Faça upload do conteúdo **dentro** de `public_site/`, não da pasta `public_site/` em si.

### 3.3 Configurar Credenciais

Editar `/home/<user>/private/config.php`:

```php
<?php
// LIMA Solutions ERP - Private Credentials File
// NUNCA colocar este ficheiro dentro do public_html

$dbHost = getenv('DB_HOST') ?: 'localhost';       // normalmente localhost no Infomaniak
$dbName = getenv('DB_NAME') ?: 'lima_solutions';   // nome da base criada
$dbUser = getenv('DB_USER') ?: 'lima_user';        // utilizador MySQL criado
$dbPass = getenv('DB_PASS') ?: 'SENHA_SEGURA_AQUI';

define('SECURE_DB_HOST', $dbHost);
define('SECURE_DB_NAME', $dbName);
define('SECURE_DB_USER', $dbUser);
define('SECURE_DB_PASS', $dbPass);
```

> **Dica**: Para maior segurança em VPS, defina variáveis de ambiente no servidor em vez de hardcoded no ficheiro.

### 3.4 Importar o Schema da Base de Dados

**Via phpMyAdmin (Infomaniak):**

1. Aceder ao phpMyAdmin no painel Infomaniak
2. Selecionar a base `lima_solutions`
3. Aba **Importar** → Selecionar ficheiro: `db/schema.sql`
4. Formato: SQL → **Executar**
5. Verificar que todas as 24 tabelas foram criadas sem erros

### 3.5 Verificar Permissões de Ficheiros

```bash
# No servidor via SSH ou terminal Infomaniak
chmod 750 /home/<user>/private/
chmod 640 /home/<user>/private/config.php

# Pasta de uploads (se existir)
chmod 755 /home/<user>/private/storage/
chmod 755 /home/<user>/public_html/assets/
```

### 3.6 Ativar SSL / HTTPS

**No painel Infomaniak:**
1. Hosting → Certificados SSL → **Let's Encrypt** → Ativar para o domínio
2. Aguardar propagação (geralmente <5 minutos no Infomaniak)
3. Ativar **redirecionamento HTTP → HTTPS** automático

---

## 4. Inicialização do Sistema (Seeder)

> ⚠️ **Executar apenas uma vez, imediatamente após o deploy.**

Aceder via browser a:
```
https://seudominio.com/api/v1/seed.php
```

**Resultado esperado (texto plano):**
```
Sucesso!
LIMA Solutions ERP inicializado.
Empresa criada: LIMA Solutions (ID: 1)
Usuário Administrador de Teste Criado:
Login: admin@limasolutions.ch
Senha: lima2026
Perfil: super_admin

ATENÇÃO: O arquivo seed.php foi deletado automaticamente por segurança.
```

> O ficheiro `seed.php` auto-apaga-se após execução. Se precisar re-executar (em caso de erro), restaure o ficheiro via FTP antes de tentar novamente.

---

## 5. Primeiro Login e Configuração

1. Aceder a `https://seudominio.com/admin/login.php`
2. Login: `admin@limasolutions.ch` / `lima2026`
3. **Alterar imediatamente a senha** após o primeiro acesso
4. Configurar os dados da empresa em **Definições**
5. Ativar os módulos necessários para a empresa

---

## 6. Configuração de .htaccess

O sistema inclui `.htaccess` em diretórios sensíveis. Verificar que o Apache tem `AllowOverride All` ativo para o diretório do site. Em Infomaniak, isso já está configurado por padrão.

**Ficheiros .htaccess incluídos:**
- `db/.htaccess` → Bloqueia acesso HTTP a `.php` e `.sql`
- `api/.htaccess` → Bloqueia acesso direto a `config.php`

---

## 7. Verificações Pós-Deploy

```
[ ] Base de dados importada sem erros
[ ] Schema: 24 tabelas criadas
[ ] seed.php executado e auto-apagado
[ ] Login com admin@limasolutions.ch funciona
[ ] SSL ativo (cadeado verde no browser)
[ ] Redirecionamento HTTP → HTTPS automático
[ ] Acesso a /db/schema.sql retorna 403 Forbidden
[ ] Acesso a /api/v1/config.php retorna 403 Forbidden
[ ] Dashboard carrega sem erros PHP visíveis
[ ] Criar cliente de teste e verificar código gerado (CLI-000001)
[ ] Alterar senha do utilizador admin
```

---

## 8. Configurações Opcionais de Produção (VPS)

Para ambientes VPS com acesso ao `php.ini`:

```ini
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /home/<user>/private/logs/php_errors.log
session.cookie_secure = On
session.cookie_httponly = On
session.cookie_samesite = Strict
session.use_strict_mode = On
session.gc_maxlifetime = 7200
```

---

## 9. Suporte e Troubleshooting

| Problema | Causa provável | Solução |
|---|---|---|
| "Erro de conexão" no login | `private/config.php` incorreto | Verificar credenciais MySQL |
| "Arquivo de credenciais não encontrado" | `private/` no caminho errado | Verificar estrutura de diretórios |
| Seed retorna 403 | Ficheiro já foi executado | Normal – o seeder auto-apaga |
| Módulos não carregam | Schema incompleto | Re-importar `schema.sql` |
| HTTPS não funciona | SSL não ativo | Ativar Let's Encrypt no painel |
| Acesso 500 após migração | Coluna em falta | Executar migrações via CLI/SSH |

---

## 10. Execução de Migrações em Produção

As migrações **não podem ser executadas via HTTP** (bloqueadas por `.htaccess`). Use SSH:

```bash
# Conectar ao servidor via SSH
ssh utilizador@servidor.infomaniak.com

# Executar migração
php /home/<user>/public_html/db/migrate_v9.php
php /home/<user>/public_html/db/migrate_v9_1.php
```

> **Nota**: Em instalações novas com `schema.sql` completo, as migrações são desnecessárias. As migrações destinam-se a instâncias existentes que precisam de atualização incremental.
