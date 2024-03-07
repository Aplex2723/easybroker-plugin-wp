<?php
/**
 * Plugin Name: Mi Plugin Inmobiliario
 * Description: Plugin para integrar APIs inmobiliarias con un post type personalizado.
 * Version: 1.0
 * Author: Codey Latinoamerica
 */

// Asegurarse de que WordPress no acceda a este script directamente.
if (!defined('ABSPATH')) exit;

// Registrar la función de activación del plugin.
register_activation_hook(__FILE__, 'mi_plugin_inmobiliario_activar');

// Registrar la función de desactivación del plugin.
register_deactivation_hook(__FILE__, 'mi_plugin_inmobiliario_desactivar');

// Función llamada al activar el plugin.
function mi_plugin_inmobiliario_activar() {
    // Al inicializar el plugin, creamos la base de datos correspondiente
    global $wpdb;
    //! Cambiar esto a la tabla de produccion
    $tabla_nombre = $wpdb->prefix . 'mi_plugin_inmobiliario_config'; // Prefijo de WP y nombre de la tabla.

    // SQL para crear la tabla.
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $tabla_nombre (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        api_key varchar(255) NOT NULL,
        api varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    //* Crando la tabla para los meta fields
    $meta_table_name = $wpdb->prefix . 'ebp_meta_fields';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $meta_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        meta_field_key varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($sql);
}

// Función llamada al desactivar el plugin.
function mi_plugin_inmobiliario_desactivar() {
    // Lógica de desactivación, si es necesaria.
}

// Hook para agregar el menú del plugin al Dashboard.
add_action('admin_menu', 'mi_plugin_inmobiliario_menu');

// Función para agregar el menú del plugin.
function mi_plugin_inmobiliario_menu() {
    add_menu_page('Configuración Plugin Inmobiliario', 'Plugin Inmobiliario', 'manage_options', 'mi-plugin-inmobiliario-config', 'mi_plugin_inmobiliario_config_page');
}

// Función para mostrar la página de configuración del plugin.
function mi_plugin_inmobiliario_config_page() {
    wp_nonce_field('ebp_nonce_meta_fields', 'ebp_meta_fields_nonce');
    // Obtén los meta fields almacenados
    $meta_fields = get_option('ebp_meta_fields', []);

    global $wpdb;
    $table_name = $wpdb->prefix . 'ebp_meta_fields';

    // Recupera los meta fields de la base de datos
    $meta_fields = $wpdb->get_results("SELECT id, meta_field_key FROM $table_name", OBJECT);

    ?>
    <div class="wrap">
        <h2>Configuración Plugin Inmobiliario</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('mi-plugin-inmobiliario-settings-group');
            do_settings_sections('mi-plugin-inmobiliario-config');
            ?>
            <h3>Meta Fields</h3>
            <table id="meta-fields-table">
                <thead>
                    <tr>
                        <th>Meta Field Key</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($meta_fields as $field): ?>
                    <tr>
                        <td><input type="text" name="ebp_meta_fields[]" value="<?php echo esc_attr($field->meta_field_key); ?>" /></td>
                        <td><button class="button remove-row" data-id="<?php echo esc_attr($field->id); ?>">Eliminar</button></td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="empty-row screen-reader-text">
                        <td><input type="text" name="ebp_meta_fields[]" /></td>
                        <td><button class="button remove-row">Eliminar</button></td>
                    </tr>
                </tbody>
            </table>
            <button id="add-row" class="button">Añadir Meta Field</button>
        </form>
        
        <button type="button" id="obtener-propiedades" class="button-primary">Obtener Propiedades</button>
        <button type="button" id="save-meta-fields" class="button-primary">Guardar Cambios</button>
     
        <!-- Aquí va tu script existente para obtener propiedades -->
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#obtener-propiedades').click(function() {
                var data = {
                    'action': 'obtener_propiedades_ajax',
                    'api': $('#mi_plugin_inmobiliario_api').val(),
                    'api_key': $('#mi_plugin_inmobiliario_api_key').val(),
                };
                
                $.post(ajaxurl, data, function(response) {
                    alert('Propiedades obtenidas: ' + response);
                });
            });
        });
        </script>
    
    <!-- Script para la gestion de contenido -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#save-meta-fields').click(function(e) {
            e.preventDefault();

            var metaFields = [];
            $("input[name='ebp_meta_fields[]']").each(function() {
                metaFields.push($(this).val());
            });

            var data = {
                'action': 'guardar_meta_fields_ajax',
                'meta_fields': metaFields,
                'nonce': $('#ebp_meta_fields_nonce').val()
            };

            $.post(ajaxurl, data, function(response) {
                alert('Cambios guardados');
                // Aquí puedes manejar la respuesta, como recargar la página para mostrar los cambios
            });
        });
    });
    </script>

    <!-- Logica para eliminar los campos no deseados mas -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.remove-row').on('click', function(e) {
            e.preventDefault();

            var row = $(this).closest('tr');
            var id = $(this).data('id');

            if(!id){
                row.remove()
            } else {
                var data = {
                    'action': 'ebp_eliminar_meta_field',
                    'id': id,
                    'nonce': $('#ebp_meta_fields_nonce').val()
                };
    
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        row.remove();
                        alert('Meta Field eliminado');
                    } else {
                        alert('Error al eliminar Meta Field');
                    }
                });
            }

        });
    });
    </script>

    <!-- Anadir nueva fila script -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#add-row').click(function(e) {
            e.preventDefault();
            var row = $('#meta-fields-table .empty-row').clone(true);
            row.removeClass('empty-row screen-reader-text');
            row.insertBefore('#meta-fields-table tbody>tr:last');
        });
        
        $('.remove-row').click(function(e) {
            e.preventDefault();
            $(this).closest('tr').remove();
        });
    });
    </script>
    <?php
}

