<?php
// api/update.php

header('Content-Type: application/json');
require_once '../includes/config.php';

// --- VALIDACIONES INICIALES ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit();
}

$action = $_POST['action'] ?? '';
// NUEVO: Usamos 'update_identifier' como el identificador principal
$update_identifier = trim($_POST['update_identifier'] ?? '');
$current_version = $_POST['version'] ?? '';
$license_key = $_POST['license_key'] ?? '';

if (($action !== 'plugin_information' && $action !== 'download_package') || empty($update_identifier) || empty($current_version)) {
    echo json_encode(['error' => 'Missing or invalid parameters.']);
    exit();
}

// --- BUSCAR EL PLUGIN POR SU IDENTIFICADOR ÚNICO ---
$stmt_plugin = $mysqli->prepare("SELECT * FROM plugins WHERE update_identifier = ? AND status = 'active'");
$stmt_plugin->bind_param('s', $update_identifier);
$stmt_plugin->execute();
$plugin_data = $stmt_plugin->get_result()->fetch_assoc();
$stmt_plugin->close();

if (!$plugin_data) {
    echo json_encode(['error' => 'Plugin not found for the given identifier.']);
    exit();
}

// --- LÓGICA PRINCIPAL ---
$is_new_version_available = version_compare($plugin_data['version'], $current_version, '>');

if (!$is_new_version_available) {
    exit(); // No hay versión nueva, no se devuelve nada.
}

if ($plugin_data['requires_license']) {
    if (empty($license_key)) { exit(); }

    $stmt_license = $mysqli->prepare("SELECT * FROM license_keys WHERE license_key = ? AND plugin_id = ?");
    $stmt_license->bind_param('si', $license_key, $plugin_data['id']);
    $stmt_license->execute();
    $license_data = $stmt_license->get_result()->fetch_assoc();
    $stmt_license->close();

    if (!$license_data || $license_data['status'] !== 'active' || ($license_data['expires_at'] && new DateTime() > new DateTime($license_data['expires_at']))) {
        $response = [
            'slug'          => $plugin_data['slug'],
            'name'          => $plugin_data['title'],
            'new_version'   => $plugin_data['version'],
            'package'       => '',
            'sections'      => ['changelog' => '<p>Tu licencia no es válida o ha expirado. Por favor, renueva tu licencia para recibir esta actualización.</p>']
        ];
        echo json_encode($response);
        exit();
    }
}

// NUEVO: El enlace de descarga ahora usa el 'update_identifier'
$download_link = SITE_URL . '/download.php?uid=' . $plugin_data['update_identifier'] . '&license=' . urlencode($license_key);

// --- INICIO DE LA MODIFICACIÓN ---
$response = [
    'slug'          => $plugin_data['slug'], // Usa el slug normal para WordPress
    'name'          => $plugin_data['title'],
    'new_version'   => $plugin_data['version'],
    'url'           => SITE_URL . '/plugin/' . $plugin_data['slug'] . '/', // URL de la página del plugin
    'package'       => $download_link, // Enlace de descarga generado antes
    'sections'      => [
        'description' => $plugin_data['short_description'] ?? '', // Descripción corta desde la BD
        // Usamos el changelog de la BD. Si está vacío, ponemos un mensaje por defecto.
        'changelog'   => !empty($plugin_data['changelog']) ? $plugin_data['changelog'] : '<p>No se proporcionaron detalles para esta versión.</p>'
    ],
    // Usamos el autor de la BD. Si está vacío, usamos el nombre del sitio (si existe), si no, 'Autor Desconocido'.
    'author'        => !empty($plugin_data['author']) ? $plugin_data['author'] : ($app_settings['site_name'] ?? 'Autor Desconocido'),
    // Usamos la versión mínima requerida de WP desde la BD. Si no hay, no se envía (null).
    'requires'      => $plugin_data['requires'] ?? null,
    // Usamos la versión probada de WP desde la BD. Si no hay, no se envía (null).
    'tested'        => $plugin_data['tested'] ?? null,
];
// --- FIN DE LA MODIFICACIÓN ---

echo json_encode($response);
?>