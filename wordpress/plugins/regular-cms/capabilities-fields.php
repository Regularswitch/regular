<?php
/**
 * CPT capabilities — post único bilíngue (EN + PT).
 */

if (defined('RS_CAPABILITIES_FIELDS_LOADED')) {
    return;
}
define('RS_CAPABILITIES_FIELDS_LOADED', true);

const RS_CAPABILITIES_I18N_KEY = 'rs_capabilities_i18n';
const RS_CAPABILITIES_HEADLINE_KEY = 'rs_capabilities_headline';
const RS_CAPABILITIES_SECTIONS_KEY = 'rs_capabilities_sections';
const RS_CAPABILITIES_LEGACY_SECTION_COUNT = 8;

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
 * Lê os campos legados rs_cap_sec_* de um post.
 *
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
 * @return array<int, array{title: string, body: string, image: string}>
 */
function rs_capabilities_sections_to_payload(array $sections): array {
    $payload = [];

    foreach ($sections as $section) {
        $image_id = (int) ($section['image_id'] ?? 0);
        $image_url = $image_id > 0 ? (string) wp_get_attachment_url($image_id) : '';

        $payload[] = [
            'title' => trim((string) ($section['title'] ?? '')),
            'body'  => (string) ($section['text'] ?? ''),
            'image' => $image_url,
        ];
    }

    return $payload;
}

/**
 * @return array{headline: string, sections: array<int, array{title: string, text: string, image_id: int}>}
 */
function rs_capabilities_default_locale(): array {
    return [
        'headline' => '',
        'sections' => [],
    ];
}

/**
 * @return array{v: int, shared: array, locales: array{en: array, pt: array}}
 */
function rs_capabilities_i18n_default(): array {
    return [
        'v'       => 1,
        'shared'  => [],
        'locales' => [
            'en' => rs_capabilities_default_locale(),
            'pt' => rs_capabilities_default_locale(),
        ],
    ];
}

/**
 * @param array<string, mixed> $raw
 * @return array{v: int, shared: array, locales: array{en: array, pt: array}}
 */
function rs_capabilities_i18n_normalize(array $raw): array {
    $data = rs_capabilities_i18n_default();

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $sections = is_array($loc['sections'] ?? null) ? $loc['sections'] : [];

        $data['locales'][$locale] = [
            'headline' => trim((string) ($loc['headline'] ?? '')),
            'sections' => rs_capabilities_normalize_sections($sections),
        ];
    }

    return $data;
}

/**
 * Lê headline e seções flat de um post legado.
 *
 * @return array{headline: string, sections: array<int, array{title: string, text: string, image_id: int}>}
 */
function rs_capabilities_locale_from_legacy_post(int $post_id): array {
    if ($post_id <= 0) {
        return rs_capabilities_default_locale();
    }

    $sections = function_exists('rs_meta_get_array')
        ? rs_meta_get_array($post_id, RS_CAPABILITIES_SECTIONS_KEY)
        : get_post_meta($post_id, RS_CAPABILITIES_SECTIONS_KEY, true);

    if (!is_array($sections)) {
        $sections = rs_capabilities_migrate_legacy_sections($post_id);
    }

    return [
        'headline' => trim((string) get_post_meta($post_id, RS_CAPABILITIES_HEADLINE_KEY, true)),
        'sections' => rs_capabilities_normalize_sections($sections),
    ];
}

/**
 * @return array{v: int, shared: array, locales: array{en: array, pt: array}}
 */
function rs_capabilities_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;

    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_CAPABILITIES_I18N_KEY)
        : null;

    if (is_array($raw)) {
        return rs_capabilities_i18n_normalize($raw);
    }

    $data = rs_capabilities_i18n_default();
    $data['locales']['en'] = rs_capabilities_locale_from_legacy_post($post_id);

    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_capabilities_locale_from_legacy_post($pt_id);
    }

    return rs_capabilities_i18n_normalize($data);
}

function rs_capabilities_get_post_id_by_locale(string $locale = 'en'): int {
    if (function_exists('rs_section_i18n_canonical_id')) {
        return rs_section_i18n_canonical_id('capabilities');
    }

    return 0;
}

