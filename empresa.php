<?php
require_once 'config.php';

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'EMPRESA') {
    header('Location: login.php'); exit;
}
$id_empresa = (int)$_SESSION['id_empresa'];
$msg = ''; $msg_tipo = 'ok';

if (!file_exists('uploads')) mkdir('uploads', 0755, true);

// ── Eliminar producto ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $idp = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
    if ($idp) {
        $chk = $conn->prepare("SELECT imagen FROM producto WHERE id_producto = ? AND id_empresa = ?");
        $chk->execute([$idp, $id_empresa]);
        $row = $chk->fetch();
        if ($row) {
            $conn->prepare("DELETE FROM menu_producto WHERE id_producto = ?")->execute([$idp]);
            $conn->prepare("DELETE FROM producto WHERE id_producto = ? AND id_empresa = ?")->execute([$idp, $id_empresa]);
            $f = 'uploads/' . $row['imagen'];
            if ($row['imagen'] !== 'default.jpg' && file_exists($f)) unlink($f);
            $msg = '¡Plato eliminado!'; $msg_tipo = 'ok';
        }
    }
}

// ── Agregar producto ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nombre = trim(filter_input(INPUT_POST,'nombre',FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $precio = filter_input(INPUT_POST,'precio',FILTER_VALIDATE_FLOAT);
    $desc   = trim(filter_input(INPUT_POST,'descripcion',FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $imagen = 'default.jpg';

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tmp  = $_FILES['imagen']['tmp_name'];
        $fi   = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $tmp); finfo_close($fi);
        $ext  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? null;
        if ($ext && $_FILES['imagen']['size'] <= 3_000_000) {
            $imagen = bin2hex(random_bytes(8)) . '.' . $ext;
            move_uploaded_file($tmp, 'uploads/' . $imagen);
        } else {
            $msg = 'Imagen no válida. Se usó imagen por defecto.'; $msg_tipo = 'warn';
        }
    }

    if ($nombre && $precio !== false && $precio >= 0) {
        try {
            $conn->prepare("INSERT INTO producto (id_empresa,nombre,precio,descripcion,imagen) VALUES (?,?,?,?,?)")
                 ->execute([$id_empresa,$nombre,$precio,$desc,$imagen]);
            $id_prod = $conn->lastInsertId();
            $menu = $conn->prepare("SELECT id_menu FROM menu WHERE id_empresa = ? LIMIT 1");
            $menu->execute([$id_empresa]);
            $m = $menu->fetch();
            if ($m) $conn->prepare("INSERT IGNORE INTO menu_producto (id_menu,id_producto) VALUES (?,?)")->execute([$m['id_menu'],$id_prod]);
            $msg = '¡Plato publicado!'; $msg_tipo = 'ok';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $msg = 'Error al guardar.'; $msg_tipo = 'err';
        }
    } else {
        $msg = 'Nombre y precio son obligatorios.'; $msg_tipo = 'err';
    }
}

$stmtP = $conn->prepare("SELECT * FROM producto WHERE id_empresa = ? ORDER BY nombre");
$stmtP->execute([$id_empresa]);
$lista = $stmtP->fetchAll();
$nombre_empresa = e($_SESSION['nombre'] ?? '');
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empresa - <?= $nombre_empresa ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script>
        const _t = localStorage.getItem('caas_tema') || 'light';
        if (_t === 'dark') document.documentElement.classList.add('dark');
    </script>
    <script src="lang.js"></script>
    <script src="theme.js"></script>
</head>
<body class="bg-slate-50 dark:bg-gray-950 text-gray-800 dark:text-gray-200 transition-colors duration-300">

