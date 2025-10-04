<?php
// Activamos la visualización de errores solo para este script, es muy útil para depurar.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<h1>Diagnóstico de Rutas de Archivos</h1>';
echo '<p>Este script comprueba si el servidor puede encontrar el archivo principal de la librería PHPMailer desde la ubicación de <code>functions.php</code>.</p>';

// Esta es la ruta exacta que nuestro script 'functions.php' está tratando de encontrar.
// __DIR__ es una constante mágica de PHP que nos da la ruta de la carpeta actual (en este caso, 'includes').
$path_to_check = __DIR__ . '/../libs/PHPMailer/PHPMailer.php';

echo '<p>Ruta completa que se está verificando:</p>';
// Usamos una caja de texto para que puedas copiar la ruta fácilmente si es necesario.
echo '<textarea rows="2" style="width: 100%; font-family: monospace;">' . htmlspecialchars($path_to_check) . '</textarea>';

echo '<hr>';

// is_readable() es una función que comprueba si un archivo existe Y si el servidor tiene permisos para leerlo.
if (is_readable($path_to_check)) {
    echo '<h2 style="color: green;">¡ÉXITO! El archivo fue encontrado y se puede leer.</h2>';
    echo '<p>Esto significa que la ruta y los permisos son correctos. El error 500 podría deberse a un error de sintaxis dentro del archivo <code>functions.php</code>. En ese caso, reemplazar el contenido de ese archivo de nuevo debería solucionarlo.</p>';
} else {
    echo '<h2 style="color: red;">¡FALLO! El archivo NO fue encontrado o NO se puede leer en esa ruta.</h2>';
    echo '<p>Esto confirma que el problema es la ruta o los permisos del archivo/carpetas. Por favor, verifica de nuevo el diagrama que te di en el mensaje anterior y asegúrate de que todo coincida al 100%:</p>';
    echo '<ul>';
    echo '<li>Que la carpeta <code>libs</code> exista en la raíz del proyecto.</li>';
    echo '<li>Que la carpeta <code>PHPMailer</code> (con M mayúscula) exista dentro de <code>libs</code>.</li>';
    echo '<li>Que el archivo <code>PHPMailer.php</code> (con M mayúscula) exista dentro de <code>libs/PHPMailer</code>.</li>';
    echo '</ul>';
}
?>