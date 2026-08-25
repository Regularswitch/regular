<?php
/**
 * CPT footer — post único bilíngue (EN + PT).
 * Links sociais são shared (mesmo URL nos dois idiomas).
 */

if (defined('RS_FOOTER_FIELDS_LOADED')) {
    return;
}
define('RS_FOOTER_FIELDS_LOADED', true);

const RS_FOOTER_I18N_KEY = 'rs_footer_i18n';
const RS_FOOTER_LOCALE_META_KEYS = [
    'rs_footer_brand_mark' => 'Marca grande (ex: REGULARSWITCH)',
    'rs_footer_link_1_title' => 'Coluna 1 — título',
    'rs_footer_link_1_subtitle' => 'Coluna 1 — subtítulo',
    'rs_footer_link_1_href' => 'Coluna 1 — link',
    'rs_footer_link_2_title' => 'Coluna 2 — título',
    'rs_footer_link_2_subtitle' => 'Coluna 2 — subtítulo',
    'rs_footer_link_2_href' => 'Coluna 2 — link',
    'rs_footer_link_3_title' => 'Coluna 3 — título',
    'rs_footer_link_3_subtitle' => 'Coluna 3 — subtítulo',
    'rs_footer_link_3_href' => 'Coluna 3 — link',
    'rs_footer_legal_brand' => 'Legal — copyright (texto, sem link)',
    'rs_footer_legal_privacy' => 'Legal — rótulo Privacidade',
    'rs_footer_legal_cookies' => 'Legal — rótulo Cookies',
];
const RS_FOOTER_SOCIAL_META_KEYS = [
    'rs_footer_social_instagram_href' => 'Instagram — link',
    'rs_footer_social_linkedin_href' => 'LinkedIn — link',
    'rs_footer_social_youtube_href' => 'YouTube — link (opcional)',
    'rs_footer_social_tiktok_href' => 'TikTok — link (opcional)',
    'rs_footer_social_x_href' => 'X / Twitter — link (opcional)',
    'rs_footer_social_behance_href' => 'Behance — link (opcional)',
];
const RS_FOOTER_SOCIAL_NETWORKS = [
    'instagram' => 'Instagram',
    'linkedin' => 'LinkedIn',
    'youtube' => 'YouTube',
    'tiktok' => 'TikTok',
    'x' => 'X',
    'behance' => 'Behance',
];

/** @return list<string> */
function rs_footer_locale_keys(): array {
    return array_keys(RS_FOOTER_LOCALE_META_KEYS);
}

/** @return list<string> */
function rs_footer_social_keys(): array {
    return array_merge(array_keys(RS_FOOTER_SOCIAL_META_KEYS), ['rs_footer_social_href']);
}

/** @return list<string> */
function rs_footer_all_keys(): array {
    return array_merge(rs_footer_locale_keys(), rs_footer_social_keys());
}

function rs_footer_default_locale(): array {
    return array_fill_keys(rs_footer_locale_keys(), '');
}

function rs_footer_default_shared(): array {
    return array_fill_keys(rs_footer_social_keys(), '');
}

function rs_footer_i18n_default(): array {
    return [
        'v' => 2,
        'shared' => rs_footer_default_shared(),
        'locales' => [
            'en' => rs_footer_default_locale(),
            'pt' => rs_footer_default_locale(),
        ],
    ];
}

/**
 * Extrai links sociais de um blob de meta (shared ou locale legado).
 *
 * @param array<string, mixed> $source
 * @return array<string, string>
 */
function rs_footer_social_map_from_source(array $source): array {
    $out = rs_footer_default_shared();
    foreach (rs_footer_social_keys() as $key) {
        $out[$key] = trim((string) ($source[$key] ?? ''));
    }
    return $out;
}

/**
 * @param array<string, mixed> $raw
 * @return array{v: int, shared: array<string, string>, locales: array{en: array<string, string>, pt: array<string, string>}}
 */
