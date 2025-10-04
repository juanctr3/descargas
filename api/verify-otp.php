<?php
// Indicamos que la respuesta será en formato JSON
header('Content-Type: application/json');
// Incluimos la configuración principal que conecta a la DB y carga los ajustes
require_once '../includes/config.php';

// Solo aceptamos solicitudes de tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Recibimos los datos enviados por el JavaScript
$phone_number = $_POST['phone_number'] ?? null;
$otp_code = $_POST['otp_code'] ?? null;

// Validación básica de los datos recibidos
if (empty($phone_number) || empty($otp_code)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
    exit();
}

// 1. Buscamos el código en la base de datos para validarlo.
// También obtenemos los datos del usuario que se guardaron temporalmente con el código.
// Buscamos donde plugin_id NO ES NULL, para diferenciarlo de los OTP de admin.
$stmt = $mysqli->prepare("
    SELECT id, plugin_id, used, expires_at, user_name, user_email, opt_in_notifications 
    FROM otp_codes 
    WHERE phone_number = ? AND otp_code = ? AND plugin_id IS NOT NULL 
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param('ss', $phone_number, $otp_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'El código de verificación es incorrecto.']);
    $stmt->close();
    exit();
}

$otp_data = $result->fetch_assoc();
$stmt->close();

// 2. Verificamos si el código ya fue usado o ha expirado
if ($otp_data['used']) {
    echo json_encode(['success' => false, 'message' => 'Este código ya ha sido utilizado.']);
    exit();
}

if (strtotime($otp_data['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Este código ha expirado. Por favor, solicita uno nuevo.']);
    exit();
}

// 3. ¡El código es válido! Marcamos el código como usado para que no se pueda volver a utilizar.
$update_stmt = $mysqli->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
$update_stmt->bind_param('i', $otp_data['id']);
$update_stmt->execute();
$update_stmt->close();

// 4. Incrementamos el contador de descargas del plugin correspondiente.
$update_plugin_stmt = $mysqli->prepare("UPDATE plugins SET download_count = download_count + 1 WHERE id = ?");
$update_plugin_stmt->bind_param('i', $otp_data['plugin_id']);
$update_plugin_stmt->execute();
$update_plugin_stmt->close();

// 5. Registramos la descarga en el historial, incluyendo los datos del usuario si los proporcionó.
$user_id_to_log = $_SESSION['user_id'] ?? null; // Obtenemos el ID del usuario de la sesión, si ha iniciado sesión.
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Desconocido';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

$insert_download_stmt = $mysqli->prepare(
    "INSERT INTO downloads (plugin_id, user_id, phone_number, ip_address, user_agent, user_name, user_email, opt_in_notifications) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$insert_download_stmt->bind_param('iisssssi', 
    $otp_data['plugin_id'], 
    $user_id_to_log, 
    $phone_number, 
    $ip_address, 
    $user_agent, 
    $otp_data['user_name'], 
    $otp_data['user_email'], 
    $otp_data['opt_in_notifications']
);
$insert_download_stmt->execute();
$insert_download_stmt->close();

// 6. Preparamos la respuesta exitosa con el enlace de descarga seguro.
$download_link = SITE_URL . '/download.php?plugin_id=' . $otp_data['plugin_id'];

// Enviamos la respuesta JSON al JavaScript para que inicie la descarga.
echo json_encode(['success' => true, 'download_url' => $download_link]);
?>