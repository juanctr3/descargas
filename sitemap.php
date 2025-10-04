<?php
// Incluimos la configuración para acceder a la base de datos y a la URL del sitio
require_once 'includes/config.php';

// Le decimos al navegador que este archivo es de tipo XML, no HTML
header("Content-Type: application/xml; charset=utf-8");

// Imprimimos la cabecera del archivo XML
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// 1. Añadimos la URL de la página de inicio
echo '<url>';
echo '  <loc>' . SITE_URL . '/</loc>';
echo '  <lastmod>' . date('Y-m-d') . '</lastmod>'; // La fecha de hoy
echo '  <changefreq>daily</changefreq>';
echo '  <priority>1.0</priority>';
echo '</url>';

// 2. Añadimos las URLs de cada plugin activo
$result = $mysqli->query("SELECT slug, updated_at FROM plugins WHERE status = 'active' ORDER BY updated_at DESC");
if ($result && $result->num_rows > 0) {
    while ($plugin = $result->fetch_assoc()) {
        echo '<url>';
        // Construimos la URL completa de la página de detalle del plugin
        echo '  <loc>' . SITE_URL . '/plugin.php?slug=' . htmlspecialchars($plugin['slug']) . '</loc>';
        // Usamos la fecha de última actualización del plugin
        echo '  <lastmod>' . date('Y-m-d', strtotime($plugin['updated_at'])) . '</lastmod>';
        echo '  <changefreq>weekly</changefreq>';
        echo '  <priority>0.9</priority>';
        echo '</url>';
    }
}

// Cerramos la etiqueta principal del sitemap
echo '</urlset>';
?>