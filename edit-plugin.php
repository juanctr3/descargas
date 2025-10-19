<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Editar Plugin';
$error_message = ''; $success_message = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: gestionar-plugins.php');
    exit();
}
$plugin_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = generate_slug($title);
    $short_description = trim($_POST['short_description']);
    $full_description = $_POST['full_description'];
    $version = trim($_POST['version']);
    $price = !empty($_POST['price']) ? trim($_POST['price']) : '0.00';
    $status = trim($_POST['status']);
    
    // NUEVO: Recoger el valor del contador de descargas
    $download_count = (int)($_POST['download_count'] ?? 0);

    $video_url = trim($_POST['video_url']);
    $seo_title = trim($_POST['seo_title']);
    $seo_meta_description = trim($_POST['seo_meta_description']);
    $seo_meta_keywords = trim($_POST['seo_meta_keywords']);

    $current_gallery_images = !empty($_POST['current_gallery_images']) ? json_decode($_POST['current_gallery_images'], true) : [];
    $images_to_delete = $_POST['delete_images'] ?? [];
    foreach ($images_to_delete as $image_path) {
        if (($key = array_search($image_path, $current_gallery_images)) !== false) {
            if (file_exists(__DIR__ . '/../' . $image_path)) {
                unlink(__DIR__ . '/../' . $image_path);
            }
            unset($current_gallery_images[$key]);
        }
    }
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['name'] as $key => $name) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['gallery_images']['tmp_name'][$key];
                $new_gallery_file_name = uniqid('gallery-', true) . '-' . basename($name);
                if (move_uploaded_file($tmp_name, __DIR__ . '/../assets/images/plugins/' . $new_gallery_file_name)) {
                    $current_gallery_images[] = 'assets/images/plugins/' . $new_gallery_file_name;
                }
            }
        }
    }
    $gallery_images_json = json_encode(array_values($current_gallery_images));

    // NUEVO: Se añade download_count a la consulta de actualización
    $update_query = "UPDATE plugins SET title = ?, slug = ?, seo_title = ?, seo_meta_description = ?, seo_meta_keywords = ?, short_description = ?, full_description = ?, version = ?, price = ?, video_url = ?, gallery_images = ?, status = ?, download_count = ?";
    $params = [$title, $slug, $seo_title, $seo_meta_description, $seo_meta_keywords, $short_description, $full_description, $version, $price, $video_url, $gallery_images_json, $status, $download_count];
    $types = 'ssssssssdsssi'; // Se añade una 'i' al final para el entero

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $new_image_file_name = uniqid('featured-', true) . '-' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/images/plugins/' . $new_image_file_name)) {
            $update_query .= ", image = ?";
            $params[] = 'assets/images/plugins/' . $new_image_file_name;
            $types .= 's';
        }
    }
    if (isset($_FILES['plugin_file']) && $_FILES['plugin_file']['error'] === UPLOAD_ERR_OK) {
        $new_plugin_file_name = uniqid('plugin-', true) . '-' . basename($_FILES['plugin_file']['name']);
        $plugin_target_path = UPLOAD_PATH . $new_plugin_file_name;
        if (move_uploaded_file($_FILES['plugin_file']['tmp_name'], $plugin_target_path)) {
            $update_query .= ", file_path = ?, file_size = ?";
            $params[] = $new_plugin_file_name;
            $params[] = filesize($plugin_target_path);
            $types .= 'ss';
        }
    }

    $update_query .= " WHERE id = ?";
    $params[] = $plugin_id;
    $types .= 'i';

    $stmt_update = $mysqli->prepare($update_query);
    $stmt_update->bind_param($types, ...$params);
    
    if ($stmt_update->execute()) {
        $success_message = '¡Plugin actualizado con éxito!';
    } else {
        $error_message = 'Error al actualizar: ' . $stmt_update->error;
    }
    $stmt_update->close();
}

