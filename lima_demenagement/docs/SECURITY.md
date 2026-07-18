# LIMA Solutions ERP – Security Guide

> Versão: RC 1.0 | Última revisão: Junho 2026

---

## 1. Modelo de Sessão

### Configuração de Cookies (api/v1/config.php)

```php
ini_set('session.cookie_httponly', '1');   // Inacessível via JavaScript
ini_set('session.use_only_cookies', '1');  // Sem session ID na URL
ini_set('session.cookie_samesite', 'Strict'); // Bloqueia requisições cross-site
ini_set('session.cookie_secure', $isHttps ? '1' : '0'); // Auto-detect HTTPS
ini_set('session.use_strict_mode', '1');   // Rejeita IDs de sessão não iniciados
ini_set('session.gc_maxlifetime', '7200'); // Timeout: 2 horas de inatividade
```

### Detecção Automática de HTTPS

```php
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
         || (getenv('APP_HTTPS') === 'true');
```

Permite funcionamento correto em:
- Desenvolvimento local (HTTP) — `cookie_secure = 0`
- Produção (HTTPS) — `cookie_secure = 1`
- Reverse proxy / CDN — via `HTTP_X_FORWARDED_PROTO`

---

## 2. Proteção CSRF

Todas as operações mutativas (POST, PUT, DELETE) exigem um token CSRF válido.

### Geração do Token

```php
// Gerado uma vez por sessão, em config.php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

### Validação nas APIs

```php
if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    $clientToken  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!hash_equals($sessionToken, $clientToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Erro CSRF.']);
        exit();
    }
}
```

> `hash_equals()` previne timing attacks na comparação de tokens.

### Envio pelo Frontend

O token é obtido via `/api/v1/session.php` e incluído em todos os requests:
```javascript
headers: { 'X-CSRF-Token': csrfToken }
```

---

## 3. Headers de Segurança HTTP

A função `sendSecurityHeaders()` (em `config.php`) é chamada em todos os entry points:

| Header | Valor | Propósito |
|---|---|---|
| `X-Frame-Options` | `DENY` | Previne clickjacking |
| `X-Content-Type-Options` | `nosniff` | Previne MIME sniffing |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limita vazamento de referrer |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` | Desativa features desnecessárias |
| `Content-Security-Policy` | (ver abaixo) | Previne XSS e injeção de recursos |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Força HTTPS (apenas quando ativo) |

**CSP aplicada:**
```
default-src 'self';
script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com;
style-src 'self' 'unsafe-inline' fonts.googleapis.com cdn.jsdelivr.net;
font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com;
img-src 'self' data: blob:;
connect-src 'self';
frame-ancestors 'none';
```

---

## 4. Autenticação e Autorização

### Middleware (admin/auth.php)

1. Verifica `$_SESSION['user_id']` — redireciona para login se ausente
2. Verifica `company_id` ativo:
   - `super_admin` sem empresa → redireciona para `select_company.php`
   - outros roles → logout + redireciona para `login.php?error=no_company`
3. Chama `sendSecurityHeaders()` em todas as páginas protegidas

### Controlo de Módulos (modules_helper.php)

```php
enforceModuleAccess('timesheets', $userRole, $companyId, 'view', $pdo);
```

Verifica:
- Se o módulo está ativado para a empresa (`company_modules`)
- Se o role tem permissão de acesso (`module_permissions`)
- Retorna `403 Forbidden` caso contrário

---

## 5. Isolamento Multi-empresa

**Regra absoluta**: Toda query que acede dados de negócio DEVE filtrar por `company_id`:

```php
// ✅ Correto
$stmt = $pdo->prepare("SELECT * FROM timesheets WHERE id = :id AND company_id = :cid");
$stmt->execute(['id' => $id, 'cid' => $companyId]);

// ❌ Errado — permite acesso cross-company
$stmt = $pdo->prepare("SELECT * FROM timesheets WHERE id = :id");
```

O `company_id` é sempre obtido via `getActiveCompanyId()` — nunca via input do utilizador.

---

## 6. Imutabilidade de Timesheets

### Regra de Bloqueio

Um timesheet é **absolutamente imutável** quando qualquer uma das condições é verdadeira:

```php
$isLocked = ($existing['status'] === 'Approved')
          || ($existing['locked'] == 1)
          || ($existing['invoice_id'] !== null);
```

### Resposta de Erro

```json
HTTP 409 Conflict
{
  "success": false,
  "message": "Ce relevé de temps est approuvé, facturé ou verrouillé et ne peut pas être modifié."
}
```

---

## 7. Snapshots Financeiros

No momento da aprovação de um timesheet, os valores de taxa são **congelados**:

```php
'approved_hourly_cost'   => $existing['hourly_rate'],   // custo interno
'approved_billable_rate' => $existing['hourly_rate'],   // taxa faturável
```

**Regra de faturação**: A conversão para fatura usa **exclusivamente** `approved_billable_rate` e `approved_hourly_cost`. Nenhuma tarifa atual de projeto, cliente ou colaborador é consultada.

---

## 8. Prevenção de SQL Injection

Todas as queries utilizam **PDO com prepared statements parametrizados**:

```php
// ✅ Sempre assim
$stmt = $pdo->prepare("SELECT * FROM clients WHERE company_id = :cid AND id = :id");
$stmt->execute(['cid' => $companyId, 'id' => $id]);

// ❌ Jamais concatenação direta
$pdo->query("SELECT * FROM clients WHERE id = " . $_GET['id']); // PROIBIDO
```

---

## 9. Gestão de Erros em Produção

### Configuração

```php
ini_set('display_errors', '0');     // Nunca exibe erros ao utilizador
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');         // Regista no log do servidor
```

### APIs

Erros internos são registados via `error_log()` e retornam apenas mensagens genéricas:

```php
} catch (Exception $e) {
    error_log('[LIMA][modulo] Contexto: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}
```

---

## 10. Proteção de Ficheiros Sensíveis

### Via .htaccess

| Diretório / Ficheiro | Proteção |
|---|---|
| `db/*.php`, `db/*.sql` | `Deny from all` (bloqueia HTTP, apenas CLI) |
| `api/config.php` | `<Files> Deny from all` |
| `private/` (fora do public_html) | Inacessível via HTTP por design |

---

## 11. Palavras-Passe

- Armazenadas como **bcrypt hash** via `password_hash($pass, PASSWORD_DEFAULT)`
- Verificadas via `password_verify()`
- O seeder usa uma senha temporária (`lima2026`) que **deve ser alterada imediatamente** após o primeiro login

---

## 12. Checklist de Segurança Pré-Produção

```
[ ] display_errors = Off confirmado
[ ] SSL/HTTPS ativo e redirecionamento HTTP→HTTPS configurado
[ ] Senha padrão (lima2026) alterada
[ ] seed.php auto-apagado após inicialização
[ ] private/ fora do public_html
[ ] Acesso a /db/schema.sql retorna 403
[ ] Acesso a /api/v1/config.php retorna 403
[ ] Headers de segurança presentes (verificar com https://securityheaders.com)
[ ] Logs de PHP configurados para ficheiro (não para ecrã)
```
