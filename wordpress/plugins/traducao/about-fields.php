<?php
/**
 * Campos editáveis do CPT about (Sobre Nós).
 */

if (defined('RS_ABOUT_FIELDS_LOADED')) {
    return;
}
define('RS_ABOUT_FIELDS_LOADED', true);

const RS_ABOUT_HERO_IMAGE_KEY = 'rs_about_hero_image_id';
const RS_ABOUT_HEADLINE_KEY = 'rs_about_headline';
const RS_ABOUT_BODY_KEY = 'rs_about_body';
const RS_ABOUT_SECTIONS_KEY = 'rs_about_sections';

/**
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_about_get_sections(int $post_id): array {
    $raw = get_post_meta($post_id, RS_ABOUT_SECTIONS_KEY, true);

    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return rs_about_normalize_sections($decoded);
        }
    }

    if (is_array($raw)) {
        return rs_about_normalize_sections($raw);
    }

    return [];
}

/**
 * @param array<int, mixed> $sections
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_about_normalize_sections(array $sections): array {
    $normalized = [];

    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }

        $title = trim((string) ($section['title'] ?? ''));
        $text = trim((string) ($section['text'] ?? $section['body'] ?? ''));
        $image_id = (int) ($section['image_id'] ?? 0);

        if ($title === '' && $text === '' && $image_id <= 0) {
            continue;
        }

        $normalized[] = [
            'title'    => $title !== '' ? $title : 'Seção',
            'text'     => $text,
            'image_id' => $image_id,
        ];
    }

    return $normalized;
}

/**
 * @param array<int, array{title: string, text: string, image_id: int}> $sections
 */
function rs_about_sections_to_payload(array $sections): array {
    $payload = [];

    foreach ($sections as $section) {
        $image_id = (int) ($section['image_id'] ?? 0);
        $image_url = $image_id > 0 ? (string) wp_get_attachment_url($image_id) : '';

        $payload[] = [
            'title' => trim($section['title']),
            'body'  => $section['text'],
            'image' => $image_url,
        ];
    }

    return $payload;
}

function rs_about_meta_to_payload(int $post_id): array {
    $hero_url = function_exists('rs_page_heroes_get_image_url')
        ? rs_page_heroes_get_image_url('about', $post_id)
        : '';

    return [
        'heroImage'          => $hero_url,
        'headline'           => trim((string) get_post_meta($post_id, RS_ABOUT_HEADLINE_KEY, true)),
        'body'               => trim((string) get_post_meta($post_id, RS_ABOUT_BODY_KEY, true)),
        'accordionSections'  => rs_about_sections_to_payload(rs_about_get_sections($post_id)),
    ];
}