<!-- ── NAVBAR ─────────────────────────────────────────────────────── -->
<nav class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 sticky top-0 z-40 px-4 sm:px-6 h-14 flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-3">
        <span class="bg-orange-500 text-white font-black text-lg px-3 py-1 rounded-xl">C.A.A.S.</span>
        <span class="hidden sm:inline text-sm font-bold text-gray-600 dark:text-gray-400"><?= $nombre_empresa ?></span>
    </div>
    <div class="flex items-center gap-2">
        <button id="btnToggleTema" class="text-lg p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">🌙</button>
        <a href="ajustes.php" class="text-lg p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">⚙️</a>
        <a href="logout.php" class="text-xs font-bold bg-red-500 text-white px-3 py-2 rounded-xl hover:bg-red-600 transition" data-i18n="nav_logout">Salir</a>
    </div>
</nav>

<div class="max-w-6xl mx-auto p-4 sm:p-6 space-y-8">

    <!-- ── PEDIDOS EN VIVO ─────────────────────────────────────── -->
    <section class="bg-white dark:bg-gray-900 rounded-2xl border dark:border-gray-800 p-5 sm:p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <h2 class="font-bold text-lg text-gray-800 dark:text-white" data-i18n="emp_orders">Pedidos Recibidos</h2>
                <span class="text-xs bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-300 font-bold px-2 py-0.5 rounded-full" data-i18n="emp_live">En Vivo</span>
            </div>
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            </span>
        </div>
        <div id="contenedor-pedidos" class="space-y-3">
            <p class="text-xs text-gray-400 dark:text-gray-500 text-center py-4" data-i18n="adm_loading">Cargando...</p>
        </div>
    </section>

    <!-- ── GESTIÓN DE PLATOS ───────────────────────────────────── -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Formulario -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border dark:border-gray-800 p-5 shadow-sm">
            <h2 class="font-bold text-lg mb-4 text-gray-800 dark:text-white" data-i18n="emp_add_dish">Agregar Plato</h2>

            <?php if ($msg):
                $cls = ['ok'=>'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                        'warn'=>'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                        'err'=>'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'][$msg_tipo]; ?>
                <div class="<?= $cls ?> text-xs font-bold p-2 rounded-lg mb-3"><?= e($msg) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-3">
                <input type="text" name="nombre" required
                       data-i18n-ph="emp_dish_name" placeholder="Nombre del plato *"
                       class="w-full p-2.5 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 dark:bg-gray-800 dark:text-white">
                <input type="number" step="0.01" min="0" name="precio" required
                       data-i18n-ph="emp_dish_price" placeholder="Precio ($) *"
                       class="w-full p-2.5 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 dark:bg-gray-800 dark:text-white">
                <textarea name="descripcion" rows="3"
                          data-i18n-ph="emp_dish_desc" placeholder="Descripción..."
                          class="w-full p-2.5 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 resize-none bg-gray-50 dark:bg-gray-800 dark:text-white"></textarea>
                <div>
                    <label class="text-xs text-gray-500 dark:text-gray-400 font-bold block mb-1" data-i18n="emp_dish_img">Imagen (jpg/png/webp, máx 3MB)</label>
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                           class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-1.5 file:px-3 file:border-0 file:rounded-lg file:text-xs file:font-bold file:bg-orange-50 dark:file:bg-orange-900/30 file:text-orange-600 dark:file:text-orange-400 hover:file:bg-orange-100">
                </div>
                <button type="submit" name="guardar"
                        class="w-full bg-orange-500 text-white font-bold py-2.5 rounded-xl hover:bg-orange-600 transition shadow text-sm"
                        data-i18n="emp_publish">Publicar Plato</button>
            </form>
        </div>

        <!-- Lista de platos -->
        <div class="md:col-span-2">
            <h2 class="font-bold text-lg mb-4 text-gray-800 dark:text-white">
                <span data-i18n="emp_published">Platos Publicados</span>
                <span class="text-sm font-normal text-gray-400 dark:text-gray-500">(<?= count($lista) ?>)</span>
            </h2>
            <?php if (empty($lista)): ?>
                <div class="bg-white dark:bg-gray-900 rounded-2xl border dark:border-gray-800 border-dashed p-10 text-center text-gray-400 dark:text-gray-500 text-sm" data-i18n="emp_no_dishes">
                    Aún no publicaste ningún plato.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($lista as $p): ?>
                    <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border dark:border-gray-800 flex gap-3 shadow-sm items-start">
                        <img src="uploads/<?= e($p['imagen']) ?>"
                             class="w-20 h-20 object-cover rounded-xl bg-gray-100 dark:bg-gray-800 flex-shrink-0"
                             onerror="this.src='https://placehold.co/80x80/f1f5f9/94a3b8?text=?'"
                             alt="<?= e($p['nombre']) ?>">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white truncate"><?= e($p['nombre']) ?></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5"><?= e($p['descripcion'] ?? '') ?></p>
                            <span class="text-sm font-black text-orange-600 dark:text-orange-400 block mt-1">$<?= number_format((float)$p['precio'],2) ?></span>
                            <form method="POST" class="mt-2" onsubmit="return confirm('¿Eliminar?')">
                                <input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>">
                                <button type="submit" name="eliminar"
                                        class="text-xs text-red-500 hover:text-red-700 font-bold"
                                        data-i18n="emp_delete">Eliminar</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const ESTADOS = ['Pendiente','En preparación','En camino','Entregado','Cancelado'];
