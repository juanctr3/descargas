<?php
// 1. Proteger la página. Solo los admins pueden acceder.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

// 2. Comprobar que hemos recibido una ID válida por la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Si no hay ID, simplemente redirigimos a la lista
    header('Location: manage-admins.php');
    exit();
}
$id_to_delete = (int)$_GET['id'];
$current_admin_id = $_SESSION['admin_id'];

// 3. REGLAS DE SEGURIDAD CRÍTICAS
if ($id_to_delete === 1) {
    // No permitir que se borre el administrador principal (ID 1)
    $_SESSION['feedback_message'] = 'Error: No se puede eliminar al administrador principal.';
    $_SESSION['message_class'] = 'alert-danger';
    header('Location: manage-admins.php');
    exit();
}

if ($id_to_delete === $current_admin_id) {
    // No permitir que un administrador se borre a sí mismo
    $_SESSION['feedback_message'] = 'Error: No puedes eliminar tu propia cuenta.';
    $_SESSION['message_class'] = 'alert-danger';
    header('Location: manage-admins.php');
    exit();
}

// 4. Si todas las comprobaciones de seguridad pasan, procedemos a borrar
$stmt = $mysqli->prepare("DELETE FROM admins WHERE id = ?");
$stmt->bind_param('i', $id_to_delete);

if ($stmt->execute()) {
    // Guardamos un mensaje de éxito en la sesión para mostrarlo en la página de la lista
    $_SESSION['feedback_message'] = 'Administrador eliminado con éxito.';
    $_SESSION['message_class'] = 'alert-success';
} else {
    $_SESSION['feedback_message'] = 'Error al eliminar el administrador.';
    $_SESSION['message_class'] = 'alert-danger';
}
$stmt->close();

// 5. Redirigimos de vuelta a la lista de administradores
header('Location: manage-admins.php');
exit();
?>