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
    'legal',
    'projects-page',
    'project',
    'site-ui',
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

    if ($profile === 'compact') {
        return array_merge($base, [
            'textarea_rows' => 2,
            'teeny'         => true,
            'quicktags'     => ['buttons' => 'strong,em,link,close'],
            'tinymce'       => [
                'toolbar1'          => 'bold,italic,link,unlink,undo,redo',
                'toolbar2'          => '',
                'forced_root_block' => false,
                'force_br_newlines' => true,
                'force_p_newlines'  => false,
                'height'            => 90,
            ],
        ]);
    }

    if ($profile === 'paragraph') {
        return array_merge($base, [
            'textarea_rows' => 4,
            'teeny'         => true,
            'quicktags'     => true,
            'tinymce'       => [
                'toolbar1' => 'bold,italic,link,unlink,bullist,undo,redo',
                'toolbar2' => '',
                'height'   => 160,
            ],
        ]);
    }

    return array_merge($base, [
        'textarea_rows' => 3,
        'teeny'         => false,
        'quicktags'     => ['buttons' => 'strong,em,link,close'],
        'tinymce'       => [
            'toolbar1'          => 'bold,italic,link,unlink,undo,redo',
            'toolbar2'          => '',
            'forced_root_block' => false,
            'force_br_newlines' => true,
            'force_p_newlines'  => false,
            'height'            => 120,
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
    return str_contains($key, '_href')
        || str_ends_with($key, '_tel')
        || str_contains($key, '_email')
        || str_contains($key, '_phone');
}

function rs_field_is_plain_text_key(string $key): bool {
    // Só URLs / contatos técnicos ficam em input simples.
    return rs_field_is_href_key($key)
        || str_ends_with($key, '_image_slug')
        || str_contains($key, 'phone_tel');
}

function rs_field_rich_text_profile(string $key): string {
    if (
        str_contains($key, '_body')
        || str_contains($key, '_text')
        || str_contains($key, '_subtitle')
        || str_contains($key, '_excerpt')
        || $key === 'rs_intro_body'
    ) {
        return 'paragraph';
    }

    // Títulos, labels, marcas — rich text compacto.
    return 'compact';
}

function rs_render_admin_text_field(string $id, string $name, string $label, string $value): void {
    echo '<p class="rs-admin-text-field" style="margin:0 0 10px;">';
    echo '<label for="' . esc_attr($id) . '" style="display:block;font-weight:500;margin-bottom:4px;">' . esc_html($label) . '</label>';

    if (rs_field_is_plain_text_key($name) || rs_field_is_href_key($name)) {
        echo '<input type="text" style="width:100%;" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
        echo '</p>';
        return;
    }

    rs_render_rich_text_field($id, $name, $value, rs_field_rich_text_profile($name));
    echo '</p>';
}

/**
 * Abre um item de acordeão no meta box (mesmo chrome das outras seções).
 */
function rs_metabox_accordion_item_open(string $title, bool $is_open = false): void {
    $class = 'rs-metabox-accordion-item' . ($is_open ? ' is-open' : '');
    echo '<fieldset class="' . esc_attr($class) . '">';
    echo '<div class="rs-metabox-accordion-head">';
    echo '<button type="button" class="rs-metabox-accordion-toggle" aria-expanded="' . ($is_open ? 'true' : 'false') . '">';
    echo '<span class="rs-metabox-accordion-head-title">' . esc_html($title) . '</span>';
    echo '</button>';
    echo '</div>';
    echo '<div class="rs-metabox-accordion-panel">';
}

function rs_metabox_accordion_item_close(): void {
    echo '</div></fieldset>';
}

function rs_sanitize_admin_text_field(string $key, string $raw): string {
    if (rs_field_is_href_key($key) || rs_field_is_plain_text_key($key)) {
        return sanitize_text_field($raw);
    }

    return rs_clean_editor_html(wp_kses_post($raw));
}

/**
 * Remove lixo de extensões (ex.: Google Translate #gtx-trans) colado no editor.
 */
function rs_clean_editor_html(string $html): string {
    $html = preg_replace('/\r\n?/', "\n", $html) ?? $html;
    // Ícone interno primeiro; depois o wrapper #gtx-trans (evitar cortar no </div> interno).
    $html = preg_replace('/<div[^>]*class=(["\'])[^"\']*gtx-trans-icon[^"\']*\1[^>]*>.*?<\/div>/is', '', $html) ?? $html;
    $html = preg_replace('/<div[^>]*\bid=(["\'])gtx-trans\1[^>]*>\s*<\/div>/is', '', $html) ?? $html;
    $html = preg_replace('/<div[^>]*\bid=(["\'])gtx-trans\1[^>]*>[\s\S]*?<\/div>/is', '', $html) ?? $html;
    $html = preg_replace('/<font[^>]*>.*?<\/font>/is', '', $html) ?? $html;
    return trim($html);
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
