<?php
/**
 * Campos editáveis do CPT project (meta box + REST API).
 */

if (defined('RS_PROJECT_FIELDS_LOADED')) {
    return;
}
define('RS_PROJECT_FIELDS_LOADED', true);

const RS_PROJECT_ACCORDION_LABELS = [
    1 => 'CONTEXTO / CONTEXT',
    2 => 'DIREÇÃO CRIATIVA / CREATIVE DIRECTION',
    3 => 'SOLUÇÃO / SOLUTION',
    4 => 'IMPACTO / IMPACT',
];

const RS_PROJECT_GALLERY_SLOTS = 8;

function rs_project_accordion_meta_keys(): array {
    $keys = [];
    foreach (array_keys(RS_PROJECT_ACCORDION_LABELS) as $index) {
        $keys["rs_project_acc_{$index}_body"] = RS_PROJECT_ACCORDION_LABELS[$index];
    }
    return $keys;
}

function rs_project_get_hero_id(int $post_id): int {
    return (int) get_post_meta($post_id, 'etc_upload_image', true);
}

function rs_project_get_logo_id(int $post_id): int {
    $custom = (int) get_post_meta($post_id, 'rs_project_logo_id', true);
    if ($custom > 0) {
        return $custom;
    }

    return (int) get_post_thumbnail_id($post_id);
}

function rs_project_get_gallery_ids(int $post_id): array {
    $raw = (string) get_post_meta($post_id, 'rs_project_gallery', true);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('intval', explode(',', $raw))));
}

function rs_project_attachment_info(int $attachment_id): ?array {
    if ($attachment_id <= 0) {
        return null;
    }

    $url = wp_get_attachment_url($attachment_id);
    if (!$url) {
        return null;
    }

    $meta = wp_get_attachment_metadata($attachment_id);

    return [
        'url'    => $url,
        'width'  => $meta['width'] ?? 0,
        'height' => $meta['height'] ?? 0,
    ];
}

function rs_project_meta_to_payload(int $post_id): array {
    $accordion = [];

    foreach (RS_PROJECT_ACCORDION_LABELS as $index => $label) {
        $body = (string) get_post_meta($post_id, "rs_project_acc_{$index}_body", true);
        if ($body === '') {
            continue;
        }

        $accordion[] = [
            'index' => $index,
            'body'  => wpautop($body),
        ];
    }

    $gallery = [];
    foreach (rs_project_get_gallery_ids($post_id) as $attachment_id) {
        $info = rs_project_attachment_info($attachment_id);
        if ($info && !empty($info['url'])) {
            $gallery[] = $info['url'];
        }
    }

    return [
        'heroImage' => rs_project_attachment_info(rs_project_get_hero_id($post_id)),
        'logoImage' => rs_project_attachment_info(rs_project_get_logo_id($post_id)),
        'accordion' => $accordion,
        'gallery'   => $gallery,
    ];
}

function rs_copy_project_fields(int $from_id, int $to_id): void {
    $hero_id = rs_project_get_hero_id($from_id);
    if ($hero_id > 0) {
        update_post_meta($to_id, 'etc_upload_image', $hero_id);
    } else {
        delete_post_meta($to_id, 'etc_upload_image');
    }

    $logo_id = (int) get_post_meta($from_id, 'rs_project_logo_id', true);
    if ($logo_id > 0) {
        update_post_meta($to_id, 'rs_project_logo_id', $logo_id);
        set_post_thumbnail($to_id, $logo_id);
    } else {
        delete_post_meta($to_id, 'rs_project_logo_id');
    }

    foreach (array_keys(RS_PROJECT_ACCORDION_LABELS) as $index) {
        $key = "rs_project_acc_{$index}_body";
        update_post_meta($to_id, $key, get_post_meta($from_id, $key, true));
    }

    update_post_meta($to_id, 'rs_project_gallery', get_post_meta($from_id, 'rs_project_gallery', true));
}

