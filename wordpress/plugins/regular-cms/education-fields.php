<?php
/**
 * Campos editáveis do CPT education (Educação).
 */

if (defined('RS_EDUCATION_FIELDS_LOADED')) {
    return;
}
define('RS_EDUCATION_FIELDS_LOADED', true);

const RS_EDUCATION_I18N_KEY = 'rs_education_i18n';
const RS_EDUCATION_HERO_IMAGE_KEY = 'rs_education_hero_image_id';
const RS_EDUCATION_HEADLINE_KEY = 'rs_education_headline';
const RS_EDUCATION_SECTIONS_KEY = 'rs_education_sections';
const RS_EDUCATION_INSTITUTIONS_KEY = 'rs_education_institutions';
const RS_EDUCATION_HERO_VIDEO_KEY = 'rs_education_hero_video_id';
const RS_EDUCATION_HERO_VIDEO_URL_LEGACY = 'rs_education_hero_video_url';

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_education_get_legacy_sections(int $post_id): array {
    $decoded = function_exists('rs_meta_get_array')
        ? rs_meta_get_array($post_id, RS_EDUCATION_SECTIONS_KEY)
        : null;

    if (is_array($decoded)) {
        return rs_education_normalize_sections($decoded);
    }

    return [];
}

/**
 * @param array<int, mixed> $sections
 * @return array<int, array{title: string, body: string}>
 */
function rs_education_normalize_sections(array $sections): array {
    $normalized = [];

    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }

        $title = trim((string) ($section['title'] ?? ''));
        $body = trim((string) ($section['body'] ?? $section['text'] ?? ''));

        if ($title === '' && $body === '') {
            continue;
        }

        $normalized[] = [
            'title' => $title !== '' ? $title : 'Seção',
            'body'  => $body,
        ];
    }

    return $normalized;
}

/**
 * @param array<int, array{title: string, body: string}> $sections
 */
function rs_education_sections_to_payload(array $sections): array {
    $payload = [];

    foreach ($sections as $section) {
        $payload[] = [
            'title' => trim($section['title']),
            'body'  => $section['body'],
        ];
    }

    return $payload;
}

/**
 * @return array<int, int>
 */
function rs_education_parse_ids_csv(string $raw): array {
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $raw) ?: [])));
}

/**
 * @param array<int, int> $ids
 * @return array<int, string>
 */
function rs_education_attachment_urls(array $ids): array {
    $urls = [];
    foreach ($ids as $attachment_id) {
        $url = wp_get_attachment_url((int) $attachment_id);
        if ($url) {
            $urls[] = $url;
        }
    }

    return $urls;
}

/**
 * @return array{layout: string, image_ids: string, caption: string, images: array<int, string>}|null
 */
function rs_education_normalize_gallery_for_storage($raw): ?array {
    if (!is_array($raw)) {
        return null;
    }

    $layout = (string) ($raw['layout'] ?? 'pair');
    if (!in_array($layout, ['pair', 'triple', 'grid-2x2'], true)) {
        $layout = 'pair';
    }

    $ids = [];
    if (!empty($raw['image_ids'])) {
        $ids = rs_education_parse_ids_csv((string) $raw['image_ids']);
    } elseif (!empty($raw['images']) && is_array($raw['images'])) {
        // Legado: só URLs — não dá para reconverter IDs.
        $ids = [];
    }

    $caption = trim((string) ($raw['caption'] ?? ''));
    $images = rs_education_attachment_urls($ids);

    if (!$images && !empty($raw['images']) && is_array($raw['images'])) {
        foreach ($raw['images'] as $url) {
            if (is_string($url) && $url !== '') {
                $images[] = $url;
            }
        }
    }

    if (!$images && $caption === '' && empty($ids)) {
        return null;
    }

    return [
        'layout'    => $layout,
        'image_ids' => implode(',', $ids),
        'caption'   => $caption,
        'images'    => array_values($images),
    ];
}

/**
 * @return array{layout: string, images: array<int, string>, caption?: string}|null
 */
function rs_education_gallery_to_payload($raw): ?array {
    $gallery = rs_education_normalize_gallery_for_storage($raw);
    if (!$gallery) {
        return null;
    }

    if (!$gallery['images'] && $gallery['caption'] === '') {
        return null;
    }

    $payload = [
        'layout' => $gallery['layout'],
        'images' => $gallery['images'],
    ];
    if ($gallery['caption'] !== '') {
        $payload['caption'] = $gallery['caption'];
    }

    return $payload;
}

/**
 * Shape editável (com image_ids / logo_id).
 *
 * @return array<int, array<string, mixed>>
 */
function rs_education_normalize_institutions(array $decoded): array {
    $normalized = [];
    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }

        $name = trim(wp_strip_all_tags((string) ($item['name'] ?? '')));
        $logo_id = (int) ($item['logo_id'] ?? 0);
        $description = trim((string) ($item['description'] ?? ''));

        $entry = [
            'name'        => $name,
            'logo_id'     => $logo_id,
            'description' => $description,
            'midGallery'  => [
                'layout'    => 'triple',
                'image_ids' => '',
                'caption'   => '',
            ],
            'bottomGallery' => [
                'layout'    => 'grid-2x2',
                'image_ids' => '',
                'caption'   => '',
            ],
        ];

        foreach (['midGallery', 'bottomGallery'] as $key) {
            $gallery = rs_education_normalize_gallery_for_storage($item[$key] ?? null);
            if ($gallery) {
                $entry[$key] = [
                    'layout'    => $gallery['layout'],
                    'image_ids' => $gallery['image_ids'],
                    'caption'   => $gallery['caption'],
                ];
            }
        }

        if ($entry['midGallery']['image_ids'] === '') {
            $legacy_top = rs_education_normalize_gallery_for_storage($item['topGallery'] ?? null);
            if ($legacy_top && $legacy_top['image_ids'] !== '') {
                $entry['midGallery'] = [
                    'layout'    => $legacy_top['layout'] !== '' ? $legacy_top['layout'] : 'triple',
                    'image_ids' => $legacy_top['image_ids'],
                    'caption'   => $legacy_top['caption'],
                ];
            }
        }

        if ($name === '' && $logo_id <= 0 && $description === ''
            && $entry['midGallery']['image_ids'] === ''
            && $entry['bottomGallery']['image_ids'] === ''
        ) {
            continue;
        }

        $normalized[] = $entry;
    }

    return $normalized;
}

function rs_education_get_legacy_institutions_raw(int $post_id): array {
    $decoded = function_exists('rs_meta_get_array')
        ? rs_meta_get_array($post_id, RS_EDUCATION_INSTITUTIONS_KEY)
        : get_post_meta($post_id, RS_EDUCATION_INSTITUTIONS_KEY, true);

    return rs_education_normalize_institutions(is_array($decoded) ? $decoded : []);
}

function rs_education_default_locale(): array {
    return ['headline' => '', 'sections' => [], 'institutions' => []];
}

function rs_education_i18n_default(): array {
    return [
        'v' => 1,
        'shared' => ['hero_image_id' => 0, 'hero_video_id' => 0],
        'locales' => [
            'en' => rs_education_default_locale(),
            'pt' => rs_education_default_locale(),
        ],
    ];
}

function rs_education_i18n_normalize(array $raw): array {
    $data = rs_education_i18n_default();
    $shared = is_array($raw['shared'] ?? null) ? $raw['shared'] : [];
    $data['shared'] = [
        'hero_image_id' => (int) ($shared['hero_image_id'] ?? 0),
        'hero_video_id' => (int) ($shared['hero_video_id'] ?? 0),
    ];

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $data['locales'][$locale] = [
            'headline' => trim((string) ($loc['headline'] ?? '')),
            'sections' => rs_education_normalize_sections(
                is_array($loc['sections'] ?? null) ? $loc['sections'] : []
            ),
            'institutions' => rs_education_normalize_institutions(
                is_array($loc['institutions'] ?? null) ? $loc['institutions'] : []
            ),
        ];
    }

    return $data;
}

function rs_education_locale_from_legacy_post(int $post_id): array {
    if ($post_id <= 0) {
        return rs_education_default_locale();
    }

    return [
        'headline' => trim((string) get_post_meta($post_id, RS_EDUCATION_HEADLINE_KEY, true)),
        'sections' => rs_education_get_legacy_sections($post_id),
        'institutions' => rs_education_get_legacy_institutions_raw($post_id),
    ];
}

function rs_education_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;
    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_EDUCATION_I18N_KEY)
        : get_post_meta($post_id, RS_EDUCATION_I18N_KEY, true);
    if (is_array($raw)) {
        return rs_education_i18n_normalize($raw);
    }

    $data = rs_education_i18n_default();
    $data['shared'] = [
        'hero_image_id' => (int) get_post_meta($post_id, RS_EDUCATION_HERO_IMAGE_KEY, true),
        'hero_video_id' => (int) get_post_meta($post_id, RS_EDUCATION_HERO_VIDEO_KEY, true),
    ];
    $data['locales']['en'] = rs_education_locale_from_legacy_post($post_id);
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_education_locale_from_legacy_post($pt_id);
    }

    return rs_education_i18n_normalize($data);
}

