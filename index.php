<?php
require_once 'config.php';

$q   = trim($_GET['q']   ?? '');
$cat = trim($_GET['cat'] ?? '');

// Categorías dinámicas desde BD (solo empresas aprobadas)
$cats = $conn->query(
    "SELECT DISTINCT categoria FROM empresa WHERE estado_aprobacion = 'APROBADO' AND categoria <> '' ORDER BY categoria"
)->fetchAll(PDO::FETCH_COLUMN);

// Empresas aprobadas con filtros
$sql    = "SELECT e.* FROM empresa e WHERE e.estado_aprobacion = 'APROBADO'";
$params = [];
if ($q !== '') {
    $sql .= " AND (e.nombre LIKE ? OR e.categoria LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
}
if ($cat !== '') {
    $sql .= " AND e.categoria = ?";
    $params[] = $cat;
}
$sql .= " ORDER BY e.nombre";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$empresas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C.A.A.S. - Comida Al Alcance Siempre</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-gray-800">

<!-- ── NAVBAR ─────────────────────────────────────────────────────── -->
<nav class="bg-white border-b sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2">
            <span class="bg-orange-500 text-white font-black text-xl px-3 py-1 rounded-xl">C.A.A.S.</span>
            <span class="hidden md:inline text-xs font-semibold text-gray-400">Comida Al Alcance Siempre</span>
        </a>

        <!-- Desktop -->
        <div class="hidden sm:flex items-center gap-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="text-sm font-bold text-gray-700 max-w-[150px] truncate">Hola, <?= e($_SESSION['nombre']) ?></span>
                <?php if ($_SESSION['tipo'] === 'ADMIN'): ?>
                    <a href="admin.php"   class="text-xs font-bold bg-sky-500 text-white px-3 py-2 rounded-xl hover:bg-sky-600 transition">Admin</a>
                <?php elseif ($_SESSION['tipo'] === 'EMPRESA'): ?>
                    <a href="empresa.php" class="text-xs font-bold bg-sky-500 text-white px-3 py-2 rounded-xl hover:bg-sky-600 transition">Mi Panel</a>
                <?php endif; ?>
                <a href="logout.php" class="text-xs font-bold bg-red-500 text-white px-3 py-2 rounded-xl hover:bg-red-600 transition">Salir</a>
            <?php else: ?>
                <a href="login.php"    class="text-sm font-bold text-orange-500 border border-orange-400 px-4 py-2 rounded-xl hover:bg-orange-50 transition">Iniciar Sesión</a>
                <a href="registro.php" class="text-sm font-bold bg-orange-500 text-white px-4 py-2 rounded-xl hover:bg-orange-600 transition shadow">Registrarse</a>
            <?php endif; ?>
        </div>

        <!-- Hamburguesa mobile -->
        <button id="btnNav" class="sm:hidden p-2 rounded-lg hover:bg-gray-100">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path id="iconBars"  stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                <path id="iconX" class="hidden" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Mobile menu -->
    <div id="mobileMenu" class="hidden sm:hidden border-t px-4 py-3 space-y-2 bg-white">
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="text-sm font-bold text-gray-700">Hola, <?= e($_SESSION['nombre']) ?></p>
            <?php if ($_SESSION['tipo'] === 'ADMIN'): ?>
                <a href="admin.php"   class="block text-sm font-bold text-sky-600">Panel Admin</a>
            <?php elseif ($_SESSION['tipo'] === 'EMPRESA'): ?>
                <a href="empresa.php" class="block text-sm font-bold text-sky-600">Mi Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="block text-sm font-bold text-red-500">Cerrar Sesión</a>
        <?php else: ?>
            <a href="login.php"    class="block text-sm font-bold text-orange-500">Iniciar Sesión</a>
            <a href="registro.php" class="block text-sm font-bold text-orange-500">Registrarse</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ── HERO + BUSCADOR ────────────────────────────────────────────── -->
