<?php
/**
 * CPT intro — post único bilíngue (EN + PT).
 *
 * content corresponde ao headline legado (post_content) e excerpt ao body
 * legado (post_excerpt). O editor WP nativo foi removido — só o metabox EN/PT.
 */

if (defined('RS_INTRO_FIELDS_LOADED')) {
    return;
}
define('RS_INTRO_FIELDS_LOADED', true);

const RS_INTRO_I18N_KEY = 'rs_intro_i18n';

function rs_intro_default_locale(): array {
    return ['content' => '', 'excerpt' => ''];
}

function rs_intro_i18n_default(): array {
    return [
        'v' => 1,
        'shared' => [],
        'locales' => ['en' => rs_intro_default_locale(), 'pt' => rs_intro_default_locale()],
    ];
}

function rs_intro_i18n_normalize(array $raw): array {
    $data = rs_intro_i18n_default();
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $data['locales'][$locale] = [
            'content' => wp_kses_post((string) ($loc['content'] ?? $loc['headline'] ?? '')),
            'excerpt' => wp_kses_post((string) ($loc['excerpt'] ?? $loc['body'] ?? '')),
        ];
    }
    return $data;
}

function rs_intro_locale_from_legacy_post(int $post_id): array {
    $post = $post_id > 0 ? get_post($post_id) : null;
    return $post
        ? ['content' => (string) $post->post_content, 'excerpt' => (string) $post->post_excerpt]
        : rs_intro_default_locale();
}

function rs_intro_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_INTRO_I18N_KEY)
        : get_post_meta($post_id, RS_INTRO_I18N_KEY, true);
    if (is_array($raw)) {
        return rs_intro_i18n_normalize($raw);
    }
    $data = rs_intro_i18n_default();
    $data['locales']['en'] = rs_intro_locale_from_legacy_post($post_id);
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_intro_locale_from_legacy_post($pt_id);
    }
    return rs_intro_i18n_normalize($data);
}

/**
 * Compatibilidade com chamadas legadas.
 *
 * @return array{headline: string, body: string}
 */
function rs_intro_get_fields(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_intro_i18n_get($post_id);
    $loc = $data['locales'][$locale] ?? rs_intro_default_locale();
    return ['headline' => (string) ($loc['content'] ?? ''), 'body' => (string) ($loc['excerpt'] ?? '')];
}

function rs_intro_sync_legacy_post(int $post_id, array $data): void {
    global $wpdb;
    $en = $data['locales']['en'] ?? rs_intro_default_locale();
    $content = (string) ($en['content'] ?? '');
    $excerpt = (string) ($en['excerpt'] ?? '');
    $post = get_post($post_id);
    if (!$post || ($post->post_content === $content && $post->post_excerpt === $excerpt)) {
        return;
    }
    $wpdb->update(
        $wpdb->posts,
        ['post_content' => $content, 'post_excerpt' => $excerpt],
        ['ID' => $post_id],
        ['%s', '%s'],
        ['%d']
    );
    clean_post_cache($post_id);
}

function rs_intro_save_fields(int $post_id, string $headline, string $body): void {
    $data = rs_intro_i18n_get($post_id);
    $data['locales']['en'] = ['content' => $headline, 'excerpt' => $body];
    $data = rs_intro_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_INTRO_I18N_KEY, $data);
    } else {
        update_post_meta($post_id, RS_INTRO_I18N_KEY, $data);
    }
    rs_intro_sync_legacy_post($post_id, $data);
}

function rs_intro_meta_to_payload(int $post_id, string $locale = 'en'): array {
    return rs_intro_get_fields($post_id, $locale);
}

function rs_intro_resolve_post_id(int $post_id): int {
    return function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
}

function rs_intro_get_post_id_by_locale(string $locale = 'en'): int {
    return function_exists('rs_section_i18n_canonical_id') ? rs_section_i18n_canonical_id('intro') : 0;
}

