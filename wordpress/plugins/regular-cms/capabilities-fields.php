<?php
/**
 * Campos editáveis do CPT capabilities (headline + seções repetíveis).
 */

if (defined('RS_CAPABILITIES_FIELDS_LOADED')) {
    return;
}
define('RS_CAPABILITIES_FIELDS_LOADED', true);

const RS_CAPABILITIES_HEADLINE_KEY = 'rs_capabilities_headline';
const RS_CAPABILITIES_SECTIONS_KEY = 'rs_capabilities_sections';
const RS_CAPABILITIES_LEGACY_SECTION_COUNT = 8;

/**
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_capabilities_get_sections(int $post_id): array {
    $decoded = function_exists('rs_meta_get_array')
        ? rs_meta_get_array($post_id, RS_CAPABILITIES_SECTIONS_KEY)
        : null;

    if (is_array($decoded)) {
        return rs_capabilities_normalize_sections($decoded);
    }

    return rs_capabilities_migrate_legacy_sections($post_id);
}

/**
 * @param array<int, mixed> $sections
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_capabilities_normalize_sections(array $sections): array {
    $normalized = [];

    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }

        $title = trim((string) ($section['title'] ?? ''));
        $text = trim((string) ($section['text'] ?? ''));
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
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_capabilities_migrate_legacy_sections(int $post_id): array {
    $sections = [];

    for ($i = 1; $i <= RS_CAPABILITIES_LEGACY_SECTION_COUNT; $i++) {
        $title = trim((string) get_post_meta($post_id, "rs_cap_sec_{$i}_title", true));
        if ($title === '') {
            continue;
        }

        $lead = trim((string) get_post_meta($post_id, "rs_cap_sec_{$i}_lead", true));
        $body = trim((string) get_post_meta($post_id, "rs_cap_sec_{$i}_body", true));
        $services_title = trim((string) get_post_meta($post_id, "rs_cap_sec_{$i}_services_title", true));
        $services_raw = trim((string) get_post_meta($post_id, "rs_cap_sec_{$i}_services", true));

        $text = $body;
        if ($lead !== '') {
            $text = '<p>' . $lead . '</p>' . $text;
        }

        if ($services_raw !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $services_raw) ?: [];
            $items = array_filter(array_map('trim', $lines));
            if ($items) {
                $list_title = $services_title !== '' ? '<p><strong>' . esc_html($services_title) . '</strong></p>' : '';
                $text .= $list_title . '<ul>';
                foreach ($items as $item) {
                    $text .= '<li>' . esc_html($item) . '</li>';
                }
                $text .= '</ul>';
            }
        }

        $sections[] = [
            'title'    => $title,
            'text'     => $text,
            'image_id' => 0,
        ];
    }

    return $sections;
}

/**
 * @param array<int, array{title: string, text: string, image_id: int}> $sections
 */
