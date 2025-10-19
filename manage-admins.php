<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';
$page_title = 'Gestionar Administradores';

// Obtenemos los administradores de la base de datos
$result = $mysqli->query("SELECT id, username, email, whatsapp_number, created_at FROM admins ORDER BY id ASC");
$admins = $result->fetch_all(MYSQLI_ASSOC);
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="add-admin.php" class="btn btn-success"><i class="fas fa-user-plus me-2"></i>Añadir Nuevo</a>
            </div>

            <?php
            if (isset($_SESSION['feedback_message'])) {
                echo '<div class="alert ' . $_SESSION['message_class'] . '">' . $_SESSION['feedback_message'] . '</div>';
                // Limpiamos la sesión para que el mensaje no se muestre de nuevo al recargar
                unset($_SESSION['feedback_message']);
                unset($_SESSION['message_class']);
            }
            ?>

            <div class="card"><div class="card-body"><div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>Usuario</th><th>Email</th><th>Teléfono 2FA</th><th>Fecha de Creación</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($admins) > 0): ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo $admin['id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['whatsapp_number'] ?? 'No asignado'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($admin['created_at'])); ?></td>
                                    <td>
                                        <a href="edit-admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-primary btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                        <?php if ($admin['id'] != $_SESSION['admin_id'] && $admin['id'] != 1): ?>
                                            <a href="delete-admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás realmente seguro de que quieres eliminar a este administrador? Esta acción no se puede deshacer.');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No hay administradores registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div></div></div>
        </main>
    </div>
</body>
</html>