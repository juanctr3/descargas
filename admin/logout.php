<?php
// Incluimos la configuración para iniciar la sesión
require_once __DIR__ . '/../includes/config.php';

// 1. Vaciamos el array de la sesión
$_SESSION = array();

// 2. Borramos la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destruimos la sesión del servidor
session_destroy();

// 4. Redirigimos al usuario a la página de login
header("Location: index.php");
exit();
?>