function rs_education_get_sections(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_education_i18n_get($post_id);
    return rs_education_normalize_sections(
        is_array($data['locales'][$locale]['sections'] ?? null)
            ? $data['locales'][$locale]['sections']
            : []
    );
}

function rs_education_get_institutions_raw(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_education_i18n_get($post_id);
    return rs_education_normalize_institutions(
        is_array($data['locales'][$locale]['institutions'] ?? null)
            ? $data['locales'][$locale]['institutions']
            : []
    );
}

/**
 * @return array<int, array<string, mixed>>
 */
function rs_education_institutions_to_payload(array $institutions): array {
    $payload = [];

    foreach ($institutions as $item) {
        if (!is_array($item)) {
            continue;
        }

        $name = trim(wp_strip_all_tags((string) ($item['name'] ?? '')));
        if ($name === '') {
            continue;
        }

        $logo_id = (int) ($item['logo_id'] ?? 0);
        $logo_url = $logo_id > 0 ? (string) wp_get_attachment_url($logo_id) : '';
        if ($logo_url === '' && !empty($item['logo']) && is_string($item['logo'])) {
            $logo_url = $item['logo'];
        }

        $entry = ['name' => $name];
        if ($logo_url !== '') {
            $entry['logo'] = $logo_url;
        }

        $description = trim((string) ($item['description'] ?? ''));
        if ($description !== '') {
            $entry['description'] = $description;
        }

        foreach (['topGallery', 'midGallery', 'bottomGallery'] as $key) {
            $gallery = rs_education_gallery_to_payload($item[$key] ?? null);
            if ($gallery) {
                $entry[$key] = $gallery;
            }
        }

        // Conteúdo legado em topGallery passa a midGallery se mid estiver vazio.
        if (empty($entry['midGallery']) && !empty($entry['topGallery'])) {
            $entry['midGallery'] = $entry['topGallery'];
        }
        unset($entry['topGallery']);

        $payload[] = $entry;
    }

    return $payload;
}

function rs_education_meta_to_payload(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;
    $data = rs_education_i18n_get($post_id);
    $loc = $data['locales'][$locale] ?? rs_education_default_locale();
    // Sem fallback EN→PT no headline: front usa defaults PT quando vazio.
    if ($locale === 'pt') {
        $en = $data['locales']['en'] ?? rs_education_default_locale();
        if (empty($loc['sections'])) {
            $loc['sections'] = $en['sections'] ?? [];
        }
        if (empty($loc['institutions'])) {
            $loc['institutions'] = $en['institutions'] ?? [];
        }
    }
    $image_id = (int) ($data['shared']['hero_image_id'] ?? 0);
    $video_id = (int) ($data['shared']['hero_video_id'] ?? 0);
    if ($image_id <= 0) {
        $image_id = (int) get_post_meta($post_id, RS_EDUCATION_HERO_IMAGE_KEY, true);
    }
    if ($video_id <= 0) {
        $video_id = (int) get_post_meta($post_id, RS_EDUCATION_HERO_VIDEO_KEY, true);
    }
    if ($image_id <= 0 && function_exists('rs_page_heroes_get_image_id')) {
        $image_id = (int) rs_page_heroes_get_image_id('education');
    }
    if ($video_id <= 0 && function_exists('rs_page_heroes_get_video_id')) {
        $video_id = (int) rs_page_heroes_get_video_id('education');
    }
    $video_url = $video_id > 0 ? (string) wp_get_attachment_url($video_id) : '';
    if ($video_url === '') {
        $video_url = trim((string) get_post_meta($post_id, RS_EDUCATION_HERO_VIDEO_URL_LEGACY, true));
    }

    return [
        'heroImage' => $image_id > 0 ? (string) wp_get_attachment_url($image_id) : '',
        'heroVideo' => $video_url,
        'headline' => trim((string) ($loc['headline'] ?? '')),
        'accordionSections' => rs_education_sections_to_payload(
            rs_education_normalize_sections(is_array($loc['sections'] ?? null) ? $loc['sections'] : [])
        ),
        'institutions' => rs_education_institutions_to_payload(
            rs_education_normalize_institutions(is_array($loc['institutions'] ?? null) ? $loc['institutions'] : [])
        ),
    ];
}

function rs_education_get_post_id_by_locale(string $locale = 'en'): int {
    return function_exists('rs_section_i18n_canonical_id')
        ? rs_section_i18n_canonical_id('education')
        : 0;
}

function rs_education_sync_legacy_meta(int $post_id, array $data): void {
    $en = is_array($data['locales']['en'] ?? null) ? $data['locales']['en'] : rs_education_default_locale();
    $image_id = (int) ($data['shared']['hero_image_id'] ?? 0);
    $video_id = (int) ($data['shared']['hero_video_id'] ?? 0);
    delete_post_meta($post_id, RS_EDUCATION_HERO_IMAGE_KEY);
    delete_post_meta($post_id, RS_EDUCATION_HERO_VIDEO_KEY);
    update_post_meta($post_id, RS_EDUCATION_HERO_IMAGE_KEY, $image_id);
    update_post_meta($post_id, RS_EDUCATION_HERO_VIDEO_KEY, $video_id);
    update_post_meta($post_id, RS_EDUCATION_HEADLINE_KEY, (string) ($en['headline'] ?? ''));
    $sections = rs_education_normalize_sections(is_array($en['sections'] ?? null) ? $en['sections'] : []);
    $institutions = rs_education_normalize_institutions(is_array($en['institutions'] ?? null) ? $en['institutions'] : []);
    if (function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, RS_EDUCATION_SECTIONS_KEY, $sections);
        rs_meta_update_array($post_id, RS_EDUCATION_INSTITUTIONS_KEY, $institutions);
    } else {
        update_post_meta($post_id, RS_EDUCATION_SECTIONS_KEY, $sections);
        update_post_meta($post_id, RS_EDUCATION_INSTITUTIONS_KEY, $institutions);
    }

    // Mantém page-heroes alinhado (fallback legado).
    if (function_exists('rs_page_heroes_get_post_id')) {
        $heroes_id = (int) rs_page_heroes_get_post_id();
        if ($heroes_id > 0) {
            update_post_meta($heroes_id, RS_PAGE_HERO_EDUCATION_IMAGE_KEY, (string) max(0, $image_id));
            update_post_meta($heroes_id, RS_PAGE_HERO_EDUCATION_VIDEO_KEY, (string) max(0, $video_id));
        }
    }
}

function rs_education_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }

    $already_migrated = (bool) get_option('rs_education_i18n_migrated_v1');
    $post_id = rs_section_i18n_migrate_twins(
        'education',
        RS_EDUCATION_I18N_KEY,
        'rs_education_i18n_migrated_v1',
        'Education',
        static function (int $post_id, string $locale): array {
            return rs_education_locale_from_legacy_post($post_id);
        },
        'rs_education_i18n_normalize'
    );
    if (!$already_migrated && $post_id > 0) {
        $data = rs_education_i18n_get($post_id);
        $data['shared'] = [
            'hero_image_id' => (int) get_post_meta($post_id, RS_EDUCATION_HERO_IMAGE_KEY, true),
            'hero_video_id' => (int) get_post_meta($post_id, RS_EDUCATION_HERO_VIDEO_KEY, true),
        ];
        $data = rs_education_i18n_normalize($data);
        rs_section_i18n_save($post_id, RS_EDUCATION_I18N_KEY, $data);
        rs_education_sync_legacy_meta($post_id, $data);
    }
}

