<?php
// config.php ya carga la variable $app_settings con todos nuestros ajustes
require_once 'includes/config.php';

if (!isset($_GET['plugin_id']) || !is_numeric($_GET['plugin_id'])) {
    http_response_code(400); 
    die('Solicitud no válida.');
}
$plugin_id = (int)$_GET['plugin_id'];

$stmt = $mysqli->prepare("SELECT file_path FROM plugins WHERE id = ? AND status = 'active'");
$stmt->bind_param('i', $plugin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die('Plugin no encontrado o no está activo.');
}

$plugin = $result->fetch_assoc();
$file_on_server = $plugin['file_path'];
$file_path_on_server = UPLOAD_PATH . $file_on_server;

// --- INICIO DE LA LÓGICA ACTUALIZADA ---

// 1. Obtenemos el prefijo desde el array de ajustes.
// Si por alguna razón no está configurado, usamos 'MiSitio' como valor por defecto.
$site_prefix = $app_settings['site_name_prefix'] ?? 'MiSitio';

// 2. Extraemos el nombre original del archivo
$original_file_name = $file_on_server;
$position_of_hyphen = strpos($file_on_server, '-');
if ($position_of_hyphen !== false) {
    $original_file_name = substr($file_on_server, $position_of_hyphen + 1);
}

// 3. Creamos el nuevo nombre de archivo
$user_friendly_filename = $site_prefix . '-' . $original_file_name;

// --- FIN DE LA LÓGICA ACTUALIZADA ---

if (!file_exists($file_path_on_server)) {
    http_response_code(500);
    die('El archivo del plugin no se encuentra en el servidor.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/zip');
// 4. Usamos el nuevo nombre de archivo personalizado
header('Content-Disposition: attachment; filename="' . basename($user_friendly_filename) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path_on_server));

flush(); 
readfile($file_path_on_server);
exit();
?>