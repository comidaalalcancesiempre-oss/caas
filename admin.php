<?php
require_once 'config.php';

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADMIN') {
    header('Location: login.php'); exit;
}

$flash = '';

// ── Aprobar / Rechazar empresa ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_empresa'])) {
    $id  = filter_input(INPUT_POST, 'id_empresa', FILTER_VALIDATE_INT);
    $acc = $_POST['accion_empresa'];
    if ($id && in_array($acc, ['aprobar','rechazar'], true)) {
        $estado = $acc === 'aprobar' ? 'APROBADO' : 'RECHAZADO';
        $conn->prepare("UPDATE empresa SET estado_aprobacion = ? WHERE id_empresa = ?")
             ->execute([$estado, $id]);
        $flash = $acc === 'aprobar' ? '✓ Empresa aprobada.' : '✓ Empresa rechazada.';
    }
}

// ── Cargar empresas ────────────────────────────────────────────────
$empresas = $conn->query(
    "SELECT e.*, u.email, u.telefono FROM empresa e
     JOIN usuario u ON e.id_usuario = u.id_usuario
     ORDER BY FIELD(e.estado_aprobacion,'PENDIENTE','APROBADO','RECHAZADO'), e.nombre"
)->fetchAll();

$pendientes = array_filter($empresas, fn($e) => $e['estado_aprobacion'] === 'PENDIENTE');
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
        <div class="bg-green-100 text-green-700 border border-green-200 p-3 rounded-xl text-sm font-bold"><?= e($flash) ?></div>
    <?php endif; ?>

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
                <div class="flex gap-2 flex-shrink-0">
                    <form method="POST">
                        <input type="hidden" name="id_empresa" value="<?= (int)$emp['id_empresa'] ?>">
                        <button name="accion_empresa" value="aprobar"
                                class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition">✓ Aprobar</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('¿Rechazar esta empresa?')">
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

</main>

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
    const fd = new FormData(); fd.append('buscar', input.value.trim());
    try {
        const r = await fetch('buscar_pedidos.php',{method:'POST',body:fd});
        if (!r.ok) throw new Error(r.status);
        const data = await r.json();
        spinner.classList.add('hidden');
        renderizar(Array.isArray(data) ? data : []);
    } catch {
        spinner.classList.add('hidden');
        body.innerHTML = '<tr><td colspan="9" class="p-4 text-center text-red-500 font-bold">Error al cargar datos.</td></tr>';
    }
}

function renderizar(rows) {
    if (!rows.length) { body.innerHTML = '<tr><td colspan="9" class="p-4 text-center text-gray-400">Sin resultados.</td></tr>'; return; }
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
