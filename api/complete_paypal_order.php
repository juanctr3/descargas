<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
$input = json_decode(file_get_contents('php://input'), true);
$orderID = $input['orderID'] ?? null;
$plugin_id = $input['pluginID'] ?? null;

if (!$orderID || !$plugin_id || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos o no has iniciado sesión.']);
    exit();
}

$clientId = $app_settings['paypal_client_id'] ?? '';
$clientSecret = $app_settings['paypal_client_secret'] ?? '';

if (empty($clientId) || empty($clientSecret)) {
    echo json_encode(['success' => false, 'message' => 'La configuración de PayPal está incompleta.']);
    exit();
}

// 1. OBTENER ACCESS TOKEN DE PAYPAL
$auth = base64_encode($clientId . ":" . $clientSecret);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.sandbox.paypal.com/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $auth]);
$authResponse = json_decode(curl_exec($ch), true);
$accessToken = $authResponse['access_token'] ?? null;
curl_close($ch);

if (!$accessToken) { echo json_encode(['success' => false, 'message' => 'Error de autenticación con PayPal.']); exit(); }

// 2. VERIFICAR LA ORDEN CON PAYPAL
$ch_verify = curl_init();
curl_setopt($ch_verify, CURLOPT_URL, 'https://api.sandbox.paypal.com/v2/checkout/orders/' . $orderID);
curl_setopt($ch_verify, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch_verify, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
$orderResponse = json_decode(curl_exec($ch_verify), true);
curl_close($ch_verify);

// 3. SI EL PAGO ESTÁ COMPLETO, GUARDAMOS Y NOTIFICAMOS
if (($orderResponse['status'] ?? '') === 'COMPLETED') {
    $transaction_id = $orderResponse['id'];
    $amount_paid = $orderResponse['purchase_units'][0]['amount']['value'];
    $currency = $orderResponse['purchase_units'][0]['amount']['currency_code'];
    $user_id = $_SESSION['user_id'];
    
    $stmt_order = $mysqli->prepare("INSERT INTO orders (user_id, plugin_id, payment_gateway, transaction_id, amount_paid, currency, payment_status) VALUES (?, ?, 'paypal', ?, ?, ?, 'completed')");
    $stmt_order->bind_param('iisds', $user_id, $plugin_id, $transaction_id, $amount_paid, $currency);
    $stmt_order->execute();
    $stmt_order->close();
    
    // Enviar notificaciones...
    $user_info_stmt = $mysqli->prepare("SELECT name, email, whatsapp_number FROM users WHERE id = ?");
    $user_info_stmt->bind_param('i', $user_id); $user_info_stmt->execute();
    $user_info = $user_info_stmt->get_result()->fetch_assoc(); $user_info_stmt->close();
    
    $plugin_info_stmt = $mysqli->prepare("SELECT title FROM plugins WHERE id = ?");
    $plugin_info_stmt->bind_param('i', $plugin_id); $plugin_info_stmt->execute();
    $plugin_title = $plugin_info_stmt->get_result()->fetch_assoc()['title']; $plugin_info_stmt->close();

    if (!empty($user_info['email'])) {
        $subject = "Confirmación de tu compra: " . $plugin_title;
        $body = "Hola " . $user_info['name'] . ",<br><br>Gracias por tu compra del plugin '" . $plugin_title . "'. Ya puedes acceder a tu descarga desde tu cuenta.<br><br>Saludos,<br>El equipo de " . ($app_settings['site_name'] ?? 'nuestro sitio');
        sendSMTPMail($user_info['email'], $user_info['name'], $subject, $body, $app_settings);
    }
    if (!empty($user_info['whatsapp_number'])) {
        $message = "¡Hola " . $user_info['name'] . "! 👋 Gracias por comprar '" . $plugin_title . "'. Ya tienes acceso a la descarga. ¡Que lo disfrutes!";
        sendWhatsAppNotification($user_info['whatsapp_number'], $message, $app_settings);
    }

    echo json_encode(['success' => true, 'message' => '¡Pago verificado y orden registrada!']);
} else {
    echo json_encode(['success' => false, 'message' => 'El pago no pudo ser verificado por PayPal.']);
}