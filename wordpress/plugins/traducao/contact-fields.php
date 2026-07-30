<?php
/**
 * Campos editáveis do CPT contact (Contato).
 */

if (defined('RS_CONTACT_FIELDS_LOADED')) {
    return;
}
define('RS_CONTACT_FIELDS_LOADED', true);

const RS_CONTACT_HERO_IMAGE_KEY = 'rs_contact_hero_image_id';
const RS_CONTACT_HERO_VIDEO_KEY = 'rs_contact_hero_video_id';
const RS_CONTACT_HEADLINE_KEY = 'rs_contact_headline';
const RS_CONTACT_BLOCKS_KEY = 'rs_contact_blocks';

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_contact_get_blocks(int $post_id): array {
    $raw = get_post_meta($post_id, RS_CONTACT_BLOCKS_KEY, true);

    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return rs_contact_normalize_blocks($decoded);
        }
    }

    if (is_array($raw)) {
        return rs_contact_normalize_blocks($raw);
    }

    return [];
}

/**
 * @param array<int, mixed> $blocks
 * @return array<int, array{title: string, body: string}>
 */
function rs_contact_normalize_blocks(array $blocks): array {
    $normalized = [];

    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }

        $title = trim((string) ($block['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $normalized[] = [
            'title' => $title,
            'body'  => trim((string) ($block['body'] ?? $block['text'] ?? '')),
        ];
    }

    return $normalized;
}

function rs_contact_meta_to_payload(int $post_id): array {
    $hero = function_exists('rs_section_hero_media')
        ? rs_section_hero_media($post_id, RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY, 'contact')
        : ['image' => '', 'video' => ''];

    return [
        'heroImage' => $hero['image'],
        'heroVideo' => $hero['video'],
        'headline'  => trim((string) get_post_meta($post_id, RS_CONTACT_HEADLINE_KEY, true)),
        'blocks'    => rs_contact_get_blocks($post_id),
    ];
}