function rs_about_get_post_id_by_locale(string $locale): int {
    $posts = get_posts([
        'post_type'      => 'about',
        'post_status'    => 'publish',
        'name'           => $locale,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

function rs_about_ensure_locale_posts(): void {
    if (get_option('rs_about_posts_ensured_v1')) {
        return;
    }

    foreach (['en', 'pt'] as $locale) {
        if (rs_about_get_post_id_by_locale($locale) > 0) {
            continue;
        }

        wp_insert_post([
            'post_title'  => $locale === 'pt' ? 'Sobre Nós (PT)' : 'About Us (EN)',
            'post_status' => 'publish',
            'post_type'   => 'about',
            'post_name'   => $locale,
            'post_author' => 1,
        ], true);
    }

    update_option('rs_about_posts_ensured_v1', 1);
}

add_action('init', function () {
    foreach ([RS_ABOUT_HERO_IMAGE_KEY, RS_ABOUT_HEADLINE_KEY, RS_ABOUT_BODY_KEY, RS_ABOUT_SECTIONS_KEY] as $key) {
        register_post_meta('about', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);

add_action('init', 'rs_about_ensure_locale_posts', 25);

add_action('rest_api_init', function () {
    register_rest_field('about', 'about_data', [
        'get_callback' => function (array $post) {
            return rs_about_meta_to_payload((int) $post['id']);
        },
        'schema' => [
            'description' => 'Dados estruturados da página Sobre Nós',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_about', function () {
    add_meta_box(
        'rs_about_fields',
        'Conteúdo da página Sobre Nós',
        'rs_about_render_meta_box',
        'about',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'about', 'normal');
}, 10);

function rs_about_render_section_row(int $index, array $section, bool $is_template = false): void {
    $title = $section['title'] ?? '';
    $text = $section['text'] ?? '';
    $image_id = (int) ($section['image_id'] ?? 0);
    $name_prefix = $is_template ? 'rs_about_sections[__INDEX__]' : 'rs_about_sections[' . $index . ']';
    $image_field_id = $is_template ? 'rs_about_image___INDEX__' : 'rs_about_image_' . $index;
    $editor_id = $is_template ? 'rs_about_section_text___INDEX__' : 'rs_about_section_text_' . $index;
    $display = $is_template ? ' style="display:none;"' : '';
    ?>
    <fieldset class="rs-about-section" data-index="<?php echo esc_attr($is_template ? '__INDEX__' : (string) $index); ?>"<?php echo $display; ?>>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
            <legend style="font-weight:600;padding:0;margin:0;"><strong>Seção do acordeão</strong></legend>
            <button type="button" class="button-link-delete rs-about-remove-section">Remover</button>
        </div>

        <div style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>
            <input
                type="text"
                style="width:100%;"
                class="rs-about-section-title"
                <?php if (!$is_template) : ?>
                    name="<?php echo esc_attr($name_prefix); ?>[title]"
                    value="<?php echo esc_attr(wp_strip_all_tags($title)); ?>"
                <?php endif; ?>
            />
        </div>

        <div style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Texto</label>
            <?php if ($is_template) : ?>
                <textarea
                    class="rs-about-section-text large-text"
                    style="width:100%;min-height:120px;"
                    id="<?php echo esc_attr($editor_id); ?>"
                ></textarea>
            <?php else : ?>
                <?php rs_render_rich_text_field($editor_id, $name_prefix . '[text]', $text, 'paragraph'); ?>
            <?php endif; ?>
        </div>

        <?php rs_render_media_field($name_prefix . '[image_id]', 'Imagem lateral', $image_id, $image_field_id, !$is_template); ?>
    </fieldset>
    <?php
}

function rs_about_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_about_save', 'rs_about_nonce');

    $headline = (string) get_post_meta($post->ID, RS_ABOUT_HEADLINE_KEY, true);
    $body = (string) get_post_meta($post->ID, RS_ABOUT_BODY_KEY, true);
    $sections = rs_about_get_sections($post->ID);

    if (!$sections) {
        $sections = [['title' => '', 'text' => '', 'image_id' => 0]];
    }

    echo '<p style="margin-top:0;color:#646970;">Um post por idioma (slug <code>en</code> / <code>pt</code>). Campos vazios usam o fallback do Next.js. A <strong>imagem do hero</strong> é editada em <a href="' . esc_url(admin_url('admin.php?page=rs-page-heroes')) . '">Heroes das páginas</a>.</p>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Headline</strong></legend>';
    rs_render_rich_text_field(RS_ABOUT_HEADLINE_KEY, RS_ABOUT_HEADLINE_KEY, $headline, 'inline');
    echo '</fieldset>';

    echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Texto introdutório</strong></legend>';
    rs_render_rich_text_field(RS_ABOUT_BODY_KEY, RS_ABOUT_BODY_KEY, $body, 'paragraph');
    echo '</fieldset>';

    echo '<div id="rs-about-sections-list">';
    foreach ($sections as $index => $section) {
        rs_about_render_section_row((int) $index, $section);
    }
    echo '</div>';

    rs_about_render_section_row(0, ['title' => '', 'text' => '', 'image_id' => 0], true);

    echo '<p style="margin:16px 0 0;">';
    echo '<button type="button" class="button button-secondary" id="rs-about-add-section">+ Adicionar seção</button>';
    echo '</p>';

    echo '<input type="hidden" id="rs-about-sections-json" name="rs_about_sections_json" value="" />';
}

/**
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_about_parse_sections_from_request(): array {
    $sections = [];

    if (!empty($_POST['rs_about_sections_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_about_sections_json']), true);
        if (is_array($decoded)) {
            foreach ($decoded as $section) {
                if (!is_array($section)) {
                    continue;
                }

                $title = trim(wp_strip_all_tags((string) ($section['title'] ?? '')));
                $text = wp_kses_post((string) ($section['text'] ?? ''));
                $image_id = (int) ($section['image_id'] ?? 0);

                if ($title === '' && $text === '' && $image_id <= 0) {
                    continue;
                }

                $sections[] = [
                    'title'    => $title !== '' ? $title : 'Seção',
                    'text'     => $text,
                    'image_id' => $image_id,
                ];
            }

            return $sections;
        }
    }

    if (!isset($_POST['rs_about_sections']) || !is_array($_POST['rs_about_sections'])) {
        return [];
    }

    foreach (wp_unslash($_POST['rs_about_sections']) as $key => $section) {
        if ($key === '__INDEX__' || !is_array($section)) {
            continue;
        }

        $title = trim(wp_strip_all_tags((string) ($section['title'] ?? '')));
        $text = wp_kses_post((string) ($section['text'] ?? ''));
        $image_id = (int) ($section['image_id'] ?? 0);

        if ($title === '' && $text === '' && $image_id <= 0) {
            continue;
        }

        $sections[] = [
            'title'    => $title !== '' ? $title : 'Seção',
            'text'     => $text,
            'image_id' => $image_id,
        ];
    }

    return $sections;
}

add_action('save_post_about', function (int $post_id) {
    if (!isset($_POST['rs_about_nonce']) || !wp_verify_nonce($_POST['rs_about_nonce'], 'rs_about_save')) {
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

    $headline = isset($_POST[RS_ABOUT_HEADLINE_KEY])
        ? wp_kses_post(wp_unslash($_POST[RS_ABOUT_HEADLINE_KEY]))
        : '';
    update_post_meta($post_id, RS_ABOUT_HEADLINE_KEY, $headline);

    $body = isset($_POST[RS_ABOUT_BODY_KEY])
        ? wp_kses_post(wp_unslash($_POST[RS_ABOUT_BODY_KEY]))
        : '';
    update_post_meta($post_id, RS_ABOUT_BODY_KEY, $body);

    $sections = rs_about_parse_sections_from_request();
    update_post_meta($post_id, RS_ABOUT_SECTIONS_KEY, wp_json_encode($sections, JSON_UNESCAPED_UNICODE));
}, 10);

function rs_copy_about_fields(int $from_id, int $to_id): void {
    update_post_meta($to_id, RS_ABOUT_HEADLINE_KEY, get_post_meta($from_id, RS_ABOUT_HEADLINE_KEY, true));
    update_post_meta($to_id, RS_ABOUT_BODY_KEY, get_post_meta($from_id, RS_ABOUT_BODY_KEY, true));
    update_post_meta($to_id, RS_ABOUT_SECTIONS_KEY, get_post_meta($from_id, RS_ABOUT_SECTIONS_KEY, true));
}

rs_enqueue_admin_media_picker(['about']);

add_action('admin_footer-post.php', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'about') {
        return;
    }
    ?>
    <script>
    jQuery(function ($) {
        const paragraphEditorSettings = <?php echo wp_json_encode(rs_rich_text_js_settings('paragraph')); ?>;
        let nextIndex = $('#rs-about-sections-list .rs-about-section').length;

        function syncAllEditors() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            $('textarea[id^="rs_about_section_text_"]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') === -1 && typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
                    wp.editor.save(id);
                }
            });
        }

        function readSectionText(textarea) {
            const editorId = textarea.attr('id');
            if (editorId && typeof tinymce !== 'undefined') {
                const editor = tinymce.get(editorId);
                if (editor) {
                    return editor.getContent();
                }
            }
            return textarea.val() || '';
        }

        function collectSectionsJson() {
            syncAllEditors();
            const sections = [];
            $('#rs-about-sections-list .rs-about-section').each(function () {
                const section = $(this);
                const title = (section.find('.rs-about-section-title').val() || '').trim();
                const text = readSectionText(section.find('textarea[id^="rs_about_section_text_"]'));
                const imageId = parseInt(section.find('input[data-rs-cap-image]').val(), 10) || 0;
                if (!title && !text && !imageId) {
                    return;
                }
                sections.push({
                    title: title || 'Seção',
                    text,
                    image_id: imageId,
                });
            });
            $('#rs-about-sections-json').val(JSON.stringify(sections));
        }

        function initEditor(id) {
            if (!id || id.indexOf('__INDEX__') !== -1) {
                return;
            }
            if (typeof wp === 'undefined' || !wp.editor) {
                return;
            }
            if (typeof tinymce !== 'undefined' && tinymce.get(id)) {
                return;
            }
            wp.editor.initialize(id, paragraphEditorSettings);
        }

        function removeEditor(id) {
            if (!id || typeof wp === 'undefined' || !wp.editor) {
                return;
            }
            wp.editor.remove(id);
        }

        function assignSectionNames(section, index) {
            section.find('.rs-about-section-title').attr('name', 'rs_about_sections[' + index + '][title]');
            section.find('textarea[id^="rs_about_section_text_"]').attr('name', 'rs_about_sections[' + index + '][text]');
            section.find('input[data-rs-cap-image]').attr('name', 'rs_about_sections[' + index + '][image_id]');
        }

        function reindexSections() {
            $('#rs-about-sections-list .rs-about-section').each(function (i) {
                $(this).attr('data-index', String(i));
                assignSectionNames($(this), i);
                $(this).find('[id^="rs_about_image_"]').each(function () {
                    const oldId = $(this).attr('id');
                    const newId = 'rs_about_image_' + i;
                    $(this).attr('id', newId);
                    $(this).closest('.rs-media-field').find('[data-target="' + oldId + '"]').attr('data-target', newId);
                });
            });
            nextIndex = $('#rs-about-sections-list .rs-about-section').length;
        }

        $('#rs-about-add-section').on('click', function (event) {
            event.preventDefault();
            const template = $('.rs-about-section[data-index="__INDEX__"]').first().clone();
            template.removeAttr('style');
            template.attr('data-index', String(nextIndex));
            template.find('.rs-about-section-title').val('');
            template.find('textarea').val('');
            template.find('input[data-rs-cap-image]').val('0');
            template.find('.rs-media-preview').empty();
            template.find('[id]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') !== -1) {
                    $(this).attr('id', id.replace(/__INDEX__/g, String(nextIndex)));
                }
            });
            template.find('[data-target]').each(function () {
                const target = $(this).attr('data-target');
                if (target) {
                    $(this).attr('data-target', target.replace(/__INDEX__/g, String(nextIndex)));
                }
            });
            assignSectionNames(template, nextIndex);
            $('#rs-about-sections-list').append(template);
            initEditor('rs_about_section_text_' + nextIndex);
            nextIndex += 1;
        });

        $(document).on('click', '.rs-about-remove-section', function (event) {
            event.preventDefault();
            if ($('#rs-about-sections-list .rs-about-section').length <= 1) {
                window.alert('Mantenha pelo menos uma seção.');
                return;
            }
            const section = $(this).closest('.rs-about-section');
            const editorId = section.find('textarea[id^="rs_about_section_text_"]').attr('id');
            removeEditor(editorId);
            section.remove();
            reindexSections();
        });

        $('#post').on('submit', collectSectionsJson);
    });
    </script>
    <?php
});
