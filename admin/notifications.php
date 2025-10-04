<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Enviar Notificaciones';
$feedback_message = '';
$message_class = '';

$plugins_result = $mysqli->query("SELECT id, title FROM plugins ORDER BY title ASC");
$plugins = $plugins_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plugin_id = $_POST['plugin_id'] ?? 0;
    $channel = $_POST['channel'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message_template = $_POST['message_template'] ?? '';

    if (empty($plugin_id) || empty($channel) || empty($message_template) || ($channel === 'email' && empty($subject))) {
        $feedback_message = 'Por favor, completa todos los campos requeridos para el canal seleccionado.';
        $message_class = 'alert-danger';
    } else {
        $query = "SELECT DISTINCT user_name, user_email, phone_number FROM downloads WHERE plugin_id = ? AND opt_in_notifications = 1";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $plugin_id);
        $stmt->execute();
        $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $sent_count = 0;
        if (count($recipients) > 0) {
            $plugin_info_stmt = $mysqli->prepare("SELECT title, version, slug FROM plugins WHERE id = ?");
            $plugin_info_stmt->bind_param('i', $plugin_id);
            $plugin_info_stmt->execute();
            $plugin_info = $plugin_info_stmt->get_result()->fetch_assoc();
            $plugin_info_stmt->close();

            // Construimos la URL amigable del plugin
            $plugin_url = SITE_URL . '/plugin/' . htmlspecialchars($plugin_info['slug']) . '/';

            foreach ($recipients as $recipient) {
                // Reemplazamos los shortcodes
                $message_body = str_replace('{nombre_usuario}', htmlspecialchars($recipient['user_name']), $message_template);
                $message_body = str_replace('{nombre_plugin}', htmlspecialchars($plugin_info['title']), $message_body);
                $message_body = str_replace('{version_actual}', htmlspecialchars($plugin_info['version']), $message_body);
                $message_body = str_replace('{url_descarga}', $plugin_url, $message_body); // Usamos la nueva URL

                // Enviamos por el canal seleccionado
                if ($channel === 'email' && !empty($recipient['user_email'])) {
                    if (sendSMTPMail($recipient['user_email'], $recipient['user_name'], $subject, nl2br($message_body), $app_settings) === true) {
                        $sent_count++;
                    }
                } elseif ($channel === 'whatsapp' && !empty($recipient['phone_number'])) {
                    if (sendWhatsAppNotification($recipient['phone_number'], $message_body, $app_settings)) {
                        $sent_count++;
                    }
                }
            }
            $feedback_message = "Proceso finalizado. Se intentaron enviar {$sent_count} de " . count($recipients) . " notificaciones.";
            $message_class = 'alert-success';
        } else {
            $feedback_message = 'No se encontraron usuarios que hayan aceptado recibir notificaciones para este plugin.';
            $message_class = 'alert-warning';
        }
    }
}
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
            <?php if ($feedback_message): ?><div class="alert <?php echo $message_class; ?>"><?php echo $feedback_message; ?></div><?php endif; ?>
            <div class="card"><div class="card-body">
                <form method="POST" action="notifications.php">
                    <div class="mb-3"><label class="form-label fw-bold">1. Canal de Envío</label><div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="channel" id="channelEmail" value="email" checked><label class="form-check-label" for="channelEmail"><i class="fas fa-envelope"></i> Email</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="channel" id="channelWhatsApp" value="whatsapp"><label class="form-check-label" for="channelWhatsApp"><i class="fab fa-whatsapp"></i> WhatsApp</label></div></div></div>
                    <div class="mb-3"><label for="plugin_id" class="form-label fw-bold">2. Selecciona el Plugin para Notificar</label><select class="form-select" id="plugin_id" name="plugin_id" required><option value="" selected disabled>-- Elige un plugin --</option><?php foreach ($plugins as $plugin): ?><option value="<?php echo $plugin['id']; ?>"><?php echo htmlspecialchars($plugin['title']); ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3" id="subject-field"><label for="subject" class="form-label fw-bold">3. Asunto del Correo</label><input type="text" class="form-control" id="subject" name="subject" required></div>
                    <div class="mb-3"><label for="message_template" class="form-label fw-bold">4. Cuerpo del Mensaje</label><textarea class="form-control" id="message_template" name="message_template" rows="10" required></textarea><div class="form-text"><strong>Shortcodes:</strong> <code>{nombre_usuario}</code>, <code>{nombre_plugin}</code>, <code>{version_actual}</code>, <code>{url_descarga}</code></div></div>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> Enviar Notificaciones</button>
                </form>
            </div></div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const subjectField = document.getElementById('subject-field');
            const subjectInput = document.getElementById('subject');
            document.querySelectorAll('input[name="channel"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'email') {
                        subjectField.style.display = 'block';
                        subjectInput.required = true;
                    } else {
                        subjectField.style.display = 'none';
                        subjectInput.required = false;
                    }
                });
            });
        });
    </script>
</body>
</html>