// Hook para registrar las opciones de configuración.
add_action('admin_init', 'mi_plugin_inmobiliario_settings');

// Función para registrar las opciones y secciones de configuración.
function mi_plugin_inmobiliario_settings() {
    register_setting('mi-plugin-inmobiliario-settings-group', 'mi_plugin_inmobiliario_api');
    register_setting('mi-plugin-inmobiliario-settings-group', 'mi_plugin_inmobiliario_api_key');
    register_setting('mi-plugin-inmobiliario-settings-group', 'ebp_meta_fields', 'ebp_sanitize_meta_fields');
    // Registra aquí más configuraciones según sea necesario.

    add_settings_section('mi-plugin-inmobiliario-settings-section', 'Configuración API', 'mi_plugin_inmobiliario_settings_section_callback', 'mi-plugin-inmobiliario-config');
    add_settings_field('mi_plugin_inmobiliario_api', 'API a usar', 'mi_plugin_inmobiliario_api_callback', 'mi-plugin-inmobiliario-config', 'mi-plugin-inmobiliario-settings-section');
    add_settings_field('mi_plugin_inmobiliario_api_key', 'API Key', 'mi_plugin_inmobiliario_api_key_callback', 'mi-plugin-inmobiliario-config', 'mi-plugin-inmobiliario-settings-section');
    // Añade más campos de configuración según sea necesario.
}

//* Funcionalidad para los metafields personalizados
function ebp_sanitize_meta_fields($input) {
    // Sanitiza cada meta field
    return array_map('sanitize_text_field', $input);
}

add_action('wp_ajax_guardar_meta_fields_ajax', 'ebp_guardar_meta_fields');

