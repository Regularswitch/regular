<?php
/**
 * Campos editáveis do CPT education (Educação).
 */

if (defined('RS_EDUCATION_FIELDS_LOADED')) {
    return;
}
define('RS_EDUCATION_FIELDS_LOADED', true);

const RS_EDUCATION_HERO_IMAGE_KEY = 'rs_education_hero_image_id';
const RS_EDUCATION_HEADLINE_KEY = 'rs_education_headline';
const RS_EDUCATION_SECTIONS_KEY = 'rs_education_sections';
const RS_EDUCATION_INSTITUTIONS_KEY = 'rs_education_institutions';
const RS_EDUCATION_HERO_VIDEO_KEY = 'rs_education_hero_video_id';
const RS_EDUCATION_HERO_VIDEO_URL_LEGACY = 'rs_education_hero_video_url';
const RS_EDUCATION_STUDIO_KEY = 'rs_education_studio_ids';

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_education_get_sections(int $post_id): array {
    $raw = get_post_meta($post_id, RS_EDUCATION_SECTIONS_KEY, true);

    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return rs_education_normalize_sections($decoded);
        }
    }

    if (is_array($raw)) {
        return rs_education_normalize_sections($raw);
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
function rs_education_get_institutions_raw(int $post_id): array {
    $raw = get_post_meta($post_id, RS_EDUCATION_INSTITUTIONS_KEY, true);
    $decoded = [];

    if (is_string($raw) && $raw !== '') {
        $parsed = json_decode($raw, true);
        if (is_array($parsed)) {
            $decoded = $parsed;
        }
    } elseif (is_array($raw)) {
        $decoded = $raw;
    }

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
            'topGallery'  => [
                'layout'    => 'pair',
                'image_ids' => '',
                'caption'   => '',
            ],
            'midGallery'  => [
                'layout'    => 'pair',
                'image_ids' => '',
                'caption'   => '',
            ],
            'bottomGallery' => [
                'layout'    => 'grid-2x2',
                'image_ids' => '',
                'caption'   => '',
            ],
        ];

        foreach (['topGallery', 'midGallery', 'bottomGallery'] as $key) {
            $gallery = rs_education_normalize_gallery_for_storage($item[$key] ?? null);
            if ($gallery) {
                $entry[$key] = [
                    'layout'    => $gallery['layout'],
                    'image_ids' => $gallery['image_ids'],
                    'caption'   => $gallery['caption'],
                ];
            }
        }

        if ($name === '' && $logo_id <= 0 && $description === ''
            && $entry['topGallery']['image_ids'] === ''
            && $entry['midGallery']['image_ids'] === ''
            && $entry['bottomGallery']['image_ids'] === ''
        ) {
            continue;
        }

        $normalized[] = $entry;
    }

    return $normalized;
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

        $payload[] = $entry;
    }

    return $payload;
}

function rs_education_meta_to_payload(int $post_id): array {
    $hero = function_exists('rs_section_hero_media')
        ? rs_section_hero_media($post_id, RS_EDUCATION_HERO_IMAGE_KEY, RS_EDUCATION_HERO_VIDEO_KEY, 'education')
        : ['image' => '', 'video' => ''];

    if ($hero['video'] === '') {
        $hero['video'] = trim((string) get_post_meta($post_id, RS_EDUCATION_HERO_VIDEO_URL_LEGACY, true));
    }

    return [
        'heroImage'         => $hero['image'],
        'heroVideo'         => $hero['video'],
        'headline'          => trim((string) get_post_meta($post_id, RS_EDUCATION_HEADLINE_KEY, true)),
        'accordionSections' => rs_education_sections_to_payload(rs_education_get_sections($post_id)),
        'institutions'      => rs_education_institutions_to_payload(rs_education_get_institutions_raw($post_id)),
        'studioImages'      => rs_education_attachment_urls(
            rs_education_parse_ids_csv((string) get_post_meta($post_id, RS_EDUCATION_STUDIO_KEY, true))
        ),
    ];
}

