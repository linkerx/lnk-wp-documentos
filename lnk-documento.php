<?php

/**
 Plugin Name: LNK documentos
 Plugin URI: https://github.com/linkerx/lnk-wp-documentos
 Description: Tipo de Dato documento para Wordpress
 Version: 1
 Author: Diego
 Author URI: https://linkerx.com.ar/
 License: GPL2
 */

/**
 * Genera el tipo de dato formulario
 */
function lnk_documento_create_type(){
    register_post_type(
        'documento',
        array(
            'labels' => array(
                'name' => __('Documentos','documentos_name'),
                'singular_name' => __('Documento','documentos_singular_name'),
                'menu_name' => __('Documentos','documentos_menu_name'),
                'all_items' => __('Lista de documentos','documentos_all_items'),
            ),
            'description' => 'Tipo de dato de documento',
            'public' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 8,
            'support' => array(
                'title',
                'excerpt',
                'editor',
                'thumbnail',
                'revisions'
            ),
            "capability_type" => 'documentos',
            "map_meta_cap" => true
        )
    );
}
add_action('init', 'lnk_documento_create_type');
add_post_type_support('documento', array('thumbnail','excerpt'));

function lnk_documento_disable_gutenberg($current_status, $post_type)
{
    if ($post_type === 'documento') return false;
    return $current_status;
}
add_filter('use_block_editor_for_post_type', 'lnk_documento_disable_gutenberg', 10, 2);

function lnk_register_documento_taxonomies(){

    /**
     * Ramo
     */
    $labels = array(
        'name' => "Carpetas",
        'singular_name' => "Carpeta",
    );
    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug'=>'carpeta'),
    );
    register_taxonomy('carpeta','documento',$args);
}
add_action( 'init', 'lnk_register_documento_taxonomies');

/**
 * agrega columnas al listado de formularios
 */
function lnk_documento_add_columns($columns) {
    global $post_type;
    if($post_type == 'documento'){
        $columns['lnk_documento_tapa'] = "Tapa";
        $columns['lnk_documento_carpeta'] = "ISBN";
        $columns['lnk_documento_pdf'] = "PDF";
    }
    return $columns;
}
add_filter ('manage_posts_columns', 'lnk_documento_add_columns');

function lnk_documento_show_columns_values($column_name) {
    global $wpdb, $post;
    $id = $post->ID;

    if($post->post_type == 'documento'){
        $id = $post->ID;
        if($column_name === 'lnk_documento_tapa'){
            // imagen destacada
        } elseif($column_name === 'lnk_documento_pdf'){
            print get_post_meta($id,'lnk-pdf',true);
        } elseif($column_name === 'lnk_documento_pdf'){
            // carpeta
        }
    }
}
add_action ('manage_posts_custom_column', 'lnk_documento_show_columns_values');

/**
 * Agrega los hooks para los datos meta en el editor de documentos
 */
function lnk_documento_custom_meta() {
    global $post;
    if($post->post_type == 'documento'){
        add_meta_box('lnk_documento_pdf',"Archivo PDF del documento", 'lnk_documento_pdf_meta_box', null, 'normal','core');
        add_meta_box('lnk_documento_isbn',"ISBN", 'lnk_documento_isbn_meta_box', null, 'side','core');
    }
}
add_action ('add_meta_boxes','lnk_documento_custom_meta');

function lnk_documento_pdf_meta_box() {
    global $post;
    wp_nonce_field(plugin_basename(__FILE__), 'lnk_documento_pdf_nonce');

    if($archivo = get_post_meta( $post->ID, 'lnk_documento_pdf', true )) {
        print "PDF: <a href='".$archivo['url']."'>".$archivo['url']."</a>";
    }

    $html = '<p class="description">';

    $html .= 'Seleccione su PDF aqui para reemplazar el existente.';

    $html .= '</p>';
    $html .= '<input type="file" id="lnk_documento_pdf" name="lnk_documento_pdf" value="" size="25">';
    echo $html;
}

function lnk_documento_isbn_meta_box() {
    global $post;
    $isbn = get_post_meta( $post->ID, 'lnk_documento_isbn', true );
    $html .= '<input type="text" id="lnk_documento_isbn" name="lnk_documento_isbn" value="'.$isbn.'" size="8">';
    echo $html;
}


function lnk_documento_update_edit_form() {
    echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'lnk_documento_update_edit_form');

function lnk_documento_save_post_meta($id) {
    global $wpdb,$post_type;
    if($post_type == 'documento'){
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return $id;
        if (defined('DOING_AJAX') && DOING_AJAX)
                return $id;

        if(!empty($_FILES['lnk_documento_pdf']['name']) && $_FILES['lnk_documento_pdf']['error'] == 0) {
            $supported_types = array('application/pdf');
            $arr_file_type = wp_check_filetype(basename($_FILES['lnk_documento_pdf']['name']));
            $uploaded_type = $arr_file_type['type'];

            if(in_array($uploaded_type, $supported_types)) {
                $upload = wp_upload_bits($_FILES['lnk_documento_pdf']['name'], null, file_get_contents($_FILES['lnk_documento_pdf']['tmp_name']));

                if(isset($upload['error']) && $upload['error'] != 0) {
                    wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                } else {
                    update_post_meta($id, 'lnk_documento_pdf', $upload);
                }
            }
            else {
                wp_die("The file type that you've uploaded is not a PDF.");
            }
        }
        update_post_meta($id, 'lnk_documento_isbn', $_POST['lnk_documento_isbn']);
    }


}
add_action('save_post','lnk_documento_save_post_meta');
