<?php
require_once 'config.php';

$token_raw = trim($_GET['token'] ?? '');
$email     = strtolower(trim($_GET['email'] ?? ''));
$error     = '';
$exito     = false;

// Validar que el token tenga el formato correcto (64 chars hex)
if (!$token_raw || !preg_match('/^[a-f0-9]{64}$/', $token_raw) || !$email) {
    $error = 'Link inválido o expirado.';
} else {
    // Hashear el token recibido y buscarlo en la BD
    $token_hash = hash('sha256', $token_raw);

    $stmt = $conn->prepare(
        "SELECT r.id, r.id_usuario, r.expira_en, r.usado
         FROM   reset_password r
         JOIN   usuario        u ON r.id_usuario = u.id_usuario
         WHERE  r.token_hash = ? AND u.email = ?
         LIMIT  1"
    );
    $stmt->execute([$token_hash, $email]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = 'Link inválido. Solicitá uno nuevo.';
    } elseif ($reset['usado']) {
        $error = 'Este link ya fue usado. Solicitá uno nuevo.';
    } elseif (strtotime($reset['expira_en']) < time()) {
        $error = 'El link expiró (válido por 30 minutos). Solicitá uno nuevo.';
    }
}

// Procesar nueva contraseña
if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidar()) {
        $error = 'Solicitud inválida.';
    } else {
        $nueva      = $_POST['password']         ?? '';
        $confirmar  = $_POST['password_confirm'] ?? '';

        if (strlen($nueva) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);

            // Determinar en qué tabla guardar según el tipo de usuario
            $tipo_stmt = $conn->prepare("SELECT tipo FROM usuario WHERE id_usuario = ?");
            $tipo_stmt->execute([$reset['id_usuario']]);
            $tipo = $tipo_stmt->fetchColumn();

            $conn->beginTransaction();
            try {
                if ($tipo === 'EMPRESA') {
                    $conn->prepare("UPDATE empresa SET contrasena = ? WHERE id_usuario = ?")
                         ->execute([$hash, $reset['id_usuario']]);
                } else {
                    $conn->prepare("UPDATE cliente SET contrasena = ? WHERE id_usuario = ?")
                         ->execute([$hash, $reset['id_usuario']]);
                }

                // Marcar el token como usado para que no pueda reutilizarse
                $conn->prepare("UPDATE reset_password SET usado = 1 WHERE id = ?")
                     ->execute([$reset['id']]);

                $conn->commit();
                $exito = true;

                // Redirigir al login con mensaje de éxito
                header('Location: login.php?reset=1');
                exit;

            } catch (PDOException $e) {
                $conn->rollBack();
                error_log('reset_password: ' . $e->getMessage());
                $error = 'Error al actualizar la contraseña. Intentá de nuevo.';
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
    <title>Nueva Contraseña - C.A.A.S.</title>
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
        <h1 class="text-xl font-black text-gray-800 dark:text-white mt-3">Nueva contraseña</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-5 text-center space-y-3">
            <p class="text-3xl">⚠️</p>
            <p class="font-bold text-red-700 dark:text-red-400 text-sm"><?= e($error) ?></p>
            <a href="recuperar.php" class="inline-block text-xs font-bold bg-orange-500 text-white px-4 py-2 rounded-xl hover:bg-orange-600 transition">
                Solicitar nuevo link
            </a>
        </div>
    <?php else: ?>
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="token" value="<?= e($token_raw) ?>">
            <input type="hidden" name="email" value="<?= e($email) ?>">

            <div class="relative">
                <input type="password" id="p1" name="password" required
                       placeholder="Nueva contraseña (mín. 8 caracteres)"
                       class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 dark:bg-gray-800 dark:text-white pr-11">
                <button type="button"
                        onclick="const i=document.getElementById('p1');i.type=i.type==='password'?'text':'password'"
                        class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 text-base">👁</button>
            </div>

            <!-- Barra de fuerza -->
            <div id="passStrength" class="hidden h-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
                <div id="passBar" class="h-full rounded-full transition-all duration-300 w-0 bg-red-400"></div>
            </div>
            <p id="passHint" class="text-xs text-gray-400 hidden">Usa mayúsculas, números y símbolos para mayor seguridad.</p>

            <div class="relative">
                <input type="password" id="p2" name="password_confirm" required
                       placeholder="Repetir nueva contraseña"
                       class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 dark:bg-gray-800 dark:text-white pr-11">
                <span id="matchIcon" class="absolute right-3 top-3 text-sm hidden"></span>
            </div>

            <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-lg transition text-sm">
                Guardar nueva contraseña
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
// Barra de fuerza
document.getElementById('p1')?.addEventListener('input', function() {
    const bar = document.getElementById('passBar');
    const wrap = document.getElementById('passStrength');
    const hint = document.getElementById('passHint');
    const v = this.value;
    if (!v) { wrap.classList.add('hidden'); hint.classList.add('hidden'); return; }
    wrap.classList.remove('hidden'); hint.classList.remove('hidden');
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const colors = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-green-500'];
    const widths = ['w-1/4','w-2/4','w-3/4','w-full'];
    bar.className = `h-full rounded-full transition-all duration-300 ${colors[score-1]||'bg-red-400'} ${widths[score-1]||'w-1/4'}`;
    matchPasswords();
});

// Verificar que coincidan
function matchPasswords() {
    const p1 = document.getElementById('p1').value;
    const p2 = document.getElementById('p2').value;
    const icon = document.getElementById('matchIcon');
    if (!p2) { icon.classList.add('hidden'); return; }
    icon.classList.remove('hidden');
    icon.textContent = p1 === p2 ? '✅' : '❌';
}
document.getElementById('p2')?.addEventListener('input', matchPasswords);
</script>
</body>
</html>