function rs_education_get_post_id_by_locale(string $locale): int {
    $posts = get_posts([
        'post_type'      => 'education',
        'post_status'    => 'publish',
        'name'           => $locale,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

function rs_education_ensure_locale_posts(): void {
    if (get_option('rs_education_posts_ensured_v1')) {
        return;
    }

    foreach (['en', 'pt'] as $locale) {
        if (rs_education_get_post_id_by_locale($locale) > 0) {
            continue;
        }

        wp_insert_post([
            'post_title'  => $locale === 'pt' ? 'Educação (PT)' : 'Education (EN)',
            'post_status' => 'publish',
            'post_type'   => 'education',
            'post_name'   => $locale,
            'post_author' => 1,
        ], true);
    }

    update_option('rs_education_posts_ensured_v1', 1);
}

add_action('init', function () {
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

add_action('init', 'rs_education_ensure_locale_posts', 25);

add_action('rest_api_init', function () {
    register_rest_field('education', 'education_data', [
        'get_callback' => function (array $post) {
            return rs_education_meta_to_payload((int) $post['id']);
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
        'rs_education_render_meta_box',
        'education',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'education', 'normal');
}, 10);

function rs_education_render_section_row(int $index, array $section, bool $is_template = false): void {
    $title = $section['title'] ?? '';
    $body = $section['body'] ?? '';
    $name_prefix = $is_template ? 'rs_education_sections[__INDEX__]' : 'rs_education_sections[' . $index . ']';
    $editor_id = $is_template ? 'rs_education_section_text___INDEX__' : 'rs_education_section_text_' . $index;
    ?>
    <fieldset class="rs-education-section" data-index="<?php echo esc_attr($is_template ? '__INDEX__' : (string) $index); ?>" style="margin:0 0 14px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;background:#fff;<?php echo $is_template ? 'display:none;' : ''; ?>">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
            <legend style="font-weight:600;padding:0;margin:0;"><strong>Seção do acordeão</strong></legend>
            <button type="button" class="button-link-delete rs-education-remove-section">Remover</button>
        </div>

        <div style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>
            <input
                type="text"
                style="width:100%;"
                class="rs-education-section-title"
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

        <p style="margin:0 0 8px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Imagens (IDs)</label>
            <input
                type="text"
                class="rs-education-gallery-ids"
                data-gallery="<?php echo esc_attr($field_suffix); ?>"
                style="width:100%;"
                <?php if ($include_name) : ?>name="<?php echo esc_attr($name_prefix . '[' . $field_suffix . '][image_ids]'); ?>"<?php endif; ?>
                value="<?php echo esc_attr($image_ids); ?>"
                placeholder="12,34,56"
            />
        </p>
        <p style="margin:0 0 10px;">
            <button type="button" class="button button-secondary rs-education-add-gallery-images" data-gallery="<?php echo esc_attr($field_suffix); ?>">+ Adicionar imagens</button>
        </p>
        <div class="rs-education-gallery-preview" data-gallery="<?php echo esc_attr($field_suffix); ?>" style="display:flex;flex-wrap:wrap;gap:8px;margin:0 0 10px;">
            <?php foreach ($ids as $attachment_id) :
                $thumb = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                if (!$thumb) {
                    continue;
                }
                ?>
                <img src="<?php echo esc_url($thumb); ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:4px;" data-id="<?php echo esc_attr((string) $attachment_id); ?>" />
            <?php endforeach; ?>
        </div>

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

function rs_education_render_institution_row(int $index, array $institution, bool $is_template = false): void {
    $name = (string) ($institution['name'] ?? '');
    $logo_id = (int) ($institution['logo_id'] ?? 0);
    $description = (string) ($institution['description'] ?? '');
    $name_prefix = $is_template ? 'rs_education_institutions[__INDEX__]' : 'rs_education_institutions[' . $index . ']';
    $logo_field_id = $is_template ? 'rs_education_inst_logo___INDEX__' : 'rs_education_inst_logo_' . $index;
    $display = $is_template ? 'display:none;' : '';

    $top = is_array($institution['topGallery'] ?? null) ? $institution['topGallery'] : ['layout' => 'pair', 'image_ids' => '', 'caption' => ''];
    $mid = is_array($institution['midGallery'] ?? null) ? $institution['midGallery'] : ['layout' => 'pair', 'image_ids' => '', 'caption' => ''];
    $bottom = is_array($institution['bottomGallery'] ?? null) ? $institution['bottomGallery'] : ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''];
    ?>
    <fieldset class="rs-education-institution" data-index="<?php echo esc_attr($is_template ? '__INDEX__' : (string) $index); ?>" style="margin:0 0 16px;padding:14px;border:1px solid #dcdcde;border-radius:4px;background:#fff;<?php echo esc_attr($display); ?>">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
            <legend style="font-weight:600;padding:0;margin:0;"><strong>Instituição</strong></legend>
            <button type="button" class="button-link-delete rs-education-remove-institution">Remover</button>
        </div>

        <p style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Nome</label>
            <input
                type="text"
                class="rs-education-institution-name"
                style="width:100%;"
                <?php if (!$is_template) : ?>name="<?php echo esc_attr($name_prefix); ?>[name]"<?php endif; ?>
                value="<?php echo esc_attr($name); ?>"
                placeholder="École de Design de Nantes Atlantique (France)"
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

        <p style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Descrição (HTML opcional)</label>
            <textarea
                class="rs-education-institution-description large-text"
                style="width:100%;min-height:90px;"
                <?php if (!$is_template) : ?>name="<?php echo esc_attr($name_prefix); ?>[description]"<?php endif; ?>
            ><?php echo esc_textarea($description); ?></textarea>
        </p>

        <?php
        rs_education_render_gallery_fields($name_prefix, 'Galeria acima do nome (top)', $top, 'topGallery', !$is_template);
        rs_education_render_gallery_fields($name_prefix, 'Galeria após o nome (mid)', $mid, 'midGallery', !$is_template);
        rs_education_render_gallery_fields($name_prefix, 'Galeria após a descrição (bottom)', $bottom, 'bottomGallery', !$is_template);
        ?>
    </fieldset>
    <?php
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
            'topGallery'    => ['layout' => 'pair', 'image_ids' => '', 'caption' => ''],
            'midGallery'    => ['layout' => 'pair', 'image_ids' => '', 'caption' => ''],
            'bottomGallery' => ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''],
        ]];
    }

    echo '<p style="margin-top:0;color:#646970;">Um post por idioma (slug <code>en</code> / <code>pt</code>). Tudo abaixo alimenta a página <code>/education</code>. <em>(Plugin Tradução v1.2.1)</em></p>';
    if (function_exists('rs_sync_media_notice_html')) {
        echo rs_sync_media_notice_html((int) $post->ID);
    }

    if (function_exists('rs_section_render_hero_fields')) {
        rs_section_render_hero_fields($post->ID, RS_EDUCATION_HERO_IMAGE_KEY, RS_EDUCATION_HERO_VIDEO_KEY);
    }

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Headline</strong></legend>';
    rs_render_rich_text_field(RS_EDUCATION_HEADLINE_KEY, RS_EDUCATION_HEADLINE_KEY, $headline, 'inline');
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Use o botão <strong>B</strong> para destacar palavras.</p>';
    echo '</fieldset>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Acordeão</strong></legend>';
    echo '<div id="rs-education-sections-list">';
    foreach ($sections as $index => $section) {
        rs_education_render_section_row((int) $index, $section);
    }
    echo '</div>';
    rs_education_render_section_row(0, ['title' => '', 'body' => ''], true);
    echo '<p style="margin:12px 0 0;"><button type="button" class="button button-secondary" id="rs-education-add-section">+ Adicionar seção</button></p>';
    echo '<input type="hidden" id="rs-education-sections-json" name="rs_education_sections_json" value="" />';
    echo '</fieldset>';

    $studio_ids = (string) get_post_meta($post->ID, RS_EDUCATION_STUDIO_KEY, true);
    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Carrossel do estúdio</strong></legend>';
    echo '<p style="margin:0 0 10px;color:#646970;font-size:12px;">Fotos do escritório/estúdio (substitui a foto “central” do site antigo). IDs separados por vírgula ou use o botão.</p>';
    echo '<input type="hidden" class="rs-education-studio-ids" id="' . esc_attr(RS_EDUCATION_STUDIO_KEY) . '" name="' . esc_attr(RS_EDUCATION_STUDIO_KEY) . '" value="' . esc_attr($studio_ids) . '" />';
    echo '<div class="rs-education-studio-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin:0 0 10px;">';
    foreach (rs_education_parse_ids_csv($studio_ids) as $aid) {
        $thumb = wp_get_attachment_image_url($aid, 'thumbnail');
        if ($thumb) {
            echo '<img src="' . esc_url($thumb) . '" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:4px;" />';
        }
    }
    echo '</div>';
    echo '<button type="button" class="button button-secondary rs-education-add-studio-images">+ Adicionar fotos</button>';
    echo '</fieldset>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Instituições</strong></legend>';
    echo '<p style="margin:0 0 12px;color:#646970;font-size:12px;">Escolas/parceiros com logo, texto e galerias (2 colunas, 3 verticais ou grade 2×2).</p>';
    echo '<div id="rs-education-institutions-list">';
    foreach ($institutions as $index => $institution) {
        rs_education_render_institution_row((int) $index, $institution);
    }
    echo '</div>';
    rs_education_render_institution_row(0, [
        'name'          => '',
        'logo_id'       => 0,
        'description'   => '',
        'topGallery'    => ['layout' => 'pair', 'image_ids' => '', 'caption' => ''],
        'midGallery'    => ['layout' => 'pair', 'image_ids' => '', 'caption' => ''],
        'bottomGallery' => ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''],
    ], true);
    echo '<p style="margin:12px 0 0;"><button type="button" class="button button-primary" id="rs-education-add-institution">+ Adicionar instituição</button></p>';
    echo '<input type="hidden" id="rs-education-institutions-json" name="rs_education_institutions_json" value="" />';
    echo '</fieldset>';
}

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_education_parse_sections_from_request(): array {
    if (!empty($_POST['rs_education_sections_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_education_sections_json']), true);
        if (is_array($decoded)) {
            return rs_education_normalize_sections($decoded);
        }
    }

    if (!isset($_POST['rs_education_sections']) || !is_array($_POST['rs_education_sections'])) {
        return [];
    }

    return rs_education_normalize_sections(wp_unslash($_POST['rs_education_sections']));
}

/**
 * @return array<int, array<string, mixed>>
 */
function rs_education_parse_institutions_from_request(): array {
    if (!empty($_POST['rs_education_institutions_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_education_institutions_json']), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    if (!isset($_POST['rs_education_institutions']) || !is_array($_POST['rs_education_institutions'])) {
        return [];
    }

    $items = [];
    foreach (wp_unslash($_POST['rs_education_institutions']) as $key => $row) {
        if ($key === '__INDEX__' || !is_array($row)) {
            continue;
        }
        $items[] = $row;
    }

    return $items;
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

    $headline = isset($_POST[RS_EDUCATION_HEADLINE_KEY])
        ? wp_kses_post(wp_unslash($_POST[RS_EDUCATION_HEADLINE_KEY]))
        : '';
    update_post_meta($post_id, RS_EDUCATION_HEADLINE_KEY, $headline);

    if (function_exists('rs_section_save_hero_media')) {
        rs_section_save_hero_media($post_id, RS_EDUCATION_HERO_IMAGE_KEY, RS_EDUCATION_HERO_VIDEO_KEY);
    }

    $sections = rs_education_parse_sections_from_request();
    update_post_meta($post_id, RS_EDUCATION_SECTIONS_KEY, wp_json_encode($sections, JSON_UNESCAPED_UNICODE));

    if (isset($_POST[RS_EDUCATION_STUDIO_KEY])) {
        $studio_raw = sanitize_text_field(wp_unslash((string) $_POST[RS_EDUCATION_STUDIO_KEY]));
        $studio_ids = rs_education_parse_ids_csv($studio_raw);
        update_post_meta($post_id, RS_EDUCATION_STUDIO_KEY, implode(',', $studio_ids));
    }

    $institutions = rs_education_parse_institutions_from_request();
    $to_store = [];
    foreach ($institutions as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim(wp_strip_all_tags((string) ($item['name'] ?? '')));
        $logo_id = (int) ($item['logo_id'] ?? 0);
        $description = wp_kses_post((string) ($item['description'] ?? ''));

        $entry = [
            'name'        => $name,
            'logo_id'     => $logo_id,
            'description' => $description,
        ];

        foreach (['topGallery', 'midGallery', 'bottomGallery'] as $key) {
            $gallery = rs_education_normalize_gallery_for_storage($item[$key] ?? null);
            if ($gallery) {
                $entry[$key] = [
                    'layout'    => $gallery['layout'],
                    'image_ids' => $gallery['image_ids'],
                    'caption'   => $gallery['caption'],
                ];
            }
        }

        if ($name === '' && $logo_id <= 0 && trim(wp_strip_all_tags($description)) === ''
            && empty($entry['topGallery']['image_ids'])
            && empty($entry['midGallery']['image_ids'])
            && empty($entry['bottomGallery']['image_ids'])
        ) {
            continue;
        }

        $to_store[] = $entry;
    }

    update_post_meta($post_id, RS_EDUCATION_INSTITUTIONS_KEY, wp_json_encode($to_store, JSON_UNESCAPED_UNICODE));
}, 10);

function rs_copy_education_fields(int $from_id, int $to_id): void {
    update_post_meta($to_id, RS_EDUCATION_HEADLINE_KEY, get_post_meta($from_id, RS_EDUCATION_HEADLINE_KEY, true));
    update_post_meta($to_id, RS_EDUCATION_SECTIONS_KEY, get_post_meta($from_id, RS_EDUCATION_SECTIONS_KEY, true));
    update_post_meta($to_id, RS_EDUCATION_INSTITUTIONS_KEY, get_post_meta($from_id, RS_EDUCATION_INSTITUTIONS_KEY, true));
    update_post_meta($to_id, RS_EDUCATION_STUDIO_KEY, get_post_meta($from_id, RS_EDUCATION_STUDIO_KEY, true));
    if (function_exists('rs_section_copy_hero_media')) {
        rs_section_copy_hero_media($from_id, $to_id, RS_EDUCATION_HERO_IMAGE_KEY, RS_EDUCATION_HERO_VIDEO_KEY);
    }
}

add_action('admin_footer-post.php', 'rs_education_admin_footer_script');
add_action('admin_footer-post-new.php', 'rs_education_admin_footer_script');

function rs_education_admin_footer_script(): void {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'education') {
        return;
    }
    ?>
    <script>
    jQuery(function ($) {
        const paragraphEditorSettings = <?php echo wp_json_encode(rs_rich_text_js_settings('paragraph')); ?>;
        let nextSectionIndex = $('#rs-education-sections-list .rs-education-section').length;
        let nextInstitutionIndex = $('#rs-education-institutions-list .rs-education-institution').length;

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
                if (headline) headline.save();
            }
        }

        function readSectionBody(textarea) {
            const editorId = textarea.attr('id');
            if (editorId && typeof tinymce !== 'undefined') {
                const editor = tinymce.get(editorId);
                if (editor) return editor.getContent();
            }
            return textarea.val() || '';
        }

        function collectSectionsJson() {
            syncAllEditors();
            const sections = [];
            $('#rs-education-sections-list .rs-education-section').each(function () {
                const section = $(this);
                const title = (section.find('.rs-education-section-title').val() || '').trim();
                const body = readSectionBody(section.find('textarea[id^="rs_education_section_text_"]'));
                if (!title && !body) return;
                sections.push({ title: title || 'Seção', body });
            });
            $('#rs-education-sections-json').val(JSON.stringify(sections));
        }

        function readGallery($block) {
            return {
                layout: ($block.find('.rs-education-gallery-layout').val() || 'pair'),
                image_ids: ($block.find('.rs-education-gallery-ids').val() || '').trim(),
                caption: ($block.find('.rs-education-gallery-caption').val() || '').trim()
            };
        }

        function collectInstitutionsJson() {
            const institutions = [];
            $('#rs-education-institutions-list .rs-education-institution').each(function () {
                const row = $(this);
                const name = (row.find('.rs-education-institution-name').val() || '').trim();
                const logoId = parseInt(row.find('input[data-rs-cap-image]').val(), 10) || 0;
                const description = (row.find('.rs-education-institution-description').val() || '').trim();
                const galleries = {};
                row.find('.rs-education-gallery-block').each(function () {
                    const key = $(this).find('.rs-education-gallery-layout').data('gallery');
                    if (key) galleries[key] = readGallery($(this));
                });

                if (!name && !logoId && !description
                    && !(galleries.topGallery && galleries.topGallery.image_ids)
                    && !(galleries.midGallery && galleries.midGallery.image_ids)
                    && !(galleries.bottomGallery && galleries.bottomGallery.image_ids)
                ) {
                    return;
                }

                institutions.push({
                    name,
                    logo_id: logoId,
                    description,
                    topGallery: galleries.topGallery || { layout: 'pair', image_ids: '', caption: '' },
                    midGallery: galleries.midGallery || { layout: 'pair', image_ids: '', caption: '' },
                    bottomGallery: galleries.bottomGallery || { layout: 'grid-2x2', image_ids: '', caption: '' }
                });
            });
            $('#rs-education-institutions-json').val(JSON.stringify(institutions));
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
            section.find('.rs-education-section-title').attr('name', 'rs_education_sections[' + index + '][title]');
            section.find('textarea[id^="rs_education_section_text_"]').attr('name', 'rs_education_sections[' + index + '][body]');
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

        $('#rs-education-add-section').on('click', function (event) {
            event.preventDefault();
            const template = $('.rs-education-section[data-index="__INDEX__"]').first().clone();
            template.removeAttr('style').attr('style', 'margin:0 0 14px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;background:#fff;');
            template.attr('data-index', String(nextSectionIndex));
            template.find('.rs-education-section-title').val('');
            template.find('textarea').val('');
            replaceIndexAttrs(template, nextSectionIndex);
            assignSectionNames(template, nextSectionIndex);
            $('#rs-education-sections-list').append(template);
            initEditor('rs_education_section_text_' + nextSectionIndex);
            nextSectionIndex += 1;
        });

        $(document).on('click', '.rs-education-remove-section', function (event) {
            event.preventDefault();
            if ($('#rs-education-sections-list .rs-education-section').length <= 1) {
                window.alert('Mantenha pelo menos uma seção.');
                return;
            }
            const section = $(this).closest('.rs-education-section');
            removeEditor(section.find('textarea[id^="rs_education_section_text_"]').attr('id'));
            section.remove();
            $('#rs-education-sections-list .rs-education-section').each(function (i) {
                $(this).attr('data-index', String(i));
                assignSectionNames($(this), i);
            });
            nextSectionIndex = $('#rs-education-sections-list .rs-education-section').length;
        });

        $('#rs-education-add-institution').on('click', function (event) {
            event.preventDefault();
            const template = $('.rs-education-institution[data-index="__INDEX__"]').first().clone();
            template.attr('style', 'margin:0 0 16px;padding:14px;border:1px solid #dcdcde;border-radius:4px;background:#fff;');
            template.attr('data-index', String(nextInstitutionIndex));
            template.find('.rs-education-institution-name').val('');
            template.find('.rs-education-institution-description').val('');
            template.find('input[data-rs-cap-image]').val('0');
            template.find('.rs-media-preview').empty();
            template.find('.rs-education-gallery-ids').val('');
            template.find('.rs-education-gallery-caption').val('');
            template.find('.rs-education-gallery-preview').empty();
            template.find('.rs-education-gallery-layout').each(function () {
                const key = $(this).data('gallery');
                $(this).val(key === 'bottomGallery' ? 'grid-2x2' : 'pair');
            });
            replaceIndexAttrs(template, nextInstitutionIndex);
            assignInstitutionNames(template, nextInstitutionIndex);
            $('#rs-education-institutions-list').append(template);
            nextInstitutionIndex += 1;
        });

        $(document).on('click', '.rs-education-remove-institution', function (event) {
            event.preventDefault();
            $(this).closest('.rs-education-institution').remove();
            $('#rs-education-institutions-list .rs-education-institution').each(function (i) {
                $(this).attr('data-index', String(i));
                assignInstitutionNames($(this), i);
                $(this).find('[id^="rs_education_inst_logo_"]').each(function () {
                    const newId = 'rs_education_inst_logo_' + i;
                    const oldId = $(this).attr('id');
                    $(this).attr('id', newId);
                    $(this).closest('.rs-media-field').find('[data-target="' + oldId + '"]').attr('data-target', newId);
                });
            });
            nextInstitutionIndex = $('#rs-education-institutions-list .rs-education-institution').length;
        });

        $(document).on('click', '.rs-education-add-gallery-images', function (event) {
            event.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) return;

            const button = $(this);
            const row = button.closest('.rs-education-gallery-block');
            const input = row.find('.rs-education-gallery-ids');
            const preview = row.find('.rs-education-gallery-preview');

            const frame = wp.media({
                title: 'Selecionar imagens',
                button: { text: 'Adicionar' },
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                const current = String(input.val() || '')
                    .split(',')
                    .map(function (v) { return parseInt(v, 10); })
                    .filter(function (v) { return v > 0; });

                frame.state().get('selection').each(function (attachment) {
                    const data = attachment.toJSON();
                    if (!data.id || current.indexOf(data.id) !== -1) return;
                    current.push(data.id);
                    const thumb = (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) || data.url;
                    preview.append(
                        '<img src="' + thumb + '" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:4px;" data-id="' + data.id + '" />'
                    );
                });

                input.val(current.join(','));
            });

            frame.open();
        });

        $(document).on('click', '.rs-education-add-studio-images', function (event) {
            event.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) return;

            const fieldset = $(this).closest('fieldset');
            const input = fieldset.find('.rs-education-studio-ids');
            const preview = fieldset.find('.rs-education-studio-preview');

            const frame = wp.media({
                title: 'Fotos do estúdio',
                button: { text: 'Adicionar' },
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                const current = String(input.val() || '')
                    .split(',')
                    .map(function (v) { return parseInt(v, 10); })
                    .filter(function (v) { return v > 0; });

                frame.state().get('selection').each(function (attachment) {
                    const data = attachment.toJSON();
                    if (!data.id || current.indexOf(data.id) !== -1) return;
                    current.push(data.id);
                    const thumb = (data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) || data.url;
                    preview.append(
                        '<img src="' + thumb + '" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:4px;" />'
                    );
                });

                input.val(current.join(','));
            });

            frame.open();
        });

        $('#post').on('submit', function () {
            collectSectionsJson();
            collectInstitutionsJson();
        });
    });
    </script>
    <?php
}

if (function_exists('rs_enqueue_admin_media_picker')) {
    rs_enqueue_admin_media_picker(['education']);
}
