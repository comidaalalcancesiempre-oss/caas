<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','message'=>'Método no permitido']); exit;
}

if (!isset($_SESSION['id_cliente'])) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Debés iniciar sesión como cliente para pedir.']); exit;
}

$id_cliente  = (int)$_SESSION['id_cliente'];
$id_producto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
$id_empresa  = filter_input(INPUT_POST, 'id_empresa',  FILTER_VALIDATE_INT);
$notas       = mb_substr(strip_tags(trim($_POST['notas'] ?? '')), 0, 300);

if (!$id_producto || !$id_empresa) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Datos incompletos.']); exit;
}

try {
    // Verificar que el producto pertenece a esa empresa
    $sp = $conn->prepare(
        "SELECT p.nombre, p.precio, u.telefono, e.nombre AS nombre_empresa
         FROM   producto p
         JOIN   empresa  e ON p.id_empresa = e.id_empresa
         JOIN   usuario  u ON e.id_usuario = u.id_usuario
         WHERE  p.id_producto = ? AND p.id_empresa = ? AND e.estado_aprobacion = 'APROBADO'"
    );
    $sp->execute([$id_producto, $id_empresa]);
    $prod = $sp->fetch();

    if (!$prod) {
        http_response_code(404);
        echo json_encode(['status'=>'error','message'=>'Producto no encontrado.']); exit;
    }

    $detalle = $prod['nombre'] . ($notas ? " — {$notas}" : '');

    $conn->prepare(
        "INSERT INTO pedido (id_cliente, id_empresa, detalle, costo, estado, fecha_hora)
         VALUES (?, ?, ?, ?, 'Pendiente', NOW())"
    )->execute([$id_cliente, $id_empresa, $detalle, $prod['precio']]);

    echo json_encode([
        'status'   => 'success',
        'message'  => '¡Pedido registrado!',
        'telefono' => preg_replace('/\D/', '', $prod['telefono']),
        'empresa'  => $prod['nombre_empresa'],
        'producto' => $prod['nombre'],
        'costo'    => number_format((float)$prod['precio'], 2),
    ]);

} catch (PDOException $e) {
    error_log('realizar_pedido: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Error interno. Intentá de nuevo.']);
}
