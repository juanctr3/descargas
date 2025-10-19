<?php
// 1. Proteger la página. Solo los admins pueden eliminar.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

// 2. Comprobar que hemos recibido una ID válida por la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestionar-plugins.php');
    exit();
}
$plugin_id = (int)$_GET['id'];

// 3. Antes de borrar de la base de datos, necesitamos saber qué archivos físicos borrar.
// Hacemos una consulta para obtener los nombres de los archivos.
$stmt = $mysqli->prepare("SELECT file_path, image FROM plugins WHERE id = ?");
$stmt->bind_param('i', $plugin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $plugin = $result->fetch_assoc();

    // 4. Borrar el archivo del plugin (.zip) del servidor
    if ($plugin['file_path'] && file_exists(UPLOAD_PATH . $plugin['file_path'])) {
        unlink(UPLOAD_PATH . $plugin['file_path']);
    }

    // 5. Borrar la imagen del plugin del servidor
    if ($plugin['image'] && file_exists(__DIR__ . '/../' . $plugin['image'])) {
        unlink(__DIR__ . '/../' . $plugin['image']);
    }
}
$stmt->close();

// 6. Ahora que los archivos físicos están borrados, borramos el registro de la base de datos.
$delete_stmt = $mysqli->prepare("DELETE FROM plugins WHERE id = ?");
$delete_stmt->bind_param('i', $plugin_id);
$delete_stmt->execute();
$delete_stmt->close();

// 7. Finalmente, redirigimos al usuario de vuelta a la lista de plugins.
// El plugin eliminado ya no aparecerá.
header('Location: gestionar-plugins.php');
exit();

?>