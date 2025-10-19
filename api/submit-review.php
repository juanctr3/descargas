<?php
// api/submit-review.php

header('Content-Type: application/json');
require_once '../includes/config.php';

// Verificamos que la solicitud sea por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

$plugin_id = $_POST['plugin_id'] ?? null;
$rating = $_POST['rating'] ?? 0;
$review_title = trim($_POST['review_title'] ?? '');
$review_text = trim($_POST['review_text'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if (empty($plugin_id) || empty($rating) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Por favor, selecciona una calificación de 1 a 5 estrellas.']);
    exit();
}

// ... (El bloque de verificación de elegibilidad se mantiene igual)
$is_eligible = false;
if ($user_id) {
    $stmt_check = $mysqli->prepare("SELECT id FROM downloads WHERE user_id = ? AND plugin_id = ? LIMIT 1");
    $stmt_check->bind_param('ii', $user_id, $plugin_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) { $is_eligible = true; }
    $stmt_check->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para dejar una reseña.']);
    exit();
}
if (!$is_eligible) {
    echo json_encode(['success' => false, 'message' => 'Necesitas haber descargado este plugin para poder dejar una reseña.']);
    exit();
}

// Insertamos la reseña
$stmt = $mysqli->prepare("INSERT INTO plugin_reviews (plugin_id, user_id, rating, review_title, review_text) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('iiiss', $plugin_id, $user_id, $rating, $review_title, $review_text);

if ($stmt->execute()) {
    // --- NUEVO: NOTIFICAR AL ADMINISTRADOR ---
    $user_name = $_SESSION['user_name'] ?? 'un usuario';
    $plugin_title_res = $mysqli->query("SELECT title FROM plugins WHERE id = $plugin_id");
    $plugin_title = $plugin_title_res->fetch_assoc()['title'];

    $admins_res = $mysqli->query("SELECT email, whatsapp_number FROM admins");
    $admins = $admins_res->fetch_all(MYSQLI_ASSOC);
    
    $panel_url = SITE_URL . '/admin/manage-reviews.php';
    $stars = str_repeat('⭐', $rating);

    foreach ($admins as $admin) {
        if (!empty($admin['whatsapp_number'])) {
            $wa_message = "⭐ ¡Nueva reseña en el sitio!\n\n*Usuario:* {$user_name}\n*Plugin:* {$plugin_title}\n*Calificación:* {$rating}/5 {$stars}\n\nRevisa el panel para moderarla:\n{$panel_url}";
            sendWhatsAppNotification($admin['whatsapp_number'], $wa_message, $app_settings);
        }
        if (!empty($admin['email'])) {
            $email_subject = "Nueva Reseña de {$rating} estrellas para: {$plugin_title}";
            $email_body = "Hola,<br><br>Se ha recibido una nueva reseña de <strong>{$user_name}</strong> para el plugin <strong>'{$plugin_title}'</strong> con una calificación de <strong>{$rating} de 5 estrellas</strong>.<br><br>Puedes verla y aprobarla desde el panel de administración:<br><a href='{$panel_url}'>Gestionar Reseñas</a>";
            sendSMTPMail($admin['email'], 'Admin', $email_subject, $email_body, $app_settings);
        }
    }
    // --- FIN DE LA NOTIFICACIÓN ---

    echo json_encode(['success' => true, 'message' => '¡Gracias por tu reseña! Será revisada por un administrador antes de publicarse.']);
} else {
    if ($mysqli->errno == 1062) {
        echo json_encode(['success' => false, 'message' => 'Ya has enviado una reseña para este plugin.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Hubo un error al enviar tu reseña.']);
    }
}
$stmt->close();
?>