/**
 * Retorna as seções do locale solicitado; o padrão EN mantém compatibilidade
 * com consumidores legados, incluindo o sincronizador de mídia.
 *
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_capabilities_get_sections(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_capabilities_i18n_get($post_id);

    return rs_capabilities_normalize_sections(
        is_array($data['locales'][$locale]['sections'] ?? null)
            ? $data['locales'][$locale]['sections']
            : []
    );
}

function rs_capabilities_meta_to_payload(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_capabilities_i18n_get($post_id);
    $loc = $data['locales'][$locale] ?? rs_capabilities_default_locale();

    if ($locale === 'pt') {
        $en = $data['locales']['en'] ?? rs_capabilities_default_locale();
        if (trim((string) ($loc['headline'] ?? '')) === '') {
            $loc['headline'] = $en['headline'] ?? '';
        }
        if (empty($loc['sections'])) {
            $loc['sections'] = $en['sections'] ?? [];
        }
    }

    return [
        'headline' => trim((string) ($loc['headline'] ?? '')),
        'sections' => rs_capabilities_sections_to_payload(
            rs_capabilities_normalize_sections(
                is_array($loc['sections'] ?? null) ? $loc['sections'] : []
            )
        ),
    ];
}

/**
 * @param array<string, mixed> $data
 */
function rs_capabilities_sync_legacy_meta(int $post_id, array $data): void {
    $en = is_array($data['locales']['en'] ?? null)
        ? $data['locales']['en']
        : rs_capabilities_default_locale();

    update_post_meta($post_id, RS_CAPABILITIES_HEADLINE_KEY, (string) ($en['headline'] ?? ''));
    $sections = rs_capabilities_normalize_sections(
        is_array($en['sections'] ?? null) ? $en['sections'] : []
    );

    if (function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, RS_CAPABILITIES_SECTIONS_KEY, $sections);
    } else {
        update_post_meta($post_id, RS_CAPABILITIES_SECTIONS_KEY, $sections);
    }
}

function rs_capabilities_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }

    rs_section_i18n_migrate_twins(
        'capabilities',
        RS_CAPABILITIES_I18N_KEY,
        'rs_capabilities_i18n_migrated_v1',
        'Capabilities',
        static function (int $post_id, string $locale): array {
            return rs_capabilities_locale_from_legacy_post($post_id);
        },
        'rs_capabilities_i18n_normalize'
    );
}

