<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Editar Usuario';
$error_message = '';
$success_message = '';

// 1. Validamos que se haya pasado un ID de usuario válido por la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage-users.php');
    exit();
}
$user_id_to_edit = (int)$_GET['id'];

// 2. Si el formulario se envía, procesamos la actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $status = trim($_POST['status']);
    $password = $_POST['password'];

    if (empty($name) || empty($email)) {
        $error_message = 'El nombre y el email son obligatorios.';
    } else {
        // Preparamos la consulta SQL para actualizar los datos
        $sql_parts = [];
        $params = [];
        $types = '';

        $sql_parts[] = "name = ?"; $params[] = $name; $types .= 's';
        $sql_parts[] = "email = ?"; $params[] = $email; $types .= 's';
        $sql_parts[] = "whatsapp_number = ?"; $params[] = $whatsapp_number; $types .= 's';
        $sql_parts[] = "status = ?"; $params[] = $status; $types .= 's';
        
        // MUY IMPORTANTE: Solo actualizamos la contraseña si se ha escrito una nueva
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $error_message = 'La nueva contraseña debe tener al menos 8 caracteres.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_parts[] = "password = ?";
                $params[] = $hashed_password;
                $types .= 's';
            }
        }

        if (empty($error_message)) {
            $update_query = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?";
            $params[] = $user_id_to_edit;
            $types .= 'i';

            $stmt_update = $mysqli->prepare($update_query);
            $stmt_update->bind_param($types, ...$params);

            if ($stmt_update->execute()) {
                $success_message = 'Usuario actualizado con éxito.';
            } else {
                $error_message = 'Error al actualizar el usuario. Es posible que el email ya esté en uso.';
            }
            $stmt_update->close();
        }
    }
}

// 3. Obtenemos los datos actuales del usuario para rellenar el formulario
$stmt = $mysqli->prepare("SELECT name, email, whatsapp_number, status FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id_to_edit);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: manage-users.php');
    exit();
}
$user_data = $result->fetch_assoc();
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
            <h1 class="mb-4">Editando Usuario: <?php echo htmlspecialchars($user_data['name']); ?></h1>

            <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?> <a href="manage-users.php" class="alert-link">Volver a la lista</a>.</div><?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="edit-user.php?id=<?php echo $user_id_to_edit; ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="whatsapp_number" class="form-label">Número de WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($user_data['whatsapp_number'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo ($user_data['status'] === 'active') ? 'selected' : ''; ?>>Activo</option>
                                <option value="banned" <?php echo ($user_data['status'] === 'banned') ? 'selected' : ''; ?>>Suspendido</option>
                            </select>
                        </div>
                        <hr>
                        <p class="text-muted">Rellena el siguiente campo solo si deseas restablecer la contraseña del usuario.</p>
                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva Contraseña (Opcional)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        
                        <a href="manage-users.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>