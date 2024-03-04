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
    ?>
    <div class="wrap">
        <h2>Configuración Plugin Inmobiliario</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('mi-plugin-inmobiliario-settings-group');
            do_settings_sections('mi-plugin-inmobiliario-config');
            ?>
        </form>
        <button type="button" id="obtener-propiedades">Obtener Propiedades</button>
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
    </div>
    <?php
}

// Hook para registrar las opciones de configuración.
add_action('admin_init', 'mi_plugin_inmobiliario_settings');

// Función para registrar las opciones y secciones de configuración.
function mi_plugin_inmobiliario_settings() {
    register_setting('mi-plugin-inmobiliario-settings-group', 'mi_plugin_inmobiliario_api');
    register_setting('mi-plugin-inmobiliario-settings-group', 'mi_plugin_inmobiliario_api_key');
    // Registra aquí más configuraciones según sea necesario.

    add_settings_section('mi-plugin-inmobiliario-settings-section', 'Configuración API', 'mi_plugin_inmobiliario_settings_section_callback', 'mi-plugin-inmobiliario-config');

    add_settings_field('mi_plugin_inmobiliario_api', 'API a usar', 'mi_plugin_inmobiliario_api_callback', 'mi-plugin-inmobiliario-config', 'mi-plugin-inmobiliario-settings-section');
    add_settings_field('mi_plugin_inmobiliario_api_key', 'API Key', 'mi_plugin_inmobiliario_api_key_callback', 'mi-plugin-inmobiliario-config', 'mi-plugin-inmobiliario-settings-section');
    // Añade más campos de configuración según sea necesario.
}

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