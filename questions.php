<?php
// questions.php

require_once 'includes/config.php';

// 1. Validamos que tengamos un slug en la URL
if (!isset($_GET['slug']) || empty(trim($_GET['slug']))) {
    http_response_code(404);
    include 'includes/header.php';
    echo "<main class='container py-5 text-center'><h1>404 - No se especificó el plugin</h1></main>";
    include 'includes/footer.php';
    exit();
}
$slug = $_GET['slug'];

// 2. Buscamos el plugin para obtener su título e ID
$stmt = $mysqli->prepare("SELECT id, title FROM plugins WHERE slug = ? AND status = 'active' LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    include 'includes/header.php';
    echo "<main class='container py-5 text-center'><h1>404 - Plugin no encontrado</h1></main>";
    include 'includes/footer.php';
    $stmt->close();
    exit();
}
$plugin = $result->fetch_assoc();
$stmt->close();


// 3. OBTENER PREGUNTAS Y RESPUESTAS APROBADAS PARA ESTE PLUGIN
$qa_stmt = $mysqli->prepare("
    SELECT q.question, q.answer, q.question_date, q.answer_date, u.name as user_name
    FROM plugin_questions q
    JOIN users u ON q.user_id = u.id
    WHERE q.plugin_id = ? AND q.is_approved = 1
    ORDER BY q.question_date DESC
");
$qa_stmt->bind_param('i', $plugin['id']);
$qa_stmt->execute();
$questions_and_answers = $qa_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$qa_stmt->close();

// 4. Preparamos variables y cabecera
$page_title = 'Preguntas sobre ' . htmlspecialchars($plugin['title']);
include 'includes/header.php';
?>

<main class="container py-5 flex-shrink-0">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="mb-2">Preguntas y Respuestas</h1>
            <p class="lead text-muted mb-4">Sobre el plugin: <a href="<?php echo SITE_URL . '/plugin/' . $slug . '/'; ?>"><?php echo htmlspecialchars($plugin['title']); ?></a></p>

            <div id="question-feedback" class="mb-3"></div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="question-form">
                            <input type="hidden" name="plugin_id" value="<?php echo $plugin['id']; ?>">
                            <div class="mb-3">
                                <label for="question_text" class="form-label fw-bold">¿Tienes alguna otra pregunta?</label>
                                <textarea class="form-control" id="question_text" name="question_text" rows="3" required placeholder="Escribe tu pregunta aquí..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Enviar Pregunta</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Inicia sesión</a> para hacer una pregunta.
                </div>
            <?php endif; ?>

            <div class="questions-list mt-4">
                <?php if (empty($questions_and_answers)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Todavía no hay preguntas para este plugin. ¡Sé el primero en preguntar!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($questions_and_answers as $qa): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <p class="mb-1"><strong><i class="fas fa-question-circle text-primary me-2"></i><?php echo htmlspecialchars($qa['user_name']); ?></strong> <small class="text-muted float-end"><?php echo date('d/m/Y', strtotime($qa['question_date'])); ?></small></p>
                                <p class="ms-4"><?php echo nl2br(htmlspecialchars($qa['question'])); ?></p>
                                
                                <?php if (!empty($qa['answer'])): ?>
                                    <hr>
                                    <div class="bg-light p-3 rounded ms-4">
                                        <p class="mb-1"><strong><i class="fas fa-check-circle text-success me-2"></i>Respuesta del Administrador</strong> <small class="text-muted float-end"><?php echo date('d/m/Y', strtotime($qa['answer_date'])); ?></small></p>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($qa['answer'])); ?></p>
                                    </div>
                                <?php endif; ?>
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
    const questionForm = document.getElementById('question-form');
    if (questionForm) {
        questionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const feedbackDiv = document.getElementById('question-feedback');
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

            fetch('<?php echo SITE_URL; ?>/api/submit-question.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                let alertClass = data.success ? 'alert-success' : 'alert-danger';
                feedbackDiv.innerHTML = '<div class="alert ' + alertClass + '">' + data.message + '</div>';
                if (data.success) {
                    questionForm.reset();
                }
            })
            .catch(error => {
                feedbackDiv.innerHTML = '<div class="alert alert-danger">Ocurrió un error de conexión.</div>';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Enviar Pregunta';
                setTimeout(() => { feedbackDiv.innerHTML = ''; }, 6000);
            });
        });
    }
});
</script>

</body>
</html>