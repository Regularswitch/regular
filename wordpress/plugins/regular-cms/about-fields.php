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
const RS_ABOUT_GALLERY_KEY = 'rs_about_gallery';
const RS_ABOUT_GALLERY_FEATURED_KEY = 'rs_about_gallery_featured';

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
        'shared' => [
            'hero_image_id' => 0,
            'hero_video_id' => 0,
            'gallery_ids' => '',
            'gallery_featured_ids' => '',
        ],
        'locales' => [
            'en' => rs_about_default_locale(),
            'pt' => rs_about_default_locale(),
        ],
    ];
}

/**
 * @return array<int, int>
 */
function rs_about_parse_csv_ids(string $raw): array {
    if ($raw === '') {
        return [];
    }

    $ids = array_map('intval', explode(',', $raw));
    return array_values(array_filter($ids, static function (int $id): bool {
        return $id > 0;
    }));
}

/**
 * @return array<int, int>
 */
function rs_about_get_gallery_ids_from_shared(array $shared): array {
    $ids = rs_about_parse_csv_ids((string) ($shared['gallery_ids'] ?? ''));
    if ($ids) {
        return $ids;
    }

    return [];
}

/**
 * @return array<int, int>
 */
function rs_about_get_gallery_featured_ids_from_shared(array $shared): array {
    $featured = rs_about_parse_csv_ids((string) ($shared['gallery_featured_ids'] ?? ''));
    $in_gallery = array_flip(rs_about_get_gallery_ids_from_shared($shared));

    return array_values(array_filter($featured, static function (int $id) use ($in_gallery): bool {
        return $id > 0 && isset($in_gallery[$id]);
    }));
}

/**
 * @return array<int, array<string, mixed>>
 */
function rs_about_gallery_to_payload(array $shared): array {
    $gallery = [];
    $featured_ids = array_flip(rs_about_get_gallery_featured_ids_from_shared($shared));

    foreach (rs_about_get_gallery_ids_from_shared($shared) as $attachment_id) {
        $info = null;
        if (function_exists('rs_project_attachment_info')) {
            $info = rs_project_attachment_info($attachment_id);
        }
        if ($info && !empty($info['url'])) {
            $info['featured'] = isset($featured_ids[$attachment_id]);
            $gallery[] = $info;
        }
    }

    return $gallery;
}

