<?php
// Activamos los errores para ver todo claramente
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Prueba de Envío de WhatsApp</h1>";

// 1. Cargamos la configuración y las funciones
require_once 'includes/config.php';
echo "<p><strong>Paso 1:</strong> Archivos de configuración y funciones cargados.</p>";

// 2. Definimos el destinatario y el mensaje de prueba
//    vvv CAMBIA ESTE NÚMERO POR TU NÚMERO DE WHATSAPP CON CÓDIGO DE PAÍS vvv
$test_recipient = '573001234567'; 
$test_message = 'Esta es una prueba de conexión con la API de SmSenlinea desde el sitio web.';

echo "<p>Intentando enviar un mensaje de prueba a: <strong>" . htmlspecialchars($test_recipient) . "</strong></p>";

// 3. Verificamos las credenciales que se cargaron desde la base de datos
echo "<h3>Verificando credenciales leídas desde el panel de Ajustes:</h3>";
echo "<ul>";
echo "<li>API Secret: <code>" . htmlspecialchars($app_settings['smsenlinea_secret'] ?? 'NO ENCONTRADO') . "</code></li>";
echo "<li>Account ID: <code>" . htmlspecialchars($app_settings['smsenlinea_account'] ?? 'NO ENCONTRADO') . "</code></li>";
echo "</ul><hr>";

// 4. Llamamos a la función de envío de WhatsApp
// Usamos la variable $app_settings que se carga globalmente desde config.php
$resultado = sendWhatsAppNotification($test_recipient, $test_message, $app_settings);

// 5. Mostramos el resultado
if ($resultado === true) {
    echo '<h2 style="color: green;">¡ÉXITO! El mensaje de prueba fue enviado correctamente a la API.</h2>';
    echo '<p>Esto significa que tus credenciales en la base de datos son correctas y la conexión con la API funciona. El problema debe estar en cómo el formulario de descarga se comunica con los scripts.</p>';
} else {
    echo '<h2 style="color: red;">¡FALLO! No se pudo enviar el mensaje.</h2>';
    echo '<p>Esto indica un problema con las credenciales que tienes guardadas en el panel de "Ajustes" o con la conexión a la API de SmSenlinea. Por favor, ve a tu panel de administración, a la sección de Ajustes, y verifica que las credenciales de la API de WhatsApp sean exactamente las correctas y no tengan espacios extra al principio o al final.</p>';
}
?>