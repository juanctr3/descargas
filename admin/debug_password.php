<?php

echo '<h2>Iniciando Diagnóstico de Contraseña</h2>';

// 1. Incluimos la configuración para conectar a la DB
require_once __DIR__ . '/../includes/config.php';
echo '<p><strong>Estado de Conexión:</strong> Se incluyó config.php correctamente.</p>';
echo '<p><strong>Versión de PHP:</strong> ' . phpversion() . '</p><hr>';

// 2. Definimos los datos que queremos probar
$username_a_probar = 'admin';
$password_a_probar = 'admin123';

echo "<p><strong>Probando con:</strong></p>";
echo "<ul><li>Usuario: <code>{$username_a_probar}</code></li><li>Contraseña: <code>{$password_a_probar}</code></li></ul><hr>";

// 3. Buscamos al usuario en la base de datos
$stmt = $mysqli->prepare("SELECT id, password FROM admins WHERE username = ?");
$stmt->bind_param('s', $username_a_probar);
$stmt->execute();
$result = $stmt->get_result();

echo '<h3>Paso 1: Búsqueda del Usuario</h3>';
echo '<p>Filas encontradas para el usuario \'admin\': ';
var_dump($result->num_rows); // Esto debería imprimir int(1)
echo '</p>';

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    echo '<h3>Paso 2: Datos Recuperados de la Base de Datos</h3>';
    echo '<p>Array completo del usuario recuperado:</p>';
    var_dump($admin); // Muestra el array completo
    
    echo '<p>Hash de la contraseña recuperada de la DB:</p>';
    $hash_de_db = $admin['password'];
    echo "<code>{$hash_de_db}</code>";
    
    echo '<h3>Paso 3: Verificación con password_verify()</h3>';
    $es_correcta = password_verify($password_a_probar, $hash_de_db);
    
    echo '<p>Resultado de password_verify(): ';
    var_dump($es_correcta); // Esto debería imprimir bool(true)
    echo '</p><hr>';

    if ($es_correcta) {
        echo '<h2 style="color: green;">¡ÉXITO! La contraseña y el hash coinciden. El login debería funcionar.</h2>';
    } else {
        echo '<h2 style="color: red;">¡FALLO! La contraseña NO coincide con el hash. Este es el problema.</h2>';
    }

} else {
    echo '<h2 style="color: red;">¡FALLO CRÍTICO! No se encontró al usuario \'admin\' en la base de datos.</h2>';
}

$stmt->close();
$mysqli->close();

?>