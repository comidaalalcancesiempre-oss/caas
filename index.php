<?php
require_once 'config.php';

$q   = trim($_GET['q']   ?? '');
$cat = trim($_GET['cat'] ?? '');

$cats = $conn->query(
    "SELECT DISTINCT categoria FROM empresa WHERE estado_aprobacion = 'APROBADO' AND categoria <> '' ORDER BY categoria"
)->fetchAll(PDO::FETCH_COLUMN);

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
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C.A.A.S. - Comida Al Alcance Siempre</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <!-- Aplicar tema antes del paint -->
    <script>
        const _t = localStorage.getItem('caas_tema') || 'light';
        if (_t === 'dark') document.documentElement.classList.add('dark');
    </script>
    <script src="lang.js"></script>
    <script src="theme.js"></script>
</head>
<body class="bg-slate-50 dark:bg-gray-950 min-h-screen text-gray-800 dark:text-gray-200 transition-colors duration-300">

<!-- ── NAVBAR ─────────────────────────────────────────────────────── -->
<nav class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2">
            <span class="bg-orange-500 text-white font-black text-xl px-3 py-1 rounded-xl">C.A.A.S.</span>
            <span class="hidden md:inline text-xs font-semibold text-gray-400 dark:text-gray-500">Comida Al Alcance Siempre</span>
        </a>

        <!-- Desktop -->
        <div class="hidden sm:flex items-center gap-2">
            <!-- Toggle tema -->
            <button id="btnToggleTema"
                    class="text-lg p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    title="Modo oscuro">🌙</button>
            <!-- Ajustes -->
            <a href="ajustes.php"
               class="text-lg p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition"
               title="Ajustes">⚙️</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Dropdown de perfil -->
                <div class="relative" id="menuPerfilWrap">
                    <button id="btnPerfil"
                            class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <span class="text-base">👤</span>
                        <span class="max-w-[120px] truncate"><?= e($_SESSION['nombre']) ?></span>
                        <span class="text-xs text-gray-400">▾</span>
                    </button>
                    <!-- Dropdown -->
                    <div id="dropdownPerfil"
                         class="hidden absolute right-0 top-full mt-2 w-52 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-2xl shadow-xl z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b dark:border-gray-800">
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate"><?= e($_SESSION['nombre']) ?></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500"><?= e($_SESSION['tipo']) ?></p>
                        </div>
                        <div class="py-1">
                            <a href="perfil.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <span>⚙️</span> Mi Perfil
                            </a>
                            <?php if ($_SESSION['tipo'] === 'CLIENTE'): ?>
                            <a href="mis_pedidos.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <span>📦</span> Mis Pedidos
                            </a>
                            <?php elseif ($_SESSION['tipo'] === 'EMPRESA'): ?>
                            <a href="empresa.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <span>🏪</span> Mi Panel
                            </a>
                            <?php elseif ($_SESSION['tipo'] === 'ADMIN'): ?>
                            <a href="admin.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <span>🛡️</span> Panel Admin
                            </a>
                            <?php endif; ?>
                            <a href="ajustes.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <span>🌐</span> Ajustes e Idioma
                            </a>
                        </div>
                        <div class="border-t dark:border-gray-800 py-1">
                            <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition font-bold">
                                <span>🚪</span> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php"    class="text-sm font-bold text-orange-500 border border-orange-400 px-4 py-2 rounded-xl hover:bg-orange-50 dark:hover:bg-orange-950 transition" data-i18n="nav_login">Iniciar Sesión</a>
                <a href="registro.php" class="text-sm font-bold bg-orange-500 text-white px-4 py-2 rounded-xl hover:bg-orange-600 transition shadow" data-i18n="nav_register">Registrarse</a>
            <?php endif; ?>
        </div>

        <!-- Hamburguesa mobile -->
        <button id="btnNav" class="sm:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
            <svg class="w-6 h-6 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path id="iconBars"  stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                <path id="iconX" class="hidden" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Mobile menu -->
    <div id="mobileMenu" class="hidden sm:hidden border-t dark:border-gray-800 px-4 py-3 space-y-2 bg-white dark:bg-gray-900">
        <div class="flex items-center gap-3 pb-2 border-b dark:border-gray-800">
            <button id="btnToggleTemaMobile" onclick="setTema(getTema()==='dark'?'light':'dark'); actualizarIconoTema();"
                    class="text-lg p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">🌙</button>
            <a href="ajustes.php" class="text-lg p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">⚙️</a>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="text-sm font-bold text-gray-700 dark:text-gray-300"><span data-i18n="nav_hello">Hola</span>, <?= e($_SESSION['nombre']) ?></p>
            <?php if ($_SESSION['tipo'] === 'ADMIN'): ?>
                <a href="admin.php"   class="block text-sm font-bold text-sky-600" data-i18n="nav_admin">Admin</a>
            <?php elseif ($_SESSION['tipo'] === 'EMPRESA'): ?>
                <a href="empresa.php" class="block text-sm font-bold text-sky-600" data-i18n="nav_panel">Mi Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="block text-sm font-bold text-red-500" data-i18n="nav_logout">Salir</a>
        <?php else: ?>
            <a href="login.php"    class="block text-sm font-bold text-orange-500" data-i18n="nav_login">Iniciar Sesión</a>
            <a href="registro.php" class="block text-sm font-bold text-orange-500" data-i18n="nav_register">Registrarse</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ── HERO + BUSCADOR ────────────────────────────────────────────── -->
