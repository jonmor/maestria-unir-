<?php
/**
 * Gestor de tareas
 *
 * CRUD sencillo de tareas para el proyecto de WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

const GT_ESTADOS = array(
    'pendiente' => 'Pendiente',
    'en_progreso' => 'En progreso',
    'completada' => 'Completada',
);

/** Registra el tipo de contenido Tareas para disponer de CRUD en wp-admin. */
function gt_registrar_tipo_tarea() {
    $etiquetas = array(
        'name' => 'Tareas',
        'singular_name' => 'Tarea',
        'menu_name' => 'Tareas',
        'add_new' => 'Añadir tarea',
        'add_new_item' => 'Añadir nueva tarea',
        'edit_item' => 'Editar tarea',
        'new_item' => 'Nueva tarea',
        'view_item' => 'Ver tarea',
        'search_items' => 'Buscar tareas',
        'not_found' => 'No se encontraron tareas',
        'not_found_in_trash' => 'No se encontraron tareas en la papelera',
        'all_items' => 'Todas las tareas',
    );

    register_post_type('tarea', array(
        'labels' => $etiquetas,
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-list-view',
        'supports' => array('title', 'editor'),
        'has_archive' => false,
        'rewrite' => array('slug' => 'tarea'),
    ));
}
add_action('init', 'gt_registrar_tipo_tarea');

/** Añade los campos de estado y fecha límite al editor de tareas. */
function gt_agregar_metabox() {
    add_meta_box(
        'gt_datos_tarea',
        'Datos de la tarea',
        'gt_mostrar_metabox',
        'tarea',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'gt_agregar_metabox');

function gt_mostrar_metabox($post) {
    wp_nonce_field('gt_guardar_datos_tarea', 'gt_datos_nonce');
    $estado = get_post_meta($post->ID, '_gt_estado', true) ?: 'pendiente';
    $fecha = get_post_meta($post->ID, '_gt_fecha_limite', true);
    ?>
    <p>
        <label for="gt_estado"><strong>Estado</strong></label>
        <select id="gt_estado" name="gt_estado" style="width:100%;">
            <?php foreach (GT_ESTADOS as $valor => $nombre) : ?>
                <option value="<?php echo esc_attr($valor); ?>" <?php selected($estado, $valor); ?>>
                    <?php echo esc_html($nombre); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="gt_fecha_limite"><strong>Fecha límite</strong></label>
        <input id="gt_fecha_limite" type="date" name="gt_fecha_limite"
               value="<?php echo esc_attr($fecha); ?>" style="width:100%;">
    </p>
    <?php
}

/** Guarda los campos adicionales de la tarea. */
function gt_guardar_datos_tarea($post_id) {
    if (!isset($_POST['gt_datos_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gt_datos_nonce'])), 'gt_guardar_datos_tarea')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== 'tarea' || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $estado = isset($_POST['gt_estado']) ? sanitize_key(wp_unslash($_POST['gt_estado'])) : 'pendiente';
    if (!array_key_exists($estado, GT_ESTADOS)) {
        $estado = 'pendiente';
    }

    $fecha = isset($_POST['gt_fecha_limite']) ? sanitize_text_field(wp_unslash($_POST['gt_fecha_limite'])) : '';
    if ($fecha !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $fecha = '';
    }

    update_post_meta($post_id, '_gt_estado', $estado);
    update_post_meta($post_id, '_gt_fecha_limite', $fecha);
}
add_action('save_post_tarea', 'gt_guardar_datos_tarea');

/** Agrega columnas útiles al listado administrativo. */
function gt_columnas_tareas($columnas) {
    return array(
        'cb' => $columnas['cb'],
        'title' => 'Tarea',
        'gt_estado' => 'Estado',
        'gt_fecha' => 'Fecha límite',
        'date' => 'Fecha de creación',
    );
}
add_filter('manage_tarea_posts_columns', 'gt_columnas_tareas');

