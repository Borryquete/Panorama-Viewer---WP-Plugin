<?php
/*
Plugin Name: Panorama Viewer
Description: Un plugin para integrar fotos panorámicas usando Pannellum.
Version: 1.0
Author: Borry.es
*/

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Cargar recursos (CSS, JS)
function panorama_viewer_enqueue_scripts() {
    // Cargar Pannellum CSS
    wp_enqueue_style('pannellum-css', plugin_dir_url(__FILE__) . 'assets/pannellum/pannellum.css');

    // Cargar Pannellum JS
    wp_enqueue_script('pannellum-js', plugin_dir_url(__FILE__) . 'assets/pannellum/pannellum.js', array(), null, true);

    // Cargar el script personalizado
    wp_enqueue_script('panorama-viewer-script', plugin_dir_url(__FILE__) . 'assets/js/panorama-viewer.js', array('pannellum-js'), null, true);
}
add_action('wp_enqueue_scripts', 'panorama_viewer_enqueue_scripts');

// Añadir menú en el backend
function panorama_viewer_admin_menu() {
    add_menu_page(
        'Panorama Viewer', // Título de la página
        'Panorama Viewer', // Título del menú
        'manage_options',  // Capacidad requerida
        'panorama-viewer', // Slug del menú
        'panorama_viewer_admin_page', // Función que renderiza la página
        'dashicons-format-gallery', // Ícono del menú
        6 // Posición en el menú
    );
}
add_action('admin_menu', 'panorama_viewer_admin_menu');

// Función para renderizar la página de administración
function panorama_viewer_admin_page() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        return;
    }

    // Guardar o actualizar una escena
    if (isset($_POST['submit_panorama'])) {
        $panorama_id = isset($_POST['panorama_id']) ? sanitize_text_field($_POST['panorama_id']) : uniqid('panorama_');

        $panorama_data = array(
            'id' => $panorama_id,
            'image_url' => esc_url_raw($_POST['image_url']),
            'title' => sanitize_text_field($_POST['title']),
            'author' => sanitize_text_field($_POST['author']),
            'show_title' => isset($_POST['show_title']) ? 1 : 0,
            'show_author' => isset($_POST['show_author']) ? 1 : 0,
            'type' => sanitize_text_field($_POST['panorama_type']), // Nuevo campo
            'auto_load' => isset($_POST['auto_load']) ? 1 : 0,
            'auto_rotate' => isset($_POST['auto_rotate']) ? intval($_POST['auto_rotate']) : 0,
            'preview_image_url' => esc_url_raw($_POST['preview_image_url']),
        );

        // Guardar en la base de datos
        $panoramas = get_option('panorama_viewer_scenes', array());
        $panoramas[$panorama_id] = $panorama_data;
        update_option('panorama_viewer_scenes', $panoramas);

        echo '<div class="notice notice-success"><p>Escena guardada correctamente. ID: ' . esc_html($panorama_id) . '</p></div>';
    }

    // Eliminar una escena
    if (isset($_GET['delete'])) {
        $panorama_id = sanitize_text_field($_GET['delete']);
        $panoramas = get_option('panorama_viewer_scenes', array());
        if (isset($panoramas[$panorama_id])) {
            unset($panoramas[$panorama_id]);
            update_option('panorama_viewer_scenes', $panoramas);
            echo '<div class="notice notice-success"><p>Escena eliminada correctamente.</p></div>';
        }
    }

    // Obtener la escena a editar
    $edit_scene = null;
    if (isset($_GET['edit'])) {
        $panorama_id = sanitize_text_field($_GET['edit']);
        $panoramas = get_option('panorama_viewer_scenes', array());
        if (isset($panoramas[$panorama_id])) {
            $edit_scene = $panoramas[$panorama_id];
            // Asegurarse de que las claves existan (para escenas antiguas)
            $edit_scene['show_title'] = isset($edit_scene['show_title']) ? $edit_scene['show_title'] : 1;
            $edit_scene['show_author'] = isset($edit_scene['show_author']) ? $edit_scene['show_author'] : 0;
            $edit_scene['type'] = isset($edit_scene['type']) ? $edit_scene['type'] : 'equirectangular';
        }
    }

    // Obtener todas las escenas guardadas
    $panoramas = get_option('panorama_viewer_scenes', array());

    // Formulario para crear/editar escenas
    ?>
    <div class="wrap">
        <h1>Panorama Viewer</h1>
        <form method="post" action="">
            <h2><?php echo $edit_scene ? 'Editar escena' : 'Añadir nueva escena'; ?></h2>
            <table class="form-table">
                <?php if ($edit_scene): ?>
                    <tr>
                        <th scope="row"><label for="panorama_id">ID de la escena</label></th>
                        <td>
                            <input name="panorama_id" type="text" id="panorama_id" value="<?php echo esc_attr($edit_scene['id']); ?>" readonly>
                            <p class="description">El ID de la escena no se puede modificar.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row"><label for="image_url">URL de la imagen panorámica</label></th>
                    <td>
                        <input name="image_url" type="text" id="image_url" value="<?php echo $edit_scene ? esc_url($edit_scene['image_url']) : ''; ?>" required>
                        <button id="upload_image_button" class="button">Subir imagen</button>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="title">Título</label></th>
                    <td><input name="title" type="text" id="title" value="<?php echo $edit_scene ? esc_attr($edit_scene['title']) : ''; ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="show_title">Mostrar título</label></th>
                    <td><input name="show_title" type="checkbox" id="show_title" <?php echo $edit_scene && $edit_scene['show_title'] ? 'checked' : ''; ?>></td>
                </tr>
                <tr>
                    <th scope="row"><label for="author">Autor</label></th>
                    <td><input name="author" type="text" id="author" value="<?php echo $edit_scene ? esc_attr($edit_scene['author']) : ''; ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="show_author">Mostrar autor</label></th>
                    <td><input name="show_author" type="checkbox" id="show_author" <?php echo $edit_scene && $edit_scene['show_author'] ? 'checked' : ''; ?>></td>
                </tr>
                <tr>
                    <th scope="row"><label for="panorama_type">Tipo de panorámica</label></th>
                    <td>
                        <select name="panorama_type" id="panorama_type" required>
                            <option value="equirectangular" <?php echo $edit_scene && $edit_scene['type'] === 'equirectangular' ? 'selected' : ''; ?>>360</option>
                            <option value="partial" <?php echo $edit_scene && $edit_scene['type'] === 'partial' ? 'selected' : ''; ?>>Parcial</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="auto_load">Auto cargar</label></th>
                    <td><input name="auto_load" type="checkbox" id="auto_load" <?php echo $edit_scene && $edit_scene['auto_load'] ? 'checked' : ''; ?>></td>
                </tr>
                <tr>
                    <th scope="row"><label for="auto_rotate">Auto rotar (segundos)</label></th>
                    <td><input name="auto_rotate" type="number" id="auto_rotate" value="<?php echo $edit_scene ? esc_attr($edit_scene['auto_rotate']) : '0'; ?>" min="0"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="preview_image_url">URL de la imagen de vista previa</label></th>
                    <td>
                        <input name="preview_image_url" type="text" id="preview_image_url" value="<?php echo $edit_scene ? esc_url($edit_scene['preview_image_url']) : ''; ?>">
                        <button id="upload_preview_button" class="button">Subir imagen</button>
                    </td>
                </tr>
            </table>
            <?php submit_button($edit_scene ? 'Actualizar escena' : 'Guardar escena', 'primary', 'submit_panorama'); ?>
            <?php if ($edit_scene): ?>
                <a href="?page=panorama-viewer" class="button">Cancelar edición</a>
            <?php endif; ?>
        </form>

        <h2>Escenas guardadas</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Shortcode</th>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($panoramas as $id => $scene): ?>
                    <tr>
                        <td><?php echo esc_html($id); ?></td>
                        <td><code>[panorama_viewer id="<?php echo esc_attr($id); ?>"]</code></td>
                        <td><img src="<?php echo esc_url($scene['image_url']); ?>" style="max-width: 100px;"></td>
                        <td><?php echo esc_html($scene['title']); ?></td>
                        <td><?php echo esc_html($scene['author']); ?></td>
                        <td>
                            <a href="?page=panorama-viewer&edit=<?php echo esc_attr($id); ?>" class="button">Editar</a>
                            <a href="?page=panorama-viewer&delete=<?php echo esc_attr($id); ?>" class="button" onclick="return confirm('¿Estás seguro de que quieres eliminar esta escena?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode para integrar el visor panorámico
