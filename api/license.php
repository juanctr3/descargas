<?php
// api/license.php

header('Content-Type: application/json');
require_once '../includes/config.php';

// --- FUNCIONES AUXILIARES ---

/**
 * Envía una respuesta en formato JSON y termina la ejecución del script.
 * @param array $data Los datos a codificar en JSON.
 */
function send_json_response($data) {
    echo json_encode($data);
    exit();
}

/**
 * Obtiene los datos de una licencia a partir de su clave.
 * @param mysqli $mysqli Objeto de conexión a la base de datos.
 * @param string $key La clave de licencia.
 * @return array|null Los datos de la licencia o null si no se encuentra.
 */
function get_license_by_key($mysqli, $key) {
    $stmt = $mysqli->prepare("
        SELECT l.*, p.slug as plugin_slug
        FROM license_keys l
        JOIN plugins p ON l.plugin_id = p.id
        WHERE l.license_key = ?
    ");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $license = $result->fetch_assoc();
    $stmt->close();
    return $license;
}

/**
 * Cuenta cuántas activaciones tiene una licencia.
 * @param mysqli $mysqli Objeto de conexión.
 * @param int $license_id El ID de la licencia.
 * @return int El número de activaciones.
 */
function get_activations_count($mysqli, $license_id) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM license_activations WHERE license_id = ?");
    $stmt->bind_param('i', $license_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    return $count;
}

/**
 * Verifica si un dominio específico ya está activado para una licencia.
 * @param mysqli $mysqli Objeto de conexión.
 * @param int $license_id El ID de la licencia.
 * @param string $domain El dominio a verificar.
 * @return bool True si está activado, false si no.
 */
function is_domain_activated($mysqli, $license_id, $domain) {
    $stmt = $mysqli->prepare("SELECT id FROM license_activations WHERE license_id = ? AND domain = ?");
    $stmt->bind_param('is', $license_id, $domain);
    $stmt->execute();
    $is_activated = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $is_activated;
}


// --- PROCESAMIENTO PRINCIPAL DE LA API ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'error' => 'invalid_request_method']);
}

$action = $_POST['action'] ?? null;
$license_key = trim($_POST['license_key'] ?? '');
$plugin_identifier = trim($_POST['plugin_slug'] ?? ''); // Identificador del plugin
$domain = trim($_POST['domain'] ?? '');

if (empty($action) || empty($license_key) || empty($plugin_identifier) || empty($domain)) {
    send_json_response(['success' => false, 'error' => 'missing_parameters']);
}

$license = get_license_by_key($mysqli, $license_key);

// --- Validaciones iniciales de la licencia ---
if (!$license) {
    send_json_response(['success' => false, 'error' => 'invalid_license_key']);
}
if ($license['plugin_slug'] !== $plugin_identifier) {
    send_json_response(['success' => false, 'error' => 'license_plugin_mismatch']);
}

// --- Lógica según la acción solicitada ---
switch ($action) {
    
    // --- ACCIÓN: ACTIVAR LICENCIA ---
    case 'activate':
        if ($license['status'] !== 'active') {
            send_json_response(['success' => false, 'error' => 'license_not_active', 'status' => $license['status']]);
        }
        if ($license['expires_at'] && new DateTime() > new DateTime($license['expires_at'])) {
            send_json_response(['success' => false, 'error' => 'license_expired']);
        }

        $activations_count = get_activations_count($mysqli, $license['id']);
        $domain_is_activated = is_domain_activated($mysqli, $license['id'], $domain);
        
        if ($domain_is_activated) {
            send_json_response(['success' => true, 'message' => 'already_activated']);
        }

        if ($activations_count >= $license['activation_limit']) {
            send_json_response(['success' => false, 'error' => 'activation_limit_reached']);
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt_activate = $mysqli->prepare("INSERT INTO license_activations (license_id, domain, ip_address) VALUES (?, ?, ?)");
        $stmt_activate->bind_param('iss', $license['id'], $domain, $ip);
        if ($stmt_activate->execute()) {
            send_json_response(['success' => true, 'message' => 'license_activated']);
        } else {
            send_json_response(['success' => false, 'error' => 'activation_failed']);
        }
        $stmt_activate->close();
        break;

    // --- ACCIÓN: DESACTIVAR LICENCIA ---
    case 'deactivate':
        $stmt_deactivate = $mysqli->prepare("DELETE FROM license_activations WHERE license_id = ? AND domain = ?");
        $stmt_deactivate->bind_param('is', $license['id'], $domain);
        if ($stmt_deactivate->execute()) {
            send_json_response(['success' => true, 'message' => 'license_deactivated']);
        } else {
            send_json_response(['success' => false, 'error' => 'deactivation_failed']);
        }
        $stmt_deactivate->close();
        break;

    // --- ACCIÓN: VERIFICAR LICENCIA ---
    case 'check':
        $domain_is_activated = is_domain_activated($mysqli, $license['id'], $domain);
        if (!$domain_is_activated) { send_json_response(['success' => false, 'error' => 'domain_not_activated']); }
        if ($license['status'] !== 'active') { send_json_response(['success' => false, 'error' => 'license_not_active', 'status' => $license['status']]); }
        if ($license['expires_at'] && new DateTime() > new DateTime($license['expires_at'])) { send_json_response(['success' => false, 'error' => 'license_expired']); }

        // Lógica de aviso de expiración
        $response_data = [
            'success' => true,
            'message' => 'license_valid',
            'expires_at' => $license['expires_at']
        ];
        
        $expiry_warning_days = 7; // Mostrar aviso si faltan 7 días o menos
        if ($license['expires_at']) {
            $today = new DateTime();
            $expiry_date = new DateTime($license['expires_at']);
            $diff = $today->diff($expiry_date);
            
            if (!$diff->invert && $diff->days <= $expiry_warning_days) {
                $days_left = $diff->days;
                if ($days_left == 0) {
                    $response_data['expiry_notice'] = 'Tu licencia para este plugin vence <strong>hoy</strong>. Renuévala para seguir recibiendo actualizaciones.';
                } else if ($days_left == 1) {
                    $response_data['expiry_notice'] = 'Tu licencia para este plugin vence en <strong>1 día</strong>. Renuévala pronto.';
                } else {
                    $response_data['expiry_notice'] = "Tu licencia para este plugin vence en <strong>{$days_left} días</strong>. Renuévala para no perder acceso a las actualizaciones.";
                }
            }
        }
        
        send_json_response($response_data);
        break;
    
    default:
        send_json_response(['success' => false, 'error' => 'invalid_action']);
        break;
}
?>