<?php
/**
 * Helpers para seções bilíngues em post único (shared + locales.en/pt).
 * Padrão alinhado a rs_project_i18n.
 */

if (defined('RS_SECTION_I18N_LOADED')) {
    return;
}
define('RS_SECTION_I18N_LOADED', true);

/**
 * CPTs migrados para post único bilíngue (sem gêmeos EN/PT).
 *
 * @return list<string>
 */
function rs_section_i18n_post_types(): array {
    return [
        'projects-page',
        'capabilities',
        'intro',
        'footer',
        'about',
        'education',
        'contact',
        'legal',
        'site-ui',
    ];
}

function rs_section_i18n_is_migrated_type(string $post_type): bool {
    return in_array($post_type, rs_section_i18n_post_types(), true);
}

function rs_section_i18n_normalize_locale(?string $locale): string {
    $locale = strtolower(trim((string) $locale));
    if ($locale === 'pt' || $locale === 'pt-br' || $locale === 'pt_br') {
        return 'pt';
    }

    return 'en';
}

/**
 * Locale a partir do request REST (?translate=PT ou ?slug=pt).
 */
function rs_section_i18n_locale_from_request($request = null): string {
    if ($request instanceof WP_REST_Request) {
        $translate = $request->get_param('translate');
        if (is_string($translate) && $translate !== '') {
            return rs_section_i18n_normalize_locale($translate);
        }
        $slug = $request->get_param('slug');
        if (is_string($slug) && $slug !== '') {
            return rs_section_i18n_normalize_locale($slug);
        }
    }

    if (isset($_GET['translate'])) {
        return rs_section_i18n_normalize_locale((string) wp_unslash($_GET['translate']));
    }
    if (isset($_GET['slug'])) {
        return rs_section_i18n_normalize_locale((string) wp_unslash($_GET['slug']));
    }

    return 'en';
}

/**
 * ID canônico do CPT (post único publicado).
 */
