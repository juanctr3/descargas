<?php
require_once 'includes/config.php';

$page_title = 'Crear una Cuenta';
$error_message = '';
$success_message = '';

// Si el usuario ya inició sesión, no debería estar aquí. Lo redirigimos.
if (isset($_SESSION['user_id'])) {
    header('Location: my-account.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolectamos los datos del formulario
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // El JavaScript de intl-tel-input nos envía el número completo en este campo oculto
    $whatsapp_number = trim($_POST['whatsapp_full'] ?? '');

    // --- Validaciones ---
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = 'El nombre, email y contraseña son obligatorios.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error_message = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'El formato del email no es válido.';
    } else {
        // Comprobar si el email ya existe en la base de datos
        $stmt_check = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param('s', $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error_message = 'Este correo electrónico ya está registrado. Por favor, <a href="login.php" class="alert-link">inicia sesión</a>.';
        }
        $stmt_check->close();

        // Si no hay errores, procedemos a crear el usuario
        if (empty($error_message)) {
            // Encriptamos la contraseña para guardarla de forma segura
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt_insert = $mysqli->prepare("INSERT INTO users (name, email, password, whatsapp_number) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param('ssss', $name, $email, $hashed_password, $whatsapp_number);
            
            if ($stmt_insert->execute()) {
                $success_message = '¡Cuenta creada con éxito! Ahora puedes <a href="login.php" class="alert-link">iniciar sesión</a>.';
            } else {
                $error_message = 'Hubo un error al crear la cuenta. Por favor, inténtalo de nuevo.';
            }
            $stmt_insert->close();
        }
    }
}

// Incluimos la cabecera completa que ya tiene <!DOCTYPE>, <head>, y el menú de navegación.
include 'includes/header.php';
?>

<main class="container my-5 flex-shrink-0">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center mb-4"><?php echo $page_title; ?></h3>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php else: ?>
                        <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
                        
                        <form action="register.php" method="POST" id="registerForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="whatsapp_register" class="form-label">Número de WhatsApp (Opcional)</label>
                                <input type="tel" id="whatsapp_register" class="form-control">
                                <input type="hidden" id="whatsapp_full" name="whatsapp_full">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Crear Cuenta</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <p class="text-muted">¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php 
// Incluimos el footer, que ya tiene el script de la librería intl-tel-input
include 'includes/footer.php'; 
?>

<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

</body>
</html>