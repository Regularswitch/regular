<?php
/**
 * CPT legal — post único bilíngue (EN + PT).
 */

if (defined('RS_LEGAL_FIELDS_LOADED')) {
    return;
}
define('RS_LEGAL_FIELDS_LOADED', true);

const RS_LEGAL_I18N_KEY = 'rs_legal_i18n';
const RS_LEGAL_PRIVACY_TITLE_KEY = 'rs_legal_privacy_title';
const RS_LEGAL_PRIVACY_BODY_KEY = 'rs_legal_privacy_body';
const RS_LEGAL_COOKIES_MODAL_TITLE_KEY = 'rs_legal_cookies_modal_title';
const RS_LEGAL_COOKIES_INTRO_KEY = 'rs_legal_cookies_intro';
const RS_LEGAL_REJECT_LABEL_KEY = 'rs_legal_reject_label';
const RS_LEGAL_SUBMIT_LABEL_KEY = 'rs_legal_submit_label';
const RS_LEGAL_CATEGORIES_KEY = 'rs_legal_cookie_categories';

function rs_legal_default_categories(string $locale): array {
    $pt = $locale === 'pt';
    return [
        ['id' => 'necessary', 'title' => $pt ? 'Cookies estritamente necessários' : 'Strictly necessary cookies', 'description' => $pt ? '<p>Esses cookies são essenciais para o funcionamento do site (idioma, tema e preferências básicas). Não podem ser desativados.</p>' : '<p>These cookies are essential for the website to function (language, theme and basic preferences). They cannot be switched off.</p>', 'locked' => true, 'defaultOn' => true],
        ['id' => 'performance', 'title' => $pt ? 'Cookies de desempenho' : 'Performance cookies', 'description' => $pt ? '<p>Esses cookies permitem que nós e nossos parceiros de análise coletem informações sobre como você e outros visitantes usam nossos serviços. Usamos esses insights para melhorar produtos e serviços.</p>' : '<p>These cookies let us and our analytics partners collect information about how you and other visitors use our services. We use these insights to improve our products and services so they work better for you and everyone else.</p>', 'locked' => false, 'defaultOn' => true],
        ['id' => 'functional', 'title' => $pt ? 'Cookies funcionais' : 'Functional cookies', 'description' => $pt ? '<p>Esses cookies permitem recursos aprimorados e personalização. Se desativados, alguns recursos podem não funcionar corretamente.</p>' : '<p>These cookies enable enhanced functionality and personalisation. If you disable them, some features may not work as expected.</p>', 'locked' => false, 'defaultOn' => false],
        ['id' => 'marketing', 'title' => $pt ? 'Cookies de marketing' : 'Marketing cookies', 'description' => $pt ? '<p>Esses cookies podem ser usados para exibir anúncios relevantes e medir campanhas. Podem ser definidos por nós ou por parceiros.</p>' : '<p>These cookies may be set to deliver relevant ads and measure campaigns. They can be set by us or by our partners.</p>', 'locked' => false, 'defaultOn' => true],
    ];
}

function rs_legal_normalize_categories(array $raw, string $locale): array {
    $by_id = [];
    foreach ($raw as $item) {
        if (is_array($item) && sanitize_key((string) ($item['id'] ?? '')) !== '') {
            $by_id[sanitize_key((string) $item['id'])] = $item;
        }
    }
    $out = [];
    foreach (rs_legal_default_categories($locale) as $default) {
        $item = $by_id[$default['id']] ?? [];
        $out[] = [
            'id' => $default['id'],
            'title' => trim(wp_strip_all_tags((string) ($item['title'] ?? $default['title']))),
            'description' => wp_kses_post((string) ($item['description'] ?? $default['description'])),
            'locked' => (bool) $default['locked'],
            'defaultOn' => array_key_exists('defaultOn', $item) ? (bool) $item['defaultOn'] : (bool) $default['defaultOn'],
        ];
    }
    return $out;
}

function rs_legal_default_locale(string $locale): array {
    $pt = $locale === 'pt';
    return [
        'privacyTitle' => $pt ? 'Política de Privacidade' : 'Privacy Policy',
        'privacyBody' => $pt
            ? '<p>Coletamos e processamos dados pessoais para operar este site e responder a solicitações. Para dúvidas, fale conosco em <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>'
            : '<p>We collect and process personal data to operate this website and respond to inquiries. For questions, contact us at <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>',
        'cookiesModalTitle' => $pt ? 'Gerenciar preferências de cookies' : 'Manage cookie preferences',
        'cookiesIntro' => '',
        'rejectAllLabel' => $pt ? 'Rejeitar todos' : 'Reject all',
        'submitLabel' => $pt ? 'Enviar minhas escolhas' : 'Submit my choices',
        'categories' => rs_legal_default_categories($locale),
    ];
}

