<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo     = $_POST['tipo']    ?? '';
    $nombre   = trim(filter_input(INPUT_POST, 'nombre',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $telefono = trim(filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $password = $_POST['password'] ?? '';

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
        $dup = $conn->prepare("SELECT COUNT(*) FROM usuario WHERE email = ?");
        $dup->execute([strtolower($email)]);
        if ($dup->fetchColumn() > 0) $error = 'Ese email ya está registrado.';
    }

    if (!$error) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        try {
            $conn->beginTransaction();
            $conn->prepare("INSERT INTO usuario (email, telefono, tipo) VALUES (?, ?, ?)")
                 ->execute([strtolower($email), $telefono, $tipo]);
            $id_usuario = $conn->lastInsertId();

            if ($tipo === 'CLIENTE') {
                $calle    = trim(filter_input(INPUT_POST, 'calle',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $num_casa = trim(filter_input(INPUT_POST, 'num_casa', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $conn->prepare("INSERT INTO cliente (id_usuario, nombre, contrasena, calle, num_casa) VALUES (?,?,?,?,?)")
                     ->execute([$id_usuario, $nombre, $hash, $calle, $num_casa]);
            } elseif ($tipo === 'EMPRESA') {
                $categoria = trim(filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'General');
                $direccion = trim(filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $horarios  = trim(filter_input(INPUT_POST, 'horarios',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $conn->prepare("INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, estado_aprobacion) VALUES (?,?,?,?,?,?,'PENDIENTE')")
                     ->execute([$id_usuario, $nombre, $hash, $categoria, $direccion, $horarios]);
                $id_empresa = $conn->lastInsertId();
                $conn->prepare("INSERT INTO menu (id_empresa, nombre) VALUES (?, 'Menú Principal')")
                     ->execute([$id_empresa]);
            }
            $conn->commit();
            header('Location: login.php?registrado=1');
            exit;
        } catch (PDOException $ex) {
            $conn->rollBack();
            error_log('registro: ' . $ex->getMessage());
            $error = 'Error al registrar. Intentá nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script>
        const _t = localStorage.getItem('caas_tema') || 'light';
        if (_t === 'dark') document.documentElement.classList.add('dark');
    </script>
    <script src="lang.js"></script>
    <script src="theme.js"></script>
</head>
<body class="bg-slate-100 dark:bg-gray-950 min-h-screen flex items-center justify-center p-4 transition-colors duration-300">

<div class="bg-white dark:bg-gray-900 rounded-3xl shadow-lg border dark:border-gray-800 max-w-md w-full p-8 space-y-5">

    <div class="text-center">
        <a href="index.php" class="inline-block bg-orange-500 text-white font-black text-2xl px-5 py-1.5 rounded-xl">C.A.A.S.</a>
        <h1 class="text-xl font-black text-gray-800 dark:text-white mt-3" data-i18n="reg_title">Crear una Cuenta</h1>
    </div>

    <!-- Botones tema + ajustes -->
    <div class="flex justify-center gap-2">
        <button id="btnToggleTema" class="text-lg p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">🌙</button>
        <a href="ajustes.php" class="text-lg p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">⚙️</a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold p-3 rounded-xl text-center">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <!-- Tipo -->
        <div>
            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1" data-i18n="reg_type_lbl">Tipo de cuenta *</label>
            <select name="tipo" id="selectTipo" onchange="mostrarCampos()" required
                    class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                <option value="" data-i18n="reg_type_ph">— Seleccioná —</option>
                <option value="CLIENTE"  <?= ($_POST['tipo']??'')==='CLIENTE'  ?'selected':'' ?> data-i18n="reg_client">Cliente</option>
                <option value="EMPRESA"  <?= ($_POST['tipo']??'')==='EMPRESA'  ?'selected':'' ?> data-i18n="reg_company">Empresa / Local</option>
            </select>
        </div>

        <!-- Aviso empresa -->
        <div id="avisoEmp" class="hidden bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs p-3 rounded-xl" data-i18n="reg_pending">
            Las empresas quedan en estado Pendiente hasta ser aprobadas.
        </div>

        <!-- Campos comunes -->
        <input type="text" name="nombre" required
               value="<?= e($_POST['nombre'] ?? '') ?>"
               data-i18n-ph="reg_name_ph"
               placeholder="Nombre completo / Nombre del local *"
               class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">

        <input type="email" name="email" required
               value="<?= e($_POST['email'] ?? '') ?>"
               data-i18n-ph="reg_email_ph"
               placeholder="Email *"
               class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">

        <input type="tel" name="telefono" required
               value="<?= e($_POST['telefono'] ?? '') ?>"
               pattern="[0-9\+\-\s]{6,20}"
               title="Solo números, mínimo 6 dígitos"
               data-i18n-ph="reg_tel_ph"
               placeholder="Teléfono / WhatsApp *"
               class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">

        <div class="relative">
            <input type="password" id="passInput" name="password" required
                   data-i18n-ph="reg_pass_ph"
                   placeholder="Contraseña (mín. 8 caracteres) *"
                   class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400 pr-11">
            <button type="button"
                    onclick="const i=document.getElementById('passInput');i.type=i.type==='password'?'text':'password'"
                    class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 text-base select-none">👁</button>
        </div>

        <!-- Campos CLIENTE -->
        <div id="camposCliente" class="hidden space-y-3 pt-2 border-t border-dashed dark:border-gray-700">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400" data-i18n="reg_delivery">Dirección de entrega</p>
            <input type="text" name="calle"
                   value="<?= e($_POST['calle'] ?? '') ?>"
                   data-i18n-ph="reg_street_ph"
                   placeholder="Calle"
                   class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" name="num_casa"
                   value="<?= e($_POST['num_casa'] ?? '') ?>"
                   data-i18n-ph="reg_house_ph"
                   placeholder="Número de casa / Apto"
                   class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
        </div>

        <!-- Campos EMPRESA -->
        <div id="camposEmpresa" class="hidden space-y-3 pt-2 border-t border-dashed dark:border-gray-700">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400" data-i18n="reg_biz_data">Datos del local</p>
            <input type="text" name="categoria"
                   value="<?= e($_POST['categoria'] ?? '') ?>"
                   data-i18n-ph="reg_cat_ph"
                   placeholder="Categoría *"
                   class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" name="horarios"
                   value="<?= e($_POST['horarios'] ?? '') ?>"
                   data-i18n-ph="reg_hours_ph"
                   placeholder="Horarios"
                   class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" name="direccion"
                   value="<?= e($_POST['direccion'] ?? '') ?>"
                   data-i18n-ph="reg_addr_ph"
                   placeholder="Dirección del local"
                   class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
        </div>

        <button type="submit"
                class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-md transition text-sm"
                data-i18n="reg_btn">Registrarse</button>
    </form>

    <p class="text-xs text-center text-gray-500 dark:text-gray-400">
        <span data-i18n="reg_have_acc">¿Ya tenés cuenta?</span>
        <a href="login.php" class="text-orange-500 font-bold hover:underline ml-1" data-i18n="reg_login">Iniciar Sesión</a>
    </p>
</div>

<script>
function mostrarCampos() {
    const tipo = document.getElementById('selectTipo').value;
    document.getElementById('camposCliente').classList.toggle('hidden', tipo !== 'CLIENTE');
    document.getElementById('camposEmpresa').classList.toggle('hidden', tipo !== 'EMPRESA');
    document.getElementById('avisoEmp').classList.toggle('hidden',      tipo !== 'EMPRESA');
}
window.addEventListener('DOMContentLoaded', mostrarCampos);
</script>
</body>
</html>
