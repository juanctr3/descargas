<?php
// my-licenses.php

require_once 'includes/config.php';
require_once 'includes/user_auth.php'; // Asegura que el usuario haya iniciado sesión

$page_title = 'Mis Licencias';
$feedback_message = '';
$message_class = '';

$user_id = $_SESSION['user_id'];

// --- CORRECCIÓN: Obtener los datos del usuario directamente de la base de datos ---
$user_stmt = $mysqli->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$user_name = $user_data['name'] ?? 'Usuario';
$user_email = $user_data['email'] ?? '';
// --- FIN DE LA CORRECCIÓN ---


// --- PROCESAR FORMULARIO DE SOLICITUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $request_type = trim($_POST['request_type']);
    $plugin_id = (int)$_POST['plugin_id'];
    $license_key = trim($_POST['license_key']);
    $details = trim($_POST['details']);

    if (empty($request_type) || empty($details)) {
        $feedback_message = 'Por favor, completa todos los campos requeridos.';
        $message_class = 'alert-danger';
    } else {
        // Enviar notificación al administrador
        $plugin_name_res = $mysqli->query("SELECT title FROM plugins WHERE id = $plugin_id");
        $plugin_name = $plugin_name_res->fetch_assoc()['title'] ?? 'No especificado';

        $admins_res = $mysqli->query("SELECT email, whatsapp_number FROM admins");
        $admins = $admins_res->fetch_all(MYSQLI_ASSOC);

        $subject = "Nueva Solicitud de Licencia: {$request_type}";
        $body = "Se ha recibido una nueva solicitud de un usuario:\n\n" .
                "Usuario: {$user_name} ({$user_email})\n" .
                "Tipo de Solicitud: {$request_type}\n" .
                "Plugin: {$plugin_name}\n" .
                "Clave de Licencia (si aplica): {$license_key}\n" .
                "Detalles: {$details}";

        foreach ($admins as $admin) {
            if (!empty($admin['whatsapp_number'])) {
                sendWhatsAppNotification($admin['whatsapp_number'], $body, $app_settings);
            }
            if (!empty($admin['email'])) {
                sendSMTPMail($admin['email'], 'Admin', $subject, nl2br($body), $app_settings);
            }
        }
        
        $feedback_message = '¡Tu solicitud ha sido enviada! Nos pondremos en contacto contigo pronto.';
        $message_class = 'alert-success';
    }
}


// --- OBTENER LAS LICENCIAS DEL USUARIO ---
$query = "
    SELECT 
        lk.license_key, lk.status, lk.expires_at,
        p.title as plugin_title,
        GROUP_CONCAT(la.domain SEPARATOR '<br>') as activated_domains
    FROM 
        license_keys AS lk
    JOIN 
        plugins AS p ON lk.plugin_id = p.id
    LEFT JOIN
        license_activations AS la ON la.license_id = lk.id
    WHERE 
        lk.user_id = ?
    GROUP BY
        lk.id
    ORDER BY 
        lk.created_at DESC
";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$licenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener lista de plugins que el usuario ha descargado
$downloaded_plugins = $mysqli->query("SELECT DISTINCT p.id, p.title FROM downloads d JOIN plugins p ON d.plugin_id = p.id WHERE d.user_id = $user_id ORDER BY p.title ASC")->fetch_all(MYSQLI_ASSOC);


include 'includes/header.php';
?>

<main class="container py-5">
    <h1 class="mb-4"><?php echo $page_title; ?></h1>

    <div class="card">
        <div class="card-header">
            <h4>Tus Licencias Activas</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Plugin</th>
                            <th>Clave de Licencia</th>
                            <th>Estado</th>
                            <th>Sitios Activos</th>
                            <th>Expira</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($licenses)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Aún no tienes licencias asociadas a tu cuenta.</td></tr>
                        <?php else: ?>
                            <?php foreach ($licenses as $license): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($license['plugin_title']); ?></td>
                                    <td><code class="user-select-all"><?php echo htmlspecialchars($license['license_key']); ?></code></td>
                                    <td><span class="badge bg-<?php echo ($license['status'] == 'active' ? 'success' : 'secondary'); ?>"><?php echo ucfirst($license['status']); ?></span></td>
                                    <td><?php echo $license['activated_domains'] ?: '<span class="text-muted">Ninguno</span>'; ?></td>
                                    <td><?php echo $license['expires_at'] ? date('d/m/Y', strtotime($license['expires_at'])) : 'Nunca'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h4>Solicitar Soporte de Licencia</h4>
        </div>
        <div class="card-body">
            <p>Usa este formulario para solicitar una nueva licencia, pedir un cambio de dominio o extender el uso de una licencia existente.</p>
            
            <?php if ($feedback_message): ?>
                <div class="alert <?php echo $message_class; ?>"><?php echo $feedback_message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="request_type" class="form-label">Tipo de Solicitud (*)</label>
                        <select id="request_type" name="request_type" class="form-select" required>
                            <option value="">Selecciona una opción...</option>
                            <option value="Nueva Licencia">Solicitar Nueva Licencia</option>
                            <option value="Cambio de Dominio">Solicitar Cambio de Dominio</option>
                            <option value="Extensión de Licencia">Solicitar Extensión de Licencia</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="plugin_id" class="form-label">Plugin Relacionado</label>
                        <select id="plugin_id" name="plugin_id" class="form-select">
                            <option value="">Selecciona un plugin (si aplica)...</option>
                            <?php foreach ($downloaded_plugins as $plugin): ?>
                                <option value="<?php echo $plugin['id']; ?>"><?php echo htmlspecialchars($plugin['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="license_key" class="form-label">Clave de Licencia (si aplica)</label>
                    <input type="text" id="license_key" name="license_key" class="form-control" placeholder="Pega aquí la licencia si tu solicitud es sobre una existente">
                </div>
                <div class="mb-3">
                    <label for="details" class="form-label">Detalles de tu Solicitud (*)</label>
                    <textarea id="details" name="details" class="form-control" rows="5" placeholder="Por favor, explícanos tu necesidad. Si es un cambio de dominio, indica el dominio antiguo y el nuevo." required></textarea>
                </div>
                <button type="submit" name="send_request" class="btn btn-primary">Enviar Solicitud</button>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>