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

function rs_section_hero_media_cleared(string $meta_key): bool {
    return !empty($_POST[$meta_key . '_cleared']);
}

function rs_section_parse_single_media_from_request(string $meta_key, int $previous): int {
    if (!array_key_exists($meta_key, $_POST) && !array_key_exists($meta_key . '_cleared', $_POST)) {
        return $previous;
    }

    if (!array_key_exists($meta_key, $_POST)) {
        return rs_section_hero_media_cleared($meta_key) ? 0 : $previous;
    }

    $posted = (int) $_POST[$meta_key];
    if ($posted > 0) {
        return $posted;
    }

    return rs_section_hero_media_cleared($meta_key) ? 0 : $previous;
}

function rs_section_save_hero_media(int $post_id, string $image_key, string $video_key): void {
    $prev_image = (int) get_post_meta($post_id, $image_key, true);
    $prev_video = (int) get_post_meta($post_id, $video_key, true);

    update_post_meta(
        $post_id,
        $image_key,
        (string) rs_section_parse_single_media_from_request($image_key, $prev_image)
    );
    update_post_meta(
        $post_id,
        $video_key,
        (string) rs_section_parse_single_media_from_request($video_key, $prev_video)
    );
}

/**
 * @param array<string, mixed> $shared
 * @return array{0: int, 1: int}
 */
function rs_section_shared_hero_resolve_ids(
    array $shared,
    int $post_id,
    string $legacy_image_key,
    string $legacy_video_key
): array {
    $hero_image_id = (int) ($shared['hero_image_id'] ?? 0);
    $hero_video_id = (int) ($shared['hero_video_id'] ?? 0);

    if ($hero_image_id <= 0 && $legacy_image_key !== '') {
        $hero_image_id = (int) get_post_meta($post_id, $legacy_image_key, true);
    }
    if ($hero_video_id <= 0 && $legacy_video_key !== '') {
        $hero_video_id = (int) get_post_meta($post_id, $legacy_video_key, true);
    }

    return [$hero_image_id, $hero_video_id];
}

function rs_section_shared_hero_echo_mirrors(
    int $hero_image_id,
    int $hero_video_id,
    string $prefix,
    string $image_source_id,
    string $video_source_id
): void {
    echo '<input type="hidden" name="' . esc_attr($prefix . '_hero_image_id') . '" id="' . esc_attr($prefix . '_hero_image_id_post') . '" data-rs-mirror-of="' . esc_attr($image_source_id) . '" value="' . esc_attr((string) $hero_image_id) . '" />';
    echo '<input type="hidden" name="' . esc_attr($prefix . '_hero_image_id_cleared') . '" id="' . esc_attr($prefix . '_hero_image_id_cleared_post') . '" data-rs-mirror-cleared-of="' . esc_attr($image_source_id) . '" value="0" />';
    echo '<input type="hidden" name="' . esc_attr($prefix . '_hero_video_id') . '" id="' . esc_attr($prefix . '_hero_video_id_post') . '" data-rs-mirror-of="' . esc_attr($video_source_id) . '" value="' . esc_attr((string) $hero_video_id) . '" />';
    echo '<input type="hidden" name="' . esc_attr($prefix . '_hero_video_id_cleared') . '" id="' . esc_attr($prefix . '_hero_video_id_cleared_post') . '" data-rs-mirror-cleared-of="' . esc_attr($video_source_id) . '" value="0" />';
}

/**
 * @param array<string, mixed> $shared
 */
function rs_section_shared_hero_render_fields(
    int $post_id,
    array $shared,
    string $prefix,
    string $shared_post_key,
    string $legacy_image_key,
    string $legacy_video_key,
    string $image_source_id,
    string $video_source_id,
    string $image_label = 'Imagem do hero',
    string $video_label = 'Vídeo do hero'
): void {
    [$hero_image_id, $hero_video_id] = rs_section_shared_hero_resolve_ids(
        $shared,
        $post_id,
        $legacy_image_key,
        $legacy_video_key
    );

    rs_section_shared_hero_echo_mirrors(
        $hero_image_id,
        $hero_video_id,
        $prefix,
        $image_source_id,
        $video_source_id
    );
    rs_render_media_field(
        $shared_post_key . '[hero_image_id]',
        $image_label,
        $hero_image_id,
        $image_source_id,
        true,
        'image'
    );
    rs_render_media_field(
        $shared_post_key . '[hero_video_id]',
        $video_label,
        $hero_video_id,
        $video_source_id,
        true,
        'video'
    );
}

/**
 * @return array{image: bool, video: bool}
 */
