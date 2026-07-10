<?php
/**
 * Campos editáveis do CPT capabilities (meta box + REST API).
 */

if (defined('RS_CAPABILITIES_FIELDS_LOADED')) {
    return;
}
define('RS_CAPABILITIES_FIELDS_LOADED', true);

const RS_CAPABILITIES_HEADLINE_KEY = 'rs_capabilities_headline';
const RS_CAPABILITIES_SECTION_COUNT = 8;

function rs_capabilities_section_field_map(int $index): array {
    return [
        "rs_cap_sec_{$index}_title"           => "Seção {$index} — título",
        "rs_cap_sec_{$index}_lead"            => "Seção {$index} — pergunta / lead",
        "rs_cap_sec_{$index}_body"            => "Seção {$index} — texto (HTML)",
        "rs_cap_sec_{$index}_services_title"  => "Seção {$index} — título da lista",
        "rs_cap_sec_{$index}_services"        => "Seção {$index} — serviços (um por linha)",
        "rs_cap_sec_{$index}_image_slug"      => "Seção {$index} — slug do projeto (imagem)",
    ];
}

function rs_capabilities_all_meta_keys(): array {
    $keys = [RS_CAPABILITIES_HEADLINE_KEY => 'Headline da página'];

    for ($i = 1; $i <= RS_CAPABILITIES_SECTION_COUNT; $i++) {
        $keys = array_merge($keys, rs_capabilities_section_field_map($i));
    }

    return $keys;
}

function rs_capabilities_get_meta(int $post_id): array {
    $data = [];
    foreach (array_keys(rs_capabilities_all_meta_keys()) as $key) {
        $data[$key] = (string) get_post_meta($post_id, $key, true);
    }
    return $data;
}

function rs_capabilities_parse_services(string $raw): array {
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $services = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $services[] = $line;
        }
    }

    return $services;
}

function rs_capabilities_meta_to_payload(array $meta): array {
    $sections = [];

    for ($i = 1; $i <= RS_CAPABILITIES_SECTION_COUNT; $i++) {
        $title = trim($meta["rs_cap_sec_{$i}_title"] ?? '');
        if ($title === '') {
            continue;
        }

        $services = rs_capabilities_parse_services($meta["rs_cap_sec_{$i}_services"] ?? '');

        $sections[] = [
            'title'             => strtoupper($title),
            'lead'              => trim($meta["rs_cap_sec_{$i}_lead"] ?? ''),
            'body'              => trim($meta["rs_cap_sec_{$i}_body"] ?? ''),
            'servicesTitle'     => trim($meta["rs_cap_sec_{$i}_services_title"] ?? ''),
            'services'          => $services,
            'imageProjectSlug'  => sanitize_title($meta["rs_cap_sec_{$i}_image_slug"] ?? ''),
        ];
    }

    return [
        'headline' => trim($meta[RS_CAPABILITIES_HEADLINE_KEY] ?? ''),
        'sections' => $sections,
    ];
}

function rs_capabilities_is_payload(array $payload): bool {
    return !empty($payload['headline']) || !empty($payload['sections']);
}

add_action('init', function () {
    foreach (array_keys(rs_capabilities_all_meta_keys()) as $key) {
        register_post_meta('capabilities', $key, [
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
    register_rest_field('capabilities', 'capabilities_data', [
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

            return rs_capabilities_meta_to_payload(rs_capabilities_get_meta($post_id));
        },
        'schema' => [
            'description' => 'Dados estruturados da página Capacidades',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_capabilities', function () {
    add_meta_box(
        'rs_capabilities_fields',
        'Conteúdo da página Capacidades',
        'rs_capabilities_render_meta_box',
        'capabilities',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'capabilities', 'normal');
}, 10);

function rs_capabilities_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_capabilities_save', 'rs_capabilities_nonce');
    $meta = rs_capabilities_get_meta($post->ID);

    echo '<p style="margin-top:0;color:#646970;">Preencha o headline e as seções do acordeão. Seções sem título são ignoradas no site.</p>';

    echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Headline</strong></legend>';
    echo '<textarea style="width:100%;min-height:100px;font-family:monospace;font-size:13px;" id="' . esc_attr(RS_CAPABILITIES_HEADLINE_KEY) . '" name="' . esc_attr(RS_CAPABILITIES_HEADLINE_KEY) . '">' . esc_textarea($meta[RS_CAPABILITIES_HEADLINE_KEY] ?? '') . '</textarea>';
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Aceita <code>&lt;strong&gt;</code> para destaques.</p>';
    echo '</fieldset>';

    for ($i = 1; $i <= RS_CAPABILITIES_SECTION_COUNT; $i++) {
        $fields = rs_capabilities_section_field_map($i);
        echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
        echo '<legend style="font-weight:600;padding:0 6px;"><strong>Seção ' . esc_html((string) $i) . '</strong></legend>';

        foreach ($fields as $key => $label) {
            $value = $meta[$key] ?? '';
            $is_textarea = str_ends_with($key, '_body') || str_ends_with($key, '_services');

            echo '<p style="margin:0 0 10px;">';
            echo '<label for="' . esc_attr($key) . '" style="display:block;font-weight:500;margin-bottom:4px;">' . esc_html($label) . '</label>';

            if ($is_textarea) {
                $rows = str_ends_with($key, '_services') ? 6 : 4;
                echo '<textarea style="width:100%;min-height:' . esc_attr((string) ($rows * 22)) . 'px;font-family:monospace;font-size:13px;" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea>';
            } else {
                echo '<input type="text" style="width:100%;" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }

            echo '</p>';
        }

        echo '</fieldset>';
    }
}

add_action('save_post_capabilities', function (int $post_id) {
    if (!isset($_POST['rs_capabilities_nonce']) || !wp_verify_nonce($_POST['rs_capabilities_nonce'], 'rs_capabilities_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (array_keys(rs_capabilities_all_meta_keys()) as $key) {
        $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
        $value = str_ends_with($key, '_body') ? wp_kses_post($raw) : sanitize_text_field($raw);
        update_post_meta($post_id, $key, $value);
    }
});

function rs_copy_capabilities_fields(int $from_id, int $to_id): void {
    foreach (rs_capabilities_get_meta($from_id) as $key => $value) {
        update_post_meta($to_id, $key, $value);
    }
}
