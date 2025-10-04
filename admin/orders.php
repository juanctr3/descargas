<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Órdenes de Compra';

// 1. Preparamos la consulta SQL que une 3 tablas: orders, users y plugins
$query = "
    SELECT
        o.id,
        o.transaction_id,
        o.amount_paid,
        o.currency,
        o.payment_gateway,
        o.payment_status,
        o.created_at,
        u.name as user_name,
        u.email as user_email,
        p.title as plugin_title
    FROM
        orders AS o
    JOIN
        users AS u ON o.user_id = u.id
    JOIN
        plugins AS p ON o.plugin_id = p.id
    ORDER BY
        o.created_at DESC
";

$result = $mysqli->query($query);
$orders = $result->fetch_all(MYSQLI_ASSOC);
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

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID Orden</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Producto</th>
                                    <th>Pasarela</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>ID Transacción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo $order['id']; ?></td>
                                            <td><?php echo date('d/m/Y h:i A', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($order['user_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['plugin_title']); ?></td>
                                            <td>
                                                <?php if ($order['payment_gateway'] == 'paypal'): ?>
                                                    <span class="badge bg-primary">PayPal</span>
                                                <?php elseif ($order['payment_gateway'] == 'mercadopago'): ?>
                                                    <span class="badge bg-info text-dark">Mercado Pago</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($order['payment_gateway']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>$<?php echo number_format($order['amount_paid'], 2); ?> <?php echo htmlspecialchars($order['currency']); ?></td>
                                            <td>
                                                <?php if ($order['payment_status'] == 'completed'): ?>
                                                    <span class="badge bg-success">Completado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning"><?php echo htmlspecialchars($order['payment_status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($order['transaction_id']); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Aún no se ha registrado ninguna compra.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>