function rs_section_i18n_canonical_id(string $post_type): int {
    if (!rs_section_i18n_is_migrated_type($post_type)) {
        return 0;
    }

    $by_slug = get_posts([
        'post_type'      => $post_type,
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'name'           => 'main',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);
    if (!empty($by_slug[0])) {
        return (int) $by_slug[0];
    }

    // Preferência: post sem meta EN (não é gêmeo PT).
    $posts = get_posts([
        'post_type'      => $post_type,
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ]);

    foreach ($posts as $id) {
        $id = (int) $id;
        if ((int) get_post_meta($id, 'EN', true) <= 0) {
            return $id;
        }
    }

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

/**
 * Resolve ID canônico a partir de qualquer ID (EN ou gêmeo PT legado).
 */
function rs_section_i18n_resolve_id(int $post_id): int {
    if ($post_id <= 0) {
        return 0;
    }

    $en_id = (int) get_post_meta($post_id, 'EN', true);
    if ($en_id > 0) {
        return $en_id;
    }

    return $post_id;
}

/**
 * @param array<string, mixed> $data
 */
function rs_section_i18n_save(int $post_id, string $meta_key, array $data): void {
    if (function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, $meta_key, $data);
        return;
    }

    update_post_meta($post_id, $meta_key, $data);
}

/**
 * @return array<string, mixed>|null
 */
function rs_section_i18n_get_raw(int $post_id, string $meta_key): ?array {
    if (function_exists('rs_meta_get_array')) {
        $decoded = rs_meta_get_array($post_id, $meta_key);
        return is_array($decoded) ? $decoded : null;
    }

    $raw = get_post_meta($post_id, $meta_key, true);
    return is_array($raw) ? $raw : null;
}

/**
 * Garante um post canônico (slug main) e lixeira gêmeos PT/EN extras.
 *
 * @param callable(int, string): array $build_locale_from_post fn($post_id, $locale): locale payload
 * @param callable(array): array       $normalize             fn($raw): full i18n shape
 * @return int canonical post ID
 */
function rs_section_i18n_migrate_twins(
    string $post_type,
    string $meta_key,
    string $option_key,
    string $title_en,
    callable $build_locale_from_post,
    callable $normalize
): int {
    if (get_option($option_key)) {
        return rs_section_i18n_canonical_id($post_type);
    }

    $all = get_posts([
        'post_type'      => $post_type,
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ]);

    $en_id = 0;
    $pt_id = 0;

    foreach ($all as $id) {
        $id = (int) $id;
        $post = get_post($id);
        if (!$post) {
            continue;
        }
        if ((int) get_post_meta($id, 'EN', true) > 0) {
            if ($pt_id <= 0) {
                $pt_id = $id;
            }
            continue;
        }
        if ($post->post_name === 'pt') {
            if ($pt_id <= 0) {
                $pt_id = $id;
            }
            continue;
        }
        if ($en_id <= 0) {
            $en_id = $id;
        }
    }

    if ($en_id <= 0 && $pt_id > 0) {
        $en_id = $pt_id;
        $pt_id = 0;
    }

    if ($en_id <= 0) {
        $en_id = (int) wp_insert_post([
            'post_title'  => $title_en,
            'post_status' => 'publish',
            'post_type'   => $post_type,
            'post_name'   => 'main',
            'post_author' => 1,
        ], true);
        if ($en_id <= 0) {
            update_option($option_key, 1);
            return 0;
        }
    }

    if ($pt_id <= 0) {
        $linked = (int) get_post_meta($en_id, 'PT', true);
        if ($linked > 0 && get_post($linked)) {
            $pt_id = $linked;
        }
    }

    $raw = [
        'v'       => 1,
        'shared'  => [],
        'locales' => [
            'en' => $build_locale_from_post($en_id, 'en'),
            'pt' => $build_locale_from_post($pt_id > 0 ? $pt_id : $en_id, 'pt'),
        ],
    ];
    $normalized = $normalize($raw);
    rs_section_i18n_save($en_id, $meta_key, $normalized);

    wp_update_post([
        'ID'         => $en_id,
        'post_name'  => 'main',
        'post_title' => $title_en,
    ]);

    delete_post_meta($en_id, 'PT');
    delete_post_meta($en_id, 'EN');
    delete_post_meta($en_id, 'rs_locale');

    foreach ($all as $id) {
        $id = (int) $id;
        if ($id === $en_id) {
            continue;
        }
        wp_trash_post($id);
    }

    update_option($option_key, 1);

    return $en_id;
}

/**
 * Redireciona edição de gêmeo legado / slug en|pt para o post main.
 */
function rs_section_i18n_redirect_legacy_edit(): void {
    if (!isset($_GET['post'], $_GET['action']) || $_GET['action'] !== 'edit') {
        return;
    }

    $post_id = (int) $_GET['post'];
    $post = get_post($post_id);
    if (!$post || !rs_section_i18n_is_migrated_type($post->post_type)) {
        return;
    }

    $canonical = rs_section_i18n_canonical_id($post->post_type);
    if ($canonical <= 0 || $canonical === $post_id) {
        return;
    }

    wp_safe_redirect(get_edit_post_link($canonical, 'raw'));
    exit;
}

add_action('load-post.php', 'rs_section_i18n_redirect_legacy_edit', 5);

/**
 * REST: slug=en|pt → post canônico (name=main).
 *
 * @param array<string, mixed> $args
 * @return array<string, mixed>
 */
function rs_section_i18n_rest_query_canonical(array $args, WP_REST_Request $request): array {
    $slug = $request->get_param('slug');
    // Sem slug, ou slug legado en/pt → post canônico main.
    if ($slug === null || $slug === '' || (is_string($slug) && in_array($slug, ['en', 'pt', 'main'], true))) {
        $args['name'] = 'main';
        if (isset($args['post_name__in'])) {
            unset($args['post_name__in']);
        }
    }

    return $args;
}

foreach (rs_section_i18n_post_types() as $rs_section_type) {
    add_filter("rest_{$rs_section_type}_query", 'rs_section_i18n_rest_query_canonical', 10, 2);
}
