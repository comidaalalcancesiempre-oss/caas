<?php
require_once 'config.php';
require_once 'uruguay.php';

$q            = trim($_GET['q']      ?? '');
$cat          = trim($_GET['cat']    ?? '');
$depto        = trim($_GET['depto']  ?? '');
$modo_turista = isset($_GET['turista']) && $_GET['turista'] === '1';

// Departamento por defecto del usuario logueado
if (!$depto && isset($_SESSION['user_id'])) {
    $stmtDep = $conn->prepare("SELECT departamento FROM usuario WHERE id_usuario = ?");
    $stmtDep->execute([$_SESSION['user_id']]);
    $deptoUsuario = $stmtDep->fetchColumn();
    // Solo pre-filtrar si no viene de una búsqueda manual
    if ($deptoUsuario && !$q && !$cat) {
        $depto = $deptoUsuario;
    }
}

// Categorías dinámicas
$cats = $conn->query(
    "SELECT DISTINCT categoria FROM empresa WHERE estado_aprobacion='APROBADO' AND categoria<>'' ORDER BY categoria"
)->fetchAll(PDO::FETCH_COLUMN);

// Empresas principales con filtros
$sql    = "SELECT e.* FROM empresa e WHERE e.estado_aprobacion='APROBADO'";
$params = [];
if ($q) {
    $sql .= " AND (e.nombre LIKE ? OR e.categoria LIKE ? OR e.localidad LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if ($cat)   { $sql .= " AND e.categoria = ?";    $params[] = $cat; }
if ($depto) { $sql .= " AND e.departamento = ?"; $params[] = $depto; }
if ($modo_turista) { $sql .= " AND e.modo_turista = 1"; }
$sql .= " ORDER BY e.nombre";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$empresas = $stmt->fetchAll();

// Nuevos restaurantes — últimas 2 semanas, máximo 6
$nuevos = $conn->query(
    "SELECT e.* FROM empresa e
     WHERE e.estado_aprobacion='APROBADO'
       AND e.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
     ORDER BY e.created_at DESC LIMIT 6"
)->fetchAll();

// Restaurantes modo turista (para el mapa)
$turistas = $conn->query(
    "SELECT e.id_empresa, e.nombre, e.categoria, e.latitud, e.longitud,
            e.plato_tipico, e.departamento, e.localidad
     FROM empresa e
     WHERE e.estado_aprobacion='APROBADO' AND e.latitud IS NOT NULL AND e.longitud IS NOT NULL"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
<?php
$pageTitle = 'C.A.A.S. - Comida Al Alcance Siempre';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>';
require_once 'head.php';
?>
</head>
<body class="bg-slate-50 dark:bg-gray-950 min-h-screen text-gray-800 dark:text-gray-200 transition-colors duration-300">

<!-- ════════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════ -->
<nav class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">

        <!-- Logo -->
        <a href="index.php" class="flex items-center gap-2 flex-shrink-0">
            <span class="bg-orange-500 text-white font-black text-xl px-3 py-1.5 rounded-xl tracking-wide notranslate">C.A.A.S.</span>
            <span class="hidden lg:inline text-xs font-semibold text-gray-400 dark:text-gray-500 notranslate">Uruguay</span>
        </a>

        <!-- Buscador rápido centrado (desktop) -->
        <form method="GET" class="hidden md:flex flex-1 max-w-lg mx-4">
            <div class="flex w-full rounded-xl overflow-hidden border dark:border-gray-700 bg-gray-50 dark:bg-gray-800 focus-within:ring-2 focus-within:ring-orange-400">
                <input type="text" name="q" value="<?= e($q) ?>"
                       placeholder="Buscar local o plato..."
                       class="flex-1 px-4 py-2.5 text-sm bg-transparent outline-none dark:text-white">
                <button type="submit" class="px-4 bg-orange-500 hover:bg-orange-600 text-white transition">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </button>
            </div>
        </form>

        <!-- Controles derecha (desktop) -->
        <div class="hidden sm:flex items-center gap-2">
            <!-- Modo Turista toggle -->
            <a href="?<?= http_build_query(array_merge($_GET, ['turista' => $modo_turista ? '0' : '1'])) ?>"
               class="flex items-center gap-1.5 text-xs font-bold px-3 py-2 rounded-xl transition
                      <?= $modo_turista
                          ? 'bg-emerald-500 text-white hover:bg-emerald-600'
                          : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' ?>">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
                <span data-i18n="<?= $modo_turista ? 'turista_off' : 'turista_mode' ?>">
                    <?= $modo_turista ? 'Modo Normal' : 'Modo Turista' ?>
                </span>
            </a>

            <button id="btnToggleTema"
                    class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    title="Cambiar tema">
                <i data-lucide="moon" class="w-5 h-5 text-gray-600 dark:text-gray-300"></i>
            </button>

            <!-- Selector de idioma rápido en navbar -->
            <div class="relative" id="langDropWrap">
                <button id="btnLangNav"
                        class="flex items-center gap-1.5 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition text-sm"
                        title="Idioma">
                    <span id="navLangIndicator" class="text-lg">🇺🇾</span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400"></i>
                </button>
                <div id="langDropMenu"
                     class="hidden absolute right-0 top-full mt-2 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-2xl shadow-xl z-50 p-2 grid grid-cols-2 gap-1 w-44 animate__animated animate__fadeIn animate__faster">
                    <?php
                    $langs = [
                        'es'=>['🇺🇾','ES'], 'en'=>['🇺🇸','EN'], 'pt'=>['🇧🇷','PT'],
                        'fr'=>['🇫🇷','FR'], 'it'=>['🇮🇹','IT'], 'de'=>['🇩🇪','DE'],
                        'zh'=>['🇨🇳','中文'], 'ja'=>['🇯🇵','JA'],
                    ];
                    foreach ($langs as $code => [$bandera, $label]):
                    ?>
                    <button onclick="switchGoogleLang('<?= $code ?>'); document.getElementById('navLangIndicator').textContent='<?= $bandera ?>'; document.getElementById('langDropMenu').classList.add('hidden');"
                            data-lang-btn="<?= $code ?>"
                            class="lang-btn flex items-center gap-2 px-2 py-1.5 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-orange-50 dark:hover:bg-orange-950 transition notranslate">
                        <span><?= $bandera ?></span> <?= $label ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="ajustes.php" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Ajustes">
                <i data-lucide="settings" class="w-5 h-5 text-gray-600 dark:text-gray-300"></i>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Dropdown perfil -->
                <div class="relative" id="menuPerfilWrap">
                    <button id="btnPerfil"
                            class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <i data-lucide="user-circle" class="w-5 h-5"></i>
                        <span class="max-w-[110px] truncate"><?= e($_SESSION['nombre']) ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                    </button>
                    <div id="dropdownPerfil"
                         class="hidden absolute right-0 top-full mt-2 w-52 bg-white dark:bg-gray-900 border dark:border-gray-700 rounded-2xl shadow-xl z-50 overflow-hidden animate__animated animate__fadeIn animate__faster">
                        <div class="px-4 py-3 border-b dark:border-gray-800">
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate"><?= e($_SESSION['nombre']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($_SESSION['tipo']) ?></p>
                        </div>
                        <div class="py-1">
                            <a href="perfil.php"       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"><i data-lucide="user" class="w-4 h-4"></i> Mi Perfil</a>
                            <?php if ($_SESSION['tipo'] === 'CLIENTE'): ?>
                            <a href="mis_pedidos.php"  class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"><i data-lucide="package" class="w-4 h-4"></i> Mis Pedidos</a>
                            <?php elseif ($_SESSION['tipo'] === 'EMPRESA'): ?>
                            <a href="empresa.php"      class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"><i data-lucide="store" class="w-4 h-4"></i> Mi Panel</a>
                            <?php elseif ($_SESSION['tipo'] === 'ADMIN'): ?>
                            <a href="admin.php"        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"><i data-lucide="shield" class="w-4 h-4"></i> Panel Admin</a>
                            <?php endif; ?>
                            <a href="ajustes.php"      class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"><i data-lucide="globe" class="w-4 h-4"></i> Ajustes e Idioma</a>
                        </div>
                        <div class="border-t dark:border-gray-800 py-1">
                            <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition font-bold">
                                <i data-lucide="log-out" class="w-4 h-4"></i> Cerrar Sesión
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
            <i data-lucide="menu" id="iconBars" class="w-6 h-6 text-gray-700 dark:text-gray-300"></i>
            <i data-lucide="x"   id="iconX"    class="w-6 h-6 text-gray-700 dark:text-gray-300 hidden"></i>
        </button>
    </div>

    <!-- Mobile menu -->
    <div id="mobileMenu" class="hidden sm:hidden border-t dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
        <form method="GET" class="flex gap-2 mb-3">
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar..."
                   class="flex-1 p-2.5 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none">
            <button type="submit" class="px-3 bg-orange-500 text-white rounded-xl"><i data-lucide="search" class="w-4 h-4"></i></button>
        </form>
        <a href="?<?= http_build_query(array_merge($_GET, ['turista' => $modo_turista ? '0' : '1'])) ?>"
           class="flex items-center gap-2 text-sm font-bold <?= $modo_turista ? 'text-emerald-600' : 'text-gray-600 dark:text-gray-400' ?>">
            <i data-lucide="map-pin" class="w-4 h-4"></i>
            <?= $modo_turista ? 'Desactivar Modo Turista' : 'Activar Modo Turista' ?>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="text-sm font-bold text-gray-700 dark:text-gray-300"><?= e($_SESSION['nombre']) ?></p>
            <a href="perfil.php"      class="block text-sm text-gray-600 dark:text-gray-400">Mi Perfil</a>
            <?php if ($_SESSION['tipo'] === 'CLIENTE'): ?>
            <a href="mis_pedidos.php" class="block text-sm text-gray-600 dark:text-gray-400">Mis Pedidos</a>
            <?php elseif ($_SESSION['tipo'] === 'EMPRESA'): ?>
            <a href="empresa.php"     class="block text-sm text-gray-600 dark:text-gray-400">Mi Panel</a>
            <?php elseif ($_SESSION['tipo'] === 'ADMIN'): ?>
            <a href="admin.php"       class="block text-sm text-gray-600 dark:text-gray-400">Panel Admin</a>
            <?php endif; ?>
            <a href="logout.php"      class="block text-sm font-bold text-red-500">Cerrar Sesión</a>
        <?php else: ?>
            <a href="login.php"    class="block text-sm font-bold text-orange-500">Iniciar Sesión</a>
            <a href="registro.php" class="block text-sm font-bold text-orange-500">Registrarse</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ════════════════════════════════════════════════
     BANNER MODO TURISTA
════════════════════════════════════════════════ -->
<?php if ($modo_turista): ?>
<div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white py-3 px-4 text-center text-sm font-bold animate__animated animate__fadeIn">
    <span class="inline-flex items-center gap-2">
        <i data-lucide="map" class="w-4 h-4"></i>
        Modo Turista activo — Mostrando platos típicos y recomendaciones gastronómicas de Uruguay
        <a href="?" class="underline hover:no-underline ml-2 opacity-80">Desactivar</a>
    </span>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     HERO + FILTROS
════════════════════════════════════════════════ -->
<header class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 py-8 px-4">
    <div class="max-w-5xl mx-auto space-y-5">
        <div class="text-center">
            <h1 class="text-2xl sm:text-4xl font-black text-gray-900 dark:text-white leading-tight" data-i18n="hero_title">
                Encontrá tu comida favorita 🍔🍕
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2" data-i18n="hero_sub">
                Los mejores locales del barrio, en un solo lugar.
            </p>
        </div>

        <!-- Filtros -->
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 max-w-5xl mx-auto">
            <?php if ($modo_turista): ?><input type="hidden" name="turista" value="1"><?php endif; ?>

            <input type="text" name="q" value="<?= e($q) ?>"
                   placeholder="Buscar local o plato..."
                   class="col-span-1 sm:col-span-2 lg:col-span-1 p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">

            <select name="cat" class="p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                <option value="">Todas las categorías</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="depto" class="p-3 border dark:border-gray-700 rounded-xl text-sm bg-gray-50 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-orange-400">
                <option value="">Todos los departamentos</option>
                <?php foreach (array_keys(DEPARTAMENTOS_UY) as $d): ?>
                    <option value="<?= e($d) ?>" <?= $depto === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit"
                    class="btn-caas px-6 py-3 rounded-xl shadow text-sm font-bold flex items-center justify-center gap-2">
                <i data-lucide="search" class="w-4 h-4"></i>
                <span data-i18n="search_btn">Buscar</span>
            </button>
        </form>

        <!-- Chips de filtros activos -->
        <?php if ($q || $cat || $depto || $modo_turista): ?>
        <div class="flex flex-wrap justify-center gap-2">
            <?php if ($q): ?>
                <span class="inline-flex items-center gap-1 bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 text-xs font-bold px-3 py-1 rounded-full">
                    "<?= e($q) ?>"
                    <a href="?<?= http_build_query(array_diff_key($_GET, ['q'=>''])) ?>" class="hover:text-orange-900 ml-1">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($cat): ?>
                <span class="inline-flex items-center gap-1 bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300 text-xs font-bold px-3 py-1 rounded-full">
                    <?= e($cat) ?>
                    <a href="?<?= http_build_query(array_diff_key($_GET, ['cat'=>''])) ?>" class="ml-1">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($depto): ?>
                <span class="inline-flex items-center gap-1 bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 text-xs font-bold px-3 py-1 rounded-full">
                    <i data-lucide="map-pin" class="w-3 h-3"></i> <?= e($depto) ?>
                    <a href="?<?= http_build_query(array_diff_key($_GET, ['depto'=>''])) ?>" class="ml-1">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($modo_turista): ?>
                <span class="inline-flex items-center gap-1 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold px-3 py-1 rounded-full">
                    <i data-lucide="map" class="w-3 h-3"></i> Modo Turista
                    <a href="?" class="ml-1">✕</a>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</header>

<!-- ════════════════════════════════════════════════
     MAPA NACIONAL (si hay empresas con coordenadas)
════════════════════════════════════════════════ -->
<?php if (!empty($turistas)): ?>
<section class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 px-4 py-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-base text-gray-800 dark:text-white flex items-center gap-2">
                <i data-lucide="map" class="w-5 h-5 text-orange-500"></i>
                Mapa de Restaurantes — Uruguay
            </h2>
            <button id="btnToggleMapa"
                    class="text-xs font-bold text-sky-500 border border-sky-300 dark:border-sky-700 px-3 py-1.5 rounded-xl hover:bg-sky-50 dark:hover:bg-sky-900/20 transition flex items-center gap-1">
                <i data-lucide="map" class="w-3.5 h-3.5"></i>
                <span id="labelMapa">Ver mapa</span>
            </button>
        </div>
        <div id="mapaWrapper" class="hidden">
            <div id="mapaNacional" style="height:380px;border-radius:16px;z-index:1;" class="border dark:border-gray-700 shadow-sm"></div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     NUEVOS RESTAURANTES
════════════════════════════════════════════════ -->
<?php if (!empty($nuevos) && !$q && !$cat && !$modo_turista): ?>
<section class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-950/20 dark:to-amber-950/20 border-b dark:border-gray-800 px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <span class="bg-orange-500 text-white text-xs font-black px-3 py-1 rounded-full animate-pulse">NUEVO</span>
            <h2 class="text-xl font-black text-gray-900 dark:text-white" data-i18n="nuevos_title">
                Nuevos Restaurantes 🌟
            </h2>
            <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto">Últimas 2 semanas</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($nuevos as $i => $nu): ?>
            <a href="local.php?id=<?= (int)$nu['id_empresa'] ?>"
               class="group bg-white dark:bg-gray-900 rounded-2xl border dark:border-gray-800 p-4 text-center shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200 fade-in"
               style="animation-delay:<?= $i*0.05 ?>s">
                <img src="uploads/<?= e($nu['logo'] ?? 'default_logo.png') ?>"
                     class="w-14 h-14 rounded-xl object-cover mx-auto mb-2 bg-gray-100 dark:bg-gray-800 group-hover:scale-105 transition"
                     onerror="this.src='https://placehold.co/56x56/f97316/white?text=?'"
                     alt="<?= e($nu['nombre']) ?>">
                <p class="font-bold text-xs text-gray-900 dark:text-white leading-tight line-clamp-2"><?= e($nu['nombre']) ?></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5"><?= e($nu['localidad'] ?? $nu['departamento'] ?? '') ?></p>
                <span class="inline-block mt-1.5 bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 text-xs font-bold px-2 py-0.5 rounded-full"><?= e($nu['categoria']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     CATÁLOGO PRINCIPAL
════════════════════════════════════════════════ -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-8">

    <?php if (!empty($depto) && !$q): ?>
    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
        <i data-lucide="map-pin" class="w-4 h-4 text-orange-500"></i>
        Mostrando restaurantes en <strong class="text-gray-900 dark:text-white"><?= e($depto) ?></strong>
        <a href="?" class="text-orange-500 hover:underline text-xs ml-1">(ver todos)</a>
    </div>
    <?php endif; ?>

    <?php if (empty($empresas)): ?>
        <div class="bg-white dark:bg-gray-900 rounded-3xl border dark:border-gray-800 border-dashed p-16 text-center">
            <i data-lucide="utensils" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4"></i>
            <p class="font-bold text-gray-700 dark:text-gray-300 text-lg" data-i18n="no_results">No se encontraron locales.</p>
            <a href="index.php" class="inline-block mt-4 text-sm font-bold text-orange-500 hover:underline" data-i18n="see_all">Ver todos</a>
        </div>
    <?php else: ?>
        <p class="text-xs text-gray-400 dark:text-gray-500">
            <?= count($empresas) ?> local<?= count($empresas)!==1?'es':'' ?> encontrado<?= count($empresas)!==1?'s':'' ?>
        </p>

        <?php foreach ($empresas as $emp):
            $prods = $conn->prepare("SELECT * FROM producto WHERE id_empresa = ? ORDER BY nombre LIMIT 8");
            $prods->execute([$emp['id_empresa']]);
            $productos = $prods->fetchAll();
        ?>
        <section class="bg-white dark:bg-gray-900 rounded-3xl border dark:border-gray-800 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">

            <!-- Cabecera empresa -->
            <div class="p-5 sm:p-6 border-b dark:border-gray-800">
                <div class="flex items-center gap-4">
                    <a href="local.php?id=<?= (int)$emp['id_empresa'] ?>" class="group flex-shrink-0">
                        <img src="uploads/<?= e($emp['logo'] ?? 'default_logo.png') ?>"
                             class="w-16 h-16 rounded-2xl object-cover border dark:border-gray-700 bg-gray-50 dark:bg-gray-800 group-hover:scale-105 transition"
                             onerror="this.src='https://placehold.co/64x64/f97316/white?text=?'"
                             alt="<?= e($emp['nombre']) ?>">
                    </a>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="local.php?id=<?= (int)$emp['id_empresa'] ?>"
                                   class="text-lg font-bold text-gray-900 dark:text-white hover:text-orange-500 transition block truncate notranslate">
                                    <?= e($emp['nombre']) ?>
                                </a>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 text-xs font-bold px-2.5 py-0.5 rounded-lg">
                                        <?= e($emp['categoria']) ?>
                                    </span>
                                    <?php if ($emp['departamento']): ?>
                                    <span class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>
                                        <?= e($emp['localidad'] ?? $emp['departamento']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($emp['horarios']): ?>
                                    <span class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        <?= e($emp['horarios']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($emp['modo_turista']): ?>
                                    <span class="flex items-center gap-1 text-xs bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 font-bold px-2 py-0.5 rounded-lg">
                                        <i data-lucide="star" class="w-3 h-3"></i> Turista
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($emp['modo_turista'] && $emp['plato_tipico']): ?>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">
                                    🍽️ Plato típico: <?= e($emp['plato_tipico']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <a href="local.php?id=<?= (int)$emp['id_empresa'] ?>"
                               class="hidden sm:flex items-center gap-1 text-xs font-bold text-sky-500 border border-sky-300 dark:border-sky-700 px-3 py-1.5 rounded-xl hover:bg-sky-50 dark:hover:bg-sky-900/20 transition flex-shrink-0">
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                Ver local
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de productos -->
            <?php if (empty($productos)): ?>
                <p class="px-6 py-4 text-xs text-gray-400 dark:text-gray-500 italic" data-i18n="no_dishes">Este local aún no publicó platos.</p>
            <?php else: ?>
                <div class="p-4 sm:p-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                    <?php foreach ($productos as $p): ?>
                    <div class="group bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col">
                        <div class="relative overflow-hidden">
                            <img src="uploads/<?= e($p['imagen']) ?>"
                                 class="w-full h-32 sm:h-40 object-cover group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='https://placehold.co/300x200/f1f5f9/94a3b8?text=Sin+imagen'"
                                 alt="<?= e($p['nombre']) ?>"
                                 loading="lazy">
                        </div>
                        <div class="p-3 flex flex-col flex-1">
                            <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100 leading-tight line-clamp-1 notranslate"><?= e($p['nombre']) ?></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mt-0.5 flex-1"><?= e($p['descripcion'] ?? '') ?></p>
                            <div class="flex justify-between items-center mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <span class="text-base font-black text-gray-900 dark:text-white">$<?= number_format((float)$p['precio'], 2) ?></span>
                                <button onclick="abrirModal(<?= (int)$p['id_producto'] ?>,<?= (int)$emp['id_empresa'] ?>,'<?= addslashes(e($p['nombre'])) ?>',<?= (float)$p['precio'] ?>)"
                                        class="btn-caas text-xs px-3 py-1.5 rounded-xl flex items-center gap-1">
                                    <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                                    <span data-i18n="local_order">Pedir</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<!-- ════════════════════════════════════════════════
     MODAL PEDIDO
════════════════════════════════════════════════ -->
<div id="modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 max-w-md w-full shadow-2xl border dark:border-gray-700 space-y-4 animate__animated animate__fadeInUp animate__faster">
        <div class="flex items-start justify-between">
            <div>
                <h3 id="mNombre" class="text-xl font-black text-gray-800 dark:text-white"></h3>
                <p id="mPrecio" class="text-lg font-bold text-orange-500 mt-0.5"></p>
            </div>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <textarea id="mNotas" rows="3" maxlength="300"
                  placeholder="Aclaraciones (sin cebolla, extra queso...)"
                  class="w-full p-3 border dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 resize-none bg-gray-50 dark:bg-gray-800 dark:text-white"></textarea>
        <div id="mFeedback" class="hidden text-xs font-bold text-center p-2 rounded-xl"></div>
        <div class="flex gap-3">
            <button onclick="cerrarModal()"
                    class="flex-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-bold py-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 text-sm transition"
                    data-i18n="modal_cancel">Cancelar</button>
            <button id="mBtn" onclick="enviarPedido()"
                    class="flex-1 btn-caas py-3 rounded-xl text-sm flex items-center justify-center gap-2"
                    data-i18n="modal_confirm">
                <i data-lucide="check" class="w-4 h-4"></i>
                Confirmar
            </button>
        </div>
        <?php if (!isset($_SESSION['id_cliente'])): ?>
            <p class="text-xs text-center text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 p-2 rounded-xl">
                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline mr-1"></i>
                <a href="login.php" class="font-bold underline" data-i18n="modal_login_warn">Iniciá sesión para pedir.</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
// ── Modal de pedido ──────────────────────────────────────────────
let _prod = {};
function abrirModal(id, idEmp, nombre, precio) {
    _prod = { id, idEmp, nombre, precio };
    document.getElementById('mNombre').textContent = nombre;
    document.getElementById('mPrecio').textContent = '$ ' + precio.toFixed(2);
    document.getElementById('mNotas').value = '';
    document.getElementById('mFeedback').className = 'hidden text-xs font-bold text-center p-2 rounded-xl';
    const btn = document.getElementById('mBtn');
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> ' + t('modal_confirm');
    lucide.createIcons();
    document.getElementById('modal').classList.replace('hidden','flex');
}
function cerrarModal() { document.getElementById('modal').classList.replace('flex','hidden'); }
document.getElementById('modal').addEventListener('click', e => { if(e.target===e.currentTarget) cerrarModal(); });

async function enviarPedido() {
    const btn = document.getElementById('mBtn');
    const fb  = document.getElementById('mFeedback');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> ' + t('modal_sending');
    lucide.createIcons();
    const fd = new FormData();
    fd.append('id_producto', _prod.id);
    fd.append('id_empresa',  _prod.idEmp);
    fd.append('notas',       document.getElementById('mNotas').value.trim());
    try {
        const r = await fetch('realizar_pedido.php', { method:'POST', body:fd });
        const d = await r.json();
        if (d.status === 'success') {
            cerrarModal();
            toastOk('¡Pedido registrado!');
            setTimeout(() => {
                const notas = document.getElementById('mNotas').value;
                const msg = `Hola! Pedido desde C.A.A.S.\n*Producto:* ${_prod.nombre}\n*Total:* $${_prod.precio.toFixed(2)}${notas?'\n*Notas:* '+notas:''}`;
                window.open(`https://wa.me/${d.telefono}?text=${encodeURIComponent(msg)}`, '_blank');
            }, 500);
        } else {
            toastErr(d.message || t('modal_conn_err'));
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> ' + t('modal_confirm');
            lucide.createIcons();
        }
    } catch {
        toastErr(t('modal_conn_err'));
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> ' + t('modal_confirm');
        lucide.createIcons();
    }
}

// ── Mapa Nacional ────────────────────────────────────────────────
const pinesEmpresas = <?= json_encode(array_map(fn($e) => [
    'id'         => $e['id_empresa'],
    'nombre'     => $e['nombre'],
    'categoria'  => $e['categoria'],
    'lat'        => (float)$e['latitud'],
    'lng'        => (float)$e['longitud'],
    'turista'    => (bool)$e['modo_turista'],
    'plato'      => $e['plato_tipico'] ?? '',
    'depto'      => $e['departamento'] ?? '',
], $turistas), JSON_UNESCAPED_UNICODE) ?>;

let mapaNac = null;

document.getElementById('btnToggleMapa')?.addEventListener('click', function() {
    const wrapper = document.getElementById('mapaWrapper');
    const label   = document.getElementById('labelMapa');
    const oculto  = wrapper.classList.contains('hidden');
    wrapper.classList.toggle('hidden', !oculto);
    label.textContent = oculto ? 'Ocultar mapa' : 'Ver mapa';

    if (oculto && !mapaNac) {
        // Uruguay centrado
        mapaNac = L.map('mapaNacional', { scrollWheelZoom: false }).setView([-32.5, -56.0], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(mapaNac);

        pinesEmpresas.forEach(emp => {
            if (!emp.lat || !emp.lng) return;
            const color = emp.turista ? '#10b981' : '#f97316';
            const icono = L.divIcon({
                html: `<div style="background:${color};color:white;font-size:18px;width:38px;height:38px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 3px 10px rgba(0,0,0,.25)"><span style="transform:rotate(45deg)">${emp.turista?'⭐':'🍴'}</span></div>`,
                iconSize: [38,38], iconAnchor:[19,38], popupAnchor:[0,-42], className:'',
            });
            L.marker([emp.lat, emp.lng], { icon: icono })
             .addTo(mapaNac)
             .bindPopup(`<strong>${emp.nombre}</strong><br><small>${emp.categoria}${emp.depto?' · '+emp.depto:''}</small>${emp.plato?`<br><em>🍽️ ${emp.plato}</em>`:''}<br><a href="local.php?id=${emp.id}" style="color:#f97316;font-weight:bold;">Ver local →</a>`);
        });
    }
});

// ── Hamburguesa ──────────────────────────────────────────────────
document.getElementById('btnNav').addEventListener('click', () => {
    const m  = document.getElementById('mobileMenu');
    const ib = document.getElementById('iconBars');
    const ix = document.getElementById('iconX');
    const h  = m.classList.contains('hidden');
    m.classList.toggle('hidden', !h);
    ib.classList.toggle('hidden', h);
    ix.classList.toggle('hidden', !h);
});

// ── Dropdown perfil ──────────────────────────────────────────────
const btnPerfil      = document.getElementById('btnPerfil');
const dropdownPerfil = document.getElementById('dropdownPerfil');
if (btnPerfil) {
    btnPerfil.addEventListener('click', e => { e.stopPropagation(); dropdownPerfil.classList.toggle('hidden'); });
    document.addEventListener('click', () => dropdownPerfil?.classList.add('hidden'));
}
// ── Dropdown idioma navbar ───────────────────────────────────────
const btnLangNav   = document.getElementById('btnLangNav');
const langDropMenu = document.getElementById('langDropMenu');
if (btnLangNav) {
    btnLangNav.addEventListener('click', e => {
        e.stopPropagation();
        langDropMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', () => langDropMenu?.classList.add('hidden'));
}

// Inicializar bandera del navbar con el idioma guardado
document.addEventListener('DOMContentLoaded', () => {
    const banderas = { es:'🇺🇾', en:'🇺🇸', pt:'🇧🇷', fr:'🇫🇷', it:'🇮🇹', de:'🇩🇪', zh:'🇨🇳', ja:'🇯🇵' };
    const ind = document.getElementById('navLangIndicator');
    if (ind) ind.textContent = banderas[getLang()] || '🌐';
});
</script>
<?php require_once 'foot.php'; ?>
</body>
</html>