add_action('init', function () {
    register_post_meta('education', RS_EDUCATION_I18N_KEY, [
        'single' => true,
        'type' => 'array',
        'show_in_rest' => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
    foreach ([
        RS_EDUCATION_HERO_IMAGE_KEY,
        RS_EDUCATION_HERO_VIDEO_KEY,
        RS_EDUCATION_HERO_VIDEO_URL_LEGACY,
        RS_EDUCATION_HEADLINE_KEY,
        RS_EDUCATION_SECTIONS_KEY,
        RS_EDUCATION_INSTITUTIONS_KEY,
    ] as $key) {
        register_post_meta('education', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);

add_action('init', 'rs_education_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('education', 'education_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request')
                ? rs_section_i18n_locale_from_request($request)
                : 'en';
            $post_id = function_exists('rs_section_i18n_resolve_id')
                ? rs_section_i18n_resolve_id((int) $post['id'])
                : (int) $post['id'];
            return rs_education_meta_to_payload($post_id, $locale);
        },
        'schema' => [
            'description' => 'Dados estruturados da página Educação',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_education', function () {
    add_meta_box(
        'rs_education_fields',
        'Conteúdo da página Educação',
        'rs_education_render_i18n_meta_box',
        'education',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'education', 'normal');
}, 10);

function rs_education_render_section_row(int $index, array $section, bool $is_template = false, string $locale = 'en'): void {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $title = $section['title'] ?? '';
    $body = $section['body'] ?? '';
    $row_index = $is_template ? '__INDEX__' : (string) $index;
    $name_prefix = 'rs_education_i18n[' . $locale . '][sections][' . $row_index . ']';
    $editor_id = 'rs_education_section_text_' . $locale . '_' . $row_index;
    $display = $is_template ? ' style="display:none;"' : '';
    $is_open = !$is_template && (int) $index === 0;
    $head_title = $title !== '' ? $title : 'Seção';
    $row_class = 'rs-metabox-accordion-item' . ($is_open ? ' is-open' : '');
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
                <span class="rs-metabox-accordion-head-title"><?php echo esc_html($head_title); ?></span>
            </button>
            <button type="button" class="button-link-delete rs-metabox-accordion-remove rs-education-remove-section">Remover</button>
        </div>
        <div class="rs-metabox-accordion-panel">
            <div style="margin:0 0 12px;">
                <label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>
                <input
                    type="text"
                    style="width:100%;"
                    class="rs-metabox-accordion-title rs-education-section-title"
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
                        class="rs-education-section-text large-text"
                        style="width:100%;min-height:120px;"
                        id="<?php echo esc_attr($editor_id); ?>"
                    ></textarea>
                <?php else : ?>
                    <?php rs_render_rich_text_field($editor_id, $name_prefix . '[body]', $body, 'paragraph'); ?>
                <?php endif; ?>
            </div>
        </div>
    </fieldset>
    <?php
}

/**
 * @param array<string, mixed> $gallery
 */
function rs_education_render_gallery_fields(string $name_prefix, string $label, array $gallery, string $field_suffix, bool $include_name): void {
    $layout = (string) ($gallery['layout'] ?? 'pair');
    $image_ids = (string) ($gallery['image_ids'] ?? '');
    $caption = (string) ($gallery['caption'] ?? '');
    $ids = rs_education_parse_ids_csv($image_ids);
    ?>
    <div class="rs-education-gallery-block" style="margin:0 0 14px;padding:12px;border:1px dashed #c3c4c7;border-radius:4px;background:#fcfcfc;">
        <p style="margin:0 0 10px;font-weight:600;"><?php echo esc_html($label); ?></p>

        <p style="margin:0 0 10px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Layout</label>
            <select
                class="rs-education-gallery-layout"
                data-gallery="<?php echo esc_attr($field_suffix); ?>"
                <?php if ($include_name) : ?>name="<?php echo esc_attr($name_prefix . '[' . $field_suffix . '][layout]'); ?>"<?php endif; ?>
            >
                <option value="pair" <?php selected($layout, 'pair'); ?>>2 imagens (lado a lado)</option>
                <option value="triple" <?php selected($layout, 'triple'); ?>>3 imagens verticais</option>
                <option value="grid-2x2" <?php selected($layout, 'grid-2x2'); ?>>Grade 2×2</option>
            </select>
        </p>

        <input
            type="hidden"
            class="rs-education-gallery-ids"
            data-gallery="<?php echo esc_attr($field_suffix); ?>"
            <?php if ($include_name) : ?>name="<?php echo esc_attr($name_prefix . '[' . $field_suffix . '][image_ids]'); ?>"<?php endif; ?>
            value="<?php echo esc_attr($image_ids); ?>"
        />

        <p style="margin:0 0 8px;color:#646970;font-size:12px;">
            Opcional. Arraste as miniaturas para definir a ordem. Use <strong>+ Adicionar imagens</strong> para incluir várias de uma vez.
        </p>
        <p class="rs-education-gallery-empty description" data-gallery="<?php echo esc_attr($field_suffix); ?>"<?php echo $ids ? ' style="display:none;"' : ''; ?>>
            Nenhuma imagem na galeria.
        </p>
        <div class="rs-education-gallery-grid" data-gallery="<?php echo esc_attr($field_suffix); ?>">
            <?php foreach ($ids as $attachment_id) :
                $url = (string) wp_get_attachment_url($attachment_id);
                $mime = (string) get_post_mime_type($attachment_id);
                $is_video = $mime !== '' && str_starts_with($mime, 'video/');
                $thumb = !$is_video
                    ? (string) (wp_get_attachment_image_url($attachment_id, 'medium') ?: $url)
                    : '';
                if ($url === '' && $thumb === '') {
                    continue;
                }
                ?>
                <div class="rs-education-gallery-item" data-id="<?php echo esc_attr((string) $attachment_id); ?>">
                    <div class="rs-education-gallery-tile">
                        <span class="rs-education-gallery-handle" title="Arrastar para reordenar" aria-hidden="true">⋮⋮</span>
                        <button type="button" class="rs-education-remove-gallery-item" title="Remover" aria-label="Remover imagem">&times;</button>
                        <div class="rs-education-gallery-thumb rs-media-thumb">
                            <?php if ($is_video) : ?>
                                <video src="<?php echo esc_url($url); ?>" muted playsinline preload="metadata"></video>
                                <span class="rs-education-gallery-badge">vídeo</span>
                            <?php else : ?>
                                <img src="<?php echo esc_url($thumb ?: $url); ?>" alt="" />
                                <?php if (str_contains(strtolower($mime), 'gif') || str_ends_with(strtolower($url), '.gif')) : ?>
                                    <span class="rs-education-gallery-badge">gif</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="margin:10px 0 12px;">
            <button type="button" class="button button-secondary rs-education-add-gallery-images" data-gallery="<?php echo esc_attr($field_suffix); ?>">+ Adicionar imagens</button>
        </p>

        <p style="margin:0;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Legenda (opcional)</label>
            <input
                type="text"
                class="rs-education-gallery-caption"
                data-gallery="<?php echo esc_attr($field_suffix); ?>"
                style="width:100%;"
                <?php if ($include_name) : ?>name="<?php echo esc_attr($name_prefix . '[' . $field_suffix . '][caption]'); ?>"<?php endif; ?>
                value="<?php echo esc_attr($caption); ?>"
            />
        </p>
    </div>
    <?php
}

function rs_education_render_institution_row(int $index, array $institution, bool $is_template = false, string $locale = 'en'): void {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $name = (string) ($institution['name'] ?? '');
    $logo_id = (int) ($institution['logo_id'] ?? 0);
    $description = (string) ($institution['description'] ?? '');
    $row_index = $is_template ? '__INDEX__' : (string) $index;
    $name_prefix = 'rs_education_i18n[' . $locale . '][institutions][' . $row_index . ']';
    $logo_field_id = 'rs_education_inst_logo_' . $locale . '_' . $row_index;

    $mid_raw = is_array($institution['midGallery'] ?? null) ? $institution['midGallery'] : null;
    $top_raw = is_array($institution['topGallery'] ?? null) ? $institution['topGallery'] : null;
    $mid_ids = is_array($mid_raw) ? (string) ($mid_raw['image_ids'] ?? '') : '';
    $mid = $mid_ids !== '' && $mid_raw
        ? $mid_raw
        : ($top_raw ?: ['layout' => 'triple', 'image_ids' => '', 'caption' => '']);
    if (!isset($mid['layout']) || $mid['layout'] === '') {
        $mid['layout'] = 'triple';
    }
    $bottom = is_array($institution['bottomGallery'] ?? null)
        ? $institution['bottomGallery']
        : ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''];
    $display = $is_template ? ' display:none;' : '';
    $is_open = !$is_template && (int) $index === 0;
    $head_title = $name !== '' ? $name : 'Instituição';
    $row_class = 'rs-metabox-accordion-item' . ($is_open ? ' is-open' : '');
    $logo_thumb = '';
    if (!$is_template && $logo_id > 0) {
        $logo_url = wp_get_attachment_image_url($logo_id, 'thumbnail');
        if ($logo_url) {
            $logo_thumb = '<span class="rs-metabox-accordion-head-thumb"><img src="' . esc_url($logo_url) . '" alt="" /></span>';
        }
    }
    ?>
    <fieldset
        class="<?php echo esc_attr($row_class); ?>"
        data-index="<?php echo esc_attr($row_index); ?>"
        data-locale="<?php echo esc_attr($locale); ?>"
        style="margin:0 0 10px;<?php echo esc_attr($display); ?>"
    >
        <div class="rs-metabox-accordion-head">
            <span class="rs-metabox-accordion-drag" title="Arrastar para reordenar" aria-hidden="true">⋮⋮</span>
            <button type="button" class="rs-metabox-accordion-toggle" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
                <?php echo $logo_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <span class="rs-metabox-accordion-head-title rs-education-institution-head-title"><?php echo esc_html($head_title); ?></span>
            </button>
            <button type="button" class="button-link-delete rs-metabox-accordion-remove rs-education-remove-institution">Remover</button>
        </div>
        <div class="rs-metabox-accordion-panel">
        <p style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Nome</label>
            <input
                type="text"
                class="rs-education-institution-name"
                style="width:100%;"
                <?php if (!$is_template) : ?>name="<?php echo esc_attr($name_prefix); ?>[name]"<?php endif; ?>
                value="<?php echo esc_attr($name); ?>"
                placeholder="Mackenzie University (Brazil)"
            />
        </p>

        <?php
        rs_render_media_field(
            $name_prefix . '[logo_id]',
            'Logo',
            $logo_id,
            $logo_field_id,
            !$is_template,
            'image'
        );
        ?>

        <?php
        rs_education_render_gallery_fields(
            $name_prefix,
            'Galeria de fotos (opcional)',
            $mid,
            'midGallery',
            !$is_template
        );
        ?>

        <p style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Texto</label>
            <textarea
                class="rs-education-institution-description large-text"
                style="width:100%;min-height:110px;"
                <?php if (!$is_template) : ?>name="<?php echo esc_attr($name_prefix); ?>[description]"<?php endif; ?>
                placeholder="Descrição da parceria…"
            ><?php echo esc_textarea($description); ?></textarea>
        </p>

        <?php
        rs_education_render_gallery_fields(
            $name_prefix,
            'Galeria após o texto (opcional)',
            $bottom,
            'bottomGallery',
            !$is_template
        );
        ?>
        </div>
    </fieldset>
    <?php
}

function rs_education_render_locale_fields(string $locale, array $loc): void {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $sections = rs_education_normalize_sections(is_array($loc['sections'] ?? null) ? $loc['sections'] : []);
    $institutions = rs_education_normalize_institutions(is_array($loc['institutions'] ?? null) ? $loc['institutions'] : []);
    if (!$sections) {
        $sections = [['title' => '', 'body' => '']];
    }
    if (!$institutions) {
        $institutions = [[
            'name' => '',
            'logo_id' => 0,
            'description' => '',
            'midGallery' => ['layout' => 'triple', 'image_ids' => '', 'caption' => ''],
            'bottomGallery' => ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''],
        ]];
    }

    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Headline</strong></legend>';
    rs_render_rich_text_field(
        'rs_education_headline_' . $locale,
        'rs_education_i18n[' . $locale . '][headline]',
        (string) ($loc['headline'] ?? ''),
        'inline'
    );
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Use o botão <strong>B</strong> para destacar palavras.</p></fieldset>';

    echo '<div id="rs-education-sections-accordion-' . esc_attr($locale) . '" data-rs-accordion data-locale="' . esc_attr($locale) . '">';
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Seções do acordeão</strong></legend>';
    echo '<div id="rs-education-sections-list-' . esc_attr($locale) . '" data-rs-accordion-list>';
    foreach ($sections as $index => $section) {
        rs_education_render_section_row((int) $index, $section, false, $locale);
    }
    echo '</div><div id="rs-education-section-template-' . esc_attr($locale) . '" hidden>';
    rs_education_render_section_row(0, ['title' => '', 'body' => ''], true, $locale);
    echo '</div><p style="margin:12px 0 0;"><button type="button" class="button button-secondary rs-education-add-section" data-locale="' . esc_attr($locale) . '">+ Adicionar seção</button></p>';
    echo '<input type="hidden" id="rs-education-sections-' . esc_attr($locale) . '-json" name="rs_education_sections_' . esc_attr($locale) . '_json" value="" />';
    echo '</fieldset></div>';

    echo '<div id="rs-education-institutions-accordion-' . esc_attr($locale) . '" data-rs-accordion data-locale="' . esc_attr($locale) . '">';
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Instituições</strong></legend>';
    echo '<div id="rs-education-institutions-list-' . esc_attr($locale) . '" data-rs-accordion-list>';
    foreach ($institutions as $index => $institution) {
        rs_education_render_institution_row((int) $index, $institution, false, $locale);
    }
    echo '</div><div id="rs-education-institution-template-' . esc_attr($locale) . '" hidden>';
    rs_education_render_institution_row(0, [
        'name' => '',
        'logo_id' => 0,
        'description' => '',
        'midGallery' => ['layout' => 'triple', 'image_ids' => '', 'caption' => ''],
        'bottomGallery' => ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''],
    ], true, $locale);
    echo '</div><p style="margin:12px 0 0;"><button type="button" class="button button-primary rs-education-add-institution" data-locale="' . esc_attr($locale) . '">+ Adicionar instituição</button></p>';
    echo '<input type="hidden" id="rs-education-institutions-' . esc_attr($locale) . '-json" name="rs_education_institutions_' . esc_attr($locale) . '_json" value="" />';
    echo '</fieldset></div>';
}

function rs_education_render_i18n_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_education_save', 'rs_education_nonce');
    $canonical = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id((int) $post->ID)
        : (int) $post->ID;
    $i18n = rs_education_i18n_get($canonical);

    echo '<p style="margin-top:0;color:#646970;">Um único post. Edite <strong>English</strong> e <strong>Português</strong> nas abas. ' . (function_exists('rs_plugin_version_markup') ? rs_plugin_version_markup() : '') . '</p>';
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Mídia compartilhada do hero</strong></legend>';
    rs_section_shared_hero_render_fields(
        $canonical,
        $i18n['shared'],
        'rs_education',
        'rs_education_shared',
        RS_EDUCATION_HERO_IMAGE_KEY,
        RS_EDUCATION_HERO_VIDEO_KEY,
        'rs_education_shared_hero_image',
        'rs_education_shared_hero_video'
    );
    echo '</fieldset>';

    echo '<div class="rs-metabox-tabs" data-rs-tabs><div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button>';
    echo '</div><div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_education_render_locale_fields('en', $i18n['locales']['en']);
    echo '</div><div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_education_render_locale_fields('pt', $i18n['locales']['pt']);
    echo '</div></div>';
}

