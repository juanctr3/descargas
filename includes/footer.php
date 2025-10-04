<?php
$site_name_footer = htmlspecialchars($app_settings['site_name'] ?? 'PluginHub');
$footer_links_result = $mysqli->query("SELECT * FROM menus WHERE link_location = 'footer' ORDER BY link_order ASC, id ASC");
$footer_links = $footer_links_result->fetch_all(MYSQLI_ASSOC);
?>
<footer class="text-center py-4 bg-dark text-white mt-auto">
    <div class="container">
        <?php if (count($footer_links) > 0): ?>
            <div class="footer-nav mb-2">
                <?php $links_array = []; foreach ($footer_links as $link) {
                    $icon_html = !empty($link['icon_class']) ? '<i class="' . htmlspecialchars($link['icon_class']) . ' me-1"></i>' : '';
                    $target = $link['open_in_new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : '';
                    $links_array[] = '<a href="' . htmlspecialchars($link['link_url']) . '" class="text-white-50 px-2"' . $target . '>' . $icon_html . htmlspecialchars($link['link_text']) . '</a>';
                } echo implode(' &middot; ', $links_array); ?>
            </div>
        <?php endif; ?>
        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $site_name_footer; ?>. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js"></script>

<script>
    const SITE_URL = "<?php echo SITE_URL; ?>";
</script>

<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>