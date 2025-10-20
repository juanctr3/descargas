<?php
// includes/Async.php
// Envía una petición POST "rápida" que no bloquea la descarga.
function fire_and_forget_post(string $url, array $payload = [], int $timeoutMs = 1200): void {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => false,  // no esperamos respuesta
        CURLOPT_HEADER         => false,
        CURLOPT_TIMEOUT_MS     => $timeoutMs, // ~1.2s máx.
        CURLOPT_FORBID_REUSE   => true,
        CURLOPT_FRESH_CONNECT  => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    @curl_exec($ch);
    curl_close($ch);
}
