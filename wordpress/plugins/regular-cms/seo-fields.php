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
    $title_id = 'rs_seo_title_' . $locale;
    $desc_id = 'rs_seo_desc_' . $locale;
    $serp_title = $title !== '' ? $title : 'Título da página';
    $serp_desc = $desc !== '' ? $desc : 'A meta description aparece aqui nos resultados de busca.';

    rs_ds_fieldset_open('Title & description');
    echo '<div class="rs-ds-field" style="margin-bottom:var(--rs-space-3);">';
    echo '<label class="rs-ds-label" for="' . esc_attr($title_id) . '">Title tag</label>';
    echo '<input type="text" class="rs-ds-input rs-seo-title-input" id="' . esc_attr($title_id) . '" name="' . esc_attr($prefix) . '[title]" value="' . esc_attr($title) . '" maxlength="120" autocomplete="off" data-rs-serp-title="' . esc_attr($locale) . '" />';
    rs_ds_help(rs_seo_char_hint('title') . ' Aparece na aba do browser e no Google.');
    echo '</div>';

    echo '<div class="rs-ds-field">';
    echo '<label class="rs-ds-label" for="' . esc_attr($desc_id) . '">Meta description</label>';
    echo '<textarea class="rs-ds-textarea rs-seo-desc-input" rows="3" id="' . esc_attr($desc_id) . '" name="' . esc_attr($prefix) . '[description]" maxlength="320" data-rs-serp-desc="' . esc_attr($locale) . '">' . esc_textarea($desc) . '</textarea>';
    rs_ds_help(rs_seo_char_hint('description') . ' Texto do snippet nos resultados de busca.');
    echo '</div>';
    rs_ds_fieldset_close();

    echo '<div class="rs-ds-serp" data-rs-serp="' . esc_attr($locale) . '">';
    echo '<p class="rs-ds-label" style="margin-bottom:var(--rs-space-2);">Prévia no Google</p>';
    echo '<div class="rs-ds-serp__preview">';
    echo '<p class="rs-ds-serp__url">regularswitch.com › …</p>';
    echo '<p class="rs-ds-serp__title" data-rs-serp-out-title>' . esc_html($serp_title) . '</p>';
    echo '<p class="rs-ds-serp__desc" data-rs-serp-out-desc>' . esc_html($serp_desc) . '</p>';
    echo '</div>';
    echo '</div>';
}

function rs_seo_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_seo_save', 'rs_seo_nonce');
    $id = rs_seo_resolve_post_id((int) $post->ID);
    $data = rs_seo_i18n_get($id);

    echo '<div class="rs-ds-editor rs-seo-editor">';
    rs_ds_alert('Campos usados pelo site headless (Next.js). Se vazios, o front usa um fallback genérico.', 'info');

    rs_ds_locale_tabs_open(['en' => 'English', 'pt' => 'Português'], 'en');

    rs_ds_locale_panel_open('en', true);
    rs_seo_render_locale_fields('en', $data['locales']['en']);
    rs_ds_locale_panel_close();

    rs_ds_locale_panel_open('pt', false);
    rs_seo_render_locale_fields('pt', $data['locales']['pt']);
    rs_ds_locale_panel_close();

    rs_ds_locale_tabs_close();
    echo '</div>';

    static $serp_js = false;
    if ($serp_js) {
        return;
    }
    $serp_js = true;
    echo '<script>(function(){function sync(el){var loc=el.getAttribute("data-rs-serp-title")||el.getAttribute("data-rs-serp-desc");if(!loc)return;var box=document.querySelector(\'[data-rs-serp="\'+loc+\'"]\');if(!box)return;var t=document.getElementById("rs_seo_title_"+loc);var d=document.getElementById("rs_seo_desc_"+loc);var ot=box.querySelector("[data-rs-serp-out-title]");var od=box.querySelector("[data-rs-serp-out-desc]");if(ot&&t)ot.textContent=t.value.trim()||"Título da página";if(od&&d)od.textContent=d.value.trim()||"A meta description aparece aqui nos resultados de busca.";}document.addEventListener("input",function(e){var t=e.target;if(t&&(t.matches(".rs-seo-title-input")||t.matches(".rs-seo-desc-input")))sync(t);});})();</script>';
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
