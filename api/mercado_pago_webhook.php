<?php
// Incluimos nuestros archivos principales
require_once '../includes/config.php';

// Leemos la notificación enviada por Mercado Pago
$json_notification = file_get_contents('php://input');
$notification_data = json_decode($json_notification, true);

// Creamos un archivo de log para depuración (muy útil)
$log_file = __DIR__ . '/mp_log.txt';
file_put_contents($log_file, "--- Nueva Notificación ---\n", FILE_APPEND);
file_put_contents($log_file, $json_notification . "\n\n", FILE_APPEND);

// Verificamos que sea una notificación de pago y que tengamos el ID del pago
if (isset($notification_data['type'], $notification_data['data']['id']) && $notification_data['type'] === 'payment') {
    
    $payment_id = $notification_data['data']['id'];
    
    // Incluimos el SDK de Mercado Pago
    require_once '../libs/mercadopago/vendor/autoload.php';

    // Configuramos el SDK con nuestro Access Token
    MercadoPago\SDK::setAccessToken($app_settings['mercadopago_access_token'] ?? '');

    try {
        // Buscamos la información completa del pago en la API de Mercado Pago
        $payment = MercadoPago\Payment::find_by_id($payment_id);

        // Si el pago existe y está APROBADO
        if ($payment && $payment->status == 'approved') {
            
            // Verificamos si esta transacción ya fue procesada para no duplicar
            $stmt_check = $mysqli->prepare("SELECT id FROM orders WHERE transaction_id = ?");
            $stmt_check->bind_param('s', $payment->id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows === 0) {
                // El pago es nuevo y válido, lo procesamos
                $external_reference = $payment->external_reference; // Ej: PLUGIN_3_USER_1_1687554321
                
                // Extraemos el ID del plugin y del usuario
                $plugin_id = 0;
                $user_id = 0;
                if (preg_match('/PLUGIN_(\d+)_USER_(\d+)/', $external_reference, $matches)) {
                    $plugin_id = (int)$matches[1];
                    $user_id = (int)$matches[2];
                }

                if ($plugin_id > 0 && $user_id > 0) {
                    // Guardamos la orden en nuestra base de datos
                    $stmt_order = $mysqli->prepare("INSERT INTO orders (user_id, plugin_id, payment_gateway, transaction_id, amount_paid, currency, payment_status) VALUES (?, ?, 'mercadopago', ?, ?, ?, 'completed')");
                    $stmt_order->bind_param('iisds', $user_id, $plugin_id, $payment->id, $payment->transaction_amount, $payment->currency_id);
                    $stmt_order->execute();
                    $stmt_order->close();

                    // Actualizamos el contador de descargas
                    $mysqli->query("UPDATE plugins SET download_count = download_count + 1 WHERE id = " . $plugin_id);

                    // Enviamos las notificaciones al usuario
                    $user_info_stmt = $mysqli->prepare("SELECT name, email, whatsapp_number FROM users WHERE id = ?");
                    $user_info_stmt->bind_param('i', $user_id);
                    $user_info_stmt->execute();
                    $user_info = $user_info_stmt->get_result()->fetch_assoc();
                    $user_info_stmt->close();

                    $plugin_title = $payment->items[0]->title;

                    if ($user_info) {
                        if (!empty($user_info['email'])) {
                            $subject = "Confirmación de tu compra: " . $plugin_title;
                            $body = "Hola " . $user_info['name'] . ",<br><br>Gracias por tu compra del plugin '" . $plugin_title . "'. Ya puedes acceder a tu descarga desde tu cuenta.<br><br>Saludos,<br>El equipo de " . ($app_settings['site_name'] ?? 'nuestro sitio');
                            sendSMTPMail($user_info['email'], $user_info['name'], $subject, $body, $app_settings);
                        }
                        if (!empty($user_info['whatsapp_number'])) {
                            $message = "¡Hola " . $user_info['name'] . "! 👋 Gracias por comprar '" . $plugin_title . "'. Ya tienes acceso a la descarga. ¡Que lo disfrutes!";
                            sendWhatsAppNotification($user_info['whatsapp_number'], $message, $app_settings);
                        }
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        // En caso de error, lo guardamos en el log
        file_put_contents($log_file, "Error al procesar: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Respondemos a Mercado Pago con un "200 OK" para que sepa que recibimos la notificación
http_response_code(200);
?>