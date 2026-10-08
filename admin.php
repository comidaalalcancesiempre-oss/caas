<?php
require_once 'config.php';

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADMIN') {
    header('Location: login.php'); exit;
}

$flash      = '';
$flash_tipo = 'ok';

// ── Aprobar / Rechazar empresa ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_empresa'])) {
    if (!csrfValidar()) { $flash = 'Solicitud inválida.'; $flash_tipo = 'err'; }
    else {
        $id  = filter_input(INPUT_POST, 'id_empresa', FILTER_VALIDATE_INT);
        $acc = $_POST['accion_empresa'];
        if ($id && in_array($acc, ['aprobar','rechazar'], true)) {
            $estado = $acc === 'aprobar' ? 'APROBADO' : 'RECHAZADO';
            $conn->prepare("UPDATE empresa SET estado_aprobacion = ? WHERE id_empresa = ?")
                 ->execute([$estado, $id]);
            $flash = $acc === 'aprobar' ? '✓ Empresa aprobada.' : '✓ Empresa rechazada.';
        }
    }
}

// ── Gestión de usuarios (bloquear/activar/eliminar) ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_usuario'])) {
    if (!csrfValidar()) { $flash = 'Solicitud inválida.'; $flash_tipo = 'err'; }
    else {
        $id_usr  = filter_input(INPUT_POST, 'id_usuario_acc', FILTER_VALIDATE_INT);
        $acc_usr = $_POST['accion_usuario'];

        // No permitir que el admin se afecte a sí mismo
        if ($id_usr && $id_usr !== (int)$_SESSION['user_id']) {
            if ($acc_usr === 'bloquear') {
                $conn->prepare("UPDATE usuario SET estado = 'BLOQUEADO' WHERE id_usuario = ?")
                     ->execute([$id_usr]);
                $flash = '✓ Usuario bloqueado.';
            } elseif ($acc_usr === 'activar') {
                $conn->prepare("UPDATE usuario SET estado = 'ACTIVO' WHERE id_usuario = ?")
                     ->execute([$id_usr]);
                $flash = '✓ Usuario activado.';
            } elseif ($acc_usr === 'eliminar') {
                // Eliminar en cascada (FK ON DELETE CASCADE se encarga de cliente/empresa)
                $conn->prepare("DELETE FROM usuario WHERE id_usuario = ? AND tipo != 'ADMIN'")
                     ->execute([$id_usr]);
                $flash = '✓ Usuario eliminado.';
            }
        } else {
            $flash = 'No podés modificar tu propia cuenta desde aquí.'; $flash_tipo = 'warn';
        }
    }
}

// ── Agregar columna estado a usuario si no existe ──────────────────
// (por si no se ejecutó el update SQL todavía)
try {
    $conn->exec("ALTER TABLE usuario ADD COLUMN IF NOT EXISTS estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO'");
} catch (PDOException $e) { /* ya existe, ignorar */ }

// ── Cargar empresas ────────────────────────────────────────────────
$empresas = $conn->query(
    "SELECT e.*, u.email, u.telefono FROM empresa e
     JOIN usuario u ON e.id_usuario = u.id_usuario
     ORDER BY FIELD(e.estado_aprobacion,'PENDIENTE','APROBADO','RECHAZADO'), e.nombre"
)->fetchAll();
$pendientes = array_filter($empresas, fn($e) => $e['estado_aprobacion'] === 'PENDIENTE');

// ── Cargar todos los usuarios ──────────────────────────────────────
$todos_usuarios = $conn->query(
    "SELECT u.id_usuario, u.email, u.telefono, u.tipo,
            COALESCE(u.estado, 'ACTIVO') AS estado,
            COALESCE(c.nombre, e.nombre, 'Sin nombre') AS nombre
     FROM   usuario u
     LEFT JOIN cliente c ON u.id_usuario = c.id_usuario AND u.tipo != 'EMPRESA'
     LEFT JOIN empresa e ON u.id_usuario = e.id_usuario AND u.tipo  = 'EMPRESA'
     ORDER BY u.tipo, nombre"
)->fetchAll();