function rs_education_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_education_save', 'rs_education_nonce');

    $headline = (string) get_post_meta($post->ID, RS_EDUCATION_HEADLINE_KEY, true);
    $sections = rs_education_get_sections($post->ID);
    $institutions = rs_education_get_institutions_raw($post->ID);

    if (!$sections) {
        $sections = [['title' => '', 'body' => '']];
    }

    if (!$institutions) {
        $institutions = [[
            'name'          => '',
            'logo_id'       => 0,
            'description'   => '',
            'midGallery'    => ['layout' => 'triple', 'image_ids' => '', 'caption' => ''],
            'bottomGallery' => ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''],
        ]];
    }

    echo '<p style="margin-top:0;color:#646970;">Um post por idioma (slug <code>en</code> / <code>pt</code>). Tudo abaixo alimenta a página <code>/education</code>. ' . rs_plugin_version_markup() . '</p>';
    $section_count = count($sections);
    $institution_count = count($institutions);

    echo '<div class="rs-metabox-tabs" data-rs-tabs>';
    echo '<div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="base">Conteúdo Base</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="accordion">Acordeão (' . (int) $section_count . ')</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="institutions">Instituições (' . (int) $institution_count . ')</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="media">Mídia</button>';
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel is-active" data-tab="base" role="tabpanel">';
    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Headline</strong></legend>';
    rs_render_rich_text_field(RS_EDUCATION_HEADLINE_KEY, RS_EDUCATION_HEADLINE_KEY, $headline, 'inline');
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Use o botão <strong>B</strong> para destacar palavras.</p>';
    echo '</fieldset>';
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel" data-tab="accordion" role="tabpanel" hidden>';
    echo '<div id="rs-education-sections-accordion" data-rs-accordion>';
    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Seções do acordeão</strong></legend>';
    echo '<div id="rs-education-sections-list" data-rs-accordion-list>';
    foreach ($sections as $index => $section) {
        rs_education_render_section_row((int) $index, $section);
    }
    echo '</div>';
    echo '<div id="rs-education-section-template" hidden>';
    rs_education_render_section_row(0, ['title' => '', 'body' => ''], true);
    echo '</div>';
    echo '<p style="margin:12px 0 0;"><button type="button" class="button button-secondary" id="rs-education-add-section">+ Adicionar seção</button></p>';
    echo '<input type="hidden" id="rs-education-sections-json" name="rs_education_sections_json" value="" />';
    echo '</fieldset>';
    echo '</div>';
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel" data-tab="institutions" role="tabpanel" hidden>';
    echo '<div id="rs-education-institutions-accordion" data-rs-accordion>';
    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Instituições</strong></legend>';
    echo '<p style="margin:0 0 12px;color:#646970;font-size:12px;">Ordem no site: <strong>logo + nome</strong> → <strong>galeria</strong> (opcional) → <strong>texto</strong> → <strong>galeria após o texto</strong> (opcional).</p>';
    echo '<div id="rs-education-institutions-list" data-rs-accordion-list>';
    foreach ($institutions as $index => $institution) {
        rs_education_render_institution_row((int) $index, $institution);
    }
    echo '</div>';
    echo '<div id="rs-education-institution-template" hidden>';
    rs_education_render_institution_row(0, [
        'name'          => '',
        'logo_id'       => 0,
        'description'   => '',
        'midGallery'    => ['layout' => 'triple', 'image_ids' => '', 'caption' => ''],
        'bottomGallery' => ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''],
    ], true);
    echo '</div>';
    echo '<p style="margin:12px 0 0;"><button type="button" class="button button-primary" id="rs-education-add-institution">+ Adicionar instituição</button></p>';
    echo '<input type="hidden" id="rs-education-institutions-json" name="rs_education_institutions_json" value="" />';
    echo '</fieldset>';
    echo '</div>';
    echo '</div>';

    echo '<div class="rs-metabox-tabpanel" data-tab="media" role="tabpanel" hidden>';
    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Hero</strong></legend>';
    if (function_exists('rs_section_render_hero_fields')) {
        rs_section_render_hero_fields($post->ID, RS_EDUCATION_HERO_IMAGE_KEY, RS_EDUCATION_HERO_VIDEO_KEY);
    }
    echo '</fieldset>';
    echo '</div>';

    echo '</div>';
}

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_education_parse_sections_from_request(string $locale = 'en'): array {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $json_key = 'rs_education_sections_' . $locale . '_json';
    if (!empty($_POST[$json_key])) {
        $decoded = json_decode(wp_unslash((string) $_POST[$json_key]), true);
        if (is_array($decoded) && $decoded !== []) {
            $normalized = rs_education_normalize_sections($decoded);
            if ($normalized !== []) {
                return $normalized;
            }
        }
    }

    $raw_i18n = isset($_POST['rs_education_i18n']) && is_array($_POST['rs_education_i18n'])
        ? wp_unslash($_POST['rs_education_i18n'])
        : [];
    $raw_locale = is_array($raw_i18n[$locale] ?? null) ? $raw_i18n[$locale] : [];
    if (!isset($raw_locale['sections']) || !is_array($raw_locale['sections'])) {
        return [];
    }

    return rs_education_normalize_sections($raw_locale['sections']);
}