function rs_capabilities_sections_to_payload(array $sections): array {
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

function rs_capabilities_meta_to_payload(int $post_id): array {
    return [
        'headline' => trim((string) get_post_meta($post_id, RS_CAPABILITIES_HEADLINE_KEY, true)),
        'sections' => rs_capabilities_sections_to_payload(rs_capabilities_get_sections($post_id)),
    ];
}

function rs_capabilities_get_post_id_by_locale(string $locale): int {
    $posts = get_posts([
        'post_type'      => 'capabilities',
        'post_status'    => 'publish',
        'name'           => $locale,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

function rs_capabilities_ensure_locale_posts(): void {
    if (get_option('rs_capabilities_posts_ensured_v1')) {
        return;
    }

    foreach (['en', 'pt'] as $locale) {
        if (rs_capabilities_get_post_id_by_locale($locale) > 0) {
            continue;
        }

        wp_insert_post([
            'post_title'  => $locale === 'pt' ? 'Capacidades (PT)' : 'Capabilities (EN)',
            'post_status' => 'publish',
            'post_type'   => 'capabilities',
            'post_name'   => $locale,
            'post_author' => 1,
        ], true);
    }

    update_option('rs_capabilities_posts_ensured_v1', 1);
}

add_action('init', function () {
    register_post_meta('capabilities', RS_CAPABILITIES_HEADLINE_KEY, [
        'single'        => true,
        'type'          => 'string',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('capabilities', RS_CAPABILITIES_SECTIONS_KEY, [
        'single'        => true,
        'type'          => 'array',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
}, 20);

add_action('init', 'rs_capabilities_ensure_locale_posts', 25);

add_action('rest_api_init', function () {
    register_rest_field('capabilities', 'capabilities_data', [
        'get_callback' => function (array $post) {
            return rs_capabilities_meta_to_payload((int) $post['id']);
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

function rs_capabilities_render_section_row(int $index, array $section, bool $is_template = false): void {
    $title = $section['title'] ?? '';
    $text = $section['text'] ?? '';
    $image_id = (int) ($section['image_id'] ?? 0);
    $name_prefix = $is_template ? 'rs_cap_sections[__INDEX__]' : 'rs_cap_sections[' . $index . ']';
    $image_field_id = $is_template ? 'rs_cap_image___INDEX__' : 'rs_cap_image_' . $index;
    $editor_id = $is_template ? 'rs_cap_section_text___INDEX__' : 'rs_cap_section_text_' . $index;
    $display = $is_template ? ' style="display:none;"' : '';
    $is_open = !$is_template && (int) $index === 0;
    $head_title = $title !== '' ? $title : 'Seção';
    $row_class = 'rs-metabox-accordion-item' . ($is_open ? ' is-open' : '');
    $editor_ids = $is_template ? '' : esc_attr($editor_id);
    ?>
    <fieldset
        class="<?php echo esc_attr($row_class); ?>"
        data-index="<?php echo esc_attr($is_template ? '__INDEX__' : (string) $index); ?>"
        <?php echo $editor_ids !== '' ? ' data-rs-editor-ids="' . $editor_ids . '"' : ''; ?>
        <?php echo $display; ?>
    >
        <div class="rs-metabox-accordion-head">
            <span class="rs-metabox-accordion-drag" title="Arrastar para reordenar" aria-hidden="true">⋮⋮</span>
            <button type="button" class="rs-metabox-accordion-toggle" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
                <span class="rs-metabox-accordion-head-title"><?php echo esc_html($head_title); ?></span>
            </button>
            <button type="button" class="button-link-delete rs-metabox-accordion-remove rs-cap-remove-section">Remover</button>
        </div>
        <div class="rs-metabox-accordion-panel">
            <div style="margin:0 0 12px;">
                <label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>
                <input
                    type="text"
                    style="width:100%;"
                    class="rs-metabox-accordion-title rs-cap-section-title"
                    <?php if (!$is_template) : ?>
                        name="<?php echo esc_attr($name_prefix); ?>[title]"
                        value="<?php echo esc_attr(wp_strip_all_tags($title)); ?>"
                    <?php endif; ?>
                    placeholder="Ex: BRANDING &amp; VISUAL SYSTEMS"
                />
            </div>

            <div style="margin:0 0 12px;">
                <label style="display:block;font-weight:500;margin-bottom:4px;">Texto</label>
                <?php if ($is_template) : ?>
                    <textarea
                        class="rs-cap-section-text large-text"
                        style="width:100%;min-height:120px;"
                        id="<?php echo esc_attr($editor_id); ?>"
                    ></textarea>
                <?php else : ?>
                    <?php rs_render_rich_text_field($editor_id, $name_prefix . '[text]', $text, 'paragraph'); ?>
                <?php endif; ?>
            </div>

            <?php
            rs_render_media_field(
                $name_prefix . '[image_id]',
                'Imagem',
                $image_id,
                $image_field_id,
                !$is_template,
            );
            ?>
        </div>
    </fieldset>
    <?php
}

function rs_capabilities_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_capabilities_save', 'rs_capabilities_nonce');

    $post_id = (int) $post->ID;
    $headline = (string) get_post_meta($post_id, RS_CAPABILITIES_HEADLINE_KEY, true);
    $sections = rs_capabilities_get_sections($post_id);

    // JSON legado (ou corrompido) → array serializado.
    $raw = get_post_meta($post_id, RS_CAPABILITIES_SECTIONS_KEY, true);
    if (is_string($raw) && $raw !== '' && function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, RS_CAPABILITIES_SECTIONS_KEY, $sections);
    }

    if (!$sections) {
        $sections = [['title' => '', 'text' => '', 'image_id' => 0]];
    }

    echo '<p style="margin-top:0;color:#646970;">Um post por idioma (slug <code>en</code> / <code>pt</code>). Headline no topo; seções no acordeão abaixo.</p>';
    if (function_exists('rs_sync_media_notice_html')) {
        echo rs_sync_media_notice_html((int) $post->ID);
    }

    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Headline</strong></legend>';
    rs_render_rich_text_field(
        RS_CAPABILITIES_HEADLINE_KEY,
        RS_CAPABILITIES_HEADLINE_KEY,
        $headline,
        'inline',
    );
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Use o botão <strong>B</strong> para destacar palavras.</p>';
    echo '</fieldset>';

    echo '<div data-rs-accordion>';
    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Seções</strong></legend>';
    echo '<div id="rs-cap-sections-list" data-rs-accordion-list>';
    foreach ($sections as $index => $section) {
        rs_capabilities_render_section_row((int) $index, $section);
    }
    echo '</div>';
    echo '<div id="rs-cap-section-template" hidden>';
    rs_capabilities_render_section_row(0, ['title' => '', 'text' => '', 'image_id' => 0], true);
    echo '</div>';
    echo '<p style="margin:12px 0 0;">';
    echo '<button type="button" class="button button-secondary" id="rs-cap-add-section">+ Adicionar seção</button>';
    echo '</p>';
    echo '<input type="hidden" id="rs-cap-sections-json" name="rs_cap_sections_json" value="" />';
    echo '</fieldset>';
    echo '</div>';
}

/**
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_capabilities_parse_sections_from_request(): array {
    $sections = [];

    if (!empty($_POST['rs_cap_sections_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_cap_sections_json']), true);
        if (is_array($decoded) && $decoded !== []) {
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

            if ($sections !== []) {
                return $sections;
            }
        }
    }

    if (!isset($_POST['rs_cap_sections']) || !is_array($_POST['rs_cap_sections'])) {
        return [];
    }

    foreach (wp_unslash($_POST['rs_cap_sections']) as $key => $section) {
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

add_action('save_post_capabilities', function (int $post_id) {
    if (!isset($_POST['rs_capabilities_nonce']) || !wp_verify_nonce($_POST['rs_capabilities_nonce'], 'rs_capabilities_save')) {
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

    $headline = isset($_POST[RS_CAPABILITIES_HEADLINE_KEY])
        ? wp_kses_post(wp_unslash($_POST[RS_CAPABILITIES_HEADLINE_KEY]))
        : '';
    update_post_meta($post_id, RS_CAPABILITIES_HEADLINE_KEY, $headline);

    $sections = rs_capabilities_parse_sections_from_request();
    if (function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, RS_CAPABILITIES_SECTIONS_KEY, $sections);
    } else {
        update_post_meta($post_id, RS_CAPABILITIES_SECTIONS_KEY, wp_slash(wp_json_encode($sections, JSON_UNESCAPED_UNICODE) ?: '[]'));
    }
}, 10);

function rs_copy_capabilities_fields(int $from_id, int $to_id): void {
    update_post_meta($to_id, RS_CAPABILITIES_HEADLINE_KEY, get_post_meta($from_id, RS_CAPABILITIES_HEADLINE_KEY, true));
    update_post_meta($to_id, RS_CAPABILITIES_SECTIONS_KEY, get_post_meta($from_id, RS_CAPABILITIES_SECTIONS_KEY, true));
}

rs_enqueue_admin_media_picker(['capabilities']);

function rs_capabilities_render_admin_footer_script(): void {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'capabilities') {
        return;
    }
    ?>
    <script>
    jQuery(function ($) {
        const paragraphEditorSettings = <?php echo wp_json_encode(rs_rich_text_js_settings('paragraph')); ?>;
        let nextIndex = $('#rs-cap-sections-list .rs-metabox-accordion-item').length;
        const $accordionRoot = document.querySelector('[data-rs-accordion]');

        function syncAllEditors() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            if (typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
                wp.editor.save('<?php echo esc_js(RS_CAPABILITIES_HEADLINE_KEY); ?>');
            }
            $('textarea[id^="rs_cap_section_text_"]').each(function () {
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
                if (editor && !editor.isHidden()) {
                    return editor.getContent();
                }
            }
            return textarea.val() || '';
        }

        function collectSectionsJson() {
            syncAllEditors();
            const sections = [];
            $('#rs-cap-sections-list .rs-metabox-accordion-item').each(function () {
                const section = $(this);
                const title = (section.find('.rs-cap-section-title').val() || '').trim();
                const text = readSectionText(section.find('textarea[id^="rs_cap_section_text_"]'));
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
            $('#rs-cap-sections-json').val(JSON.stringify(sections));
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
            section.find('.rs-cap-section-title').attr('name', 'rs_cap_sections[' + index + '][title]');
            section.find('textarea[id^="rs_cap_section_text_"]').attr('name', 'rs_cap_sections[' + index + '][text]');
            section.find('input[data-rs-cap-image]').attr('name', 'rs_cap_sections[' + index + '][image_id]');
            const editorId = 'rs_cap_section_text_' + index;
            section.attr('data-rs-editor-ids', editorId);
        }

        function reindexSections() {
            $('#rs-cap-sections-list .rs-metabox-accordion-item').each(function (i) {
                $(this).attr('data-index', String(i));
                assignSectionNames($(this), i);
                $(this).find('[id^="rs_cap_image_"]').each(function () {
                    const oldId = $(this).attr('id');
                    const newId = 'rs_cap_image_' + i;
                    $(this).attr('id', newId);
                    $(this).closest('.rs-media-field').find('[data-target="' + oldId + '"]').attr('data-target', newId);
                });
                const textarea = $(this).find('textarea[id^="rs_cap_section_text_"]');
                const oldEditorId = textarea.attr('id');
                const newEditorId = 'rs_cap_section_text_' + i;
                if (oldEditorId && oldEditorId !== newEditorId) {
                    removeEditor(oldEditorId);
                    textarea.attr('id', newEditorId);
                    initEditor(newEditorId);
                }
            });
            nextIndex = $('#rs-cap-sections-list .rs-metabox-accordion-item').length;
        }

        let accordionApi = null;
        if ($accordionRoot && window.RsMetaboxUi) {
            accordionApi = window.RsMetaboxUi.initAccordion($accordionRoot, {
                defaultTitle: 'Seção',
                onExpand: function ($item, editorIds) {
                    window.RsMetaboxUi.resizeEditors(editorIds);
                },
                onRemove: function (event, $section) {
                    event.preventDefault();
                    if ($('#rs-cap-sections-list .rs-metabox-accordion-item').length <= 1) {
                        window.alert('Mantenha pelo menos uma seção.');
                        return;
                    }
                    const editorId = $section.find('textarea[id^="rs_cap_section_text_"]').attr('id');
                    removeEditor(editorId);
                    $section.remove();
                    reindexSections();
                },
                onSortUpdate: function () {
                    reindexSections();
                },
            });
        }

        $('#rs-cap-add-section').on('click', function (event) {
            event.preventDefault();
            const template = $('#rs-cap-section-template .rs-metabox-accordion-item').first().clone();
            template.removeAttr('style').removeClass('is-open');
            template.attr('data-index', String(nextIndex));
            template.find('.rs-cap-section-title').val('');
            template.find('textarea').val('');
            template.find('.rs-metabox-accordion-head-title').text('Seção');
            template.find('.rs-metabox-accordion-toggle').attr('aria-expanded', 'false');
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
            $('#rs-cap-sections-list').append(template);
            initEditor('rs_cap_section_text_' + nextIndex);
            if (accordionApi) {
                accordionApi.openItem(template);
            }
            nextIndex += 1;
        });

        // Coleta antes do envio (form + botões WP; evita JSON vazio se TinyMCE ainda não sincronizou).
        $('#post').on('submit', function () {
            collectSectionsJson();
        });
        $(document).on('click', '#publish, #save-post', function () {
            collectSectionsJson();
        });
        if (typeof tinymce !== 'undefined') {
            $(document).on('tinymce-editor-init', function () {
                // noop — garante listeners após init
            });
        }
    });
    </script>
    <?php
}

add_action('admin_footer-post.php', 'rs_capabilities_render_admin_footer_script');
add_action('admin_footer-post-new.php', 'rs_capabilities_render_admin_footer_script');