// ── Estadísticas rápidas ───────────────────────────────────────────
$stats = $conn->query(
    "SELECT
        (SELECT COUNT(*) FROM usuario WHERE tipo='CLIENTE') AS total_clientes,
        (SELECT COUNT(*) FROM usuario WHERE tipo='EMPRESA') AS total_empresas,
        (SELECT COUNT(*) FROM empresa WHERE estado_aprobacion='PENDIENTE') AS empresas_pendientes,
        (SELECT COUNT(*) FROM pedido)  AS total_pedidos,
        (SELECT COUNT(*) FROM pedido WHERE estado='Pendiente') AS pedidos_pendientes,
        (SELECT COUNT(*) FROM producto) AS total_productos"
)->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-gray-800">

<nav class="bg-gray-900 text-white px-4 sm:px-6 h-14 flex items-center justify-between sticky top-0 z-40">
    <div class="flex items-center gap-3">
        <span class="font-black text-sky-400 text-lg">C.A.A.S. — Admin</span>
        <?php if (!empty($pendientes)): ?>
            <span class="bg-red-500 text-white text-xs font-black px-2 py-0.5 rounded-full animate-pulse">
                <?= count($pendientes) ?> pendiente<?= count($pendientes)>1?'s':'' ?>
            </span>
        <?php endif; ?>
    </div>
    <a href="logout.php" class="text-xs font-bold bg-red-600 px-3 py-1.5 rounded-lg hover:bg-red-700 transition">Salir</a>
</nav>