/**
 * @return array<int, array<string, mixed>>
 */
function rs_education_parse_institutions_from_request(string $locale = 'en'): array {
    $locale = $locale === 'pt' ? 'pt' : 'en';
    $json_key = 'rs_education_institutions_' . $locale . '_json';
    if (!empty($_POST[$json_key])) {
        $decoded = json_decode(wp_unslash((string) $_POST[$json_key]), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $raw_i18n = isset($_POST['rs_education_i18n']) && is_array($_POST['rs_education_i18n'])
        ? wp_unslash($_POST['rs_education_i18n'])
        : [];
    $raw_locale = is_array($raw_i18n[$locale] ?? null) ? $raw_i18n[$locale] : [];
    if (!isset($raw_locale['institutions']) || !is_array($raw_locale['institutions'])) {
        return [];
    }

    $items = [];
    foreach ($raw_locale['institutions'] as $key => $row) {
        if ($key === '__INDEX__' || !is_array($row)) {
            continue;
        }
        $items[] = $row;
    }

    return $items;
}

function rs_education_sanitize_institutions(array $institutions): array {
    $clean = [];
    foreach ($institutions as $item) {
        if (!is_array($item)) {
            continue;
        }
        $entry = [
            'name' => trim(wp_strip_all_tags((string) ($item['name'] ?? ''))),
            'logo_id' => (int) ($item['logo_id'] ?? 0),
            'description' => wp_kses_post((string) ($item['description'] ?? '')),
        ];
        foreach (['midGallery', 'bottomGallery'] as $key) {
            $gallery = rs_education_normalize_gallery_for_storage($item[$key] ?? null);
            if ($gallery) {
                $entry[$key] = [
                    'layout' => $gallery['layout'],
                    'image_ids' => $gallery['image_ids'],
                    'caption' => sanitize_text_field($gallery['caption']),
                ];
            }
        }
        if (empty($entry['midGallery']['image_ids'] ?? '')) {
            $legacy_top = rs_education_normalize_gallery_for_storage($item['topGallery'] ?? null);
            if ($legacy_top && $legacy_top['image_ids'] !== '') {
                $entry['midGallery'] = [
                    'layout' => $legacy_top['layout'] ?: 'triple',
                    'image_ids' => $legacy_top['image_ids'],
                    'caption' => sanitize_text_field($legacy_top['caption']),
                ];
            }
        }
        $clean[] = $entry;
    }

    return rs_education_normalize_institutions($clean);
}

add_action('save_post_education', function (int $post_id) {
    if (!isset($_POST['rs_education_nonce']) || !wp_verify_nonce($_POST['rs_education_nonce'], 'rs_education_save')) {
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
    $previous = rs_education_i18n_get($post_id);
    $data = rs_section_shared_hero_parse_from_request($previous, 'rs_education', 'rs_education_shared');
    $raw = isset($_POST['rs_education_i18n']) && is_array($_POST['rs_education_i18n'])
        ? wp_unslash($_POST['rs_education_i18n'])
        : [];
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        // Headline: TinyMCE às vezes só preenche o textarea após triggerSave (feito no JS).
        $headline = (string) ($loc['headline'] ?? '');
        if ($headline === '' && isset($_POST['rs_education_headline_' . $locale])) {
            $headline = (string) wp_unslash($_POST['rs_education_headline_' . $locale]);
        }
        $data['locales'][$locale] = [
            'headline' => wp_kses_post($headline),
            'sections' => rs_education_parse_sections_from_request($locale),
            'institutions' => rs_education_sanitize_institutions(
                rs_education_parse_institutions_from_request($locale)
            ),
        ];
    }

    $normalized = rs_education_i18n_normalize(
        rs_section_shared_hero_guard_against_wipe(
            $data,
            $previous,
            'rs_education',
            $post_id,
            RS_EDUCATION_HERO_IMAGE_KEY,
            RS_EDUCATION_HERO_VIDEO_KEY,
            'education'
        )
    );
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_EDUCATION_I18N_KEY, $normalized);
    } else {
        delete_post_meta($post_id, RS_EDUCATION_I18N_KEY);
        update_post_meta($post_id, RS_EDUCATION_I18N_KEY, $normalized);
    }
    rs_education_sync_legacy_meta($post_id, $normalized);
}, 10);

function rs_copy_education_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}

add_action('admin_footer-post.php', 'rs_education_admin_footer_script');
add_action('admin_footer-post-new.php', 'rs_education_admin_footer_script');
add_action('admin_footer-post.php', 'rs_education_i18n_admin_footer_script', 20);
add_action('admin_footer-post-new.php', 'rs_education_i18n_admin_footer_script', 20);

add_action('admin_enqueue_scripts', function (string $hook): void {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'education') {
        return;
    }
    wp_enqueue_script('jquery-ui-sortable');
});

