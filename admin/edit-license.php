<?php
// admin/edit-license.php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Añadir Nueva Licencia';
$license_data = [];
$is_editing = false;
$feedback_message = '';
$message_class = '';

// --- Cargar datos si estamos editando ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $is_editing = true;
    $license_id = (int)$_GET['id'];
    $page_title = 'Editar Licencia';
    $stmt = $mysqli->prepare("SELECT * FROM license_keys WHERE id = ?");
    $stmt->bind_param('i', $license_id);
    $stmt->execute();
    $license_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$license_data) {
        header('Location: manage-licenses.php');
        exit();
    }
}

// --- Procesar el formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plugin_id = (int)$_POST['plugin_id'];
    $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $license_key = trim($_POST['license_key']);
    $status = $_POST['status'];
    $activation_limit = (int)$_POST['activation_limit'];
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    if ($is_editing) {
        // --- Lógica de Actualización ---
        $stmt = $mysqli->prepare("UPDATE license_keys SET plugin_id = ?, user_id = ?, license_key = ?, status = ?, activation_limit = ?, expires_at = ? WHERE id = ?");
        // CORREGIDO: Se cambiaron los tipos de 'iissssi' a 'iissisi' para que coincidan con los datos
        $stmt->bind_param('iissisi', $plugin_id, $user_id, $license_key, $status, $activation_limit, $expires_at, $license_id);
    } else {
        // --- Lógica de Creación ---
        if (empty($license_key)) {
            $license_key = 'LIC-' . strtoupper(bin2hex(random_bytes(16)));
        }
        $stmt = $mysqli->prepare("INSERT INTO license_keys (plugin_id, user_id, license_key, status, activation_limit, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        // CORREGIDO: Se cambiaron los tipos de 'iisssi' a 'iissis' para que coincidan con los datos
        $stmt->bind_param('iissis', $plugin_id, $user_id, $license_key, $status, $activation_limit, $expires_at);
    }

    if ($stmt->execute()) {
        $feedback_message = '¡Licencia guardada con éxito!';
        $message_class = 'alert-success';
        if (!$is_editing) {
             header('Location: manage-licenses.php?created=true'); exit();
        }
    } else {
        $feedback_message = 'Error al guardar la licencia: ' . $stmt->error;
        $message_class = 'alert-danger';
    }
    $stmt->close();
    
    if ($is_editing) {
         $stmt = $mysqli->prepare("SELECT * FROM license_keys WHERE id = ?");
         $stmt->bind_param('i', $license_id);
         $stmt->execute();
         $license_data = $stmt->get_result()->fetch_assoc();
         $stmt->close();
    }
}

$plugins = $mysqli->query("SELECT id, title FROM plugins ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);
$users = $mysqli->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
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
            <h1><?php echo htmlspecialchars($page_title); ?></h1>

            <?php if ($feedback_message): ?>
                <div class="alert <?php echo $message_class; ?>"><?php echo $feedback_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="plugin_id" class="form-label">Plugin (*)</label>
                                <select class="form-select" id="plugin_id" name="plugin_id" required>
                                    <option value="">Selecciona un plugin...</option>
                                    <?php foreach ($plugins as $plugin): ?>
                                        <option value="<?php echo $plugin['id']; ?>" <?php echo (($license_data['plugin_id'] ?? '') == $plugin['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($plugin['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label">Usuario (Opcional)</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">Sin asignar / Licencia manual</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo (($license_data['user_id'] ?? '') == $user['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="license_key" class="form-label">Clave de Licencia</label>
                            <input type="text" class="form-control" id="license_key" name="license_key" value="<?php echo htmlspecialchars($license_data['license_key'] ?? ''); ?>">
                            <div class="form-text">Déjalo en blanco para generar una clave automáticamente.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Estado</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo (($license_data['status'] ?? 'active') == 'active') ? 'selected' : ''; ?>>Activa</option>
                                    <option value="inactive" <?php echo (($license_data['status'] ?? '') == 'inactive') ? 'selected' : ''; ?>>Inactiva</option>
                                    <option value="expired" <?php echo (($license_data['status'] ?? '') == 'expired') ? 'selected' : ''; ?>>Expirada</option>
                                    <option value="disabled" <?php echo (($license_data['status'] ?? '') == 'disabled') ? 'selected' : ''; ?>>Deshabilitada</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="activation_limit" class="form-label">Límite de Activaciones</label>
                                <input type="number" class="form-control" id="activation_limit" name="activation_limit" min="1" value="<?php echo htmlspecialchars($license_data['activation_limit'] ?? '1'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="expires_at" class="form-label">Fecha de Expiración</label>
                                <input type="date" class="form-control" id="expires_at" name="expires_at" value="<?php echo htmlspecialchars($license_data['expires_at'] ?? ''); ?>">
                                <div class="form-text">Déjalo en blanco para que la licencia nunca expire.</div>
                            </div>
                        </div>

                        <a href="manage-licenses.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Licencia</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>