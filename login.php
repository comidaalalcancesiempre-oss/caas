<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $map = ['ADMIN' => 'admin.php', 'EMPRESA' => 'empresa.php'];
    header('Location: ' . ($map[$_SESSION['tipo']] ?? 'index.php'));
    exit;
}

$error      = '';
$bloqueado  = false;
$segundos   = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Validar CSRF ───────────────────────────────────────────────
    if (!csrfValidar()) {
        $error = 'Solicitud inválida. Recargá la página e intentá de nuevo.';
    } else {
        $input    = trim($_POST['input']    ?? '');
        $password = $_POST['password']      ?? '';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // ── Rate limiting por IP ───────────────────────────────────
        // Máximo 5 intentos fallidos en 15 minutos
        if (checkRateLimit('login_' . $ip, 5, 900)) {
            $bloqueado = true;
            $segundos  = rateLimitTiempoRestante('login_' . $ip, 900);
            $error     = "Demasiados intentos. Esperá " . ceil($segundos / 60) . " minuto(s).";
        } elseif (!$input || !$password) {
            $error = 'Completá todos los campos.';
        } else {
            $stmt = $conn->prepare(
                "SELECT u.id_usuario, u.tipo,
                        c.id_cliente, c.nombre AS nombre_cliente, c.contrasena AS hash_cliente,
                        e.id_empresa, e.nombre AS nombre_empresa, e.contrasena AS hash_empresa,
                        e.estado_aprobacion
                 FROM   usuario u
                 LEFT JOIN cliente c ON u.id_usuario = c.id_usuario
                 LEFT JOIN empresa e ON u.id_usuario = e.id_usuario
                 WHERE  u.email = ? OR u.telefono = ?
                 LIMIT 1"
            );
            $stmt->execute([strtolower($input), $input]);
            $user = $stmt->fetch();

            if ($user) {
                $hash = ($user['tipo'] === 'EMPRESA') ? $user['hash_empresa'] : $user['hash_cliente'];

                if ($hash && password_verify($password, $hash)) {
                    // Login correcto — limpiar rate limit
                    resetRateLimit('login_' . $ip);

                    if ($user['tipo'] === 'EMPRESA') {
                        if ($user['estado_aprobacion'] === 'PENDIENTE') {
                            $error = 'empresa_pendiente';
                        } elseif ($user['estado_aprobacion'] === 'RECHAZADO') {
                            $error = 'empresa_rechazada';
                        }
                    }

                    if (!$error) {
                        // Regenerar ID de sesión previene Session Fixation
                        session_regenerate_id(true);
                        $_SESSION['user_id']    = $user['id_usuario'];
                        $_SESSION['tipo']       = $user['tipo'];
                        $_SESSION['login_time'] = time();

                        if ($user['tipo'] === 'ADMIN') {
                            $_SESSION['nombre'] = $user['nombre_cliente'] ?? 'Admin';
                            header('Location: admin.php'); exit;
                        } elseif ($user['tipo'] === 'EMPRESA') {
                            $_SESSION['id_empresa'] = $user['id_empresa'];
                            $_SESSION['nombre']     = $user['nombre_empresa'];
                            header('Location: empresa.php'); exit;
                        } else {
                            $_SESSION['id_cliente'] = $user['id_cliente'];
                            $_SESSION['nombre']     = $user['nombre_cliente'];
                            header('Location: index.php'); exit;
                        }
                    }
                } else {
                    $error = 'credenciales_invalidas';
                }
            } else {
                // Mismo mensaje para no revelar si el usuario existe
                $error = 'credenciales_invalidas';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script>const _t=localStorage.getItem('caas_tema')||'light';if(_t==='dark')document.documentElement.classList.add('dark');</script>
    <script src="lang.js"></script>
    <script src="theme.js"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeInUp .4s ease both; }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-gray-950 dark:to-gray-900 min-h-screen flex items-center justify-center p-4 transition-colors duration-300">

<div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-sm w-full p-8 border border-orange-100 dark:border-gray-800 fade-in">

    <!-- Header -->
    <div class="text-center mb-7">
        <a href="index.php" class="inline-block bg-orange-500 text-white font-black text-2xl px-5 py-1.5 rounded-xl shadow-lg hover:bg-orange-600 transition">C.A.A.S.</a>
        <h1 class="text-xl font-black text-gray-800 dark:text-white mt-3" data-i18n="login_title">Bienvenido de vuelta</h1>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" data-i18n="login_sub">Ingresá con tu email o teléfono</p>
    </div>

    <!-- Controles rápidos -->
    <div class="flex justify-center gap-2 mb-5">
        <button id="btnToggleTema" title="Cambiar tema"
                class="text-xl p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">🌙</button>
        <a href="ajustes.php" title="Ajustes"
           class="text-xl p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">⚙️</a>
    </div>

    <?php if (isset($_GET['registrado'])): ?>
        <div class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-bold p-3 rounded-xl mb-4 text-center flex items-center justify-center gap-2">
            <span>✓</span> <span data-i18n="login_success">¡Registro exitoso! Ya podés ingresar.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['reset'])): ?>
        <div class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-bold p-3 rounded-xl mb-4 text-center">
            ✓ Contraseña actualizada. Ya podés ingresar.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold p-3 rounded-xl mb-4 text-center">
            <?php
            echo match($error) {
                'empresa_pendiente'    => 'Tu empresa está pendiente de aprobación por el administrador.',
                'empresa_rechazada'    => 'Tu solicitud fue rechazada. Contactá al administrador.',
                'credenciales_invalidas' => 'Email, teléfono o contraseña incorrectos.',
                default                => e($error),
            };
            ?>
        </div>
    <?php endif; ?>

    <?php if (!$bloqueado): ?>
    <form method="POST" class="space-y-4" id="formLogin">
        <?= csrfField() ?>

        <div class="space-y-1">
            <input type="text" name="input" id="inputLogin"
                   data-i18n-ph="login_input_ph"
                   placeholder="Email o Teléfono" required autocomplete="username"
                   class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 dark:bg-gray-800 dark:text-white transition">
        </div>

        <div class="relative">
            <input type="password" id="passInput" name="password"
                   data-i18n-ph="login_pass_ph"
                   placeholder="Contraseña" required autocomplete="current-password"
                   class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 dark:bg-gray-800 dark:text-white transition pr-11">
            <button type="button"
                    onclick="const i=document.getElementById('passInput');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'👁':'🙈'"
                    class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 text-base select-none">👁</button>
        </div>

        <!-- Indicador de fuerza de contraseña visual -->
        <div id="passStrength" class="hidden h-1 rounded-full bg-gray-200 dark:bg-gray-700">
            <div id="passBar" class="h-full rounded-full transition-all duration-300 w-0 bg-red-400"></div>
        </div>

        <button type="submit" id="btnLogin"
                class="w-full bg-orange-500 hover:bg-orange-600 active:scale-95 text-white font-bold py-3 rounded-xl transition shadow-lg text-sm"
                data-i18n="login_btn">Entrar</button>
    </form>

    <!-- Recuperar contraseña -->
    <div class="mt-4 text-center">
        <a href="recuperar.php"
           class="text-xs text-orange-500 hover:underline font-bold">¿Olvidaste tu contraseña?</a>
    </div>

    <div class="mt-3 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <span data-i18n="login_no_acc">¿No tenés cuenta?</span>
            <a href="registro.php" class="text-orange-500 font-bold hover:underline ml-1" data-i18n="login_register">Registrarse</a>
        </p>
    </div>
    <?php else: ?>
    <!-- Cuenta bloqueada por intentos -->
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-5 text-center space-y-3">
        <p class="text-3xl">🔒</p>
        <p class="font-bold text-red-700 dark:text-red-400 text-sm">Acceso temporalmente bloqueado</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Demasiados intentos fallidos.</p>
        <div id="countdown" class="text-2xl font-black text-red-600 dark:text-red-400"></div>
        <p class="text-xs text-gray-400">El bloqueo se libera automáticamente.</p>
    </div>
    <script>
    let secs = <?= (int)$segundos ?>;
    const el = document.getElementById('countdown');
    function tick() {
        if (secs <= 0) { location.reload(); return; }
        const m = Math.floor(secs/60), s = secs%60;
        el.textContent = `${m}:${s.toString().padStart(2,'0')}`;
        secs--; setTimeout(tick, 1000);
    }
    tick();
    </script>
    <?php endif; ?>
</div>

<script>
// Mostrar barra de fuerza de contraseña
document.getElementById('passInput')?.addEventListener('input', function() {
    const bar = document.getElementById('passBar');
    const wrap = document.getElementById('passStrength');
    const v = this.value;
    if (!v) { wrap.classList.add('hidden'); return; }
    wrap.classList.remove('hidden');
    let score = 0;
    if (v.length >= 8)  score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const colors = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-green-400'];
    const widths = ['w-1/4','w-2/4','w-3/4','w-full'];
    bar.className = `h-full rounded-full transition-all duration-300 ${colors[score-1]||'bg-red-400'} ${widths[score-1]||'w-1/4'}`;
});
</script>
</body>
</html>
