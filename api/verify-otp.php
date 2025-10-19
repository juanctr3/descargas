<?php
// api/verify-otp.php

header('Content-Type: application/json');
require_once '../includes/config.php';

// Validamos que la solicitud sea por el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

// Recibimos y limpiamos los datos del formulario
$plugin_id = filter_input(INPUT_POST, 'plugin_id', FILTER_VALIDATE_INT);
$plugin_slug = filter_input(INPUT_POST, 'plugin_slug', FILTER_SANITIZE_STRING);
$otp_code = trim($_POST['otp_code'] ?? '');
$phone_number = $_SESSION['download_phone'] ?? null;

// Validaciones básicas de los datos recibidos
if (!$plugin_id || !$plugin_slug || empty($otp_code) || empty($phone_number)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para la verificación.']);
    exit();
}

// Verificamos que el código OTP sea correcto y no haya expirado
if (!isset($_SESSION['otp_code']) || $_SESSION['otp_code'] != $otp_code || time() > $_SESSION['otp_expiry']) {
    echo json_encode(['success' => false, 'message' => 'El código es incorrecto o ha expirado.']);
    exit();
}

// Limpiamos las variables de sesión del OTP para que no se pueda reutilizar
unset($_SESSION['otp_code']);
unset($_SESSION['otp_expiry']);
unset($_SESSION['download_phone']);

// Recogemos los datos opcionales del usuario
$user_name = trim($_POST['user_name'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ? trim($_POST['email']) : null;
$opt_in = isset($_POST['opt_in']) ? 1 : 0;

// Verificamos si el número de teléfono corresponde a un usuario ya registrado
$user_id = null;
$stmt_user = $mysqli->prepare("SELECT id, name FROM users WHERE whatsapp_number = ?");
$stmt_user->bind_param('s', $phone_number);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $user_id = $user_data['id'];
    // Si el usuario ya existe, usamos su nombre de la base de datos
    $user_name = $user_data['name'];
}
$stmt_user->close();


// Registramos la descarga en la base de datos
$stmt = $mysqli->prepare("INSERT INTO downloads (plugin_id, user_id, user_name, user_email, phone_number, opt_in_notifications) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('iisssi', $plugin_id, $user_id, $user_name, $email, $phone_number, $opt_in);

if ($stmt->execute()) {
    $download_id = $stmt->insert_id;

    // Incrementar el contador de descargas del plugin
    $mysqli->query("UPDATE plugins SET download_count = download_count + 1 WHERE id = $plugin_id");

    $response = [
        'success' => true,
        'message' => '¡Código verificado! Tu descarga comenzará en breve.',
        'download_url' => SITE_URL . '/download.php?slug=' . $plugin_slug
    ];

    // --- GENERACIÓN DE LICENCIA AUTOMÁTICA ---
    $plugin_res = $mysqli->query("SELECT title, requires_license FROM plugins WHERE id = $plugin_id");
    $plugin_data = $plugin_res->fetch_assoc();

    if ($plugin_data && $plugin_data['requires_license']) {
        $license_key = 'LIC-' . strtoupper(bin2hex(random_bytes(16)));
        $duration_days = (int)($app_settings['default_license_duration'] ?? 365);
        $expires_at = null;
        $expires_at_formatted = 'Nunca';

        if ($duration_days > 0) {
            $expires_at = date('Y-m-d', strtotime("+$duration_days days"));
            $expires_at_formatted = date('d/m/Y', strtotime($expires_at));
        }

        // Insertar la nueva licencia en la base de datos
        $stmt_lic = $mysqli->prepare("INSERT INTO license_keys (plugin_id, user_id, download_id, license_key, expires_at) VALUES (?, ?, ?, ?, ?)");
        
        // --- LÍNEA CORREGIDA ---
        // Se cambió el segundo tipo de 'i' (integer) a 's' (string) para que la base de datos
        // acepte correctamente el valor `null` cuando el usuario no ha iniciado sesión.
        $stmt_lic->bind_param('isiss', $plugin_id, $user_id, $download_id, $license_key, $expires_at);
        
        $stmt_lic->execute();
        $stmt_lic->close();

        // Añadir la licencia a la respuesta para mostrarla en el alert
        $response['license_key'] = $license_key;
        $response['expires_at'] = $expires_at_formatted;

        // Enviar notificaciones con la licencia
        $user_name_to_notify = !empty($user_name) ? $user_name : 'Usuario';
        
        // Notificación por WhatsApp
        if (!empty($phone_number)) {
            $wa_message = "🎉 ¡Gracias por descargar *{$plugin_data['title']}*!\n\nTu clave de licencia es:\n*{$license_key}*\n\nExpira: {$expires_at_formatted}\n\nGuárdala para activar el plugin.";
            sendWhatsAppNotification($phone_number, $wa_message, $app_settings);
        }
        // Notificación por Email
        if (!empty($email)) {
            $email_subject = "Tu licencia para el plugin {$plugin_data['title']}";
            $email_body = "Hola {$user_name_to_notify},<br><br>Gracias por tu descarga. Aquí tienes tu clave de licencia para activar <strong>{$plugin_data['title']}</strong>:<br><br><strong style='font-size: 1.2em; background-color: #f0f0f0; padding: 5px 10px; border-radius: 5px;'>{$license_key}</strong><br><br>Esta licencia expira el: <strong>{$expires_at_formatted}</strong>.<br><br>¡Disfruta del plugin!";
            sendSMTPMail($email, $user_name_to_notify, $email_subject, $email_body, $app_settings);
        }
    }
    // --- FIN DE GENERACIÓN DE LICENCIA ---

    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al registrar la descarga.']);
}

$stmt->close();
?>