const COLORES = {
    'Pendiente':      'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
    'En preparación': 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    'En camino':      'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300',
    'Entregado':      'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    'Cancelado':      'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
};
const esc = v => v==null?'':String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

async function cambiarEstado(id, estado) {
    const fd = new FormData();
    fd.append('accion','cambiar_estado'); fd.append('id_pedido',id); fd.append('estado',estado);
    const r = await fetch('obtener_pedidos.php',{method:'POST',body:fd});
    const d = await r.json();
    if (d.status === 'success') cargarPedidos();
    else alert('No se pudo actualizar.');
}

async function cargarPedidos() {
    try {
        const r = await fetch('obtener_pedidos.php');
        const d = await r.json();
        const c = document.getElementById('contenedor-pedidos');
        if (d.status !== 'success' || !d.data?.length) {
            c.innerHTML = `<p class="text-xs text-gray-400 dark:text-gray-500 text-center py-4">${t('emp_no_orders')}</p>`;
            return;
        }
        c.innerHTML = d.data.map(p => {
            const badge = COLORES[p.estado] ?? 'bg-gray-100 text-gray-600';
            const opts  = ESTADOS.map(s=>`<option value="${s}"${s===p.estado?' selected':''}>${s}</option>`).join('');
            const dir   = p.calle ? `${esc(p.calle)} ${esc(p.num_casa??'')}` : '—';
            return `<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-black bg-orange-500 text-white px-2 py-0.5 rounded-md">#${esc(p.id_pedido)}</span>
                        <span class="text-sm font-bold text-gray-800 dark:text-white">${esc(p.nombre_cliente)}</span>
                        <span class="text-xs ${badge} font-bold px-2 py-0.5 rounded-full">${esc(p.estado)}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">📦 ${esc(p.detalle)}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">📍 ${dir} · 🕒 ${esc(p.fecha_hora??'')}</p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="text-base font-black text-gray-800 dark:text-white">$${parseFloat(p.costo||0).toFixed(2)}</span>
                    <select onchange="cambiarEstado(${p.id_pedido},this.value)"
                            class="text-xs border dark:border-gray-600 rounded-lg px-2 py-1.5 outline-none focus:ring-2 focus:ring-orange-400 bg-white dark:bg-gray-700 dark:text-white cursor-pointer">
                        ${opts}
                    </select>
                </div>
            </div>`;
        }).join('');
    } catch(e) { console.error(e); }
}

cargarPedidos();
setInterval(cargarPedidos, 8000);
</script>
</body>
</html>
