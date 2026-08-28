<?php
/**
 * Campos editáveis do CPT about (Sobre Nós).
 */

if (defined('RS_ABOUT_FIELDS_LOADED')) {
    return;
}
define('RS_ABOUT_FIELDS_LOADED', true);

const RS_ABOUT_I18N_KEY = 'rs_about_i18n';
const RS_ABOUT_HERO_IMAGE_KEY = 'rs_about_hero_image_id';
const RS_ABOUT_HERO_VIDEO_KEY = 'rs_about_hero_video_id';
const RS_ABOUT_HEADLINE_KEY = 'rs_about_headline';
const RS_ABOUT_BODY_KEY = 'rs_about_body';
const RS_ABOUT_SECTIONS_KEY = 'rs_about_sections';

/**
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_about_get_legacy_sections(int $post_id): array {
    $decoded = function_exists('rs_meta_get_array')
        ? rs_meta_get_array($post_id, RS_ABOUT_SECTIONS_KEY)
        : null;

    if (is_array($decoded)) {
        return rs_about_normalize_sections($decoded);
    }

    return [];
}

function rs_about_default_locale(): array {
    return ['headline' => '', 'body' => '', 'sections' => []];
}

function rs_about_i18n_default(): array {
    return [
        'v' => 1,
        'shared' => ['hero_image_id' => 0, 'hero_video_id' => 0],
        'locales' => [
            'en' => rs_about_default_locale(),
            'pt' => rs_about_default_locale(),
        ],
    ];
}

function rs_about_i18n_normalize(array $raw): array {
    $data = rs_about_i18n_default();
    $shared = is_array($raw['shared'] ?? null) ? $raw['shared'] : [];
    $data['shared']['hero_image_id'] = (int) ($shared['hero_image_id'] ?? 0);
    $data['shared']['hero_video_id'] = (int) ($shared['hero_video_id'] ?? 0);

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $data['locales'][$locale] = [
            'headline' => trim((string) ($loc['headline'] ?? '')),
            'body' => trim((string) ($loc['body'] ?? '')),
            'sections' => rs_about_normalize_sections(
                is_array($loc['sections'] ?? null) ? $loc['sections'] : []
            ),
        ];
    }

    return $data;
}

function rs_about_locale_from_legacy_post(int $post_id): array {
    if ($post_id <= 0) {
        return rs_about_default_locale();
    }

    return [
        'headline' => trim((string) get_post_meta($post_id, RS_ABOUT_HEADLINE_KEY, true)),
        'body' => trim((string) get_post_meta($post_id, RS_ABOUT_BODY_KEY, true)),
        'sections' => rs_about_get_legacy_sections($post_id),
    ];
}

function rs_about_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;
    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_ABOUT_I18N_KEY)
        : get_post_meta($post_id, RS_ABOUT_I18N_KEY, true);

    if (is_array($raw)) {
        return rs_about_i18n_normalize($raw);
    }

    $data = rs_about_i18n_default();
    $data['shared'] = [
        'hero_image_id' => (int) get_post_meta($post_id, RS_ABOUT_HERO_IMAGE_KEY, true),
        'hero_video_id' => (int) get_post_meta($post_id, RS_ABOUT_HERO_VIDEO_KEY, true),
    ];
    $data['locales']['en'] = rs_about_locale_from_legacy_post($post_id);
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_about_locale_from_legacy_post($pt_id);
    }

    return rs_about_i18n_normalize($data);
}

function rs_about_get_sections(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_about_i18n_get($post_id);
    return rs_about_normalize_sections(
        is_array($data['locales'][$locale]['sections'] ?? null)
            ? $data['locales'][$locale]['sections']
            : []
    );
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

function rs_about_meta_to_payload(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;
    $data = rs_about_i18n_get($post_id);
    $loc = $data['locales'][$locale] ?? rs_about_default_locale();
    // Sem fallback EN→PT: o front usa defaults em português quando PT está vazio.
    $image_id = (int) ($data['shared']['hero_image_id'] ?? 0);
    $video_id = (int) ($data['shared']['hero_video_id'] ?? 0);

    return [
        'heroImage' => $image_id > 0 ? (string) wp_get_attachment_url($image_id) : '',
        'heroVideo' => $video_id > 0 ? (string) wp_get_attachment_url($video_id) : '',
        'headline' => trim((string) ($loc['headline'] ?? '')),
        'body' => trim((string) ($loc['body'] ?? '')),
        'accordionSections' => rs_about_sections_to_payload(
            rs_about_normalize_sections(is_array($loc['sections'] ?? null) ? $loc['sections'] : [])
        ),
    ];
}

function rs_about_get_post_id_by_locale(string $locale = 'en'): int {
    return function_exists('rs_section_i18n_canonical_id')
        ? rs_section_i18n_canonical_id('about')
        : 0;
}

function rs_about_sync_legacy_meta(int $post_id, array $data): void {
    $en = is_array($data['locales']['en'] ?? null) ? $data['locales']['en'] : rs_about_default_locale();
    update_post_meta($post_id, RS_ABOUT_HERO_IMAGE_KEY, (int) ($data['shared']['hero_image_id'] ?? 0));
    update_post_meta($post_id, RS_ABOUT_HERO_VIDEO_KEY, (int) ($data['shared']['hero_video_id'] ?? 0));
    update_post_meta($post_id, RS_ABOUT_HEADLINE_KEY, (string) ($en['headline'] ?? ''));
    update_post_meta($post_id, RS_ABOUT_BODY_KEY, (string) ($en['body'] ?? ''));
    $sections = rs_about_normalize_sections(is_array($en['sections'] ?? null) ? $en['sections'] : []);
    if (function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, RS_ABOUT_SECTIONS_KEY, $sections);
    } else {
        update_post_meta($post_id, RS_ABOUT_SECTIONS_KEY, $sections);
    }
}

function rs_about_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }

    $already_migrated = (bool) get_option('rs_about_i18n_migrated_v1');
    $post_id = rs_section_i18n_migrate_twins(
        'about',
        RS_ABOUT_I18N_KEY,
        'rs_about_i18n_migrated_v1',
        'About',
        static function (int $post_id, string $locale): array {
            return rs_about_locale_from_legacy_post($post_id);
        },
        'rs_about_i18n_normalize'
    );
    if (!$already_migrated && $post_id > 0) {
        $data = rs_about_i18n_get($post_id);
        $data['shared'] = [
            'hero_image_id' => (int) get_post_meta($post_id, RS_ABOUT_HERO_IMAGE_KEY, true),
            'hero_video_id' => (int) get_post_meta($post_id, RS_ABOUT_HERO_VIDEO_KEY, true),
        ];
        $data = rs_about_i18n_normalize($data);
        rs_section_i18n_save($post_id, RS_ABOUT_I18N_KEY, $data);
        rs_about_sync_legacy_meta($post_id, $data);
    }
}

add_action('init', function () {
    register_post_meta('about', RS_ABOUT_I18N_KEY, [
        'single' => true,
        'type' => 'array',
        'show_in_rest' => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
    foreach ([RS_ABOUT_HERO_IMAGE_KEY, RS_ABOUT_HERO_VIDEO_KEY, RS_ABOUT_HEADLINE_KEY, RS_ABOUT_BODY_KEY, RS_ABOUT_SECTIONS_KEY] as $key) {
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

add_action('init', 'rs_about_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('about', 'about_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request')
                ? rs_section_i18n_locale_from_request($request)
                : 'en';
            return rs_about_meta_to_payload((int) $post['id'], $locale);
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

function rs_about_render_section_row(int $index, array $section, bool $is_template = false, string $locale = 'en'): void {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $title = $section['title'] ?? '';
    $text = $section['text'] ?? '';
    $image_id = (int) ($section['image_id'] ?? 0);
    $row_index = $is_template ? '__INDEX__' : (string) $index;
    $name_prefix = 'rs_about_i18n[' . $locale . '][sections][' . $row_index . ']';
    $image_field_id = 'rs_about_image_' . $locale . '_' . $row_index;
    $editor_id = 'rs_about_section_text_' . $locale . '_' . $row_index;
    $display = $is_template ? ' style="display:none;"' : '';
    $is_open = !$is_template && (int) $index === 0;
    $head_title = $title !== '' ? $title : 'Seção';
    $row_class = 'rs-metabox-accordion-item' . ($is_open ? ' is-open' : '');
    $thumb = '';
    if (!$is_template && $image_id > 0) {
        $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail');
        if ($thumb_url) {
            $thumb = '<span class="rs-metabox-accordion-head-thumb"><img src="' . esc_url($thumb_url) . '" alt="" /></span>';
        }
    }
    $editor_ids = $is_template ? '' : esc_attr($editor_id);
    ?>
    <fieldset
        class="<?php echo esc_attr($row_class); ?>"
        data-index="<?php echo esc_attr($row_index); ?>"
        data-locale="<?php echo esc_attr($locale); ?>"
        <?php echo $editor_ids !== '' ? ' data-rs-editor-ids="' . $editor_ids . '"' : ''; ?>
        <?php echo $display; ?>
    >
        <div class="rs-metabox-accordion-head">
            <span class="rs-metabox-accordion-drag" title="Arrastar para reordenar" aria-hidden="true">⋮⋮</span>
            <button type="button" class="rs-metabox-accordion-toggle" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
                <?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url acima ?>
                <span class="rs-metabox-accordion-head-title"><?php echo esc_html($head_title); ?></span>
            </button>
            <button type="button" class="button-link-delete rs-metabox-accordion-remove rs-about-remove-section">Remover</button>
        </div>
        <div class="rs-metabox-accordion-panel">
            <div style="margin:0 0 12px;">
                <label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>
                <input
                    type="text"
                    style="width:100%;"
                    class="rs-metabox-accordion-title rs-about-section-title"
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
        </div>
    </fieldset>
    <?php
}

function rs_about_render_locale_fields(string $locale, array $loc): void {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $sections = rs_about_normalize_sections(is_array($loc['sections'] ?? null) ? $loc['sections'] : []);
    if (!$sections) {
        $sections = [['title' => '', 'text' => '', 'image_id' => 0]];
    }

    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Headline</strong></legend>';
    rs_render_rich_text_field(
        'rs_about_headline_' . $locale,
        'rs_about_i18n[' . $locale . '][headline]',
        (string) ($loc['headline'] ?? ''),
        'inline'
    );
    echo '</fieldset>';
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Texto introdutório</strong></legend>';
    rs_render_rich_text_field(
        'rs_about_body_' . $locale,
        'rs_about_i18n[' . $locale . '][body]',
        (string) ($loc['body'] ?? ''),
        'paragraph'
    );
    echo '</fieldset>';

    echo '<div id="rs-about-accordion-' . esc_attr($locale) . '" data-rs-accordion data-locale="' . esc_attr($locale) . '">';
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Seções do acordeão</strong></legend>';
    echo '<div id="rs-about-sections-list-' . esc_attr($locale) . '" data-rs-accordion-list>';
    foreach ($sections as $index => $section) {
        rs_about_render_section_row((int) $index, $section, false, $locale);
    }
    echo '</div>';
    echo '<div id="rs-about-section-template-' . esc_attr($locale) . '" hidden>';
    rs_about_render_section_row(0, ['title' => '', 'text' => '', 'image_id' => 0], true, $locale);
    echo '</div>';
    echo '<p style="margin:12px 0 0;"><button type="button" class="button button-secondary rs-about-add-section" data-locale="' . esc_attr($locale) . '">+ Adicionar seção</button></p>';
    echo '<input type="hidden" id="rs-about-sections-' . esc_attr($locale) . '-json" name="rs_about_sections_' . esc_attr($locale) . '_json" value="" />';
    echo '</fieldset></div>';
}

function rs_about_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_about_save', 'rs_about_nonce');

    $canonical = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id((int) $post->ID)
        : (int) $post->ID;
    $i18n = rs_about_i18n_get($canonical);

    echo '<p style="margin-top:0;color:#646970;">Um único post. Edite <strong>English</strong> e <strong>Português</strong> nas abas. ' . (function_exists('rs_plugin_version_markup') ? rs_plugin_version_markup() : '') . '</p>';
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Mídia compartilhada do hero</strong></legend>';
    rs_section_shared_hero_render_fields(
        $canonical,
        $i18n['shared'],
        'rs_about',
        'rs_about_shared',
        RS_ABOUT_HERO_IMAGE_KEY,
        RS_ABOUT_HERO_VIDEO_KEY,
        'rs_about_shared_hero_image',
        'rs_about_shared_hero_video'
    );
    echo '</fieldset>';

    echo '<div class="rs-metabox-tabs" data-rs-tabs>';
    echo '<div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button>';
    echo '</div>';
    echo '<div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_about_render_locale_fields('en', $i18n['locales']['en']);
    echo '</div>';
    echo '<div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_about_render_locale_fields('pt', $i18n['locales']['pt']);
    echo '</div>';
    echo '</div>';
}

/**
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_about_parse_sections_from_request(string $locale = 'en'): array {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $sections = [];

    $json_key = 'rs_about_sections_' . $locale . '_json';
    if (!empty($_POST[$json_key])) {
        $decoded = json_decode(wp_unslash((string) $_POST[$json_key]), true);
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

    $raw_i18n = isset($_POST['rs_about_i18n']) && is_array($_POST['rs_about_i18n'])
        ? wp_unslash($_POST['rs_about_i18n'])
        : [];
    $raw_locale = is_array($raw_i18n[$locale] ?? null) ? $raw_i18n[$locale] : [];
    if (!isset($raw_locale['sections']) || !is_array($raw_locale['sections'])) {
        return [];
    }

    foreach ($raw_locale['sections'] as $key => $section) {
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

    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;
    $previous = rs_about_i18n_get($post_id);
    $data = rs_section_shared_hero_parse_from_request($previous, 'rs_about', 'rs_about_shared');
    $raw = isset($_POST['rs_about_i18n']) && is_array($_POST['rs_about_i18n'])
        ? wp_unslash($_POST['rs_about_i18n'])
        : [];
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        $data['locales'][$locale] = [
            'headline' => wp_kses_post((string) ($loc['headline'] ?? '')),
            'body' => wp_kses_post((string) ($loc['body'] ?? '')),
            'sections' => rs_about_parse_sections_from_request($locale),
        ];
    }

    $normalized = rs_about_i18n_normalize(
        rs_section_shared_hero_guard_against_wipe(
            $data,
            $previous,
            'rs_about',
            $post_id,
            RS_ABOUT_HERO_IMAGE_KEY,
            RS_ABOUT_HERO_VIDEO_KEY,
            'about'
        )
    );
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_ABOUT_I18N_KEY, $normalized);
    } else {
        update_post_meta($post_id, RS_ABOUT_I18N_KEY, $normalized);
    }
    rs_about_sync_legacy_meta($post_id, $normalized);
}, 10);

function rs_copy_about_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}

rs_enqueue_admin_media_picker(['about']);

function rs_about_render_admin_footer_script(): void {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'about') {
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
            return $('#rs-about-sections-list-' + locale);
        }

        function syncHeadlineEditors() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            if (typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
                locales.forEach(function (locale) {
                    wp.editor.save('rs_about_headline_' + locale);
                    wp.editor.save('rs_about_body_' + locale);
                });
            }
        }

        function syncAllEditors() {
            syncHeadlineEditors();
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
                if (editor && !editor.isHidden()) {
                    return editor.getContent();
                }
            }
            return textarea.val() || '';
        }

        function syncSectionHeadThumb($section) {
            const $toggle = $section.find('.rs-metabox-accordion-toggle').first();
            const $previewImg = $section.find('.rs-media-preview img').first();
            $section.find('.rs-metabox-accordion-head-thumb').remove();
            if ($previewImg.length) {
                $toggle.prepend(
                    $('<span class="rs-metabox-accordion-head-thumb"><img alt="" /></span>')
                        .find('img')
                        .attr('src', $previewImg.attr('src'))
                        .end()
                );
            }
        }

        function collectSectionsJson(locale, syncEditors) {
            if (syncEditors !== false) syncAllEditors();
            const sections = [];
            list(locale).find('.rs-metabox-accordion-item').each(function () {
                const section = $(this);
                const title = (section.find('.rs-about-section-title').val() || '').trim();
                const text = readSectionText(section.find('textarea[id^="rs_about_section_text_' + locale + '_"]'));
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
            $('#rs-about-sections-' + locale + '-json').val(JSON.stringify(sections));
        }

        function collectAllSectionsJson() {
            syncAllEditors();
            locales.forEach(function (locale) {
                collectSectionsJson(locale, false);
            });
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

        function assignSectionNames(section, locale, index) {
            const prefix = 'rs_about_i18n[' + locale + '][sections][' + index + ']';
            const $textarea = section.find('textarea[id^="rs_about_section_text_' + locale + '_"]');
            section.find('.rs-about-section-title').attr('name', prefix + '[title]');
            $textarea.attr('name', prefix + '[text]');
            section.find('input[data-rs-cap-image]').attr('name', prefix + '[image_id]');
            // Mantém o id do TinyMCE estável — só atualiza data-rs-editor-ids.
            const editorId = $textarea.attr('id');
            if (editorId) {
                section.attr('data-rs-editor-ids', editorId);
            }
        }

        function maxEditorSuffix(locale) {
            let max = -1;
            list(locale).find('textarea[id^="rs_about_section_text_' + locale + '_"]').each(function () {
                const match = String(this.id || '').match(/_(\d+)$/);
                if (match) {
                    max = Math.max(max, parseInt(match[1], 10));
                }
            });
            return max;
        }

        function reindexSections(locale) {
            // Só renomeia name=/data-index. Nunca destroy/recria TinyMCE (apaga conteúdo).
            list(locale).find('.rs-metabox-accordion-item').each(function (i) {
                $(this).attr('data-index', String(i));
                assignSectionNames($(this), locale, i);
                $(this).find('[id^="rs_about_image_' + locale + '_"]').each(function () {
                    const oldId = $(this).attr('id');
                    const newId = 'rs_about_image_' + locale + '_' + i;
                    if (oldId === newId) {
                        return;
                    }
                    $(this).attr('id', newId);
                    $(this).closest('.rs-media-field').find('[data-target="' + oldId + '"]').attr('data-target', newId);
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
                    list(locale).find('textarea[id^="rs_about_section_text_' + locale + '_"]').each(function () {
                        const match = String(this.id || '').match(/_(\d+)$/);
                        if (match) max = Math.max(max, parseInt(match[1], 10));
                    });
                    return max + 1;
                })()
            );
            const accordionRoot = document.querySelector('#rs-about-accordion-' + locale);
            if (accordionRoot && window.RsMetaboxUi) {
            accordionApis[locale] = window.RsMetaboxUi.initAccordion(accordionRoot, {
                onExpand: function ($item, editorIds) {
                    window.RsMetaboxUi.resizeEditors(editorIds);
                },
                onRemove: function (event, $section) {
                    event.preventDefault();
                    if (list(locale).find('.rs-metabox-accordion-item').length <= 1) {
                        window.alert('Mantenha pelo menos uma seção.');
                        return;
                    }
                    removeEditor($section.find('textarea[id^="rs_about_section_text_' + locale + '_"]').attr('id'));
                    $section.remove();
                    reindexSections(locale);
                },
                onSortUpdate: function () {
                    reindexSections(locale);
                },
            });
            }
        });

        $('[data-rs-tabs]').on('rs-metabox-tabchange', function (_event, locale) {
            if (locale === 'en' || locale === 'pt') {
                window.setTimeout(function () {
                    list(locale).find('.rs-metabox-accordion-item.is-open').each(function () {
                        window.RsMetaboxUi.resizeEditors(window.RsMetaboxUi.parseEditorIds($(this)));
                    });
                }, 50);
            }
        });

        $(document).on('click', '.rs-media-pick, .rs-media-clear', function () {
            const $section = $(this).closest('.rs-metabox-accordion-item');
            if (!$section.length) {
                return;
            }
            window.setTimeout(function () {
                syncSectionHeadThumb($section);
            }, 120);
        });

        $('.rs-about-add-section').on('click', function (event) {
            event.preventDefault();
            const locale = $(this).data('locale') === 'pt' ? 'pt' : 'en';
            const index = nextIndex[locale];
            const template = $('#rs-about-section-template-' + locale + ' .rs-metabox-accordion-item').first().clone();
            template.removeAttr('style').removeClass('is-open');
            template.attr('data-index', String(index));
            template.find('.rs-about-section-title').val('');
            template.find('textarea').val('');
            template.find('.rs-metabox-accordion-head-title').text('Seção');
            template.find('.rs-metabox-accordion-head-thumb').remove();
            template.find('.rs-metabox-accordion-toggle').attr('aria-expanded', 'false');
            template.find('input[data-rs-cap-image]').val('0');
            template.find('.rs-media-preview').empty();
            template.find('[id]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') !== -1) {
                    $(this).attr('id', id.replace(/__INDEX__/g, String(index)));
                }
            });
            template.find('[data-target]').each(function () {
                const target = $(this).attr('data-target');
                if (target) {
                    $(this).attr('data-target', target.replace(/__INDEX__/g, String(index)));
                }
            });
            assignSectionNames(template, locale, index);
            list(locale).append(template);
            initEditor('rs_about_section_text_' + locale + '_' + index);
            if (accordionApis[locale]) {
                accordionApis[locale].openItem(template);
            }
            nextIndex[locale] += 1;
        });

        $('#post').on('submit', collectAllSectionsJson);
        $('#publish, #save-post').on('click', function () {
            window.setTimeout(collectAllSectionsJson, 0);
        });
    });
    </script>
    <?php
}

add_action('admin_footer-post.php', 'rs_about_render_admin_footer_script');
add_action('admin_footer-post-new.php', 'rs_about_render_admin_footer_script');
