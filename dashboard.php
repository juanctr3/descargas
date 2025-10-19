<?php
// Proteger la página
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Dashboard';

// --- INICIO DE LA LÓGICA DEL DASHBOARD ---

// 1. Obtener el total de plugins activos
$result_total_plugins = $mysqli->query("SELECT COUNT(id) as total FROM plugins WHERE status = 'active'");
$total_plugins_activos = $result_total_plugins->fetch_assoc()['total'];

// 2. Obtener el total de descargas de hoy
// CURDATE() obtiene la fecha actual del servidor MySQL
$result_downloads_today = $mysqli->query("SELECT COUNT(id) as total FROM downloads WHERE DATE(downloaded_at) = CURDATE()");
$descargas_hoy = $result_downloads_today->fetch_assoc()['total'];

// 3. Obtener el total de descargas de todos los tiempos
$result_total_downloads = $mysqli->query("SELECT SUM(download_count) as total FROM plugins");
$total_descargas = $result_total_downloads->fetch_assoc()['total'];


// 4. Obtener las últimas 5 descargas para la tabla de actividad reciente
$query_ultimas_descargas = "
    SELECT d.phone_number, d.downloaded_at, p.title as plugin_title
    FROM downloads d
    JOIN plugins p ON d.plugin_id = p.id
    ORDER BY d.downloaded_at DESC
    LIMIT 5
";
$result_ultimas_descargas = $mysqli->query($query_ultimas_descargas);
$ultimas_descargas = $result_ultimas_descargas->fetch_all(MYSQLI_ASSOC);

// --- FIN DE LA LÓGICA ---

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
            <p>¡Bienvenido de nuevo, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>!</p>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-download"></i> Descargas Hoy</h5>
                            <p class="card-text fs-2"><?php echo $descargas_hoy; ?></p>
                        </div>
                    </div>
                </div>
                 <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-plug"></i> Plugins Activos</h5>
                            <p class="card-text fs-2"><?php echo $total_plugins_activos; ?></p>
                        </div>
                    </div>
                </div>
                 <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-globe"></i> Total Descargas</h5>
                            <p class="card-text fs-2"><?php echo number_format($total_descargas); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-history me-2"></i>Actividad Reciente (Últimas 5 Descargas)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Plugin Descargado</th>
                                    <th>Número de Teléfono</th>
                                    <th>Fecha y Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($ultimas_descargas) > 0): ?>
                                    <?php foreach ($ultimas_descargas as $descarga): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($descarga['plugin_title']); ?></td>
                                            <td><?php echo htmlspecialchars($descarga['phone_number']); ?></td>
                                            <td><?php echo date('d/m/Y h:i A', strtotime($descarga['downloaded_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No hay actividad de descarga registrada.</td>
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