<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { die('ID de producto no válido.'); }
$plugin_id = (int)$_GET['id'];

$stmt = $mysqli->prepare("SELECT title, price FROM plugins WHERE id = ? AND price > 0");
$stmt->bind_param('i', $plugin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { die('Producto no válido o es gratuito.'); }
$plugin = $result->fetch_assoc();
$stmt->close();

$access_token = $app_settings['mercadopago_access_token'] ?? '';
if (empty($access_token)) {
    die('La pasarela de pago de Mercado Pago no está configurada.');
}

// 1. Preparamos los datos de la compra en un array
$preference_data = [
    "items" => [
        [
            "id" => $plugin_id,
            "title" => $plugin['title'],
            "quantity" => 1,
            "unit_price" => (float)$plugin['price'],
            "currency_id" => "USD"
        ]
    ],
    "back_urls" => [
        "success" => SITE_URL . "/payment_status.php?status=success",
        "failure" => SITE_URL . "/payment_status.php?status=failure",
        "pending" => SITE_URL . "/payment_status.php?status=pending"
    ],
    "auto_return" => "approved"
];

// 2. Convertimos los datos a formato JSON
$preference_json = json_encode($preference_data);

// 3. Nos comunicamos con la API de Mercado Pago usando cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/checkout/preferences');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $preference_json);
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 4. Procesamos la respuesta
if ($http_code == 201 || $http_code == 200) {
    $response_data = json_decode($response, true);
    if (isset($response_data['init_point'])) {
        // 5. Si todo fue bien, redirigimos al usuario a la página de pago
        header('Location: ' . $response_data['init_point']);
        exit();
    }
}

// Si algo falló, mostramos un error
die('Hubo un error al crear la preferencia de pago. Revisa el Access Token. Respuesta de la API: ' . $response);
?>