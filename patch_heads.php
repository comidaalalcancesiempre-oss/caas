<?php
/**
 * Parche automático: reemplaza el <head> antiguo con require_once 'head.php'
 * Ejecutar una sola vez: http://localhost/caas_nuevo/patch_heads.php
 */
header('Content-Type: text/plain; charset=utf-8');

$archivos = [
    'empresa.php'        => 'Panel Empresa - C.A.A.S.',
    'admin.php'          => 'Panel Admin - C.A.A.S.',
    'local.php'          => 'Local - C.A.A.S.',
    'perfil.php'         => 'Mi Perfil - C.A.A.S.',
    'mis_pedidos.php'    => 'Mis Pedidos - C.A.A.S.',
    'ajustes.php'        => 'Ajustes - C.A.A.S.',
    'recuperar.php'      => 'Recuperar Contraseña - C.A.A.S.',
    'reset_password.php' => 'Nueva Contraseña - C.A.A.S.',
];

// Patrones de head que pueden existir en los archivos (con variaciones)
$patrones_head = [
    // Con tailwind.config inline
    '/<meta charset="UTF-8">\s*<meta name="viewport"[^>]+>\s*<title>[^<]+<\/title>\s*<script src="https:\/\/cdn\.tailwindcss\.com"><\/script>\s*<script>tailwind\.config[^<]+<\/script>\s*<script>const _t=[^<]+<\/script>\s*(?:<script src="lang\.js"><\/script><script src="theme\.js"><\/script>|<script src="lang\.js"><\/script>\s*<script src="theme\.js"><\/script>)\s*(?:<style>[^<]*<\/style>)?/s',
    // Con Leaflet adicional (local.php)
    '/<meta charset="UTF-8">\s*<meta name="viewport"[^>]+>\s*<title>[^<]+<\/title>\s*<script src="https:\/\/cdn\.tailwindcss\.com"><\/script>\s*<script>tailwind\.config[^<]+<\/script>\s*<script>const _t=[^<]+<\/script>[^<]*<link rel="stylesheet"\s+href="https:\/\/unpkg\.com\/leaflet[^"]+"\s+crossorigin=""\/>.*?<script src="lang\.js"><\/script>\s*<script src="theme\.js"><\/script>\s*(?:<style>[^<]*<\/style>)?/s',
];

foreach ($archivos as $archivo => $titulo) {
    $ruta    = __DIR__ . '/' . $archivo;
    if (!file_exists($ruta)) {
        echo "❌ No existe: $archivo\n";
        continue;
    }

    $content = file_get_contents($ruta);

    // Ya tiene head.php
    if (str_contains($content, "require_once 'head.php'") || str_contains($content, 'require_once \'head.php\'')) {
        echo "— Ya actualizado: $archivo\n";
        continue;
    }

    // Determinar si tiene Leaflet
    $tieneLeaflet = str_contains($content, 'leaflet');
    $extraHead = '';
    if ($tieneLeaflet) {
        $extraHead = "\n\$extraHead = '<link rel=\"stylesheet\" href=\"https://unpkg.com/leaflet@1.9.4/dist/leaflet.css\" crossorigin=\"\"/>';";
    }

    // Nuevo head
    $nuevoHead = "<?php \$pageTitle = '{$titulo}';{$extraHead} require_once 'head.php'; ?>";

    // Reemplazar bloque completo del <head> con regex flexible
    $patron = '/<meta charset="UTF-8">[\s\S]*?<\/style>\s*(?=<!--|\<\/head>|\<script src="lang)|<meta charset="UTF-8">[\s\S]*?<script src="theme\.js"><\/script>\s*/';

    // Intentar sustitución
    $nuevo = preg_replace($patron, $nuevoHead . "\n", $content, 1, $count);

    if ($count > 0 && $nuevo !== null) {
        // También quitar Leaflet CSS duplicado si ya está en extraHead
        if ($tieneLeaflet) {
            $nuevo = preg_replace('/<link rel="stylesheet"\s+href="https:\/\/unpkg\.com\/leaflet[^"]+"\s+crossorigin=""\/>/', '', $nuevo);
        }
        file_put_contents($ruta, $nuevo);
        echo "✓ Actualizado: $archivo\n";
    } else {
        echo "⚠ No se pudo parchear automáticamente: $archivo — requiere edición manual\n";
    }
}

echo "\nListo. Eliminá este archivo.\n";