<header class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 py-8 px-4">
    <div class="max-w-4xl mx-auto text-center space-y-4">
        <h1 class="text-2xl sm:text-4xl font-black text-gray-900 dark:text-white" data-i18n="hero_title">
            Encontrá tu comida favorita 
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400" data-i18n="hero_sub">
            Los mejores locales de Colonia del Sacramento, en un solo lugar.
        </p>

        <form method="GET" class="flex flex-col sm:flex-row gap-3 pt-2 max-w-3xl mx-auto">
            <input type="text" name="q" value="<?= e($q) ?>"
                   data-i18n-ph="search_ph"
                   placeholder="Buscar local o plato..."
                   class="flex-1 p-3.5 border dark:border-gray-700 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-orange-400 bg-gray-50 dark:bg-gray-800 dark:text-white">
            <select name="cat" class="p-3.5 border dark:border-gray-700 rounded-2xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                <option value="" data-i18n="search_cats">Todas las categorías</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit"
                    class="bg-orange-500 text-white font-bold px-6 py-3.5 rounded-2xl hover:bg-orange-600 transition shadow text-sm"
                    data-i18n="search_btn">Buscar</button>
        </form>

        <?php if ($q || $cat): ?>
        <div class="flex flex-wrap justify-center gap-2 pt-1">
            <?php if ($q): ?>
                <span class="bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 text-xs font-bold px-3 py-1 rounded-full">
                    "<?= e($q) ?>"
                    <a href="index.php<?= $cat ? '?cat='.urlencode($cat) : '' ?>" class="ml-1 hover:text-orange-900">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($cat): ?>
                <span class="bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300 text-xs font-bold px-3 py-1 rounded-full">
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
        <div class="bg-white dark:bg-gray-900 rounded-3xl border dark:border-gray-800 border-dashed p-16 text-center">
            <p class="text-5xl mb-4">🍽️</p>
            <p class="text-gray-500 dark:text-gray-400 font-medium" data-i18n="no_results">No se encontraron locales.</p>
            <a href="index.php" class="inline-block mt-4 text-sm font-bold text-orange-500 hover:underline" data-i18n="see_all">Ver todos</a>
        </div>
    <?php else: ?>
        <p class="text-xs text-gray-400 dark:text-gray-500" id="countLabel">
            <?= count($empresas) ?> local<?= count($empresas) !== 1 ? 'es' : '' ?> encontrado<?= count($empresas) !== 1 ? 's' : '' ?>
        </p>

        <?php foreach ($empresas as $emp):
            $prods = $conn->prepare("SELECT * FROM producto WHERE id_empresa = ? ORDER BY nombre");
            $prods->execute([$emp['id_empresa']]);
            $productos = $prods->fetchAll();
        ?>
        <section class="bg-white dark:bg-gray-900 rounded-3xl p-5 sm:p-8 border dark:border-gray-800 border-gray-100 shadow-sm space-y-5">

            <div class="flex items-center gap-4 border-b dark:border-gray-800 pb-4">
                <a href="local.php?id=<?= (int)$emp['id_empresa'] ?>" class="group flex items-center gap-4 flex-1 min-w-0">
                    <img src="uploads/<?= e($emp['logo']) ?>"
                         class="w-14 h-14 rounded-2xl object-cover border dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0 group-hover:opacity-80 transition"
                         onerror="this.src='https://placehold.co/56x56/f97316/white?text=?'"
                         alt="<?= e($emp['nombre']) ?>">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-orange-500 transition truncate"><?= e($emp['nombre']) ?></h2>
                        <div class="flex flex-wrap gap-2 mt-1">
                            <span class="bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 text-xs font-bold px-2.5 py-0.5 rounded-lg"><?= e($emp['categoria']) ?></span>
                            <?php if ($emp['horarios']): ?>
                                <span class="text-xs text-gray-400 dark:text-gray-500">🕒 <?= e($emp['horarios']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <a href="local.php?id=<?= (int)$emp['id_empresa'] ?>"
                   class="hidden sm:block text-xs font-bold text-sky-500 border border-sky-300 dark:border-sky-700 px-3 py-1.5 rounded-xl hover:bg-sky-50 dark:hover:bg-sky-900/30 transition flex-shrink-0"
                   data-i18n="see_local">Ver local →</a>
            </div>

            <?php if (empty($productos)): ?>
                <p class="text-xs text-gray-400 dark:text-gray-500 italic" data-i18n="no_dishes">Este local aún no publicó platos.</p>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($productos as $p): ?>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-3 border border-gray-100 dark:border-gray-700 flex flex-col hover:shadow-md transition">
                        <img src="uploads/<?= e($p['imagen']) ?>"
                             class="w-full h-36 sm:h-44 object-cover rounded-xl mb-3 bg-white dark:bg-gray-700"
                             onerror="this.src='https://placehold.co/300x200/f1f5f9/94a3b8?text=Sin+imagen'"
                             alt="<?= e($p['nombre']) ?>">
                        <div class="flex-1">
                            <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100 leading-tight"><?= e($p['nombre']) ?></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5"><?= e($p['descripcion'] ?? '') ?></p>
                        </div>
                        <div class="flex justify-between items-center mt-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-base font-black text-gray-900 dark:text-white">$<?= number_format((float)$p['precio'], 2) ?></span>
                            <button onclick="abrirModal(<?= (int)$p['id_producto'] ?>, <?= (int)$emp['id_empresa'] ?>, '<?= addslashes(e($p['nombre'])) ?>', <?= (float)$p['precio'] ?>)"
                                    class="bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs px-3 py-1.5 rounded-xl transition shadow"
                                    data-i18n="local_order">Pedir</button>
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
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4 border dark:border-gray-700">
        <h3 id="mNombre" class="text-xl font-black text-gray-800 dark:text-white"></h3>
        <p  id="mPrecio" class="text-lg font-bold text-orange-500"></p>
        <textarea id="mNotas" rows="3" maxlength="300"
                  data-i18n-ph="modal_notes_ph"
                  placeholder="Aclaraciones (sin cebolla, extra queso...)"
                  class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 resize-none bg-gray-50 dark:bg-gray-800 dark:text-white"></textarea>
        <div id="mFeedback" class="hidden text-xs font-bold text-center p-2 rounded-xl"></div>
        <div class="flex gap-3">
            <button onclick="cerrarModal()"
                    class="flex-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-bold py-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 text-sm"
                    data-i18n="modal_cancel">Cancelar</button>
            <button id="mBtn" onclick="enviarPedido()"
                    class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow text-sm transition"
                    data-i18n="modal_confirm">Confirmar</button>
        </div>
        <?php if (!isset($_SESSION['id_cliente'])): ?>
            <p class="text-xs text-center text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 p-2 rounded-xl">
                ⚠️ <a href="login.php" class="font-bold underline" data-i18n="modal_login_warn">Iniciá sesión como cliente para pedir.</a>
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
    const btn = document.getElementById('mBtn');
    btn.disabled = false;
    btn.textContent = t('modal_confirm');
    document.getElementById('modal').classList.replace('hidden','flex');
}
function cerrarModal() { document.getElementById('modal').classList.replace('flex','hidden'); }
document.getElementById('modal').addEventListener('click', e => { if (e.target === e.currentTarget) cerrarModal(); });

