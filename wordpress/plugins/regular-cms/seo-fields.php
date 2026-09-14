<?php
/**
 * SEO editável (title + meta description) por CPT — EN/PT.
 *
 * Metabox compartilhado + REST `seo_data` (locale via ?translate=PT).
 */

if (defined('RS_SEO_FIELDS_LOADED')) {
    return;
}
define('RS_SEO_FIELDS_LOADED', true);

const RS_SEO_I18N_KEY = 'rs_seo_i18n';

/** @return list<string> */
function rs_seo_post_types(): array {
    return [
        'intro',
        'about',
        'capabilities',
        'education',
        'contact',
        'projects-page',
        'project',
    ];
}

function rs_seo_default_locale(): array {
    return [
        'title'       => '',
        'description' => '',
    ];
}

function rs_seo_i18n_default(): array {
    return [
        'v'       => 1,
        'locales' => [
            'en' => rs_seo_default_locale(),
            'pt' => rs_seo_default_locale(),
        ],
    ];
}

function rs_seo_i18n_normalize(array $raw): array {
    $data = rs_seo_i18n_default();
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $data['locales'][$locale] = [
            'title'       => sanitize_text_field((string) ($loc['title'] ?? '')),
            'description' => sanitize_textarea_field((string) ($loc['description'] ?? '')),
        ];
    }
    return $data;
}

function rs_seo_resolve_post_id(int $post_id): int {
    $type = get_post_type($post_id);
    if ($type === 'project' && function_exists('rs_project_resolve_canonical_id')) {
        return rs_project_resolve_canonical_id($post_id);
    }
    if (function_exists('rs_section_i18n_resolve_id')) {
        return rs_section_i18n_resolve_id($post_id);
    }
    return $post_id;
}

function rs_seo_i18n_get(int $post_id): array {
    $post_id = rs_seo_resolve_post_id($post_id);
    if ($post_id <= 0) {
        return rs_seo_i18n_default();
    }

    $raw = get_post_meta($post_id, RS_SEO_I18N_KEY, true);
    if (is_array($raw)) {
        return rs_seo_i18n_normalize($raw);
    }

    return rs_seo_i18n_default();
}

/**
 * @return array{title: string, description: string}
 */
function rs_seo_meta_to_payload(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_seo_i18n_get($post_id);
    $loc = $data['locales'][$locale] ?? rs_seo_default_locale();

    return [
        'title'       => (string) ($loc['title'] ?? ''),
        'description' => (string) ($loc['description'] ?? ''),
    ];
}

function rs_seo_locale_from_request($request = null): string {
    if (function_exists('rs_section_i18n_locale_from_request') && $request instanceof WP_REST_Request) {
        return rs_section_i18n_locale_from_request($request);
    }
    if ($request instanceof WP_REST_Request) {
        $raw = $request->get_param('translate');
        if (is_string($raw) && strtoupper($raw) === 'PT') {
            return 'pt';
        }
    }
    return 'en';
}

add_action('rest_api_init', function () {
    foreach (rs_seo_post_types() as $type) {
        register_rest_field($type, 'seo_data', [
            'get_callback' => function (array $post, $attr, $request = null) {
                $locale = rs_seo_locale_from_request($request);
                return rs_seo_meta_to_payload((int) $post['id'], $locale);
            },
            'schema' => [
                'description' => 'SEO title e meta description (locale da request)',
                'type'        => 'object',
                'context'     => ['view', 'edit'],
            ],
        ]);
    }
});

add_action('add_meta_boxes', function () {
    foreach (rs_seo_post_types() as $type) {
        add_meta_box(
            'rs_seo_fields',
            'SEO (title & meta description)',
            'rs_seo_render_meta_box',
            $type,
            'normal',
            'default'
        );
    }
}, 20);

/**
 * Dicas de tamanho (Google ~50–60 / ~150–160).
 */
function rs_seo_char_hint(string $field): string {
    if ($field === 'title') {
        return 'Recomendado: até ~60 caracteres.';
    }
    return 'Recomendado: até ~160 caracteres.';
}

function rs_seo_render_locale_fields(string $locale, array $loc): void {
    $prefix = 'rs_seo_i18n_input[' . $locale . ']';
    $title = (string) ($loc['title'] ?? '');
    $desc = (string) ($loc['description'] ?? '');

    echo '<p style="margin:0 0 12px;">';
    echo '<label for="rs_seo_title_' . esc_attr($locale) . '" style="display:block;font-weight:600;margin-bottom:4px;">Title tag</label>';
    echo '<input type="text" class="large-text" id="rs_seo_title_' . esc_attr($locale) . '" name="' . esc_attr($prefix) . '[title]" value="' . esc_attr($title) . '" maxlength="120" autocomplete="off" />';
    echo '<span style="display:block;margin-top:4px;color:#646970;font-size:12px;">' . esc_html(rs_seo_char_hint('title')) . ' Aparece na aba do browser e no Google.</span>';
    echo '</p>';

    echo '<p style="margin:0;">';
    echo '<label for="rs_seo_desc_' . esc_attr($locale) . '" style="display:block;font-weight:600;margin-bottom:4px;">Meta description</label>';
    echo '<textarea class="large-text" rows="3" id="rs_seo_desc_' . esc_attr($locale) . '" name="' . esc_attr($prefix) . '[description]" maxlength="320">' . esc_textarea($desc) . '</textarea>';
    echo '<span style="display:block;margin-top:4px;color:#646970;font-size:12px;">' . esc_html(rs_seo_char_hint('description')) . ' Texto do snippet nos resultados de busca.</span>';
    echo '</p>';
}

function rs_seo_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_seo_save', 'rs_seo_nonce');
    $id = rs_seo_resolve_post_id((int) $post->ID);
    $data = rs_seo_i18n_get($id);

    echo '<p style="margin-top:0;color:#646970;">Campos usados pelo site headless (Next.js). Se vazios, o front usa um fallback genérico.</p>';
    if (function_exists('rs_plugin_version_markup')) {
        echo '<p style="margin:0 0 12px;color:#646970;font-size:12px;">' . rs_plugin_version_markup() . '</p>';
    }

    echo '<div class="rs-metabox-tabs" data-rs-tabs>';
    echo '<div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button>';
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_seo_render_locale_fields('en', $data['locales']['en']);
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_seo_render_locale_fields('pt', $data['locales']['pt']);
    echo '</div>';
    echo '</div>';
}

/**
 * Save único para todos os CPTs com metabox SEO.
 */
function rs_seo_handle_save(int $post_id): void {
    if (!isset($_POST['rs_seo_nonce']) || !wp_verify_nonce($_POST['rs_seo_nonce'], 'rs_seo_save')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $type = get_post_type($post_id);
    if (!in_array($type, rs_seo_post_types(), true)) {
        return;
    }

    $post_id = rs_seo_resolve_post_id($post_id);
    $data = rs_seo_i18n_get($post_id);
    $raw = isset($_POST['rs_seo_i18n_input']) && is_array($_POST['rs_seo_i18n_input'])
        ? wp_unslash($_POST['rs_seo_i18n_input'])
        : [];

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        $data['locales'][$locale] = [
            'title'       => sanitize_text_field((string) ($loc['title'] ?? '')),
            'description' => sanitize_textarea_field((string) ($loc['description'] ?? '')),
        ];
    }

    $data = rs_seo_i18n_normalize($data);
    update_post_meta($post_id, RS_SEO_I18N_KEY, $data);
}

foreach (rs_seo_post_types() as $rs_seo_type) {
    add_action('save_post_' . $rs_seo_type, 'rs_seo_handle_save', 20);
}
