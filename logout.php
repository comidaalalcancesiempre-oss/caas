<?php
require_once 'config.php';

// Eliminar todas las variables guardadas en la sesión activa
// (nombre, id, tipo de usuario, etc.)
session_unset();

// Destruir la sesión completamente del servidor
// Esto invalida el ID de sesión — aunque alguien tenga la cookie, ya no sirve
session_destroy();

// Redirigir al login después de cerrar sesión
header('Location: login.php');
exit; // detener la ejecución del script (sin exit, el código de abajo seguiría corriendo)
