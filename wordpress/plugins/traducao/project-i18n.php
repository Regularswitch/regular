<?php
/**
 * Projetos bilíngues em um único post (EN + PT + mídia compartilhada).
 */

if (defined('RS_PROJECT_I18N_LOADED')) {
    return;
}
define('RS_PROJECT_I18N_LOADED', true);

const RS_PROJECT_I18N_KEY = 'rs_project_i18n';
const RS_PROJECT_I18N_VERSION = 1;

/**
 * @return array<string, mixed>
 */
function rs_project_i18n_default_locale(): array {
    return [
        'title'     => '',
        'excerpt'   => '',
        'accordion' => [],
        'youtube'   => [],
    ];
}

/**
 * @return array<string, mixed>
 */
function rs_project_i18n_default(): array {
    return [
        'v'       => RS_PROJECT_I18N_VERSION,
        'shared'  => [
            'hero_id'              => 0,
            'logo_id'              => 0,
            'gallery_ids'          => '',
            'gallery_featured_ids' => '',
            'featured_home'        => false,
            'show_vignette'        => true,
        ],
        'locales' => [
            'en' => rs_project_i18n_default_locale(),
            'pt' => rs_project_i18n_default_locale(),
        ],
    ];
}

function rs_project_i18n_normalize_locale_key(string $locale): string {
    return strtolower($locale) === 'pt' ? 'pt' : 'en';
}

/**
 * @param array<string, mixed> $raw
 * @return array<string, mixed>
 */
function rs_project_i18n_normalize(array $raw): array {
    $base = rs_project_i18n_default();
    $shared = is_array($raw['shared'] ?? null) ? $raw['shared'] : [];

    $base['shared']['hero_id'] = (int) ($shared['hero_id'] ?? 0);
    $base['shared']['logo_id'] = (int) ($shared['logo_id'] ?? 0);
    $base['shared']['gallery_ids'] = trim((string) ($shared['gallery_ids'] ?? ''));
    $base['shared']['gallery_featured_ids'] = trim((string) ($shared['gallery_featured_ids'] ?? ''));
    $base['shared']['featured_home'] = !empty($shared['featured_home']);
    $base['shared']['show_vignette'] = !array_key_exists('show_vignette', $shared) || !empty($shared['show_vignette']);

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $base['locales'][$locale]['title'] = trim((string) ($loc['title'] ?? ''));
        $base['locales'][$locale]['excerpt'] = trim((string) ($loc['excerpt'] ?? ''));
        $base['locales'][$locale]['accordion'] = rs_project_normalize_accordion_sections(
            is_array($loc['accordion'] ?? null) ? $loc['accordion'] : []
        );
        $base['locales'][$locale]['youtube'] = rs_project_normalize_youtube_videos(
            is_array($loc['youtube'] ?? null) ? $loc['youtube'] : []
        );
    }

    return $base;
}

function rs_project_i18n_is_migrated(int $post_id): bool {
    $raw = get_post_meta($post_id, RS_PROJECT_I18N_KEY, true);

    return is_string($raw) && $raw !== '';
}

/**
 * @return array<string, mixed>
 */
function rs_project_i18n_get(int $post_id): array {
    $raw = get_post_meta($post_id, RS_PROJECT_I18N_KEY, true);
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return rs_project_i18n_normalize($decoded);
        }
    }

    return rs_project_i18n_from_legacy_post($post_id);
}

/**
 * Monta i18n a partir das meta keys legadas (pré-migração ou leitura temporária).
 *
 * @return array<string, mixed>
 */
