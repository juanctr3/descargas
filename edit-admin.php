<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Editar Administrador';
$error_message = '';
$success_message = '';

// 1. Verificamos que se haya pasado un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage-admins.php');
    exit();
}
$admin_id_to_edit = (int)$_GET['id'];

// 2. Si el formulario se envía, procesamos la actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validaciones
    if (empty($username) || empty($email)) {
        $error_message = 'El nombre de usuario y el email son obligatorios.';
    } elseif (!empty($password) && $password !== $password_confirm) {
        $error_message = 'Las contraseñas no coinciden.';
    } elseif (!empty($password) && strlen($password) < 8) {
        $error_message = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } else {
        // Comprobar si el nombre de usuario ya existe (excluyendo al usuario actual)
        $stmt_check = $mysqli->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $stmt_check->bind_param('si', $username, $admin_id_to_edit);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error_message = 'El nombre de usuario ya está en uso.';
        }
        $stmt_check->close();

        // Si no hay errores, procedemos a actualizar
        if (empty($error_message)) {
            if (!empty($password)) {
                // Si se proporcionó una nueva contraseña, la encriptamos y la incluimos en la actualización
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_update = $mysqli->prepare("UPDATE admins SET username = ?, email = ?, whatsapp_number = ?, password = ? WHERE id = ?");
                $stmt_update->bind_param('ssssi', $username, $email, $whatsapp_number, $hashed_password, $admin_id_to_edit);
            } else {
                // Si no se proporcionó una nueva contraseña, la excluimos de la actualización
                $stmt_update = $mysqli->prepare("UPDATE admins SET username = ?, email = ?, whatsapp_number = ? WHERE id = ?");
                $stmt_update->bind_param('sssi', $username, $email, $whatsapp_number, $admin_id_to_edit);
            }

            if ($stmt_update->execute()) {
                $success_message = 'Administrador actualizado con éxito.';
            } else {
                $error_message = 'Error al actualizar el administrador.';
            }
            $stmt_update->close();
        }
    }
}

// 3. Obtenemos los datos actuales del administrador para rellenar el formulario
$stmt = $mysqli->prepare("SELECT username, email, whatsapp_number FROM admins WHERE id = ?");
$stmt->bind_param('i', $admin_id_to_edit);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // Si no se encuentra un admin con ese ID, redirigir
    header('Location: manage-admins.php');
    exit();
}
$admin_data = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include '_sidebar.php'; ?>
        <main class="w-100 p-4">
            <h1 class="mb-4"><?php echo htmlspecialchars($page_title); ?></h1>

            <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

            <div class="card"><div class="card-body">
                <form method="POST" action="edit-admin.php?id=<?php echo $admin_id_to_edit; ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($admin_data['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="whatsapp_number" class="form-label">Número de WhatsApp (para 2FA)</label>
                        <input type="tel" class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($admin_data['whatsapp_number'] ?? ''); ?>">
                    </div>
                    <hr>
                    <p class="text-muted">Rellena los siguientes campos solo si deseas cambiar la contraseña.</p>
                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                    </div>
                    <a href="manage-admins.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Administrador</button>
                </form>
            </div></div>
        </main>
    </div>
</body>
</html>