function rs_footer_i18n_normalize(array $raw): array {
    $data = rs_footer_i18n_default();

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        foreach (rs_footer_locale_keys() as $key) {
            $data['locales'][$locale][$key] = trim((string) ($loc[$key] ?? ''));
        }
    }

    $shared_raw = is_array($raw['shared'] ?? null) ? $raw['shared'] : [];
    $shared = rs_footer_social_map_from_source($shared_raw);

    // Migração: se shared social estiver vazio, puxa de EN e depois PT (legado bilíngue).
    if (!rs_footer_social_links_from_meta($shared)) {
        $en = is_array($raw['locales']['en'] ?? null) ? $raw['locales']['en'] : [];
        $pt = is_array($raw['locales']['pt'] ?? null) ? $raw['locales']['pt'] : [];
        $from_en = rs_footer_social_map_from_source($en);
        $from_pt = rs_footer_social_map_from_source($pt);
        $shared = rs_footer_social_links_from_meta($from_en)
            ? $from_en
            : (rs_footer_social_links_from_meta($from_pt) ? $from_pt : $shared);
    }

    $data['shared'] = $shared;
    return $data;
}

function rs_footer_locale_from_legacy_post(int $post_id, string $locale = 'en'): array {
    $data = rs_footer_default_locale();
    if ($post_id > 0) {
        foreach (rs_footer_locale_keys() as $key) {
            $data[$key] = (string) get_post_meta($post_id, $key, true);
        }
    }
    return $data;
}

function rs_footer_shared_from_legacy_post(int $post_id): array {
    $data = rs_footer_default_shared();
    if ($post_id <= 0) {
        return $data;
    }
    foreach (rs_footer_social_keys() as $key) {
        $data[$key] = trim((string) get_post_meta($post_id, $key, true));
    }
    return $data;
}

function rs_footer_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_FOOTER_I18N_KEY)
        : get_post_meta($post_id, RS_FOOTER_I18N_KEY, true);
    if (is_array($raw)) {
        return rs_footer_i18n_normalize($raw);
    }

    $data = rs_footer_i18n_default();
    $data['locales']['en'] = rs_footer_locale_from_legacy_post($post_id, 'en');
    $data['shared'] = rs_footer_shared_from_legacy_post($post_id);
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_footer_locale_from_legacy_post($pt_id, 'pt');
        if (!rs_footer_social_links_from_meta($data['shared'])) {
            $data['shared'] = rs_footer_shared_from_legacy_post($pt_id);
        }
    }
    return rs_footer_i18n_normalize($data);
}

function rs_footer_get_meta(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_footer_i18n_get($post_id);
    return $data['locales'][$locale] ?? rs_footer_default_locale();
}

function rs_footer_get_shared(int $post_id): array {
    return rs_footer_i18n_get($post_id)['shared'] ?? rs_footer_default_shared();
}

/**
 * @param array<string, string> $meta
 * @return list<array{network: string, href: string, label: string}>
 */
function rs_footer_social_links_from_meta(array $meta): array {
    $links = [];
    $legacy = trim((string) ($meta['rs_footer_social_href'] ?? ''));
    foreach (RS_FOOTER_SOCIAL_NETWORKS as $network => $label) {
        $href = trim((string) ($meta["rs_footer_social_{$network}_href"] ?? ''));
        if ($href === '' && $network === 'instagram' && $legacy !== '') {
            $href = $legacy;
        }
        if ($href !== '') {
            $links[] = ['network' => $network, 'href' => $href, 'label' => $label];
        }
    }
    return $links;
}

/**
 * @param array<string, string> $meta
 * @param array<string, string> $shared
 */
