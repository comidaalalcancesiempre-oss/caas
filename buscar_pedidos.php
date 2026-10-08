<?php
require_once 'config.php';

// Este archivo devuelve datos en formato JSON (para que JavaScript lo lea)
header('Content-Type: application/json; charset=utf-8');

// ── Control de acceso ──────────────────────────────────────────────
// Verificamos DOS cosas: que haya sesión activa Y que sea ADMIN
// Si no cumple alguna, devolvemos error 403 (Prohibido) y cortamos
// Esto evita que cualquier persona abra este archivo directamente en el navegador
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'ADMIN') {
    http_response_code(403); // código HTTP que significa "no tenés permiso"
    echo json_encode(['error' => 'No autorizado']);
    exit; // detener todo — sin esto el código seguiría ejecutándose
}

// Solo aceptamos peticiones POST (no GET)
// El formulario de búsqueda en admin.php envía los datos por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 = Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Armar el patrón de búsqueda con % de cada lado
// El % en SQL significa "cualquier cosa" — busca en cualquier parte del texto
// Ej: buscar "juan" → "%juan%" encuentra "Juan Pérez", "Anjuana", etc.
$q = '%' . trim($_POST['buscar'] ?? '') . '%';

try {
    // Verificar si hay pedidos antes de hacer la consulta compleja
    // Evita un JOIN innecesario si la tabla está vacía
    $count = $conn->query("SELECT COUNT(*) FROM pedido")->fetchColumn();

    if ($count == 0) {
        // No hay pedidos — devolvemos array vacío (no es un error)
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Prepared Statement ─────────────────────────────────────────
    // Los ? son "cajitas vacías" que se llenan después con los valores reales
    // Esto previene SQL Injection: el usuario no puede romper la consulta
    // porque los valores van separados de la estructura SQL
    $s = $conn->prepare(
        "SELECT p.id_pedido,
                p.detalle,
                p.costo,
                p.estado,
                p.fecha_hora,
                c.nombre  AS nombre_cliente,
                c.calle,
                c.num_casa,
                e.nombre  AS nombre_empresa
         FROM   pedido   p
         JOIN   cliente  c ON p.id_cliente = c.id_cliente
         JOIN   empresa  e ON p.id_empresa = e.id_empresa
         WHERE  c.nombre  LIKE ?
            OR  p.detalle LIKE ?
            OR  p.estado  LIKE ?
            OR  c.calle   LIKE ?
            OR  e.nombre  LIKE ?
         ORDER BY p.id_pedido DESC
         LIMIT 200"
    );

    // Ejecutamos pasando $q cinco veces — una por cada ? de la query
    // IMPORTANTE: PDO no permite reutilizar named params (:q) múltiples veces
    // por eso usamos ? y pasamos el valor repetido
    $s->execute([$q, $q, $q, $q, $q]);

    // fetchAll trae TODOS los resultados como array
    // JSON_UNESCAPED_UNICODE para que las tildes y ñ se vean bien
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Si algo falla en la base de datos, lo registramos en el log
    // pero NO mostramos el error técnico al usuario (seguridad)
    error_log('buscar_pedidos: ' . $e->getMessage());
    http_response_code(500); // 500 = Error interno del servidor
    echo json_encode([
        'error'   => 'Error interno',
        // En desarrollo mostramos el detalle para depurar, en producción no
        'detalle' => APP_ENV === 'development' ? $e->getMessage() : ''
    ]);
}
