<?php
/**
 * Campos editáveis do CPT project (meta box + REST API).
 */

if (defined('RS_PROJECT_FIELDS_LOADED')) {
    return;
}
define('RS_PROJECT_FIELDS_LOADED', true);

const RS_PROJECT_HERO_KEY = 'rs_project_hero_id';
const RS_PROJECT_LOGO_KEY = 'rs_project_logo_id';
const RS_PROJECT_ACCORDION_KEY = 'rs_project_accordion';
const RS_PROJECT_GALLERY_KEY = 'rs_project_gallery';
const RS_PROJECT_FEATURED_KEY = 'rs_project_featured_home';
const RS_PROJECT_VIGNETTE_KEY = 'rs_project_show_vignette';

const RS_PROJECT_LEGACY_ACCORDION_LABELS = [
    1 => 'CONTEXTO / CONTEXT',
    2 => 'DIREÇÃO CRIATIVA / CREATIVE DIRECTION',
    3 => 'SOLUÇÃO / SOLUTION',
    4 => 'IMPACTO / IMPACT',
];

/**
 * @return 'en'|'pt'
 */
function rs_project_guess_locale(int $post_id): string {
    if ((int) get_post_meta($post_id, 'en', true) > 0) {
        return 'pt';
    }

    if ((int) get_post_meta($post_id, 'pt', true) > 0) {
        return 'en';
    }

    return 'en';
}

/**
 * @return array<int, string>
 */
function rs_project_default_accordion_titles(string $locale): array {
    if ($locale === 'pt') {
        return ['Contexto', 'Direção criativa', 'Solução', 'Impacto'];
    }

    return ['Context', 'Creative direction', 'Solution', 'Impact'];
}

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_project_default_accordion_sections(string $locale): array {
    $sections = [];

    foreach (rs_project_default_accordion_titles($locale) as $title) {
        $sections[] = [
            'title' => $title,
            'body'  => '',
        ];
    }

    return $sections;
}

/**
 * @param array<int, mixed> $sections
 * @return array<int, array{title: string, body: string}>
 */
function rs_project_normalize_accordion_sections(array $sections): array {
    $normalized = [];

    foreach ($sections as $section) {
        if (!is_array($section)) {
            continue;
        }

        $title = trim(wp_strip_all_tags((string) ($section['title'] ?? '')));
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
 * @return array<int, array{title: string, body: string}>
 */
function rs_project_get_accordion_sections(int $post_id): array {
    $raw = get_post_meta($post_id, RS_PROJECT_ACCORDION_KEY, true);

    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $sections = rs_project_normalize_accordion_sections($decoded);
            if ($sections) {
                return $sections;
            }
        }
    }

    if (is_array($raw)) {
        $sections = rs_project_normalize_accordion_sections($raw);
        if ($sections) {
            return $sections;
        }
    }

    $locale = rs_project_guess_locale($post_id);
    $legacy = [];

    foreach (RS_PROJECT_LEGACY_ACCORDION_LABELS as $index => $label) {
        $body = trim((string) get_post_meta($post_id, "rs_project_acc_{$index}_body", true));
        if ($body === '') {
            continue;
        }

        $parts = array_map('trim', explode('/', $label));
        $title = $locale === 'pt'
            ? ($parts[0] ?? $label)
            : ($parts[1] ?? $parts[0] ?? $label);

        $legacy[] = [
            'title' => $title,
            'body'  => $body,
        ];
    }

    return $legacy;
}

function rs_project_get_hero_id(int $post_id): int {
    $hero_id = (int) get_post_meta($post_id, RS_PROJECT_HERO_KEY, true);
    if ($hero_id > 0) {
        return $hero_id;
    }

    return (int) get_post_meta($post_id, 'etc_upload_image', true);
}

function rs_project_get_logo_id(int $post_id): int {
    $custom = (int) get_post_meta($post_id, RS_PROJECT_LOGO_KEY, true);
    if ($custom > 0) {
        return $custom;
    }

    return (int) get_post_thumbnail_id($post_id);
}

