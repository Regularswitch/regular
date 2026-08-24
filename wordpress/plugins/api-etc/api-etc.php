<?php

/*
 * Plugin Name:       Api Rest Etc Extension
 * Plugin URI:        https://regularswitch.com
 * Description:       Exibe campos personalizados da postagem customizada projects
 * Version:           0.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Regular Witch
 * Author URI:        https://regularswitch.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       api-etc
 * Domain Path:       /languages
 */


add_action('rest_api_init', 'add_rota_personalizada');
function add_rota_personalizada()
{
    register_rest_route('api-etc/v2', '/all-posts', array(
        'methods' => 'GET',
        'callback' => 'campos_personalizados',
    ));
}

function file_info($file_id) {
    $file = wp_get_attachment_metadata($file_id);
    return [
        "url" => wp_get_attachment_url($file_id),
        "width" => $file['width'] ?? 0,
        "height" => $file['height'] ?? 0,
    ];
}

function campos_personalizados( $data ) {
    $posts = get_posts([
        'numberposts' => -1,
        'post_type'   => 'project',
        'post_status' => 'publish',
    ]);
    $response = array();
    foreach( $posts as $post ) {
        // wp_get_attachment_url
        $post_meta = get_post_meta( $post->ID );
        $response[] = array(
            'ID' => $post->ID,
            'slug' => $post->post_name,
            'title' => $post->post_title,
            'img_single' => file_info($post_meta['etc_upload_image'][0] ?? 0),
            'img_secondary' => file_info($post_meta['etc_project_secondary_thumbnail'][0] ?? 0),
            'img_primary' => file_info(get_post_thumbnail_id($post->ID)),
            'video' => file_info($post_meta['etc_video_url'][0] ?? 0),
            'project_data' => function_exists('rs_project_meta_to_payload')
                ? rs_project_meta_to_payload((int) $post->ID)
                : null,
        );
    }
    return $response;
}