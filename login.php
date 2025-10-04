<?php
require_once 'includes/config.php';
$page_title = 'Iniciar Sesión';
$error_message = '';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = 'Por favor, ingresa tu email y contraseña.';
    } else {
        $stmt = $mysqli->prepare("SELECT id, name, password FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: index.php');
                exit();
            }
        }
        $error_message = 'El email o la contraseña son incorrectos.';
    }
}

// Incluimos la cabecera completa
include 'includes/header.php';
?>
<main class="container my-5 flex-shrink-0">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="card-title text-center mb-4"><?php echo $page_title; ?></h3>
                    <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
                    <form action="login.php" method="POST">
                        <div class="mb-3"><label for="email" class="form-label">Correo Electrónico</label><input type="email" class="form-control" id="email" name="email" required></div>
                        <div class="mb-3"><label for="password" class="form-label">Contraseña</label><input type="password" class="form-control" id="password" name="password" required></div>
                        <div class="d-grid"><button type="submit" class="btn btn-primary">Iniciar Sesión</button></div>
                    </form>
                    <div class="text-center mt-3"><p class="text-muted">¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a>.</p></div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>