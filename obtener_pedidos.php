<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['tipo'], ['ADMIN','EMPRESA'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'No autorizado']); exit;
}

// ── Cambiar estado ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estado') {
    $id_pedido = filter_input(INPUT_POST, 'id_pedido', FILTER_VALIDATE_INT);
    $estado    = trim($_POST['estado'] ?? '');
    $validos   = ['Pendiente','En preparación','En camino','Entregado','Cancelado'];

    if (!$id_pedido || !in_array($estado, $validos, true)) {
        http_response_code(400);
        echo json_encode(['status'=>'error','message'=>'Datos inválidos']); exit;
    }

    try {
        if ($_SESSION['tipo'] === 'EMPRESA') {
            $s = $conn->prepare("UPDATE pedido SET estado = ? WHERE id_pedido = ? AND id_empresa = ?");
            $s->execute([$estado, $id_pedido, $_SESSION['id_empresa']]);
        } else {
            $s = $conn->prepare("UPDATE pedido SET estado = ? WHERE id_pedido = ?");
            $s->execute([$estado, $id_pedido]);
        }
        echo json_encode(['status' => $s->rowCount() ? 'success' : 'error',
                          'message'=> $s->rowCount() ? 'Actualizado' : 'Sin cambios']);
    } catch (PDOException $e) {
        error_log('obtener_pedidos update: '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>'Error interno']);
    }
    exit;
}

// ── Listar pedidos ────────────────────────────────────────────────
try {
    if ($_SESSION['tipo'] === 'EMPRESA') {
        $s = $conn->prepare(
            "SELECT p.id_pedido, p.detalle, p.costo, p.estado, p.fecha_hora,
                    c.nombre AS nombre_cliente, c.calle, c.num_casa
             FROM   pedido  p
             JOIN   cliente c ON p.id_cliente = c.id_cliente
             WHERE  p.id_empresa = ?
             ORDER BY p.fecha_hora DESC LIMIT 100"
        );
        $s->execute([$_SESSION['id_empresa']]);
    } else {
        $s = $conn->query(
            "SELECT p.id_pedido, p.detalle, p.costo, p.estado, p.fecha_hora,
                    c.nombre AS nombre_cliente, c.calle, c.num_casa,
                    e.nombre AS nombre_empresa
             FROM   pedido  p
             JOIN   cliente c ON p.id_cliente = c.id_cliente
             JOIN   empresa e ON p.id_empresa = e.id_empresa
             ORDER BY p.fecha_hora DESC LIMIT 300"
        );
    }
    echo json_encode(['status'=>'success','data'=>$s->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('obtener_pedidos: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Error al obtener pedidos']);
}