$stmt = $mysqli->prepare("SELECT * FROM plugins WHERE id = ?");
$stmt->bind_param('i', $plugin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: gestionar-plugins.php');
    exit();
}
$plugin = $result->fetch_assoc();
$gallery_images = !empty($plugin['gallery_images']) ? json_decode($plugin['gallery_images'], true) : [];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editando: <?php echo htmlspecialchars($plugin['title'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include '_sidebar.php'; ?>
        <main class="w-100 p-4">
            <h1 class="mb-4">Editando: <?php echo htmlspecialchars($plugin['title'] ?? ''); ?></h1>
            <?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?> <a href="manage-plugins.php" class="alert-link">Volver a la lista</a></div><?php endif; ?>

            <form method="POST" action="edit-plugin.php?id=<?php echo $plugin_id; ?>" enctype="multipart/form-data">
                <input type="hidden" name="current_gallery_images" value="<?php echo htmlspecialchars($plugin['gallery_images'] ?? '[]'); ?>">
                
                <div class="card mb-4"><div class="card-header">Información Principal</div><div class="card-body">
                    <div class="mb-3"><label for="title" class="form-label">Título del Plugin</label><input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($plugin['title'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label for="short_description" class="form-label">Descripción Corta</label><textarea class="form-control" id="short_description" name="short_description" rows="2" required><?php echo htmlspecialchars($plugin['short_description'] ?? ''); ?></textarea></div>
                    <div class="mb-3"><label for="full_description" class="form-label">Descripción Completa</label><textarea class="form-control" id="editor" name="full_description" rows="10"><?php echo htmlspecialchars($plugin['full_description'] ?? ''); ?></textarea></div>
                    <div class="row">
                        <div class="col-md-3 mb-3"><label for="version" class="form-label">Versión</label><input type="text" class="form-control" id="version" name="version" value="<?php echo htmlspecialchars($plugin['version'] ?? ''); ?>" required></div>
                        <div class="col-md-3 mb-3"><label for="price" class="form-label">Precio (USD)</label><input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($plugin['price'] ?? '0.00'); ?>"></div>
                        
                        <div class="col-md-3 mb-3"><label for="download_count" class="form-label">Contador de Descargas</label><input type="number" class="form-control" id="download_count" name="download_count" min="0" value="<?php echo htmlspecialchars($plugin['download_count'] ?? '0'); ?>"></div>
                        
                        <div class="col-md-3 mb-3"><label for="status" class="form-label">Estado</label><select class="form-select" id="status" name="status"><option value="active" <?php echo (($plugin['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Activo</option><option value="inactive" <?php echo (($plugin['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactivo</option></select></div>
                    </div>
                </div></div>

                <div class="card mb-4"><div class="card-header">Media Adicional</div><div class="card-body">
                    <div class="mb-3"><label for="video_url" class="form-label">URL del Video de YouTube</label><input type="url" class="form-control" id="video_url" name="video_url" value="<?php echo htmlspecialchars($plugin['video_url'] ?? ''); ?>" placeholder="Pega aquí la URL de YouTube"></div><hr>
                    <h6>Gestionar Galería de Imágenes</h6>
                    <?php if (!empty($gallery_images)): ?><div class="row">
                        <?php foreach ($gallery_images as $image): ?><div class="col-md-3 mb-3 text-center position-relative"><img src="../<?php echo htmlspecialchars($image); ?>?v=<?php echo time();?>" class="img-thumbnail mb-2" style="height: 100px; width: 100%; object-fit: cover;"><div class="form-check"><input class="form-check-input" type="checkbox" name="delete_images[]" value="<?php echo htmlspecialchars($image); ?>" id="del_<?php echo md5($image); ?>"><label class="form-check-label text-danger" for="del_<?php echo md5($image); ?>">Eliminar</label></div></div><?php endforeach; ?>
                        </div><?php else: ?><p class="text-muted">No hay imágenes en la galería.</p><?php endif; ?>
                    <div class="mb-3 mt-3"><label for="gallery_images" class="form-label">Añadir nuevas imágenes a la galería</label><input class="form-control" type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*"></div>
                </div></div>
                
                <div class="card mb-4"><div class="card-header">Ajustes SEO</div><div class="card-body">
                    <div class="mb-3"><label for="seo_title" class="form-label">Título para SEO</label><input type="text" class="form-control" id="seo_title" name="seo_title" value="<?php echo htmlspecialchars($plugin['seo_title'] ?? ''); ?>"></div>
                    <div class="mb-3"><label for="seo_meta_description" class="form-label">Meta Descripción</label><textarea class="form-control" id="seo_meta_description" name="seo_meta_description" rows="3"><?php echo htmlspecialchars($plugin['seo_meta_description'] ?? ''); ?></textarea></div>
                    <div class="mb-3"><label for="seo_meta_keywords" class="form-label">Palabras Clave</label><input type="text" class="form-control" id="seo_meta_keywords" name="seo_meta_keywords" value="<?php echo htmlspecialchars($plugin['seo_meta_keywords'] ?? ''); ?>"></div>
                </div></div>

                <div class="card"><div class="card-header">Archivos Principales (Opcional: reemplazar existentes)</div><div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="image" class="form-label">Reemplazar Imagen Destacada</label><input class="form-control" type="file" id="image" name="image" accept="image/*"></div>
                        <div class="col-md-6 mb-3"><label for="plugin_file" class="form-label">Reemplazar Archivo del Plugin (.zip)</label><input class="form-control" type="file" id="plugin_file" name="plugin_file" accept=".zip,application/zip"></div>
                    </div>
                </div></div>

                <a href="manage-plugins.php" class="btn btn-secondary mt-3">Cancelar</a>
                <button type="submit" class="btn btn-primary mt-3">Actualizar Plugin</button>
            </form>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>$(document).ready(function() {$('#editor').summernote({height: 300, toolbar: [['style', ['style']],['font', ['bold', 'underline', 'clear']],['color', ['color']],['para', ['ul', 'ol', 'paragraph']],['table', ['table']],['insert', ['link', 'picture', 'video']],['view', ['fullscreen', 'codeview', 'help']]]});});</script>
</body>
</html>