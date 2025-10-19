<?php
// Incluimos la configuración para poder iniciar la sesión
require_once 'includes/config.php';

// Destruimos todas las variables de sesión
$_SESSION = array();

// Borramos la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruimos la sesión del servidor
session_destroy();

// Redirigimos al usuario a la página de inicio
header("Location: index.php");
exit();
?>