<?php
// ── Entorno ────────────────────────────────────────────────────────
define('APP_ENV', 'development'); // cambiar a 'production' al desplegar

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/app_errors.log');
}

// ── Conexión ───────────────────────────────────────────────────────
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
             <h3>Error de conexión</h3><p>' . htmlspecialchars($e->getMessage()) . '</p></div>');
    }
    die('<p style="font-family:sans-serif;padding:20px">Servicio no disponible. Intente más tarde.</p>');
}

// ── Sesión segura ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // true en HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Helper anti-XSS ───────────────────────────────────────────────
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