function rs_footer_meta_to_payload(array $meta, string $locale = 'en', array $shared = []): array {
    $clean = static function (string $value): string {
        return function_exists('rs_clean_editor_html') ? rs_clean_editor_html($value) : trim($value);
    };

    return [
        'brandMark' => $clean((string) ($meta['rs_footer_brand_mark'] ?: 'REGULARSWITCH')),
        'links' => [
            ['title' => $clean((string) ($meta['rs_footer_link_1_title'] ?? '')), 'subtitle' => $clean((string) ($meta['rs_footer_link_1_subtitle'] ?? '')), 'href' => (string) ($meta['rs_footer_link_1_href'] ?? '')],
            ['title' => $clean((string) ($meta['rs_footer_link_2_title'] ?? '')), 'subtitle' => $clean((string) ($meta['rs_footer_link_2_subtitle'] ?? '')), 'href' => (string) ($meta['rs_footer_link_2_href'] ?? '')],
            ['title' => $clean((string) ($meta['rs_footer_link_3_title'] ?? '')), 'subtitle' => $clean((string) ($meta['rs_footer_link_3_subtitle'] ?? '')), 'href' => (string) ($meta['rs_footer_link_3_href'] ?? '')],
        ],
        'legal' => [
            'brand' => $clean((string) ($meta['rs_footer_legal_brand'] ?: ('© ' . gmdate('Y') . ' Regularswitch'))),
            'privacy' => $clean((string) ($meta['rs_footer_legal_privacy'] ?: ($locale === 'pt' ? 'Política de Privacidade' : 'Privacy Policy'))),
            'privacyHref' => '/privacy-policy',
            'cookies' => $clean((string) ($meta['rs_footer_legal_cookies'] ?: ($locale === 'pt' ? 'Política de Cookies' : 'Cookies Policy'))),
            'cookiesHref' => '/cookies-policy',
        ],
        'socialLinks' => rs_footer_social_links_from_meta($shared !== [] ? $shared : $meta),
    ];
}

function rs_footer_get_post_id_by_locale(string $locale = 'en'): int {
    return function_exists('rs_section_i18n_canonical_id') ? rs_section_i18n_canonical_id('footer') : 0;
}

function rs_footer_sync_legacy_meta(int $post_id, array $data): void {
    $en = is_array($data['locales']['en'] ?? null) ? $data['locales']['en'] : rs_footer_default_locale();
    foreach (rs_footer_locale_keys() as $key) {
        update_post_meta($post_id, $key, (string) ($en[$key] ?? ''));
    }
    $shared = is_array($data['shared'] ?? null) ? $data['shared'] : rs_footer_default_shared();
    foreach (rs_footer_social_keys() as $key) {
        update_post_meta($post_id, $key, (string) ($shared[$key] ?? ''));
    }
}

function rs_footer_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }
    $id = rs_section_i18n_migrate_twins(
        'footer',
        RS_FOOTER_I18N_KEY,
        'rs_footer_i18n_migrated_v1',
        'Footer',
        static function (int $post_id, string $locale): array {
            return rs_footer_locale_from_legacy_post($post_id, $locale);
        },
        'rs_footer_i18n_normalize'
    );
    if ($id > 0) {
        rs_footer_sync_legacy_meta($id, rs_footer_i18n_get($id));
    }
}

