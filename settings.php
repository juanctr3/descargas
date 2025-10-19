<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Ajustes Generales';
$success_message = '';
$error_message = '';
$admin_id = $_SESSION['admin_id'];

// --- LÓGICA PARA PROCESAR LOS FORMULARIOS ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- FORMULARIO 1: GUARDAR AJUSTES GENERALES, SEO, APIS, ETC. ---
    if (isset($_POST['save_general_settings'])) {
        $settings_to_update = [
            'site_name'              => $_POST['site_name'] ?? '',
            'site_name_prefix'       => $_POST['site_name_prefix'] ?? '',
            'seo_meta_description'   => $_POST['seo_meta_description'] ?? '',
            'seo_meta_keywords'      => $_POST['seo_meta_keywords'] ?? '',
            'color_primary'          => $_POST['color_primary'] ?? '#667eea',
            'color_secondary'        => $_POST['color_secondary'] ?? '#764ba2',
            'color_accent'           => $_POST['color_accent'] ?? '#4facfe',
            'color_dark'             => $_POST['color_dark'] ?? '#1a202c',
            'color_light'            => $_POST['color_light'] ?? '#f7fafc',
            'google_analytics_code'  => $_POST['google_analytics_code'] ?? '',
            'review_reminder_days'   => (int)($_POST['review_reminder_days'] ?? 7), // NUEVO
            'paypal_client_id'       => $_POST['paypal_client_id'] ?? '',
            'paypal_client_secret'   => $_POST['paypal_client_secret'] ?? '',
            'mercadopago_access_token' => $_POST['mercadopago_access_token'] ?? '',
            'smsenlinea_secret'      => $_POST['smsenlinea_secret'] ?? '',
            'smsenlinea_account'     => $_POST['smsenlinea_account'] ?? '',
            'smtp_host'              => $_POST['smtp_host'] ?? '',
            'smtp_user'              => $_POST['smtp_user'] ?? '',
            'smtp_pass'              => $_POST['smtp_pass'] ?? '',
            'smtp_port'              => $_POST['smtp_port'] ?? '',
            'smtp_secure'            => $_POST['smtp_secure'] ?? 'tls',
            'smtp_from_email'        => $_POST['smtp_from_email'] ?? '',
            'smtp_from_name'         => $_POST['smtp_from_name'] ?? ''
        ];
        
        $all_ok = true;
        foreach ($settings_to_update as $key => $value) {
            $stmt = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param('ss', $value, $key);
            if (!$stmt->execute()) { $all_ok = false; }
            $stmt->close();
        }

        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_logo']; $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
            if (in_array($file['type'], $allowed_types) && $file['size'] < 2000000) {
                $file_name = 'site-logo.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $target_path = __DIR__ . '/../assets/images/' . $file_name;
                $db_path = 'assets/images/' . $file_name;
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $stmt_file = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo_path'");
                    $stmt_file->bind_param('s', $db_path);
                    if (!$stmt_file->execute()) { $all_ok = false; } $stmt_file->close();
                } else { $all_ok = false; $error_message .= ' Error al subir el logo.'; }
            } else { $all_ok = false; $error_message .= ' Archivo de logo no permitido o muy grande (máx 2MB).'; }
        }

        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_favicon']; $allowed_types = ['image/vnd.microsoft.icon', 'image/x-icon', 'image/png', 'image/svg+xml'];
            if (in_array($file['type'], $allowed_types) && $file['size'] < 1000000) {
                $file_name = 'favicon.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $target_path = __DIR__ . '/../' . $file_name;
                $db_path = $file_name;
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $stmt_file = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_favicon_path'");
                    $stmt_file->bind_param('s', $db_path);
                    if (!$stmt_file->execute()) { $all_ok = false; } $stmt_file->close();
                } else { $all_ok = false; $error_message .= ' Error al subir favicon.'; }
            } else { $all_ok = false; $error_message .= ' Archivo de favicon no permitido o muy grande.'; }
        }

        if ($all_ok) { $success_message = '¡Ajustes guardados con éxito!'; } 
        else { $error_message = 'Hubo un error al guardar algunos ajustes.'; }
        
        $app_settings = load_settings($mysqli);
    }

    // --- FORMULARIO 2: GUARDAR PERFIL DE SEGURIDAD ---
    if (isset($_POST['save_security_profile'])) {
        $whatsapp_number = $_POST['whatsapp_number'] ?? '';
        if (!empty($whatsapp_number) && !preg_match('/^\d{10,15}$/', $whatsapp_number)) {
            $error_message = 'El formato del número de WhatsApp no es válido.';
        } else {
            $stmt = $mysqli->prepare("UPDATE admins SET whatsapp_number = ? WHERE id = ?");
            $stmt->bind_param('si', $whatsapp_number, $admin_id);
            if ($stmt->execute()) { $success_message = '¡Tu número de WhatsApp para 2FA ha sido actualizado!';
            } else { $error_message = 'Hubo un error al actualizar tu número.'; }
            $stmt->close();
        }
    }
}

