<?php
require_once 'includes/config.php';
$page_title = 'Estado del Pago';

$status = $_GET['status'] ?? 'unknown';
$message = '';
$alert_class = '';

switch ($status) {
    case 'success':
        $message = '¡Tu pago fue aprobado con éxito! En breve recibirás acceso a tu descarga. Revisa tu perfil o tu email.';
        $alert_class = 'alert-success';
        break;
    case 'failure':
        $message = 'Hubo un problema con tu pago y fue rechazado. Por favor, intenta de nuevo o contacta a tu banco.';
        $alert_class = 'alert-danger';
        break;
    case 'pending':
        $message = 'Tu pago está pendiente de aprobación. Te notificaremos cuando se complete el proceso.';
        $alert_class = 'alert-warning';
        break;
    default:
        $message = 'El estado de tu pago es desconocido. Por favor, contacta a soporte.';
        $alert_class = 'alert-secondary';
        break;
}

include 'includes/header.php';
?>
<main class="container my-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="alert <?php echo $alert_class; ?>" role="alert">
                <h4 class="alert-heading">
                    <?php 
                        if ($status === 'success') echo '¡Gracias por tu compra!';
                        elseif ($status === 'failure') echo 'Pago Rechazado';
                        else echo 'Pago Pendiente';
                    ?>
                </h4>
                <p><?php echo $message; ?></p>
            </div>
            <a href="index.php" class="btn btn-primary mt-3">Volver a la Tienda</a>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>