function gt_contenido_columna($columna, $post_id) {
    if ($columna === 'gt_estado') {
        $estado = get_post_meta($post_id, '_gt_estado', true) ?: 'pendiente';
        echo esc_html(GT_ESTADOS[$estado] ?? 'Pendiente');
    }

    if ($columna === 'gt_fecha') {
        $fecha = get_post_meta($post_id, '_gt_fecha_limite', true);
        echo $fecha ? esc_html($fecha) : 'Sin fecha';
    }
}
add_action('manage_tarea_posts_custom_column', 'gt_contenido_columna', 10, 2);

/** Procesa las operaciones crear, actualizar y eliminar de la interfaz pública. */
function gt_procesar_formulario() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['gt_accion'])) {
        return;
    }

    if (!is_user_logged_in() || !current_user_can('edit_posts')) {
        return;
    }

    $accion = sanitize_key(wp_unslash($_POST['gt_accion']));
    $redireccion = wp_get_referer() ?: home_url('/tareas/');
    $resultado = 'error';

    if ($accion === 'crear') {
        check_admin_referer('gt_crear_tarea', 'gt_nonce');
        $id = gt_guardar_tarea_desde_formulario();
        $resultado = $id ? 'creada' : 'error';
    }

    if ($accion === 'actualizar') {
        $id = isset($_POST['gt_id']) ? absint($_POST['gt_id']) : 0;
        check_admin_referer('gt_actualizar_tarea_' . $id, 'gt_nonce');
        if ($id && current_user_can('edit_post', $id)) {
            $resultado = gt_guardar_tarea_desde_formulario($id) ? 'actualizada' : 'error';
        }
    }

    if ($accion === 'eliminar') {
        $id = isset($_POST['gt_id']) ? absint($_POST['gt_id']) : 0;
        check_admin_referer('gt_eliminar_tarea_' . $id, 'gt_nonce');
        if ($id && current_user_can('delete_post', $id)) {
            $resultado = wp_delete_post($id, true) ? 'eliminada' : 'error';
        }
    }

    wp_safe_redirect(add_query_arg('gt_mensaje', $resultado, remove_query_arg('editar_tarea', $redireccion)));
    exit;
}
add_action('init', 'gt_procesar_formulario', 20);

function gt_guardar_tarea_desde_formulario($id = 0) {
    $titulo = isset($_POST['gt_titulo']) ? sanitize_text_field(wp_unslash($_POST['gt_titulo'])) : '';
    $descripcion = isset($_POST['gt_descripcion']) ? sanitize_textarea_field(wp_unslash($_POST['gt_descripcion'])) : '';
    $estado = isset($_POST['gt_estado']) ? sanitize_key(wp_unslash($_POST['gt_estado'])) : 'pendiente';
    $fecha = isset($_POST['gt_fecha']) ? sanitize_text_field(wp_unslash($_POST['gt_fecha'])) : '';

    if ($titulo === '' || !array_key_exists($estado, GT_ESTADOS)) {
        return 0;
    }

    if ($fecha !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $fecha = '';
    }

    $datos = array(
        'post_type' => 'tarea',
        'post_title' => $titulo,
        'post_content' => $descripcion,
        'post_status' => 'publish',
    );

    if ($id) {
        $datos['ID'] = $id;
        $id = wp_update_post($datos, true);
    } else {
        $id = wp_insert_post($datos, true);
    }

    if (is_wp_error($id)) {
        return 0;
    }

    update_post_meta($id, '_gt_estado', $estado);
    update_post_meta($id, '_gt_fecha_limite', $fecha);
    return $id;
}

