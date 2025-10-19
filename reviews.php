<?php
// reviews.php

require_once 'includes/config.php';

// 1. Validar slug del plugin
if (!isset($_GET['slug']) || empty(trim($_GET['slug']))) {
    http_response_code(404); die('Plugin no especificado.');
}
$slug = $_GET['slug'];

// 2. Buscar el plugin
$stmt = $mysqli->prepare("SELECT id, title FROM plugins WHERE slug = ? AND status = 'active' LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$plugin_result = $stmt->get_result();
if ($plugin_result->num_rows === 0) {
    http_response_code(404); die('Plugin no encontrado.');
}
$plugin = $plugin_result->fetch_assoc();
$stmt->close();

// 3. Obtener todas las reseñas APROBADAS para este plugin
$reviews_stmt = $mysqli->prepare("
    SELECT r.rating, r.review_title, r.review_text, r.review_date, COALESCE(u.name, 'Anónimo') as user_name
    FROM plugin_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.plugin_id = ? AND r.is_approved = 1
    ORDER BY r.review_date DESC
");
$reviews_stmt->bind_param('i', $plugin['id']);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reviews_stmt->close();

// 4. Calcular el promedio de calificación y el desglose
$total_reviews = count($reviews);
$average_rating = 0;
$rating_breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
if ($total_reviews > 0) {
    $total_rating_sum = 0;
    foreach ($reviews as $review) {
        $total_rating_sum += $review['rating'];
        $rating_breakdown[$review['rating']]++;
    }
    $average_rating = round($total_rating_sum / $total_reviews, 1);
}

// 5. Verificar si el usuario actual puede dejar una reseña
$can_review = false;
$user_has_reviewed = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // ¿Ha descargado el plugin?
    $check_download_stmt = $mysqli->prepare("SELECT id FROM downloads WHERE user_id = ? AND plugin_id = ? LIMIT 1");
    $check_download_stmt->bind_param('ii', $user_id, $plugin['id']);
    $check_download_stmt->execute();
    if ($check_download_stmt->get_result()->num_rows > 0) {
        $can_review = true;
    }
    $check_download_stmt->close();

    // ¿Ya ha dejado una reseña?
    $check_review_stmt = $mysqli->prepare("SELECT id FROM plugin_reviews WHERE user_id = ? AND plugin_id = ? LIMIT 1");
    $check_review_stmt->bind_param('ii', $user_id, $plugin['id']);
    $check_review_stmt->execute();
    if ($check_review_stmt->get_result()->num_rows > 0) {
        $user_has_reviewed = true;
    }
    $check_review_stmt->close();
}


// 6. Preparar cabecera y página
$page_title = 'Reseñas de ' . htmlspecialchars($plugin['title']);
include 'includes/header.php';
?>
<style>
    .rating-stars .fa-star { font-size: 1.5rem; color: #ffc107; cursor: pointer; }
    .rating-stars .fa-star:hover, .rating-stars .fa-star.selected { color: #ffc107; }
    .rating-stars .fa-star.far { color: #e4e5e9; }
</style>

<main class="container py-5 flex-shrink-0">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="mb-2">Reseñas de Usuarios</h1>
            <p class="lead text-muted mb-4">Sobre el plugin: <a href="<?php echo SITE_URL . '/plugin/' . $slug . '/'; ?>"><?php echo htmlspecialchars($plugin['title']); ?></a></p>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center">
                            <h1 class="display-3 fw-bold"><?php echo $average_rating; ?></h1>
                            <div class="text-warning mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo ($i <= $average_rating) ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-muted">Basado en <?php echo $total_reviews; ?> reseñas</p>
                        </div>
                        <div class="col-md-8">
                            <?php for ($i = 5; $i >= 1; $i--): 
                                $percentage = ($total_reviews > 0) ? ($rating_breakdown[$i] / $total_reviews) * 100 : 0;
                            ?>
                            <div class="d-flex align-items-center mb-1">
                                <div class="text-nowrap me-2"><?php echo $i; ?> estrellas</div>
                                <div class="progress flex-grow-1" style="height: 10px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="text-nowrap ms-2" style="width: 40px; text-align: right;"><?php echo $rating_breakdown[$i]; ?></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="review-feedback" class="mb-3"></div>
            <?php if ($can_review && !$user_has_reviewed): ?>
            <div class="card mb-4" id="review-form-card">
                <div class="card-body">
                    <h5 class="card-title">Escribe tu reseña</h5>
                    <form id="review-form">
                        <input type="hidden" name="plugin_id" value="<?php echo $plugin['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Tu calificación:</label>
                            <div class="rating-stars">
                                <i class="far fa-star" data-rating="1"></i>
                                <i class="far fa-star" data-rating="2"></i>
                                <i class="far fa-star" data-rating="3"></i>
                                <i class="far fa-star" data-rating="4"></i>
                                <i class="far fa-star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="rating-value" value="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="review_title" class="form-label">Título de tu reseña</label>
                            <input type="text" class="form-control" name="review_title" id="review_title">
                        </div>
                        <div class="mb-3">
                            <label for="review_text" class="form-label">Tu reseña (opcional)</label>
                            <textarea class="form-control" name="review_text" id="review_text" rows="4"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar Reseña</button>
                    </form>
                </div>
            </div>
            <?php elseif (!$can_review && !isset($_SESSION['user_id'])): ?>
                 <div class="alert alert-info">Debes <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">iniciar sesión</a> y haber descargado el plugin para poder calificarlo.</div>
            <?php elseif ($user_has_reviewed): ?>
                <div class="alert alert-success">¡Gracias! Ya has dejado tu reseña para este plugin.</div>
            <?php endif; ?>


            <div class="reviews-list mt-4">
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Aún no hay reseñas para este plugin. Si lo has descargado, ¡sé el primero en calificarlo!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?php echo ($i <= $review['rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($review['review_date'])); ?></small>
                            </div>
                            <h5 class="mt-2"><?php echo htmlspecialchars($review['review_title']); ?></h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lógica para las estrellas de calificación
    const stars = document.querySelectorAll('.rating-stars .fa-star');
    const ratingInput = document.getElementById('rating-value');

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            ratingInput.value = rating;
            stars.forEach(s => {
                s.classList.remove('fas', 'selected');
                s.classList.add('far');
                if (s.dataset.rating <= rating) {
                    s.classList.remove('far');
                    s.classList.add('fas', 'selected');
                }
            });
        });
    });

    // Lógica para el envío del formulario con AJAX
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const feedbackDiv = document.getElementById('review-feedback');
            const submitButton = this.querySelector('button[type="submit"]');

            if (ratingInput.value == 0) {
                feedbackDiv.innerHTML = '<div class="alert alert-warning">Por favor, selecciona una calificación.</div>';
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Publicando...';

            fetch('<?php echo SITE_URL; ?>/api/submit-review.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                let alertClass = data.success ? 'alert-success' : 'alert-danger';
                feedbackDiv.innerHTML = '<div class="alert ' + alertClass + '">' + data.message + '</div>';
                if (data.success) {
                    document.getElementById('review-form-card').style.display = 'none';
                }
            })
            .catch(error => {
                feedbackDiv.innerHTML = '<div class="alert alert-danger">Ocurrió un error de conexión.</div>';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Enviar Reseña';
            });
        });
    }
});
</script>

</body>
</html>