<?php
// ── Cargar dependencias de Composer ───────────────────────────────
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use voku\helper\AntiXSS;

// ── Cargar variables de entorno desde .env ─────────────────────────
// phpdotenv lee el archivo .env y los pone en $_ENV y getenv()
// Las credenciales ya NO están hardcodeadas en el código
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Valores requeridos — si falta alguno lanza excepción
$dotenv->required(['APP_ENV', 'DB_HOST', 'DB_NAME', 'DB_USER']);

// ── Entorno ────────────────────────────────────────────────────────
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/app_errors.log');
}

// ── Conexión a la base de datos ────────────────────────────────────
// Las credenciales vienen del .env, no del código fuente
try {
    $conn = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4',
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'] ?? '',
        [
            // Si hay un error SQL, lanzar una excepción (para poder atraparla con try/catch)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Los resultados vienen como arrays asociativos
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Prepared statements reales del driver MySQL (no emulados)
            // Esto es lo que realmente previene SQL injection a nivel de motor
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        die('<div style="font-family:sans-serif;color:red;padding:20px;border:2px solid red;margin:20px">
             <h3>Error de conexión BD</h3><p>' . htmlspecialchars($e->getMessage()) . '</p></div>');
    }
    die('<p style="font-family:sans-serif;padding:20px">Servicio no disponible. Intente más tarde.</p>');
}

// ── Sesión segura ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // cambiar a true en HTTPS
        'httponly' => true,    // la cookie NO es accesible desde JS (protege XSS)
        'samesite' => 'Lax',   // protege contra CSRF básico
    ]);
    session_start();
}

// ── Headers de seguridad HTTP ──────────────────────────────────────
if (!defined('JSON_ENDPOINT')) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // CSP actualizada para incluir Bootstrap, Animate.css, SweetAlert2, Lucide
    header("Content-Security-Policy: default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' " .
               "https://cdn.tailwindcss.com " .
               "https://unpkg.com " .
               "https://cdn.jsdelivr.net " .
               "https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' " .
               "https://cdn.tailwindcss.com " .
               "https://unpkg.com " .
               "https://cdn.jsdelivr.net " .
               "https://cdnjs.cloudflare.com; " .
           "img-src 'self' data: https://placehold.co https://*.tile.openstreetmap.org; " .
           "connect-src 'self'; font-src 'self' data:;");
}

// ── Instancia global de AntiXSS (voku/anti-xss) ───────────────────
// Más potente que htmlspecialchars solo:
// detecta y neutraliza payloads XSS complejos como:
//   <img src=x onerror=alert(1)>
//   javascript:alert(1)
//   &#60;script&#62;
// Se usa con la función xss() definida abajo
$antiXss = new AntiXSS();

// ── Helpers de seguridad ───────────────────────────────────────────

/**
 * Sanitiza salida HTML — para mostrar datos en pantalla
 * Usa htmlspecialchars nativo de PHP
 */
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Limpia input del usuario con voku/anti-xss + htmlspecialchars
 * Usar en campos de texto libre donde el usuario puede escribir lo que quiera
 * AntiXSS neutraliza ataques XSS complejos que filter_input no detecta
 */
function xss(string $v): string {
    global $antiXss;
    // Primero AntiXSS neutraliza payloads maliciosos
    $limpio = $antiXss->xss_clean($v);
    // Luego htmlspecialchars escapa los caracteres HTML restantes
    return htmlspecialchars(trim($limpio), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Genera un token CSRF único por sesión
 * Previene Cross-Site Request Forgery (formularios maliciosos de otros sitios)
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        // bin2hex(random_bytes(32)) = 64 chars aleatorios criptográficamente seguros
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF
 * hash_equals() previene timing attacks
 */
function csrfValidar(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Campo oculto HTML con token CSRF — agregar en todo formulario POST
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Rate limiting por clave — previene fuerza bruta
 * Máximo $max intentos en $ventana segundos
 */
function checkRateLimit(string $key, int $max = 5, int $ventana = 900): bool {
    $sessionKey = 'rl_' . md5($key);
    $now        = time();
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = ['intentos' => 0, 'desde' => $now];
    }
    if ($now - $_SESSION[$sessionKey]['desde'] > $ventana) {
        $_SESSION[$sessionKey] = ['intentos' => 0, 'desde' => $now];
    }
    $_SESSION[$sessionKey]['intentos']++;
    return $_SESSION[$sessionKey]['intentos'] > $max;
}

function rateLimitTiempoRestante(string $key, int $ventana = 900): int {
    $sessionKey = 'rl_' . md5($key);
    if (!isset($_SESSION[$sessionKey])) return 0;
    return max(0, $ventana - (time() - $_SESSION[$sessionKey]['desde']));
}

function resetRateLimit(string $key): void {
    unset($_SESSION['rl_' . md5($key)]);
}