function ebp_guardar_meta_fields() {
    // Verifica el nonce por seguridad
    check_ajax_referer('ebp_nonce_meta_fields', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'ebp_meta_fields';
    $meta_fields = isset($_POST['meta_fields']) ? $_POST['meta_fields'] : [];

    // Vacía la tabla antes de insertar los nuevos valores
    $wpdb->query("TRUNCATE TABLE $table_name");

    // Inserta los nuevos valores
    foreach ($meta_fields as $field) {
        if (!empty($field)) { // Asegúrate de que el campo no esté vacío
            $wpdb->insert($table_name, ['meta_field_key' => sanitize_text_field($field)]);
        }
    }

    wp_send_json_success('Meta Fields guardados correctamente');
}

//* Eliminacion de Meta Fields ya no deseados
add_action('wp_ajax_ebp_eliminar_meta_field', 'ebp_eliminar_meta_field');

function ebp_eliminar_meta_field() {
    // Verifica el nonce por seguridad
    check_ajax_referer('ebp_nonce_meta_fields', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes suficientes permisos para realizar esta acción');
    }

    $meta_field_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    global $wpdb;
    $table_name = $wpdb->prefix . 'ebp_meta_fields';

    $result = $wpdb->delete($table_name, array( 'id' => $meta_field_id ), array( '%d' ));

    if ($result) {
        wp_send_json_success('Meta Field eliminado correctamente');
    } else {
        wp_send_json_error('Error al eliminar Meta Field ' . $result );
    }
}


//* Seccion de subtitulo encima de los campos
function mi_plugin_inmobiliario_settings_section_callback() {
    echo 'Selecciona la API y configura las opciones necesarias.';
}

// Funcion que maneja la base de datos y obtiene los valores guardados si es que existen
function obtener_valor_configuracion($clave) {
    global $wpdb;
    //! Cambiar esto a la tabla de produccion
    $tabla_nombre = $wpdb->prefix . 'mi_plugin_inmobiliario_config';
    
    // Intenta recuperar el valor de la configuración por su clave (api o api_key)
    $valor = $wpdb->get_var($wpdb->prepare("SELECT $clave FROM $tabla_nombre LIMIT 1"));
    
    // Si el valor existe, devuélvelo. De lo contrario, devuelve una cadena vacía.
    return $valor ? $valor : '';
}

function mi_plugin_inmobiliario_api_callback() {
    // Obtén el valor actual de la API desde la base de datos.
    $api = obtener_valor_configuracion('api');
    echo '<input type="text" id="mi_plugin_inmobiliario_api" name="mi_plugin_inmobiliario_api" value="' . esc_attr($api) . '" />';
}

function mi_plugin_inmobiliario_api_key_callback() {
    // Obtén el valor actual de la API Key desde la base de datos.
    $apiKey = obtener_valor_configuracion('api_key');
    echo '<input type="text" id="mi_plugin_inmobiliario_api_key" name="mi_plugin_inmobiliario_api_key" value="' . esc_attr($apiKey) . '" />';
}


// Aquí puedes añadir más callbacks para los campos de configuración según sea necesario.


add_action('wp_ajax_obtener_propiedades_ajax', 'mi_plugin_inmobiliario_obtener_propiedades');

function mi_plugin_inmobiliario_obtener_propiedades() {
    global $wpdb;
    // Asegúrate de que solo usuarios autorizados puedan ejecutar esta acción.
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permiso para realizar esta acción.');
    }

    $api = isset($_POST['api']) ? sanitize_text_field($_POST['api']) : '';
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

    $tabla_nombre = $wpdb->prefix . 'mi_plugin_inmobiliario_config';

    // Verificar si ya existe un registro.
    $existe = $wpdb->get_var("SELECT id FROM $tabla_nombre LIMIT 1");

    if ($existe) {
        // Si existe, actualizar.
        $wpdb->update(
            $tabla_nombre,
            ['api_key' => $api_key, 'api' => $api], // Valores a actualizar.
            ['id' => $existe] // Condición para actualizar.
        );
    } else {
        // Si no existe, insertar.
        $wpdb->insert(
            $tabla_nombre,
            ['api_key' => $api_key, 'api' => $api]
        );
    }

    // Asume que $api contiene la URL base de la API. Ajusta según sea necesario.
    $url = $api;

    // Inicializar sesión cURL
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'X-Authorization: ' . $api_key,
        'Accept: application/json'
    ]);

    // Ejecutar solicitud cURL
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo 'Error cURL: ' . $err;
    } else {
        // Convertir la respuesta JSON en un objeto PHP
        $data = json_decode($response, true); // true para obtener arrays asociativos.

        if (json_last_error() === JSON_ERROR_NONE) { // Verificar que el JSON es válido
            if (isset($data['content']) && is_array($data['content'])) {
                $public_ids = [];
                foreach ($data['content'] as $property) {
                    if (isset($property['public_id'])) {
                        $public_ids[] = $property['public_id'];
                    }
                }
                // Mostrar los IDs públicos obtenidos.
                echo 'Public IDs: ' . implode(', ', $public_ids);
            } else {
                echo 'No se encontraron propiedades.';
            }
        } else {

            echo 'Error al decodificar JSON ' . esc_html($response);
        }
    }

    wp_die(); // Esto es necesario para terminar correctamente las solicitudes AJAX.
}