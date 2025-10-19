<?php
// api-documentation.php

require_once 'includes/config.php';

// --- VERIFICACIÓN DE SESIÓN DE USUARIO ---
// Si el usuario no ha iniciado sesión, se le redirige a la página de login.
if (!isset($_SESSION['user_id'])) {
    // Guardamos la URL actual para redirigir al usuario de vuelta después de iniciar sesión.
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . SITE_URL . '/login.php?redirect=' . $redirect_url);
    exit();
}
// --- FIN DE LA VERIFICACIÓN ---


$page_title = 'Documentación para Desarrolladores';
include 'includes/header.php';
?>
<style>
    :root { --bs-font-monospace: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    .endpoint { background-color: #e9ecef; padding: 0.5rem 1rem; border-radius: 0.25rem; font-family: var(--bs-font-monospace); word-break: break-all; }
    .param-table td, .param-table th { vertical-align: middle; }
    .nav-pills .nav-link.active { background-color: var(--bs-primary); }
    .nav-pills .nav-link { color: var(--bs-primary); }
    pre { background-color: #282c34; color: #abb2bf; padding: 1.2rem; border-radius: 0.5rem; white-space: pre-wrap; word-wrap: break-word; font-size: 0.9em; }
    code.inline { color: #e06c75; background-color: #f8f9fa; padding: 0.2em 0.4em; border-radius: 3px; font-size: 0.9em;}
    .text-success-emphasis { color: #198754 !important; font-weight: bold; }
    .text-danger-emphasis { color: #dc3545 !important; font-weight: bold; }
    .step { font-weight: bold; color: var(--bs-primary); font-size: 1.2em; }
</style>

<main class="container py-5 flex-shrink-0">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">Guía Completa para Desarrolladores</h1>
        <p class="lead text-muted">Integra tus plugins con nuestro ecosistema de licencias y actualizaciones automáticas.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <ul class="nav nav-pills card-header-pills" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pills-licenses" type="button">1. Integración de Licencias</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-updates" type="button">2. Integración de Actualizaciones</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-full-example" type="button">3. Ejemplo Completo</button></li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content p-3" id="pills-tabContent">
                
                <div class="tab-pane fade show active" id="pills-licenses" role="tabpanel">
                    <h2 class="h4 mb-3">Sistema de Licencias y Activación</h2>
                    <p>El sistema de licencias te permite proteger tu plugin, asegurando que solo los usuarios con una clave válida puedan acceder a sus funcionalidades completas y recibir soporte o actualizaciones.</p>
                    
                    <hr class="my-4">
                    
                    <h3 class="h5"><span class="step">Paso 1:</span> Entender el Endpoint de la API</h3>
                    <p>Todas las solicitudes de licencias (activar, desactivar, verificar) se gestionan a través de un único endpoint. Siempre debes enviar los datos vía <strong>POST</strong>.</p>
                    <p class="endpoint"><?php echo SITE_URL; ?>/api/license.php</p>

                    <h4 class="mt-4">Parámetros Requeridos</h4>
                    <table class="table table-bordered param-table">
                        <thead class="table-light"><tr><th>Parámetro</th><th>Descripción</th></tr></thead>
                        <tbody>
                            <tr><td><code class="inline">action</code></td><td>La acción a realizar: <code class="inline">activate</code>, <code class="inline">deactivate</code> o <code class="inline">check</code>.</td></tr>
                            <tr><td><code class="inline">license_key</code></td><td>La clave de licencia que el usuario introduce.</td></tr>
                            <tr><td><code class="inline">plugin_slug</code></td><td><strong>Crucial:</strong> El slug público de tu plugin (el que aparece en la URL del producto).</td></tr>
                            <tr><td><code class="inline">domain</code></td><td>El dominio del sitio del usuario. En WordPress, se obtiene de forma segura con <code class="inline">home_url()</code>.</td></tr>
                        </tbody>
                    </table>
                    
                    <h4 class="mt-4">Respuestas de la API</h4>
                    <p>La API siempre devolverá un objeto JSON. Una respuesta exitosa tendrá <code class="inline">"success": true</code>. Una fallida tendrá <code class="inline">"success": false</code> y un código de <code class="inline">error</code> para identificar el problema.</p>
                    
                    <h6>Posibles Códigos de Error:</h6>
                    <ul>
                        <li><code class="inline">invalid_license_key</code>: La clave no existe.</li>
                        <li><code class="inline">license_plugin_mismatch</code>: La clave es válida, pero para otro plugin.</li>
                        <li><code class="inline">license_not_active</code>: La licencia está inactiva, expirada o deshabilitada por un administrador.</li>
                        <li><code class="inline">license_expired</code>: La fecha de expiración ha pasado.</li>
                        <li><code class="inline">activation_limit_reached</code>: No quedan activaciones disponibles.</li>
                        <li><code class="inline">domain_not_activated</code>: Se intenta hacer un <code class="inline">check</code> en un dominio no registrado.</li>
                    </ul>
                    
                    <hr class="my-4">

                    <h3 class="h5"><span class="step">Paso 2:</span> Crear el Gestor de Licencias en tu Plugin</h3>
                    <p>Añade una página de ajustes a tu plugin donde el usuario pueda introducir su licencia. El siguiente código PHP crea una clase completa para gestionar esta página y toda la comunicación con la API, incluyendo los avisos de expiración.</p>
                    <p>Crea un archivo llamado <code class="inline">license-manager.php</code> en tu plugin y pega este código:</p>

<pre><code><?php echo htmlspecialchars(
'<?php
/**
 * Clase para gestionar la licencia y los avisos de expiración.
 */
class My_Plugin_License_Manager {

    private $plugin_slug;
    private $option_group = \'my_plugin_license_options\';
    private $page_slug = \'my-plugin-license\';
    private $transient_key = \'my_plugin_license_check\';

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        add_action(\'admin_menu\', [$this, \'add_license_page\']);
        add_action(\'admin_init\', [$this, \'register_settings\']);
        add_action(\'admin_notices\', [$this, \'show_expiry_notice\']);
    }
    
    public function show_expiry_notice() {
        $license_status = get_option(\'my_plugin_license_status\');
        $license_check_data = get_transient($this->transient_key);

        if ($license_status === \'active\' && !empty($license_check_data[\'expiry_notice\'])) {
            $message = $license_check_data[\'expiry_notice\'];
            printf(\'<div class="notice notice-warning is-dismissible"><p>%s</p></div>\', $message);
        }
    }

    public function add_license_page() {
        add_options_page(\'Licencia de Mi Plugin\', \'Mi Plugin Premium\', \'manage_options\', $this->page_slug, [$this, \'render_license_page\']);
    }

    public function render_license_page() {
        ?>
        <div class="wrap">
            <h1>Ajustes de Licencia de Mi Plugin Premium</h1>
            <?php settings_errors(); ?>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_group);
                do_settings_sections($this->page_slug);
                submit_button(\'Guardar Cambios\');
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting($this->option_group, \'my_plugin_license_key\', [$this, \'sanitize_and_validate_license\']);
        add_settings_section(\'my_plugin_license_section\', \'Estado de la Activación\', [$this, \'render_section_text\'], $this->page_slug);
        add_settings_field(\'my_plugin_license_key_field\', \'Clave de Licencia\', [$this, \'render_license_key_field\'], $this->page_slug, \'my_plugin_license_section\');
    }
    
    public function render_section_text() {
        $status = get_option(\'my_plugin_license_status\', \'inactive\');
        if ($status === \'active\') {
            echo \'<p style="color: green; font-weight: bold;">Tu licencia está activa. ¡Gracias por usar nuestro plugin!</p>\';
        } else {
            echo \'<p style="color: red; font-weight: bold;">Tu licencia está inactiva. Introduce una clave válida para activar las funcionalidades completas, el soporte y las actualizaciones.</p>\';
        }
    }

    public function render_license_key_field() {
        $license_key = get_option(\'my_plugin_license_key\', \'\');
        echo "<input type=\'text\' name=\'my_plugin_license_key\' value=\'" . esc_attr($license_key) . "\' class=\'regular-text\' placeholder=\'Pega tu clave de licencia aquí\' />";
    }

    public function sanitize_and_validate_license($new_key) {
        $old_key = get_option(\'my_plugin_license_key\');
        
        delete_transient($this->transient_key);

        if ($old_key && $new_key !== $old_key) {
            $this->call_api(\'deactivate\', $old_key);
            update_option(\'my_plugin_license_status\', \'inactive\');
        }

        if (empty($new_key)) {
            add_settings_error(\'my_plugin_license_options\', \'cleared\', \'Ajustes guardados. La licencia ha sido desactivada.\', \'updated\');
            return \'\';
        }

        $response = $this->call_api(\'activate\', $new_key);

        if (isset($response[\'success\']) && $response[\'success\']) {
            update_option(\'my_plugin_license_status\', \'active\');
            set_transient($this->transient_key, $response, DAY_IN_SECONDS);
            add_settings_error(\'my_plugin_license_options\', \'activated\', \'¡Licencia activada con éxito!\', \'updated\');
            return $new_key;
        } else {
            update_option(\'my_plugin_license_status\', \'inactive\');
            $error_message = \'Error al activar la licencia. Código: \' . ($response[\'error\'] ?? \'desconocido\');
            add_settings_error(\'my_plugin_license_options\', \'error\', $error_message, \'error\');
            return \'\';
        }
    }
    
    public function check_license_periodically() {
        if (get_transient($this->transient_key) === false) {
            $license_key = get_option(\'my_plugin_license_key\');
            if ($license_key) {
                $response = $this->call_api(\'check\', $license_key);
                if (isset($response[\'success\']) && $response[\'success\']) {
                    set_transient($this->transient_key, $response, DAY_IN_SECONDS); 
                } else {
                    update_option(\'my_plugin_license_status\', \'inactive\');
                }
            }
        }
    }

    private function call_api($action, $license_key) {
        $api_url = \'' . SITE_URL . '/api/license.php\';
        $response = wp_remote_post($api_url, [
            \'body\' => [ \'action\' => $action, \'license_key\' => $license_key, \'plugin_slug\' => $this->plugin_slug, \'domain\' => home_url() ],
            \'timeout\' => 20, \'sslverify\' => true,
        ]);
        if (is_wp_error($response)) { return [\'success\' => false, \'error\' => \'request_failed\']; }
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
'); ?></code></pre>
                </div>

                <div class="tab-pane fade" id="pills-updates" role="tabpanel">
                    <h2 class="h4 mb-3">Sistema de Actualizaciones Automáticas</h2>
                    <p>Permite a tus usuarios actualizar el plugin con un solo clic desde su panel de WordPress, igual que con los plugins del repositorio oficial. Este sistema es seguro y solo funcionará para usuarios con una licencia activa.</p>
                    
                    <hr class="my-4">

                    <h3 class="h5"><span class="step">Paso 1:</span> Entender el Endpoint de la API</h3>
                    <p>WordPress buscará actualizaciones enviando una petición <strong>POST</strong> al siguiente endpoint:</p>
                    <p class="endpoint"><?php echo SITE_URL; ?>/api/update.php</p>

                    <h4 class="mt-4">Parámetros Requeridos</h4>
                     <table class="table table-bordered param-table">
                        <thead class="table-light"><tr><th>Parámetro</th><th>Descripción</th></tr></thead>
                        <tbody>
                            <tr><td><code class="inline">action</code></td><td>Para el chequeo inicial, el valor siempre es <code class="inline">plugin_information</code>.</td></tr>
                            <tr><td><code class="inline">update_identifier</code></td><td><strong>Crucial:</strong> El "Identificador para Actualizaciones" que definiste en el panel de administración. Este valor es permanente.</td></tr>
                            <tr><td><code class="inline">version</code></td><td>La versión actual del plugin instalada en el sitio del usuario.</td></tr>
                            <tr><td><code class="inline">license_key</code></td><td>La clave de licencia activa del usuario. Si es inválida, no se proporcionará el enlace de descarga.</td></tr>
                        </tbody>
                    </table>

                    <hr class="my-4">

                    <h3 class="h5"><span class="step">Paso 2:</span> Implementar el Código del Actualizador</h3>
                    <p>La siguiente clase se integra con los filtros de WordPress para gestionar el proceso de actualización. Crea un archivo llamado <code class="inline">updater.php</code> en tu plugin y pega este código:</p>
<pre><code><?php echo htmlspecialchars(
'<?php
/**
 * Clase para gestionar las actualizaciones automáticas del plugin.
 */
class My_Plugin_Auto_Updater {

    private $api_url;
    private $update_identifier;
    private $plugin_version;
    private $license_key;
    private $plugin_file_basename;

    public function __construct($plugin_file, $update_identifier, $plugin_version, $license_key) {
        $this->api_url = \'' . SITE_URL . '/api/update.php\';
        $this->plugin_file_basename = plugin_basename($plugin_file);
        $this->update_identifier = $update_identifier;
        $this->plugin_version = $plugin_version;
        $this->license_key = $license_key;

        add_filter(\'pre_set_site_transient_update_plugins\', [$this, \'check_for_updates\']);
    }

    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $response = $this->call_api(\'plugin_information\');

        if ($response && version_compare($this->plugin_version, $response->new_version, \'<\')) {
            $transient->response[$this->plugin_file_basename] = $response;
        }

        return $transient;
    }
    
    private function call_api($action) {
        $payload = [
            \'body\' => [
                \'action\' => $action,
                \'update_identifier\' => $this->update_identifier,
                \'version\' => $this->plugin_version,
                \'license_key\' => $this->license_key,
            ],
            \'timeout\' => 20, \'sslverify\' => true,
        ];
        
        $request = wp_remote_post($this->api_url, $payload);
        
        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) !== 200) {
            return false;
        }
        
        $response = json_decode(wp_remote_retrieve_body($request));
        
        if (is_object($response) && !empty($response)) {
            $response->plugin = $this->plugin_file_basename;
            return $response;
        }

        return false;
    }
}
'); ?></code></pre>
                </div>

                <div class="tab-pane fade" id="pills-full-example" role="tabpanel">
                    <h2 class="h4 mb-3">Ejemplo de Integración Completa</h2>
                    <p>Este es un ejemplo de cómo deberías estructurar tu archivo principal del plugin para inicializar ambos sistemas: el gestor de licencias y el actualizador automático.</p>
<pre><code><?php echo htmlspecialchars(
'<?php
/**
 * Plugin Name: Mi Plugin Premium
 * Plugin URI:  https://tusitio.com/plugins/mi-plugin-premium
 * Description: Un plugin de ejemplo que integra licencias y actualizaciones.
 * Version:     1.0.0
 * Author:      Tu Nombre
 * Author URI:  https://tusitio.com
 */

// Evitar acceso directo
if ( ! defined( \'ABSPATH\' ) ) {
    exit;
}

// 1. Define las constantes de tu plugin
define( \'MY_PLUGIN_FILE\', __FILE__ );
define( \'MY_PLUGIN_VERSION\', \'1.0.0\' );
define( \'MY_PLUGIN_SLUG\', \'mi-plugin-premium\' ); // Slug PÚBLICO del plugin
define( \'MY_PLUGIN_UPDATE_ID\', \'mi-plugin-premium-stable\' ); // Identificador ÚNICO y PERMANENTE para actualizaciones

// 2. Incluye los archivos del gestor de licencia y del actualizador
require_once __DIR__ . \'/license-manager.php\';
require_once __DIR__ . \'/updater.php\';

/**
 * Función para inicializar los sistemas del plugin.
 */
function my_plugin_init() {
    // Instanciar el gestor de licencias
    $license_manager = new My_Plugin_License_Manager( MY_PLUGIN_SLUG );

    // OBTENER DATOS DE LICENCIA
    $license_key = get_option(\'my_plugin_license_key\');
    $license_status = get_option(\'my_plugin_license_status\');
    
    // Iniciar el actualizador solo si la licencia está activa
    if ($license_status === \'active\' && !empty($license_key)) {
        new My_Plugin_Auto_Updater(
            MY_PLUGIN_FILE,
            MY_PLUGIN_UPDATE_ID,
            MY_PLUGIN_VERSION,
            $license_key
        );
    }
    
    // Ejecutar la verificación periódica de la licencia en el panel de admin
    if (is_admin()) {
        $license_manager->check_license_periodically();
    }
}
add_action(\'plugins_loaded\', \'my_plugin_init\');

/**
 * Función de ejemplo para comprobar si la licencia está activa.
 * Puedes usarla para bloquear funcionalidades premium.
 */
function my_plugin_is_license_active() {
    return get_option(\'my_plugin_license_status\') === \'active\';
}

// Ejemplo de cómo proteger una funcionalidad:
/*
if ( my_plugin_is_license_active() ) {
    // Cargar aquí las funcionalidades premium
    require_once __DIR__ . \'/includes/premium-features.php\';
}
*/
'); ?></code></pre>
                </div>

            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>