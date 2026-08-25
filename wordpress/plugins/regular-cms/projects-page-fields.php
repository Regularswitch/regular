<?php
/**
 * CPT projects-page — post único bilíngue (EN + PT).
 */

if (defined('RS_PROJECTS_PAGE_FIELDS_LOADED')) {
    return;
}
define('RS_PROJECTS_PAGE_FIELDS_LOADED', true);

const RS_PROJECTS_PAGE_I18N_KEY = 'rs_projects_page_i18n';
const RS_PROJECTS_PAGE_TITLE_KEY = 'rs_projects_page_title';
const RS_PROJECTS_PAGE_HEADLINE_KEY = 'rs_projects_page_headline';
const RS_PROJECTS_PAGE_EMPTY_KEY = 'rs_projects_page_empty_message';

/**
 * @return array{title: string, headline: string, emptyMessage: string}
 */
function rs_projects_page_default_locale(): array {
    return [
        'title'        => '',
        'headline'     => '',
        'emptyMessage' => '',
    ];
}

/**
 * @return array{v: int, shared: array, locales: array{en: array, pt: array}}
 */
function rs_projects_page_i18n_default(): array {
    return [
        'v'       => 1,
        'shared'  => [],
        'locales' => [
            'en' => rs_projects_page_default_locale(),
            'pt' => rs_projects_page_default_locale(),
        ],
    ];
}

/**
 * @param array<string, mixed> $raw
 * @return array{v: int, shared: array, locales: array{en: array, pt: array}}
 */
function rs_projects_page_i18n_normalize(array $raw): array {
    $base = rs_projects_page_i18n_default();
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $base['locales'][$locale]['title'] = trim((string) ($loc['title'] ?? ''));
        $base['locales'][$locale]['headline'] = trim((string) ($loc['headline'] ?? ''));
        $base['locales'][$locale]['emptyMessage'] = trim((string) ($loc['emptyMessage'] ?? $loc['empty_message'] ?? ''));
    }

    return $base;
}

/**
 * @return array{title: string, headline: string, emptyMessage: string}
 */
function rs_projects_page_locale_from_legacy_post(int $post_id): array {
    if ($post_id <= 0) {
        return rs_projects_page_default_locale();
    }

    return [
        'title'        => trim((string) get_post_meta($post_id, RS_PROJECTS_PAGE_TITLE_KEY, true)),
        'headline'     => trim((string) get_post_meta($post_id, RS_PROJECTS_PAGE_HEADLINE_KEY, true)),
        'emptyMessage' => trim((string) get_post_meta($post_id, RS_PROJECTS_PAGE_EMPTY_KEY, true)),
    ];
}

/**
 * @return array{v: int, shared: array, locales: array{en: array, pt: array}}
 */
function rs_projects_page_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;

    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_PROJECTS_PAGE_I18N_KEY)
        : null;

    if (is_array($raw)) {
        return rs_projects_page_i18n_normalize($raw);
    }

    // Fallback legado flat.
    $data = rs_projects_page_i18n_default();
    $data['locales']['en'] = rs_projects_page_locale_from_legacy_post($post_id);
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_projects_page_locale_from_legacy_post($pt_id);
    }

    return rs_projects_page_i18n_normalize($data);
}

function rs_projects_page_meta_to_payload(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_projects_page_i18n_get($post_id);
    $loc = $data['locales'][$locale] ?? rs_projects_page_default_locale();

    // Fallback EN → PT se vazio.
    if ($locale === 'pt') {
        $en = $data['locales']['en'] ?? rs_projects_page_default_locale();
        foreach (['title', 'headline', 'emptyMessage'] as $key) {
            if (trim((string) ($loc[$key] ?? '')) === '') {
                $loc[$key] = $en[$key] ?? '';
            }
        }
    }

    return [
        'title'        => trim((string) ($loc['title'] ?? '')),
        'headline'     => trim((string) ($loc['headline'] ?? '')),
        'emptyMessage' => trim((string) ($loc['emptyMessage'] ?? '')),
    ];
}

function rs_projects_page_get_post_id_by_locale(string $locale = 'en'): int {
    if (function_exists('rs_section_i18n_canonical_id')) {
        return rs_section_i18n_canonical_id('projects-page');
    }

    return 0;
}

function rs_projects_page_sync_legacy_meta(int $post_id, array $data): void {
    $en = $data['locales']['en'] ?? rs_projects_page_default_locale();
    update_post_meta($post_id, RS_PROJECTS_PAGE_TITLE_KEY, (string) ($en['title'] ?? ''));
    update_post_meta($post_id, RS_PROJECTS_PAGE_HEADLINE_KEY, (string) ($en['headline'] ?? ''));
    update_post_meta($post_id, RS_PROJECTS_PAGE_EMPTY_KEY, (string) ($en['emptyMessage'] ?? ''));
}

