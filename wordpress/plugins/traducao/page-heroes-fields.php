<?php
/**
 * Mídia de hero compartilhada entre EN e PT (Sobre, Educação, Contato).
 * Cada página: imagem e/ou vídeo (vídeo tem prioridade no front).
 */

if (defined('RS_PAGE_HEROES_LOADED')) {
    return;
}
define('RS_PAGE_HEROES_LOADED', true);

const RS_PAGE_HERO_ABOUT_IMAGE_KEY = 'rs_page_hero_about_image_id';
const RS_PAGE_HERO_EDUCATION_IMAGE_KEY = 'rs_page_hero_education_image_id';
const RS_PAGE_HERO_CONTACT_IMAGE_KEY = 'rs_page_hero_contact_image_id';

const RS_PAGE_HERO_ABOUT_VIDEO_KEY = 'rs_page_hero_about_video_id';
const RS_PAGE_HERO_EDUCATION_VIDEO_KEY = 'rs_page_hero_education_video_id';
const RS_PAGE_HERO_CONTACT_VIDEO_KEY = 'rs_page_hero_contact_video_id';

/** @return array<string, string> */
function rs_page_heroes_meta_keys(): array {
    return [
        'about'     => RS_PAGE_HERO_ABOUT_IMAGE_KEY,
        'education' => RS_PAGE_HERO_EDUCATION_IMAGE_KEY,
        'contact'   => RS_PAGE_HERO_CONTACT_IMAGE_KEY,
    ];
}

/** @return array<string, string> */
function rs_page_heroes_video_meta_keys(): array {
    return [
        'about'     => RS_PAGE_HERO_ABOUT_VIDEO_KEY,
        'education' => RS_PAGE_HERO_EDUCATION_VIDEO_KEY,
        'contact'   => RS_PAGE_HERO_CONTACT_VIDEO_KEY,
    ];
}

/** @return array<string, string> */
function rs_page_heroes_legacy_meta_keys(): array {
    return [
        'about'     => 'rs_about_hero_image_id',
        'education' => 'rs_education_hero_image_id',
        'contact'   => 'rs_contact_hero_image_id',
    ];
}

