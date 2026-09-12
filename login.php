<?php
require_once 'config.php';

// Ya tiene sesión → redirigir
if (isset($_SESSION['user_id'])) {
    $map = ['ADMIN' => 'admin.php', 'EMPRESA' => 'empresa.php'];
    header('Location: ' . ($map[$_SESSION['tipo']] ?? 'index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input    = trim($_POST['input']     ?? '');
    $password = trim($_POST['password']  ?? '');

    if (!$input || !$password) {
        $error = 'Completá todos los campos.';
    } else {
        // Buscar por email O teléfono en tabla usuario
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
            // Seleccionar el hash según el rol
            $hash = ($user['tipo'] === 'EMPRESA') ? $user['hash_empresa'] : $user['hash_cliente'];

            if ($hash && password_verify($password, $hash)) {
                // Verificar aprobación para empresas
                if ($user['tipo'] === 'EMPRESA') {
                    if ($user['estado_aprobacion'] === 'PENDIENTE') {
                        $error = 'Tu empresa está pendiente de aprobación por el administrador.';
                    } elseif ($user['estado_aprobacion'] === 'RECHAZADO') {
                        $error = 'Tu solicitud fue rechazada. Contactá al administrador.';
                    }
                }

                if (!$error) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id_usuario'];
                    $_SESSION['tipo']    = $user['tipo'];

                    if ($user['tipo'] === 'ADMIN') {
                        $_SESSION['nombre'] = $user['nombre_cliente'] ?? 'Admin';
                        header('Location: admin.php');
                    } elseif ($user['tipo'] === 'EMPRESA') {
                        $_SESSION['id_empresa'] = $user['id_empresa'];
                        $_SESSION['nombre']     = $user['nombre_empresa'];
                        header('Location: empresa.php');
                    } else {
                        $_SESSION['id_cliente'] = $user['id_cliente'];
                        $_SESSION['nombre']     = $user['nombre_cliente'];
                        header('Location: index.php');
                    }
                    exit;
                }
            } else {
                $error = 'Email, teléfono o contraseña incorrectos.';
            }
        } else {
            $error = 'Email, teléfono o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-orange-50 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-8 border border-orange-100">

    <div class="text-center mb-7">
        <a href="index.php" class="inline-block bg-orange-500 text-white font-black text-2xl px-5 py-1.5 rounded-xl tracking-wide">C.A.A.S.</a>
        <h1 class="text-xl font-black text-gray-800 mt-3">Bienvenido de vuelta</h1>
        <p class="text-xs text-gray-400 mt-1">Ingresá con tu email o teléfono</p>
    </div>

    <?php if (isset($_GET['registrado'])): ?>
        <div class="bg-green-100 text-green-700 text-xs font-bold p-3 rounded-xl mb-4 text-center">
            ✓ ¡Registro exitoso! Ya podés ingresar.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 text-xs font-bold p-3 rounded-xl mb-4 text-center">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <input type="text" name="input"
               placeholder="Email o Teléfono" required autocomplete="username"
               class="w-full p-3 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50">

        <div class="relative">
            <input type="password" id="pass" name="password"
                   placeholder="Contraseña" required autocomplete="current-password"
                   class="w-full p-3 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 pr-11">
            <button type="button" onclick="document.getElementById('pass').type = document.getElementById('pass').type==='password'?'text':'password'"
                    class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 text-base select-none">👁</button>
        </div>

        <button type="submit"
                class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition shadow-md text-sm">
            Entrar
        </button>
    </form>

    <p class="text-center text-xs text-gray-500 mt-5">
        ¿No tenés cuenta?
        <a href="registro.php" class="text-orange-500 font-bold hover:underline">Registrarse</a>
    </p>
</div>
</body>
</html>