function rs_about_i18n_normalize(array $raw): array {
    $data = rs_about_i18n_default();
    $shared = is_array($raw['shared'] ?? null) ? $raw['shared'] : [];
    $data['shared']['hero_image_id'] = (int) ($shared['hero_image_id'] ?? 0);
    $data['shared']['hero_video_id'] = (int) ($shared['hero_video_id'] ?? 0);
    $data['shared']['gallery_ids'] = implode(',', rs_about_parse_csv_ids((string) ($shared['gallery_ids'] ?? '')));
    $data['shared']['gallery_featured_ids'] = implode(
        ',',
        rs_about_get_gallery_featured_ids_from_shared([
            'gallery_ids' => $data['shared']['gallery_ids'],
            'gallery_featured_ids' => (string) ($shared['gallery_featured_ids'] ?? ''),
        ])
    );

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
        'gallery_ids' => (string) get_post_meta($post_id, RS_ABOUT_GALLERY_KEY, true),
        'gallery_featured_ids' => (string) get_post_meta($post_id, RS_ABOUT_GALLERY_FEATURED_KEY, true),
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
        'gallery' => rs_about_gallery_to_payload(is_array($data['shared'] ?? null) ? $data['shared'] : []),
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
    update_post_meta($post_id, RS_ABOUT_GALLERY_KEY, (string) ($data['shared']['gallery_ids'] ?? ''));
    update_post_meta($post_id, RS_ABOUT_GALLERY_FEATURED_KEY, (string) ($data['shared']['gallery_featured_ids'] ?? ''));
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
            'gallery_ids' => (string) get_post_meta($post_id, RS_ABOUT_GALLERY_KEY, true),
            'gallery_featured_ids' => (string) get_post_meta($post_id, RS_ABOUT_GALLERY_FEATURED_KEY, true),
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
    foreach ([RS_ABOUT_HERO_IMAGE_KEY, RS_ABOUT_HERO_VIDEO_KEY, RS_ABOUT_HEADLINE_KEY, RS_ABOUT_BODY_KEY, RS_ABOUT_SECTIONS_KEY, RS_ABOUT_GALLERY_KEY, RS_ABOUT_GALLERY_FEATURED_KEY] as $key) {
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

    rs_ds_fieldset_open('Headline');
    rs_render_rich_text_field(
        'rs_about_headline_' . $locale,
        'rs_about_i18n[' . $locale . '][headline]',
        (string) ($loc['headline'] ?? ''),
        'inline'
    );
    rs_ds_fieldset_close();

    rs_ds_fieldset_open('Texto introdutório');
    rs_render_rich_text_field(
        'rs_about_body_' . $locale,
        'rs_about_i18n[' . $locale . '][body]',
        (string) ($loc['body'] ?? ''),
        'paragraph'
    );
    rs_ds_fieldset_close();

    echo '<div id="rs-about-accordion-' . esc_attr($locale) . '" data-rs-accordion data-locale="' . esc_attr($locale) . '">';
    rs_ds_fieldset_open('Seções do acordeão');
    echo '<div id="rs-about-sections-list-' . esc_attr($locale) . '" data-rs-accordion-list>';
    foreach ($sections as $index => $section) {
        rs_about_render_section_row((int) $index, $section, false, $locale);
    }
    echo '</div>';
    echo '<div id="rs-about-section-template-' . esc_attr($locale) . '" hidden>';
    rs_about_render_section_row(0, ['title' => '', 'text' => '', 'image_id' => 0], true, $locale);
    echo '</div>';
    echo '<p class="rs-ds-actions"><button type="button" class="button button-secondary rs-about-add-section" data-locale="' . esc_attr($locale) . '">+ Adicionar seção</button></p>';
    echo '<input type="hidden" id="rs-about-sections-' . esc_attr($locale) . '-json" name="rs_about_sections_' . esc_attr($locale) . '_json" value="" />';
    rs_ds_fieldset_close();
    echo '</div>';
}

function rs_about_render_gallery_row(int $index, int $attachment_id, bool $is_template = false, bool $featured = false): void {
    $field_id = $is_template ? 'rs_about_gallery_image___INDEX__' : 'rs_about_gallery_image_' . $index;
    $display = $is_template ? ' style="display:none;"' : '';
    $featured = $is_template ? false : $featured;

    $url = $attachment_id > 0 ? (string) wp_get_attachment_url($attachment_id) : '';
    $mime = $attachment_id > 0 ? (string) get_post_mime_type($attachment_id) : '';
    $is_video = $mime !== '' && str_starts_with($mime, 'video/');
    $meta = $attachment_id > 0 ? wp_get_attachment_metadata($attachment_id) : [];
    $media_width = (int) ($meta['width'] ?? 0);
    $media_height = (int) ($meta['height'] ?? 0);
    $thumb = '';
    if ($attachment_id > 0 && !$is_video) {
        $thumb = (string) (wp_get_attachment_image_url($attachment_id, 'medium') ?: $url);
    }

    $row_classes = 'rs-project-gallery-row';
    if ($featured) {
        $row_classes .= ' rs-project-gallery-row--wide';
    }
    ?>
    <div
        class="<?php echo esc_attr($row_classes); ?>"
        data-index="<?php echo esc_attr($is_template ? '__INDEX__' : (string) $index); ?>"
        <?php if ($media_width > 0) : ?>data-media-width="<?php echo esc_attr((string) $media_width); ?>"<?php endif; ?>
        <?php if ($media_height > 0) : ?>data-media-height="<?php echo esc_attr((string) $media_height); ?>"<?php endif; ?>
        <?php echo $display; ?>
    >
        <div class="rs-project-gallery-tile<?php echo $featured ? ' is-featured' : ''; ?>">
            <input
                type="hidden"
                id="<?php echo esc_attr($field_id); ?>"
                value="<?php echo esc_attr((string) $attachment_id); ?>"
                data-rs-cap-image="1"
                data-rs-library="media"
            />
            <input
                type="hidden"
                class="rs-project-gallery-featured-flag"
                value="<?php echo $featured ? '1' : '0'; ?>"
            />
            <div class="rs-project-gallery-media">
                <div class="rs-media-preview rs-project-gallery-preview" data-target="<?php echo esc_attr($field_id); ?>">
                    <?php if ($url && $is_video) : ?>
                        <video src="<?php echo esc_url($url); ?>" muted playsinline preload="metadata"></video>
                        <span class="rs-project-gallery-badge" title="Vídeo">
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg>
                            vídeo
                        </span>
                    <?php elseif ($thumb || $url) : ?>
                        <img src="<?php echo esc_url($thumb ?: $url); ?>" alt="" />
                        <?php if (str_contains(strtolower($mime), 'gif') || str_ends_with(strtolower($url), '.gif')) : ?>
                            <span class="rs-project-gallery-badge">gif</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="rs-project-gallery-actions" aria-hidden="false">
                <span class="rs-project-gallery-handle" title="Arrastar para reordenar" aria-hidden="true">⋮⋮</span>
                <button type="button" class="rs-about-remove-gallery rs-project-remove-gallery" title="Remover" aria-label="Remover mídia">&times;</button>
                <button
                    type="button"
                    class="rs-about-gallery-featured rs-project-gallery-featured"
                    title="Destaque: ocupa duas colunas no desktop"
                    aria-label="Destaque (duas colunas no desktop)"
                    aria-pressed="<?php echo $featured ? 'true' : 'false'; ?>"
                >★</button>
            </div>
        </div>
    </div>
    <?php
}

function rs_about_render_gallery_fields(array $shared): void {
    $gallery_ids = rs_about_get_gallery_ids_from_shared($shared);
    $gallery_featured_ids = array_flip(rs_about_get_gallery_featured_ids_from_shared($shared));

    rs_ds_fieldset_open('Galeria (como em Projetos)');
    echo '<p class="rs-ds-help">Imagens, GIFs e vídeos. Arraste para reordenar. ★ marca destaque (duas colunas).</p>';
    echo '<textarea id="rs-about-gallery-json" name="rs_about_gallery_json" hidden>' . esc_textarea(wp_json_encode($gallery_ids) ?: '[]') . '</textarea>';
    echo '<textarea id="rs-about-gallery-featured-json" name="rs_about_gallery_featured_json" hidden>' . esc_textarea(wp_json_encode(array_keys($gallery_featured_ids)) ?: '[]') . '</textarea>';
    echo '<p id="rs-about-gallery-empty" class="rs-ds-help"' . ($gallery_ids ? ' style="display:none;"' : '') . '>Nenhuma mídia na galeria.</p>';
    echo '<div id="rs-about-gallery-list" class="rs-project-gallery-grid">';
    foreach ($gallery_ids as $index => $attachment_id) {
        rs_about_render_gallery_row((int) $index, (int) $attachment_id, false, isset($gallery_featured_ids[(int) $attachment_id]));
    }
    echo '</div>';
    echo '<div id="rs-about-gallery-template" hidden>';
    rs_about_render_gallery_row(0, 0, true);
    echo '</div>';
    echo '<p class="rs-ds-actions"><button type="button" class="button button-primary" id="rs-about-add-gallery">+ Adicionar mídias</button></p>';
    ?>
    <style>
        #rs-about-gallery-list.rs-project-gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin: 12px 0;
        }
        #rs-about-gallery-list .rs-project-gallery-row { margin: 0; }
        #rs-about-gallery-list .rs-project-gallery-row--wide { grid-column: span 2; }
        #rs-about-gallery-list .rs-project-gallery-tile {
            position: relative;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 6px;
            background: #f0f0f1;
            border: 1px solid #c3c4c7;
            cursor: grab;
        }
        #rs-about-gallery-list .rs-project-gallery-tile.is-featured { border-color: #2271b1; box-shadow: inset 0 0 0 1px #2271b1; }
        #rs-about-gallery-list .rs-project-gallery-media,
        #rs-about-gallery-list .rs-project-gallery-preview { position: absolute; inset: 0; }
        #rs-about-gallery-list .rs-project-gallery-preview img,
        #rs-about-gallery-list .rs-project-gallery-preview video {
            width: 100%; height: 100%; object-fit: cover; display: block;
        }
        #rs-about-gallery-list .rs-project-gallery-actions {
            position: absolute; top: 6px; right: 6px; display: flex; gap: 4px; z-index: 2;
        }
        #rs-about-gallery-list .rs-project-gallery-handle,
        #rs-about-gallery-list .rs-project-remove-gallery,
        #rs-about-gallery-list .rs-project-gallery-featured {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border: 0; border-radius: 4px;
            background: rgba(0,0,0,.65); color: #fff; cursor: pointer; opacity: 0;
        }
        #rs-about-gallery-list .rs-project-gallery-handle { cursor: grab; font-size: 12px; }
        #rs-about-gallery-list .rs-project-gallery-tile:hover .rs-project-gallery-handle,
        #rs-about-gallery-list .rs-project-gallery-tile:hover .rs-project-remove-gallery,
        #rs-about-gallery-list .rs-project-gallery-tile:hover .rs-project-gallery-featured,
        #rs-about-gallery-list .rs-project-gallery-tile.is-featured .rs-project-gallery-featured {
            opacity: 1;
        }
        #rs-about-gallery-list .rs-project-gallery-badge {
            position: absolute; left: 6px; bottom: 6px; padding: 2px 6px;
            border-radius: 3px; background: rgba(0,0,0,.7); color: #fff; font-size: 11px;
        }
        #rs-about-gallery-list .rs-project-gallery-placeholder {
            border: 1px dashed #2271b1; border-radius: 6px; background: #f0f6fc; min-height: 80px;
        }
        @media (max-width: 1100px) {
            #rs-about-gallery-list.rs-project-gallery-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 782px) {
            #rs-about-gallery-list.rs-project-gallery-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            #rs-about-gallery-list .rs-project-gallery-handle,
            #rs-about-gallery-list .rs-project-remove-gallery,
            #rs-about-gallery-list .rs-project-gallery-featured { opacity: 1; }
        }
    </style>
    <?php
    rs_ds_fieldset_close();
}

