<?php
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, ingresa tu usuario y contraseña.';
    } else {
        $stmt = $mysqli->prepare("SELECT id, username, password, whatsapp_number FROM admins WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                // ¡Contraseña correcta! Ahora procedemos con el 2FA.
                if (empty($admin['whatsapp_number'])) {
                    $error_message = 'El acceso 2FA no está configurado para este usuario. Guarda tu número de WhatsApp en la sección de Ajustes.';
                } else {
                    $otp_code = rand(100000, 999999);
                    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    $phone_number = $admin['whatsapp_number'];
                    
                    // --- LA CORRECCIÓN ESTÁ AQUÍ ---
                    // Guardamos el código en la tabla otp_codes (usamos plugin_id=NULL para diferenciarlo)
                    $otp_stmt = $mysqli->prepare("INSERT INTO otp_codes (phone_number, otp_code, plugin_id, expires_at) VALUES (?, ?, NULL, ?)");
                    // Como el plugin_id es NULL, solo necesitamos enlazar 3 parámetros
                    $otp_stmt->bind_param('sss', $phone_number, $otp_code, $expires_at);
                    
                    if ($otp_stmt->execute() && sendWhatsAppNotification($phone_number, "🔐 Tu código de acceso al panel es: *{$otp_code}*. Válido por 10 minutos.", $app_settings)) {
                        // Redirigimos a la página de verificación
                        header('Location: verify-login.php?id=' . $admin['id']);
                        exit();
                    } else {
                        $error_message = 'No se pudo enviar el código de verificación a tu WhatsApp.';
                    }
                }
            } else {
                $error_message = 'El usuario o la contraseña son incorrectos.';
            }
        } else {
            $error_message = 'El usuario o la contraseña son incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body {display: flex; align-items: center; justify-content: center; height: 100vh; background-color: #f8f9fa;} .login-card {width: 100%; max-width: 400px; padding: 2rem; border: none; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);}</style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Acceso de Administrador</h3>
            <?php if (!empty($error_message)): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
            <form action="index.php" method="POST">
                <div class="mb-3"><label for="username" class="form-label">Usuario</label><input type="text" class="form-control" id="username" name="username" required></div>
                <div class="mb-3"><label for="password" class="form-label">Contraseña</label><input type="password" class="form-control" id="password" name="password" required></div>
                <div class="d-grid"><button type="submit" class="btn btn-primary">Ingresar</button></div>
            </form>
        </div>
    </div>
</body>
</html>