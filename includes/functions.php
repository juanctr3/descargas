<?php
// Versión Completa con TODAS las funciones, incluyendo la de YouTube
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';


function load_settings($mysqli) {
    $settings = [];
    $result = $mysqli->query("SELECT setting_key, setting_value FROM settings");
    if ($result) { while ($row = $result->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; } }
    return $settings;
}

function sendWhatsAppOTP($phoneNumber, $otpCode, $settings) {
    $api_secret = $settings['smsenlinea_secret'] ?? '';
    $api_account = $settings['smsenlinea_account'] ?? '';
    if (empty($api_secret) || empty($api_account)) { return false; }
    $url = "https://whatsapp.smsenlinea.com/api/send/whatsapp";
    $data = ["secret" => $api_secret, "account" => $api_account, "recipient" => $phoneNumber, "type" => "text", "message" => "🔐 Tu código de verificación es: *{$otpCode}*\n\n✅ Ingresa este código para descargar tu plugin.\n⏰ Válido por 10 minutos.\n\n🚫 No compartas este código."];
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return $httpCode === 200;
}

function sendWhatsAppNotification($phoneNumber, $message, $settings) {
    $api_secret = $settings['smsenlinea_secret'] ?? '';
    $api_account = $settings['smsenlinea_account'] ?? '';
    if (empty($api_secret) || empty($api_account)) { return false; }
    $url = "https://whatsapp.smsenlinea.com/api/send/whatsapp";
    $data = ["secret" => $api_secret, "account" => $api_account, "recipient" => $phoneNumber, "type" => "text", "message" => $message];
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return $httpCode === 200;
}

function sendSMTPMail($to_email, $to_name, $subject, $body, $settings) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host = $settings['smtp_host'] ?? ''; $mail->SMTPAuth = true; $mail->Username = $settings['smtp_user'] ?? ''; $mail->Password = $settings['smtp_pass'] ?? '';
        $mail->SMTPSecure = $settings['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = (int)($settings['smtp_port'] ?? 587);
        $mail->setFrom($settings['smtp_from_email'] ?? '', $settings['smtp_from_name'] ?? 'PluginHub');
        $mail->addAddress($to_email, $to_name); $mail->isHTML(true); $mail->CharSet = 'UTF-8'; $mail->Subject = $subject; $mail->Body = $body; $mail->AltBody = strip_tags($body);
        $mail->send(); return true;
    } catch (Exception $e) { return "El mensaje no pudo ser enviado. Error: {$mail->ErrorInfo}"; }
}

function generate_slug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text); setlocale(LC_ALL, 'en_US.UTF-8');
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text); $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-'); $text = preg_replace('~-+~', '-', $text); $text = strtolower($text);
    if (empty($text)) { return 'n-a-' . time(); } return $text;
}

/**
 * ¡NUEVA FUNCIÓN!
 * Extrae el ID de un video de YouTube desde una URL y crea la URL para incrustar.
 */
function get_youtube_embed_url($url) {
    if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[2] . '?autoplay=1&modestbranding=1&rel=0';
    }
    return ''; // Devuelve vacío si no es una URL de YouTube válida
}