/** Shortcode [gestor_tareas] para mostrar el CRUD en una página pública. */
function gt_mostrar_tareas() {
    $editar_id = isset($_GET['editar_tarea']) ? absint($_GET['editar_tarea']) : 0;
    if ($editar_id && get_post_type($editar_id) !== 'tarea') {
        $editar_id = 0;
    }

    $tareas = get_posts(array(
        'post_type' => 'tarea',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    $puede_editar = is_user_logged_in() && current_user_can('edit_posts');
    $conteos = array_fill_keys(array_keys(GT_ESTADOS), 0);
    foreach ($tareas as $tarea) {
        $estado_tarea = get_post_meta($tarea->ID, '_gt_estado', true) ?: 'pendiente';
        if (isset($conteos[$estado_tarea])) {
            $conteos[$estado_tarea]++;
        }
    }

    $mensaje = isset($_GET['gt_mensaje']) ? sanitize_key(wp_unslash($_GET['gt_mensaje'])) : '';
    $mensajes = array(
        'creada' => 'La tarea se creó correctamente.',
        'actualizada' => 'La tarea se actualizó correctamente.',
        'eliminada' => 'La tarea se eliminó correctamente.',
    );

    ob_start();
    ?>
    <div class="gt-contenedor">
        <div class="gt-encabezado">
            <div>
                <span class="gt-etiqueta">ORGANIZACIÓN PERSONAL</span>
                <h2>Gestor de tareas</h2>
                <p>Planifica, organiza y da seguimiento a tus actividades.</p>
            </div>
            <?php if ($puede_editar) : ?>
                <a class="gt-boton gt-boton-principal" href="#formulario-tarea">+ Nueva tarea</a>
            <?php endif; ?>
        </div>

        <div class="gt-resumen" aria-label="Resumen de tareas">
            <div class="gt-resumen-tarjeta">
                <span class="gt-resumen-numero"><?php echo esc_html(count($tareas)); ?></span>
                <span class="gt-resumen-texto">Total</span>
            </div>
            <div class="gt-resumen-tarjeta gt-resumen-pendiente">
                <span class="gt-resumen-numero"><?php echo esc_html($conteos['pendiente']); ?></span>
                <span class="gt-resumen-texto">Pendientes</span>
            </div>
            <div class="gt-resumen-tarjeta gt-resumen-progreso">
                <span class="gt-resumen-numero"><?php echo esc_html($conteos['en_progreso']); ?></span>
                <span class="gt-resumen-texto">En progreso</span>
            </div>
            <div class="gt-resumen-tarjeta gt-resumen-completada">
                <span class="gt-resumen-numero"><?php echo esc_html($conteos['completada']); ?></span>
                <span class="gt-resumen-texto">Completadas</span>
            </div>
        </div>

        <?php if (isset($mensajes[$mensaje])) : ?>
            <p class="gt-mensaje-exito" role="status">✓ <?php echo esc_html($mensajes[$mensaje]); ?></p>
        <?php endif; ?>

        <?php if (!$puede_editar) : ?>
            <p>Inicia sesión con un usuario autorizado para crear, editar o eliminar tareas.</p>
        <?php else :
            $tarea_editar = $editar_id ? get_post($editar_id) : null;
            $es_edicion = $tarea_editar instanceof WP_Post;
            $estado_formulario = $es_edicion ? get_post_meta($editar_id, '_gt_estado', true) : 'pendiente';
            $fecha_formulario = $es_edicion ? get_post_meta($editar_id, '_gt_fecha_limite', true) : '';
            ?>
            <section class="gt-panel gt-panel-form">
                <div class="gt-panel-encabezado">
                    <div>
                        <span class="gt-etiqueta">NUEVA ACTIVIDAD</span>
                        <h3 id="formulario-tarea"><?php echo $es_edicion ? 'Editar tarea' : 'Crear una tarea'; ?></h3>
                    </div>
                    <?php if ($es_edicion) : ?>
                        <a class="gt-enlace-secundario" href="<?php echo esc_url(remove_query_arg('editar_tarea')); ?>">Cancelar edición</a>
                    <?php endif; ?>
                </div>
                <form method="post" class="gt-formulario">
                <input type="hidden" name="gt_accion" value="<?php echo $es_edicion ? 'actualizar' : 'crear'; ?>">
                <?php if ($es_edicion) : ?>
                    <input type="hidden" name="gt_id" value="<?php echo esc_attr($editar_id); ?>">
                    <?php wp_nonce_field('gt_actualizar_tarea_' . $editar_id, 'gt_nonce'); ?>
                <?php else : ?>
                    <?php wp_nonce_field('gt_crear_tarea', 'gt_nonce'); ?>
                <?php endif; ?>
                <p>
                    <label for="gt_titulo">Título</label>
                    <input id="gt_titulo" type="text" name="gt_titulo" required
                           value="<?php echo $es_edicion ? esc_attr($tarea_editar->post_title) : ''; ?>">
                </p>
                <p>
                    <label for="gt_descripcion">Descripción</label>
                    <textarea id="gt_descripcion" name="gt_descripcion" rows="4"><?php
                        echo $es_edicion ? esc_textarea($tarea_editar->post_content) : '';
                    ?></textarea>
                </p>
                <p>
                    <label for="gt_estado_formulario">Estado</label>
                    <select id="gt_estado_formulario" name="gt_estado">
                        <?php foreach (GT_ESTADOS as $valor => $nombre) : ?>
                            <option value="<?php echo esc_attr($valor); ?>" <?php selected($estado_formulario, $valor); ?>>
                                <?php echo esc_html($nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label for="gt_fecha">Fecha límite</label>
                    <input id="gt_fecha" type="date" name="gt_fecha" value="<?php echo esc_attr($fecha_formulario); ?>">
                </p>
                <button class="gt-boton gt-boton-principal" type="submit"><?php echo $es_edicion ? 'Guardar cambios' : 'Crear tarea'; ?></button>
            </form>
            </section>
        <?php endif; ?>

        <section class="gt-panel gt-panel-lista">
            <div class="gt-panel-encabezado">
                <div>
                    <span class="gt-etiqueta">SEGUIMIENTO</span>
                    <h3>Listado de tareas</h3>
                </div>
                <span class="gt-contador"><?php echo esc_html(count($tareas)); ?> <?php echo count($tareas) === 1 ? 'tarea' : 'tareas'; ?></span>
            </div>
        <?php if (!$tareas) : ?>
            <div class="gt-vacio">
                <span class="gt-vacio-icono">✓</span>
                <strong>Aún no hay tareas registradas</strong>
                <span>Empieza creando tu primera tarea.</span>
            </div>
        <?php else : ?>
            <div class="gt-tabla-contenedor">
                <table class="gt-tabla">
                <thead>
                    <tr>
                        <th>Tarea</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Fecha límite</th>
                        <?php if ($puede_editar) : ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tareas as $tarea) :
                        $estado = get_post_meta($tarea->ID, '_gt_estado', true) ?: 'pendiente';
                        $fecha = get_post_meta($tarea->ID, '_gt_fecha_limite', true);
                        ?>
                        <tr>
                            <td class="gt-tarea-titulo"><strong><?php echo esc_html($tarea->post_title); ?></strong></td>
                            <td class="gt-tarea-descripcion"><?php echo esc_html(wp_trim_words($tarea->post_content, 18)); ?></td>
                            <td><span class="gt-estado gt-estado-<?php echo esc_attr($estado); ?>"><?php echo esc_html(GT_ESTADOS[$estado] ?? 'Pendiente'); ?></span></td>
                            <td class="gt-fecha"><?php echo $fecha ? esc_html($fecha) : 'Sin fecha'; ?></td>
                            <?php if ($puede_editar) : ?>
                                <td class="gt-acciones">
                                    <a class="gt-enlace-editar" href="<?php echo esc_url(add_query_arg('editar_tarea', $tarea->ID)); ?>#formulario-tarea">Editar</a>
                                    <form method="post" class="gt-formulario-eliminar">
                                        <input type="hidden" name="gt_accion" value="eliminar">
                                        <input type="hidden" name="gt_id" value="<?php echo esc_attr($tarea->ID); ?>">
                                        <?php wp_nonce_field('gt_eliminar_tarea_' . $tarea->ID, 'gt_nonce'); ?>
                                        <button class="gt-boton-eliminar" type="submit" onclick="return confirm('¿Eliminar esta tarea?');">Eliminar</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
        </section>
    </div>
    <style>
        .gt-contenedor {
            --gt-primario: #2563eb;
            --gt-primario-oscuro: #1d4ed8;
            --gt-texto: #172033;
            --gt-secundario: #64748b;
            --gt-borde: #e2e8f0;
            --gt-fondo: #f8fafc;
            max-width: 1120px;
            margin: 32px auto;
            padding: 0 20px;
            color: var(--gt-texto);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }
        .gt-contenedor *, .gt-contenedor *::before, .gt-contenedor *::after { box-sizing: border-box; }
        .gt-encabezado, .gt-panel-encabezado { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; }
        .gt-encabezado { margin-bottom: 26px; }
        .gt-etiqueta { display: block; color: var(--gt-primario); font-size: 11px; font-weight: 800; letter-spacing: .12em; margin-bottom: 7px; }
        .gt-encabezado h2, .gt-panel h3 { margin: 0; color: var(--gt-texto); font-weight: 750; letter-spacing: -.025em; }
        .gt-encabezado h2 { font-size: clamp(28px, 4vw, 38px); }
        .gt-panel h3 { font-size: 21px; }
        .gt-encabezado p { margin: 7px 0 0; color: var(--gt-secundario); font-size: 15px; }
        .gt-boton { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 9px 17px; border: 0; border-radius: 9px; font-size: 14px; font-weight: 700; line-height: 1.2; text-decoration: none; cursor: pointer; transition: background .2s, transform .2s, box-shadow .2s; }
        .gt-boton:hover { transform: translateY(-1px); text-decoration: none; }
        .gt-boton-principal { background: var(--gt-primario); color: #fff !important; box-shadow: 0 5px 12px rgba(37, 99, 235, .22); }
        .gt-boton-principal:hover { background: var(--gt-primario-oscuro); }
        .gt-resumen { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
        .gt-resumen-tarjeta { padding: 18px 20px; background: #fff; border: 1px solid var(--gt-borde); border-radius: 12px; box-shadow: 0 3px 12px rgba(15, 23, 42, .04); }
        .gt-resumen-numero { display: block; color: var(--gt-texto); font-size: 27px; font-weight: 750; line-height: 1; }
        .gt-resumen-texto { display: block; margin-top: 8px; color: var(--gt-secundario); font-size: 13px; }
        .gt-resumen-pendiente { border-top: 3px solid #f59e0b; }
        .gt-resumen-progreso { border-top: 3px solid #3b82f6; }
        .gt-resumen-completada { border-top: 3px solid #10b981; }
        .gt-mensaje-exito { margin: 0 0 20px; padding: 13px 16px; border: 1px solid #a7f3d0; border-radius: 9px; color: #047857; background: #ecfdf5; font-size: 14px; }
        .gt-panel { margin-bottom: 24px; padding: 25px; border: 1px solid var(--gt-borde); border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgba(15, 23, 42, .045); }
        .gt-panel-encabezado { align-items: center; margin-bottom: 20px; }
        .gt-formulario { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 20px; }
        .gt-formulario p { margin: 0 0 17px; }
        .gt-formulario p:nth-of-type(2) { grid-column: 1 / -1; }
        .gt-formulario label { display: block; margin-bottom: 7px; color: #334155; font-size: 13px; font-weight: 700; }
        .gt-formulario input[type="text"], .gt-formulario input[type="date"], .gt-formulario textarea, .gt-formulario select { width: 100%; max-width: none; padding: 11px 12px; border: 1px solid #cbd5e1; border-radius: 8px; color: var(--gt-texto); background: #fff; font: inherit; font-size: 14px; }
        .gt-formulario textarea { min-height: 105px; resize: vertical; }
        .gt-formulario input:focus, .gt-formulario textarea:focus, .gt-formulario select:focus { border-color: var(--gt-primario); outline: 3px solid rgba(37, 99, 235, .12); }
        .gt-formulario > .gt-boton { grid-column: 1 / -1; justify-self: start; }
        .gt-enlace-secundario, .gt-enlace-editar { color: var(--gt-primario); font-size: 13px; font-weight: 700; text-decoration: none; }
        .gt-enlace-secundario:hover, .gt-enlace-editar:hover { color: var(--gt-primario-oscuro); text-decoration: underline; }
        .gt-contador { padding: 5px 10px; border-radius: 20px; color: #475569; background: var(--gt-fondo); font-size: 12px; font-weight: 700; }
        .gt-tabla-contenedor { overflow-x: auto; }
        .gt-tabla { width: 100%; border-collapse: collapse; }
        .gt-tabla th { padding: 12px 14px; border-bottom: 1px solid var(--gt-borde); color: #64748b; background: #f8fafc; font-size: 11px; font-weight: 800; letter-spacing: .06em; text-align: left; text-transform: uppercase; }
        .gt-tabla td { padding: 16px 14px; border-bottom: 1px solid #eef2f7; color: #475569; font-size: 14px; text-align: left; vertical-align: middle; }
        .gt-tabla tr:last-child td { border-bottom: 0; }
        .gt-tabla tr:hover td { background: #fafcff; }
        .gt-tarea-titulo { min-width: 165px; color: var(--gt-texto) !important; }
        .gt-tarea-descripcion { min-width: 230px; color: #64748b !important; }
        .gt-fecha { white-space: nowrap; }
        .gt-estado { display: inline-flex; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 750; white-space: nowrap; }
        .gt-estado-pendiente { color: #92400e; background: #fef3c7; }
        .gt-estado-en_progreso { color: #1e40af; background: #dbeafe; }
        .gt-estado-completada { color: #065f46; background: #d1fae5; }
        .gt-acciones { white-space: nowrap; }
        .gt-formulario-eliminar { display: inline-block; margin: 0 0 0 12px; }
        .gt-boton-eliminar { padding: 0; border: 0; color: #dc2626; background: transparent; font: inherit; font-size: 13px; font-weight: 700; cursor: pointer; }
        .gt-boton-eliminar:hover { color: #991b1b; text-decoration: underline; }
        .gt-vacio { display: flex; align-items: center; flex-direction: column; gap: 5px; padding: 42px 20px; color: var(--gt-secundario); text-align: center; }
        .gt-vacio strong { color: var(--gt-texto); font-size: 16px; }
        .gt-vacio-icono { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; margin-bottom: 7px; border-radius: 50%; color: #047857; background: #d1fae5; font-weight: 800; }
        @media (max-width: 700px) {
            .gt-contenedor { margin: 22px auto; padding: 0 14px; }
            .gt-encabezado { align-items: stretch; flex-direction: column; }
            .gt-encabezado .gt-boton { align-self: flex-start; }
            .gt-resumen { grid-template-columns: repeat(2, 1fr); }
            .gt-panel { padding: 18px 14px; }
            .gt-formulario { display: block; }
            .gt-formulario p { margin-bottom: 15px; }
            .gt-panel-encabezado { align-items: flex-start; }
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('gestor_tareas', 'gt_mostrar_tareas');

/** Crea automáticamente la página local /tareas/ la primera vez que carga el sitio. */
function gt_crear_pagina_tareas() {
    if (get_option('gt_pagina_tareas_id')) {
        return;
    }

    $pagina = get_page_by_path('tareas', OBJECT, 'page');
    if (!$pagina) {
        $pagina_id = wp_insert_post(array(
            'post_title' => 'Tareas',
            'post_name' => 'tareas',
            'post_content' => '[gestor_tareas]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ));
    } else {
        $pagina_id = $pagina->ID;
    }

    if ($pagina_id && !is_wp_error($pagina_id)) {
        update_option('gt_pagina_tareas_id', $pagina_id, false);
    }
}
add_action('init', 'gt_crear_pagina_tareas', 30);
