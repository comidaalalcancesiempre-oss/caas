<?php
// ── Entorno ────────────────────────────────────────────────────────
// 'development' muestra errores en pantalla
// 'production'  los oculta y los guarda en log
define('APP_ENV', 'development');

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
define('DB_HOST', 'localhost');
define('DB_NAME', 'caas_v2');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $conn = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
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
// Solo en respuestas HTML (no en endpoints JSON)
if (!defined('JSON_ENDPOINT')) {
    // Impide que la página sea embebida en un iframe (clickjacking)
    header('X-Frame-Options: SAMEORIGIN');
    // Impide que el navegador "adivine" el tipo de contenido
    header('X-Content-Type-Options: nosniff');
    // Activa protección XSS en navegadores viejos
    header('X-XSS-Protection: 1; mode=block');
    // Controla qué información se envía en el header Referer
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Content Security Policy básica: solo cargar recursos de nuestro dominio + CDNs permitidos
    header("Content-Security-Policy: default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com; " .
           "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://cdnjs.cloudflare.com; " .
           "img-src 'self' data: https://placehold.co https://*.tile.openstreetmap.org; " .
           "connect-src 'self'; font-src 'self' data:;");
}

// ── Helpers de seguridad ───────────────────────────────────────────

/**
 * Sanitiza salida HTML — siempre usar al mostrar datos del usuario
 */
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Genera un token CSRF único por sesión
 * CSRF = Cross-Site Request Forgery: atacante engaña al usuario para
 * que su navegador envíe un formulario malicioso sin saberlo.
 * El token previene esto: el servidor verifica que el formulario
 * vino de nuestra propia página, no de un sitio externo.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        // bin2hex(random_bytes(32)) genera 64 caracteres aleatorios criptográficamente seguros
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF enviado en un formulario
 * hash_equals() previene timing attacks (comparación en tiempo constante)
 */
function csrfValidar(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Campo oculto HTML con el token CSRF — insertar en todo formulario POST
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Rate limiting: limita intentos de login para prevenir fuerza bruta
 * Máximo $max intentos en $ventana segundos
 */
function checkRateLimit(string $key, int $max = 5, int $ventana = 900): bool {
    $sessionKey = 'rl_' . md5($key);
    $now        = time();

    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = ['intentos' => 0, 'desde' => $now];
    }

    // Reiniciar si pasó la ventana de tiempo (15 min por defecto)
    if ($now - $_SESSION[$sessionKey]['desde'] > $ventana) {
        $_SESSION[$sessionKey] = ['intentos' => 0, 'desde' => $now];
    }

    $_SESSION[$sessionKey]['intentos']++;

    // Devuelve true si superó el límite (bloqueado)
    return $_SESSION[$sessionKey]['intentos'] > $max;
}

/**
 * Cuántos segundos faltan para que se libere el rate limit
 */
function rateLimitTiempoRestante(string $key, int $ventana = 900): int {
    $sessionKey = 'rl_' . md5($key);
    if (!isset($_SESSION[$sessionKey])) return 0;
    $restante = $ventana - (time() - $_SESSION[$sessionKey]['desde']);
    return max(0, $restante);
}

/**
 * Limpia los intentos de rate limit (después de login exitoso)
 */
function resetRateLimit(string $key): void {
    unset($_SESSION['rl_' . md5($key)]);
}
