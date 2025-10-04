<?php
// Proteger la página
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Gestionar Plugins';

// 1. Consultar la base de datos para obtener todos los plugins
// Los ordenamos por ID descendente para que los más nuevos aparezcan primero
$result = $mysqli->query("SELECT * FROM plugins ORDER BY id DESC");
$plugins = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .plugin-image-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include '_sidebar.php'; ?>

        <main class="w-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="add-plugin.php" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Añadir Nuevo Plugin
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Título</th>
                                    <th>Versión</th>
                                    <th>Descargas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 2. Comprobar si hay plugins para mostrar
                                if (count($plugins) > 0):
                                    // 3. Recorrer la lista de plugins y mostrarlos en la tabla
                                    foreach ($plugins as $plugin):
                                ?>
                                    <tr>
                                        <td><?php echo $plugin['id']; ?></td>
                                        <td>
                                            <?php if ($plugin['image']): ?>
                                                <img src="../<?php echo htmlspecialchars($plugin['image']); ?>" alt="Imagen del plugin" class="plugin-image-thumb">
                                            <?php else: ?>
                                                <img src="../assets/images/plugins/default.png" alt="Imagen por defecto" class="plugin-image-thumb"> <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($plugin['title']); ?></td>
                                        <td>v<?php echo htmlspecialchars($plugin['version']); ?></td>
                                        <td><?php echo $plugin['download_count']; ?></td>
                                        <td>
                                            <?php if ($plugin['status'] === 'active'): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit-plugin.php?id=<?php echo $plugin['id']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete-plugin.php?id=<?php echo $plugin['id']; ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar este plugin?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aún no has añadido ningún plugin.</td>
                                    </tr>
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