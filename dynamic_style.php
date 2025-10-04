<?php
// 1. Le decimos al navegador que este archivo es CSS, no HTML.
header("Content-Type: text/css; charset=utf-8");

// 2. Cargamos nuestra configuración para acceder a los ajustes de la base de datos.
require_once 'includes/config.php';

// 3. Usamos la sintaxis de PHP para "imprimir" código CSS.
// Definimos las variables de color de CSS con los valores de nuestros ajustes.
?>
:root {
    --color-primary: <?php echo htmlspecialchars($app_settings['color_primary'] ?? '#667eea'); ?>;
    --color-secondary: <?php echo htmlspecialchars($app_settings['color_secondary'] ?? '#764ba2'); ?>;
    --color-accent: <?php echo htmlspecialchars($app_settings['color_accent'] ?? '#4facfe'); ?>;
    --color-dark: <?php echo htmlspecialchars($app_settings['color_dark'] ?? '#1a202c'); ?>;
    --color-light: <?php echo htmlspecialchars($app_settings['color_light'] ?? '#f7fafc'); ?>;
}

/* Ahora podemos usar estas variables en nuestro CSS.
  Cualquier cambio de color en el panel de admin se reflejará aquí automáticamente.
*/

.header-modern {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
}

.hero-section {
    background: linear-gradient(135deg, rgba(44, 62, 80, 0.9), rgba(52, 73, 94, 0.9)), url('https://www.toptal.com/designers/subtlepatterns/uploads/double-bubble-outline.png');
    /* Podríamos usar nuestros colores aquí también si quisiéramos */
}

.btn-gradient {
    background-image: linear-gradient(to right, var(--color-primary) 0%, var(--color-accent) 51%, var(--color-primary) 100%);
}

/* Puedes añadir más reglas aquí que usen tus colores dinámicos */