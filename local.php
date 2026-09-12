<?php
require_once 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $conn->prepare(
    "SELECT e.*, u.telefono FROM empresa e
     JOIN usuario u ON e.id_usuario = u.id_usuario
     WHERE e.id_empresa = ? AND e.estado_aprobacion = 'APROBADO'"
);
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) { header('Location: index.php'); exit; }

$prods = $conn->prepare("SELECT * FROM producto WHERE id_empresa = ? ORDER BY nombre");
$prods->execute([$id]);
$productos = $prods->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($emp['nombre']) ?> - C.A.A.S.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-gray-800">

<nav class="bg-white border-b sticky top-0 z-50 px-4 sm:px-8 h-14 flex items-center justify-between shadow-sm">
    <a href="index.php" class="bg-orange-500 text-white font-black text-lg px-3 py-1 rounded-xl">C.A.A.S.</a>
    <div class="flex items-center gap-3">
        <a href="index.php" class="text-sm font-bold text-gray-500 hover:text-orange-500 hidden sm:inline">← Volver</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="text-xs font-bold text-gray-600 hidden sm:inline"><?= e($_SESSION['nombre']) ?></span>
            <a href="logout.php" class="text-xs font-bold bg-red-500 text-white px-3 py-1.5 rounded-xl hover:bg-red-600 transition">Salir</a>
        <?php else: ?>
            <a href="login.php" class="text-xs font-bold text-orange-500 border border-orange-400 px-3 py-1.5 rounded-xl hover:bg-orange-50 transition">Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Cabecera local -->
<header class="bg-white border-b py-8 px-4">
    <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center sm:items-start gap-5">
        <img src="uploads/<?= e($emp['logo']) ?>"
             class="w-24 h-24 rounded-3xl object-cover border shadow bg-gray-50 flex-shrink-0"
             onerror="this.src='https://placehold.co/96x96/f97316/white?text=?'"
             alt="<?= e($emp['nombre']) ?>">
        <div class="text-center sm:text-left space-y-2">
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900"><?= e($emp['nombre']) ?></h1>
            <span class="inline-block bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1 rounded-lg"><?= e($emp['categoria']) ?></span>
            <div class="text-xs text-gray-500 space-y-1 pt-1">
                <?php if ($emp['direccion']): ?>
                    <p>📍 <?= e($emp['direccion']) ?></p>
                <?php endif; ?>
                <?php if ($emp['horarios']): ?>
                    <p>🕒 <?= e($emp['horarios']) ?></p>
                <?php endif; ?>
                <?php if ($emp['telefono']): ?>
                    <p>📞 <a href="https://wa.me/<?= e(preg_replace('/\D/','',$emp['telefono'])) ?>"
                             class="text-emerald-600 font-bold hover:underline" target="_blank">
                        <?= e($emp['telefono']) ?>
                    </a></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="sm:ml-auto text-center flex-shrink-0">
            <span class="text-3xl font-black text-orange-500"><?= count($productos) ?></span>
            <p class="text-xs text-gray-400">plato<?= count($productos) !== 1 ? 's' : '' ?></p>
        </div>
    </div>
</header>

<main class="max-w-5xl mx-auto px-4 py-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Menú</h2>

    <?php if (empty($productos)): ?>
        <div class="bg-white rounded-2xl border border-dashed p-12 text-center text-gray-400">
            <p class="text-4xl mb-3">🍽️</p>
            <p>Este local aún no publicó platos.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($productos as $p): ?>
            <div class="bg-white rounded-2xl p-4 border flex flex-col shadow-sm hover:shadow-md transition">
                <img src="uploads/<?= e($p['imagen']) ?>"
                     class="w-full h-36 sm:h-44 object-cover rounded-xl mb-3 bg-gray-50"
                     onerror="this.src='https://placehold.co/300x200/f1f5f9/94a3b8?text=Sin+imagen'"
                     alt="<?= e($p['nombre']) ?>">
                <div class="flex-1">
                    <h3 class="font-bold text-sm sm:text-base text-gray-800"><?= e($p['nombre']) ?></h3>
                    <p class="text-xs text-gray-500 line-clamp-2 mt-1"><?= e($p['descripcion'] ?? '') ?></p>
                </div>
                <div class="flex justify-between items-center mt-4 pt-3 border-t">
                    <span class="text-lg font-black text-gray-900">$<?= number_format((float)$p['precio'], 2) ?></span>
                    <button onclick="abrirModal(<?= (int)$p['id_producto'] ?>, <?= (int)$emp['id_empresa'] ?>, '<?= addslashes(e($p['nombre'])) ?>', <?= (float)$p['precio'] ?>)"
                            class="bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow">
                        Pedir
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modal pedido (idéntico al de index.php) -->
<div id="modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <h3 id="mNombre" class="text-xl font-black text-gray-800"></h3>
        <p  id="mPrecio"  class="text-lg font-bold text-orange-500"></p>
        <textarea id="mNotas" rows="3" maxlength="300" placeholder="Aclaraciones opcionales..."
                  class="w-full p-3 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 resize-none"></textarea>
        <div id="mFeedback" class="hidden text-xs font-bold text-center p-2 rounded-xl"></div>
        <div class="flex gap-3">
            <button onclick="cerrarModal()" class="flex-1 bg-gray-100 text-gray-600 font-bold py-3 rounded-xl hover:bg-gray-200 text-sm">Cancelar</button>
            <button id="mBtn" onclick="enviarPedido()" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-xl shadow text-sm">✓ Confirmar</button>
        </div>
        <?php if (!isset($_SESSION['id_cliente'])): ?>
            <p class="text-xs text-center text-amber-600 bg-amber-50 p-2 rounded-xl">
                ⚠️ <a href="login.php" class="font-bold underline">Iniciá sesión</a> para pedir.
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
    document.getElementById('mBtn').textContent = '✓ Confirmar';
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
        } else { btn.disabled = false; btn.textContent = '✓ Confirmar'; }
    } catch {
        fb.className = 'text-xs font-bold text-center p-2 rounded-xl bg-red-100 text-red-700';
        fb.textContent = 'Error de conexión.';
        btn.disabled = false; btn.textContent = '✓ Confirmar';
    }
}
</script>
</body>
</html>
