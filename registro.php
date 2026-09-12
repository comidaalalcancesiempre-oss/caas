<?php
require_once 'config.php';

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo     = $_POST['tipo']    ?? '';
    $nombre   = trim(filter_input(INPUT_POST, 'nombre',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $password = $_POST['password'] ?? '';

    // ── Validaciones comunes ───────────────────────────────────────
    if (!in_array($tipo, ['CLIENTE', 'EMPRESA'], true)) {
        $error = 'Seleccioná un tipo de usuario.';
    } elseif (!$nombre) {
        $error = 'El nombre es obligatorio.';
    } elseif (!$email) {
        $error = 'Ingresá un email válido.';
    } elseif (!$telefono) {
        $error = 'El teléfono es obligatorio.';
    } elseif (!preg_match('/^[0-9\+\-\s]{6,20}$/', $telefono)) {
        $error = 'El teléfono solo puede contener números (mín. 6 dígitos).';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        // Verificar email duplicado
        $dup = $conn->prepare("SELECT COUNT(*) FROM usuario WHERE email = ?");
        $dup->execute([strtolower($email)]);
        if ($dup->fetchColumn() > 0) {
            $error = 'Ese email ya está registrado.';
        }
    }

    if (!$error) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $conn->beginTransaction();

            // 1. Insertar en usuario (tabla raíz)
            $conn->prepare("INSERT INTO usuario (email, telefono, tipo) VALUES (?, ?, ?)")
                 ->execute([strtolower($email), $telefono, $tipo]);
            $id_usuario = $conn->lastInsertId();

            if ($tipo === 'CLIENTE') {
                $calle    = trim(filter_input(INPUT_POST, 'calle',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $num_casa = trim(filter_input(INPUT_POST, 'num_casa', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

                $conn->prepare(
                    "INSERT INTO cliente (id_usuario, nombre, contrasena, calle, num_casa)
                     VALUES (?, ?, ?, ?, ?)"
                )->execute([$id_usuario, $nombre, $hash, $calle, $num_casa]);

            } elseif ($tipo === 'EMPRESA') {
                $categoria = trim(filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'General');
                $direccion = trim(filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $horarios  = trim(filter_input(INPUT_POST, 'horarios',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

                $conn->prepare(
                    "INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, estado_aprobacion)
                     VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE')"
                )->execute([$id_usuario, $nombre, $hash, $categoria, $direccion, $horarios]);

                // Crear menú principal automáticamente
                $id_empresa = $conn->lastInsertId();
                $conn->prepare("INSERT INTO menu (id_empresa, nombre) VALUES (?, 'Menú Principal')")
                     ->execute([$id_empresa]);

                // Notificación por email desactivada
            }

            $conn->commit();
            header('Location: login.php?registrado=1');
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log('registro error: ' . $e->getMessage());
            $error = 'Error al registrar. Intentá nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-3xl shadow-lg border max-w-md w-full p-8 space-y-5">

    <div class="text-center">
        <a href="index.php" class="inline-block bg-orange-500 text-white font-black text-2xl px-5 py-1.5 rounded-xl">C.A.A.S.</a>
        <h1 class="text-xl font-black text-gray-800 mt-3">Crear una Cuenta</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 text-xs font-bold p-3 rounded-xl text-center">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4" id="formReg">

        <!-- Tipo -->
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1">Tipo de cuenta *</label>
            <select name="tipo" id="selectTipo" onchange="mostrarCampos()" required
                    class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">
                <option value="">— Seleccioná —</option>
                <option value="CLIENTE"  <?= ($_POST['tipo']??'')==='CLIENTE'  ? 'selected':'' ?>>👤 Cliente</option>
                <option value="EMPRESA"  <?= ($_POST['tipo']??'')==='EMPRESA'  ? 'selected':'' ?>>🏪 Empresa / Local</option>
            </select>
        </div>

        <!-- Aviso empresa -->
        <div id="avisoEmp" class="hidden bg-amber-50 border border-amber-200 text-amber-800 text-xs p-3 rounded-xl">
            ⚠️ Las empresas quedan en estado <strong>Pendiente</strong> hasta ser aprobadas por el administrador.
        </div>

        <!-- Campos comunes -->
        <input type="text" name="nombre" required
               value="<?= e($_POST['nombre'] ?? '') ?>"
               placeholder="Nombre completo / Nombre del local *"
               class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">

        <input type="email" name="email" required
               value="<?= e($_POST['email'] ?? '') ?>"
               placeholder="Email *"
               class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">

        <input type="tel" name="telefono" required
               value="<?= e($_POST['telefono'] ?? '') ?>"
               placeholder="Teléfono / WhatsApp *"
               pattern="[0-9\+\-\s]{6,20}"
               title="Solo números, mínimo 6 dígitos"
               class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">

        <div class="relative">
            <input type="password" id="passInput" name="password" required
                   placeholder="Contraseña (mín. 8 caracteres) *"
                   class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400 pr-11">
            <button type="button"
                    onclick="const i=document.getElementById('passInput');i.type=i.type==='password'?'text':'password'"
                    class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 text-base select-none">👁</button>
        </div>

        <!-- Campos CLIENTE -->
        <div id="camposCliente" class="hidden space-y-3 pt-2 border-t border-dashed border-gray-200">
            <p class="text-xs font-bold text-gray-500">Dirección de entrega</p>
            <input type="text" name="calle"
                   value="<?= e($_POST['calle'] ?? '') ?>"
                   placeholder="Calle"
                   class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" name="num_casa"
                   value="<?= e($_POST['num_casa'] ?? '') ?>"
                   placeholder="Número de casa / Apto"
                   class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">
        </div>

        <!-- Campos EMPRESA -->
        <div id="camposEmpresa" class="hidden space-y-3 pt-2 border-t border-dashed border-gray-200">
            <p class="text-xs font-bold text-gray-500">Datos del local</p>
            <input type="text" name="categoria"
                   value="<?= e($_POST['categoria'] ?? '') ?>"
                   placeholder="Categoría (ej: Hamburguesería, Pizzería) *"
                   class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" name="horarios"
                   value="<?= e($_POST['horarios'] ?? '') ?>"
                   placeholder="Horarios (ej: 19:00 - 00:00)"
                   class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" name="direccion"
                   value="<?= e($_POST['direccion'] ?? '') ?>"
                   placeholder="Dirección del local"
                   class="w-full p-3 border border-gray-200 rounded-xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">
        </div>

        <button type="submit"
                class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-md transition text-sm">
            Registrarse
        </button>
    </form>

    <p class="text-xs text-center text-gray-500">
        ¿Ya tenés cuenta? <a href="login.php" class="text-orange-500 font-bold hover:underline">Iniciar Sesión</a>
    </p>
</div>

<script>
function mostrarCampos() {
    const tipo = document.getElementById('selectTipo').value;
    document.getElementById('camposCliente').classList.toggle('hidden', tipo !== 'CLIENTE');
    document.getElementById('camposEmpresa').classList.toggle('hidden', tipo !== 'EMPRESA');
    document.getElementById('avisoEmp').classList.toggle('hidden',      tipo !== 'EMPRESA');
}
// Restaurar si hubo error
window.addEventListener('DOMContentLoaded', mostrarCampos);
</script>
</body>
</html>