function rs_legal_i18n_default(): array {
    return ['v' => 1, 'shared' => [], 'locales' => ['en' => rs_legal_default_locale('en'), 'pt' => rs_legal_default_locale('pt')]];
}

function rs_legal_i18n_normalize(array $raw): array {
    $data = rs_legal_i18n_default();
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $defaults = rs_legal_default_locale($locale);
        foreach (['privacyTitle', 'cookiesModalTitle', 'rejectAllLabel', 'submitLabel'] as $key) {
            $data['locales'][$locale][$key] = wp_kses_post((string) ($loc[$key] ?? $defaults[$key]));
        }
        foreach (['privacyBody', 'cookiesIntro'] as $key) {
            $data['locales'][$locale][$key] = wp_kses_post((string) ($loc[$key] ?? $defaults[$key]));
        }
        $categories = is_array($loc['categories'] ?? null) ? $loc['categories'] : [];
        $data['locales'][$locale]['categories'] = rs_legal_normalize_categories($categories, $locale);
    }
    return $data;
}

function rs_legal_locale_from_legacy_post(int $post_id, string $locale): array {
    if ($post_id <= 0) {
        return rs_legal_default_locale($locale);
    }
    $defaults = rs_legal_default_locale($locale);
    $map = [
        'privacyTitle' => RS_LEGAL_PRIVACY_TITLE_KEY, 'privacyBody' => RS_LEGAL_PRIVACY_BODY_KEY,
        'cookiesModalTitle' => RS_LEGAL_COOKIES_MODAL_TITLE_KEY, 'cookiesIntro' => RS_LEGAL_COOKIES_INTRO_KEY,
        'rejectAllLabel' => RS_LEGAL_REJECT_LABEL_KEY, 'submitLabel' => RS_LEGAL_SUBMIT_LABEL_KEY,
    ];
    $loc = $defaults;
    foreach ($map as $field => $key) {
        $value = (string) get_post_meta($post_id, $key, true);
        if ($value !== '') {
            $loc[$field] = $value;
        }
    }
    $cats = function_exists('rs_meta_get_array')
        ? rs_meta_get_array($post_id, RS_LEGAL_CATEGORIES_KEY)
        : get_post_meta($post_id, RS_LEGAL_CATEGORIES_KEY, true);
    $loc['categories'] = rs_legal_normalize_categories(is_array($cats) ? $cats : [], $locale);
    return $loc;
}

function rs_legal_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_LEGAL_I18N_KEY)
        : get_post_meta($post_id, RS_LEGAL_I18N_KEY, true);
    if (is_array($raw)) {
        return rs_legal_i18n_normalize($raw);
    }
    $data = rs_legal_i18n_default();
    $data['locales']['en'] = rs_legal_locale_from_legacy_post($post_id, 'en');
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_legal_locale_from_legacy_post($pt_id, 'pt');
    }
    return rs_legal_i18n_normalize($data);
}

function rs_legal_meta_to_payload(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_legal_i18n_get($post_id);
    return $data['locales'][$locale] ?? rs_legal_default_locale($locale);
}

function rs_legal_get_post_id_by_locale(string $locale = 'en'): int {
    return function_exists('rs_section_i18n_canonical_id') ? rs_section_i18n_canonical_id('legal') : 0;
}

function rs_legal_sync_legacy_meta(int $post_id, array $data): void {
    $en = $data['locales']['en'] ?? rs_legal_default_locale('en');
    $map = [
        'privacyTitle' => RS_LEGAL_PRIVACY_TITLE_KEY, 'privacyBody' => RS_LEGAL_PRIVACY_BODY_KEY,
        'cookiesModalTitle' => RS_LEGAL_COOKIES_MODAL_TITLE_KEY, 'cookiesIntro' => RS_LEGAL_COOKIES_INTRO_KEY,
        'rejectAllLabel' => RS_LEGAL_REJECT_LABEL_KEY, 'submitLabel' => RS_LEGAL_SUBMIT_LABEL_KEY,
    ];
    foreach ($map as $field => $key) {
        update_post_meta($post_id, $key, (string) ($en[$field] ?? ''));
    }
    $cats = rs_legal_normalize_categories((array) ($en['categories'] ?? []), 'en');
    if (function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, RS_LEGAL_CATEGORIES_KEY, $cats);
    } else {
        update_post_meta($post_id, RS_LEGAL_CATEGORIES_KEY, $cats);
    }
}