/**
 * @return array<int, int>
 */
function rs_project_get_gallery_ids(int $post_id): array {
    $raw = (string) get_post_meta($post_id, RS_PROJECT_GALLERY_KEY, true);
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
    $mime = (string) get_post_mime_type($attachment_id);
    $type = 'image';

    if ($mime !== '' && str_starts_with($mime, 'video/')) {
        $type = 'video';
    } elseif ($mime === 'image/gif') {
        $type = 'gif';
    }

    return [
        'url'    => $url,
        'width'  => (int) ($meta['width'] ?? 0),
        'height' => (int) ($meta['height'] ?? 0),
        'mime'   => $mime,
        'type'   => $type,
    ];
}

function rs_project_meta_to_payload(int $post_id): array {
    $accordion = [];
    $index = 1;

    foreach (rs_project_get_accordion_sections($post_id) as $section) {
        $body = trim((string) ($section['body'] ?? ''));
        if ($body === '') {
            continue;
        }

        $accordion[] = [
            'index' => $index,
            'title' => trim((string) ($section['title'] ?? '')),
            'body'  => wpautop($body),
        ];
        $index += 1;
    }

    $gallery = [];
    foreach (rs_project_get_gallery_ids($post_id) as $attachment_id) {
        $info = rs_project_attachment_info($attachment_id);
        if ($info && !empty($info['url'])) {
            $gallery[] = $info;
        }
    }

    return [
        'heroImage'      => rs_project_attachment_info(rs_project_get_hero_id($post_id)),
        'logoImage'      => rs_project_attachment_info(rs_project_get_logo_id($post_id)),
        'accordion'      => $accordion,
        'gallery'        => $gallery,
        'featuredOnHome' => (bool) get_post_meta($post_id, RS_PROJECT_FEATURED_KEY, true),
        'showVignette'   => get_post_meta($post_id, RS_PROJECT_VIGNETTE_KEY, true) === ''
            ? true
            : (bool) get_post_meta($post_id, RS_PROJECT_VIGNETTE_KEY, true),
    ];
}

function rs_copy_project_fields(int $from_id, int $to_id): void {
    $hero_id = rs_project_get_hero_id($from_id);
    if ($hero_id > 0) {
        update_post_meta($to_id, RS_PROJECT_HERO_KEY, $hero_id);
        update_post_meta($to_id, 'etc_upload_image', $hero_id);
    } else {
        delete_post_meta($to_id, RS_PROJECT_HERO_KEY);
        delete_post_meta($to_id, 'etc_upload_image');
    }

    $logo_id = (int) get_post_meta($from_id, RS_PROJECT_LOGO_KEY, true);
    if ($logo_id > 0) {
        update_post_meta($to_id, RS_PROJECT_LOGO_KEY, $logo_id);
        set_post_thumbnail($to_id, $logo_id);
    } else {
        delete_post_meta($to_id, RS_PROJECT_LOGO_KEY);
    }

    update_post_meta($to_id, RS_PROJECT_ACCORDION_KEY, get_post_meta($from_id, RS_PROJECT_ACCORDION_KEY, true));

    foreach (array_keys(RS_PROJECT_LEGACY_ACCORDION_LABELS) as $index) {
        $key = "rs_project_acc_{$index}_body";
        update_post_meta($to_id, $key, get_post_meta($from_id, $key, true));
    }

    update_post_meta($to_id, RS_PROJECT_GALLERY_KEY, get_post_meta($from_id, RS_PROJECT_GALLERY_KEY, true));
    update_post_meta($to_id, RS_PROJECT_FEATURED_KEY, get_post_meta($from_id, RS_PROJECT_FEATURED_KEY, true));
    update_post_meta($to_id, RS_PROJECT_VIGNETTE_KEY, get_post_meta($from_id, RS_PROJECT_VIGNETTE_KEY, true));
}

