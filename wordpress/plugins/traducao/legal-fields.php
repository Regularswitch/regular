<?php
/**
 * CPT legal — Privacidade & Cookies (menu lateral, rich text).
 */

if (defined('RS_LEGAL_FIELDS_LOADED')) {
    return;
}
define('RS_LEGAL_FIELDS_LOADED', true);

const RS_LEGAL_PRIVACY_TITLE_KEY = 'rs_legal_privacy_title';
const RS_LEGAL_PRIVACY_BODY_KEY = 'rs_legal_privacy_body';
const RS_LEGAL_COOKIES_MODAL_TITLE_KEY = 'rs_legal_cookies_modal_title';
const RS_LEGAL_COOKIES_INTRO_KEY = 'rs_legal_cookies_intro';
const RS_LEGAL_REJECT_LABEL_KEY = 'rs_legal_reject_label';
const RS_LEGAL_SUBMIT_LABEL_KEY = 'rs_legal_submit_label';
const RS_LEGAL_CATEGORIES_KEY = 'rs_legal_cookie_categories';

/**
 * @return array<int, array{id: string, title: string, description: string, locked: bool, defaultOn: bool}>
 */
function rs_legal_default_categories(string $locale): array {
    if ($locale === 'pt') {
        return [
            [
                'id'          => 'necessary',
                'title'       => 'Cookies estritamente necessários',
                'description' => '<p>Esses cookies são essenciais para o funcionamento do site (idioma, tema e preferências básicas). Não podem ser desativados.</p>',
                'locked'      => true,
                'defaultOn'   => true,
            ],
            [
                'id'          => 'performance',
                'title'       => 'Cookies de desempenho',
                'description' => '<p>Esses cookies permitem que nós e nossos parceiros de análise coletem informações sobre como você e outros visitantes usam nossos serviços. Usamos esses insights para melhorar produtos e serviços.</p>',
                'locked'      => false,
                'defaultOn'   => true,
            ],
            [
                'id'          => 'functional',
                'title'       => 'Cookies funcionais',
                'description' => '<p>Esses cookies permitem recursos aprimorados e personalização. Se desativados, alguns recursos podem não funcionar corretamente.</p>',
                'locked'      => false,
                'defaultOn'   => false,
            ],
            [
                'id'          => 'marketing',
                'title'       => 'Cookies de marketing',
                'description' => '<p>Esses cookies podem ser usados para exibir anúncios relevantes e medir campanhas. Podem ser definidos por nós ou por parceiros.</p>',
                'locked'      => false,
                'defaultOn'   => true,
            ],
        ];
    }

    return [
        [
            'id'          => 'necessary',
            'title'       => 'Strictly necessary cookies',
            'description' => '<p>These cookies are essential for the website to function (language, theme and basic preferences). They cannot be switched off.</p>',
            'locked'      => true,
            'defaultOn'   => true,
        ],
        [
            'id'          => 'performance',
            'title'       => 'Performance cookies',
            'description' => '<p>These cookies let us and our analytics partners collect information about how you and other visitors use our services. We use these insights to improve our products and services so they work better for you and everyone else.</p>',
            'locked'      => false,
            'defaultOn'   => true,
        ],
        [
            'id'          => 'functional',
            'title'       => 'Functional cookies',
            'description' => '<p>These cookies enable enhanced functionality and personalisation. If you disable them, some features may not work as expected.</p>',
            'locked'      => false,
            'defaultOn'   => false,
        ],
        [
            'id'          => 'marketing',
            'title'       => 'Marketing cookies',
            'description' => '<p>These cookies may be set to deliver relevant ads and measure campaigns. They can be set by us or by our partners.</p>',
            'locked'      => false,
            'defaultOn'   => true,
        ],
    ];
}

/**
 * @param array<int, mixed> $raw
 * @return array<int, array{id: string, title: string, description: string, locked: bool, defaultOn: bool}>
 */