async function enviarPedido() {
    const btn = document.getElementById('mBtn');
    const fb  = document.getElementById('mFeedback');
    btn.disabled = true;
    btn.textContent = t('modal_sending');
    const fd = new FormData();
    fd.append('id_producto', _prod.id);
    fd.append('id_empresa',  _prod.idEmp);
    fd.append('notas',       document.getElementById('mNotas').value.trim());
    try {
        const r = await fetch('realizar_pedido.php', { method:'POST', body:fd });
        const d = await r.json();
        fb.className = `text-xs font-bold text-center p-2 rounded-xl ${d.status==='success'?'bg-green-100 text-green-700':'bg-red-100 text-red-700'}`;
        fb.textContent = d.status === 'success' ? t('modal_success') : d.message;
        if (d.status === 'success') {
            setTimeout(() => {
                const notas = document.getElementById('mNotas').value;
                const msg = `Hola! Pedido desde C.A.A.S.\n*Producto:* ${_prod.nombre}\n*Total:* $${_prod.precio.toFixed(2)}\n${notas ? '*Notas:* '+notas : ''}`;
                window.open(`https://wa.me/${d.telefono}?text=${encodeURIComponent(msg)}`, '_blank');
                cerrarModal();
            }, 1200);
        } else {
            btn.disabled = false;
            btn.textContent = t('modal_confirm');
        }
    } catch {
        fb.className = 'text-xs font-bold text-center p-2 rounded-xl bg-red-100 text-red-700';
        fb.textContent = t('modal_conn_err');
        btn.disabled = false;
        btn.textContent = t('modal_confirm');
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

// Dropdown de perfil
const btnPerfil = document.getElementById('btnPerfil');
const dropdownPerfil = document.getElementById('dropdownPerfil');
if (btnPerfil) {
    btnPerfil.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownPerfil.classList.toggle('hidden');
    });
    document.addEventListener('click', () => dropdownPerfil.classList.add('hidden'));
}
</script>
</body>
</html>