function rs_project_i18n_from_legacy_post(int $post_id, int $pt_id = 0): array {
    $data = rs_project_i18n_default();
    $post = get_post($post_id);
    if (!$post) {
        return $data;
    }

    if ($pt_id <= 0) {
        $pt_id = (int) get_post_meta($post_id, 'PT', true);
    }

    $data['shared']['hero_id'] = rs_project_get_hero_id($post_id);
    $data['shared']['logo_id'] = (int) get_post_meta($post_id, RS_PROJECT_LOGO_KEY, true);
    $data['shared']['gallery_ids'] = implode(',', rs_project_get_gallery_ids($post_id));
    $data['shared']['gallery_featured_ids'] = implode(',', rs_project_get_gallery_featured_ids($post_id));
    $data['shared']['featured_home'] = (bool) get_post_meta($post_id, RS_PROJECT_FEATURED_KEY, true);
    $vignette = get_post_meta($post_id, RS_PROJECT_VIGNETTE_KEY, true);
    $data['shared']['show_vignette'] = $vignette === '' ? true : (bool) $vignette;

    $data['locales']['en']['title'] = (string) $post->post_title;
    $data['locales']['en']['excerpt'] = (string) $post->post_excerpt;
    $data['locales']['en']['accordion'] = rs_project_get_accordion_sections($post_id);
    $data['locales']['en']['youtube'] = rs_project_get_youtube_videos($post_id);

    if ($pt_id > 0) {
        $pt_post = get_post($pt_id);
        if ($pt_post) {
            $data['locales']['pt']['title'] = (string) $pt_post->post_title;
            $data['locales']['pt']['excerpt'] = (string) $pt_post->post_excerpt;
            $data['locales']['pt']['accordion'] = rs_project_get_accordion_sections($pt_id);
            $data['locales']['pt']['youtube'] = rs_project_get_youtube_videos($pt_id);
        }
    }

    return rs_project_i18n_normalize($data);
}

/**
 * @param array<string, mixed> $data
 */
function rs_project_i18n_save(int $post_id, array $data): void {
    $normalized = rs_project_i18n_normalize($data);
    update_post_meta($post_id, RS_PROJECT_I18N_KEY, wp_json_encode($normalized, JSON_UNESCAPED_UNICODE));
    rs_project_i18n_sync_legacy_meta($post_id, $normalized);
}

/**
 * Mantém meta legadas alinhadas ao EN + mídia compartilhada (api-etc / fallbacks).
 *
 * @param array<string, mixed> $data
 */
function rs_project_i18n_sync_legacy_meta(int $post_id, array $data): void {
    $shared = $data['shared'] ?? [];
    $en = $data['locales']['en'] ?? rs_project_i18n_default_locale();

    $hero_id = (int) ($shared['hero_id'] ?? 0);
    if ($hero_id > 0) {
        update_post_meta($post_id, RS_PROJECT_HERO_KEY, $hero_id);
        update_post_meta($post_id, 'etc_upload_image', $hero_id);
    } else {
        delete_post_meta($post_id, RS_PROJECT_HERO_KEY);
        delete_post_meta($post_id, 'etc_upload_image');
    }

    $logo_id = (int) ($shared['logo_id'] ?? 0);
    if ($logo_id > 0) {
        update_post_meta($post_id, RS_PROJECT_LOGO_KEY, $logo_id);
        set_post_thumbnail($post_id, $logo_id);
    } else {
        delete_post_meta($post_id, RS_PROJECT_LOGO_KEY);
    }

    update_post_meta($post_id, RS_PROJECT_GALLERY_KEY, (string) ($shared['gallery_ids'] ?? ''));
    update_post_meta($post_id, RS_PROJECT_GALLERY_FEATURED_KEY, (string) ($shared['gallery_featured_ids'] ?? ''));
    update_post_meta($post_id, RS_PROJECT_FEATURED_KEY, !empty($shared['featured_home']) ? 1 : 0);
    update_post_meta($post_id, RS_PROJECT_VIGNETTE_KEY, !empty($shared['show_vignette']) ? 1 : 0);

    $accordion = is_array($en['accordion'] ?? null) ? $en['accordion'] : [];
    update_post_meta($post_id, RS_PROJECT_ACCORDION_KEY, wp_json_encode($accordion, JSON_UNESCAPED_UNICODE));
    update_post_meta($post_id, RS_PROJECT_YOUTUBE_KEY, wp_json_encode($en['youtube'] ?? [], JSON_UNESCAPED_SLASHES));

    foreach (array_keys(RS_PROJECT_LEGACY_ACCORDION_LABELS) as $index) {
        $legacy_body = $accordion[$index - 1]['body'] ?? '';
        update_post_meta($post_id, "rs_project_acc_{$index}_body", $legacy_body);
    }
}

