<?php
// Usamos las variables globales que se cargan en config.php ($mysqli, $app_settings, SITE_URL)

// --- PREPARACIÓN DE VARIABLES PARA EL HEADER ---

$site_name_header = htmlspecialchars($app_settings['site_name'] ?? 'PluginHub');
$logo_path = $app_settings['site_logo_path'] ?? '';
$favicon_path = $app_settings['site_favicon_path'] ?? '';

// La página que incluye este archivo (ej. index.php) debe definir $page_title y $meta_description.
// Si no están definidas, usamos los valores por defecto de los ajustes generales.
$final_page_title = isset($page_title) ? htmlspecialchars($page_title) . ' - ' . $site_name_header : $site_name_header;
$final_meta_description = isset($meta_description) ? htmlspecialchars($meta_description) : htmlspecialchars($app_settings['seo_meta_description'] ?? 'Descarga de plugins y recursos.');

// Obtenemos los enlaces del menú para el encabezado desde la base de datos
$header_links_result = $mysqli->query("SELECT * FROM menus WHERE link_location = 'header' ORDER BY link_order ASC, id ASC");
$header_links = $header_links_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $final_page_title; ?></title>
    <meta name="description" content="<?php echo $final_meta_description; ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($app_settings['seo_meta_keywords'] ?? ''); ?>">

    <meta property="og:title" content="<?php echo $final_page_title; ?>">
    <meta property="og:description" content="<?php echo $final_meta_description; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:site_name" content="<?php echo $site_name_header; ?>">
    <meta property="og:image" content="<?php echo SITE_URL . '/' . ($logo_path ?: 'assets/images/plugins/default.png'); ?>">
    
    <?php if (!empty($favicon_path)): ?>
        <link rel="icon" href="<?php echo SITE_URL . '/' . htmlspecialchars($favicon_path); ?>">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/dynamic_style.php" rel="stylesheet">
    
    <?php
    if (!empty($app_settings['google_analytics_code'])) {
        // Imprimimos el código tal cual lo pega el usuario
        echo $app_settings['google_analytics_code'];
    }
    ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="header-modern sticky-top">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>/">
                    <?php if (!empty($logo_path)): ?>
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($logo_path); ?>?v=<?php echo time(); ?>" alt="<?php echo $site_name_header; ?> Logo" style="max-height: 40px;">
                    <?php else: ?>
                        <i class="fas fa-plug me-2"></i><?php echo $site_name_header; ?>
                    <?php endif; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto">
                        <?php
                        // Mostramos los enlaces dinámicos que creaste en el panel
                        foreach ($header_links as $link):
                        ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo htmlspecialchars($link['link_url']); ?>" <?php if ($link['open_in_new_tab']) { echo ' target="_blank" rel="noopener noreferrer"'; } ?>>
                                    <?php if (!empty($link['icon_class'])): ?><i class="<?php echo htmlspecialchars($link['icon_class']); ?> me-2"></i><?php endif; ?>
                                    <?php echo htmlspecialchars($link['link_text']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/my-account.php">Mi Cuenta</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">Iniciar Sesión</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">Registrarse</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>