<main class="max-w-6xl mx-auto p-4 sm:p-6 space-y-8">

    <?php if ($flash): ?>
        <div class="<?= $flash_tipo==='err'?'bg-red-100 dark:bg-red-900/30 text-red-700':($flash_tipo==='warn'?'bg-amber-100 dark:bg-amber-900/30 text-amber-700':'bg-green-100 dark:bg-green-900/30 text-green-700') ?> border p-3 rounded-xl text-sm font-bold"><?= e($flash) ?></div>
    <?php endif; ?>

    <!-- ── ESTADÍSTICAS RÁPIDAS ──────────────────────────────────── -->
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <?php
        $cards = [
            ['👥', 'Clientes',          $stats['total_clientes'],      'bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300'],
            ['🏪', 'Empresas',          $stats['total_empresas'],      'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300'],
            ['⏳', 'Pend. aprobación',  $stats['empresas_pendientes'], 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300'],
            ['📦', 'Pedidos totales',   $stats['total_pedidos'],       'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300'],
            ['🕐', 'Pedidos pend.',     $stats['pedidos_pendientes'],  'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'],
            ['🍽️', 'Productos',         $stats['total_productos'],     'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300'],
        ];
        foreach ($cards as [$icon, $label, $val, $cls]): ?>
        <div class="<?= $cls ?> rounded-2xl p-4 text-center border border-current/10">
            <p class="text-2xl font-black"><?= (int)$val ?></p>
            <p class="text-xs font-bold mt-0.5 opacity-80"><?= $icon ?> <?= $label ?></p>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- ── EMPRESAS PENDIENTES ──────────────────────────────────── -->
    <?php if (!empty($pendientes)): ?>
    <section class="bg-white rounded-2xl border border-amber-200 overflow-hidden shadow-sm">
        <div class="bg-amber-50 border-b border-amber-200 px-5 py-4">
            <h2 class="font-black text-lg text-amber-800">⚠️ Empresas Pendientes de Verificación</h2>
        </div>
        <div class="divide-y">
            <?php foreach ($pendientes as $emp): ?>
            <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <p class="font-bold text-gray-900"><?= e($emp['nombre']) ?>
                        <span class="ml-2 text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-lg"><?= e($emp['categoria']) ?></span>
                    </p>
                    <p class="text-xs text-gray-500">📧 <?= e($emp['email']) ?> · 📞 <?= e($emp['telefono']) ?></p>
                    <p class="text-xs text-gray-500">📍 <?= e($emp['direccion'] ?: '—') ?> · 🕒 <?= e($emp['horarios'] ?: '—') ?></p>
                </div>
                <div class="flex gap-2 flex-shrink-0 flex-wrap">
                    <?php
                    $tel_limpio_adm = preg_replace('/[^0-9]/', '', $emp['telefono'] ?? '');
                    $wa_adm = urlencode(
                        "Hola {$emp['nombre']}, somos el equipo de C.A.A.S. 🍔\n\n" .
                        "Recibimos tu solicitud de registro y necesitamos verificar algunos datos antes de aprobarte.\n\n" .
                        "¿Podés confirmar:\n- Nombre del local: {$emp['nombre']}\n- Categoría: {$emp['categoria']}\n" .
                        "- Dirección: {$emp['direccion']}\n\n¡Gracias! Te respondemos a la brevedad. 🙌"
                    );
                    ?>
                    <?php if ($tel_limpio_adm): ?>
                    <a href="https://wa.me/<?= e($tel_limpio_adm) ?>?text=<?= $wa_adm ?>"
                       target="_blank" rel="noopener"
                       class="flex items-center gap-1 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold px-3 py-2 rounded-xl transition shadow">
                        📱 WhatsApp
                    </a>
                    <?php endif; ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="id_empresa" value="<?= (int)$emp['id_empresa'] ?>">
                        <button name="accion_empresa" value="aprobar"
                                class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition">✓ Aprobar</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('¿Rechazar esta empresa?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id_empresa" value="<?= (int)$emp['id_empresa'] ?>">
                        <button name="accion_empresa" value="rechazar"
                                class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition">✕ Rechazar</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── TODAS LAS EMPRESAS ──────────────────────────────────── -->
    <section class="bg-white rounded-2xl border overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h2 class="font-black text-lg text-gray-800">Empresas Registradas</h2>
            <span class="text-xs text-gray-400"><?= count($empresas) ?> total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 border-b text-gray-600 font-bold uppercase">
                    <tr>
                        <th class="p-3">Nombre</th><th class="p-3">Categoría</th>
                        <th class="p-3">Email</th><th class="p-3">Teléfono</th>
                        <th class="p-3 text-center">Estado</th><th class="p-3 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($empresas as $emp):
                        $badge = ['APROBADO'=>'bg-green-100 text-green-700','PENDIENTE'=>'bg-amber-100 text-amber-700','RECHAZADO'=>'bg-red-100 text-red-700'][$emp['estado_aprobacion']] ?? 'bg-gray-100 text-gray-600';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-3 font-bold"><?= e($emp['nombre']) ?></td>
                        <td class="p-3"><?= e($emp['categoria']) ?></td>
                        <td class="p-3"><?= e($emp['email']) ?></td>
                        <td class="p-3"><?= e($emp['telefono']) ?></td>
                        <td class="p-3 text-center"><span class="<?= $badge ?> px-2 py-0.5 rounded-full font-bold"><?= e($emp['estado_aprobacion']) ?></span></td>
                        <td class="p-3 text-center">
                            <?php if ($emp['estado_aprobacion'] !== 'APROBADO'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="id_empresa" value="<?= (int)$emp['id_empresa'] ?>">
                                    <button name="accion_empresa" value="aprobar" class="text-green-600 hover:text-green-800 font-bold">Aprobar</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="inline" onsubmit="return confirm('¿Rechazar?')">
                                    <input type="hidden" name="id_empresa" value="<?= (int)$emp['id_empresa'] ?>">
                                    <button name="accion_empresa" value="rechazar" class="text-red-500 hover:text-red-700 font-bold">Rechazar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ── CONTROL DE PEDIDOS ──────────────────────────────────── -->
    <section class="bg-white rounded-2xl border overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b bg-gray-50">
            <h2 class="font-black text-lg text-gray-800">Control de Pedidos Globales</h2>
        </div>
        <div class="px-5 py-3 border-b flex flex-col md:flex-row gap-3">
            <div class="relative flex-1">
                <input id="inputBuscar" type="text" placeholder="Buscar por cliente, empresa, estado, dirección..."
                       class="w-full p-2.5 border rounded-xl text-xs outline-none focus:ring-2 focus:ring-sky-400 pr-24">
                <span id="spinner" class="hidden absolute right-3 top-2.5 text-xs text-sky-500 font-bold animate-pulse">Buscando...</span>
            </div>
            <div class="flex gap-2">
                <button id="btnBuscar" class="bg-sky-500 hover:bg-sky-600 text-white font-bold px-4 py-2.5 rounded-xl text-xs transition">Buscar</button>
                <button id="btnLimpiar" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2.5 rounded-xl text-xs transition">Limpiar</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 border-b text-gray-600 font-bold uppercase">
                    <tr>
                        <th class="p-3">ID</th><th class="p-3">Cliente</th><th class="p-3">Empresa</th>
                        <th class="p-3">Dirección</th><th class="p-3">Detalle</th>
                        <th class="p-3">Costo</th><th class="p-3">Fecha</th>
                        <th class="p-3">Estado</th><th class="p-3 text-center">Cambiar</th>
                    </tr>
                </thead>
                <tbody id="tablaBody" class="divide-y">
                    <tr><td colspan="9" class="p-4 text-center text-gray-400">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ── GESTIÓN DE TODOS LOS USUARIOS ──────────────────────── -->
    <section class="bg-white dark:bg-gray-900 rounded-2xl border dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-black text-lg text-gray-800 dark:text-white">Gestión de Usuarios</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400"><?= count($todos_usuarios) ?> usuarios registrados</p>
            </div>
            <!-- Filtro rápido -->
            <input type="text" id="filtroUsuarios" placeholder="Filtrar por nombre, email, tipo..."
                   oninput="filtrarUsuarios()"
                   class="p-2.5 border dark:border-gray-700 rounded-xl text-xs outline-none focus:ring-2 focus:ring-sky-400 bg-white dark:bg-gray-800 dark:text-white w-full sm:w-64">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left" id="tablaUsuarios">
                <thead class="bg-gray-50 dark:bg-gray-800/50 border-b dark:border-gray-800 text-gray-600 dark:text-gray-400 font-bold uppercase tracking-wide">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Nombre</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Teléfono</th>
                        <th class="p-3 text-center">Tipo</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-800">
                    <?php foreach ($todos_usuarios as $usr):
                        $estado_usr = $usr['estado'] ?? 'ACTIVO';
                        $tipo_badge = [
                            'ADMIN'   => 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
                            'EMPRESA' => 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300',
                            'CLIENTE' => 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300',
                        ][$usr['tipo']] ?? 'bg-gray-100 text-gray-600';
                        $estado_badge = $estado_usr === 'ACTIVO'
                            ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                            : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300';
                        $es_yo = ((int)$usr['id_usuario'] === (int)$_SESSION['user_id']);
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition usuario-fila"
                        data-buscar="<?= strtolower(e($usr['nombre'].' '.$usr['email'].' '.$usr['tipo'])) ?>">
                        <td class="p-3 font-bold text-gray-500 dark:text-gray-400">#<?= (int)$usr['id_usuario'] ?></td>
                        <td class="p-3 font-bold text-gray-900 dark:text-white"><?= e($usr['nombre']) ?></td>
                        <td class="p-3 text-gray-600 dark:text-gray-400"><?= e($usr['email']) ?></td>
                        <td class="p-3 text-gray-600 dark:text-gray-400"><?= e($usr['telefono']) ?></td>
                        <td class="p-3 text-center">
                            <span class="<?= $tipo_badge ?> px-2 py-0.5 rounded-full font-bold"><?= e($usr['tipo']) ?></span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="<?= $estado_badge ?> px-2 py-0.5 rounded-full font-bold"><?= e($estado_usr) ?></span>
                        </td>
                        <td class="p-3 text-center">
                            <?php if ($es_yo): ?>
                                <span class="text-xs text-gray-400 italic">(tu cuenta)</span>
                            <?php elseif ($usr['tipo'] !== 'ADMIN'): ?>
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <?php if ($estado_usr === 'BLOQUEADO'): ?>
                                        <form method="POST" class="inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id_usuario_acc" value="<?= (int)$usr['id_usuario'] ?>">
                                            <button name="accion_usuario" value="activar"
                                                    class="text-green-600 dark:text-green-400 hover:underline font-bold">Activar</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id_usuario_acc" value="<?= (int)$usr['id_usuario'] ?>">
                                            <button name="accion_usuario" value="bloquear"
                                                    class="text-amber-600 dark:text-amber-400 hover:underline font-bold">Bloquear</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline"
                                          onsubmit="return confirm('¿Eliminar permanentemente a <?= addslashes(e($usr['nombre'])) ?>? Esto no se puede deshacer.')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id_usuario_acc" value="<?= (int)$usr['id_usuario'] ?>">
                                        <button name="accion_usuario" value="eliminar"
                                                class="text-red-500 dark:text-red-400 hover:underline font-bold">Eliminar</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
// Filtro de usuarios en tiempo real
function filtrarUsuarios() {
    const q = document.getElementById('filtroUsuarios').value.toLowerCase();
    document.querySelectorAll('.usuario-fila').forEach(fila => {
        const coincide = fila.getAttribute('data-buscar').includes(q);
        fila.style.display = coincide ? '' : 'none';
    });
}
</script>

<script>
const ESTADOS = ['Pendiente','En preparación','En camino','Entregado','Cancelado'];
const COLORES = {'Pendiente':'bg-amber-100 text-amber-700','En preparación':'bg-blue-100 text-blue-700','En camino':'bg-violet-100 text-violet-700','Entregado':'bg-green-100 text-green-700','Cancelado':'bg-red-100 text-red-700'};
const esc = v => v==null?'':String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

let timer;
const input   = document.getElementById('inputBuscar');
const body    = document.getElementById('tablaBody');
const spinner = document.getElementById('spinner');

async function buscar() {
    spinner.classList.remove('hidden');
    const fd = new FormData();
    fd.append('buscar', input.value.trim());
    try {
        const r   = await fetch('buscar_pedidos.php', { method: 'POST', body: fd });
        const txt = await r.text();
        spinner.classList.add('hidden');

        let data;
        try {
            data = JSON.parse(txt);
        } catch {
            // Respuesta no es JSON válido — mostrar el texto crudo para depurar
            body.innerHTML = `<tr><td colspan="9" class="p-4 text-center text-red-500 font-bold text-xs">
                Error de respuesta: <code>${txt.substring(0, 300)}</code></td></tr>`;
            return;
        }

        // Error del servidor con detalle
        if (data.error) {
            const msg = data.detalle ? `${data.error}: ${data.detalle}` : data.error;
            body.innerHTML = `<tr><td colspan="9" class="p-4 text-center text-red-500 font-bold">${msg}</td></tr>`;
            return;
        }

        renderizar(Array.isArray(data) ? data : []);

    } catch (err) {
        spinner.classList.add('hidden');
        body.innerHTML = `<tr><td colspan="9" class="p-4 text-center text-red-500 font-bold">
            Error de conexión: ${err.message}</td></tr>`;
    }
}

function renderizar(rows) {
    if (!rows.length) {
        body.innerHTML = `<tr><td colspan="9" class="p-6 text-center text-gray-400 dark:text-gray-500">
            <p class="text-2xl mb-2">📋</p>
            <p class="text-sm font-medium">No hay pedidos registrados aún.</p>
        </td></tr>`;
        return;
    }
    body.innerHTML = rows.map(p => {
        const badge = COLORES[p.estado] ?? 'bg-gray-100 text-gray-600';
        const opts  = ESTADOS.map(s=>`<option value="${s}"${s===p.estado?' selected':''}>${s}</option>`).join('');
        const dir   = p.calle ? `${esc(p.calle)} Nº${esc(p.num_casa??'')}` : '—';
        return `<tr class="hover:bg-gray-50">
            <td class="p-3 font-bold">#${esc(p.id_pedido)}</td>
            <td class="p-3">${esc(p.nombre_cliente)}</td>
            <td class="p-3">${esc(p.nombre_empresa??'—')}</td>
            <td class="p-3">${dir}</td>
            <td class="p-3 max-w-xs truncate" title="${esc(p.detalle)}">${esc(p.detalle)}</td>
            <td class="p-3 font-bold">$${esc(p.costo)}</td>
            <td class="p-3 whitespace-nowrap">${esc(p.fecha_hora??'—')}</td>
            <td class="p-3"><span class="${badge} px-2 py-0.5 rounded-full font-bold">${esc(p.estado)}</span></td>
            <td class="p-3 text-center">
                <select onchange="cambiarEstado(${p.id_pedido},this.value)" class="text-xs border rounded-lg px-2 py-1 outline-none focus:ring-2 focus:ring-sky-400 bg-white cursor-pointer">${opts}</select>
            </td>
        </tr>`;
    }).join('');
}

async function cambiarEstado(id, estado) {
    const fd = new FormData();
    fd.append('accion','cambiar_estado'); fd.append('id_pedido',id); fd.append('estado',estado);
    const r = await fetch('obtener_pedidos.php',{method:'POST',body:fd});
    const d = await r.json();
    if (d.status === 'success') buscar();
    else alert('No se pudo actualizar.');
}

document.getElementById('btnBuscar').addEventListener('click', () => { clearTimeout(timer); buscar(); });
document.getElementById('btnLimpiar').addEventListener('click', () => { input.value=''; buscar(); });
input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(buscar, 350); });

buscar();
</script>
</body>
</html>