<header class="bg-white border-b py-8 px-4">
    <div class="max-w-4xl mx-auto text-center space-y-4">
        <h1 class="text-2xl sm:text-4xl font-black text-gray-900">Encontrá tu comida favorita </h1>
        <p class="text-sm text-gray-500">Los mejores locales del barrio, en un solo lugar.</p>

        <form method="GET" class="flex flex-col sm:flex-row gap-3 pt-2 max-w-3xl mx-auto">
            <input type="text" name="q" value="<?= e($q) ?>"
                   placeholder="Buscar local o plato..."
                   class="flex-1 p-3.5 border rounded-2xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50">
            <select name="cat" class="p-3.5 border rounded-2xl text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-orange-400">
                <option value="">Todas las categorías</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-orange-500 text-white font-bold px-6 py-3.5 rounded-2xl hover:bg-orange-600 transition shadow text-sm">Buscar</button>
        </form>

        <!-- Chips de filtro activo -->
        <?php if ($q || $cat): ?>
        <div class="flex flex-wrap justify-center gap-2 pt-1">
            <?php if ($q): ?>
                <span class="bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1 rounded-full">
                    "<?= e($q) ?>"
                    <a href="index.php<?= $cat ? '?cat='.urlencode($cat) : '' ?>" class="ml-1 hover:text-orange-900">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($cat): ?>
                <span class="bg-sky-100 text-sky-700 text-xs font-bold px-3 py-1 rounded-full">
                    <?= e($cat) ?>
                    <a href="index.php<?= $q ? '?q='.urlencode($q) : '' ?>" class="ml-1 hover:text-sky-900">✕</a>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</header>

<!-- ── CATÁLOGO ───────────────────────────────────────────────────── -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-10">

    <?php if (empty($empresas)): ?>
        <div class="bg-white rounded-3xl border border-dashed p-16 text-center">
            <p class="text-5xl mb-4">🍽️</p>
            <p class="text-gray-500 font-medium">No se encontraron locales.</p>
            <a href="index.php" class="inline-block mt-4 text-sm font-bold text-orange-500 hover:underline">Ver todos</a>
        </div>
    <?php else: ?>
        <p class="text-xs text-gray-400"><?= count($empresas) ?> local<?= count($empresas) !== 1 ? 'es' : '' ?> encontrado<?= count($empresas) !== 1 ? 's' : '' ?></p>

        <?php foreach ($empresas as $emp):
            $prods = $conn->prepare("SELECT * FROM producto WHERE id_empresa = ? ORDER BY nombre");
            $prods->execute([$emp['id_empresa']]);
            $productos = $prods->fetchAll();
        ?>
        <section class="bg-white rounded-3xl p-5 sm:p-8 border border-gray-100 shadow-sm space-y-5">

            <!-- Cabecera empresa -->
            <div class="flex items-center gap-4 border-b pb-4">
                <a href="local.php?id=<?= (int)$emp['id_empresa'] ?>" class="group flex items-center gap-4 flex-1 min-w-0">
                    <img src="uploads/<?= e($emp['logo']) ?>"
                         class="w-14 h-14 rounded-2xl object-cover border bg-gray-50 flex-shrink-0 group-hover:opacity-80 transition"
                         onerror="this.src='https://placehold.co/56x56/f97316/white?text=?'"
                         alt="<?= e($emp['nombre']) ?>">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900 group-hover:text-orange-500 transition truncate"><?= e($emp['nombre']) ?></h2>
                        <div class="flex flex-wrap gap-2 mt-1">
                            <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2.5 py-0.5 rounded-lg"><?= e($emp['categoria']) ?></span>
                            <?php if ($emp['horarios']): ?>
                                <span class="text-xs text-gray-400">🕒 <?= e($emp['horarios']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <a href="local.php?id=<?= (int)$emp['id_empresa'] ?>"
                   class="hidden sm:block text-xs font-bold text-sky-500 border border-sky-300 px-3 py-1.5 rounded-xl hover:bg-sky-50 transition flex-shrink-0">
                   Ver local →
                </a>
            </div>

            <!-- Productos -->
            <?php if (empty($productos)): ?>
                <p class="text-xs text-gray-400 italic">Este local aún no publicó platos.</p>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($productos as $p): ?>
                    <div class="bg-gray-50 rounded-2xl p-3 border border-gray-100 flex flex-col hover:shadow-md transition">
                        <img src="uploads/<?= e($p['imagen']) ?>"
                             class="w-full h-36 sm:h-44 object-cover rounded-xl mb-3 bg-white"
                             onerror="this.src='https://placehold.co/300x200/f1f5f9/94a3b8?text=Sin+imagen'"
                             alt="<?= e($p['nombre']) ?>">
                        <div class="flex-1">
                            <h3 class="font-bold text-sm text-gray-800 leading-tight"><?= e($p['nombre']) ?></h3>
                            <p class="text-xs text-gray-500 line-clamp-2 mt-0.5"><?= e($p['descripcion'] ?? '') ?></p>
                        </div>
                        <div class="flex justify-between items-center mt-3 pt-2 border-t border-gray-200">
                            <span class="text-base font-black text-gray-900">$<?= number_format((float)$p['precio'], 2) ?></span>
                            <button onclick="abrirModal(<?= (int)$p['id_producto'] ?>, <?= (int)$emp['id_empresa'] ?>, '<?= addslashes(e($p['nombre'])) ?>', <?= (float)$p['precio'] ?>)"
                                    class="bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs px-3 py-1.5 rounded-xl transition shadow">
                                Pedir
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<!-- ── MODAL PEDIDO ───────────────────────────────────────────────── -->
<div id="modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <h3 id="mNombre" class="text-xl font-black text-gray-800"></h3>
        <p id="mPrecio"  class="text-lg font-bold text-orange-500"></p>
        <textarea id="mNotas" rows="3" maxlength="300"
                  placeholder="Aclaraciones (sin cebolla, extra queso...)"
                  class="w-full p-3 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 resize-none"></textarea>
        <div id="mFeedback" class="hidden text-xs font-bold text-center p-2 rounded-xl"></div>
        <div class="flex gap-3">
            <button onclick="cerrarModal()" class="flex-1 bg-gray-100 text-gray-600 font-bold py-3 rounded-xl hover:bg-gray-200 text-sm">Cancelar</button>
            <button id="mBtn" onclick="enviarPedido()" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow text-sm transition">Confirmar</button>
        </div>
        <?php if (!isset($_SESSION['id_cliente'])): ?>
            <p class="text-xs text-center text-amber-600 bg-amber-50 p-2 rounded-xl">
                ⚠️ <a href="login.php" class="font-bold underline">Iniciá sesión</a> como cliente para pedir.
            </p>
        <?php endif; ?>
    </div>
</div>

<script>
let _prod = {};
function abrirModal(id, idEmp, nombre, precio) {
    _prod = { id, idEmp, nombre, precio };
    document.getElementById('mNombre').textContent = nombre;
    document.getElementById('mPrecio').textContent = '$ ' + precio.toFixed(2);
    document.getElementById('mNotas').value = '';
    document.getElementById('mFeedback').className = 'hidden text-xs font-bold text-center p-2 rounded-xl';
    document.getElementById('mBtn').disabled = false;
    document.getElementById('mBtn').textContent = 'Confirmar';
    document.getElementById('modal').classList.replace('hidden','flex');
}
function cerrarModal() { document.getElementById('modal').classList.replace('flex','hidden'); }
document.getElementById('modal').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModal(); });

