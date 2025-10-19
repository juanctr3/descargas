<?php
// download.php
require_once 'includes/config.php';

$slug = $_GET['slug'] ?? '';
$uid = $_GET['uid'] ?? ''; // Identificador único de actualización
$license_key = $_GET['license'] ?? '';

if (empty($slug) && empty($uid)) {
    die('Acceso denegado: no se especificó el plugin.');
}

// Lógica de búsqueda priorizada
$plugin = null;
if (!empty($uid)) {
    $stmt = $mysqli->prepare("SELECT * FROM plugins WHERE update_identifier = ?");
    $stmt->bind_param('s', $uid);
} else {
    $stmt = $mysqli->prepare("SELECT * FROM plugins WHERE slug = ?");
    $stmt->bind_param('s', $slug);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $plugin = $result->fetch_assoc();
}
$stmt->close();

if (!$plugin) { die('Plugin no encontrado.'); }

// --- Lógica de Permisos de Descarga ---
$has_permission = false;
$is_paid = $plugin['price'] > 0;
$user_id = $_SESSION['user_id'] ?? null;

// CASO 1: Actualización automática con licencia válida
if ($plugin['requires_license'] && !empty($license_key)) {
    $stmt_license = $mysqli->prepare("SELECT status, expires_at FROM license_keys WHERE license_key = ? AND plugin_id = ?");
    $stmt_license->bind_param('si', $license_key, $plugin['id']);
    $stmt_license->execute();
    $license_data = $stmt_license->get_result()->fetch_assoc();
    $stmt_license->close();
    
    if ($license_data && $license_data['status'] === 'active' && (!$license_data['expires_at'] || new DateTime() <= new DateTime($license_data['expires_at']))) {
        $has_permission = true;
    }
} 
// CASO 2: Usuario logueado descarga un plugin gratuito
elseif ($user_id && !$is_paid) {
    $has_permission = true;
}
// CASO 3: Usuario logueado que ha comprado el plugin
elseif ($user_id && $is_paid) {
    $stmt_order = $mysqli->prepare("SELECT id FROM orders WHERE user_id = ? AND plugin_id = ? AND status = 'completed'");
    $stmt_order->bind_param('ii', $user_id, $plugin['id']);
    $stmt_order->execute();
    if ($stmt_order->get_result()->num_rows > 0) {
        $has_permission = true;
    }
    $stmt_order->close();
}
// CASO 4: Descarga anónima (vía OTP) de un plugin gratuito
// Esta se valida en verify-otp.php, el link de descarga es la prueba de permiso.
// Si el slug está presente y el plugin es gratuito, lo permitimos.
elseif (empty($license_key) && !$user_id && !$is_paid && !empty($slug)) {
    $has_permission = true;
}


if (!$has_permission) {
    die('No tienes permiso para descargar este archivo. Posibles razones: tu licencia ha expirado, no has comprado este plugin o no has iniciado sesión.');
}

// --- Proceder con la descarga ---
$file_path = UPLOAD_PATH . $plugin['file_path'];
if (file_exists($file_path)) {
    // Registrar la descarga si no es una actualización automática (sin licencia en la URL)
    if (empty($license_key) && $user_id) {
         $mysqli->query("INSERT INTO downloads (plugin_id, user_id, user_name, user_email, phone_number) VALUES ({$plugin['id']}, {$_SESSION['user_id']}, '{$_SESSION['user_name']}', '{$_SESSION['user_email']}', '')");
         $mysqli->query("UPDATE plugins SET download_count = download_count + 1 WHERE id = {$plugin['id']}");
    }
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($plugin['slug'] . '.zip') . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    die('El archivo no existe en el servidor.');
}
?>