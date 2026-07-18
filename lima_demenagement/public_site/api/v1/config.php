<?php
// LIMA Solutions ERP - API Configuration V1
// Loads secure credentials from private directory.
header('X-Content-Type-Options: nosniff');


// ─── Production Error Handling ───────────────────────────────────────────────
// Never expose errors to the browser; always log server-side.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ─── HTTPS Auto-detection ────────────────────────────────────────────────────
// Detect HTTPS from standard server variables or reverse-proxy headers.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
         || (getenv('APP_HTTPS') === 'true');

// ─── Session Security ────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', '7200'); // 2-hour idle timeout

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Load Private Credentials ────────────────────────────────────────────────
$privateConfigPath = __DIR__ . '/../../../private_lima/config.php';
if (!file_exists($privateConfigPath)) {
    $privateConfigPath = __DIR__ . '/../../../private/config.php';
}

if (!file_exists($privateConfigPath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro crítico: configuração do servidor indisponível.'
    ]);
    exit();
}
require_once $privateConfigPath;

// ─── Database Connection ─────────────────────────────────────────────────────
try {
    $dsn = "mysql:host=" . SECURE_DB_HOST . ";dbname=" . SECURE_DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, SECURE_DB_USER, SECURE_DB_PASS, $options);
} catch (PDOException $e) {
    error_log('[LIMA][config] DB connection failed: ' . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com o banco de dados.'
    ]);
    exit();
}

// ─── Security Headers ────────────────────────────────────────────────────────
/**
 * Sends a hardened set of HTTP security headers.
 * Must be called before any output in entry-point files (admin, api).
 */
function sendSecurityHeaders() {
    global $isHttps;

    // Prevent clickjacking
    header('X-Frame-Options: DENY');

    // Prevent MIME-type sniffing
    header('X-Content-Type-Options: nosniff');

    // Limit referrer information leakage
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Restrict browser features
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // Content Security Policy – tighten as needed per environment
    $csp  = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ";
    $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ";
    $csp .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; ";
    $csp .= "img-src 'self' data: blob:; ";
    $csp .= "connect-src 'self' https://cdn.jsdelivr.net; ";
    $csp .= "frame-ancestors 'none';";
    header('Content-Security-Policy: ' . $csp);

    // HSTS – only set over real HTTPS (not local dev)
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ─── Active Company Scope Helper ─────────────────────────────────────────────
/**
 * Returns the active company_id for the current session.
 * Returns null if the user has no active company context.
 */
function getActiveCompanyId() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    // Super admins may switch company via session override
    if (
        isset($_SESSION['user_role']) &&
        $_SESSION['user_role'] === 'super_admin' &&
        isset($_SESSION['selected_company_id'])
    ) {
        return (int)$_SESSION['selected_company_id'];
    }
    // All other users: use their primary company assignment
    return isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null;
}

// ─── Global Error & Exception Handlers for Observability ─────────────────────
require_once __DIR__ . '/../../helpers/ObservabilityHelper.php';

set_exception_handler(function($exception) {
    ObservabilityHelper::log(
        $exception->getMessage(),
        'EXCEPTION',
        'CRITICAL',
        [
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]
    );
    
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno do servidor (Observability Exception Handled).'
        ]);
        exit();
    }
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $severity = 'ERROR';
    if ($errno === E_WARNING || $errno === E_USER_WARNING) {
        $severity = 'WARNING';
    } elseif ($errno === E_NOTICE || $errno === E_USER_NOTICE) {
        $severity = 'INFO';
    }
    
    ObservabilityHelper::log(
        $errstr,
        'API_ERROR',
        $severity,
        [
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline
        ]
    );
    
    if ($errno === E_ERROR || $errno === E_USER_ERROR) {
        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro fatal de execução (Observability Error Handled).'
            ]);
            exit();
        }
    }
    return false;
});