function rs_legal_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }
    $id = rs_section_i18n_migrate_twins(
        'legal', RS_LEGAL_I18N_KEY, 'rs_legal_i18n_migrated_v1', 'Privacy & Cookies',
        static fn(int $post_id, string $locale): array => rs_legal_locale_from_legacy_post($post_id, $locale),
        'rs_legal_i18n_normalize'
    );
    if ($id > 0) {
        rs_legal_sync_legacy_meta($id, rs_legal_i18n_get($id));
    }
}

add_action('init', function () {
    register_post_meta('legal', RS_LEGAL_I18N_KEY, [
        'single' => true, 'type' => 'array', 'show_in_rest' => false,
        'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
    ]);
    foreach ([RS_LEGAL_PRIVACY_TITLE_KEY, RS_LEGAL_PRIVACY_BODY_KEY, RS_LEGAL_COOKIES_MODAL_TITLE_KEY, RS_LEGAL_COOKIES_INTRO_KEY, RS_LEGAL_REJECT_LABEL_KEY, RS_LEGAL_SUBMIT_LABEL_KEY, RS_LEGAL_CATEGORIES_KEY] as $key) {
        register_post_meta('legal', $key, [
            'single' => true, 'type' => 'string', 'show_in_rest' => false,
            'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
        ]);
    }
}, 20);
add_action('init', 'rs_legal_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('legal', 'legal_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request') ? rs_section_i18n_locale_from_request($request) : 'en';
            return rs_legal_meta_to_payload((int) $post['id'], $locale);
        },
        'schema' => ['description' => 'Privacidade e preferências de cookies', 'type' => 'object', 'context' => ['view', 'edit']],
    ]);
});

add_action('add_meta_boxes_legal', function () {
    add_meta_box('rs_legal_fields', 'Privacidade & Cookies', 'rs_legal_render_meta_box', 'legal', 'normal', 'high');
    remove_meta_box('postcustom', 'legal', 'normal');
}, 10);

function rs_legal_render_locale_fields(string $locale, array $loc): void {
    $prefix = 'rs_legal_i18n_input[' . $locale . ']';
    echo '<div data-rs-accordion class="rs-legal-accordion">';

    rs_metabox_accordion_item_open('Política de privacidade', true);
    echo '<p class="rs-admin-text-field"><label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>';
    rs_render_rich_text_field('rs_legal_privacy_title_' . $locale, $prefix . '[privacyTitle]', (string) $loc['privacyTitle'], 'compact');
    echo '</p>';
    echo '<p class="rs-admin-text-field"><label style="display:block;font-weight:500;margin-bottom:4px;">Corpo</label>';
    rs_render_rich_text_field('rs_legal_privacy_body_' . $locale, $prefix . '[privacyBody]', (string) $loc['privacyBody'], 'paragraph');
    echo '</p>';
    rs_metabox_accordion_item_close();

    rs_metabox_accordion_item_open('Popup de cookies', false);
    echo '<p class="rs-admin-text-field"><label style="display:block;font-weight:500;margin-bottom:4px;">Título do modal</label>';
    rs_render_rich_text_field('rs_legal_cookies_modal_title_' . $locale, $prefix . '[cookiesModalTitle]', (string) $loc['cookiesModalTitle'], 'compact');
    echo '</p>';
    echo '<p class="rs-admin-text-field"><label style="display:block;font-weight:500;margin-bottom:4px;">Introdução (opcional)</label>';
    rs_render_rich_text_field('rs_legal_cookies_intro_' . $locale, $prefix . '[cookiesIntro]', (string) $loc['cookiesIntro'], 'paragraph');
    echo '</p>';
    echo '<p class="rs-admin-text-field"><label style="display:block;font-weight:500;margin-bottom:4px;">Botão rejeitar</label>';
    rs_render_rich_text_field('rs_legal_reject_' . $locale, $prefix . '[rejectAllLabel]', (string) $loc['rejectAllLabel'], 'compact');
    echo '</p>';
    echo '<p class="rs-admin-text-field"><label style="display:block;font-weight:500;margin-bottom:4px;">Botão enviar</label>';
    rs_render_rich_text_field('rs_legal_submit_' . $locale, $prefix . '[submitLabel]', (string) $loc['submitLabel'], 'compact');
    echo '</p>';
    rs_metabox_accordion_item_close();

    foreach ((array) $loc['categories'] as $cat) {
        $id = (string) $cat['id'];
        $cat_prefix = $prefix . '[categories][' . $id . ']';
        rs_metabox_accordion_item_open((string) $cat['title'] . ' (' . $id . ')', false);
        echo '<input type="hidden" name="' . esc_attr($cat_prefix . '[id]') . '" value="' . esc_attr($id) . '" />';
        echo '<p class="rs-admin-text-field"><label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>';
        rs_render_rich_text_field('rs_legal_cat_title_' . $locale . '_' . $id, $cat_prefix . '[title]', (string) $cat['title'], 'compact');
        echo '</p>';
        echo '<p class="rs-admin-text-field"><label style="display:block;font-weight:500;margin-bottom:4px;">Descrição</label>';
        rs_render_rich_text_field('rs_legal_cat_' . $locale . '_' . $id, $cat_prefix . '[description]', (string) $cat['description'], 'paragraph');
        echo '</p>';
        if (empty($cat['locked'])) {
            echo '<p><label><input type="checkbox" name="' . esc_attr($cat_prefix . '[defaultOn]') . '" value="1"' . checked(!empty($cat['defaultOn']), true, false) . ' /> Ligado por padrão</label></p>';
        } else {
            echo '<input type="hidden" name="' . esc_attr($cat_prefix . '[defaultOn]') . '" value="1" /><p style="color:#646970;">Categoria obrigatória.</p>';
        }
        rs_metabox_accordion_item_close();
    }

    echo '</div>';
}