function rs_page_heroes_get_post_id(): int {
    $posts = get_posts([
        'post_type'      => 'page-heroes',
        'post_status'    => 'publish',
        'name'           => 'default',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

function rs_page_heroes_get_image_id(string $page_key): int {
    $keys = rs_page_heroes_meta_keys();
    if (!isset($keys[$page_key])) {
        return 0;
    }

    $post_id = rs_page_heroes_get_post_id();
    if ($post_id <= 0) {
        return 0;
    }

    return (int) get_post_meta($post_id, $keys[$page_key], true);
}

function rs_page_heroes_get_video_id(string $page_key): int {
    $keys = rs_page_heroes_video_meta_keys();
    if (!isset($keys[$page_key])) {
        return 0;
    }

    $post_id = rs_page_heroes_get_post_id();
    if ($post_id <= 0) {
        return 0;
    }

    return (int) get_post_meta($post_id, $keys[$page_key], true);
}

function rs_page_heroes_get_image_url(string $page_key, int $locale_post_id = 0): string {
    $image_id = rs_page_heroes_get_image_id($page_key);

    if ($image_id <= 0 && $locale_post_id > 0) {
        $legacy_keys = rs_page_heroes_legacy_meta_keys();
        if (isset($legacy_keys[$page_key])) {
            $image_id = (int) get_post_meta($locale_post_id, $legacy_keys[$page_key], true);
        }
    }

    if ($image_id <= 0) {
        return '';
    }

    return (string) wp_get_attachment_url($image_id);
}

function rs_page_heroes_get_video_url(string $page_key): string {
    $video_id = rs_page_heroes_get_video_id($page_key);
    if ($video_id <= 0) {
        return '';
    }

    return (string) wp_get_attachment_url($video_id);
}

/**
 * @return array{image: string, video: string}
 */
function rs_page_heroes_get_media(string $page_key, int $locale_post_id = 0): array {
    return [
        'image' => rs_page_heroes_get_image_url($page_key, $locale_post_id),
        'video' => rs_page_heroes_get_video_url($page_key),
    ];
}

function rs_page_heroes_payload(): array {
    $payload = [];

    foreach (array_keys(rs_page_heroes_meta_keys()) as $page_key) {
        $media = rs_page_heroes_get_media($page_key);
        // Compat: string = URL da imagem (legado).
        $payload[$page_key] = $media['image'];
        $payload[$page_key . 'Video'] = $media['video'];
        $payload[$page_key . 'Media'] = $media;
    }

    return $payload;
}

function rs_page_heroes_migrate_legacy_meta(): void {
    if (get_option('rs_page_heroes_migrated_v1')) {
        return;
    }

    $post_id = rs_page_heroes_get_post_id();
    if ($post_id <= 0) {
        return;
    }

    $locale_resolvers = [
        'about'     => 'rs_about_get_post_id_by_locale',
        'education' => 'rs_education_get_post_id_by_locale',
        'contact'   => 'rs_contact_get_post_id_by_locale',
    ];

    foreach (rs_page_heroes_meta_keys() as $page_key => $meta_key) {
        if ((int) get_post_meta($post_id, $meta_key, true) > 0) {
            continue;
        }

        $legacy_key = rs_page_heroes_legacy_meta_keys()[$page_key] ?? '';
        if ($legacy_key === '') {
            continue;
        }

        $resolver = $locale_resolvers[$page_key] ?? '';
        if (!is_string($resolver) || !function_exists($resolver)) {
            continue;
        }

        foreach (['en', 'pt'] as $locale) {
            $locale_post_id = (int) $resolver($locale);
            if ($locale_post_id <= 0) {
                continue;
            }

            $legacy_id = (int) get_post_meta($locale_post_id, $legacy_key, true);
            if ($legacy_id > 0) {
                update_post_meta($post_id, $meta_key, (string) $legacy_id);
                break;
            }
        }
    }

    update_option('rs_page_heroes_migrated_v1', 1);
}

function rs_page_heroes_ensure_post(): void {
    if (get_option('rs_page_heroes_post_ensured_v1')) {
        rs_page_heroes_migrate_legacy_meta();
        return;
    }

    if (rs_page_heroes_get_post_id() <= 0) {
        wp_insert_post([
            'post_title'  => 'Heroes das páginas',
            'post_status' => 'publish',
            'post_type'   => 'page-heroes',
            'post_name'   => 'default',
            'post_author' => 1,
        ], true);
    }

    update_option('rs_page_heroes_post_ensured_v1', 1);
    rs_page_heroes_migrate_legacy_meta();
}

add_action('init', function () {
    $all_keys = array_merge(
        array_values(rs_page_heroes_meta_keys()),
        array_values(rs_page_heroes_video_meta_keys())
    );

    foreach ($all_keys as $meta_key) {
        register_post_meta('page-heroes', $meta_key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);

add_action('init', 'rs_page_heroes_ensure_post', 26);

add_action('rest_api_init', function () {
    register_rest_route('rs/v1', '/page-heroes', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            return rs_page_heroes_payload();
        },
    ]);
});

add_action('add_meta_boxes_page-heroes', function () {
    add_meta_box(
        'rs_page_heroes_fields',
        'Heroes (imagem ou vídeo)',
        'rs_page_heroes_render_meta_box',
        'page-heroes',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'page-heroes', 'normal');
}, 10);

function rs_page_heroes_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_page_heroes_save', 'rs_page_heroes_nonce');

    $labels = [
        'about'     => 'Sobre Nós',
        'education' => 'Educação',
        'contact'   => 'Contato',
    ];

    echo '<p style="margin-top:0;color:#646970;">';
    echo 'Mídia de hero <strong>compartilhada entre inglês e português</strong>. ';
    echo 'Se houver <strong>vídeo</strong>, ele tem prioridade no site; a imagem serve de poster/fallback. ';
    echo 'O texto de cada página continua editável por idioma.';
    echo '</p>';

    $image_keys = rs_page_heroes_meta_keys();
    $video_keys = rs_page_heroes_video_meta_keys();

    foreach ($image_keys as $page_key => $image_meta_key) {
        $label = $labels[$page_key] ?? ucfirst($page_key);
        $image_id = (int) get_post_meta($post->ID, $image_meta_key, true);
        $video_meta_key = $video_keys[$page_key] ?? '';
        $video_id = $video_meta_key !== '' ? (int) get_post_meta($post->ID, $video_meta_key, true) : 0;

        echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
        echo '<legend style="font-weight:600;padding:0 6px;"><strong>' . esc_html($label) . '</strong></legend>';
        rs_render_media_field($image_meta_key, 'Imagem', $image_id, $image_meta_key, true, 'image');
        if ($video_meta_key !== '') {
            rs_render_media_field($video_meta_key, 'Vídeo (mp4) — opcional', $video_id, $video_meta_key, true, 'video');
        }
        echo '</fieldset>';
    }
}

add_action('save_post_page-heroes', function (int $post_id) {
    if (!isset($_POST['rs_page_heroes_nonce']) || !wp_verify_nonce($_POST['rs_page_heroes_nonce'], 'rs_page_heroes_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    $all_keys = array_merge(
        array_values(rs_page_heroes_meta_keys()),
        array_values(rs_page_heroes_video_meta_keys())
    );

    foreach ($all_keys as $meta_key) {
        if (!array_key_exists($meta_key, $_POST)) {
            continue;
        }

        $attachment_id = (int) $_POST[$meta_key];
        update_post_meta($post_id, $meta_key, (string) max(0, $attachment_id));
    }
});

rs_enqueue_admin_media_picker(['page-heroes']);

add_action('admin_menu', function () {
    $hook = add_menu_page(
        'Heroes',
        'Heroes',
        'edit_posts',
        'rs-page-heroes',
        'rs_page_heroes_admin_redirect',
        'dashicons-format-image',
        29.5
    );

    add_action("load-{$hook}", 'rs_page_heroes_admin_redirect');
}, 20);

function rs_page_heroes_admin_redirect(): void {
    $post_id = rs_page_heroes_get_post_id();

    if ($post_id > 0) {
        wp_safe_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
        exit;
    }

    wp_die('Post de heroes não encontrado. Recarregue o painel do WordPress.');
}
