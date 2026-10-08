<?php
require_once 'config.php';

// Solo usuarios autenticados
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

$id_usuario = (int)$_SESSION['user_id'];
$tipo       = $_SESSION['tipo'];
$msg        = '';
$msg_tipo   = 'ok';

// ── Cargar datos actuales ──────────────────────────────────────────
if ($tipo === 'EMPRESA') {
    $stmt = $conn->prepare(
        "SELECT e.*, u.email, u.telefono FROM empresa e
         JOIN usuario u ON e.id_usuario = u.id_usuario
         WHERE e.id_empresa = ?"
    );
    $stmt->execute([$_SESSION['id_empresa']]);
} else {
    $stmt = $conn->prepare(
        "SELECT c.*, u.email, u.telefono FROM cliente c
         JOIN usuario u ON c.id_usuario = u.id_usuario
         WHERE c.id_usuario = ?"
    );
    $stmt->execute([$id_usuario]);
}
$perfil = $stmt->fetch();

// ── Procesar actualización ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValidar()) {
        $msg = 'Solicitud inválida.'; $msg_tipo = 'err';
    } else {
        $telefono_nuevo = trim(filter_input(INPUT_POST,'telefono',FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $telefono_viejo = $perfil['telefono'];
        $cambio_telefono = $telefono_nuevo !== $telefono_viejo;

        // Si el teléfono cambió, verificar contraseña actual
        if ($cambio_telefono) {
            $pass_confirmacion = $_POST['confirmar_pass'] ?? '';
            // Obtener hash según tipo
            if ($tipo === 'EMPRESA') {
                $stmt_h = $conn->prepare("SELECT contrasena FROM empresa WHERE id_usuario = ?");
            } else {
                $stmt_h = $conn->prepare("SELECT contrasena FROM cliente WHERE id_usuario = ?");
            }
            $stmt_h->execute([$id_usuario]);
            $hash_actual = $stmt_h->fetchColumn();

            if (!$pass_confirmacion || !password_verify($pass_confirmacion, $hash_actual)) {
                $msg = 'Contraseña incorrecta. No se pudo cambiar el teléfono.';
                $msg_tipo = 'err';
                $cambio_telefono = false; // bloquear el cambio
                $telefono_nuevo  = $telefono_viejo; // revertir al valor anterior
            }
        }

        if ($msg_tipo !== 'err') {
            if ($telefono_nuevo && !preg_match('/^[0-9\+\-\s]{6,20}$/', $telefono_nuevo)) {
                $msg = 'Teléfono inválido.'; $msg_tipo = 'err';
            } else {
                $conn->prepare("UPDATE usuario SET telefono = ? WHERE id_usuario = ?")
                     ->execute([$telefono_nuevo, $id_usuario]);

            if ($tipo === 'CLIENTE') {
                $nombre   = trim(filter_input(INPUT_POST,'nombre',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $calle    = trim(filter_input(INPUT_POST,'calle',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $num_casa = trim(filter_input(INPUT_POST,'num_casa', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $conn->prepare("UPDATE cliente SET nombre=?, calle=?, num_casa=? WHERE id_usuario=?")
                     ->execute([$nombre, $calle, $num_casa, $id_usuario]);
                $_SESSION['nombre'] = $nombre;

            } elseif ($tipo === 'EMPRESA') {
                $nombre    = trim(filter_input(INPUT_POST,'nombre',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $categoria = trim(filter_input(INPUT_POST,'categoria', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $horarios  = trim(filter_input(INPUT_POST,'horarios',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $direccion = trim(filter_input(INPUT_POST,'direccion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $descripcion = trim(filter_input(INPUT_POST,'descripcion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
                $latitud   = filter_input(INPUT_POST,'latitud',  FILTER_VALIDATE_FLOAT) ?: null;
                $longitud  = filter_input(INPUT_POST,'longitud', FILTER_VALIDATE_FLOAT) ?: null;

                $logo = $perfil['logo'];
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $tmp  = $_FILES['logo']['tmp_name'];
                    $fi   = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($fi,$tmp); finfo_close($fi);
                    $ext  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? null;
                    if ($ext && $_FILES['logo']['size'] <= 2_000_000) {
                        $logo = 'logo_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        move_uploaded_file($tmp, 'uploads/' . $logo);
                    }
                }

                $conn->prepare(
                    "UPDATE empresa SET nombre=?,categoria=?,horarios=?,direccion=?,descripcion=?,latitud=?,longitud=?,logo=?
                     WHERE id_empresa=?"
                )->execute([$nombre,$categoria,$horarios,$direccion,$descripcion,$latitud,$longitud,$logo,$_SESSION['id_empresa']]);
                $_SESSION['nombre'] = $nombre;
            }

            $msg = '¡Perfil actualizado correctamente!'; $msg_tipo = 'ok';
            header('Location: perfil.php?ok=1'); exit;
            } // cierre if !err validación teléfono
        } // cierre if !err contraseña
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class'}</script>
    <script>const _t=localStorage.getItem('caas_tema')||'light';if(_t==='dark')document.documentElement.classList.add('dark');</script>
    <script src="lang.js"></script><script src="theme.js"></script>
    <style>@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}.fade-in{animation:fadeInUp .35s ease both}</style>
</head>
<body class="bg-slate-100 dark:bg-gray-950 min-h-screen transition-colors duration-300">

<!-- NAVBAR -->
<nav class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 sticky top-0 z-50 shadow-sm">
    <div class="max-w-4xl mx-auto px-4 h-14 flex items-center justify-between">
        <a href="index.php" class="bg-orange-500 text-white font-black text-xl px-3 py-1 rounded-xl">C.A.A.S.</a>
        <div class="flex items-center gap-2">
            <button id="btnToggleTema" class="text-xl p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">🌙</button>
            <a href="ajustes.php" class="text-xl p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">⚙️</a>
            <?php if ($tipo === 'EMPRESA'): ?>
                <a href="empresa.php" class="text-xs font-bold bg-sky-500 text-white px-3 py-2 rounded-xl hover:bg-sky-600 transition">Mi Panel</a>
            <?php else: ?>
                <a href="index.php" class="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-orange-500">← Inicio</a>
            <?php endif; ?>
            <a href="logout.php" class="text-xs font-bold bg-red-500 text-white px-3 py-2 rounded-xl hover:bg-red-600 transition">Salir</a>
        </div>
    </div>
</nav>

<main class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <!-- Cabecera de perfil -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl border dark:border-gray-800 p-6 shadow-sm fade-in flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-3xl flex-shrink-0">
            <?= $tipo === 'EMPRESA' ? '🏪' : '👤' ?>
        </div>
        <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white"><?= e($perfil['nombre'] ?? '') ?></h1>
            <p class="text-xs text-gray-500 dark:text-gray-400"><?= e($perfil['email'] ?? '') ?></p>
            <span class="inline-block mt-1 text-xs font-bold px-2.5 py-0.5 rounded-full
                <?= $tipo === 'EMPRESA' ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' :
                   ($tipo === 'ADMIN'   ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' :
                                          'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300') ?>">
                <?= e($tipo) ?>
            </span>
        </div>
    </div>

    <?php if (isset($_GET['ok'])): ?>
        <div class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm font-bold p-3 rounded-xl text-center">
            ✓ ¡Perfil actualizado!
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="<?= $msg_tipo==='ok' ? 'bg-green-100 dark:bg-green-900/30 text-green-700' : 'bg-red-100 dark:bg-red-900/30 text-red-700' ?> text-sm font-bold p-3 rounded-xl text-center">
            <?= e($msg) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form method="POST" enctype="multipart/form-data"
          class="bg-white dark:bg-gray-900 rounded-3xl border dark:border-gray-800 p-6 shadow-sm fade-in space-y-5">
        <?= csrfField() ?>

        <h2 class="font-bold text-base text-gray-800 dark:text-gray-200">Editar información</h2>

        <!-- Campos comunes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Nombre</label>
                <input type="text" name="nombre" value="<?= e($perfil['nombre'] ?? '') ?>"
                       class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">
                    Teléfono / WhatsApp
                    <span class="text-orange-500 font-normal ml-1">🔒 requiere contraseña para cambiar</span>
                </label>
                <input type="tel" name="telefono" id="inputTelefono"
                       value="<?= e($perfil['telefono'] ?? '') ?>"
                       onchange="verificarCambioTelefono(this.value)"
                       class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
            </div>

            <!-- Campo de confirmación de contraseña — aparece solo si cambia el teléfono -->
            <div id="bloqueConfirmarPass" class="hidden bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4 space-y-2">
                <p class="text-xs font-bold text-amber-800 dark:text-amber-300">
                    🔒 Estás cambiando tu número de teléfono. Confirmá tu contraseña actual para continuar.
                </p>
                <div class="relative">
                    <input type="password" name="confirmar_pass" id="confirmarPass"
                           placeholder="Contraseña actual *"
                           class="w-full p-3 border border-amber-300 dark:border-amber-700 rounded-xl text-sm bg-white dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-amber-400 pr-11">
                    <button type="button"
                            onclick="const i=document.getElementById('confirmarPass');i.type=i.type==='password'?'text':'password'"
                            class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 text-base">👁</button>
                </div>
            </div>
        </div>

        <?php if ($tipo === 'CLIENTE'): ?>
        <!-- Campos cliente -->
        <div class="pt-2 border-t dark:border-gray-800 space-y-4">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400">Dirección de entrega</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Calle</label>
                    <input type="text" name="calle" value="<?= e($perfil['calle'] ?? '') ?>"
                           class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Número</label>
                    <input type="text" name="num_casa" value="<?= e($perfil['num_casa'] ?? '') ?>"
                           class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                </div>
            </div>
        </div>
        <?php elseif ($tipo === 'EMPRESA'): ?>
        <!-- Campos empresa -->
        <div class="pt-2 border-t dark:border-gray-800 space-y-4">
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400">Datos del local</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Categoría</label>
                    <input type="text" name="categoria" value="<?= e($perfil['categoria'] ?? '') ?>"
                           class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Horarios</label>
                    <input type="text" name="horarios" value="<?= e($perfil['horarios'] ?? '') ?>"
                           class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Dirección del local</label>
                <input type="text" name="direccion" value="<?= e($perfil['direccion'] ?? '') ?>"
                       class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Descripción del local</label>
                <textarea name="descripcion" rows="3"
                          class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400 resize-none"><?= e($perfil['descripcion'] ?? '') ?></textarea>
            </div>

            <!-- Coordenadas para el mapa -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Latitud 📍 <span class="text-gray-400 font-normal">(para el mapa)</span></label>
                    <input type="number" step="0.0000001" name="latitud" value="<?= e($perfil['latitud'] ?? '') ?>"
                           placeholder="-34.9011"
                           class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Longitud 📍</label>
                    <input type="number" step="0.0000001" name="longitud" value="<?= e($perfil['longitud'] ?? '') ?>"
                           placeholder="-56.1645"
                           class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                </div>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500">💡 Buscá tu local en <a href="https://www.openstreetmap.org" target="_blank" class="text-orange-500 hover:underline">openstreetmap.org</a>, hacé clic derecho → "Mostrar dirección" para obtener las coordenadas.</p>

            <!-- Logo -->
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Logo del local</label>
                <div class="flex items-center gap-4">
                    <img src="uploads/<?= e($perfil['logo'] ?? 'default_logo.png') ?>"
                         class="w-16 h-16 rounded-2xl object-cover border dark:border-gray-700 bg-gray-100 dark:bg-gray-800"
                         onerror="this.src='https://placehold.co/64x64/f97316/white?text=?'">
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp"
                           class="text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-1.5 file:px-3 file:border-0 file:rounded-lg file:text-xs file:font-bold file:bg-orange-50 dark:file:bg-orange-900/30 file:text-orange-600 hover:file:bg-orange-100">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <button type="submit"
                class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-md transition text-sm">
            Guardar cambios
        </button>
    </form>

    <!-- Cambiar contraseña -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl border dark:border-gray-800 p-6 shadow-sm fade-in">
        <h2 class="font-bold text-base text-gray-800 dark:text-gray-200 mb-3">Contraseña</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Para cambiar tu contraseña, usá el sistema de recuperación.</p>
        <a href="recuperar.php"
           class="inline-block text-sm font-bold text-orange-500 border border-orange-300 dark:border-orange-700 px-4 py-2 rounded-xl hover:bg-orange-50 dark:hover:bg-orange-900/20 transition">
            Cambiar contraseña →
        </a>
    </div>

    <?php if ($tipo === 'CLIENTE'): ?>
    <!-- Acceso rápido a pedidos -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl border dark:border-gray-800 p-6 shadow-sm fade-in flex items-center justify-between">
        <div>
            <h2 class="font-bold text-base text-gray-800 dark:text-gray-200">Mis Pedidos</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ver el historial de todos tus pedidos</p>
        </div>
        <a href="mis_pedidos.php"
           class="text-sm font-bold bg-sky-500 text-white px-4 py-2.5 rounded-xl hover:bg-sky-600 transition shadow">
            Ver pedidos →
        </a>
    </div>
    <?php endif; ?>

</main>
</body>
</html>

<script>
// Teléfono original al cargar la página
const telefonoOriginal = document.getElementById('inputTelefono')?.value || '';

function verificarCambioTelefono(nuevoValor) {
    const bloque = document.getElementById('bloqueConfirmarPass');
    const input  = document.getElementById('confirmarPass');
    if (!bloque) return;

    if (nuevoValor.trim() !== telefonoOriginal.trim()) {
        // El teléfono cambió — mostrar campo de contraseña y hacerlo requerido
        bloque.classList.remove('hidden');
        input.required = true;
    } else {
        // Volvió al valor original — ocultar y quitar requerido
        bloque.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}
</script>