function rs_section_shared_hero_media_cleared(string $prefix): array {
    return [
        'image' => !empty($_POST[$prefix . '_hero_image_id_cleared'])
            || !empty($_POST[$prefix . '_shared[hero_image_id]_cleared']),
        'video' => !empty($_POST[$prefix . '_hero_video_id_cleared'])
            || !empty($_POST[$prefix . '_shared[hero_video_id]_cleared']),
    ];
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function rs_section_shared_hero_parse_from_request(
    array $data,
    string $prefix,
    string $shared_post_key = ''
): array {
    $shared_post_key = $shared_post_key !== '' ? $shared_post_key : $prefix . '_shared';
    $cleared = rs_section_shared_hero_media_cleared($prefix);
    $prev_image = (int) ($data['shared']['hero_image_id'] ?? 0);
    $prev_video = (int) ($data['shared']['hero_video_id'] ?? 0);

    if (array_key_exists($prefix . '_hero_image_id', $_POST)) {
        $posted = (int) $_POST[$prefix . '_hero_image_id'];
        if ($posted > 0) {
            $data['shared']['hero_image_id'] = $posted;
        } elseif ($cleared['image']) {
            $data['shared']['hero_image_id'] = 0;
        } else {
            $data['shared']['hero_image_id'] = $prev_image;
        }
    } else {
        $shared = isset($_POST[$shared_post_key]) && is_array($_POST[$shared_post_key])
            ? wp_unslash($_POST[$shared_post_key])
            : [];
        if (!array_key_exists('hero_image_id', $shared)) {
            $data['shared']['hero_image_id'] = $prev_image;
        } else {
            $posted = (int) $shared['hero_image_id'];
            if ($posted > 0) {
                $data['shared']['hero_image_id'] = $posted;
            } elseif ($cleared['image']) {
                $data['shared']['hero_image_id'] = 0;
            } else {
                $data['shared']['hero_image_id'] = $prev_image;
            }
        }
    }

    if (array_key_exists($prefix . '_hero_video_id', $_POST)) {
        $posted = (int) $_POST[$prefix . '_hero_video_id'];
        if ($posted > 0) {
            $data['shared']['hero_video_id'] = $posted;
        } elseif ($cleared['video']) {
            $data['shared']['hero_video_id'] = 0;
        } else {
            $data['shared']['hero_video_id'] = $prev_video;
        }
    } else {
        $shared = isset($_POST[$shared_post_key]) && is_array($_POST[$shared_post_key])
            ? wp_unslash($_POST[$shared_post_key])
            : [];
        if (!array_key_exists('hero_video_id', $shared)) {
            $data['shared']['hero_video_id'] = $prev_video;
        } else {
            $posted = (int) $shared['hero_video_id'];
            if ($posted > 0) {
                $data['shared']['hero_video_id'] = $posted;
            } elseif ($cleared['video']) {
                $data['shared']['hero_video_id'] = 0;
            } else {
                $data['shared']['hero_video_id'] = $prev_video;
            }
        }
    }

    return $data;
}

/**
 * @param array<string, mixed> $incoming
 * @param array<string, mixed> $previous
 * @return array<string, mixed>
 */
function rs_section_shared_hero_guard_against_wipe(
    array $incoming,
    array $previous,
    string $prefix,
    int $post_id = 0,
    string $legacy_image_key = '',
    string $legacy_video_key = '',
    string $log_label = ''
): array {
    $cleared = rs_section_shared_hero_media_cleared($prefix);
    $prev_image = (int) ($previous['shared']['hero_image_id'] ?? 0);
    $prev_video = (int) ($previous['shared']['hero_video_id'] ?? 0);

    if ($legacy_image_key !== '' && $prev_image <= 0 && $post_id > 0) {
        $prev_image = (int) get_post_meta($post_id, $legacy_image_key, true);
    }
    if ($legacy_video_key !== '' && $prev_video <= 0 && $post_id > 0) {
        $prev_video = (int) get_post_meta($post_id, $legacy_video_key, true);
    }

    if ($prev_image <= 0 && $prev_video <= 0) {
        return $incoming;
    }

    $log_label = $log_label !== '' ? $log_label : $prefix;

    if (!$cleared['image']
        && (int) ($incoming['shared']['hero_image_id'] ?? 0) <= 0
        && $prev_image > 0
    ) {
        $incoming['shared']['hero_image_id'] = $prev_image;
        if (function_exists('rs_cms_log')) {
            rs_cms_log('guard.restore', ['post_id' => $post_id, 'field' => $log_label . '.hero_image_id', 'value' => $prev_image], 'warning');
        }
    }

    if (!$cleared['video']
        && (int) ($incoming['shared']['hero_video_id'] ?? 0) <= 0
        && $prev_video > 0
    ) {
        $incoming['shared']['hero_video_id'] = $prev_video;
        if (function_exists('rs_cms_log')) {
            rs_cms_log('guard.restore', ['post_id' => $post_id, 'field' => $log_label . '.hero_video_id', 'value' => $prev_video], 'warning');
        }
    }

    return $incoming;
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
