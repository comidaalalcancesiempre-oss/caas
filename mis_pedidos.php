<?php
require_once 'config.php';

if (!isset($_SESSION['id_cliente'])) {
    header('Location: login.php'); exit;
}

$id_cliente = (int)$_SESSION['id_cliente'];

// Cargar pedidos del cliente con datos de la empresa
$stmt = $conn->prepare(
    "SELECT p.id_pedido, p.detalle, p.costo, p.estado, p.fecha_hora,
            e.nombre AS nombre_empresa, e.logo, e.telefono AS tel_empresa,
            u.telefono
     FROM   pedido  p
     JOIN   empresa e ON p.id_empresa = e.id_empresa
     JOIN   usuario u ON e.id_usuario = u.id_usuario
     WHERE  p.id_cliente = ?
     ORDER BY p.fecha_hora DESC"
);
$stmt->execute([$id_cliente]);
$pedidos = $stmt->fetchAll();

$colores = [
    'Pendiente'      => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
    'En preparación' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
    'En camino'      => 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300',
    'Entregado'      => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    'Cancelado'      => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
];

$iconos = [
    'Pendiente'      => '🕐',
    'En preparación' => '👨‍🍳',
    'En camino'      => '🛵',
    'Entregado'      => '✅',
    'Cancelado'      => '❌',
];
?>
<!DOCTYPE html>
<html lang="es" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={darkMode:'class'}</script>
    <script>const _t=localStorage.getItem('caas_tema')||'light';if(_t==='dark')document.documentElement.classList.add('dark');</script>
    <script src="lang.js"></script><script src="theme.js"></script>
    <style>
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-in{animation:fadeInUp .35s ease both}
        .fade-in-delay{animation:fadeInUp .35s ease both .1s;opacity:0;animation-fill-mode:forwards}
    </style>
</head>
<body class="bg-slate-100 dark:bg-gray-950 min-h-screen transition-colors duration-300">

<!-- NAVBAR -->
<nav class="bg-white dark:bg-gray-900 border-b dark:border-gray-800 sticky top-0 z-50 shadow-sm">
    <div class="max-w-4xl mx-auto px-4 h-14 flex items-center justify-between">
        <a href="index.php" class="bg-orange-500 text-white font-black text-xl px-3 py-1 rounded-xl">C.A.A.S.</a>
        <div class="flex items-center gap-2">
            <button id="btnToggleTema" class="text-xl p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">🌙</button>
            <a href="perfil.php" class="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-orange-500 px-2 py-1">Mi Perfil</a>
            <a href="logout.php" class="text-xs font-bold bg-red-500 text-white px-3 py-2 rounded-xl hover:bg-red-600 transition">Salir</a>
        </div>
    </div>
</nav>

<main class="max-w-4xl mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6 fade-in">
        <div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white">Mis Pedidos</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?= count($pedidos) ?> pedido<?= count($pedidos) !== 1 ? 's' : '' ?> en total</p>
        </div>
        <a href="index.php"
           class="text-sm font-bold bg-orange-500 text-white px-4 py-2.5 rounded-xl hover:bg-orange-600 transition shadow">
            Hacer un pedido +
        </a>
    </div>

    <?php if (empty($pedidos)): ?>
        <div class="bg-white dark:bg-gray-900 rounded-3xl border dark:border-gray-800 border-dashed p-16 text-center fade-in">
            <p class="text-5xl mb-4">🍽️</p>
            <p class="font-bold text-gray-700 dark:text-gray-300 text-lg">Todavía no hiciste ningún pedido</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Explorá los locales del barrio y pedí tu comida favorita.</p>
            <a href="index.php"
               class="inline-block mt-6 bg-orange-500 text-white font-bold px-6 py-3 rounded-xl hover:bg-orange-600 transition shadow">
                Ver locales →
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($pedidos as $i => $p):
                $badge = $colores[$p['estado']] ?? 'bg-gray-100 text-gray-600';
                $icono = $iconos[$p['estado']] ?? '📦';
                $tel_limpio = preg_replace('/[^0-9]/', '', $p['tel_empresa'] ?? $p['telefono'] ?? '');
                $wa_msg = "Hola! Quisiera consultar sobre mi pedido #{$p['id_pedido']} — {$p['detalle']}";
            ?>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border dark:border-gray-800 p-5 shadow-sm fade-in"
                 style="animation-delay: <?= $i * 0.05 ?>s; opacity:0; animation-fill-mode:forwards">

                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">

                    <!-- Info empresa -->
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <img src="uploads/<?= e($p['logo']) ?>"
                             class="w-12 h-12 rounded-xl object-cover border dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex-shrink-0"
                             onerror="this.src='https://placehold.co/48x48/f97316/white?text=?'">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-black bg-orange-500 text-white px-2 py-0.5 rounded-md">#<?= (int)$p['id_pedido'] ?></span>
                                <span class="font-bold text-sm text-gray-900 dark:text-white truncate"><?= e($p['nombre_empresa']) ?></span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">📦 <?= e($p['detalle']) ?></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">🕒 <?= e($p['fecha_hora']) ?></p>
                        </div>
                    </div>

                    <!-- Estado y precio -->
                    <div class="flex flex-row sm:flex-col items-center sm:items-end gap-3 flex-shrink-0">
                        <span class="text-xl font-black text-gray-900 dark:text-white">
                            $<?= number_format((float)$p['costo'], 2) ?>
                        </span>
                        <span class="text-xs font-bold px-3 py-1 rounded-full <?= $badge ?>">
                            <?= $icono ?> <?= e($p['estado']) ?>
                        </span>
                    </div>
                </div>

                <!-- Barra de progreso del estado -->
                <?php if ($p['estado'] !== 'Cancelado'): ?>
                <?php
                $pasos   = ['Pendiente', 'En preparación', 'En camino', 'Entregado'];
                $idx_act = array_search($p['estado'], $pasos);
                if ($idx_act === false) $idx_act = 0;
                $porcentaje = (($idx_act + 1) / count($pasos)) * 100;
                ?>
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mb-1.5">
                        <?php foreach ($pasos as $i_paso => $paso): ?>
                            <span class="<?= $i_paso <= $idx_act ? 'text-orange-500 dark:text-orange-400 font-bold' : '' ?> hidden sm:inline">
                                <?= $iconos[$paso] ?? '' ?> <?= e($paso) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-orange-500 rounded-full transition-all duration-700"
                             style="width: <?= $porcentaje ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botón contactar empresa -->
                <?php if ($tel_limpio && $p['estado'] !== 'Entregado' && $p['estado'] !== 'Cancelado'): ?>
                <div class="mt-3 pt-3 border-t dark:border-gray-800">
                    <a href="https://wa.me/<?= e($tel_limpio) ?>?text=<?= urlencode($wa_msg) ?>"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        <span>💬</span> Consultar por este pedido
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