function rs_education_admin_footer_script(): void {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'education') {
        return;
    }
    ?>
    <style>
        .rs-education-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
            gap: 8px;
            margin: 0 0 4px;
        }
        .rs-education-gallery-item { margin: 0; }
        .rs-education-gallery-tile {
            position: relative;
            aspect-ratio: 1;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            background: #f6f7f7;
            overflow: hidden;
            cursor: grab;
        }
        .rs-education-gallery-tile:active { cursor: grabbing; }
        .rs-education-gallery-handle {
            position: absolute;
            top: 4px;
            left: 4px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.55);
            color: #fff;
            font-size: 10px;
            line-height: 1;
            letter-spacing: -1px;
            user-select: none;
        }
        .rs-education-remove-gallery-item {
            position: absolute;
            top: 2px;
            right: 2px;
            z-index: 2;
            width: 22px;
            height: 22px;
            padding: 0;
            border: 0;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.55);
            color: #fff;
            font-size: 14px;
            line-height: 1;
            cursor: pointer;
        }
        .rs-education-remove-gallery-item:hover { background: #b32d2e; }
        .rs-education-gallery-thumb {
            display: block;
            width: 100%;
            height: 100%;
        }
        .rs-education-gallery-thumb img,
        .rs-education-gallery-thumb video {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .rs-education-gallery-badge {
            position: absolute;
            bottom: 4px;
            left: 4px;
            z-index: 2;
            padding: 1px 5px;
            border-radius: 3px;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .rs-education-gallery-placeholder {
            aspect-ratio: 1;
            border: 2px dashed #2271b1;
            border-radius: 6px;
            background: #f0f6fc;
        }
        .rs-education-gallery-item.ui-sortable-helper .rs-education-gallery-tile {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.16);
        }
    </style>
    <script>
    jQuery(function ($) {
        const paragraphEditorSettings = <?php echo wp_json_encode(rs_rich_text_js_settings('paragraph')); ?>;
        let nextSectionIndex = $('#rs-education-sections-list .rs-metabox-accordion-item').length;
        let nextInstitutionIndex = $('#rs-education-institutions-list .rs-metabox-accordion-item').length;
        const $sectionsAccordionRoot = document.querySelector('#rs-education-sections-accordion');
        const $institutionsAccordionRoot = document.querySelector('#rs-education-institutions-accordion');

        function syncAllEditors() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            $('textarea[id^="rs_education_section_text_"]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') === -1 && typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
                    wp.editor.save(id);
                }
            });
            if (typeof tinymce !== 'undefined') {
                const headline = tinymce.get('<?php echo esc_js(RS_EDUCATION_HEADLINE_KEY); ?>');
                if (headline) {
                    headline.save();
                }
            }
        }

        function readSectionBody(textarea) {
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
            $('#rs-education-sections-list .rs-metabox-accordion-item').each(function () {
                const section = $(this);
                const title = (section.find('.rs-education-section-title').val() || '').trim();
                const body = readSectionBody(section.find('textarea[id^="rs_education_section_text_"]'));
                if (!title && !body) {
                    return;
                }
                sections.push({ title: title || 'Seção', body });
            });
            $('#rs-education-sections-json').val(JSON.stringify(sections));
            $('.rs-metabox-tab[data-tab="accordion"]').text('Acordeão (' + sections.length + ')');
        }

        function syncInstitutionHeadThumb($row) {
            const $toggle = $row.find('.rs-metabox-accordion-toggle').first();
            const $previewImg = $row.find('.rs-media-preview img').first();
            $row.find('.rs-metabox-accordion-head-thumb').remove();
            if ($previewImg.length) {
                $toggle.prepend(
                    $('<span class="rs-metabox-accordion-head-thumb"><img alt="" /></span>')
                        .find('img')
                        .attr('src', $previewImg.attr('src'))
                        .end()
                );
            }
        }

        function syncGalleryIds($block) {
            const ids = [];
            $block.find('.rs-education-gallery-item').each(function () {
                const id = parseInt($(this).attr('data-id'), 10) || 0;
                if (id > 0) ids.push(id);
            });
            $block.find('.rs-education-gallery-ids').val(ids.join(','));
            $block.find('.rs-education-gallery-empty').toggle(ids.length === 0);
        }

        function readGallery($block) {
            syncGalleryIds($block);
            return {
                layout: ($block.find('.rs-education-gallery-layout').val() || 'triple'),
                image_ids: ($block.find('.rs-education-gallery-ids').val() || '').trim(),
                caption: ($block.find('.rs-education-gallery-caption').val() || '').trim()
            };
        }

        function collectInstitutionsJson() {
            const institutions = [];
            $('#rs-education-institutions-list .rs-metabox-accordion-item').each(function () {
                const row = $(this);
                const name = (row.find('.rs-education-institution-name').val() || '').trim();
                const logoId = parseInt(row.find('input[data-rs-cap-image]').val(), 10) || 0;
                const description = (row.find('.rs-education-institution-description').val() || '').trim();
                const galleries = {};
                row.find('.rs-education-gallery-block').each(function () {
                    const key = $(this).find('.rs-education-gallery-layout').data('gallery');
                    if (key) {
                        galleries[key] = readGallery($(this));
                    }
                });

                if (!name && !logoId && !description
                    && !(galleries.midGallery && galleries.midGallery.image_ids)
                    && !(galleries.bottomGallery && galleries.bottomGallery.image_ids)
                ) {
                    return;
                }

                institutions.push({
                    name,
                    logo_id: logoId,
                    description,
                    midGallery: galleries.midGallery || { layout: 'triple', image_ids: '', caption: '' },
                    bottomGallery: galleries.bottomGallery || { layout: 'grid-2x2', image_ids: '', caption: '' }
                });
            });
            $('#rs-education-institutions-json').val(JSON.stringify(institutions));
            $('.rs-metabox-tab[data-tab="institutions"]').text('Instituições (' + institutions.length + ')');
        }

        function initEditor(id) {
            if (!id || id.indexOf('__INDEX__') !== -1) return;
            if (typeof wp === 'undefined' || !wp.editor) return;
            if (typeof tinymce !== 'undefined' && tinymce.get(id)) return;
            wp.editor.initialize(id, paragraphEditorSettings);
        }

        function removeEditor(id) {
            if (!id || typeof wp === 'undefined' || !wp.editor) return;
            wp.editor.remove(id);
        }

        function assignSectionNames(section, index) {
            const $textarea = section.find('textarea[id^="rs_education_section_text_"]');
            section.find('.rs-education-section-title').attr('name', 'rs_education_sections[' + index + '][title]');
            $textarea.attr('name', 'rs_education_sections[' + index + '][body]');
            const editorId = $textarea.attr('id');
            if (editorId) {
                section.attr('data-rs-editor-ids', editorId);
            }
        }

        function reindexSections() {
            // Só name=/data-index — não recria TinyMCE ao reordenar.
            $('#rs-education-sections-list .rs-metabox-accordion-item').each(function (i) {
                $(this).attr('data-index', String(i));
                assignSectionNames($(this), i);
            });
            let maxEd = -1;
            $('#rs-education-sections-list textarea[id^="rs_education_section_text_"]').each(function () {
                const match = String(this.id || '').match(/_(\d+)$/);
                if (match) maxEd = Math.max(maxEd, parseInt(match[1], 10));
            });
            nextSectionIndex = Math.max(
                maxEd + 1,
                $('#rs-education-sections-list .rs-metabox-accordion-item').length
            );
        }

        function assignInstitutionNames(row, index) {
            const prefix = 'rs_education_institutions[' + index + ']';
            row.find('.rs-education-institution-name').attr('name', prefix + '[name]');
            row.find('.rs-education-institution-description').attr('name', prefix + '[description]');
            row.find('input[data-rs-cap-image]').attr('name', prefix + '[logo_id]');
            row.find('.rs-education-gallery-block').each(function () {
                const key = $(this).find('.rs-education-gallery-layout').data('gallery');
                if (!key) return;
                $(this).find('.rs-education-gallery-layout').attr('name', prefix + '[' + key + '][layout]');
                $(this).find('.rs-education-gallery-ids').attr('name', prefix + '[' + key + '][image_ids]');
                $(this).find('.rs-education-gallery-caption').attr('name', prefix + '[' + key + '][caption]');
            });
        }

        function reindexInstitutions() {
            $('#rs-education-institutions-list .rs-metabox-accordion-item').each(function (i) {
                $(this).attr('data-index', String(i));
                assignInstitutionNames($(this), i);
                $(this).find('[id^="rs_education_inst_logo_"]').each(function () {
                    const newId = 'rs_education_inst_logo_' + i;
                    const oldId = $(this).attr('id');
                    $(this).attr('id', newId);
                    $(this).closest('.rs-media-field').find('[data-target="' + oldId + '"]').attr('data-target', newId);
                });
            });
            nextInstitutionIndex = $('#rs-education-institutions-list .rs-metabox-accordion-item').length;
        }

        function replaceIndexAttrs($el, index) {
            $el.find('[id]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') !== -1) {
                    $(this).attr('id', id.replace(/__INDEX__/g, String(index)));
                }
            });
            $el.find('[data-target]').each(function () {
                const target = $(this).attr('data-target');
                if (target && target.indexOf('__INDEX__') !== -1) {
                    $(this).attr('data-target', target.replace(/__INDEX__/g, String(index)));
                }
            });
        }

        function galleryThumbHtml(attachment) {
            if (!attachment || !attachment.url) return '';
            const mime = attachment.mime || '';
            if (mime.indexOf('video/') === 0) {
                return '<video src="' + attachment.url + '" muted playsinline preload="metadata"></video><span class="rs-education-gallery-badge">vídeo</span>';
            }
            const thumb = (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url)
                || (attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url)
                || attachment.url;
            const isGif = mime.indexOf('gif') !== -1 || /\.gif(\?|$)/i.test(attachment.url);
            return '<img src="' + thumb + '" alt="" />' + (isGif ? '<span class="rs-education-gallery-badge">gif</span>' : '');
        }

        function appendGalleryItem($block, attachment) {
            if (!attachment || !attachment.id) return;
            const existing = {};
            $block.find('.rs-education-gallery-item').each(function () {
                existing[String($(this).attr('data-id'))] = true;
            });
            if (existing[String(attachment.id)]) return;

            const item = $(
                '<div class="rs-education-gallery-item" data-id="' + attachment.id + '">' +
                    '<div class="rs-education-gallery-tile">' +
                        '<span class="rs-education-gallery-handle" title="Arrastar para reordenar" aria-hidden="true">⋮⋮</span>' +
                        '<button type="button" class="rs-education-remove-gallery-item" title="Remover" aria-label="Remover imagem">&times;</button>' +
                        '<div class="rs-education-gallery-thumb rs-media-thumb"></div>' +
                    '</div>' +
                '</div>'
            );
            item.find('.rs-education-gallery-thumb').html(galleryThumbHtml(attachment));
            $block.find('.rs-education-gallery-grid').append(item);
            syncGalleryIds($block);
        }

        function initGallerySortable($grid) {
            if (!$.fn.sortable || !$grid.length) return;
            if ($grid.hasClass('ui-sortable')) {
                $grid.sortable('refresh');
                return;
            }
            $grid.sortable({
                items: '.rs-education-gallery-item',
                handle: '.rs-education-gallery-handle, .rs-education-gallery-tile',
                placeholder: 'rs-education-gallery-placeholder',
                tolerance: 'pointer',
                opacity: 0.9,
                update: function () {
                    syncGalleryIds($grid.closest('.rs-education-gallery-block'));
                }
            });
        }

        function initAllGallerySortables(scope) {
            (scope || $(document)).find('.rs-education-gallery-grid').each(function () {
                initGallerySortable($(this));
            });
        }

        let sectionsAccordionApi = null;
        let institutionsAccordionApi = null;

        if ($sectionsAccordionRoot && window.RsMetaboxUi) {
            sectionsAccordionApi = window.RsMetaboxUi.initAccordion($sectionsAccordionRoot, {
                onExpand: function (_$item, editorIds) {
                    window.RsMetaboxUi.resizeEditors(editorIds);
                },
                onRemove: function (event, $section) {
                    event.preventDefault();
                    if ($('#rs-education-sections-list .rs-metabox-accordion-item').length <= 1) {
                        window.alert('Mantenha pelo menos uma seção.');
                        return;
                    }
                    removeEditor($section.find('textarea[id^="rs_education_section_text_"]').attr('id'));
                    $section.remove();
                    reindexSections();
                },
                onSortUpdate: function () {
                    reindexSections();
                },
            });
        }

        if ($institutionsAccordionRoot && window.RsMetaboxUi) {
            institutionsAccordionApi = window.RsMetaboxUi.initAccordion($institutionsAccordionRoot, {
                titleInputSelector: '.rs-education-institution-name',
                headTitleSelector: '.rs-education-institution-head-title',
                defaultTitle: 'Instituição',
                onRemove: function (event, $row) {
                    event.preventDefault();
                    $row.remove();
                    reindexInstitutions();
                },
                onSortUpdate: function () {
                    reindexInstitutions();
                },
            });
        }

        $('[data-rs-tabs]').on('rs-metabox-tabchange', function (_event, tab) {
            if (tab === 'accordion') {
                window.setTimeout(function () {
                    $('#rs-education-sections-list .rs-metabox-accordion-item.is-open').each(function () {
                        window.RsMetaboxUi.resizeEditors(window.RsMetaboxUi.parseEditorIds($(this)));
                    });
                }, 50);
            }
        });

        $(document).on('click', '#rs-education-institutions-list .rs-media-pick, #rs-education-institutions-list .rs-media-clear', function () {
            const $row = $(this).closest('.rs-metabox-accordion-item');
            if (!$row.length) {
                return;
            }
            window.setTimeout(function () {
                syncInstitutionHeadThumb($row);
            }, 120);
        });

        $('#rs-education-add-section').on('click', function (event) {
            event.preventDefault();
            const template = $('#rs-education-section-template .rs-metabox-accordion-item').first().clone();
            template.removeAttr('style').removeClass('is-open');
            template.attr('data-index', String(nextSectionIndex));
            template.find('.rs-education-section-title').val('');
            template.find('textarea').val('');
            template.find('.rs-metabox-accordion-head-title').text('Seção');
            template.find('.rs-metabox-accordion-toggle').attr('aria-expanded', 'false');
            replaceIndexAttrs(template, nextSectionIndex);
            assignSectionNames(template, nextSectionIndex);
            $('#rs-education-sections-list').append(template);
            initEditor('rs_education_section_text_' + nextSectionIndex);
            if (sectionsAccordionApi) {
                sectionsAccordionApi.openItem(template);
            }
            nextSectionIndex += 1;
        });

        $('#rs-education-add-institution').on('click', function (event) {
            event.preventDefault();
            const template = $('#rs-education-institution-template .rs-metabox-accordion-item').first().clone();
            template.removeAttr('style').removeClass('is-open');
            template.attr('data-index', String(nextInstitutionIndex));
            template.find('.rs-education-institution-name').val('');
            template.find('.rs-education-institution-description').val('');
            template.find('.rs-metabox-accordion-head-title').text('Instituição');
            template.find('.rs-metabox-accordion-head-thumb').remove();
            template.find('.rs-metabox-accordion-toggle').attr('aria-expanded', 'false');
            template.find('input[data-rs-cap-image]').val('0');
            template.find('.rs-media-preview').empty();
            template.find('.rs-education-gallery-ids').val('');
            template.find('.rs-education-gallery-caption').val('');
            template.find('.rs-education-gallery-grid').empty();
            template.find('.rs-education-gallery-empty').show();
            template.find('.rs-education-gallery-layout').each(function () {
                const key = $(this).data('gallery');
                $(this).val(key === 'bottomGallery' ? 'grid-2x2' : 'triple');
            });
            replaceIndexAttrs(template, nextInstitutionIndex);
            assignInstitutionNames(template, nextInstitutionIndex);
            $('#rs-education-institutions-list').append(template);
            initAllGallerySortables(template);
            if (institutionsAccordionApi) {
                institutionsAccordionApi.openItem(template);
            }
            nextInstitutionIndex += 1;
        });

        $(document).on('click', '.rs-education-add-gallery-images', function (event) {
            event.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) return;

            const $block = $(this).closest('.rs-education-gallery-block');

            const frame = wp.media({
                title: 'Selecionar imagens',
                button: { text: 'Adicionar' },
                multiple: true,
                library: { type: ['image', 'video'] }
            });

            frame.on('select', function () {
                frame.state().get('selection').each(function (attachment) {
                    appendGalleryItem($block, attachment.toJSON());
                });
                initGallerySortable($block.find('.rs-education-gallery-grid'));
            });

            frame.open();
        });

        $(document).on('click', '.rs-education-remove-gallery-item', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const $block = $(this).closest('.rs-education-gallery-block');
            $(this).closest('.rs-education-gallery-item').remove();
            syncGalleryIds($block);
        });

        $('#post').on('submit', function () {
            $('.rs-education-gallery-block').each(function () {
                syncGalleryIds($(this));
            });
            collectSectionsJson();
            collectInstitutionsJson();
        });

        $('#publish, #save-post').on('click', function () {
            window.setTimeout(function () {
                collectSectionsJson();
                collectInstitutionsJson();
            }, 0);
        });

        initAllGallerySortables();
    });
    </script>
    <?php
}

