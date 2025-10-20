<?php
// --- INICIO: CÓDIGO DE PRODUCCIÓN FINAL verify-otp.php ---

header('Content-Type: application/json');
require_once '../includes/config.php';

// Activar el modo de reporte de errores de MySQLi para que lance excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    // --- Lectura de datos ---
    $plugin_id    = $_POST['otp_plugin_id'] ?? 0;
    $plugin_slug  = $_POST['otp_plugin_slug'] ?? '';
    $otp_code     = $_POST['otp_code'] ?? '';
    $phone_number = $_SESSION['download_phone'] ?? null;

    if (empty($plugin_id) || empty($plugin_slug) || empty($otp_code) || empty($phone_number)) {
        throw new Exception('Faltan datos para la verificación.');
    }

    if (!isset($_SESSION['otp_code']) || $_SESSION['otp_code'] != $otp_code || !isset($_SESSION['otp_expiry']) || time() > $_SESSION['otp_expiry']) {
        throw new Exception('El código es incorrecto o ha expirado.');
    }

    unset($_SESSION['otp_code'], $_SESSION['otp_expiry'], $_SESSION['download_phone']);

    $user_name = trim(strip_tags($_POST['user_name'] ?? ''));
    $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ? trim($_POST['email']) : null;
    $opt_in    = isset($_POST['opt_in']) ? 1 : 0;

    // --- Gestión de Usuario (Crear o Verificar) ---
    $user_id = null;
    $is_new_user = false;

    $stmt_user = $mysqli->prepare("SELECT id, name FROM users WHERE whatsapp_number = ?");
    $stmt_user->bind_param('s', $phone_number);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();

    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_id = $user_data['id'];
        $user_name = $user_data['name'];
    } else {
        $is_new_user = true;
        if (!empty($user_name) && !empty($email)) {
            $stmt_create = $mysqli->prepare("INSERT INTO users (name, email, whatsapp_number, password) VALUES (?, ?, ?, ?)");
            $stmt_create->bind_param('ssss', $user_name, $email, $phone_number, '');
            $stmt_create->execute();
            $user_id = $stmt_create->insert_id;
            $stmt_create->close();
        } else {
            // Si es un usuario nuevo pero no proporcionó nombre y email, es un error.
            throw new Exception('Nombre y Email son requeridos para nuevos usuarios.');
        }
    }
    $stmt_user->close();

    // --- Registro de Descarga ---
    $stmt_download = $mysqli->prepare("INSERT INTO downloads (plugin_id, user_id, user_name, user_email, phone_number, opt_in_notifications) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_download->bind_param('iisssi', $plugin_id, $user_id, $user_name, $email, $phone_number, $opt_in);
    $stmt_download->execute();
    $download_id = $stmt_download->insert_id;
    $stmt_download->close();

    $mysqli->query("UPDATE plugins SET download_count = download_count + 1 WHERE id = $plugin_id");

    $response = [
        'success'      => true,
        'message'      => '¡Código verificado! Tu descarga comenzará en breve.',
        'download_url' => SITE_URL . '/download.php?slug=' . urlencode($plugin_slug),
        'is_new_user'  => $is_new_user,
        'user_id'      => $user_id
    ];

    // --- Generación de Licencia ---
    $stmt_plugin = $mysqli->prepare("SELECT title, requires_license FROM plugins WHERE id = ?");
    $stmt_plugin->bind_param('i', $plugin_id);
    $stmt_plugin->execute();
    $plugin_res = $stmt_plugin->get_result();
    if ($plugin_data = $plugin_res->fetch_assoc()) {
        if ($plugin_data['requires_license']) {
            $license_key = 'LIC-' . strtoupper(bin2hex(random_bytes(16)));
            $duration_days = (int)($app_settings['default_license_duration'] ?? 365);
            $expires_at = null;
            $expires_at_formatted = 'Nunca';

            if ($duration_days > 0) {
                $expires_at = date('Y-m-d', strtotime("+$duration_days days"));
                $expires_at_formatted = date('d/m/Y', strtotime($expires_at));
            }

            $stmt_lic = $mysqli->prepare("INSERT INTO license_keys (plugin_id, user_id, download_id, license_key, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt_lic->bind_param('iiiss', $plugin_id, $user_id, $download_id, $license_key, $expires_at);
            $stmt_lic->execute();
            $stmt_lic->close();

            $response['license_key'] = $license_key;
            $response['expires_at'] = $expires_at_formatted;
        }
    }
    $stmt_plugin->close();

    echo json_encode($response);

} catch (mysqli_sql_exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de Base de Datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error General: ' . $e->getMessage()]);
}

// --- FIN: CÓDIGO DE PRODUCCIÓN FINAL ---
?>