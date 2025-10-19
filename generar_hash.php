<?php
// Contraseña que queremos encriptar
$password_clara = 'admin123';

// Usamos la función de PHP para crear un hash seguro
$nuevo_hash = password_hash($password_clara, PASSWORD_DEFAULT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Hash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { padding: 2rem; }</style>
</head>
<body>
    <div class="container">
        <h1 class="mb-3">Generador de Hash para la Contraseña</h1>
        <p>Contraseña a encriptar: <strong><?php echo htmlspecialchars($password_clara); ?></strong></p>
        <div class="alert alert-success">
            <label for="new_hash" class="form-label"><strong>NUEVO HASH GENERADO:</strong></label>
            <p>Copia la siguiente línea completa y pégala en la base de datos para el usuario 'admin'.</p>
            <textarea id="new_hash" rows="3" class="form-control" readonly onclick="this.select();"><?php echo htmlspecialchars($nuevo_hash); ?></textarea>
        </div>
    </div>
</body>
</html>