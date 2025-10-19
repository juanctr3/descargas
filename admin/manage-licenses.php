<?php
// admin/manage-licenses.php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Gestionar Licencias';

// --- Lógica para eliminar una licencia ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $license_id_to_delete = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM license_keys WHERE id = ?");
    $stmt->bind_param('i', $license_id_to_delete);
    $stmt->execute();
    $stmt->close();
    header('Location: manage-licenses.php?deleted=true');
    exit();
}

// --- CONSULTA MEJORADA: Incluye GROUP_CONCAT para obtener los dominios activados ---
$query = "
    SELECT 
        lk.id, lk.license_key, lk.status, lk.activation_limit, lk.expires_at,
        p.title as plugin_title,
        COALESCE(u.name, 'N/A') as user_name,
        (SELECT COUNT(*) FROM license_activations WHERE license_id = lk.id) as activation_count,
        GROUP_CONCAT(la.domain SEPARATOR '<br>') as activated_domains
    FROM 
        license_keys AS lk
    JOIN 
        plugins AS p ON lk.plugin_id = p.id
    LEFT JOIN 
        users AS u ON lk.user_id = u.id
    LEFT JOIN
        license_activations AS la ON la.license_id = lk.id
    GROUP BY
        lk.id
    ORDER BY 
        lk.created_at DESC
";
$result = $mysqli->query($query);
$licenses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Mapeo de colores para los estados
$status_colors = [
    'active' => 'success',
    'inactive' => 'secondary',
    'expired' => 'warning',
    'disabled' => 'danger'
];

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
                <a href="edit-license.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Añadir Nueva Licencia
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Clave de Licencia</th>
                                    <th>Plugin</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Activaciones (Usadas/Límite)</th>
                                    <th>Dominios Activos</th>
                                    <th>Expira</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($licenses)): ?>
                                    <tr><td colspan="8" class="text-center text-muted">No se han generado licencias todavía.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($licenses as $license): ?>
                                        <tr>
                                            <td><code class="user-select-all"><?php echo htmlspecialchars($license['license_key']); ?></code></td>
                                            <td><?php echo htmlspecialchars($license['plugin_title']); ?></td>
                                            <td><?php echo htmlspecialchars($license['user_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $status_colors[$license['status']] ?? 'light'; ?>">
                                                    <?php echo ucfirst($license['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold"><?php echo $license['activation_count']; ?> / <?php echo $license['activation_limit']; ?></span>
                                            </td>
                                            <td>
                                                <?php echo $license['activated_domains'] ? $license['activated_domains'] : '<span class="text-muted">Ninguno</span>'; ?>
                                            </td>
                                            <td><?php echo $license['expires_at'] ? date('d/m/Y', strtotime($license['expires_at'])) : 'Nunca'; ?></td>
                                            <td>
                                                <a href="edit-license.php?id=<?php echo $license['id']; ?>" class="btn btn-sm btn-secondary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $license['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar esta licencia? Esta acción no se puede deshacer.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>