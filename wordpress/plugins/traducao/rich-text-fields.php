<?php
/**
 * Editores rich text (TinyMCE) para campos de conteúdo.
 */

if (defined('RS_RICH_TEXT_LOADED')) {
    return;
}
define('RS_RICH_TEXT_LOADED', true);

const RS_RICH_TEXT_CPT_TYPES = [
    'intro',
    'footer',
    'capabilities',
    'about',
    'education',
    'contact',
    'projects-page',
    'project',
];

/**
 * @return array<string, mixed>
 */
function rs_rich_text_editor_settings(string $profile = 'inline'): array {
    $base = [
        'media_buttons' => false,
        'wpautop'       => false,
        'textarea_rows' => 5,
    ];

    if ($profile === 'paragraph') {
        return array_merge($base, [
            'teeny'     => true,
            'quicktags' => true,
            'tinymce'   => [
                'toolbar1' => 'bold,italic,link,unlink,bullist,undo,redo',
                'toolbar2' => '',
            ],
        ]);
    }

    return array_merge($base, [
        'teeny'     => false,
        'quicktags' => ['buttons' => 'strong,em,link,close'],
        'tinymce'   => [
            'toolbar1'          => 'bold,italic,link,unlink,undo,redo',
            'toolbar2'          => '',
            'forced_root_block' => false,
            'force_br_newlines' => true,
            'force_p_newlines'  => false,
        ],
    ]);
}

function rs_render_rich_text_field(string $id, string $name, string $value, string $profile = 'inline'): void {
    wp_enqueue_editor();

    wp_editor(
        $value,
        $id,
        array_merge(
            rs_rich_text_editor_settings($profile),
            ['textarea_name' => $name],
        ),
    );
}

function rs_field_is_href_key(string $key): bool {
    return str_contains($key, '_href');
}

function rs_field_is_plain_text_key(string $key): bool {
    return $key === 'rs_footer_brand_mark'
        || $key === 'rs_footer_legal_brand'
        || $key === 'rs_footer_legal_privacy'
        || $key === 'rs_footer_legal_cookies'
        || str_ends_with($key, '_image_slug')
        || (str_contains($key, 'rs_footer_link_') && str_ends_with($key, '_title'));
}

function rs_field_rich_text_profile(string $key): string {
    if (
        str_contains($key, '_body')
        || str_contains($key, '_text')
        || str_contains($key, '_subtitle')
        || $key === 'rs_intro_body'
    ) {
        return 'paragraph';
    }

    return 'inline';
}

function rs_render_admin_text_field(string $id, string $name, string $label, string $value): void {
    echo '<p style="margin:0 0 10px;">';
    echo '<label for="' . esc_attr($id) . '" style="display:block;font-weight:500;margin-bottom:4px;">' . esc_html($label) . '</label>';

    if (rs_field_is_href_key($name) || rs_field_is_plain_text_key($name)) {
        echo '<input type="text" style="width:100%;" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
        echo '</p>';
        return;
    }

    rs_render_rich_text_field($id, $name, $value, rs_field_rich_text_profile($name));
    echo '</p>';
}

function rs_sanitize_admin_text_field(string $key, string $raw): string {
    if (rs_field_is_href_key($key) || rs_field_is_plain_text_key($key)) {
        return sanitize_text_field($raw);
    }

    return wp_kses_post($raw);
}

function rs_rich_text_js_settings(string $profile = 'paragraph'): array {
    $settings = rs_rich_text_editor_settings($profile);

    return [
        'mediaButtons' => false,
        'tinymce'      => $settings['tinymce'] ?? true,
        'quicktags'    => $settings['quicktags'] ?? true,
    ];
}

add_filter('use_block_editor_for_post_type', function ($use, string $post_type) {
    if (in_array($post_type, RS_RICH_TEXT_CPT_TYPES, true)) {
        return false;
    }

    return $use;
}, 999, 2);

add_filter('use_block_editor_for_post', function ($use, WP_Post $post) {
    if (in_array($post->post_type, RS_RICH_TEXT_CPT_TYPES, true)) {
        return false;
    }

    return $use;
}, 999, 2);

add_action('admin_enqueue_scripts', function (string $hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, RS_RICH_TEXT_CPT_TYPES, true)) {
        return;
    }

    wp_enqueue_editor();
}, 5);
