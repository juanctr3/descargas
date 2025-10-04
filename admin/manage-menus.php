<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Gestionar Menús';
$feedback_message = ''; $message_class = '';
$edit_mode = false;
$link_to_edit = ['id' => '', 'link_text' => '', 'link_url' => '', 'icon_class' => '', 'link_location' => 'header', 'link_order' => 0, 'open_in_new_tab' => 0];

if (isset($_POST['save_link'])) {
    $link_id = $_POST['link_id'] ?? null;
    $link_text = trim($_POST['link_text']);
    $link_url = trim($_POST['link_url']);
    $icon_class = trim($_POST['icon_class']);
    $link_location = $_POST['link_location'];
    $link_order = (int)($_POST['link_order'] ?? 0);
    $open_in_new_tab = isset($_POST['open_in_new_tab']) ? 1 : 0;

    if (!empty($link_text) && !empty($link_url)) {
        if (!empty($link_id)) {
            $stmt = $mysqli->prepare("UPDATE menus SET link_text = ?, link_url = ?, icon_class = ?, link_location = ?, link_order = ?, open_in_new_tab = ? WHERE id = ?");
            $stmt->bind_param('ssssiii', $link_text, $link_url, $icon_class, $link_location, $link_order, $open_in_new_tab, $link_id);
            $feedback_message = 'Enlace actualizado con éxito.';
        } else {
            $stmt = $mysqli->prepare("INSERT INTO menus (link_text, link_url, icon_class, link_location, link_order, open_in_new_tab) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssii', $link_text, $link_url, $icon_class, $link_location, $link_order, $open_in_new_tab);
            $feedback_message = 'Enlace añadido con éxito.';
        }
        if ($stmt->execute()) { $message_class = 'alert-success'; } else { $feedback_message = 'Error al guardar el enlace: ' . $stmt->error; $message_class = 'alert-danger'; }
        $stmt->close();
    } else { $feedback_message = 'El texto y la URL del enlace no pueden estar vacíos.'; $message_class = 'alert-danger'; }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $mysqli->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->bind_param('i', $_GET['delete']);
    if ($stmt->execute()) { $feedback_message = 'Enlace eliminado con éxito.'; $message_class = 'alert-success'; } else { $feedback_message = 'Error al eliminar el enlace.'; $message_class = 'alert-danger'; }
    $stmt->close();
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $stmt = $mysqli->prepare("SELECT * FROM menus WHERE id = ?");
    $stmt->bind_param('i', $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) { $link_to_edit = $result->fetch_assoc(); }
    $stmt->close();
}

$header_links_result = $mysqli->query("SELECT * FROM menus WHERE link_location = 'header' ORDER BY link_order ASC, id ASC");
$header_links = $header_links_result->fetch_all(MYSQLI_ASSOC);
$footer_links_result = $mysqli->query("SELECT * FROM menus WHERE link_location = 'footer' ORDER BY link_order ASC, id ASC");
$footer_links = $footer_links_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="d-flex">
        <?php include '_sidebar.php'; ?>
        <main class="w-100 p-4">
            <h1 class="mb-4"><?php echo htmlspecialchars($page_title); ?></h1>
            <?php if ($feedback_message): ?><div class="alert <?php echo $message_class; ?>"><?php echo $feedback_message; ?></div><?php endif; ?>

            <div class="card mb-4"><div class="card-header fw-bold"><?php echo $edit_mode ? 'Editando Enlace' : 'Añadir Nuevo Enlace'; ?></div><div class="card-body">
                <form method="POST" action="manage-menus.php">
                    <input type="hidden" name="link_id" value="<?php echo htmlspecialchars($link_to_edit['id']); ?>">
                    
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="link_text" class="form-label">Texto del Enlace</label><input type="text" class="form-control" id="link_text" name="link_text" value="<?php echo htmlspecialchars($link_to_edit['link_text']); ?>" required></div>
                        <div class="col-md-5 mb-3"><label for="link_url" class="form-label">URL del Enlace</label><input type="text" class="form-control" id="link_url" name="link_url" value="<?php echo htmlspecialchars($link_to_edit['link_url']); ?>" required></div>
                        <div class="col-md-3 mb-3"><label for="link_order" class="form-label">Orden</label><input type="number" class="form-control" id="link_order" name="link_order" value="<?php echo htmlspecialchars($link_to_edit['link_order']); ?>"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="icon_class" class="form-label">Clase del Icono (Opcional)</label><input type="text" class="form-control" id="icon_class" name="icon_class" value="<?php echo htmlspecialchars($link_to_edit['icon_class']); ?>"><div class="form-text">Ej: <code>fas fa-home</code>. Busca en <a href="https://fontawesome.com/search?m=free" target="_blank">FontAwesome</a>.</div></div>
                        <div class="col-md-4 mb-3"><label for="link_location" class="form-label">Ubicación</label><select class="form-select" name="link_location"><option value="header" <?php echo ($link_to_edit['link_location'] == 'header') ? 'selected' : ''; ?>>Encabezado</option><option value="footer" <?php echo ($link_to_edit['link_location'] == 'footer') ? 'selected' : ''; ?>>Pie de Página</option></select></div>
                        <div class="col-md-4 d-flex align-items-center pt-3">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="open_in_new_tab" id="open_in_new_tab" value="1" <?php echo ($link_to_edit['open_in_new_tab'] == 1) ? 'checked' : ''; ?>><label class="form-check-label" for="open_in_new_tab">Abrir en una nueva pestaña</label></div>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" name="save_link" class="btn btn-primary"><?php echo $edit_mode ? 'Actualizar Enlace' : 'Añadir Enlace'; ?></button>
                    <?php if ($edit_mode): ?><a href="manage-menus.php" class="btn btn-secondary">Cancelar Edición</a><?php endif; ?>
                </form>
            </div></div>

            <div class="row">
                <div class="col-lg-6 mb-4"><div class="card"><div class="card-header">Enlaces del Encabezado</div><div class="card-body"><div class="table-responsive">
                    <table class="table table-sm table-striped"><thead><tr><th>Orden</th><th>Texto del Enlace</th><th>Acciones</th></tr></thead><tbody>
                        <?php if (empty($header_links)): ?> <tr><td colspan="3" class="text-center text-muted">No hay enlaces en el encabezado.</td></tr> <?php endif; ?>
                        <?php foreach ($header_links as $link): ?>
                            <tr><td><?php echo $link['link_order']; ?></td><td><i class="<?php echo htmlspecialchars($link['icon_class']); ?> me-2 text-muted"></i><?php echo htmlspecialchars($link['link_text']); ?></td><td><a href="?edit=<?php echo $link['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a> <a href="?delete=<?php echo $link['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')"><i class="fas fa-trash"></i></a></td></tr>
                        <?php endforeach; ?>
                    </tbody></table>
                </div></div></div></div>
                <div class="col-lg-6 mb-4"><div class="card"><div class="card-header">Enlaces del Pie de Página</div><div class="card-body"><div class="table-responsive">
                    <table class="table table-sm table-striped"><thead><tr><th>Orden</th><th>Texto del Enlace</th><th>Acciones</th></tr></thead><tbody>
                        <?php if (empty($footer_links)): ?> <tr><td colspan="3" class="text-center text-muted">No hay enlaces en el pie de página.</td></tr> <?php endif; ?>
                        <?php foreach ($footer_links as $link): ?>
                            <tr><td><?php echo $link['link_order']; ?></td><td><i class="<?php echo htmlspecialchars($link['icon_class']); ?> me-2 text-muted"></i><?php echo htmlspecialchars($link['link_text']); ?></td><td><a href="?edit=<?php echo $link['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a> <a href="?delete=<?php echo $link['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')"><i class="fas fa-trash"></i></a></td></tr>
                        <?php endforeach; ?>
                    </tbody></table>
                </div></div></div></div>
        </main>
    </div>
</body>
</html>