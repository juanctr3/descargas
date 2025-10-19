<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';
$page_title = 'Añadir Nuevo Plugin';
$error_message = ''; $success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $short_description = trim($_POST['short_description']);
    $full_description = $_POST['full_description'];
    $version = trim($_POST['version']);
    $status = trim($_POST['status']);
    $price = trim($_POST['price']);
    $video_url = trim($_POST['video_url']);
    $seo_title = trim($_POST['seo_title']);
    $seo_meta_description = trim($_POST['seo_meta_description']);
    $seo_meta_keywords = trim($_POST['seo_meta_keywords']);

    if (empty($title) || empty($short_description) || empty($version) || empty($_FILES['plugin_file']['name'])) {
        $error_message = 'Título, Descripción Corta, Versión y Archivo del Plugin son obligatorios.';
    } else {
        $base_slug = generate_slug($title);
        $slug = $base_slug; $counter = 1;
        while (true) {
            $stmt_check = $mysqli->prepare("SELECT id FROM plugins WHERE slug = ?");
            $stmt_check->bind_param('s', $slug);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows === 0) { $stmt_check->close(); break; }
            $stmt_check->close(); $counter++; $slug = $base_slug . '-' . $counter;
        }

        $image_target_path_db = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image_file_name = uniqid('featured-', true) . '-' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/images/plugins/' . $new_image_file_name)) {
                $image_target_path_db = 'assets/images/plugins/' . $new_image_file_name;
            } else { $error_message = 'Error al subir la imagen destacada.'; }
        }

        $gallery_paths = [];
        if (empty($error_message) && isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            foreach ($_FILES['gallery_images']['name'] as $key => $name) {
                if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['gallery_images']['tmp_name'][$key];
                    $new_gallery_file_name = uniqid('gallery-', true) . '-' . basename($name);
                    if (move_uploaded_file($tmp_name, __DIR__ . '/../assets/images/plugins/' . $new_gallery_file_name)) {
                        $gallery_paths[] = 'assets/images/plugins/' . $new_gallery_file_name;
                    }
                }
            }
        }
        $gallery_images_json = json_encode($gallery_paths);

        $new_plugin_file_name = ''; $file_size = '0';
        if (empty($error_message) && isset($_FILES['plugin_file']) && $_FILES['plugin_file']['error'] === UPLOAD_ERR_OK) {
            $new_plugin_file_name = uniqid('plugin-', true) . '-' . basename($_FILES['plugin_file']['name']);
            $plugin_target_path = UPLOAD_PATH . $new_plugin_file_name;
            if (move_uploaded_file($_FILES['plugin_file']['tmp_name'], $plugin_target_path)) {
                $file_size = filesize($plugin_target_path);
            } else { $error_message = 'Error al subir el archivo del plugin.'; }
        }

        if (empty($error_message)) {
            $stmt = $mysqli->prepare("INSERT INTO plugins (title, slug, seo_title, seo_meta_description, seo_meta_keywords, short_description, full_description, version, price, image, video_url, gallery_images, file_path, file_size, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssssdssssss', $title, $slug, $seo_title, $seo_meta_description, $seo_meta_keywords, $short_description, $full_description, $version, $price, $image_target_path_db, $video_url, $gallery_images_json, $new_plugin_file_name, $file_size, $status);
            if ($stmt->execute()) {
                $success_message = '¡Plugin añadido con éxito!';
            } else { $error_message = 'Error al guardar en la base de datos: ' . $stmt->error; }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include '_sidebar.php'; ?>
        <main class="w-100 p-4">
            <h1 class="mb-4"><?php echo htmlspecialchars($page_title); ?></h1>
            <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
            <form method="POST" action="add-plugin.php" enctype="multipart/form-data">
                <div class="card mb-4"><div class="card-header">Información Principal</div><div class="card-body">
                    <div class="mb-3"><label for="title" class="form-label">Título del Plugin</label><input type="text" class="form-control" id="title" name="title" required></div>
                    <div class="mb-3"><label for="short_description" class="form-label">Descripción Corta</label><textarea class="form-control" id="short_description" name="short_description" rows="2" required></textarea></div>
                    <div class="mb-3"><label for="full_description" class="form-label">Descripción Completa</label><textarea class="form-control" id="editor" name="full_description" rows="10"></textarea></div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="version" class="form-label">Versión (ej. 1.0.0)</label><input type="text" class="form-control" id="version" name="version" required></div>
                        <div class="col-md-4 mb-3"><label for="price" class="form-label">Precio (USD)</label><input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="0.00"><div class="form-text">Pon 0.00 o déjalo vacío para que sea gratuito.</div></div>
                        <div class="col-md-4 mb-3"><label for="status" class="form-label">Estado</label><select class="form-select" id="status" name="status"><option value="active" selected>Activo</option><option value="inactive">Inactivo</option></select></div>
                    </div>
                </div></div>
                <div class="card mb-4"><div class="card-header">Media Adicional</div><div class="card-body">
                    <div class="mb-3"><label for="video_url" class="form-label">URL del Video de YouTube (Opcional)</label><input type="url" class="form-control" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=VIDEO_ID"></div>
                    <div class="mb-3"><label for="gallery_images" class="form-label">Imágenes para la Galería (Opcional)</label><input class="form-control" type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*"></div>
                </div></div>
                <div class="card mb-4"><div class="card-header">Ajustes SEO</div><div class="card-body">
                    <div class="mb-3"><label for="seo_title" class="form-label">Título para SEO</label><input type="text" class="form-control" id="seo_title" name="seo_title"><div class="form-text">Si se deja en blanco, se usará el título del plugin.</div></div>
                    <div class="mb-3"><label for="seo_meta_description" class="form-label">Meta Descripción para SEO</label><textarea class="form-control" id="seo_meta_description" name="seo_meta_description" rows="3"></textarea><div class="form-text">La descripción que aparecerá en Google.</div></div>
                    <div class="mb-3"><label for="seo_meta_keywords" class="form-label">Palabras Clave para SEO</label><input type="text" class="form-control" id="seo_meta_keywords" name="seo_meta_keywords"><div class="form-text">Separadas por comas.</div></div>
                </div></div>
                <div class="card"><div class="card-header">Archivos Principales</div><div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="image" class="form-label">Imagen Destacada</label><input class="form-control" type="file" id="image" name="image" accept="image/*"></div>
                        <div class="col-md-6 mb-3"><label for="plugin_file" class="form-label">Archivo del Plugin (.zip)</label><input class="form-control" type="file" id="plugin_file" name="plugin_file" accept=".zip,application/zip" required></div>
                    </div>
                </div></div>
                <button type="submit" class="btn btn-primary mt-3">Añadir Plugin</button>
            </form>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>$(document).ready(function() { $('#editor').summernote({height: 300}); });</script>
</body>
</html>