function rs_education_i18n_admin_footer_script(): void {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'education') {
        return;
    }
    ?>
    <script>
    jQuery(function ($) {
        const locales = ['en', 'pt'];
        const paragraphEditorSettings = <?php echo wp_json_encode(rs_rich_text_js_settings('paragraph')); ?>;
        const nextSection = {};
        const nextInstitution = {};
        const sectionApis = {};
        const institutionApis = {};

        function sectionsList(locale) { return $('#rs-education-sections-list-' + locale); }
        function institutionsList(locale) { return $('#rs-education-institutions-list-' + locale); }

        function saveEditors() {
            if (typeof tinymce !== 'undefined') tinymce.triggerSave();
            if (typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
                locales.forEach(function (locale) {
                    wp.editor.save('rs_education_headline_' + locale);
                });
                $('textarea[id^="rs_education_section_text_"]').each(function () {
                    const id = $(this).attr('id');
                    if (id && id.indexOf('__INDEX__') === -1) wp.editor.save(id);
                });
            }
        }

        function sectionBody($textarea) {
            const id = $textarea.attr('id');
            const editor = id && typeof tinymce !== 'undefined' ? tinymce.get(id) : null;
            return editor && !editor.isHidden() ? editor.getContent() : ($textarea.val() || '');
        }

        function gallery($block) {
            const ids = [];
            $block.find('.rs-education-gallery-item').each(function () {
                const id = parseInt($(this).attr('data-id'), 10) || 0;
                if (id > 0) ids.push(id);
            });
            $block.find('.rs-education-gallery-ids').val(ids.join(','));
            return {
                layout: $block.find('.rs-education-gallery-layout').val() || 'triple',
                image_ids: ids.join(','),
                caption: ($block.find('.rs-education-gallery-caption').val() || '').trim()
            };
        }

        function collect(locale) {
            const sections = [];
            sectionsList(locale).find('.rs-metabox-accordion-item').each(function () {
                const $row = $(this);
                const title = ($row.find('.rs-education-section-title').val() || '').trim();
                const body = sectionBody($row.find('textarea[id^="rs_education_section_text_' + locale + '_"]'));
                if (title || body) sections.push({ title: title || 'Seção', body: body });
            });
            $('#rs-education-sections-' + locale + '-json').val(JSON.stringify(sections));

            const institutions = [];
            institutionsList(locale).find('.rs-metabox-accordion-item').each(function () {
                const $row = $(this);
                const galleries = {};
                $row.find('.rs-education-gallery-block').each(function () {
                    const key = $(this).find('.rs-education-gallery-layout').data('gallery');
                    if (key) galleries[key] = gallery($(this));
                });
                const item = {
                    name: ($row.find('.rs-education-institution-name').val() || '').trim(),
                    logo_id: parseInt($row.find('input[data-rs-cap-image]').val(), 10) || 0,
                    description: ($row.find('.rs-education-institution-description').val() || '').trim(),
                    midGallery: galleries.midGallery || { layout: 'triple', image_ids: '', caption: '' },
                    bottomGallery: galleries.bottomGallery || { layout: 'grid-2x2', image_ids: '', caption: '' }
                };
                if (item.name || item.logo_id || item.description || item.midGallery.image_ids || item.bottomGallery.image_ids) {
                    institutions.push(item);
                }
            });
            $('#rs-education-institutions-' + locale + '-json').val(JSON.stringify(institutions));
        }

        function collectAll() {
            saveEditors();
            locales.forEach(collect);
        }

        function initEditor(id) {
            if (!id || id.indexOf('__INDEX__') !== -1 || typeof wp === 'undefined' || !wp.editor) return;
            if (typeof tinymce !== 'undefined' && tinymce.get(id)) return;
            wp.editor.initialize(id, paragraphEditorSettings);
        }

        function replaceIndexes($row, index) {
            $row.find('[id]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') !== -1) $(this).attr('id', id.replace(/__INDEX__/g, String(index)));
            });
            $row.find('[data-target]').each(function () {
                const target = $(this).attr('data-target');
                if (target && target.indexOf('__INDEX__') !== -1) $(this).attr('data-target', target.replace(/__INDEX__/g, String(index)));
            });
        }

        function assignSectionNames($row, locale, index) {
            const prefix = 'rs_education_i18n[' + locale + '][sections][' + index + ']';
            const $textarea = $row.find('textarea[id^="rs_education_section_text_' + locale + '_"]');
            $row.find('.rs-education-section-title').attr('name', prefix + '[title]');
            $textarea.attr('name', prefix + '[body]');
            const editorId = $textarea.attr('id');
            if (editorId) {
                $row.attr('data-rs-editor-ids', editorId);
            }
        }

        function assignInstitutionNames($row, locale, index) {
            const prefix = 'rs_education_i18n[' + locale + '][institutions][' + index + ']';
            $row.find('.rs-education-institution-name').attr('name', prefix + '[name]');
            $row.find('.rs-education-institution-description').attr('name', prefix + '[description]');
            $row.find('input[data-rs-cap-image]').attr('name', prefix + '[logo_id]');
            $row.find('.rs-education-gallery-block').each(function () {
                const key = $(this).find('.rs-education-gallery-layout').data('gallery');
                if (!key) return;
                $(this).find('.rs-education-gallery-layout').attr('name', prefix + '[' + key + '][layout]');
                $(this).find('.rs-education-gallery-ids').attr('name', prefix + '[' + key + '][image_ids]');
                $(this).find('.rs-education-gallery-caption').attr('name', prefix + '[' + key + '][caption]');
            });
        }

        function reindexSections(locale) {
            // Só name= — ids do TinyMCE ficam estáveis ao arrastar.
            sectionsList(locale).find('.rs-metabox-accordion-item').each(function (index) {
                const $row = $(this);
                $row.attr('data-index', String(index));
                assignSectionNames($row, locale, index);
            });
            let maxEd = -1;
            sectionsList(locale).find('textarea[id^="rs_education_section_text_' + locale + '_"]').each(function () {
                const match = String(this.id || '').match(/_(\d+)$/);
                if (match) maxEd = Math.max(maxEd, parseInt(match[1], 10));
            });
            nextSection[locale] = Math.max(
                maxEd + 1,
                sectionsList(locale).find('.rs-metabox-accordion-item').length
            );
        }

        function reindexInstitutions(locale) {
            institutionsList(locale).find('.rs-metabox-accordion-item').each(function (index) {
                assignInstitutionNames($(this), locale, index);
            });
            nextInstitution[locale] = institutionsList(locale).find('.rs-metabox-accordion-item').length;
        }

        locales.forEach(function (locale) {
            nextSection[locale] = sectionsList(locale).find('.rs-metabox-accordion-item').length;
            nextInstitution[locale] = institutionsList(locale).find('.rs-metabox-accordion-item').length;
            const sectionsRoot = document.querySelector('#rs-education-sections-accordion-' + locale);
            const institutionsRoot = document.querySelector('#rs-education-institutions-accordion-' + locale);
            if (sectionsRoot && window.RsMetaboxUi) {
                sectionApis[locale] = window.RsMetaboxUi.initAccordion(sectionsRoot, {
                    onExpand: function (_$item, ids) { window.RsMetaboxUi.resizeEditors(ids); },
                    onRemove: function (event, $row) {
                        event.preventDefault();
                        if (sectionsList(locale).find('.rs-metabox-accordion-item').length <= 1) return;
                        $row.remove();
                        reindexSections(locale);
                    },
                    onSortUpdate: function () { reindexSections(locale); }
                });
            }
            if (institutionsRoot && window.RsMetaboxUi) {
                institutionApis[locale] = window.RsMetaboxUi.initAccordion(institutionsRoot, {
                    titleInputSelector: '.rs-education-institution-name',
                    headTitleSelector: '.rs-education-institution-head-title',
                    defaultTitle: 'Instituição',
                    onRemove: function (event, $row) {
                        event.preventDefault();
                        $row.remove();
                        reindexInstitutions(locale);
                    },
                    onSortUpdate: function () { reindexInstitutions(locale); }
                });
            }
        });

        $('.rs-education-add-section').on('click', function (event) {
            event.preventDefault();
            const locale = $(this).data('locale') === 'pt' ? 'pt' : 'en';
            const index = nextSection[locale];
            const $row = $('#rs-education-section-template-' + locale + ' .rs-metabox-accordion-item').first().clone();
            $row.removeAttr('style').removeClass('is-open').attr('data-index', String(index));
            $row.find('input, textarea').val('');
            $row.find('.rs-metabox-accordion-head-title').text('Seção');
            replaceIndexes($row, index);
            assignSectionNames($row, locale, index);
            sectionsList(locale).append($row);
            initEditor('rs_education_section_text_' + locale + '_' + index);
            if (sectionApis[locale]) sectionApis[locale].openItem($row);
            nextSection[locale] += 1;
        });

        $('.rs-education-add-institution').on('click', function (event) {
            event.preventDefault();
            const locale = $(this).data('locale') === 'pt' ? 'pt' : 'en';
            const index = nextInstitution[locale];
            const $row = $('#rs-education-institution-template-' + locale + ' .rs-metabox-accordion-item').first().clone();
            $row.removeAttr('style').removeClass('is-open').attr('data-index', String(index));
            $row.find('input, textarea').val('');
            $row.find('.rs-media-preview, .rs-education-gallery-grid').empty();
            $row.find('.rs-metabox-accordion-head-title').text('Instituição');
            replaceIndexes($row, index);
            assignInstitutionNames($row, locale, index);
            institutionsList(locale).append($row);
            $row.find('.rs-education-gallery-grid').removeClass('ui-sortable').sortable({
                items: '.rs-education-gallery-item',
                handle: '.rs-education-gallery-handle, .rs-education-gallery-tile'
            });
            if (institutionApis[locale]) institutionApis[locale].openItem($row);
            nextInstitution[locale] += 1;
        });

        $('[data-rs-tabs]').on('rs-metabox-tabchange', function (_event, locale) {
            if (locale !== 'en' && locale !== 'pt') return;
            window.setTimeout(function () {
                $('[data-locale="' + locale + '"] .rs-metabox-accordion-item.is-open').each(function () {
                    window.RsMetaboxUi.resizeEditors(window.RsMetaboxUi.parseEditorIds($(this)));
                });
            }, 0);
        });

        $('#post').on('submit', collectAll);
        $(document).on('click', '#publish, #save-post', collectAll);
    });
    </script>
    <?php
}

if (function_exists('rs_enqueue_admin_media_picker')) {
    rs_enqueue_admin_media_picker(['education']);
}
