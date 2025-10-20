<?php
// --- INICIO DE CAMBIOS ---
// Desactivar mostrar errores en pantalla (importante para sitio en vivo)
ini_set('display_errors', 0);
// Activar el registro de errores en un archivo
ini_set('log_errors', 1);
// Definir el nombre y ubicación del archivo de registro (puedes cambiar 'php_error.log' si quieres)
ini_set('error_log', __DIR__ . '/../php_error.log'); // Lo guardará en la carpeta principal del sitio
// Reportar todos los errores
error_reporting(E_ALL);
// --- FIN DE CAMBIOS ---

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- TUS DATOS DE CONEXIÓN ---
define('DB_HOST', 'localhost');
define('DB_USER', 'descargas_2');
define('DB_PASS', 'vk7PQtw3MHNvVel');
define('DB_NAME', 'descargas_4');

// --- TUS AJUSTES GENERALES ---
define('SITE_URL', 'https://descargas.smsenlinea.com');
define('UPLOAD_PATH', __DIR__ . '/../uploads/files/');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("FALLO LA CONEXIÓN A LA BASE DE DATOS: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// Incluimos y ejecutamos la carga de ajustes
require_once __DIR__ . '/functions.php';
$app_settings = load_settings($mysqli);
// --- CLAVE SECRETA PARA TAREAS CRON ---
define('CRON_SECRET_KEY', 'JC0020560392jc');
?>