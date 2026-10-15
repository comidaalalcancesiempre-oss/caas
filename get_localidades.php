<?php
define('JSON_ENDPOINT', true);
require_once 'config.php';
require_once 'uruguay.php';

header('Content-Type: application/json; charset=utf-8');

$depto = trim($_GET['departamento'] ?? '');
$localidades = DEPARTAMENTOS_UY[$depto] ?? [];
echo json_encode(array_values($localidades), JSON_UNESCAPED_UNICODE);