// Obtenemos los datos actualizados del admin para mostrar en el formulario
$admin_info_stmt = $mysqli->prepare("SELECT whatsapp_number FROM admins WHERE id = ?");
$admin_info_stmt->bind_param('i', $admin_id);
$admin_info_stmt->execute();
$admin_whatsapp_number = $admin_info_stmt->get_result()->fetch_assoc()['whatsapp_number'];
$admin_info_stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="d-flex">
        <?php include '_sidebar.php'; ?>
        <main class="w-100 p-4">
            <h1 class="mb-4"><?php echo htmlspecialchars($page_title); ?></h1>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

            <div class="card mb-4"><div class="card-header fw-bold">Mi Perfil de Seguridad</div><div class="card-body">
                <form method="POST" action="settings.php">
                    <div class="mb-3"><label for="whatsapp_number" class="form-label">Mi Número de WhatsApp para Verificación (2FA)</label><input type="tel" class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($admin_whatsapp_number ?? ''); ?>" placeholder="Ej: 573001234567"><div class="form-text">Se usará para enviarte un código cada vez que inicies sesión.</div></div>
                    <button type="submit" name="save_security_profile" class="btn btn-info">Guardar mi Número</button>
                </form>
            </div></div>

            <form method="POST" action="settings.php" enctype="multipart/form-data">
                <div class="card mb-4"><div class="card-header">Ajustes Generales y de Marca</div><div class="card-body">
                    <div class="mb-3"><label for="site_name" class="form-label">Nombre del Sitio</label><input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($app_settings['site_name'] ?? ''); ?>"></div>
                    <div class="mb-3"><label for="site_logo" class="form-label">Logo del Sitio</label><input class="form-control" type="file" id="site_logo" name="site_logo" accept="image/*"><div class="form-text">Sube una imagen (JPG, PNG, GIF, SVG) para reemplazar el nombre en el encabezado.</div>
                        <?php if (!empty($app_settings['site_logo_path'])): ?><div class="mt-2"><p class="mb-1">Logo actual:</p><img src="../<?php echo htmlspecialchars($app_settings['site_logo_path']); ?>?v=<?php echo time(); ?>" alt="Logo actual" style="max-height: 40px; background-color: #f8f9fa; padding: 5px; border-radius: 5px; border: 1px solid #dee2e6;"></div><?php endif; ?>
                    </div>
                    <div class="mb-3"><label for="site_favicon" class="form-label">Favicon del Sitio</label><input class="form-control" type="file" id="site_favicon" name="site_favicon" accept=".ico, .png, .svg"><div class="form-text">Sube el icono para la pestaña del navegador.</div>
                        <?php if (!empty($app_settings['site_favicon_path'])): ?><div class="mt-2"><p class="mb-1">Favicon actual:</p><img src="../<?php echo htmlspecialchars($app_settings['site_favicon_path']); ?>?v=<?php echo time(); ?>" alt="Favicon actual" style="width: 32px; height: 32px;"></div><?php endif; ?>
                    </div>
                    <div class="mb-3"><label for="site_name_prefix" class="form-label">Prefijo para Nombres de Archivo</label><input type="text" class="form-control" id="site_name_prefix" name="site_name_prefix" value="<?php echo htmlspecialchars($app_settings['site_name_prefix'] ?? ''); ?>"></div>
                </div></div>
                <div class="card mb-4"><div class="card-header">Personalización de Colores</div><div class="card-body"><div class="row">
                    <div class="col-md-4 mb-3"><label for="color_primary" class="form-label">Color Primario</label><input type="color" class="form-control form-control-color" id="color_primary" name="color_primary" value="<?php echo htmlspecialchars($app_settings['color_primary'] ?? '#667eea'); ?>"></div>
                    <div class="col-md-4 mb-3"><label for="color_secondary" class="form-label">Color Secundario</label><input type="color" class="form-control form-control-color" id="color_secondary" name="color_secondary" value="<?php echo htmlspecialchars($app_settings['color_secondary'] ?? '#764ba2'); ?>"></div>
                    <div class="col-md-4 mb-3"><label for="color_accent" class="form-label">Color de Acento</label><input type="color" class="form-control form-control-color" id="color_accent" name="color_accent" value="<?php echo htmlspecialchars($app_settings['color_accent'] ?? '#4facfe'); ?>"></div>
                    <div class="col-md-4 mb-3"><label for="color_dark" class="form-label">Color Oscuro</label><input type="color" class="form-control form-control-color" id="color_dark" name="color_dark" value="<?php echo htmlspecialchars($app_settings['color_dark'] ?? '#1a202c'); ?>"></div>
                    <div class="col-md-4 mb-3"><label for="color_light" class="form-label">Color Claro</label><input type="color" class="form-control form-control-color" id="color_light" name="color_light" value="<?php echo htmlspecialchars($app_settings['color_light'] ?? '#f7fafc'); ?>"></div>
                </div></div></div>
                <div class="card mb-4"><div class="card-header">Ajustes SEO</div><div class="card-body">
                    <div class="mb-3"><label for="seo_meta_description" class="form-label">Meta Descripción</label><textarea class="form-control" id="seo_meta_description" name="seo_meta_description" rows="3"><?php echo htmlspecialchars($app_settings['seo_meta_description'] ?? ''); ?></textarea></div>
                    <div class="mb-3"><label for="seo_meta_keywords" class="form-label">Palabras Clave (Keywords)</label><input type="text" class="form-control" id="seo_meta_keywords" name="seo_meta_keywords" value="<?php echo htmlspecialchars($app_settings['seo_meta_keywords'] ?? ''); ?>"></div>
                </div></div>
                <div class="card mb-4"><div class="card-header">Integraciones y Scripts</div><div class="card-body">
                    <div class="mb-3"><label for="google_analytics_code" class="form-label">Código de Google Analytics (GA4)</label><textarea class="form-control" id="google_analytics_code" name="google_analytics_code" rows="8" placeholder="Pega aquí el código completo de Google, incluyendo las etiquetas <script>..."><?php echo htmlspecialchars($app_settings['google_analytics_code'] ?? ''); ?></textarea></div>
                    
                    <div class="mb-3">
                        <label for="review_reminder_days" class="form-label">Días para Recordatorio de Reseña</label>
                        <input type="number" class="form-control" id="review_reminder_days" name="review_reminder_days" min="1" value="<?php echo htmlspecialchars($app_settings['review_reminder_days'] ?? '7'); ?>">
                        <div class="form-text">Número de días a esperar después de una descarga antes de enviar un recordatorio para dejar una reseña.</div>
                    </div>
                    </div></div>
                <div class="card mb-4"><div class="card-header">Ajustes de Pasarelas de Pago</div><div class="card-body">
                    <h6 class="card-title">PayPal</h6>
                    <div class="mb-3"><label for="paypal_client_id" class="form-label">PayPal Client ID</label><input type="text" class="form-control" id="paypal_client_id" name="paypal_client_id" value="<?php echo htmlspecialchars($app_settings['paypal_client_id'] ?? ''); ?>"></div>
                    <div class="mb-3"><label for="paypal_client_secret" class="form-label">PayPal Client Secret</label><input type="password" class="form-control" id="paypal_client_secret" name="paypal_client_secret" value="<?php echo htmlspecialchars($app_settings['paypal_client_secret'] ?? ''); ?>"></div>
                    <hr><h6 class="card-title mt-3">Mercado Pago</h6>
                    <div class="mb-3"><label for="mercadopago_access_token" class="form-label">Mercado Pago Access Token</label><input type="text" class="form-control" id="mercadopago_access_token" name="mercadopago_access_token" value="<?php echo htmlspecialchars($app_settings['mercadopago_access_token'] ?? ''); ?>"></div>
                </div></div>
                <div class="card mb-4"><div class="card-header">Ajustes de Email (SMTP)</div><div class="card-body"><div class="row">
                    <div class="col-md-6 mb-3"><label for="smtp_host" class="form-label">Host SMTP</label><input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($app_settings['smtp_host'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="smtp_user" class="form-label">Usuario SMTP</label><input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($app_settings['smtp_user'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="smtp_pass" class="form-label">Contraseña SMTP</label><input type="password" class="form-control" id="smtp_pass" name="smtp_pass" value="<?php echo htmlspecialchars($app_settings['smtp_pass'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="smtp_port" class="form-label">Puerto SMTP</label><input type="text" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($app_settings['smtp_port'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="smtp_secure" class="form-label">Seguridad</label><select class="form-select" name="smtp_secure"><option value="tls" <?php echo (($app_settings['smtp_secure'] ?? '') == 'tls') ? 'selected' : ''; ?>>TLS</option><option value="ssl" <?php echo (($app_settings['smtp_secure'] ?? '') == 'ssl') ? 'selected' : ''; ?>>SSL</option></select></div>
                    <div class="col-md-6 mb-3"><label for="smtp_from_email" class="form-label">Email Remitente</label><input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($app_settings['smtp_from_email'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="smtp_from_name" class="form-label">Nombre Remitente</label><input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($app_settings['smtp_from_name'] ?? ''); ?>"></div>
                </div></div></div>
                <div class="card"><div class="card-header">Ajustes de la API de WhatsApp (SmSenlinea)</div><div class="card-body">
                    <div class="mb-3"><label for="smsenlinea_secret" class="form-label">API Secret Key</label><input type="text" class="form-control" id="smsenlinea_secret" name="smsenlinea_secret" value="<?php echo htmlspecialchars($app_settings['smsenlinea_secret'] ?? ''); ?>"></div>
                    <div class="mb-3"><label for="smsenlinea_account" class="form-label">Account ID</label><input type="text" class="form-control" id="smsenlinea_account" name="smsenlinea_account" value="<?php echo htmlspecialchars($app_settings['smsenlinea_account'] ?? ''); ?>"></div>
                </div></div>
                <button type="submit" name="save_general_settings" class="btn btn-primary mt-3 w-100">Guardar Todos los Ajustes</button>
            </form>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>