function rs_legal_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_legal_save', 'rs_legal_nonce');
    $id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id((int) $post->ID) : (int) $post->ID;
    $data = rs_legal_i18n_get($id);
    echo '<p style="margin-top:0;color:#646970;">Um único post. Edite English e Português nas abas. ' . (function_exists('rs_plugin_version_markup') ? rs_plugin_version_markup() : '') . '</p>';
    echo '<div class="rs-metabox-tabs" data-rs-tabs><div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button></div>';
    echo '<div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_legal_render_locale_fields('en', $data['locales']['en']);
    echo '</div><div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_legal_render_locale_fields('pt', $data['locales']['pt']);
    echo '</div></div>';
}

add_action('save_post_legal', function (int $post_id) {
    if (!isset($_POST['rs_legal_nonce']) || !wp_verify_nonce($_POST['rs_legal_nonce'], 'rs_legal_save')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)
        || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $data = rs_legal_i18n_get($post_id);
    $raw = isset($_POST['rs_legal_i18n_input']) && is_array($_POST['rs_legal_i18n_input'])
        ? wp_unslash($_POST['rs_legal_i18n_input'])
        : [];
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        $cats = [];
        foreach ((array) ($loc['categories'] ?? []) as $id => $cat) {
            if (!is_array($cat)) {
                continue;
            }
            $cats[] = [
                'id' => sanitize_key((string) ($cat['id'] ?? $id)),
                'title' => wp_kses_post((string) ($cat['title'] ?? '')),
                'description' => wp_kses_post((string) ($cat['description'] ?? '')),
                'defaultOn' => !empty($cat['defaultOn']),
            ];
        }
        $data['locales'][$locale] = [
            'privacyTitle' => wp_kses_post((string) ($loc['privacyTitle'] ?? '')),
            'privacyBody' => wp_kses_post((string) ($loc['privacyBody'] ?? '')),
            'cookiesModalTitle' => wp_kses_post((string) ($loc['cookiesModalTitle'] ?? '')),
            'cookiesIntro' => wp_kses_post((string) ($loc['cookiesIntro'] ?? '')),
            'rejectAllLabel' => wp_kses_post((string) ($loc['rejectAllLabel'] ?? '')),
            'submitLabel' => wp_kses_post((string) ($loc['submitLabel'] ?? '')),
            'categories' => $cats,
        ];
    }
    $data = rs_legal_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_LEGAL_I18N_KEY, $data);
    } else {
        update_post_meta($post_id, RS_LEGAL_I18N_KEY, $data);
    }
    rs_legal_sync_legacy_meta($post_id, $data);
}, 10);

function rs_legal_ensure_locale_posts(): void {
    // Legado no-op: post único.
}

function rs_copy_legal_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}
