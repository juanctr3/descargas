<?php
// Incluimos la configuración para poder usar las sesiones
require_once __DIR__ . '/../includes/config.php';

// Verificamos si la variable de sesión del admin NO está establecida
if (!isset($_SESSION['admin_id'])) {
    // Si no ha iniciado sesión, lo redirigimos a la página de login
    header('Location: index.php');
    exit(); // Detenemos la ejecución del script para seguridad
}
?>