<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px; min-height: 100vh;">
    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="fas fa-cogs me-3 fs-4"></i>
        <span class="fs-4">Admin Panel</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        
        <li><a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : 'text-white'; ?>"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a></li>
        
        <li class="nav-item mt-3"><small class="text-muted ps-3">Ventas</small></li>
        <li><a href="orders.php" class="nav-link <?php echo ($current_page == 'orders.php') ? 'active' : 'text-white'; ?>"><i class="fas fa-file-invoice-dollar fa-fw me-2"></i> Órdenes de Compra</a></li>

        <li class="nav-item mt-3"><small class="text-muted ps-3">Gestión de Contenido</small></li>
        <li><a href="add-plugin.php" class="nav-link <?php echo ($current_page == 'add-plugin.php') ? 'active' : 'text-white'; ?>"><i class="fas fa-plus-circle fa-fw me-2"></i> Añadir Plugin</a></li>
        <li><a href="gestionar-plugins.php" class="nav-link <?php echo ($current_page == 'gestionar-plugins.php') ? 'active' : 'text-white'; ?>"><i class="fas fa-plug fa-fw me-2"></i> Gestionar Plugins</a></li>
        
        <li class="nav-item mt-3"><small class="text-muted ps-3">Gestión de Usuarios</small></li>
        <li><a href="manage-users.php" class="nav-link <?php echo ($current_page == 'manage-users.php') ? 'active' : 'text-white'; ?>"><i class="fas fa-users fa-fw me-2"></i> Usuarios Públicos</a></li>
        <li><a href="manage-admins.php" class="nav-link <?php echo in_array($current_page, ['manage-admins.php', 'add-admin.php', 'edit-admin.php']) ? 'active' : 'text-white'; ?>"><i class="fas fa-user-shield fa-fw me-2"></i> Administradores</a></li>

        <li class="nav-item mt-3"><small class="text-muted ps-3">Apariencia y Marketing</small></li>
        <li><a href="manage-menus.php" class="nav-link <?php echo ($current_page == 'manage-menus.php') ? 'active' : 'text-white'; ?>"><i class="fas fa-bars fa-fw me-2"></i> Menús</a></li>
        <li><a href="notifications.php" class="nav-link <?php echo ($current_page == 'notifications.php') ? 'active' : 'text-white'; ?>"><i class="fas fa-paper-plane fa-fw me-2"></i> Notificaciones</a></li>
        
        <li class="nav-item mt-3"><small class="text-muted ps-3">Sistema</small></li>
        <li><a href="settings.php" class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : 'text-white'; ?>"><i class="fas fa-sliders-h fa-fw me-2"></i> Ajustes Generales</a></li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle fa-fw me-2"></i><strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
            <li><a class="dropdown-item" href="logout.php">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>