function rs_intro_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }
    $id = rs_section_i18n_migrate_twins(
        'intro', RS_INTRO_I18N_KEY, 'rs_intro_i18n_migrated_v1', 'Intro',
        static fn(int $post_id, string $locale): array => rs_intro_locale_from_legacy_post($post_id),
        'rs_intro_i18n_normalize'
    );
    if ($id > 0) {
        rs_intro_sync_legacy_post($id, rs_intro_i18n_get($id));
    }
}

add_action('init', function () {
    register_post_meta('intro', RS_INTRO_I18N_KEY, [
        'single' => true,
        'type' => 'array',
        'show_in_rest' => false,
        'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
    ]);
}, 20);
add_action('init', 'rs_intro_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('intro', 'intro_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request')
                ? rs_section_i18n_locale_from_request($request)
                : 'en';
            return rs_intro_meta_to_payload((int) $post['id'], $locale);
        },
        'schema' => [
            'description' => 'Conteúdo estruturado da intro (headline + body)',
            'type' => 'object',
            'context' => ['view', 'edit'],
        ],
    ]);
});

add_action('init', function () {
    remove_post_type_support('intro', 'editor');
    remove_post_type_support('intro', 'excerpt');
}, 100);

add_action('add_meta_boxes_intro', function () {
    add_meta_box('rs_intro_fields', 'Conteúdo da Intro (home)', 'rs_intro_render_meta_box', 'intro', 'normal', 'high');
    remove_meta_box('postexcerpt', 'intro', 'normal');
    remove_meta_box('postexcerpt', 'intro', 'side');
}, 10);

function rs_intro_render_locale_fields(string $locale, array $loc): void {
    $prefix = 'rs_intro_i18n_input[' . $locale . ']';
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Título grande (headline)</strong></legend>';
    rs_render_rich_text_field('rs_intro_content_' . $locale, $prefix . '[content]', (string) ($loc['content'] ?? ''), 'compact');
    echo '</fieldset><fieldset class="rs-metabox-fieldset"><legend><strong>Parágrafo abaixo (body)</strong></legend>';
    rs_render_rich_text_field('rs_intro_excerpt_' . $locale, $prefix . '[excerpt]', (string) ($loc['excerpt'] ?? ''), 'paragraph');
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Texto menor abaixo do título.</p></fieldset>';
}

function rs_intro_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_intro_save', 'rs_intro_nonce');
    $id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id((int) $post->ID) : (int) $post->ID;
    $data = rs_intro_i18n_get($id);
    echo '<p style="margin-top:0;color:#646970;">Um único post. Edite English e Português nas abas.</p>';
    echo '<div class="rs-metabox-tabs" data-rs-tabs><div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button></div>';
    echo '<div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_intro_render_locale_fields('en', $data['locales']['en']);
    echo '</div><div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_intro_render_locale_fields('pt', $data['locales']['pt']);
    echo '</div></div>';
}

add_action('save_post_intro', function (int $post_id) {
    if (!isset($_POST['rs_intro_nonce']) || !wp_verify_nonce($_POST['rs_intro_nonce'], 'rs_intro_save')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)
        || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $data = rs_intro_i18n_get($post_id);
    $raw = isset($_POST['rs_intro_i18n_input']) && is_array($_POST['rs_intro_i18n_input'])
        ? wp_unslash($_POST['rs_intro_i18n_input'])
        : [];
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        $data['locales'][$locale] = [
            'content' => wp_kses_post((string) ($loc['content'] ?? '')),
            'excerpt' => wp_kses_post((string) ($loc['excerpt'] ?? '')),
        ];
    }
    $data = rs_intro_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_INTRO_I18N_KEY, $data);
    } else {
        update_post_meta($post_id, RS_INTRO_I18N_KEY, $data);
    }
    rs_intro_sync_legacy_post($post_id, $data);
}, 10);

function rs_copy_intro_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}