add_action('init', function () {
    register_post_meta('footer', RS_FOOTER_I18N_KEY, [
        'single' => true,
        'type' => 'array',
        'show_in_rest' => false,
        'auth_callback' => static function (): bool {
            return current_user_can('edit_posts');
        },
    ]);
    foreach (rs_footer_all_keys() as $key) {
        register_post_meta('footer', $key, [
            'single' => true,
            'type' => 'string',
            'show_in_rest' => false,
            'default' => '',
            'auth_callback' => static function (): bool {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);
add_action('init', 'rs_footer_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('footer', 'footer_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request')
                ? rs_section_i18n_locale_from_request($request)
                : 'en';
            $post_id = (int) $post['id'];
            $meta = rs_footer_get_meta($post_id, $locale);
            $shared = rs_footer_get_shared($post_id);
            return rs_footer_meta_to_payload($meta, $locale, $shared);
        },
        'schema' => [
            'description' => 'Dados estruturados do footer',
            'type' => 'object',
            'context' => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_footer', function () {
    add_meta_box('rs_footer_fields', 'Conteúdo do Footer', 'rs_footer_render_meta_box', 'footer', 'normal', 'high');
    remove_meta_box('postcustom', 'footer', 'normal');
}, 10);

function rs_footer_render_locale_fields(string $locale, array $meta): void {
    $groups = [
        'Marca' => ['rs_footer_brand_mark'],
        'Coluna 1' => ['rs_footer_link_1_title', 'rs_footer_link_1_subtitle', 'rs_footer_link_1_href'],
        'Coluna 2' => ['rs_footer_link_2_title', 'rs_footer_link_2_subtitle', 'rs_footer_link_2_href'],
        'Coluna 3' => ['rs_footer_link_3_title', 'rs_footer_link_3_subtitle', 'rs_footer_link_3_href'],
        'Linha legal' => ['rs_footer_legal_brand', 'rs_footer_legal_privacy', 'rs_footer_legal_cookies'],
    ];

    echo '<div data-rs-accordion class="rs-footer-accordion">';
    $index = 0;
    foreach ($groups as $label => $keys) {
        rs_metabox_accordion_item_open($label, $index === 0);
        echo '<div class="rs-metabox-fieldset" style="border:0;padding:4px 0 8px;margin:0;">';
        foreach ($keys as $key) {
            rs_render_admin_text_field(
                $key . '_' . $locale,
                'rs_footer_i18n_input[' . $locale . '][' . $key . ']',
                RS_FOOTER_LOCALE_META_KEYS[$key],
                (string) ($meta[$key] ?? '')
            );
        }
        echo '</div>';
        rs_metabox_accordion_item_close();
        $index++;
    }
    echo '</div>';
}

function rs_footer_render_shared_social(array $shared): void {
    echo '<div data-rs-accordion class="rs-footer-social-accordion" style="margin:0 0 16px;">';
    rs_metabox_accordion_item_open('Social (EN + PT)', true);
    echo '<p style="margin:0 0 10px;color:#646970;">Os mesmos links valem para English e Português. Campo vazio = ícone oculto no site.</p>';
    echo '<div class="rs-metabox-fieldset" style="border:0;padding:4px 0 8px;margin:0;">';
    foreach (RS_FOOTER_SOCIAL_META_KEYS as $key => $label) {
        rs_render_admin_text_field(
            $key . '_shared',
            'rs_footer_shared[' . $key . ']',
            $label,
            (string) ($shared[$key] ?? '')
        );
    }
    echo '</div>';
    rs_metabox_accordion_item_close();
    echo '</div>';
}

function rs_footer_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_footer_save', 'rs_footer_nonce');
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id((int) $post->ID) : (int) $post->ID;
    $data = rs_footer_i18n_get($post_id);
    echo '<p style="margin-top:0;color:#646970;">Um único post. Textos por idioma nas abas; redes sociais são compartilhadas. ' . (function_exists('rs_plugin_version_markup') ? rs_plugin_version_markup() : '') . '</p>';

    rs_footer_render_shared_social($data['shared']);

    echo '<div class="rs-metabox-tabs" data-rs-tabs><div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button></div>';
    echo '<div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_footer_render_locale_fields('en', $data['locales']['en']);
    echo '</div><div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_footer_render_locale_fields('pt', $data['locales']['pt']);
    echo '</div></div>';
}

add_action('save_post_footer', function (int $post_id) {
    if (!isset($_POST['rs_footer_nonce'])
        || !wp_verify_nonce($_POST['rs_footer_nonce'], 'rs_footer_save')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || wp_is_post_revision($post_id)
        || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $data = rs_footer_i18n_get($post_id);
    $raw = isset($_POST['rs_footer_i18n_input']) && is_array($_POST['rs_footer_i18n_input'])
        ? wp_unslash($_POST['rs_footer_i18n_input'])
        : [];
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        foreach (rs_footer_locale_keys() as $key) {
            $value = $loc[$key] ?? '';
            $data['locales'][$locale][$key] = function_exists('rs_sanitize_admin_text_field')
                ? rs_sanitize_admin_text_field($key, (string) $value)
                : sanitize_text_field((string) $value);
        }
    }

    $shared_raw = isset($_POST['rs_footer_shared']) && is_array($_POST['rs_footer_shared'])
        ? wp_unslash($_POST['rs_footer_shared'])
        : [];
    foreach (rs_footer_social_keys() as $key) {
        $value = $shared_raw[$key] ?? '';
        $data['shared'][$key] = function_exists('rs_sanitize_admin_text_field')
            ? rs_sanitize_admin_text_field($key, (string) $value)
            : sanitize_text_field((string) $value);
    }

    $data = rs_footer_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_FOOTER_I18N_KEY, $data);
    } else {
        update_post_meta($post_id, RS_FOOTER_I18N_KEY, $data);
    }
    rs_footer_sync_legacy_meta($post_id, $data);
}, 10);

function rs_footer_ensure_locale_posts(): void {
    // Legado no-op: a migração mantém um único post.
}

function rs_copy_footer_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}
