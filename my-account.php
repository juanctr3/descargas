<?php
// my-account.php

// Usamos nuestro guardián para proteger la página
require_once 'includes/user_auth.php';

$page_title = 'Mi Cuenta';
$user_id = $_SESSION['user_id'];
$success_message = ''; $error_message = '';

// --- LÓGICA PARA ACTUALIZAR EL PERFIL ---
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $country_code = $_POST['country_code'] ?? '';
    $local_phone = trim($_POST['local_phone'] ?? '');
    $whatsapp_number = !empty($local_phone) ? $country_code . $local_phone : '';

    if (empty($name) || empty($email)) { $error_message = 'El nombre y el email son obligatorios.'; }
    else {
        $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, whatsapp_number = ? WHERE id = ?");
        $stmt->bind_param('sssi', $name, $email, $whatsapp_number, $user_id);
        if($stmt->execute()){ 
            $_SESSION['user_name'] = $name; // Actualizar nombre en sesión
            $success_message = "Perfil actualizado con éxito."; 
        }
        else { $error_message = "Error al actualizar el perfil."; }
        $stmt->close();
    }
}

// --- LÓGICA PARA CAMBIAR LA CONTRASEÑA ---
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $error_message = 'Todos los campos de contraseña son obligatorios.';
    } elseif ($new_password !== $confirm_new_password) {
        $error_message = 'Las contraseñas nuevas no coinciden.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } else {
        $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (password_verify($current_password, $user['password'])) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param('si', $new_hashed_password, $user_id);
            if ($update_stmt->execute()) {
                $success_message = "Contraseña actualizada con éxito.";
            } else { $error_message = "Error al actualizar la contraseña."; }
            $update_stmt->close();
        } else {
            $error_message = "La contraseña actual es incorrecta.";
        }
    }
}

// Obtenemos los datos actualizados del usuario para mostrar en la página
$user_stmt = $mysqli->prepare("SELECT name, email, whatsapp_number FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Obtenemos el historial de descargas del usuario
$history_stmt = $mysqli->prepare("SELECT p.title, p.slug, d.downloaded_at FROM downloads d JOIN plugins p ON d.plugin_id = p.id WHERE d.user_id = ? ORDER BY d.downloaded_at DESC");
$history_stmt->bind_param('i', $user_id);
$history_stmt->execute();
$download_history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$history_stmt->close();


include 'includes/header.php';
?>
<main class="container my-5 flex-shrink-0">
    <h1 class="mb-4">Hola, <?php echo htmlspecialchars($user_data['name']); ?></h1>
    <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h4>Mis Datos</h4></div>
                <div class="card-body">
                    <form action="my-account.php" method="POST">
                        <div class="mb-3"><label for="name" class="form-label">Nombre</label><input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($user_data['name']); ?>" required></div>
                        <div class="mb-3"><label for="email" class="form-label">Email</label><input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" required></div>
                        <div class="mb-3"><label for="local_phone" class="form-label">Número de WhatsApp</label><div class="input-group">
                            <select id="country_code" name="country_code" class="form-select" style="max-width: 120px;"><option value="57">Colombia (+57)</option><option value="52">México (+52)</option><option value="54">Argentina (+54)</option><option value="51">Perú (+51)</option><option value="56">Chile (+56)</option><option value="593">Ecuador (+593)</option><option value="34">España (+34)</option><option value="1">EE.UU. (+1)</option></select>
                            <input type="tel" id="local_phone" name="local_phone" class="form-control" placeholder="Ej: 3001234567">
                        </div></div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Guardar Cambios</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4>Cambiar Contraseña</h4></div>
                <div class="card-body">
                    <form action="my-account.php" method="POST">
                        <div class="mb-3"><label for="current_password" class="form-label">Contraseña Actual</label><input type="password" name="current_password" id="current_password" class="form-control" required></div>
                        <div class="mb-3"><label for="new_password" class="form-label">Nueva Contraseña</label><input type="password" name="new_password" id="new_password" class="form-control" required></div>
                        <div class="mb-3"><label for="confirm_new_password" class="form-label">Confirmar Nueva Contraseña</label><input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required></div>
                        <button type="submit" name="change_password" class="btn btn-secondary">Cambiar Contraseña</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h4>Panel de Usuario</h4></div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?php echo SITE_URL; ?>/my-account.php" class="list-group-item list-group-item-action active" aria-current="true"><i class="fas fa-user-edit fa-fw me-2"></i> Mi Perfil</a>
                        <a href="<?php echo SITE_URL; ?>/my-licenses.php" class="list-group-item list-group-item-action"><i class="fas fa-key fa-fw me-2"></i> Mis Licencias</a>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><h4>Mi Historial de Descargas</h4></div>
                <div class="card-body">
                    <?php if(empty($download_history)): ?>
                        <p class="text-muted">Aún no has realizado ninguna descarga.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                        <?php foreach($download_history as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                <a href="plugin/<?php echo htmlspecialchars($item['slug']); ?>/"><?php echo htmlspecialchars($item['title']); ?></a>
                                <span class="badge bg-light text-dark-emphasis rounded-pill"><?php echo date('d/m/Y', strtotime($item['downloaded_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fullNumber = "<?php echo htmlspecialchars($user_data['whatsapp_number'] ?? ''); ?>";
        const countrySelect = document.getElementById('country_code');
        const phoneInput = document.getElementById('local_phone');
        
        if (fullNumber) {
            let found = false;
            for (let i = 0; i < countrySelect.options.length; i++) {
                let countryCode = countrySelect.options[i].value;
                if (fullNumber.startsWith(countryCode)) {
                    countrySelect.value = countryCode;
                    phoneInput.value = fullNumber.substring(countryCode.length);
                    found = true;
                    break;
                }
            }
            if (!found) { phoneInput.value = fullNumber; }
        }
    });
</script>
</body>
</html>