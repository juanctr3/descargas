<?php
// admin/add-plugin.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Añadir Nuevo Plugin';
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = generate_slug($title);
    
    $update_identifier = trim($_POST['update_identifier']);
    if (empty($update_identifier)) {
        $update_identifier = $slug . '-' . uniqid();
    }

    $stmt_check = $mysqli->prepare("SELECT id FROM plugins WHERE update_identifier = ?");
    $stmt_check->bind_param('s', $update_identifier);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $error_message = 'El "Identificador para Actualizaciones" ya existe. Por favor, elige uno único.';
    }
    $stmt_check->close();

    if (empty($error_message)) {
        $short_description = trim($_POST['short_description']);
        $full_description = $_POST['full_description'];
        $version = trim($_POST['version']);
        $price = !empty($_POST['price']) ? trim($_POST['price']) : '0.00';
        $status = trim($_POST['status']);
        $requires_license = isset($_POST['requires_license']) ? 1 : 0;
        
        $video_url = trim($_POST['video_url']);
        $seo_title = trim($_POST['seo_title']);
        $seo_meta_description = trim($_POST['seo_meta_description']);
        $seo_meta_keywords = trim($_POST['seo_meta_keywords']);

        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image_file_name = uniqid('featured-', true) . '-' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/images/plugins/' . $new_image_file_name)) {
                $image_path = 'assets/images/plugins/' . $new_image_file_name;
            }
        }
        
        $plugin_file_path = '';
        $plugin_file_size = 0;
        if (isset($_FILES['plugin_file']) && $_FILES['plugin_file']['error'] === UPLOAD_ERR_OK) {
            $new_plugin_file_name = uniqid('plugin-', true) . '-' . basename($_FILES['plugin_file']['name']);
            $plugin_target_path = UPLOAD_PATH . $new_plugin_file_name;
            if (move_uploaded_file($_FILES['plugin_file']['tmp_name'], $plugin_target_path)) {
                $plugin_file_path = $new_plugin_file_name;
                $plugin_file_size = filesize($plugin_target_path);
            }
        }

        $gallery_images = [];
        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            foreach ($_FILES['gallery_images']['name'] as $key => $name) {
                if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['gallery_images']['tmp_name'][$key];
                    $new_gallery_file_name = uniqid('gallery-', true) . '-' . basename($name);
                    if (move_uploaded_file($tmp_name, __DIR__ . '/../assets/images/plugins/' . $new_gallery_file_name)) {
                        $gallery_images[] = 'assets/images/plugins/' . $new_gallery_file_name;
                    }
                }
            }
        }
        $gallery_images_json = json_encode($gallery_images);

        $stmt_insert = $mysqli->prepare("INSERT INTO plugins (update_identifier, title, slug, short_description, full_description, version, price, status, requires_license, image, file_path, file_size, gallery_images, video_url, seo_title, seo_meta_description, seo_meta_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param('ssssssdssssssssss', $update_identifier, $title, $slug, $short_description, $full_description, $version, $price, $status, $requires_license, $image_path, $plugin_file_path, $plugin_file_size, $gallery_images_json, $video_url, $seo_title, $seo_meta_description, $seo_meta_keywords);

        if ($stmt_insert->execute()) {
            $success_message = '¡Plugin añadido con éxito!';
        } else {
            $error_message = 'Error al añadir el plugin: ' . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include '_sidebar.php'; ?>
        <main class="w-100 p-4">
            <h1><?php echo $page_title; ?></h1>
            <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="card mb-4">
                    <div class="card-header">Información Principal</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Título del Plugin (*)</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="update_identifier" class="form-label">Identificador para Actualizaciones (*)</label>
                            <input type="text" class="form-control" id="update_identifier" name="update_identifier" required>
                            <div class="form-text">Un ID único y permanente para la API (ej: `mi-plugin-premium`). No cambiará aunque cambies el título.</div>
                        </div>
                        <div class="mb-3">
                            <label for="short_description" class="form-label">Descripción Corta</label>
                            <textarea class="form-control" id="short_description" name="short_description" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="full_description" class="form-label">Descripción Completa</label>
                            <textarea class="form-control" id="editor" name="full_description" rows="10"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3"><label for="version" class="form-label">Versión</label><input type="text" class="form-control" id="version" name="version" required></div>
                            <div class="col-md-3 mb-3"><label for="price" class="form-label">Precio (USD)</label><input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="0.00"></div>
                            <div class="col-md-3 mb-3"><label for="status" class="form-label">Estado</label><select class="form-select" id="status" name="status"><option value="active" selected>Activo</option><option value="inactive">Inactivo</option></select></div>
                            <div class="col-md-3 mb-3 align-self-center"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="requires_license" id="requires_license" value="1"><label class="form-check-label" for="requires_license">Requiere Licencia</label></div></div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4"><div class="card-header">Archivos</div><div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="image" class="form-label">Imagen Destacada</label><input class="form-control" type="file" id="image" name="image" accept="image/*"></div>
                        <div class="col-md-6 mb-3"><label for="plugin_file" class="form-label">Archivo del Plugin (.zip)</label><input class="form-control" type="file" id="plugin_file" name="plugin_file" accept=".zip,application/zip"></div>
                    </div>
                    <div class="mb-3"><label for="gallery_images" class="form-label">Imágenes de Galería</label><input class="form-control" type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*"></div>
                </div></div>

                <div class="card mb-4"><div class="card-header">Media Adicional</div><div class="card-body">
                    <div class="mb-3"><label for="video_url" class="form-label">URL del Video de YouTube</label><input type="url" class="form-control" id="video_url" name="video_url" placeholder="Pega aquí la URL de YouTube"></div>
                </div></div>
                
                <div class="card mb-4"><div class="card-header">Ajustes SEO</div><div class="card-body">
                    <div class="mb-3"><label for="seo_title" class="form-label">Título para SEO</label><input type="text" class="form-control" id="seo_title" name="seo_title"></div>
                    <div class="mb-3"><label for="seo_meta_description" class="form-label">Meta Descripción</label><textarea class="form-control" id="seo_meta_description" name="seo_meta_description" rows="3"></textarea></div>
                    <div class="mb-3"><label for="seo_meta_keywords" class="form-label">Palabras Clave</label><input type="text" class="form-control" id="seo_meta_keywords" name="seo_meta_keywords"></div>
                </div></div>

                <button type="submit" class="btn btn-primary">Guardar Plugin</button>
            </form>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        $('#editor').summernote({height: 300});
        document.getElementById('title').addEventListener('input', function() {
            let title = this.value;
            let slug = title.toString().toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]+/g, '').replace(/\-\-+/g, '-').replace(/^-+/, '').replace(/-+$/, '');
            document.getElementById('update_identifier').value = slug;
        });
    </script>
</body>
</html>