add_action('init', function () {
    register_post_meta('project', 'rs_project_logo_id', [
        'single'        => true,
        'type'          => 'integer',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('project', 'rs_project_gallery', [
        'single'        => true,
        'type'          => 'string',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);

    foreach (array_keys(RS_PROJECT_ACCORDION_LABELS) as $index) {
        register_post_meta('project', "rs_project_acc_{$index}_body", [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
});

add_action('rest_api_init', function () {
    register_rest_field('project', 'project_data', [
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

            return rs_project_meta_to_payload($post_id);
        },
        'schema' => [
            'description' => 'Dados estruturados do projeto',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

function rs_project_register_meta_box(): void {
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;

    add_meta_box(
        'rs_project_fields',
        'Conteúdo do Projeto (site)',
        'rs_project_render_meta_box',
        'project',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'project', 'normal');
}

add_action('add_meta_boxes_project', 'rs_project_register_meta_box', 5);

// Editor clássico para meta boxes com rich text (filtro central em rich-text-fields.php).

function rs_project_render_media_field(string $name, string $label, int $attachment_id): void {
    $url = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : '';
    ?>
    <p class="rs-project-media-field" style="margin:0 0 14px;">
        <label style="display:block;font-weight:500;margin-bottom:6px;"><?php echo esc_html($label); ?></label>
        <input type="hidden" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string) $attachment_id); ?>" />
        <button type="button" class="button rs-project-pick-media" data-target="<?php echo esc_attr($name); ?>">Selecionar imagem</button>
        <button type="button" class="button rs-project-clear-media" data-target="<?php echo esc_attr($name); ?>">Remover</button>
        <span class="rs-project-media-preview" data-target="<?php echo esc_attr($name); ?>" style="display:block;margin-top:8px;">
            <?php if ($url) : ?>
                <img src="<?php echo esc_url($url); ?>" alt="" style="max-width:220px;height:auto;border-radius:4px;" />
            <?php endif; ?>
        </span>
    </p>
    <?php
}

function rs_project_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_project_save', 'rs_project_nonce');

    $hero_id = rs_project_get_hero_id($post->ID);
    $logo_id = (int) get_post_meta($post->ID, 'rs_project_logo_id', true);
    if ($logo_id <= 0) {
        $logo_id = (int) get_post_thumbnail_id($post->ID);
    }

    $gallery_ids = rs_project_get_gallery_ids($post->ID);
    while (count($gallery_ids) < RS_PROJECT_GALLERY_SLOTS) {
        $gallery_ids[] = 0;
    }

    echo '<p style="margin-top:0;color:#646970;">Preencha aqui o que aparece na página do projeto. O <strong>resumo</strong> (texto à esquerda) continua no campo <em>Resumo</em> da barra lateral.</p>';

    echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Hero (topo)</strong></legend>';
    rs_project_render_media_field('rs_project_hero_id', 'Foto de fundo — imagem grande no topo', $hero_id);
    rs_project_render_media_field('rs_project_logo_id', 'Logo — imagem sobre a foto (canto inferior esquerdo)', $logo_id);
    echo '</fieldset>';

    echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Acordeão (direita)</strong></legend>';

    foreach (RS_PROJECT_ACCORDION_LABELS as $index => $label) {
        $key = 'rs_project_acc_' . $index . '_body';
        $value = (string) get_post_meta($post->ID, $key, true);
        echo '<p style="margin:0 0 12px;">';
        echo '<label for="' . esc_attr($key) . '" style="display:block;font-weight:500;margin-bottom:4px;">' . esc_html($label) . '</label>';
        rs_render_rich_text_field($key, $key, $value, 'paragraph');
        echo '</p>';
    }

    echo '</fieldset>';

    echo '<fieldset style="margin:0 0 10px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Galeria (fotos abaixo)</strong></legend>';

    for ($slot = 0; $slot < RS_PROJECT_GALLERY_SLOTS; $slot += 1) {
        $field_name = 'rs_project_gallery_' . ($slot + 1);
        $attachment_id = (int) ($gallery_ids[$slot] ?? 0);
        rs_project_render_media_field($field_name, 'Imagem ' . ($slot + 1), $attachment_id);
    }

    echo '</fieldset>';
}

add_action('save_post_project', function (int $post_id) {
    if (!isset($_POST['rs_project_nonce']) || !wp_verify_nonce($_POST['rs_project_nonce'], 'rs_project_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $hero_id = isset($_POST['rs_project_hero_id']) ? (int) $_POST['rs_project_hero_id'] : 0;
    if ($hero_id > 0) {
        update_post_meta($post_id, 'etc_upload_image', $hero_id);
    } else {
        delete_post_meta($post_id, 'etc_upload_image');
    }

    $logo_id = isset($_POST['rs_project_logo_id']) ? (int) $_POST['rs_project_logo_id'] : 0;
    if ($logo_id > 0) {
        update_post_meta($post_id, 'rs_project_logo_id', $logo_id);
        set_post_thumbnail($post_id, $logo_id);
    } else {
        delete_post_meta($post_id, 'rs_project_logo_id');
    }

    foreach (array_keys(RS_PROJECT_ACCORDION_LABELS) as $index) {
        $key = "rs_project_acc_{$index}_body";
        $value = isset($_POST[$key]) ? wp_kses_post(wp_unslash($_POST[$key])) : '';
        update_post_meta($post_id, $key, $value);
    }

    $gallery_ids = [];
    for ($slot = 1; $slot <= RS_PROJECT_GALLERY_SLOTS; $slot += 1) {
        $field_name = 'rs_project_gallery_' . $slot;
        $attachment_id = isset($_POST[$field_name]) ? (int) $_POST[$field_name] : 0;
        if ($attachment_id > 0) {
            $gallery_ids[] = $attachment_id;
        }
    }

    update_post_meta($post_id, 'rs_project_gallery', implode(',', $gallery_ids));
});

add_action('admin_enqueue_scripts', function (string $hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'project') {
        return;
    }

    wp_enqueue_media();

    wp_add_inline_script('jquery', <<<'JS'
jQuery(function ($) {
    function setPreview(target, attachment) {
        const preview = $('.rs-project-media-preview[data-target="' + target + '"]');
        if (!attachment || !attachment.url) {
            preview.empty();
            return;
        }
        preview.html('<img src="' + attachment.url + '" alt="" style="max-width:220px;height:auto;border-radius:4px;" />');
    }

    $(document).on('click', '.rs-project-pick-media', function (event) {
        event.preventDefault();
        const target = $(this).data('target');
        const input = $('#' + target);
        const frame = wp.media({
            title: 'Selecionar imagem',
            button: { text: 'Usar esta imagem' },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.id);
            setPreview(target, attachment);
        });

        frame.open();
    });

    $(document).on('click', '.rs-project-clear-media', function (event) {
        event.preventDefault();
        const target = $(this).data('target');
        $('#' + target).val('');
        setPreview(target, null);
    });
});
JS
    );
});