function panorama_viewer_shortcode($atts) {
    // Atributos del shortcode
    $atts = shortcode_atts(array(
        'id' => '', // ID de la escena
    ), $atts, 'panorama_viewer');

    // Obtener la escena guardada
    $panoramas = get_option('panorama_viewer_scenes', array());
    if (empty($atts['id']) || !isset($panoramas[$atts['id']])) {
        return '<p>Escena no encontrada.</p>';
    }

    $scene = $panoramas[$atts['id']];

    // Asegurarse de que las claves existan (para escenas antiguas)
    $scene['show_title'] = isset($scene['show_title']) ? $scene['show_title'] : 1;
    $scene['show_author'] = isset($scene['show_author']) ? $scene['show_author'] : 0;
    $scene['type'] = isset($scene['type']) ? $scene['type'] : 'equirectangular';

    // Configuración de Pannellum
$config = array(
    'panorama' => $scene['image_url'],
    'autoLoad' => (bool)$scene['auto_load'],
);

if ($scene['type'] === 'partial') {
    $config = [
        "type" => "equirectangular",
        "panorama" => $scene['image_url'],
        "haov" => 120, // Ajusta el campo de visión horizontal según sea necesario
        "vaov" => 50,  // Ajusta el campo de visión vertical según sea necesario
        "vOffset" => 0.5 // Ajusta el desplazamiento vertical si es necesario
    ];
}else {
    // Escena 360 normal
    $config['type'] = 'equirectangular';
}

if ($scene['show_title'] && !empty($scene['title'])) {
    $config['title'] = $scene['title'];
}
if ($scene['show_author'] && !empty($scene['author'])) {
    $config['author'] = $scene['author'];
}
if ($scene['auto_rotate'] > 0) {
    $config['autoRotate'] = $scene['auto_rotate'];
}
if (!empty($scene['preview_image_url'])) {
    $config['preview'] = $scene['preview_image_url'];
}

    // Generar el HTML del visor
    $html = '<div id="panorama-viewer-' . esc_attr($atts['id']) . '" style="width: 100%; height: 500px;"></div>';
    $html .= '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    pannellum.viewer("panorama-viewer-' . esc_attr($atts['id']) . '", ' . json_encode($config) . ');
                });
              </script>';

    return $html;
}
add_shortcode('panorama_viewer', 'panorama_viewer_shortcode');

// Cargar scripts de administración
function panorama_viewer_enqueue_admin_scripts($hook) {
    if ($hook != 'toplevel_page_panorama-viewer') {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('panorama-viewer-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'panorama_viewer_enqueue_admin_scripts');