function rs_legal_normalize_categories(array $raw, string $locale): array {
    $defaults = rs_legal_default_categories($locale);
    $by_id = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = sanitize_key((string) ($item['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $by_id[$id] = $item;
    }

    $out = [];
    foreach ($defaults as $default) {
        $id = $default['id'];
        $item = $by_id[$id] ?? [];
        $out[] = [
            'id'          => $id,
            'title'       => trim(wp_strip_all_tags((string) ($item['title'] ?? $default['title']))),
            'description' => wp_kses_post((string) ($item['description'] ?? $default['description'])),
            'locked'      => !empty($default['locked']),
            'defaultOn'   => array_key_exists('defaultOn', $item)
                ? (bool) $item['defaultOn']
                : (bool) $default['defaultOn'],
        ];
    }

    return $out;
}

function rs_legal_get_post_id_by_locale(string $locale): int {
    $posts = get_posts([
        'post_type'      => 'legal',
        'post_status'    => 'publish',
        'name'           => $locale,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

function rs_legal_meta_to_payload(int $post_id): array {
    $locale = get_post_field('post_name', $post_id) === 'pt' ? 'pt' : 'en';
    $defaults = rs_legal_default_categories($locale);

    $raw_cats = get_post_meta($post_id, RS_LEGAL_CATEGORIES_KEY, true);
    $cats = [];
    if (is_string($raw_cats) && $raw_cats !== '') {
        $decoded = json_decode($raw_cats, true);
        if (is_array($decoded)) {
            $cats = $decoded;
        }
    } elseif (is_array($raw_cats)) {
        $cats = $raw_cats;
    }

    $privacy_title = trim((string) get_post_meta($post_id, RS_LEGAL_PRIVACY_TITLE_KEY, true));
    $privacy_body = trim((string) get_post_meta($post_id, RS_LEGAL_PRIVACY_BODY_KEY, true));
    $modal_title = trim((string) get_post_meta($post_id, RS_LEGAL_COOKIES_MODAL_TITLE_KEY, true));
    $intro = trim((string) get_post_meta($post_id, RS_LEGAL_COOKIES_INTRO_KEY, true));
    $reject = trim((string) get_post_meta($post_id, RS_LEGAL_REJECT_LABEL_KEY, true));
    $submit = trim((string) get_post_meta($post_id, RS_LEGAL_SUBMIT_LABEL_KEY, true));

    return [
        'privacyTitle' => $privacy_title !== ''
            ? $privacy_title
            : ($locale === 'pt' ? 'Política de Privacidade' : 'Privacy Policy'),
        'privacyBody' => $privacy_body !== ''
            ? $privacy_body
            : ($locale === 'pt'
                ? '<p>Coletamos e processamos dados pessoais para operar este site e responder a solicitações. Para dúvidas, fale conosco em <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>'
                : '<p>We collect and process personal data to operate this website and respond to inquiries. For questions, contact us at <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>'),
        'cookiesModalTitle' => $modal_title !== ''
            ? $modal_title
            : ($locale === 'pt' ? 'Gerenciar preferências de cookies' : 'Manage cookie preferences'),
        'cookiesIntro' => $intro,
        'rejectAllLabel' => $reject !== ''
            ? $reject
            : ($locale === 'pt' ? 'Rejeitar todos' : 'Reject all'),
        'submitLabel' => $submit !== ''
            ? $submit
            : ($locale === 'pt' ? 'Enviar minhas escolhas' : 'Submit my choices'),
        'categories' => rs_legal_normalize_categories($cats ?: $defaults, $locale),
    ];
}

function rs_legal_seed_post(int $post_id, string $locale): void {
    $defaults = rs_legal_default_categories($locale);

    if (trim((string) get_post_meta($post_id, RS_LEGAL_PRIVACY_TITLE_KEY, true)) === '') {
        update_post_meta(
            $post_id,
            RS_LEGAL_PRIVACY_TITLE_KEY,
            $locale === 'pt' ? 'Política de Privacidade' : 'Privacy Policy'
        );
    }
    if (trim((string) get_post_meta($post_id, RS_LEGAL_PRIVACY_BODY_KEY, true)) === '') {
        update_post_meta(
            $post_id,
            RS_LEGAL_PRIVACY_BODY_KEY,
            $locale === 'pt'
                ? '<p>Coletamos e processamos dados pessoais para operar este site e responder a solicitações. Para dúvidas, fale conosco em <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>'
                : '<p>We collect and process personal data to operate this website and respond to inquiries. For questions, contact us at <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>'
        );
    }
    if (trim((string) get_post_meta($post_id, RS_LEGAL_COOKIES_MODAL_TITLE_KEY, true)) === '') {
        update_post_meta(
            $post_id,
            RS_LEGAL_COOKIES_MODAL_TITLE_KEY,
            $locale === 'pt' ? 'Gerenciar preferências de cookies' : 'Manage cookie preferences'
        );
    }
    if (trim((string) get_post_meta($post_id, RS_LEGAL_REJECT_LABEL_KEY, true)) === '') {
        update_post_meta($post_id, RS_LEGAL_REJECT_LABEL_KEY, $locale === 'pt' ? 'Rejeitar todos' : 'Reject all');
    }
    if (trim((string) get_post_meta($post_id, RS_LEGAL_SUBMIT_LABEL_KEY, true)) === '') {
        update_post_meta(
            $post_id,
            RS_LEGAL_SUBMIT_LABEL_KEY,
            $locale === 'pt' ? 'Enviar minhas escolhas' : 'Submit my choices'
        );
    }
    if (trim((string) get_post_meta($post_id, RS_LEGAL_CATEGORIES_KEY, true)) === '') {
        update_post_meta($post_id, RS_LEGAL_CATEGORIES_KEY, wp_json_encode($defaults, JSON_UNESCAPED_UNICODE));
    }
}

function rs_legal_ensure_locale_posts(): void {
    foreach (['en', 'pt'] as $locale) {
        $post_id = rs_legal_get_post_id_by_locale($locale);
        if ($post_id <= 0) {
            $post_id = (int) wp_insert_post([
                'post_title'  => $locale === 'pt' ? 'Privacidade & Cookies (PT)' : 'Privacy & Cookies (EN)',
                'post_status' => 'publish',
                'post_type'   => 'legal',
                'post_name'   => $locale,
                'post_author' => 1,
            ], true);
        }
        if ($post_id > 0 && !is_wp_error($post_id)) {
            rs_legal_seed_post($post_id, $locale);
        }
    }
    update_option('rs_legal_posts_ensured_v1', 1);
}

add_action('init', function () {
    $keys = [
        RS_LEGAL_PRIVACY_TITLE_KEY,
        RS_LEGAL_PRIVACY_BODY_KEY,
        RS_LEGAL_COOKIES_MODAL_TITLE_KEY,
        RS_LEGAL_COOKIES_INTRO_KEY,
        RS_LEGAL_REJECT_LABEL_KEY,
        RS_LEGAL_SUBMIT_LABEL_KEY,
        RS_LEGAL_CATEGORIES_KEY,
    ];
    foreach ($keys as $key) {
        register_post_meta('legal', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);

add_action('init', 'rs_legal_ensure_locale_posts', 25);

add_action('rest_api_init', function () {
    register_rest_field('legal', 'legal_data', [
        'get_callback' => function (array $post) {
            return rs_legal_meta_to_payload((int) $post['id']);
        },
        'schema' => [
            'description' => 'Privacidade e preferências de cookies',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_legal', function () {
    add_meta_box(
        'rs_legal_fields',
        'Privacidade & Cookies',
        'rs_legal_render_meta_box',
        'legal',
        'normal',
        'high'
    );
    remove_meta_box('postcustom', 'legal', 'normal');
}, 10);

function rs_legal_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_legal_save', 'rs_legal_nonce');
    $locale = $post->post_name === 'pt' ? 'pt' : 'en';
    rs_legal_seed_post((int) $post->ID, $locale);
    $payload = rs_legal_meta_to_payload((int) $post->ID);

    echo '<p style="margin-top:0;color:#646970;">Um post por idioma (<code>en</code> / <code>pt</code>). Textos com editor rich text. O popup de cookies no site usa as categorias abaixo. <em>(Plugin Tradução v1.2.15)</em></p>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Política de privacidade</strong></legend>';
    echo '<p style="margin:0 0 12px;"><label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>';
    echo '<input type="text" class="widefat" name="' . esc_attr(RS_LEGAL_PRIVACY_TITLE_KEY) . '" value="' . esc_attr($payload['privacyTitle']) . '" /></p>';
    echo '<p style="margin:0;"><label style="display:block;font-weight:500;margin-bottom:4px;">Conteúdo</label>';
    rs_render_rich_text_field(RS_LEGAL_PRIVACY_BODY_KEY, RS_LEGAL_PRIVACY_BODY_KEY, $payload['privacyBody'], 'paragraph');
    echo '</p></fieldset>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Popup de cookies</strong></legend>';
    echo '<p style="margin:0 0 12px;"><label style="display:block;font-weight:500;margin-bottom:4px;">Título do modal</label>';
    echo '<input type="text" class="widefat" name="' . esc_attr(RS_LEGAL_COOKIES_MODAL_TITLE_KEY) . '" value="' . esc_attr($payload['cookiesModalTitle']) . '" /></p>';
    echo '<p style="margin:0 0 12px;"><label style="display:block;font-weight:500;margin-bottom:4px;">Introdução (opcional)</label>';
    rs_render_rich_text_field(RS_LEGAL_COOKIES_INTRO_KEY, RS_LEGAL_COOKIES_INTRO_KEY, $payload['cookiesIntro'], 'paragraph');
    echo '</p>';
    echo '<p style="margin:0 0 12px;"><label style="display:block;font-weight:500;margin-bottom:4px;">Botão rejeitar</label>';
    echo '<input type="text" class="widefat" name="' . esc_attr(RS_LEGAL_REJECT_LABEL_KEY) . '" value="' . esc_attr($payload['rejectAllLabel']) . '" /></p>';
    echo '<p style="margin:0;"><label style="display:block;font-weight:500;margin-bottom:4px;">Botão enviar</label>';
    echo '<input type="text" class="widefat" name="' . esc_attr(RS_LEGAL_SUBMIT_LABEL_KEY) . '" value="' . esc_attr($payload['submitLabel']) . '" /></p>';
    echo '</fieldset>';

    foreach ($payload['categories'] as $index => $cat) {
        $id = (string) $cat['id'];
        $prefix = 'rs_legal_cat[' . $id . ']';
        echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
        echo '<legend style="font-weight:600;padding:0 6px;"><strong>' . esc_html($cat['title']) . '</strong> <code style="font-weight:400;">' . esc_html($id) . '</code></legend>';
        echo '<input type="hidden" name="' . esc_attr($prefix . '[id]') . '" value="' . esc_attr($id) . '" />';
        echo '<p style="margin:0 0 12px;"><label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>';
        echo '<input type="text" class="widefat" name="' . esc_attr($prefix . '[title]') . '" value="' . esc_attr($cat['title']) . '" /></p>';
        echo '<p style="margin:0 0 12px;"><label style="display:block;font-weight:500;margin-bottom:4px;">Descrição</label>';
        rs_render_rich_text_field(
            'rs_legal_cat_desc_' . $id,
            $prefix . '[description]',
            (string) $cat['description'],
            'paragraph'
        );
        echo '</p>';
        if (empty($cat['locked'])) {
            echo '<p style="margin:0;"><label><input type="checkbox" name="' . esc_attr($prefix . '[defaultOn]') . '" value="1"' . checked(!empty($cat['defaultOn']), true, false) . ' /> Ligado por padrão</label></p>';
        } else {
            echo '<p style="margin:0;color:#646970;font-size:12px;">Categoria obrigatória (sempre ligada).</p>';
            echo '<input type="hidden" name="' . esc_attr($prefix . '[defaultOn]') . '" value="1" />';
        }
        echo '</fieldset>';
    }
}

add_action('save_post_legal', function (int $post_id) {
    if (!isset($_POST['rs_legal_nonce']) || !wp_verify_nonce($_POST['rs_legal_nonce'], 'rs_legal_save')) {
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

    $locale = get_post_field('post_name', $post_id) === 'pt' ? 'pt' : 'en';

    update_post_meta(
        $post_id,
        RS_LEGAL_PRIVACY_TITLE_KEY,
        isset($_POST[RS_LEGAL_PRIVACY_TITLE_KEY]) ? sanitize_text_field(wp_unslash((string) $_POST[RS_LEGAL_PRIVACY_TITLE_KEY])) : ''
    );
    update_post_meta(
        $post_id,
        RS_LEGAL_PRIVACY_BODY_KEY,
        isset($_POST[RS_LEGAL_PRIVACY_BODY_KEY]) ? wp_kses_post(wp_unslash((string) $_POST[RS_LEGAL_PRIVACY_BODY_KEY])) : ''
    );
    update_post_meta(
        $post_id,
        RS_LEGAL_COOKIES_MODAL_TITLE_KEY,
        isset($_POST[RS_LEGAL_COOKIES_MODAL_TITLE_KEY]) ? sanitize_text_field(wp_unslash((string) $_POST[RS_LEGAL_COOKIES_MODAL_TITLE_KEY])) : ''
    );
    update_post_meta(
        $post_id,
        RS_LEGAL_COOKIES_INTRO_KEY,
        isset($_POST[RS_LEGAL_COOKIES_INTRO_KEY]) ? wp_kses_post(wp_unslash((string) $_POST[RS_LEGAL_COOKIES_INTRO_KEY])) : ''
    );
    update_post_meta(
        $post_id,
        RS_LEGAL_REJECT_LABEL_KEY,
        isset($_POST[RS_LEGAL_REJECT_LABEL_KEY]) ? sanitize_text_field(wp_unslash((string) $_POST[RS_LEGAL_REJECT_LABEL_KEY])) : ''
    );
    update_post_meta(
        $post_id,
        RS_LEGAL_SUBMIT_LABEL_KEY,
        isset($_POST[RS_LEGAL_SUBMIT_LABEL_KEY]) ? sanitize_text_field(wp_unslash((string) $_POST[RS_LEGAL_SUBMIT_LABEL_KEY])) : ''
    );

    $raw_cats = isset($_POST['rs_legal_cat']) && is_array($_POST['rs_legal_cat'])
        ? wp_unslash($_POST['rs_legal_cat'])
        : [];
    $normalized_input = [];
    foreach ($raw_cats as $id => $row) {
        if (!is_array($row)) {
            continue;
        }
        $normalized_input[] = [
            'id'          => sanitize_key((string) ($row['id'] ?? $id)),
            'title'       => sanitize_text_field((string) ($row['title'] ?? '')),
            'description' => wp_kses_post((string) ($row['description'] ?? '')),
            'defaultOn'   => !empty($row['defaultOn']),
        ];
    }
    $cats = rs_legal_normalize_categories($normalized_input, $locale);
    update_post_meta($post_id, RS_LEGAL_CATEGORIES_KEY, wp_json_encode($cats, JSON_UNESCAPED_UNICODE));
});

function rs_copy_legal_fields(int $from_id, int $to_id): void {
    foreach ([
        RS_LEGAL_PRIVACY_TITLE_KEY,
        RS_LEGAL_PRIVACY_BODY_KEY,
        RS_LEGAL_COOKIES_MODAL_TITLE_KEY,
        RS_LEGAL_COOKIES_INTRO_KEY,
        RS_LEGAL_REJECT_LABEL_KEY,
        RS_LEGAL_SUBMIT_LABEL_KEY,
        RS_LEGAL_CATEGORIES_KEY,
    ] as $key) {
        update_post_meta($to_id, $key, get_post_meta($from_id, $key, true));
    }
}
