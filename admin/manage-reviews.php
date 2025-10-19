<?php
// admin/manage-reviews.php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Gestionar Reseñas y Valoraciones';

// --- LÓGICA PARA APROBAR/DESAPROBAR/ELIMINAR ---
if (isset($_GET['action'], $_GET['id'])) {
    $review_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'toggle_approval') {
        // Obtenemos el estado actual ANTES de cambiarlo
        $current_status_res = $mysqli->query("SELECT is_approved FROM plugin_reviews WHERE id = $review_id");
        $was_approved = $current_status_res->fetch_assoc()['is_approved'];
        
        // Cambia el estado de aprobación
        $new_status = 1 - $was_approved;
        $mysqli->query("UPDATE plugin_reviews SET is_approved = $new_status WHERE id = $review_id");

        // --- NUEVO: NOTIFICAR AL USUARIO SI LA RESEÑA FUE APROBADA ---
        if ($new_status == 1) { // Si el nuevo estado es "Aprobado"
            $query = "
                SELECT u.name, u.email, u.whatsapp_number, p.title as plugin_title, p.slug as plugin_slug
                FROM plugin_reviews r
                JOIN users u ON r.user_id = u.id
                JOIN plugins p ON r.plugin_id = p.id
                WHERE r.id = ?
            ";
            $notif_stmt = $mysqli->prepare($query);
            $notif_stmt->bind_param('i', $review_id);
            $notif_stmt->execute();
            $data = $notif_stmt->get_result()->fetch_assoc();
            $notif_stmt->close();

            if ($data) {
                $review_url = SITE_URL . '/reviews.php?slug=' . $data['plugin_slug'];
                if (!empty($data['whatsapp_number'])) {
                    $wa_message = "👍 ¡Hola, {$data['name']}! Tu reseña sobre *'{$data['plugin_title']}'* ha sido aprobada y ya está visible para todos.\n\n¡Muchas gracias por tu opinión!\n{$review_url}";
                    sendWhatsAppNotification($data['whatsapp_number'], $wa_message, $app_settings);
                }
                if (!empty($data['email'])) {
                    $email_subject = "Tu reseña para '{$data['plugin_title']}' ha sido publicada";
                    $email_body = "Hola {$data['name']},<br><br>Te informamos que tu reseña sobre el plugin <strong>'{$data['plugin_title']}'</strong> ha sido aprobada y ya es pública.<br><br>Agradecemos mucho tu tiempo y tu valoración.<br><br>Puedes verla aquí:<br><a href='{$review_url}'>Ver Reseñas</a>";
                    sendSMTPMail($data['email'], $data['name'], $email_subject, $email_body, $app_settings);
                }
            }
        }
        // --- FIN DE LA NOTIFICACIÓN ---

    } elseif ($action === 'delete') {
        $mysqli->query("DELETE FROM plugin_reviews WHERE id = $review_id");
    }
    
    header('Location: manage-reviews.php');
    exit();
}

// --- (El resto del archivo HTML para mostrar las reseñas se mantiene igual) ---
$query = "
    SELECT 
        r.id, r.rating, r.review_title, r.review_text, r.review_date, r.is_approved,
        p.title as plugin_title,
        COALESCE(u.name, 'Anónimo (OTP)') as user_name
    FROM 
        plugin_reviews AS r
    JOIN 
        plugins AS p ON r.plugin_id = p.id
    LEFT JOIN 
        users AS u ON r.user_id = u.id
    ORDER BY 
        r.review_date DESC
";
$result = $mysqli->query($query);
$reviews = $result->fetch_all(MYSQLI_ASSOC);

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

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Plugin</th>
                                    <th>Usuario</th>
                                    <th>Calificación</th>
                                    <th>Reseña</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reviews)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No hay reseñas por el momento.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reviews as $review): ?>
                                        <tr>
                                            <td><em><?php echo htmlspecialchars($review['plugin_title']); ?></em></td>
                                            <td><strong><?php echo htmlspecialchars($review['user_name']); ?></strong></td>
                                            <td>
                                                <span class="text-warning">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="<?php echo ($i <= $review['rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($review['review_title']); ?></strong>
                                                <p class="small mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($review['review_date'])); ?></td>
                                            <td>
                                                <?php if ($review['is_approved']): ?>
                                                    <span class="badge bg-success">Aprobada</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?action=toggle_approval&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-<?php echo $review['is_approved'] ? 'secondary' : 'success'; ?>" title="<?php echo $review['is_approved'] ? 'Desaprobar' : 'Aprobar'; ?>">
                                                    <i class="fas fa-<?php echo $review['is_approved'] ? 'times-circle' : 'check-circle'; ?>"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar esta reseña?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>