function rs_about_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_about_save', 'rs_about_nonce');

    $canonical = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id((int) $post->ID)
        : (int) $post->ID;
    $i18n = rs_about_i18n_get($canonical);

    echo '<div class="rs-ds-editor rs-about-editor">';
    rs_ds_alert('Um único post. Edite English e Português nas abas.', 'info');

    rs_ds_fieldset_open('Mídia compartilhada do hero');
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
    rs_ds_fieldset_close();

    rs_about_render_gallery_fields(is_array($i18n['shared'] ?? null) ? $i18n['shared'] : []);

    rs_ds_locale_tabs_open(['en' => 'English', 'pt' => 'Português'], 'en');

    rs_ds_locale_panel_open('en', true);
    rs_about_render_locale_fields('en', $i18n['locales']['en']);
    rs_ds_locale_panel_close();

    rs_ds_locale_panel_open('pt', false);
    rs_about_render_locale_fields('pt', $i18n['locales']['pt']);
    rs_ds_locale_panel_close();

    rs_ds_locale_tabs_close();
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

    $gallery_ids = null;
    $gallery_featured = null;
    if (!empty($_POST['rs_about_gallery_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_about_gallery_json']), true);
        if (is_array($decoded)) {
            $gallery_ids = array_values(array_filter(array_map('intval', $decoded)));
        }
    }
    if (!empty($_POST['rs_about_gallery_featured_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_about_gallery_featured_json']), true);
        if (is_array($decoded)) {
            $gallery_featured = array_values(array_filter(array_map('intval', $decoded)));
        }
    }
    if ($gallery_ids !== null) {
        $data['shared']['gallery_ids'] = implode(',', $gallery_ids);
        $in_gallery = array_flip($gallery_ids);
        $featured = [];
        if (is_array($gallery_featured)) {
            foreach ($gallery_featured as $id) {
                if (isset($in_gallery[$id])) {
                    $featured[] = $id;
                }
            }
        }
        $data['shared']['gallery_featured_ids'] = implode(',', $featured);
    } else {
        $data['shared']['gallery_ids'] = (string) ($previous['shared']['gallery_ids'] ?? '');
        $data['shared']['gallery_featured_ids'] = (string) ($previous['shared']['gallery_featured_ids'] ?? '');
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
        let nextGalleryIndex = $('#rs-about-gallery-list .rs-project-gallery-row').length;

        function list(locale) {
            return $('#rs-about-sections-list-' + locale);
        }

        function syncGalleryEmptyState() {
            const hasRows = $('#rs-about-gallery-list .rs-project-gallery-row').length > 0;
            $('#rs-about-gallery-empty').toggle(!hasRows);
        }

        function collectGalleryJson() {
            const ids = [];
            const featured = [];
            $('#rs-about-gallery-list .rs-project-gallery-row').each(function () {
                const row = $(this);
                const imageId = parseInt(row.find('input[data-rs-cap-image]').val(), 10) || 0;
                if (imageId > 0) {
                    ids.push(imageId);
                    if (row.find('.rs-project-gallery-featured-flag').val() === '1') {
                        featured.push(imageId);
                    }
                }
            });
            $('#rs-about-gallery-json').val(JSON.stringify(ids));
            $('#rs-about-gallery-featured-json').val(JSON.stringify(featured));
        }

        function assignGalleryNames(row, index) {
            const fieldId = 'rs_about_gallery_image_' + index;
            row.find('input[data-rs-cap-image]').removeAttr('name').attr('id', fieldId);
            row.find('.rs-project-gallery-featured-flag').removeAttr('name');
            row.find('.rs-media-preview').attr('data-target', fieldId);
        }

        function reindexGallery() {
            $('#rs-about-gallery-list .rs-project-gallery-row').each(function (i) {
                $(this).attr('data-index', String(i));
                assignGalleryNames($(this), i);
            });
            nextGalleryIndex = $('#rs-about-gallery-list .rs-project-gallery-row').length;
            syncGalleryEmptyState();
            collectGalleryJson();
        }

        function syncGalleryRowWide(row) {
            const isWide = row.find('.rs-project-gallery-featured-flag').val() === '1';
            row.toggleClass('rs-project-gallery-row--wide', isWide);
            row.find('.rs-project-gallery-tile').toggleClass('is-featured', isWide);
            row.find('.rs-about-gallery-featured').attr('aria-pressed', isWide ? 'true' : 'false');
        }

        function setGalleryRowMediaSize(row, attachment) {
            row.removeAttr('data-media-width data-media-height');
            const width = parseInt(attachment && attachment.width, 10) || 0;
            const height = parseInt(attachment && attachment.height, 10) || 0;
            if (width > 0) row.attr('data-media-width', String(width));
            if (height > 0) row.attr('data-media-height', String(height));
        }

        function galleryPreviewHtml(attachment) {
            if (!attachment || !attachment.url) return '';
            const mime = attachment.mime || '';
            if (mime.indexOf('video/') === 0 || /\.mp4(\?|$)/i.test(attachment.url)) {
                return '<video src="' + attachment.url + '" muted playsinline preload="metadata"></video><span class="rs-project-gallery-badge" title="Vídeo"><svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg> vídeo</span>';
            }
            const thumb = (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url) || attachment.url;
            const isGif = mime.indexOf('gif') !== -1 || /\.gif(\?|$)/i.test(attachment.url);
            return '<img src="' + thumb + '" alt="" />' + (isGif ? '<span class="rs-project-gallery-badge">gif</span>' : '');
        }

        function appendGalleryAttachment(attachment) {
            if (!attachment || !attachment.id) return;
            const index = nextGalleryIndex;
            const template = $('#rs-about-gallery-template .rs-project-gallery-row').first().clone();
            template.removeAttr('style').attr('data-index', String(index));
            template.find('input[data-rs-cap-image]').val(String(attachment.id));
            template.find('.rs-project-gallery-featured-flag').val('0');
            template.find('.rs-project-gallery-tile').removeClass('is-featured');
            template.find('.rs-about-gallery-featured').attr('aria-pressed', 'false');
            template.removeClass('rs-project-gallery-row--wide');
            setGalleryRowMediaSize(template, attachment);
            template.find('.rs-project-gallery-preview').html(galleryPreviewHtml(attachment));
            assignGalleryNames(template, index);
            $('#rs-about-gallery-list').append(template);
            nextGalleryIndex += 1;
            syncGalleryEmptyState();
            collectGalleryJson();
        }

        if ($.fn.sortable) {
            $('#rs-about-gallery-list').sortable({
                items: '.rs-project-gallery-row',
                handle: '.rs-project-gallery-handle, .rs-project-gallery-tile',
                cancel: '.rs-about-gallery-featured, .rs-about-remove-gallery',
                placeholder: 'rs-project-gallery-placeholder',
                tolerance: 'pointer',
                opacity: 0.9,
                start: function (_event, ui) {
                    ui.placeholder.toggleClass(
                        'rs-project-gallery-row--wide',
                        ui.item.hasClass('rs-project-gallery-row--wide')
                    );
                },
                update: reindexGallery,
            });
        }

        $('#rs-about-add-gallery').on('click', function (event) {
            event.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) return;
            const frame = wp.media({
                title: 'Adicionar mídias à galeria',
                button: { text: 'Adicionar' },
                multiple: true,
                library: { type: ['image', 'video'] },
            });
            frame.on('select', function () {
                const selection = frame.state().get('selection');
                if (!selection) return;
                selection.each(function (model) {
                    appendGalleryAttachment(model.toJSON());
                });
            });
            frame.open();
        });

        $(document).on('click', '.rs-about-remove-gallery', function (event) {
            event.preventDefault();
            $(this).closest('.rs-project-gallery-row').remove();
            reindexGallery();
        });

        $(document).on('click', '.rs-about-gallery-featured', function (event) {
            event.preventDefault();
            const row = $(this).closest('.rs-project-gallery-row');
            const flag = row.find('.rs-project-gallery-featured-flag');
            flag.val(flag.val() === '1' ? '0' : '1');
            syncGalleryRowWide(row);
            collectGalleryJson();
        });

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

        $('#post').on('submit', function () {
            collectAllSectionsJson();
            collectGalleryJson();
        });
        $('#publish, #save-post').on('click', function () {
            window.setTimeout(function () {
                collectAllSectionsJson();
                collectGalleryJson();
            }, 0);
        });
        syncGalleryEmptyState();
        collectGalleryJson();
    });
    </script>
    <?php
}

add_action('admin_footer-post.php', 'rs_about_render_admin_footer_script');
add_action('admin_footer-post-new.php', 'rs_about_render_admin_footer_script');
