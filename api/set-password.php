<?php
// api/set-password.php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Verificamos que tengamos un user_id y una contraseña
$user_id = $_POST['user_id'] ?? null;
$password = $_POST['password'] ?? '';

if (empty($user_id) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
    exit();
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
    exit();
}

// Hasheamos la contraseña por seguridad
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('si', $password_hash, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '¡Contraseña guardada con éxito!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la contraseña.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error del servidor.']);
}
?>