function rs_contact_get_post_id_by_locale(string $locale): int {
    $posts = get_posts([
        'post_type'      => 'contact',
        'post_status'    => 'publish',
        'name'           => $locale,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

function rs_contact_ensure_locale_posts(): void {
    if (get_option('rs_contact_posts_ensured_v1')) {
        return;
    }

    foreach (['en', 'pt'] as $locale) {
        if (rs_contact_get_post_id_by_locale($locale) > 0) {
            continue;
        }

        wp_insert_post([
            'post_title'  => $locale === 'pt' ? 'Contato (PT)' : 'Contact (EN)',
            'post_status' => 'publish',
            'post_type'   => 'contact',
            'post_name'   => $locale,
            'post_author' => 1,
        ], true);
    }

    update_option('rs_contact_posts_ensured_v1', 1);
}

add_action('init', function () {
    foreach ([RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY, RS_CONTACT_HEADLINE_KEY, RS_CONTACT_BLOCKS_KEY] as $key) {
        register_post_meta('contact', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);

add_action('init', 'rs_contact_ensure_locale_posts', 25);

add_action('rest_api_init', function () {
    register_rest_field('contact', 'contact_data', [
        'get_callback' => function (array $post) {
            return rs_contact_meta_to_payload((int) $post['id']);
        },
        'schema' => [
            'description' => 'Dados estruturados da página Contato',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_contact', function () {
    add_meta_box(
        'rs_contact_fields',
        'Conteúdo da página Contato',
        'rs_contact_render_meta_box',
        'contact',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'contact', 'normal');
}, 10);

function rs_contact_render_block_row(int $index, array $block, bool $is_template = false): void {
    $title = $block['title'] ?? '';
    $body = $block['body'] ?? '';
    $editor_id = $is_template ? 'rs_contact_block_body___INDEX__' : 'rs_contact_block_body_' . $index;
    $name_prefix = $is_template ? 'rs_contact_blocks[__INDEX__]' : 'rs_contact_blocks[' . $index . ']';
    $display = $is_template ? ' style="display:none;"' : '';
    ?>
    <fieldset class="rs-contact-block" data-index="<?php echo esc_attr($is_template ? '__INDEX__' : (string) $index); ?>"<?php echo $display; ?>>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
            <legend style="font-weight:600;padding:0;margin:0;"><strong>Bloco</strong></legend>
            <button type="button" class="button-link-delete rs-contact-remove-block">Remover</button>
        </div>

        <div style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>
            <input
                type="text"
                style="width:100%;"
                class="rs-contact-block-title"
                <?php if (!$is_template) : ?>
                    name="<?php echo esc_attr($name_prefix); ?>[title]"
                    value="<?php echo esc_attr(wp_strip_all_tags($title)); ?>"
                <?php endif; ?>
            />
        </div>

        <p style="margin:0;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Conteúdo</label>
            <?php
            if ($is_template) {
                echo '<textarea class="rs-contact-block-body" style="width:100%;min-height:120px;" id="' . esc_attr($editor_id) . '"></textarea>';
            } else {
                rs_render_rich_text_field($editor_id, $name_prefix . '[body]', $body, 'paragraph');
            }
            ?>
        </p>
    </fieldset>
    <?php
}

function rs_contact_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_contact_save', 'rs_contact_nonce');

    $headline = (string) get_post_meta($post->ID, RS_CONTACT_HEADLINE_KEY, true);
    $blocks = rs_contact_get_blocks($post->ID);

    if (!$blocks) {
        $blocks = [['title' => '', 'body' => '']];
    }

    echo '<p style="margin-top:0;color:#646970;">Um post por idioma (slug <code>en</code> / <code>pt</code>). Campos vazios usam o fallback do Next.js. Sem imagem/vídeo no Hero, a página Contato não exibe o topo.</p>';

    if (function_exists('rs_section_render_hero_fields')) {
        rs_section_render_hero_fields($post->ID, RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY);
    }

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Headline</strong></legend>';
    rs_render_rich_text_field(RS_CONTACT_HEADLINE_KEY, RS_CONTACT_HEADLINE_KEY, $headline, 'inline');
    echo '</fieldset>';

    echo '<div id="rs-contact-blocks-list">';
    foreach ($blocks as $index => $block) {
        rs_contact_render_block_row((int) $index, $block);
    }
    echo '</div>';

    rs_contact_render_block_row(0, ['title' => '', 'body' => ''], true);

    echo '<p style="margin:16px 0 0;">';
    echo '<button type="button" class="button button-secondary" id="rs-contact-add-block">+ Adicionar bloco</button>';
    echo '</p>';

    echo '<input type="hidden" id="rs-contact-blocks-json" name="rs_contact_blocks_json" value="" />';
}

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_contact_parse_blocks_from_request(): array {
    $blocks = [];

    if (!empty($_POST['rs_contact_blocks_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_contact_blocks_json']), true);
        if (is_array($decoded)) {
            foreach ($decoded as $block) {
                if (!is_array($block)) {
                    continue;
                }

                $title = trim(wp_strip_all_tags((string) ($block['title'] ?? '')));
                $body = wp_kses_post((string) ($block['body'] ?? ''));

                if ($title === '' && $body === '') {
                    continue;
                }

                $blocks[] = [
                    'title' => $title !== '' ? $title : 'Bloco',
                    'body'  => $body,
                ];
            }
        }
    }

    return $blocks;
}

add_action('save_post_contact', function (int $post_id) {
    if (!isset($_POST['rs_contact_nonce']) || !wp_verify_nonce($_POST['rs_contact_nonce'], 'rs_contact_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $headline = isset($_POST[RS_CONTACT_HEADLINE_KEY])
        ? wp_kses_post(wp_unslash($_POST[RS_CONTACT_HEADLINE_KEY]))
        : '';
    update_post_meta($post_id, RS_CONTACT_HEADLINE_KEY, $headline);

    if (function_exists('rs_section_save_hero_media')) {
        rs_section_save_hero_media($post_id, RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY);
    }

    $blocks = rs_contact_parse_blocks_from_request();
    update_post_meta($post_id, RS_CONTACT_BLOCKS_KEY, wp_json_encode($blocks, JSON_UNESCAPED_UNICODE));
});

function rs_copy_contact_fields(int $from_id, int $to_id): void {
    update_post_meta($to_id, RS_CONTACT_HEADLINE_KEY, get_post_meta($from_id, RS_CONTACT_HEADLINE_KEY, true));
    update_post_meta($to_id, RS_CONTACT_BLOCKS_KEY, get_post_meta($from_id, RS_CONTACT_BLOCKS_KEY, true));
    if (function_exists('rs_section_copy_hero_media')) {
        rs_section_copy_hero_media($from_id, $to_id, RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY);
    }
}

rs_enqueue_admin_media_picker(['contact']);

add_action('admin_enqueue_scripts', function (string $hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'contact') {
        return;
    }

    $paragraph_settings = wp_json_encode(rs_rich_text_js_settings('paragraph'));

    wp_add_inline_script('editor', <<<JS
jQuery(function ($) {
    const paragraphEditorSettings = {$paragraph_settings};
    let nextIndex = $('#rs-contact-blocks-list .rs-contact-block').length;

    function syncAllEditors() {
        if (typeof tinymce !== 'undefined') tinymce.triggerSave();
        if (typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
            $('textarea[id^="rs_contact_block_body_"]').each(function () {
                const id = $(this).attr('id');
                if (id) wp.editor.save(id);
            });
        }
    }

    function readBlockBody(textarea) {
        const editorId = textarea.attr('id');
        if (editorId && typeof tinymce !== 'undefined') {
            const editor = tinymce.get(editorId);
            if (editor) return editor.getContent();
        }
        return textarea.val() || '';
    }

    function collectBlocksJson() {
        syncAllEditors();
        const blocks = [];
        $('#rs-contact-blocks-list .rs-contact-block').each(function () {
            const block = $(this);
            const title = (block.find('.rs-contact-block-title').val() || '').trim();
            const body = readBlockBody(block.find('textarea.rs-contact-block-body, textarea[id^="rs_contact_block_body_"]'));
            if (!title && !body) return;
            blocks.push({ title: title || 'Bloco', body });
        });
        $('#rs-contact-blocks-json').val(JSON.stringify(blocks));
    }

    $('#post').on('submit', collectBlocksJson);

    function initEditor(id) {
        if (typeof wp === 'undefined' || !wp.editor) return;
        wp.editor.initialize(id, paragraphEditorSettings);
    }

    function assignBlockNames(block, index) {
        block.attr('data-index', String(index));
        block.find('.rs-contact-block-title').attr('name', 'rs_contact_blocks[' + index + '][title]');
        block.find('textarea').attr('name', 'rs_contact_blocks[' + index + '][body]');
    }

    $('#rs-contact-add-block').on('click', function (event) {
        event.preventDefault();
        const template = $('.rs-contact-block[data-index="__INDEX__"]').first().clone();
        template.attr('data-index', String(nextIndex)).show();
        template.find('.rs-contact-block-title').val('');
        template.find('textarea').val('');
        template.find('[id]').each(function () {
            const id = $(this).attr('id');
            if (id && id.indexOf('__INDEX__') !== -1) {
                $(this).attr('id', id.replace(/__INDEX__/g, String(nextIndex)));
            }
        });
        assignBlockNames(template, nextIndex);
        $('#rs-contact-blocks-list').append(template);
        initEditor('rs_contact_block_body_' + nextIndex);
        nextIndex += 1;
    });

    $(document).on('click', '.rs-contact-remove-block', function (event) {
        event.preventDefault();
        if ($('#rs-contact-blocks-list .rs-contact-block').length <= 1) {
            window.alert('Mantenha pelo menos um bloco.');
            return;
        }
        const block = $(this).closest('.rs-contact-block');
        const editorId = block.find('textarea[id^="rs_contact_block_body_"]').attr('id');
        if (editorId && typeof wp !== 'undefined' && wp.editor) wp.editor.remove(editorId);
        block.remove();
    });
});
JS
    );
}, 20);
