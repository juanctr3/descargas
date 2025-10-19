<?php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}
$admin_id = (int)$_GET['id'];

$user_stmt = $mysqli->prepare("SELECT username, whatsapp_number FROM admins WHERE id = ?");
$user_stmt->bind_param('i', $admin_id);
$user_stmt->execute();
$admin_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$admin_data || empty($admin_data['whatsapp_number'])) {
    header('Location: index.php');
    exit();
}
$phone_number = $admin_data['whatsapp_number'];
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = $_POST['otp_code'] ?? '';
    if (empty($otp_code)) {
        $error_message = 'Por favor, ingresa el código de verificación.';
    } else {
        // --- LA CORRECCIÓN ESTÁ AQUÍ ---
        // Buscamos donde la columna plugin_id ES NULL, que es la marca para los OTP de admin.
        $stmt = $mysqli->prepare("SELECT id, used, expires_at FROM otp_codes WHERE phone_number = ? AND otp_code = ? AND plugin_id IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('ss', $phone_number, $otp_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $otp_data = $result->fetch_assoc();
            if ($otp_data['used']) {
                $error_message = 'Este código ya ha sido utilizado.';
            } elseif (strtotime($otp_data['expires_at']) < time()) {
                $error_message = 'Este código ha expirado.';
            } else {
                // ¡Código correcto! Iniciamos sesión y lo marcamos como usado.
                $update_stmt = $mysqli->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
                $update_stmt->bind_param('i', $otp_data['id']);
                $update_stmt->execute();
                $update_stmt->close();

                $_SESSION['admin_id'] = $admin_id;
                $_SESSION['admin_username'] = $admin_data['username'];
                header('Location: dashboard.php');
                exit();
            }
        } else {
            $error_message = 'El código ingresado es incorrecto.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Dos Factores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body {display: flex; align-items: center; justify-content: center; height: 100vh; background-color: #f8f9fa;} .login-card {width: 100%; max-width: 400px; padding: 2rem; border: none; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);}</style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body text-center">
            <h3 class="card-title mb-3">Verificar Acceso</h3>
            <p class="text-muted">Hemos enviado un código de 6 dígitos a tu WhatsApp. Por favor, ingrésalo a continuación.</p>
            <?php if (!empty($error_message)): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
            <form action="verify-login.php?id=<?php echo $admin_id; ?>" method="POST">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-lg text-center" id="otp_code" name="otp_code" maxlength="6" required autofocus>
                </div>
                <div class="d-grid"><button type="submit" class="btn btn-success">Verificar e Ingresar</button></div>
            </form>
            <div class="mt-3"><a href="index.php">Volver al inicio de sesión</a></div>
        </div>
    </div>
</body>
</html>