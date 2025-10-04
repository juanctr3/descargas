<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';
$page_title = 'Usuarios Registrados';
$result = $mysqli->query("SELECT id, name, email, whatsapp_number, status, created_at FROM users ORDER BY created_at DESC");
$users = $result->fetch_all(MYSQLI_ASSOC);
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
            <div class="card"><div class="card-body"><div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>Nombre</th><th>Email</th><th>WhatsApp</th><th>Estado</th><th>Registrado el</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['whatsapp_number'] ?? 'No proporcionado'); ?></td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?><span class="badge bg-success">Activo</span>
                                        <?php elseif ($user['status'] === 'banned'): ?><span class="badge bg-danger">Suspendido</span>
                                        <?php else: ?><span class="badge bg-warning"><?php echo htmlspecialchars($user['status']); ?></span><?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm" title="Editar Usuario"><i class="fas fa-edit"></i></a>
                                        <a href="#" class="btn btn-warning btn-sm" title="Suspender Usuario"><i class="fas fa-ban"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted">No hay usuarios registrados en el sitio.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div></div></div>
        </main>
    </div>
</body>
</html>