function rs_projects_page_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }

    rs_section_i18n_migrate_twins(
        'projects-page',
        RS_PROJECTS_PAGE_I18N_KEY,
        'rs_projects_page_i18n_migrated_v1',
        'Projects page',
        static function (int $post_id, string $locale): array {
            return rs_projects_page_locale_from_legacy_post($post_id);
        },
        'rs_projects_page_i18n_normalize'
    );
}

add_action('init', function () {
    register_post_meta('projects-page', RS_PROJECTS_PAGE_I18N_KEY, [
        'single'        => true,
        'type'          => 'array',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
}, 20);

add_action('init', 'rs_projects_page_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('projects-page', 'projects_page_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request')
                ? rs_section_i18n_locale_from_request($request)
                : 'en';

            return rs_projects_page_meta_to_payload((int) $post['id'], $locale);
        },
        'schema' => [
            'description' => 'Dados estruturados da página de projetos',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_projects-page', function () {
    add_meta_box(
        'rs_projects_page_fields',
        'Conteúdo da página de projetos',
        'rs_projects_page_render_meta_box',
        'projects-page',
        'normal',
        'high'
    );
    remove_meta_box('postcustom', 'projects-page', 'normal');
}, 10);

function rs_projects_page_render_locale_fields(string $locale, array $loc): void {
    $suffix = '_' . $locale;
    $title_name = 'rs_pp_i18n[' . $locale . '][title]';
    $headline_name = 'rs_pp_i18n[' . $locale . '][headline]';
    $empty_name = 'rs_pp_i18n[' . $locale . '][emptyMessage]';

    echo '<p class="rs-admin-text-field" style="margin:0 0 12px;">';
    echo '<label style="display:block;font-weight:500;margin-bottom:4px;">Título da seção</label>';
    rs_render_rich_text_field('rs_pp_title' . $suffix, $title_name, (string) ($loc['title'] ?? ''), 'compact');
    echo '</p>';

    echo '<fieldset class="rs-metabox-fieldset" style="margin:0 0 16px;">';
    echo '<legend><strong>Headline</strong></legend>';
    rs_render_rich_text_field('rs_pp_headline' . $suffix, $headline_name, (string) ($loc['headline'] ?? ''), 'compact');
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Use o botão <strong>B</strong> para destacar palavras.</p>';
    echo '</fieldset>';

    echo '<p class="rs-admin-text-field" style="margin:0;">';
    echo '<label style="display:block;font-weight:500;margin-bottom:4px;">Mensagem quando não há projetos</label>';
    rs_render_rich_text_field('rs_pp_empty' . $suffix, $empty_name, (string) ($loc['emptyMessage'] ?? ''), 'compact');
    echo '</p>';
}

function rs_projects_page_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_projects_page_save', 'rs_projects_page_nonce');

    $canonical = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id((int) $post->ID)
        : (int) $post->ID;
    $i18n = rs_projects_page_i18n_get($canonical);
    $en = $i18n['locales']['en'];
    $pt = $i18n['locales']['pt'];

    echo '<p style="margin-top:0;color:#646970;">Um único post. Edite <strong>English</strong> e <strong>Português</strong> nas abas. ' . (function_exists('rs_plugin_version_markup') ? rs_plugin_version_markup() : '') . '</p>';

    echo '<div class="rs-metabox-tabs" data-rs-tabs>';
    echo '<div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button>';
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_projects_page_render_locale_fields('en', $en);
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_projects_page_render_locale_fields('pt', $pt);
    echo '</div>';
    echo '</div>';
}

add_action('save_post_projects-page', function (int $post_id) {
    if (!isset($_POST['rs_projects_page_nonce']) || !wp_verify_nonce($_POST['rs_projects_page_nonce'], 'rs_projects_page_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id)) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;

    $data = rs_projects_page_i18n_get($post_id);
    $raw = isset($_POST['rs_pp_i18n']) && is_array($_POST['rs_pp_i18n'])
        ? wp_unslash($_POST['rs_pp_i18n'])
        : [];

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        $data['locales'][$locale]['title'] = wp_kses_post((string) ($loc['title'] ?? ''));
        $data['locales'][$locale]['headline'] = wp_kses_post((string) ($loc['headline'] ?? ''));
        $data['locales'][$locale]['emptyMessage'] = wp_kses_post((string) ($loc['emptyMessage'] ?? ''));
    }

    $normalized = rs_projects_page_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_PROJECTS_PAGE_I18N_KEY, $normalized);
    }
    rs_projects_page_sync_legacy_meta($post_id, $normalized);
}, 10);

function rs_copy_projects_page_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}
