<?php
require_once 'config.php';
require_once 'mailer.php';

$mensaje  = '';
$tipo_msg = 'ok';
$enviado  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!csrfValidar()) {
        $mensaje = 'Solicitud inválida. Recargá la página.';
        $tipo_msg = 'err';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Rate limit: máximo 3 recuperaciones por IP cada 15 minutos
        if (checkRateLimit('recover_' . $ip, 3, 900)) {
            $mensaje  = 'Demasiados intentos. Esperá unos minutos.';
            $tipo_msg = 'err';
        } elseif (!$email) {
            $mensaje  = 'Ingresá un email válido.';
            $tipo_msg = 'err';
        } else {
            // Buscar usuario por email
            $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1");
            $stmt->execute([strtolower($email)]);
            $user = $stmt->fetch();

            // SIEMPRE mostrar el mismo mensaje (no revelar si el email existe)
            $enviado = true;

            if ($user) {
                // Generar token criptográficamente seguro (64 chars hex = 256 bits)
                $token_raw  = bin2hex(random_bytes(32));
                // Guardar solo el HASH en la BD — si la BD se filtra, el token no sirve
                $token_hash = hash('sha256', $token_raw);
                // Expiración en 30 minutos
                $expira     = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                // Borrar tokens previos del mismo usuario
                $conn->prepare("DELETE FROM reset_password WHERE id_usuario = ?")
                     ->execute([$user['id_usuario']]);

                // Guardar el nuevo token hasheado
                $conn->prepare(
                    "INSERT INTO reset_password (id_usuario, token_hash, expira_en) VALUES (?,?,?)"
                )->execute([$user['id_usuario'], $token_hash, $expira]);

                // URL con el token RAW
                $link = "http://localhost/caas_nuevo/reset_password.php?token={$token_raw}&email=" . urlencode($email);

                // Enviar email AL USUARIO (no al admin)
                enviarEmailRecuperacion($email, $link);
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
    <title>Recuperar Contraseña - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class'}</script>
    <script>const _t=localStorage.getItem('caas_tema')||'light';if(_t==='dark')document.documentElement.classList.add('dark');</script>
    <script src="lang.js"></script><script src="theme.js"></script>
    <style>@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}.fade-in{animation:fadeInUp .4s ease both}</style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-gray-950 dark:to-gray-900 min-h-screen flex items-center justify-center p-4">

<div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-sm w-full p-8 border border-orange-100 dark:border-gray-800 fade-in">

    <div class="text-center mb-6">
        <a href="index.php" class="inline-block bg-orange-500 text-white font-black text-2xl px-5 py-1.5 rounded-xl shadow-lg">C.A.A.S.</a>
        <h1 class="text-xl font-black text-gray-800 dark:text-white mt-3">Recuperar contraseña</h1>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Te enviamos un link a tu email</p>
    </div>

    <!-- Controles rápidos -->
    <div class="flex justify-center gap-2 mb-5">
        <button id="btnToggleTema" class="text-xl p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">🌙</button>
        <a href="ajustes.php" class="text-xl p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">⚙️</a>
    </div>

    <?php if ($enviado): ?>
        <!-- Mensaje de confirmación — mismo mensaje sin importar si el email existe -->
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 text-center space-y-3">
            <p class="text-4xl">📧</p>
            <p class="font-bold text-green-700 dark:text-green-400">¡Revisá tu email!</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Si ese email está registrado, vas a recibir un link para restablecer tu contraseña en los próximos minutos.</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">El link expira en 30 minutos.</p>
        </div>
        <div class="mt-4 text-center">
            <a href="login.php" class="text-sm font-bold text-orange-500 hover:underline">← Volver al login</a>
        </div>

    <?php else: ?>
        <?php if ($mensaje): ?>
            <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold p-3 rounded-xl mb-4 text-center">
                <?= e($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Email de tu cuenta</label>
                <input type="email" name="email" required
                       placeholder="tucorreo@email.com"
                       class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 dark:bg-gray-800 dark:text-white">
            </div>
            <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-lg transition text-sm">
                Enviar link de recuperación
            </button>
        </form>

        <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-4">
            <a href="login.php" class="text-orange-500 font-bold hover:underline">← Volver al login</a>
        </p>
    <?php endif; ?>
</div>
</body>
</html>
