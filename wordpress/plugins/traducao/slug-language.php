<?php
/**
 * Slugs automáticos no padrão {tipo}/{locale}.
 * O post_name vira "en" ou "pt"; o permalink fica ex.: /footer/en/, /contact/pt/.
 */

if (defined('RS_SLUG_LANGUAGE_LOADED')) {
    return;
}
define('RS_SLUG_LANGUAGE_LOADED', true);

const RS_LOCALE_SLUGS = ['en', 'pt'];

const RS_LOCALE_CPT_TYPES = [
    'intro',
    'footer',
    'capabilities',
    'about',
    'education',
    'contact',
    'projects-page',
    'site-ui',
];

const RS_LOCALE_PAGE_BASES = [
    'education',
];

function rs_normalize_locale(string $value): ?string {
    $value = strtolower(trim($value));

    return in_array($value, RS_LOCALE_SLUGS, true) ? $value : null;
}

function rs_locale_from_title(string $title): ?string {
    if (preg_match('/\bPT\b/i', $title)) {
        return 'pt';
    }
    if (preg_match('/\bEN\b/i', $title)) {
        return 'en';
    }

    return null;
}

function rs_detect_post_locale(int $post_id): string {
    $stored = rs_normalize_locale((string) get_post_meta($post_id, 'rs_locale', true));
    if ($stored) {
        return $stored;
    }

    $post = get_post($post_id);
    if (!$post) {
        return 'en';
    }

    $from_title = rs_locale_from_title($post->post_title);
    if ($from_title) {
        return $from_title;
    }

    if ((int) get_post_meta($post_id, 'PT', true) > 0) {
        return 'en';
    }

    if ((int) get_post_meta($post_id, 'EN', true) > 0) {
        return 'pt';
    }

    return 'en';
}

function rs_detect_page_base(int $post_id): ?string {
    $stored = (string) get_post_meta($post_id, 'rs_page_base', true);
    if (in_array($stored, RS_LOCALE_PAGE_BASES, true)) {
        return $stored;
    }

    $post = get_post($post_id);
    if (!$post) {
        return null;
    }

    $title = strtolower($post->post_title);
    foreach (RS_LOCALE_PAGE_BASES as $base) {
        if (str_contains($title, $base)) {
            return $base;
        }
    }

    $slug = $post->post_name;
    foreach (RS_LOCALE_PAGE_BASES as $base) {
        if ($slug === $base || str_starts_with($slug, $base . '-')) {
            return $base;
        }
    }

    return null;
}

function rs_ensure_page_parent(string $base_slug): int {
    $existing = get_page_by_path($base_slug);
    if ($existing) {
        return (int) $existing->ID;
    }

    $id = wp_insert_post([
        'post_title'  => ucfirst($base_slug),
        'post_name'   => $base_slug,
        'post_status' => 'publish',
        'post_type'   => 'page',
    ], true);

    return is_wp_error($id) ? 0 : (int) $id;
}

function rs_apply_locale_slug(int $post_id): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    $post = get_post($post_id);
    if (!$post || $post->post_status === 'auto-draft') {
        return;
    }

    $locale = rs_detect_post_locale($post_id);
    update_post_meta($post_id, 'rs_locale', $locale);

    if (in_array($post->post_type, RS_LOCALE_CPT_TYPES, true)) {
        if ($post->post_name === $locale) {
            return;
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ['post_name' => $locale],
            ['ID' => $post_id],
            ['%s'],
            ['%d']
        );
        clean_post_cache($post_id);

        return;
    }

    if ($post->post_type !== 'page') {
        return;
    }

    $base = rs_detect_page_base($post_id);
    if (!$base) {
        return;
    }

    update_post_meta($post_id, 'rs_page_base', $base);
    $parent_id = rs_ensure_page_parent($base);
    if ($parent_id <= 0) {
        return;
    }

    if ((int) $post->post_parent === $parent_id && $post->post_name === $locale) {
        return;
    }

    wp_update_post([
        'ID'          => $post_id,
        'post_parent' => $parent_id,
        'post_name'   => $locale,
    ]);
}

function rs_migrate_locale_slugs_once(): void {
    if (get_option('rs_locale_slugs_migrated_v2')) {
        return;
    }

    foreach (RS_LOCALE_CPT_TYPES as $type) {
        $posts = get_posts([
            'post_type'      => $type,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        foreach ($posts as $post_id) {
            rs_apply_locale_slug((int) $post_id);
        }
    }

    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($pages as $post_id) {
        if (rs_detect_page_base((int) $post_id)) {
            rs_apply_locale_slug((int) $post_id);
        }
    }

    update_option('rs_locale_slugs_migrated_v2', 1);
}

foreach (RS_LOCALE_CPT_TYPES as $type) {
    add_action("save_post_{$type}", function (int $post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        rs_apply_locale_slug($post_id);
    }, 99);
}

add_action('save_post_page', function (int $post_id) {
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    rs_apply_locale_slug($post_id);
}, 99);

add_action('init', 'rs_migrate_locale_slugs_once', 20);

add_action('add_meta_boxes', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post') {
        return;
    }

    $post_type = $screen->post_type;
    $is_locale_cpt = in_array($post_type, RS_LOCALE_CPT_TYPES, true);
    $is_locale_page = $post_type === 'page';

    if (!$is_locale_cpt && !$is_locale_page) {
        return;
    }

    add_meta_box(
        'rs_locale_slug_info',
        'Slug / idioma (automático)',
        function (WP_Post $post) use ($is_locale_cpt) {
            $locale = rs_detect_post_locale($post->ID);
            $type = $post->post_type;

            if ($is_locale_cpt) {
                $permalink = home_url("/{$type}/{$locale}/");
            } else {
                $base = rs_detect_page_base($post->ID) ?: '{pagina}';
                $permalink = home_url("/{$base}/{$locale}/");
            }

            echo '<p style="margin:0 0 8px;color:#646970;">';
            echo 'Ao salvar, o slug é definido automaticamente como <code>' . esc_html($locale) . '</code>.';
            echo '</p>';
            echo '<p style="margin:0;font-family:monospace;font-size:12px;">';
            echo esc_html($permalink);
            echo '</p>';
        },
        $post_type,
        'side',
        'high'
    );
}, 20);