add_action('init', function () {
    register_post_meta('project', RS_PROJECT_HERO_KEY, [
        'single'        => true,
        'type'          => 'integer',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);

    register_post_meta('project', RS_PROJECT_LOGO_KEY, [
        'single'        => true,
        'type'          => 'integer',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);

    foreach ([RS_PROJECT_ACCORDION_KEY, RS_PROJECT_GALLERY_KEY] as $key) {
        register_post_meta('project', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    foreach ([RS_PROJECT_FEATURED_KEY, RS_PROJECT_VIGNETTE_KEY] as $key) {
        register_post_meta('project', $key, [
            'single'        => true,
            'type'          => 'boolean',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    foreach (array_keys(RS_PROJECT_LEGACY_ACCORDION_LABELS) as $index) {
        register_post_meta('project', "rs_project_acc_{$index}_body", [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);

add_action('init', function () {
    remove_post_type_support('project', 'editor');
}, 100);

add_action('rest_api_init', function () {
    register_rest_field('project', 'project_data', [
        'get_callback' => function (array $post, $attr, $request = null) {
            $source_id = (int) $post['id'];
            $post_id = $source_id;

            $lang = null;
            if ($request instanceof WP_REST_Request) {
                $raw = $request->get_param('translate');
                if (is_string($raw) && $raw !== '') {
                    $lang = strtoupper($raw);
                }
            }

            if ($lang) {
                $translated_id = (int) get_post_meta($source_id, $lang, true);
                if ($translated_id > 0) {
                    $post_id = $translated_id;
                }
            }

            $payload = rs_project_meta_to_payload($post_id);

            // Tradução PT sem campos preenchidos → herda do EN.
            if ($post_id !== $source_id) {
                $source_payload = rs_project_meta_to_payload($source_id);
                if (empty($payload['accordion']) && !empty($source_payload['accordion'])) {
                    $payload['accordion'] = $source_payload['accordion'];
                }
                if (empty($payload['gallery']) && !empty($source_payload['gallery'])) {
                    $payload['gallery'] = $source_payload['gallery'];
                }
                if (empty($payload['heroImage']['url']) && !empty($source_payload['heroImage']['url'])) {
                    $payload['heroImage'] = $source_payload['heroImage'];
                }
                if (empty($payload['logoImage']['url']) && !empty($source_payload['logoImage']['url'])) {
                    $payload['logoImage'] = $source_payload['logoImage'];
                }
            }

            return $payload;
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

function rs_project_render_accordion_row(int $index, array $section, bool $is_template = false): void {
    $title = $section['title'] ?? '';
    $body = $section['body'] ?? '';
    $name_prefix = $is_template ? 'rs_project_accordion[__INDEX__]' : 'rs_project_accordion[' . $index . ']';
    $editor_id = $is_template ? 'rs_project_accordion_body___INDEX__' : 'rs_project_accordion_body_' . $index;
    $display = $is_template ? ' style="display:none;"' : '';
    ?>
    <fieldset class="rs-project-accordion-row" data-index="<?php echo esc_attr($is_template ? '__INDEX__' : (string) $index); ?>"<?php echo $display; ?>>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
            <legend style="font-weight:600;padding:0;margin:0;"><strong>Seção do acordeão</strong></legend>
            <button type="button" class="button-link-delete rs-project-remove-accordion">Remover</button>
        </div>

        <div style="margin:0 0 12px;">
            <label style="display:block;font-weight:500;margin-bottom:4px;">Título</label>
            <input
                type="text"
                style="width:100%;"
                class="rs-project-accordion-title"
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
                    class="rs-project-accordion-body large-text"
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

function rs_project_render_gallery_row(int $index, int $attachment_id, bool $is_template = false): void {
    $name_prefix = $is_template ? 'rs_project_gallery[__INDEX__]' : 'rs_project_gallery[' . $index . ']';
    $field_id = $is_template ? 'rs_project_gallery_image___INDEX__' : 'rs_project_gallery_image_' . $index;
    $display = $is_template ? ' style="display:none;"' : '';
    ?>
    <div class="rs-project-gallery-row" data-index="<?php echo esc_attr($is_template ? '__INDEX__' : (string) $index); ?>"<?php echo $display; ?>>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin:0 0 12px;padding:12px;border:1px solid #dcdcde;border-radius:4px;background:#fff;">
            <div style="flex:1;min-width:0;">
                <?php rs_render_media_field($name_prefix . '[image_id]', '', $attachment_id, $field_id, !$is_template, 'media'); ?>
            </div>
            <?php if (!$is_template) : ?>
                <button type="button" class="button-link-delete rs-project-remove-gallery" style="flex-shrink:0;margin-top:2px;">Remover</button>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function rs_project_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_project_save', 'rs_project_nonce');

    $locale = rs_project_guess_locale($post->ID);
    $hero_id = rs_project_get_hero_id($post->ID);
    $logo_id = (int) get_post_meta($post->ID, RS_PROJECT_LOGO_KEY, true);
    $accordion_sections = rs_project_get_accordion_sections($post->ID);
    $gallery_ids = rs_project_get_gallery_ids($post->ID);
    $featured = (bool) get_post_meta($post->ID, RS_PROJECT_FEATURED_KEY, true);
    $vignette_meta = get_post_meta($post->ID, RS_PROJECT_VIGNETTE_KEY, true);
    $show_vignette = $vignette_meta === '' ? true : (bool) $vignette_meta;

    if (!$accordion_sections) {
        $accordion_sections = rs_project_default_accordion_sections($locale);
    }

    if (!$gallery_ids) {
        $gallery_ids = [];
    }

    $locale_badge = function_exists('rs_project_locale_badge') ? rs_project_locale_badge((int) $post->ID) : 'EN';
    echo '<p style="margin-top:0;color:#646970;">Edite aqui o que aparece em <code>/project/{slug}</code>. Este post é a versão <strong>' . esc_html($locale_badge) . '</strong>. O <strong>resumo</strong> (coluna esquerda do site) fica no campo <em>Resumo</em> da barra lateral. Com o site em PT, o front lê a versão PT — abra-a pela coluna <strong>Language → PT</strong>. Não crie projetos duplicados com o mesmo título; use EN/PT. <em>(Plugin Tradução v1.1.9)</em></p>';

    echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Hero (topo)</strong></legend>';
    rs_render_media_field('rs_project_hero_id', 'Fundo (imagem, GIF ou vídeo mp4) — proporção 1:1 no mobile, 16:9 no desktop', $hero_id, 'rs_project_hero_id', true, 'media');
    rs_render_media_field('rs_project_logo_id', 'Logo / vignette — aparece sobre a mídia no desktop (canto inferior esquerdo)', $logo_id, 'rs_project_logo_id');
    echo '<p style="margin:0 0 10px;"><label><input type="checkbox" name="rs_project_show_vignette" value="1"' . checked($show_vignette, true, false) . ' /> Exibir vignette (logo) no canto inferior esquerdo</label></p>';
    echo '<p style="margin:0;color:#646970;font-size:12px;">A <em>imagem destacada</em> da barra lateral só é usada como fallback se o campo Logo acima estiver vazio.</p>';
    echo '</fieldset>';

    echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Home</strong></legend>';
    echo '<p style="margin:0;"><label><input type="checkbox" name="rs_project_featured_home" value="1"' . checked($featured, true, false) . ' /> Destaque na home (apenas <strong>um</strong> projeto deve estar marcado)</label></p>';
    echo '</fieldset>';

    echo '<fieldset style="margin:0 0 20px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Acordeão (coluna direita)</strong></legend>';
    echo '<div id="rs-project-accordion-list">';
    foreach ($accordion_sections as $index => $section) {
        rs_project_render_accordion_row((int) $index, $section);
    }
    echo '</div>';
    rs_project_render_accordion_row(0, ['title' => '', 'body' => ''], true);
    echo '<p style="margin:12px 0 0;"><button type="button" class="button button-secondary" id="rs-project-add-accordion">+ Adicionar seção</button></p>';
    echo '<input type="hidden" id="rs-project-accordion-json" name="rs_project_accordion_json" value="" />';
    echo '</fieldset>';

    echo '<fieldset style="margin:0 0 10px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Galeria</strong></legend>';
    echo '<p style="margin:0 0 12px;color:#646970;font-size:12px;">Imagens, GIFs e vídeos (mp4). A ordem aqui define a ordem no site; o layout se adapta à proporção de cada mídia.</p>';
    echo '<p id="rs-project-gallery-empty" class="description"' . ($gallery_ids ? ' style="display:none;"' : '') . '>Nenhuma mídia na galeria.</p>';
    echo '<div id="rs-project-gallery-list">';
    foreach ($gallery_ids as $index => $attachment_id) {
        rs_project_render_gallery_row((int) $index, (int) $attachment_id);
    }
    echo '</div>';
    rs_project_render_gallery_row(0, 0, true);
    echo '<p style="margin:12px 0 0;"><button type="button" class="button button-primary" id="rs-project-add-gallery">+ Adicionar mídia</button></p>';
    echo '<input type="hidden" id="rs-project-gallery-json" name="rs_project_gallery_json" value="" />';
    echo '</fieldset>';
}

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_project_parse_accordion_from_request(): array {
    if (!empty($_POST['rs_project_accordion_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_project_accordion_json']), true);
        if (is_array($decoded)) {
            return rs_project_normalize_accordion_sections($decoded);
        }
    }

    if (!isset($_POST['rs_project_accordion']) || !is_array($_POST['rs_project_accordion'])) {
        return [];
    }

    $sections = [];
    foreach (wp_unslash($_POST['rs_project_accordion']) as $key => $section) {
        if ($key === '__INDEX__' || !is_array($section)) {
            continue;
        }

        $title = trim(wp_strip_all_tags((string) ($section['title'] ?? '')));
        $body = wp_kses_post((string) ($section['body'] ?? ''));

        if ($title === '' && $body === '') {
            continue;
        }

        $sections[] = [
            'title' => $title !== '' ? $title : 'Seção',
            'body'  => $body,
        ];
    }

    return $sections;
}

/**
 * @return array<int, int>
 */
function rs_project_parse_gallery_from_request(): array {
    if (!empty($_POST['rs_project_gallery_json'])) {
        $decoded = json_decode(wp_unslash((string) $_POST['rs_project_gallery_json']), true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded)));
        }
    }

    if (!isset($_POST['rs_project_gallery']) || !is_array($_POST['rs_project_gallery'])) {
        return [];
    }

    $ids = [];
    foreach (wp_unslash($_POST['rs_project_gallery']) as $key => $row) {
        if ($key === '__INDEX__' || !is_array($row)) {
            continue;
        }

        $attachment_id = (int) ($row['image_id'] ?? 0);
        if ($attachment_id > 0) {
            $ids[] = $attachment_id;
        }
    }

    return $ids;
}

add_action('save_post_project', function (int $post_id) {
    if (!isset($_POST['rs_project_nonce']) || !wp_verify_nonce($_POST['rs_project_nonce'], 'rs_project_save')) {
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

    $hero_id = isset($_POST['rs_project_hero_id']) ? (int) $_POST['rs_project_hero_id'] : 0;
    if ($hero_id > 0) {
        update_post_meta($post_id, RS_PROJECT_HERO_KEY, $hero_id);
        update_post_meta($post_id, 'etc_upload_image', $hero_id);
    } else {
        delete_post_meta($post_id, RS_PROJECT_HERO_KEY);
        delete_post_meta($post_id, 'etc_upload_image');
    }

    $logo_id = isset($_POST['rs_project_logo_id']) ? (int) $_POST['rs_project_logo_id'] : 0;
    if ($logo_id > 0) {
        update_post_meta($post_id, RS_PROJECT_LOGO_KEY, $logo_id);
        set_post_thumbnail($post_id, $logo_id);
    } else {
        delete_post_meta($post_id, RS_PROJECT_LOGO_KEY);
    }

    $featured = !empty($_POST['rs_project_featured_home']) ? 1 : 0;
    update_post_meta($post_id, RS_PROJECT_FEATURED_KEY, $featured);

    // Checkbox: ausente no POST = false.
    $show_vignette = !empty($_POST['rs_project_show_vignette']) ? 1 : 0;
    update_post_meta($post_id, RS_PROJECT_VIGNETTE_KEY, $show_vignette);

    $accordion = rs_project_parse_accordion_from_request();
    update_post_meta($post_id, RS_PROJECT_ACCORDION_KEY, wp_json_encode($accordion, JSON_UNESCAPED_UNICODE));

    foreach (array_keys(RS_PROJECT_LEGACY_ACCORDION_LABELS) as $index) {
        $legacy_body = $accordion[$index - 1]['body'] ?? '';
        update_post_meta($post_id, "rs_project_acc_{$index}_body", $legacy_body);
    }

    $gallery_ids = rs_project_parse_gallery_from_request();
    update_post_meta($post_id, RS_PROJECT_GALLERY_KEY, implode(',', $gallery_ids));
}, 10);

rs_enqueue_admin_media_picker(['project']);

function rs_project_render_admin_footer_script(): void {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'project') {
        return;
    }
    ?>
    <script>
    jQuery(function ($) {
        const paragraphEditorSettings = <?php echo wp_json_encode(rs_rich_text_js_settings('paragraph')); ?>;
        let nextAccordionIndex = $('#rs-project-accordion-list .rs-project-accordion-row').length;
        let nextGalleryIndex = $('#rs-project-gallery-list .rs-project-gallery-row:visible').length;

        function syncGalleryEmptyState() {
            const hasRows = $('#rs-project-gallery-list .rs-project-gallery-row:visible').length > 0;
            $('#rs-project-gallery-empty').toggle(!hasRows);
        }

        function syncAllEditors() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
            $('textarea[id^="rs_project_accordion_body_"]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') === -1 && typeof wp !== 'undefined' && wp.editor && wp.editor.save) {
                    wp.editor.save(id);
                }
            });
        }

        function readAccordionBody(textarea) {
            const editorId = textarea.attr('id');
            if (editorId && typeof tinymce !== 'undefined') {
                const editor = tinymce.get(editorId);
                if (editor) {
                    return editor.getContent();
                }
            }
            return textarea.val() || '';
        }

        function collectAccordionJson() {
            syncAllEditors();
            const sections = [];
            $('#rs-project-accordion-list .rs-project-accordion-row').each(function () {
                const row = $(this);
                const title = (row.find('.rs-project-accordion-title').val() || '').trim();
                const body = readAccordionBody(row.find('textarea[id^="rs_project_accordion_body_"]'));
                if (!title && !body) {
                    return;
                }
                sections.push({
                    title: title || 'Seção',
                    body,
                });
            });
            $('#rs-project-accordion-json').val(JSON.stringify(sections));
        }

        function collectGalleryJson() {
            const ids = [];
            $('#rs-project-gallery-list .rs-project-gallery-row:visible').each(function () {
                const imageId = parseInt($(this).find('input[data-rs-cap-image]').val(), 10) || 0;
                if (imageId > 0) {
                    ids.push(imageId);
                }
            });
            $('#rs-project-gallery-json').val(JSON.stringify(ids));
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

        function assignAccordionNames(row, index) {
            row.find('.rs-project-accordion-title').attr('name', 'rs_project_accordion[' + index + '][title]');
            row.find('textarea[id^="rs_project_accordion_body_"]').attr('name', 'rs_project_accordion[' + index + '][body]');
        }

        function assignGalleryNames(row, index) {
            row.find('input[data-rs-cap-image]').attr('name', 'rs_project_gallery[' + index + '][image_id]');
        }

        $('#rs-project-add-accordion').on('click', function (event) {
            event.preventDefault();
            const template = $('.rs-project-accordion-row[data-index="__INDEX__"]').first().clone();
            template.removeAttr('style');
            template.attr('data-index', String(nextAccordionIndex));
            template.find('.rs-project-accordion-title').val('');
            template.find('textarea').val('');
            template.find('[id]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') !== -1) {
                    $(this).attr('id', id.replace(/__INDEX__/g, String(nextAccordionIndex)));
                }
            });
            assignAccordionNames(template, nextAccordionIndex);
            $('#rs-project-accordion-list').append(template);
            initEditor('rs_project_accordion_body_' + nextAccordionIndex);
            nextAccordionIndex += 1;
        });

        $(document).on('click', '.rs-project-remove-accordion', function (event) {
            event.preventDefault();
            if ($('#rs-project-accordion-list .rs-project-accordion-row').length <= 1) {
                window.alert('Mantenha pelo menos uma seção.');
                return;
            }
            const row = $(this).closest('.rs-project-accordion-row');
            const editorId = row.find('textarea[id^="rs_project_accordion_body_"]').attr('id');
            removeEditor(editorId);
            row.remove();
            $('#rs-project-accordion-list .rs-project-accordion-row').each(function (i) {
                $(this).attr('data-index', String(i));
                assignAccordionNames($(this), i);
            });
            nextAccordionIndex = $('#rs-project-accordion-list .rs-project-accordion-row').length;
        });

        $('#rs-project-add-gallery').on('click', function (event) {
            event.preventDefault();
            const template = $('.rs-project-gallery-row[data-index="__INDEX__"]').first().clone();
            template.removeAttr('style');
            template.attr('data-index', String(nextGalleryIndex));
            template.find('input[data-rs-cap-image]').val('0');
            template.find('.rs-media-preview').empty();
            template.find('[id]').each(function () {
                const id = $(this).attr('id');
                if (id && id.indexOf('__INDEX__') !== -1) {
                    $(this).attr('id', id.replace(/__INDEX__/g, String(nextGalleryIndex)));
                }
            });
            template.find('[data-target]').each(function () {
                const target = $(this).attr('data-target');
                if (target) {
                    $(this).attr('data-target', target.replace(/__INDEX__/g, String(nextGalleryIndex)));
                }
            });
            assignGalleryNames(template, nextGalleryIndex);
            $('#rs-project-gallery-list').append(template);
            nextGalleryIndex += 1;
            syncGalleryEmptyState();
        });

        $(document).on('click', '.rs-project-remove-gallery', function (event) {
            event.preventDefault();
            $(this).closest('.rs-project-gallery-row').remove();
            $('#rs-project-gallery-list .rs-project-gallery-row:visible').each(function (i) {
                $(this).attr('data-index', String(i));
                assignGalleryNames($(this), i);
                $(this).find('[id^="rs_project_gallery_image_"]').each(function () {
                    const oldId = $(this).attr('id');
                    const newId = 'rs_project_gallery_image_' + i;
                    $(this).attr('id', newId);
                    $(this).closest('.rs-media-field').find('[data-target="' + oldId + '"]').attr('data-target', newId);
                });
            });
            nextGalleryIndex = $('#rs-project-gallery-list .rs-project-gallery-row:visible').length;
            syncGalleryEmptyState();
        });

        $('#post').on('submit', function () {
            collectAccordionJson();
            collectGalleryJson();
        });

        syncGalleryEmptyState();
    });
    </script>
    <?php
}

add_action('admin_footer-post.php', 'rs_project_render_admin_footer_script');
add_action('admin_footer-post-new.php', 'rs_project_render_admin_footer_script');
