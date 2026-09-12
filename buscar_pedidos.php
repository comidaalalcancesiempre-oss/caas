<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['error'=>'No autorizado']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'Método no permitido']); exit;
}

$q = '%' . trim($_POST['buscar'] ?? '') . '%';

try {
    $s = $conn->prepare(
        "SELECT p.id_pedido, p.detalle, p.costo, p.estado, p.fecha_hora,
                c.nombre AS nombre_cliente, c.calle, c.num_casa,
                e.nombre AS nombre_empresa
         FROM   pedido  p
         JOIN   cliente c ON p.id_cliente = c.id_cliente
         JOIN   empresa e ON p.id_empresa = e.id_empresa
         WHERE  c.nombre   LIKE :q
            OR  p.detalle  LIKE :q
            OR  p.estado   LIKE :q
            OR  c.calle    LIKE :q
            OR  e.nombre   LIKE :q
         ORDER BY p.id_pedido DESC LIMIT 200"
    );
    $s->execute([':q' => $q]);
    echo json_encode($s->fetchAll(), JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('buscar_pedidos: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Error interno']);
}
