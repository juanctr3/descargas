<?php
// Incluimos config.php para que las sesiones ya estén iniciadas
require_once __DIR__ . '/config.php';

// Si la variable de sesión del usuario no existe, lo redirigimos a la página de login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>