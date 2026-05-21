<?php
session_start();

// Limpiar todas las variables de sesión
$_SESSION = [];

// Destruir la sesión
session_destroy();

// Headers para prevenir caché y que el botón atrás no muestre contenido guardado
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirigir al login
header("Location: ../index.php");
exit;
?>
