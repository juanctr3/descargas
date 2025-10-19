<?php
// api/submit-question.php

header('Content-Type: application/json');
require_once '../includes/config.php';

// Solo permitimos que usuarios logueados envíen preguntas
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para poder preguntar.']);
    exit();
}

// Verificamos que la solicitud sea por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$plugin_id = $_POST['plugin_id'] ?? null;
$question_text = trim($_POST['question_text'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($plugin_id) || empty($question_text)) {
    echo json_encode(['success' => false, 'message' => 'La pregunta no puede estar vacía.']);
    exit();
}

// Insertamos la pregunta en la base de datos
$stmt = $mysqli->prepare("INSERT INTO plugin_questions (plugin_id, user_id, question) VALUES (?, ?, ?)");
$stmt->bind_param('iis', $plugin_id, $user_id, $question_text);

if ($stmt->execute()) {
    $question_id = $stmt->insert_id; // Obtenemos el ID de la pregunta insertada

    // --- NUEVO: NOTIFICAR AL ADMINISTRADOR ---
    // Obtenemos datos para la notificación
    $user_name = $_SESSION['user_name'] ?? 'un usuario';
    $plugin_title_res = $mysqli->query("SELECT title FROM plugins WHERE id = $plugin_id");
    $plugin_title = $plugin_title_res->fetch_assoc()['title'];

    // Obtenemos los datos de todos los admins
    $admins_res = $mysqli->query("SELECT email, whatsapp_number FROM admins");
    $admins = $admins_res->fetch_all(MYSQLI_ASSOC);
    
    $panel_url = SITE_URL . '/admin/manage-questions.php';
    
    foreach ($admins as $admin) {
        // Notificación por WhatsApp
        if (!empty($admin['whatsapp_number'])) {
            $wa_message = "🔔 ¡Nueva pregunta en el sitio!\n\n*Usuario:* {$user_name}\n*Plugin:* {$plugin_title}\n\nRevisa el panel de administración para responder:\n{$panel_url}";
            sendWhatsAppNotification($admin['whatsapp_number'], $wa_message, $app_settings);
        }
        // Notificación por Email
        if (!empty($admin['email'])) {
            $email_subject = "Nueva Pregunta sobre el plugin: {$plugin_title}";
            $email_body = "Hola,<br><br>Se ha recibido una nueva pregunta de <strong>{$user_name}</strong> sobre el plugin <strong>'{$plugin_title}'</strong>.<br><br>Puedes verla y responderla desde el panel de administración:<br><a href='{$panel_url}'>Gestionar Preguntas</a>";
            sendSMTPMail($admin['email'], 'Admin', $email_subject, $email_body, $app_settings);
        }
    }
    // --- FIN DE LA NOTIFICACIÓN ---

    echo json_encode(['success' => true, 'message' => '¡Pregunta enviada! Será revisada por un administrador antes de publicarse.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Hubo un error al enviar tu pregunta.']);
}

$stmt->close();
?>