async function enviarPedido() {
    const btn = document.getElementById('mBtn');
    const fb  = document.getElementById('mFeedback');
    btn.disabled = true; btn.textContent = 'Enviando...';
    const fd = new FormData();
    fd.append('id_producto', _prod.id);
    fd.append('id_empresa',  _prod.idEmp);
    fd.append('notas',       document.getElementById('mNotas').value.trim());
    try {
        const r = await fetch('realizar_pedido.php', { method:'POST', body:fd });
        const d = await r.json();
        fb.className = `text-xs font-bold text-center p-2 rounded-xl ${d.status==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'}`;
        fb.textContent = d.message;
        if (d.status === 'success') {
            setTimeout(() => {
                const msg = `Hola! Pedido desde C.A.A.S.\n*Producto:* ${_prod.nombre}\n*Total:* $${_prod.precio.toFixed(2)}\n${document.getElementById('mNotas').value ? '*Notas:* '+document.getElementById('mNotas').value : ''}`;
                window.open(`https://wa.me/${d.telefono}?text=${encodeURIComponent(msg)}`, '_blank');
                cerrarModal();
            }, 1200);
        } else {
            btn.disabled = false; btn.textContent = 'Confirmar';
        }
    } catch {
        fb.className = 'text-xs font-bold text-center p-2 rounded-xl bg-red-100 text-red-700';
        fb.textContent = 'Error de conexión.';
        btn.disabled = false; btn.textContent = 'Confirmar';
    }
}

// Hamburguesa
document.getElementById('btnNav').addEventListener('click', () => {
    const m = document.getElementById('mobileMenu');
    const isHidden = m.classList.contains('hidden');
    m.classList.toggle('hidden', !isHidden);
    document.getElementById('iconBars').classList.toggle('hidden',  isHidden);
    document.getElementById('iconX').classList.toggle('hidden',    !isHidden);
});
</script>
</body>
</html>
