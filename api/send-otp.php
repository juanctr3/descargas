<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$plugin_id = $_POST['plugin_id'] ?? null;
$phone_number = $_POST['phone_number'] ?? null;
$opt_in = $_POST['opt_in'] ?? '0';
$user_name = trim($_POST['user_name'] ?? '');
$user_email = trim($_POST['user_email'] ?? '');
$user_id = $_SESSION['user_id'] ?? null; // Capturamos el ID del usuario si ha iniciado sesión

if (empty($plugin_id) || empty($phone_number) || !is_numeric($plugin_id)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

$otp_code = rand(100000, 999999);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$opt_in_bool = ($opt_in === '1');

$stmt = $mysqli->prepare("INSERT INTO otp_codes (phone_number, otp_code, plugin_id, expires_at, user_name, user_email, opt_in_notifications) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssisssi', $phone_number, $otp_code, $plugin_id, $expires_at, $user_name, $user_email, $opt_in_bool);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el código de verificación.']);
    exit();
}
$stmt->close();

if (sendWhatsAppOTP($phone_number, $otp_code, $app_settings)) {
    echo json_encode(['success' => true, 'message' => 'Código enviado con éxito.']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo enviar el código por WhatsApp. Revisa las credenciales de la API.']);
}
?>