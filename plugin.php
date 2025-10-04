<?php
require_once 'includes/config.php';

// 1. Validamos que tengamos un slug en la URL
if (!isset($_GET['slug']) || empty(trim($_GET['slug']))) {
    http_response_code(404);
    $page_title = "Página no encontrada";
    include 'includes/header.php';
    echo "<main class='container py-5 text-center'><h1>404 - Página no encontrada</h1><p>Falta el identificador del plugin.</p><a href='" . SITE_URL . "' class='btn btn-primary'>Volver al inicio</a></main>";
    include 'includes/footer.php';
    exit();
}
$slug = $_GET['slug'];

// 2. Buscamos el plugin en la base de datos de forma segura
$stmt = $mysqli->prepare("SELECT * FROM plugins WHERE slug = ? AND status = 'active' LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    $page_title = "Plugin no encontrado";
    include 'includes/header.php';
    echo "<main class='container py-5 text-center'><h1>404 - Plugin no encontrado</h1><p>El plugin que buscas no existe o fue movido.</p><a href='" . SITE_URL . "' class='btn btn-primary'>Volver al inicio</a></main>";
    include 'includes/footer.php';
    $stmt->close();
    exit();
}
$plugin = $result->fetch_assoc();
$stmt->close();

// 3. Preparamos todas las variables que usaremos en la página
$site_name = htmlspecialchars($app_settings['site_name'] ?? 'PluginHub');
$page_title = !empty($plugin['seo_title']) ? $plugin['seo_title'] : $plugin['title'] . ' - ' . $site_name;
$meta_description = !empty($plugin['seo_meta_description']) ? $plugin['seo_meta_description'] : mb_substr(strip_tags($plugin['short_description']), 0, 155);

$is_paid_plugin = isset($plugin['price']) && $plugin['price'] > 0.00;
$paypal_client_id = $app_settings['paypal_client_id'] ?? '';

$gallery_images = !empty($plugin['gallery_images']) ? json_decode($plugin['gallery_images'], true) : [];
if (!empty($plugin['image'])) {
    array_unshift($gallery_images, $plugin['image']);
}
$gallery_images = array_unique(array_filter($gallery_images));

$video_embed_url = !empty($plugin['video_url']) ? get_youtube_embed_url($plugin['video_url']) : '';


// 4. Incluimos la cabecera completa del sitio
include 'includes/header.php';
?>

<main class="container py-5 flex-shrink-0">
    <div class="row">
        <div class="col-lg-8">
            <article>
                <h1 class="mb-3"><?php echo htmlspecialchars($plugin['title']); ?></h1>
                
                <?php if(!empty($gallery_images)): ?>
                <div id="pluginImageCarousel" class="carousel slide shadow-sm rounded mb-4" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <?php foreach ($gallery_images as $i => $image): ?>
                        <button type="button" data-bs-target="#pluginImageCarousel" data-bs-slide-to="<?php echo $i; ?>" class="<?php echo ($i == 0) ? 'active' : ''; ?>" aria-current="<?php echo ($i == 0) ? 'true' : 'false'; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner rounded">
                        <?php foreach ($gallery_images as $i => $image): ?>
                        <div class="carousel-item <?php echo ($i == 0) ? 'active' : ''; ?>">
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($image); ?>" class="d-block w-100" style="aspect-ratio: 16/9; object-fit: cover;" alt="Imagen de galería <?php echo $i+1; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($gallery_images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#pluginImageCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#pluginImageCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="plugin-full-description mt-4">
                    <h3>Descripción</h3>
                    <hr>
                    <?php echo $plugin['full_description']; // Mostramos el HTML del editor WYSIWYG ?>
                </div>
            </article>
        </div>

        <aside class="col-lg-4">
            <div class="card sticky-top" style="top: 100px;">
                <div class="card-body text-center">
                    
                    <?php if ($is_paid_plugin): ?>
                        <div class="mb-3">
                            <span class="fs-2 fw-bold">$<?php echo number_format($plugin['price'], 2); ?></span>
                            <span class="text-muted">USD</span>
                        </div>
                        <div class="d-grid">
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="fas fa-shopping-cart me-2"></i>Comprar Ahora
                            </button>
                        </div>
                    <?php else: ?>
                        <h4 class="text-success fw-bold">¡Gratis!</h4>
                        <div class="d-grid mt-3">
                            <button class="btn btn-primary btn-lg btn-download" data-plugin-id="<?php echo $plugin['id']; ?>"><i class="fas fa-download me-2"></i>Descargar Ahora</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($video_embed_url): ?>
                        <div class="d-grid mt-2">
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#videoModal" data-video-src="<?php echo $video_embed_url; ?>"><i class="fab fa-youtube me-2"></i>Ver Video</button>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <h5 class="card-title">Detalles</h5>
                    <ul class="list-group list-group-flush text-start">
                        <li class="list-group-item d-flex justify-content-between"><b>Versión:</b> <span><?php echo htmlspecialchars($plugin['version'] ?? 'N/A'); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><b>Descargas:</b> <span><?php echo number_format($plugin['download_count']); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><b>Actualizado:</b> <span><?php echo date('d/m/Y', strtotime($plugin['updated_at'])); ?></span></li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/modal_otp.php'; ?>
<?php include 'includes/modal_video.php'; ?>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Elige tu método de pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-center text-muted">Serás redirigido a una plataforma segura para completar tu pago.</p>
                <div id="paypal-button-container" class="mb-3">
                    <?php if (empty($paypal_client_id)): ?>
                        <div class="alert alert-secondary text-center small">Pago con PayPal no disponible.</div>
                    <?php endif; ?>
                </div>
                <div class="d-grid">
                    <a href="<?php echo SITE_URL; ?>/create_mp_preference.php?id=<?php echo $plugin['id']; ?>" class="btn btn-info">
                        Pagar con <img src="<?php echo SITE_URL; ?>/assets/images/mercado-pago-logo.png" style="height: 20px; vertical-align: middle;">
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($is_paid_plugin && !empty($paypal_client_id)): ?>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_client_id; ?>&currency=USD&intent=capture"></script>
    <script>
    if (document.getElementById('paypal-button-container')) {
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        description: '<?php echo addslashes(htmlspecialchars($plugin['title'])); ?>',
                        amount: { value: '<?php echo $plugin['price']; ?>' }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    let container = document.getElementById('paypal-button-container');
                    container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Verificando pago...</p></div>';
                    fetch('<?php echo SITE_URL; ?>/api/complete_paypal_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ orderID: data.orderID, pluginID: <?php echo $plugin['id']; ?> })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            container.innerHTML = '<div class="alert alert-success">¡Pago verificado! Tu descarga está disponible en tu cuenta.</div>';
                        } else {
                            container.innerHTML = '<div class="alert alert-danger">Error: ' + (result.message || 'No se pudo verificar el pago.') + '</div>';
                        }
                    });
                });
            },
            onError: function (err) {
                console.error('Ocurrió un error con el pago de PayPal:', err);
                alert('Ocurrió un error al procesar tu pago. Por favor, inténtalo de nuevo.');
            }
        }).render('#paypal-button-container');
    }
    </script>
<?php endif; ?>

</body>
</html>