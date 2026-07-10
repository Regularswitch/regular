<?php
/**
 * Campos editáveis do CPT footer (meta box + REST API).
 */

if (defined('RS_FOOTER_FIELDS_LOADED')) {
    return;
}
define('RS_FOOTER_FIELDS_LOADED', true);

const RS_FOOTER_META_KEYS = [
    'rs_footer_brand_mark'      => 'Marca grande (ex: REGULARSWITCH)',
    'rs_footer_link_1_title'    => 'Coluna 1 — título',
    'rs_footer_link_1_subtitle' => 'Coluna 1 — subtítulo',
    'rs_footer_link_1_href'     => 'Coluna 1 — link',
    'rs_footer_link_2_title'    => 'Coluna 2 — título',
    'rs_footer_link_2_subtitle' => 'Coluna 2 — subtítulo',
    'rs_footer_link_2_href'     => 'Coluna 2 — link',
    'rs_footer_link_3_title'    => 'Coluna 3 — título',
    'rs_footer_link_3_subtitle' => 'Coluna 3 — subtítulo',
    'rs_footer_link_3_href'     => 'Coluna 3 — link',
    'rs_footer_legal_brand'     => 'Legal — marca',
    'rs_footer_legal_privacy'   => 'Legal — privacidade (texto)',
    'rs_footer_legal_privacy_href' => 'Legal — privacidade (link)',
    'rs_footer_legal_cookies'   => 'Legal — cookies (texto)',
    'rs_footer_legal_cookies_href' => 'Legal — cookies (link)',
];

function rs_footer_get_meta(int $post_id): array {
    $data = [];
    foreach (array_keys(RS_FOOTER_META_KEYS) as $key) {
        $data[$key] = (string) get_post_meta($post_id, $key, true);
    }
    return $data;
}

function rs_footer_meta_to_payload(array $meta): array {
    return [
        'brandMark' => $meta['rs_footer_brand_mark'] ?: 'REGULARSWITCH',
        'links'     => [
            [
                'title'    => $meta['rs_footer_link_1_title'] ?: '',
                'subtitle' => $meta['rs_footer_link_1_subtitle'] ?: '',
                'href'     => $meta['rs_footer_link_1_href'] ?: '',
            ],
            [
                'title'    => $meta['rs_footer_link_2_title'] ?: '',
                'subtitle' => $meta['rs_footer_link_2_subtitle'] ?: '',
                'href'     => $meta['rs_footer_link_2_href'] ?: '',
            ],
            [
                'title'    => $meta['rs_footer_link_3_title'] ?: '',
                'subtitle' => $meta['rs_footer_link_3_subtitle'] ?: '',
                'href'     => $meta['rs_footer_link_3_href'] ?: '',
            ],
        ],
        'legal' => [
            'brand'        => $meta['rs_footer_legal_brand'] ?: '@ RegularSwitch',
            'privacy'      => $meta['rs_footer_legal_privacy'] ?: 'Privacy Policy',
            'privacyHref'  => $meta['rs_footer_legal_privacy_href'] ?: '/privacy-policy',
            'cookies'      => $meta['rs_footer_legal_cookies'] ?: 'Cookies Policy',
            'cookiesHref'  => $meta['rs_footer_legal_cookies_href'] ?: '/cookies-policy',
        ],
    ];
}

add_action('init', function () {
    foreach (RS_FOOTER_META_KEYS as $key => $label) {
        register_post_meta('footer', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'default'       => '',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
});

add_action('rest_api_init', function () {
    register_rest_field('footer', 'footer_data', [
        'get_callback' => function (array $post) {
            $post_id = (int) $post['id'];

            if (function_exists('_getLang')) {
                $lang = _getLang();
                if ($lang) {
                    $translated_id = (int) get_post_meta($post_id, $lang, true);
                    if ($translated_id > 0) {
                        $post_id = $translated_id;
                    }
                }
            }

            return rs_footer_meta_to_payload(rs_footer_get_meta($post_id));
        },
        'schema' => [
            'description' => 'Dados estruturados do footer',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_footer', function () {
    add_meta_box(
        'rs_footer_fields',
        'Conteúdo do Footer',
        'rs_footer_render_meta_box',
        'footer',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'footer', 'normal');
}, 10);

function rs_footer_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_footer_save', 'rs_footer_nonce');
    $meta = rs_footer_get_meta($post->ID);

    $groups = [
        'Marca' => ['rs_footer_brand_mark'],
        'Coluna 1' => ['rs_footer_link_1_title', 'rs_footer_link_1_subtitle', 'rs_footer_link_1_href'],
        'Coluna 2' => ['rs_footer_link_2_title', 'rs_footer_link_2_subtitle', 'rs_footer_link_2_href'],
        'Coluna 3' => ['rs_footer_link_3_title', 'rs_footer_link_3_subtitle', 'rs_footer_link_3_href'],
        'Links legais' => [
            'rs_footer_legal_brand',
            'rs_footer_legal_privacy',
            'rs_footer_legal_privacy_href',
            'rs_footer_legal_cookies',
            'rs_footer_legal_cookies_href',
        ],
    ];

    echo '<p style="margin-top:0;color:#646970;">Preencha os campos abaixo. Não é necessário editar JSON no editor.</p>';

    foreach ($groups as $group_label => $keys) {
        echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
        echo '<legend style="font-weight:600;padding:0 6px;"><strong>' . esc_html($group_label) . '</strong></legend>';

        foreach ($keys as $key) {
            $label = RS_FOOTER_META_KEYS[$key];
            $value = $meta[$key] ?? '';
            rs_render_admin_text_field($key, $key, $label, $value);
        }

        echo '</fieldset>';
    }
}

add_action('save_post_footer', function (int $post_id) {
    if (!isset($_POST['rs_footer_nonce']) || !wp_verify_nonce($_POST['rs_footer_nonce'], 'rs_footer_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (array_keys(RS_FOOTER_META_KEYS) as $key) {
        $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
        update_post_meta($post_id, $key, rs_sanitize_admin_text_field($key, $raw));
    }
});
