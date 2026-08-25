<?php
/**
 * REST legado api-etc/v2/all-posts — consumido pelo Next.js (ApiWp.ts).
 */

if (defined('RS_REST_ALL_POSTS_LOADED')) {
    return;
}
define('RS_REST_ALL_POSTS_LOADED', true);

add_action('rest_api_init', function (): void {
    register_rest_route('api-etc/v2', '/all-posts', [
        'methods'             => 'GET',
        'callback'            => 'rs_rest_all_posts_callback',
        'permission_callback' => '__return_true',
    ]);
});

function rs_rest_attachment_info(int $file_id): array {
    if ($file_id <= 0) {
        return [
            'url'    => '',
            'width'  => 0,
            'height' => 0,
        ];
    }

    $file = wp_get_attachment_metadata($file_id);

    return [
        'url'    => (string) wp_get_attachment_url($file_id),
        'width'  => (int) ($file['width'] ?? 0),
        'height' => (int) ($file['height'] ?? 0),
    ];
}

function rs_rest_all_posts_callback(): array {
    $posts = get_posts([
        'numberposts' => -1,
        'post_type'   => 'project',
        'post_status' => 'publish',
    ]);

    $response = [];

    foreach ($posts as $post) {
        $post_meta = get_post_meta($post->ID);
        $response[] = [
            'ID'            => (int) $post->ID,
            'slug'          => (string) $post->post_name,
            'title'         => (string) $post->post_title,
            'img_single'    => rs_rest_attachment_info((int) ($post_meta['etc_upload_image'][0] ?? 0)),
            'img_secondary' => rs_rest_attachment_info((int) ($post_meta['etc_project_secondary_thumbnail'][0] ?? 0)),
            'img_primary'   => rs_rest_attachment_info((int) get_post_thumbnail_id($post->ID)),
            'video'         => rs_rest_attachment_info((int) ($post_meta['etc_video_url'][0] ?? 0)),
            'project_data'  => function_exists('rs_project_meta_to_payload')
                ? rs_project_meta_to_payload((int) $post->ID)
                : null,
        ];
    }

    return $response;
}
