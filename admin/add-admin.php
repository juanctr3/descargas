<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Añadir Nuevo Administrador';
$error_message = '';
$success_message = '';

// Si el formulario fue enviado...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // --- Validaciones de Seguridad ---
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = 'Todos los campos son obligatorios.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error_message = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'El formato del correo electrónico no es válido.';
    } else {
        // Comprobar si el nombre de usuario ya existe
        $stmt = $mysqli->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = 'El nombre de usuario ya está en uso. Por favor, elige otro.';
        }
        $stmt->close();

        // Comprobar si el email ya existe
        if (empty($error_message)) {
            $stmt = $mysqli->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error_message = 'El correo electrónico ya está registrado.';
            }
            $stmt->close();
        }

        // Si no hay errores, procedemos a crear el usuario
        if (empty($error_message)) {
            // Encriptamos la contraseña - ¡NUNCA guardes contraseñas en texto plano!
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert_stmt = $mysqli->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
            $insert_stmt->bind_param('sss', $username, $email, $hashed_password);

            if ($insert_stmt->execute()) {
                $success_message = '¡Administrador creado con éxito! Ya puede iniciar sesión.';
            } else {
                $error_message = 'Hubo un error al crear el administrador en la base de datos.';
            }
            $insert_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="d-flex">
        <?php include '_sidebar.php'; ?>
        <main class="w-100 p-4">
            <h1 class="mb-4"><?php echo htmlspecialchars($page_title); ?></h1>

            <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="add-admin.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Debe tener al menos 8 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Crear Administrador</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>