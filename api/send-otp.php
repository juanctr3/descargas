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

// --- INICIO: Verificación de usuario existente (Versión robusta) ---
$stmt_check = $mysqli->prepare("SELECT id FROM users WHERE whatsapp_number = ?");

// Comprobamos que la consulta se preparó correctamente para evitar errores fatales
if ($stmt_check) {
    $stmt_check->bind_param('s', $phone_number);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        // Si encontramos un usuario, detenemos el proceso y le pedimos que inicie sesión.
        echo json_encode(['success' => false, 'message' => 'Este número de teléfono ya está registrado. Por favor, inicia sesión para descargar.']);
        $stmt_check->close();
        exit();
    }
    $stmt_check->close();
} else {
    // Si la consulta falla, es un error del servidor, no del usuario.
    echo json_encode(['success' => false, 'message' => 'Error del servidor al verificar el usuario. Por favor, contacta al soporte.']);
    exit();
}
// --- FIN: Verificación de usuario existente ---


$otp_code = rand(100000, 999999);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$opt_in_bool = ($opt_in === '1');

// --- AÑADIR ESTE NUEVO BLOQUE ---
$_SESSION['otp_code'] = $otp_code;
$_SESSION['otp_expiry'] = strtotime('+10 minutes'); // Guarda la hora de expiración
$_SESSION['download_phone'] = $phone_number; // Guarda el número de teléfono
// --- FIN DEL BLOQUE A AÑADIR ---

if (sendWhatsAppOTP($phone_number, $otp_code, $app_settings)) {
    echo json_encode(['success' => true, 'message' => 'Código enviado con éxito.']);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo enviar el código por WhatsApp. Revisa las credenciales de la API.']);
}
?>