add_action('init', function () {
    register_post_meta('capabilities', RS_CAPABILITIES_I18N_KEY, [
        'single'        => true,
        'type'          => 'array',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);

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

add_action('init', 'rs_capabilities_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('capabilities', 'capabilities_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request')
                ? rs_section_i18n_locale_from_request($request)
                : 'en';

            return rs_capabilities_meta_to_payload((int) $post['id'], $locale);
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

function rs_capabilities_render_section_row(
    int $index,
    array $section,
    bool $is_template = false,
    string $locale = 'en'
): void {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $title = (string) ($section['title'] ?? '');
    $text = (string) ($section['text'] ?? '');
    $image_id = (int) ($section['image_id'] ?? 0);
    $row_index = $is_template ? '__INDEX__' : (string) $index;
    $name_prefix = 'rs_cap_i18n[' . $locale . '][sections][' . $row_index . ']';
    $image_field_id = 'rs_cap_image_' . $locale . '_' . $row_index;
    $editor_id = 'rs_cap_section_text_' . $locale . '_' . $row_index;
    $display = $is_template ? ' style="display:none;"' : '';
    $is_open = !$is_template && $index === 0;
    $head_title = $title !== '' ? $title : 'Seção';
    $row_class = 'rs-metabox-accordion-item' . ($is_open ? ' is-open' : '');
    ?>
    <fieldset
        class="<?php echo esc_attr($row_class); ?>"
        data-index="<?php echo esc_attr($row_index); ?>"
        data-locale="<?php echo esc_attr($locale); ?>"
        <?php echo !$is_template ? ' data-rs-editor-ids="' . esc_attr($editor_id) . '"' : ''; ?>
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

function rs_capabilities_render_locale_fields(string $locale, array $loc): void {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $headline_id = 'rs_cap_headline_' . $locale;
    $headline_name = 'rs_cap_i18n[' . $locale . '][headline]';
    $sections = rs_capabilities_normalize_sections(
        is_array($loc['sections'] ?? null) ? $loc['sections'] : []
    );

    if (!$sections) {
        $sections = [['title' => '', 'text' => '', 'image_id' => 0]];
    }

    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Headline</strong></legend>';
    rs_render_rich_text_field(
        $headline_id,
        $headline_name,
        (string) ($loc['headline'] ?? ''),
        'inline',
    );
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Use o botão <strong>B</strong> para destacar palavras.</p>';
    echo '</fieldset>';

    echo '<div id="rs-cap-accordion-' . esc_attr($locale) . '" data-rs-accordion data-locale="' . esc_attr($locale) . '">';
    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Seções</strong></legend>';
    echo '<div id="rs-cap-sections-list-' . esc_attr($locale) . '" data-rs-accordion-list>';
    foreach ($sections as $index => $section) {
        rs_capabilities_render_section_row((int) $index, $section, false, $locale);
    }
    echo '</div>';
    echo '<div id="rs-cap-section-template-' . esc_attr($locale) . '" hidden>';
    rs_capabilities_render_section_row(
        0,
        ['title' => '', 'text' => '', 'image_id' => 0],
        true,
        $locale
    );
    echo '</div>';
    echo '<p style="margin:12px 0 0;">';
    echo '<button type="button" class="button button-secondary rs-cap-add-section" data-locale="' . esc_attr($locale) . '">+ Adicionar seção</button>';
    echo '</p>';
    echo '<input type="hidden" id="rs-cap-sections-' . esc_attr($locale) . '-json" name="rs_cap_sections_' . esc_attr($locale) . '_json" value="" />';
    echo '</fieldset>';
    echo '</div>';
}

function rs_capabilities_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_capabilities_save', 'rs_capabilities_nonce');

    $canonical = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id((int) $post->ID)
        : (int) $post->ID;
    $i18n = rs_capabilities_i18n_get($canonical);

    echo '<p style="margin-top:0;color:#646970;">Um único post. Edite <strong>English</strong> e <strong>Português</strong> nas abas. ' . (function_exists('rs_plugin_version_markup') ? rs_plugin_version_markup() : '') . '</p>';
    echo '<div class="rs-metabox-tabs" data-rs-tabs>';
    echo '<div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button>';
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_capabilities_render_locale_fields('en', $i18n['locales']['en']);
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_capabilities_render_locale_fields('pt', $i18n['locales']['pt']);
    echo '</div>';
    echo '</div>';
}

/**
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_capabilities_parse_sections_from_request(string $locale = 'en'): array {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $json_key = 'rs_cap_sections_' . $locale . '_json';

    if (isset($_POST[$json_key]) && (string) $_POST[$json_key] !== '') {
        $decoded = json_decode(wp_unslash((string) $_POST[$json_key]), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sections = [];
            foreach ($decoded as $section) {
                if (!is_array($section)) {
                    continue;
                }

                $sections[] = [
                    'title'    => trim(wp_strip_all_tags((string) ($section['title'] ?? ''))),
                    'text'     => wp_kses_post((string) ($section['text'] ?? '')),
                    'image_id' => (int) ($section['image_id'] ?? 0),
                ];
            }

            return rs_capabilities_normalize_sections($sections);
        }
    }

    $raw_i18n = isset($_POST['rs_cap_i18n']) && is_array($_POST['rs_cap_i18n'])
        ? wp_unslash($_POST['rs_cap_i18n'])
        : [];
    $raw_locale = is_array($raw_i18n[$locale] ?? null) ? $raw_i18n[$locale] : [];
    $raw_sections = is_array($raw_locale['sections'] ?? null) ? $raw_locale['sections'] : [];
    $sections = [];

    foreach ($raw_sections as $key => $section) {
        if ($key === '__INDEX__' || !is_array($section)) {
            continue;
        }

        $sections[] = [
            'title'    => trim(wp_strip_all_tags((string) ($section['title'] ?? ''))),
            'text'     => wp_kses_post((string) ($section['text'] ?? '')),
            'image_id' => (int) ($section['image_id'] ?? 0),
        ];
    }

    return rs_capabilities_normalize_sections($sections);
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

    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;
    $data = rs_capabilities_i18n_get($post_id);
    $raw = isset($_POST['rs_cap_i18n']) && is_array($_POST['rs_cap_i18n'])
        ? wp_unslash($_POST['rs_cap_i18n'])
        : [];

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        $data['locales'][$locale] = [
            'headline' => wp_kses_post((string) ($loc['headline'] ?? '')),
            'sections' => rs_capabilities_parse_sections_from_request($locale),
        ];
    }

    $normalized = rs_capabilities_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_CAPABILITIES_I18N_KEY, $normalized);
    } else {
        update_post_meta($post_id, RS_CAPABILITIES_I18N_KEY, $normalized);
    }
    rs_capabilities_sync_legacy_meta($post_id, $normalized);
}, 10);

function rs_copy_capabilities_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
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
        const locales = ['en', 'pt'];
        const nextIndex = {};
        const accordionApis = {};

        function list(locale) {
            return $('#rs-cap-sections-list-' + locale);
        }

        function syncAllEditors() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            locales.forEach(function (locale) {
                const headlineId = 'rs_cap_headline_' + locale;
                if (typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
                    wp.editor.save(headlineId);
                }
            });
            $('textarea[id^="rs_cap_section_text_"]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') === -1 && typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
                    wp.editor.save(id);
                }
            });
        }

        function readSectionText($textarea) {
            const editorId = $textarea.attr('id');
            if (editorId && typeof tinymce !== 'undefined') {
                const editor = tinymce.get(editorId);
                if (editor && !editor.isHidden()) {
                    return editor.getContent();
                }
            }
            return $textarea.val() || '';
        }

        function collectSectionsJson(locale, syncEditors) {
            if (syncEditors !== false) {
                syncAllEditors();
            }
            const sections = [];
            list(locale).find('.rs-metabox-accordion-item').each(function () {
                const $section = $(this);
                const title = ($section.find('.rs-cap-section-title').val() || '').trim();
                const text = readSectionText($section.find('textarea[id^="rs_cap_section_text_' + locale + '_"]'));
                const imageId = parseInt($section.find('input[data-rs-cap-image]').val(), 10) || 0;
                if (!title && !text && !imageId) {
                    return;
                }
                sections.push({
                    title: title || 'Seção',
                    text: text,
                    image_id: imageId,
                });
            });
            $('#rs-cap-sections-' + locale + '-json').val(JSON.stringify(sections));
        }

        function collectAllSectionsJson() {
            syncAllEditors();
            locales.forEach(function (locale) {
                collectSectionsJson(locale, false);
            });
        }

        function initEditor(id) {
            if (!id || id.indexOf('__INDEX__') !== -1 || typeof wp === 'undefined' || !wp.editor) {
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

        function assignSectionNames($section, locale, index) {
            const prefix = 'rs_cap_i18n[' + locale + '][sections][' + index + ']';
            const $textarea = $section.find('textarea[id^="rs_cap_section_text_' + locale + '_"]');
            $section.find('.rs-cap-section-title').attr('name', prefix + '[title]');
            $textarea.attr('name', prefix + '[text]');
            $section.find('input[data-rs-cap-image]').attr('name', prefix + '[image_id]');
            const editorId = $textarea.attr('id');
            if (editorId) {
                $section.attr('data-rs-editor-ids', editorId);
            }
        }

        function maxEditorSuffix(locale) {
            let max = -1;
            list(locale).find('textarea[id^="rs_cap_section_text_' + locale + '_"]').each(function () {
                const match = String(this.id || '').match(/_(\d+)$/);
                if (match) {
                    max = Math.max(max, parseInt(match[1], 10));
                }
            });
            return max;
        }

        function reindexSections(locale) {
            // Só name=/data-index — não recria TinyMCE (perdia conteúdo ao reordenar).
            list(locale).find('.rs-metabox-accordion-item').each(function (index) {
                const $section = $(this);
                $section.attr('data-index', String(index));
                assignSectionNames($section, locale, index);

                $section.find('[id^="rs_cap_image_' + locale + '_"]').each(function () {
                    const oldId = $(this).attr('id');
                    const newId = 'rs_cap_image_' + locale + '_' + index;
                    if (oldId === newId) {
                        return;
                    }
                    $(this).attr('id', newId);
                    $section.find('[data-target="' + oldId + '"]').attr('data-target', newId);
                });
            });
            nextIndex[locale] = Math.max(
                maxEditorSuffix(locale) + 1,
                list(locale).find('.rs-metabox-accordion-item').length
            );
        }

        locales.forEach(function (locale) {
            nextIndex[locale] = Math.max(
                list(locale).find('.rs-metabox-accordion-item').length,
                (function () {
                    let max = -1;
                    list(locale).find('textarea[id^="rs_cap_section_text_' + locale + '_"]').each(function () {
                        const match = String(this.id || '').match(/_(\d+)$/);
                        if (match) max = Math.max(max, parseInt(match[1], 10));
                    });
                    return max + 1;
                })()
            );
            const root = document.querySelector('#rs-cap-accordion-' + locale);

            if (root && window.RsMetaboxUi) {
                accordionApis[locale] = window.RsMetaboxUi.initAccordion(root, {
                    defaultTitle: 'Seção',
                    onExpand: function ($item, editorIds) {
                        window.RsMetaboxUi.resizeEditors(editorIds);
                    },
                    onRemove: function (event, $section) {
                        event.preventDefault();
                        if (list(locale).find('.rs-metabox-accordion-item').length <= 1) {
                            window.alert('Mantenha pelo menos uma seção.');
                            return;
                        }
                        removeEditor($section.find('textarea[id^="rs_cap_section_text_' + locale + '_"]').attr('id'));
                        $section.remove();
                        reindexSections(locale);
                    },
                    onSortUpdate: function () {
                        reindexSections(locale);
                    },
                });
            }
        });

        $('.rs-cap-add-section').on('click', function (event) {
            event.preventDefault();
            const locale = $(this).data('locale') === 'pt' ? 'pt' : 'en';
            const index = nextIndex[locale];
            const $template = $('#rs-cap-section-template-' + locale + ' .rs-metabox-accordion-item').first().clone();

            $template.removeAttr('style').removeClass('is-open');
            $template.attr('data-index', String(index));
            $template.find('.rs-cap-section-title').val('');
            $template.find('textarea').val('');
            $template.find('.rs-metabox-accordion-head-title').text('Seção');
            $template.find('.rs-metabox-accordion-toggle').attr('aria-expanded', 'false');
            $template.find('input[data-rs-cap-image]').val('0');
            $template.find('.rs-media-preview').empty();
            $template.find('[id]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') !== -1) {
                    $(this).attr('id', id.replace(/__INDEX__/g, String(index)));
                }
            });
            $template.find('[data-target]').each(function () {
                const target = $(this).attr('data-target');
                if (target) {
                    $(this).attr('data-target', target.replace(/__INDEX__/g, String(index)));
                }
            });

            assignSectionNames($template, locale, index);
            list(locale).append($template);
            initEditor('rs_cap_section_text_' + locale + '_' + index);
            if (accordionApis[locale]) {
                accordionApis[locale].openItem($template);
            }
            nextIndex[locale] += 1;
        });

        $('[data-rs-tabs]').on('rs-metabox-tabchange', function (_event, locale) {
            if (locale !== 'en' && locale !== 'pt') {
                return;
            }
            window.setTimeout(function () {
                list(locale).find('.rs-metabox-accordion-item.is-open').each(function () {
                    window.RsMetaboxUi.resizeEditors(
                        window.RsMetaboxUi.parseEditorIds($(this))
                    );
                });
            }, 0);
        });

        $('#post').on('submit', collectAllSectionsJson);
        $(document).on('click', '#publish, #save-post', collectAllSectionsJson);
    });
    </script>
    <?php
}

add_action('admin_footer-post.php', 'rs_capabilities_render_admin_footer_script');
add_action('admin_footer-post-new.php', 'rs_capabilities_render_admin_footer_script');
