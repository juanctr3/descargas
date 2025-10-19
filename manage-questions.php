<?php
// admin/manage-questions.php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Gestionar Preguntas y Respuestas';
$feedback_message = '';
$message_class = '';

// --- LÓGICA PARA PROCESAR EL FORMULARIO DE RESPUESTA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer_question'])) {
    $question_id = $_POST['question_id'];
    $answer_text = trim($_POST['answer_text']);
    $is_approved = isset($_POST['is_approved']) ? 1 : 0;

    if (!empty($answer_text)) {
        $stmt = $mysqli->prepare("UPDATE plugin_questions SET answer = ?, answer_date = NOW(), is_answered = 1, is_approved = ? WHERE id = ?");
        $stmt->bind_param('sii', $answer_text, $is_approved, $question_id);
        
        if ($stmt->execute()) {
            $feedback_message = 'Respuesta guardada y actualizada con éxito.';
            $message_class = 'alert-success';

            // --- NUEVO: NOTIFICAR AL USUARIO SI LA PREGUNTA ESTÁ APROBADA ---
            if ($is_approved) {
                // Obtenemos los datos del usuario y del plugin para la notificación
                $query = "
                    SELECT u.name, u.email, u.whatsapp_number, p.title as plugin_title, p.slug as plugin_slug
                    FROM plugin_questions q
                    JOIN users u ON q.user_id = u.id
                    JOIN plugins p ON q.plugin_id = p.id
                    WHERE q.id = ?
                ";
                $notif_stmt = $mysqli->prepare($query);
                $notif_stmt->bind_param('i', $question_id);
                $notif_stmt->execute();
                $data = $notif_stmt->get_result()->fetch_assoc();
                $notif_stmt->close();

                if ($data) {
                    $question_url = SITE_URL . '/questions.php?slug=' . $data['plugin_slug'];
                    if (!empty($data['whatsapp_number'])) {
                        $wa_message = "✅ ¡Hola, {$data['name']}! Tu pregunta sobre *'{$data['plugin_title']}'* ha sido respondida.\n\nPuedes ver la respuesta aquí:\n{$question_url}";
                        sendWhatsAppNotification($data['whatsapp_number'], $wa_message, $app_settings);
                    }
                    if (!empty($data['email'])) {
                        $email_subject = "Tu pregunta sobre '{$data['plugin_title']}' ha sido respondida";
                        $email_body = "Hola {$data['name']},<br><br>Un administrador ha respondido a tu pregunta sobre el plugin <strong>'{$data['plugin_title']}'</strong>.<br><br>Puedes ver la respuesta directamente en la página de preguntas del plugin:<br><a href='{$question_url}'>Ver Respuesta</a>";
                        sendSMTPMail($data['email'], $data['name'], $email_subject, $email_body, $app_settings);
                    }
                }
            }
            // --- FIN DE LA NOTIFICACIÓN ---
        } else {
            $feedback_message = 'Error al guardar la respuesta.';
            $message_class = 'alert-danger';
        }
        $stmt->close();
    } else {
        $stmt = $mysqli->prepare("UPDATE plugin_questions SET is_approved = ? WHERE id = ?");
        $stmt->bind_param('ii', $is_approved, $question_id);
        $stmt->execute();
        $stmt->close();
        $feedback_message = 'Estado de aprobación actualizado.';
        $message_class = 'alert-info';
    }
}

// --- (El resto del archivo HTML para mostrar las preguntas se mantiene igual) ---
$query = "
    SELECT 
        q.id, q.question, q.answer, q.question_date, q.is_approved,
        p.title as plugin_title,
        u.name as user_name
    FROM 
        plugin_questions AS q
    JOIN 
        plugins AS p ON q.plugin_id = p.id
    JOIN 
        users AS u ON q.user_id = u.id
    ORDER BY 
        q.is_answered ASC, q.question_date DESC
";
$result = $mysqli->query($query);
$questions = $result->fetch_all(MYSQLI_ASSOC);

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

            <?php if ($feedback_message): ?>
                <div class="alert <?php echo $message_class; ?>"><?php echo $feedback_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($questions)): ?>
                        <p class="text-center text-muted">No hay preguntas de usuarios por el momento.</p>
                    <?php else: ?>
                        <div class="accordion" id="questionsAccordion">
                            <?php foreach ($questions as $q): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-<?php echo $q['id']; ?>">
                                        <button class="accordion-button <?php echo empty($q['answer']) ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $q['id']; ?>" aria-expanded="<?php echo empty($q['answer']) ? 'true' : 'false'; ?>">
                                            <span class="badge bg-<?php echo empty($q['answer']) ? 'warning text-dark' : 'success'; ?> me-2"><?php echo empty($q['answer']) ? 'Pendiente' : 'Respondida'; ?></span>
                                            <strong><?php echo htmlspecialchars($q['user_name']); ?></strong>&nbsp;preguntó sobre&nbsp;<em><?php echo htmlspecialchars($q['plugin_title']); ?></em>
                                        </button>
                                    </h2>
                                    <div id="collapse-<?php echo $q['id']; ?>" class="accordion-collapse collapse <?php echo empty($q['answer']) ? 'show' : ''; ?>" data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <p class="text-muted"><strong>Pregunta:</strong></p>
                                            <p><?php echo nl2br(htmlspecialchars($q['question'])); ?></p>
                                            <hr>
                                            <form method="POST" action="manage-questions.php">
                                                <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="answer-<?php echo $q['id']; ?>" class="form-label fw-bold">Tu Respuesta:</label>
                                                    <textarea name="answer_text" id="answer-<?php echo $q['id']; ?>" class="form-control" rows="4"><?php echo htmlspecialchars($q['answer'] ?? ''); ?></textarea>
                                                </div>
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" name="is_approved" id="approve-<?php echo $q['id']; ?>" value="1" <?php echo ($q['is_approved']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="approve-<?php echo $q['id']; ?>">Aprobar y mostrar públicamente esta pregunta y respuesta</label>
                                                </div>
                                                <button type="submit" name="answer_question" class="btn btn-primary">Guardar Respuesta</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>