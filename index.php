<?php
// 1. Incluimos la configuración para conectar a la DB y cargar los ajustes globales
require_once 'includes/config.php';

// 2. Consultamos la base de datos para obtener solo los plugins ACTIVOS
$result = $mysqli->query("SELECT * FROM plugins WHERE status = 'active' ORDER BY id DESC");
$plugins = $result->fetch_all(MYSQLI_ASSOC);

// 3. Definimos las variables de SEO para esta página, que serán usadas por el header.php
$site_name = htmlspecialchars($app_settings['site_name'] ?? 'Mi Sitio de Plugins');
$page_title = $site_name . ' - Descarga de Plugins Premium y Gratuitos';
$meta_description = htmlspecialchars($app_settings['seo_meta_description'] ?? 'Descarga los mejores plugins de forma segura.');
$meta_keywords = htmlspecialchars($app_settings['seo_meta_keywords'] ?? 'plugins, descargas, gratis');

// 4. Incluimos la cabecera completa del sitio
include 'includes/header.php';
?>

<section class="hero-section text-center text-white">
    <div class="container">
        <h1 class="hero-title">Plugins Premium. Simples. Seguros.</h1>
        <p class="hero-subtitle lead">Descarga los mejores plugins con verificación segura vía WhatsApp.</p>
        <a href="#plugins" class="btn btn-primary btn-lg btn-gradient"><i class="fas fa-download me-2"></i> Explorar Plugins</a>
    </div>
</section>

<main class="flex-shrink-0">
    <section id="plugins" class="plugins-section py-5">
        <div class="container">
            <h2 class="text-center mb-5">Plugins Disponibles</h2>
            <div class="row">
                <?php if (count($plugins) > 0): ?>
                    <?php foreach ($plugins as $plugin): ?>
                        <div class="col-md-6 col-lg-4 mb-4 d-flex align-items-stretch">
                            <div class="plugin-card">
                                <div class="plugin-image">
                                    <a href="plugin/<?php echo htmlspecialchars($plugin['slug']); ?>/">
                                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($plugin['image'] ? $plugin['image'] : 'assets/images/plugins/default.png'); ?>" alt="<?php echo htmlspecialchars($plugin['title']); ?>">
                                    </a>
                                </div>
                                <div class="plugin-content">
                                    <h5 class="plugin-title">
                                        <a href="plugin/<?php echo htmlspecialchars($plugin['slug']); ?>/" class="text-dark text-decoration-none"><?php echo htmlspecialchars($plugin['title']); ?></a>
                                    </h5>
                                    <p class="plugin-description"><?php echo htmlspecialchars($plugin['short_description']); ?></p>
                                    <div class="plugin-meta">
                                        <span title="Versión"><i class="fas fa-code-branch"></i> v<?php echo htmlspecialchars($plugin['version'] ?? '1.0'); ?></span>
                                        
                                        <?php if (isset($plugin['price']) && $plugin['price'] > 0): ?>
                                            <span class="plugin-price text-success fw-bold">
                                                $<?php echo number_format($plugin['price'], 2); ?> USD
                                            </span>
                                        <?php else: ?>
                                            <span class="plugin-price text-primary fw-bold">
                                                Gratis
                                            </span>
                                        <?php endif; ?>

                                    </div>
                                    <a href="plugin/<?php echo htmlspecialchars($plugin['slug']); ?>/" class="btn btn-outline-primary w-100 mt-auto">
                                        Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-center text-muted">No hay plugins disponibles en este momento. Vuelve pronto.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php 
// Incluimos el footer, que se encarga de los scripts comunes
include 'includes/footer.php'; 

// Incluimos los modales que esta página pueda necesitar (aunque no se activen directamente desde aquí)
include 'includes/modal_otp.php'; 
include 'includes/modal_video.php';
?>
</body>
</html>