/**
 * @return array{title: string, excerpt: string}
 */
function rs_project_i18n_get_locale_text(int $post_id, string $locale): array {
    $locale = rs_project_i18n_normalize_locale_key($locale);
    $data = rs_project_i18n_get($post_id);
    $post = get_post($post_id);
    $loc = $data['locales'][$locale] ?? rs_project_i18n_default_locale();

    $title = trim((string) ($loc['title'] ?? ''));
    $excerpt = trim((string) ($loc['excerpt'] ?? ''));

    if ($locale === 'en' && $post) {
        if ($title === '') {
            $title = (string) $post->post_title;
        }
        if ($excerpt === '') {
            $excerpt = (string) $post->post_excerpt;
        }
    }

    return [
        'title'   => $title,
        'excerpt' => $excerpt,
    ];
}

function rs_project_meta_to_payload_for_locale(int $post_id, string $locale = 'en'): array {
    $locale = rs_project_i18n_normalize_locale_key($locale);
    $data = rs_project_i18n_get($post_id);
    $shared = $data['shared'];
    $loc = $data['locales'][$locale] ?? rs_project_i18n_default_locale();

    $accordion = [];
    $index = 1;
    foreach ($loc['accordion'] as $section) {
        $body = trim((string) ($section['body'] ?? ''));
        if ($body === '') {
            continue;
        }
        $accordion[] = [
            'index' => $index,
            'title' => trim((string) ($section['title'] ?? '')),
            'body'  => wpautop($body),
        ];
        $index += 1;
    }

    // Fallback EN → PT para campos textuais vazios.
    if ($locale === 'pt' && $accordion === []) {
        $en_loc = $data['locales']['en'] ?? rs_project_i18n_default_locale();
        foreach ($en_loc['accordion'] as $section) {
            $body = trim((string) ($section['body'] ?? ''));
            if ($body === '') {
                continue;
            }
            $accordion[] = [
                'index' => $index,
                'title' => trim((string) ($section['title'] ?? '')),
                'body'  => wpautop($body),
            ];
            $index += 1;
        }
    }

    $gallery_ids = array_values(array_filter(array_map('intval', explode(',', (string) ($shared['gallery_ids'] ?? '')))));
    $featured_ids = array_flip(array_values(array_filter(array_map('intval', explode(',', (string) ($shared['gallery_featured_ids'] ?? '')))));
    $gallery = [];
    foreach ($gallery_ids as $attachment_id) {
        $info = rs_project_attachment_info($attachment_id);
        if ($info && !empty($info['url'])) {
            $info['featured'] = isset($featured_ids[$attachment_id]);
            $gallery[] = $info;
        }
    }

    $youtube = $loc['youtube'] ?? [];
    if ($locale === 'pt' && $youtube === []) {
        $youtube = $data['locales']['en']['youtube'] ?? [];
    }

    $hero_id = (int) ($shared['hero_id'] ?? 0);
    $logo_id = (int) ($shared['logo_id'] ?? 0);

    return [
        'heroImage'      => rs_project_attachment_info($hero_id > 0 ? $hero_id : rs_project_get_hero_id($post_id)),
        'logoImage'      => rs_project_attachment_info($logo_id > 0 ? $logo_id : (int) get_post_meta($post_id, RS_PROJECT_LOGO_KEY, true)),
        'accordion'      => $accordion,
        'gallery'        => $gallery,
        'youtubeVideos'  => $youtube,
        'featuredOnHome' => !empty($shared['featured_home']),
        'showVignette'   => !array_key_exists('show_vignette', $shared) || !empty($shared['show_vignette']),
    ];
}

/**
 * Resolve o ID canônico (post único) a partir de qualquer ID legado EN ou PT twin.
 */
function rs_project_resolve_canonical_id(int $post_id): int {
    if ($post_id <= 0) {
        return 0;
    }

    $en_id = (int) get_post_meta($post_id, 'EN', true);
    if ($en_id > 0) {
        return $en_id;
    }

    return $post_id;
}

function rs_project_is_legacy_twin(int $post_id): bool {
    return (int) get_post_meta($post_id, 'EN', true) > 0;
}

/**
 * Migra pares EN/PT legados para rs_project_i18n e remove gêmeos PT.
 */
function rs_project_migrate_to_i18n_once(): void {
    if (get_option('rs_project_i18n_migrated_v1')) {
        return;
    }

    $processed = [];

    $canonical_ids = get_posts([
        'post_type'      => 'project',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'EN',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);

    foreach ($canonical_ids as $raw_id) {
        $en_id = (int) $raw_id;
        if ($en_id <= 0 || isset($processed[$en_id])) {
            continue;
        }

        $pt_id = (int) get_post_meta($en_id, 'PT', true);
        $pt_post = $pt_id > 0 ? get_post($pt_id) : null;
        if (!$pt_post || $pt_post->post_type !== 'project' || $pt_post->post_status === 'trash') {
            $pt_id = 0;
        }

        $i18n = rs_project_i18n_from_legacy_post($en_id, $pt_id);
        rs_project_i18n_save($en_id, $i18n);

        if ($pt_id > 0) {
            delete_post_meta($en_id, 'PT');
            delete_post_meta($pt_id, 'EN');
            wp_trash_post($pt_id);
        }

        $processed[$en_id] = true;
    }

    // Gêmeos PT órfãos (EN meta aponta para post inexistente ou já migrado).
    $twins = get_posts([
        'post_type'      => 'project',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'EN',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    foreach ($twins as $raw_id) {
        $pt_id = (int) $raw_id;
        $en_id = (int) get_post_meta($pt_id, 'EN', true);
        if ($en_id > 0 && !rs_project_i18n_is_migrated($en_id)) {
            $i18n = rs_project_i18n_from_legacy_post($en_id, $pt_id);
            rs_project_i18n_save($en_id, $i18n);
            delete_post_meta($en_id, 'PT');
            delete_post_meta($pt_id, 'EN');
        }
        wp_trash_post($pt_id);
    }

    // Normaliza slugs *-pt no canônico.
    $all = get_posts([
        'post_type'      => 'project',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($all as $raw_id) {
        $post_id = (int) $raw_id;
        $post = get_post($post_id);
        if (!$post || !preg_match('/-pt$/i', (string) $post->post_name)) {
            continue;
        }
        $new_slug = preg_replace('/-pt$/i', '', (string) $post->post_name);
        if ($new_slug !== '' && $new_slug !== $post->post_name) {
            wp_update_post([
                'ID'        => $post_id,
                'post_name' => sanitize_title($new_slug),
            ]);
        }
    }

    update_option('rs_project_i18n_migrated_v1', 1);
}

add_action('init', 'rs_project_migrate_to_i18n_once', 15);

add_action('init', function () {
    register_post_meta('project', RS_PROJECT_I18N_KEY, [
        'single'        => true,
        'type'          => 'string',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
}, 21);

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_project_i18n_parse_accordion_json_field(string $field_name): array {
    if (empty($_POST[$field_name])) {
        return [];
    }

    $decoded = json_decode(wp_unslash((string) $_POST[$field_name]), true);
    if (!is_array($decoded)) {
        return [];
    }

    return rs_project_normalize_accordion_sections($decoded);
}

/**
 * @return array<int, array{id: string, url: string}>
 */
function rs_project_i18n_parse_youtube_json_field(string $field_name): array {
    if (empty($_POST[$field_name])) {
        return [];
    }

    $decoded = json_decode(wp_unslash((string) $_POST[$field_name]), true);
    if (!is_array($decoded)) {
        return [];
    }

    return rs_project_normalize_youtube_videos($decoded);
}

/**
 * @return array<string, mixed>
 */
function rs_project_i18n_parse_from_request(int $post_id): array {
    $data = rs_project_i18n_get($post_id);
    $post = get_post($post_id);

    $hero_id = isset($_POST['rs_project_hero_id']) ? (int) $_POST['rs_project_hero_id'] : 0;
    $logo_id = isset($_POST['rs_project_logo_id']) ? (int) $_POST['rs_project_logo_id'] : 0;

    $gallery_ids = [];
    if (!empty($_POST['rs_project_gallery_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_project_gallery_json']), true);
        if (is_array($decoded)) {
            $gallery_ids = array_values(array_filter(array_map('intval', $decoded)));
        }
    }

    $featured_ids = [];
    if (!empty($_POST['rs_project_gallery_featured_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_project_gallery_featured_json']), true);
        if (is_array($decoded)) {
            $featured_ids = array_values(array_filter(array_map('intval', $decoded)));
        }
    }

    $data['shared'] = [
        'hero_id'              => $hero_id,
        'logo_id'              => $logo_id,
        'gallery_ids'          => implode(',', $gallery_ids),
        'gallery_featured_ids' => implode(',', $featured_ids),
        'featured_home'        => !empty($_POST['rs_project_featured_home']),
        'show_vignette'        => !empty($_POST['rs_project_show_vignette']),
    ];

    $data['locales']['en']['title'] = $post ? (string) $post->post_title : '';
    $data['locales']['en']['excerpt'] = isset($_POST['excerpt'])
        ? wp_kses_post(wp_unslash((string) $_POST['excerpt']))
        : ($post ? (string) $post->post_excerpt : '');
    $parsed_en_accordion = rs_project_i18n_parse_accordion_json_field('rs_project_accordion_en_json');
    if ($parsed_en_accordion !== [] || !empty($_POST['rs_project_accordion_en_json'])) {
        $data['locales']['en']['accordion'] = $parsed_en_accordion;
    }
    $parsed_en_youtube = rs_project_i18n_parse_youtube_json_field('rs_project_youtube_en_json');
    if ($parsed_en_youtube !== [] || !empty($_POST['rs_project_youtube_en_json'])) {
        $data['locales']['en']['youtube'] = $parsed_en_youtube;
    }

    $data['locales']['pt']['title'] = isset($_POST['rs_project_pt_title'])
        ? sanitize_text_field(wp_unslash((string) $_POST['rs_project_pt_title']))
        : (string) ($data['locales']['pt']['title'] ?? '');
    $data['locales']['pt']['excerpt'] = isset($_POST['rs_project_pt_excerpt'])
        ? wp_kses_post(wp_unslash((string) $_POST['rs_project_pt_excerpt']))
        : (string) ($data['locales']['pt']['excerpt'] ?? '');
    $parsed_pt_accordion = rs_project_i18n_parse_accordion_json_field('rs_project_accordion_pt_json');
    if ($parsed_pt_accordion !== [] || !empty($_POST['rs_project_accordion_pt_json'])) {
        $data['locales']['pt']['accordion'] = $parsed_pt_accordion;
    }
    $parsed_pt_youtube = rs_project_i18n_parse_youtube_json_field('rs_project_youtube_pt_json');
    if ($parsed_pt_youtube !== [] || !empty($_POST['rs_project_youtube_pt_json'])) {
        $data['locales']['pt']['youtube'] = $parsed_pt_youtube;
    }

    return rs_project_i18n_normalize($data);
}

add_action('load-post.php', function (): void {
    if (!isset($_GET['post'], $_GET['action']) || $_GET['action'] !== 'edit') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'project') {
        return;
    }

    $post_id = (int) $_GET['post'];
    if (!function_exists('rs_project_is_legacy_twin') || !rs_project_is_legacy_twin($post_id)) {
        return;
    }

    $canonical = rs_project_resolve_canonical_id($post_id);
    if ($canonical <= 0 || $canonical === $post_id) {
        return;
    }

    wp_safe_redirect(
        add_query_arg('rs_project_tab', 'pt', admin_url('post.php?post=' . $canonical . '&action=edit'))
    );
    exit;
});
