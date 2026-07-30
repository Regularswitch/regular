<?php
/**
 * Helpers de hero (imagem/vídeo) por seção — Sobre, Educação, Contato.
 */

if (defined('RS_SECTION_HERO_FIELDS_LOADED')) {
    return;
}
define('RS_SECTION_HERO_FIELDS_LOADED', true);

/**
 * Resolve imagem/vídeo de hero no post da seção, com fallback do antigo CPT page-heroes.
 *
 * @return array{image: string, video: string}
 */
function rs_section_hero_media(int $post_id, string $image_key, string $video_key, string $page_key): array {
    $image_id = (int) get_post_meta($post_id, $image_key, true);
    $video_id = (int) get_post_meta($post_id, $video_key, true);

    if ($image_id <= 0 && function_exists('rs_page_heroes_get_image_id')) {
        $shared = (int) rs_page_heroes_get_image_id($page_key);
        if ($shared > 0) {
            update_post_meta($post_id, $image_key, (string) $shared);
            $image_id = $shared;
        }
    }

    if ($video_id <= 0 && function_exists('rs_page_heroes_get_video_id')) {
        $shared_video = (int) rs_page_heroes_get_video_id($page_key);
        if ($shared_video > 0) {
            update_post_meta($post_id, $video_key, (string) $shared_video);
            $video_id = $shared_video;
        }
    }

    $image_url = $image_id > 0 ? (string) wp_get_attachment_url($image_id) : '';
    if ($image_url === '' && function_exists('rs_page_heroes_get_image_url')) {
        $image_url = rs_page_heroes_get_image_url($page_key, $post_id);
    }

    $video_url = $video_id > 0 ? (string) wp_get_attachment_url($video_id) : '';
    if ($video_url === '' && function_exists('rs_page_heroes_get_video_url')) {
        $video_url = rs_page_heroes_get_video_url($page_key);
    }

    return [
        'image' => $image_url,
        'video' => $video_url,
    ];
}

function rs_section_save_hero_media(int $post_id, string $image_key, string $video_key): void {
    if (array_key_exists($image_key, $_POST)) {
        update_post_meta($post_id, $image_key, (string) max(0, (int) $_POST[$image_key]));
    }
    if (array_key_exists($video_key, $_POST)) {
        update_post_meta($post_id, $video_key, (string) max(0, (int) $_POST[$video_key]));
    }
}

function rs_section_render_hero_fields(int $post_id, string $image_key, string $video_key): void {
    $image_id = (int) get_post_meta($post_id, $image_key, true);
    $video_id = (int) get_post_meta($post_id, $video_key, true);

    echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Hero</strong></legend>';
    echo '<p style="margin:0 0 12px;color:#646970;font-size:12px;">Imagem e/ou vídeo. Se houver vídeo, ele tem prioridade no site (a imagem vira poster).</p>';
    rs_render_media_field($image_key, 'Imagem', $image_id, $image_key, true, 'image');
    rs_render_media_field($video_key, 'Vídeo (mp4) — opcional', $video_id, $video_key, true, 'video');
    echo '</fieldset>';
}

function rs_section_copy_hero_media(int $from_id, int $to_id, string $image_key, string $video_key): void {
    update_post_meta($to_id, $image_key, get_post_meta($from_id, $image_key, true));
    update_post_meta($to_id, $video_key, get_post_meta($from_id, $video_key, true));
}
