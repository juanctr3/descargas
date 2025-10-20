<?php
// includes/footer.php
?>

<footer class="footer mt-auto py-3 bg-light border-top">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-3 mb-lg-0 text-center text-lg-start">
                <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($app_settings['site_name'] ?? 'PluginStore'); ?>. Todos los derechos reservados.</p>
            </div>

            <div class="col-lg-8 text-center text-lg-end">
                <?php
                // Cargar menús del footer si existen
                $footer_menus_query = $mysqli->query("SELECT link_text, link_url, open_in_new_tab FROM menus WHERE link_location = 'footer' ORDER BY link_order ASC");
                if ($footer_menus_query && $footer_menus_query->num_rows > 0) {
                    while ($item = $footer_menus_query->fetch_assoc()) {
                        $target_attr = !empty($item['open_in_new_tab']) ? ' target="_blank"' : '';
                        echo '<a href="' . htmlspecialchars($item['link_url']) . '" class="text-muted me-3"' . $target_attr . '>' . htmlspecialchars($item['link_text']) . '</a>';
                    }
                }
                
                // --- NUEVO: El enlace solo se muestra si el usuario ha iniciado sesión ---
                if (isset($_SESSION['user_id'])) {
                    echo '<a href="' . SITE_URL . '/api-documentation.php" class="text-muted me-3">Documentación para Desarrolladores</a>';
                }
                ?>
            </div>
        </div>
    </div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js"></script>

<script>
    const SITE_URL = '<?php echo SITE_URL; ?>';
</script>

<script src="<?php echo SITE_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>

</body>
</html>