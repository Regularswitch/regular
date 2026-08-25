<?php
/**
 * CPT contact — post único bilíngue (EN + PT).
 */

if (defined('RS_CONTACT_FIELDS_LOADED')) {
    return;
}
define('RS_CONTACT_FIELDS_LOADED', true);

const RS_CONTACT_I18N_KEY = 'rs_contact_i18n';
const RS_CONTACT_HERO_IMAGE_KEY = 'rs_contact_hero_image_id';
const RS_CONTACT_HERO_VIDEO_KEY = 'rs_contact_hero_video_id';
const RS_CONTACT_HEADLINE_KEY = 'rs_contact_headline';
const RS_CONTACT_BLOCKS_KEY = 'rs_contact_blocks';
const RS_CONTACT_INFO_KEY = 'rs_contact_info';

/**
 * @return array<string, string>
 */
function rs_contact_default_info(string $locale): array {
    if ($locale === 'pt') {
        return [
            'contact_title' => 'CONTATO', 'contact_location' => 'São Paulo – Brasil',
            'contact_phone' => '+55 11 (9) 4540-8448', 'contact_phone_tel' => '+5511945408448',
            'contact_email' => 'contact@regularswitch.com', 'address_title' => 'ENDEREÇO',
            'address_location' => 'São Paulo – Brasil', 'address_street' => 'Rua da Consolação, 65',
            'jobs_title' => 'VAGAS', 'jobs_text' => 'No momento não estamos contratando.',
            'jobs_email' => 'join-us@regularswitch.com', 'internship_title' => 'ESTÁGIO',
            'internship_text' => 'Envie um e-mail para se candidatar.',
            'internship_email' => 'join-us@regularswitch.com',
        ];
    }

    return [
        'contact_title' => 'CONTACT', 'contact_location' => 'São Paulo – Brazil',
        'contact_phone' => '+55 11 (9) 4540-8448', 'contact_phone_tel' => '+5511945408448',
        'contact_email' => 'contact@regularswitch.com', 'address_title' => 'ADDRESS',
        'address_location' => 'São Paulo – Brazil', 'address_street' => 'Rua da Consolação, 65',
        'jobs_title' => 'JOBS', 'jobs_text' => 'We are not hiring at the moment.',
        'jobs_email' => 'join-us@regularswitch.com', 'internship_title' => 'INTERNSHIP',
        'internship_text' => 'Send us an e-mail to apply.',
        'internship_email' => 'join-us@regularswitch.com',
    ];
}

function rs_contact_default_headline(string $locale): string {
    return $locale === 'pt'
        ? 'Vamos <strong>conversar</strong> sobre o seu <strong>próximo projeto</strong>.'
        : 'Let\'s <strong>talk</strong> about your <strong>next project</strong>.';
}

/**
 * @param array<string, mixed> $raw
 * @return array<string, string>
 */
function rs_contact_info_is_plain_key(string $key): bool {
    return str_contains($key, 'email')
        || str_contains($key, 'phone')
        || str_ends_with($key, '_tel');
}

function rs_contact_normalize_info(array $raw, string $locale = 'en'): array {
    $out = rs_contact_default_info($locale);
    foreach ($out as $key => $_default) {
        if (!array_key_exists($key, $raw)) {
            continue;
        }
        $value = (string) $raw[$key];
        $out[$key] = rs_contact_info_is_plain_key($key)
            ? trim(wp_strip_all_tags($value))
            : trim(wp_kses_post($value));
    }
    if ($out['contact_phone_tel'] === '' && $out['contact_phone'] !== '') {
        $digits = preg_replace('/\D+/', '', wp_strip_all_tags($out['contact_phone']));
        $out['contact_phone_tel'] = is_string($digits) ? $digits : '';
    }
    return $out;
}

function rs_contact_default_locale(string $locale): array {
    return [
        'headline' => rs_contact_default_headline($locale),
        'info'     => rs_contact_default_info($locale),
    ];
}

function rs_contact_i18n_default(): array {
    return [
        'v' => 1,
        'shared' => ['hero_image_id' => 0, 'hero_video_id' => 0],
        'locales' => [
            'en' => rs_contact_default_locale('en'),
            'pt' => rs_contact_default_locale('pt'),
        ],
    ];
}

function rs_contact_i18n_normalize(array $raw): array {
    $data = rs_contact_i18n_default();
    $shared = is_array($raw['shared'] ?? null) ? $raw['shared'] : [];
    $en_raw = is_array($raw['locales']['en'] ?? null) ? $raw['locales']['en'] : [];
    $data['shared'] = [
        'hero_image_id' => max(0, (int) ($shared['hero_image_id'] ?? $en_raw['hero_image_id'] ?? 0)),
        'hero_video_id' => max(0, (int) ($shared['hero_video_id'] ?? $en_raw['hero_video_id'] ?? 0)),
    ];

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $info = is_array($loc['info'] ?? null) ? $loc['info'] : [];
        $data['locales'][$locale] = [
            'headline' => wp_kses_post((string) ($loc['headline'] ?? rs_contact_default_headline($locale))),
            'info'     => rs_contact_normalize_info($info, $locale),
        ];
    }
    return $data;
}

function rs_contact_locale_from_legacy_post(int $post_id, string $locale): array {
    if ($post_id <= 0) {
        return rs_contact_default_locale($locale);
    }
    $info = function_exists('rs_meta_get_array')
        ? rs_meta_get_array($post_id, RS_CONTACT_INFO_KEY)
        : get_post_meta($post_id, RS_CONTACT_INFO_KEY, true);
    return [
        'headline'     => (string) (get_post_meta($post_id, RS_CONTACT_HEADLINE_KEY, true) ?: rs_contact_default_headline($locale)),
        'info'         => rs_contact_normalize_info(is_array($info) ? $info : [], $locale),
        'hero_image_id'=> (int) get_post_meta($post_id, RS_CONTACT_HERO_IMAGE_KEY, true),
        'hero_video_id'=> (int) get_post_meta($post_id, RS_CONTACT_HERO_VIDEO_KEY, true),
    ];
}

function rs_contact_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_CONTACT_I18N_KEY)
        : get_post_meta($post_id, RS_CONTACT_I18N_KEY, true);
    if (is_array($raw)) {
        $data = rs_contact_i18n_normalize($raw);
        if ((int) $data['shared']['hero_image_id'] <= 0 && function_exists('rs_page_heroes_get_image_id')) {
            $data['shared']['hero_image_id'] = (int) rs_page_heroes_get_image_id('contact');
        }
        if ((int) $data['shared']['hero_video_id'] <= 0 && function_exists('rs_page_heroes_get_video_id')) {
            $data['shared']['hero_video_id'] = (int) rs_page_heroes_get_video_id('contact');
        }
        return $data;
    }

    $data = rs_contact_i18n_default();
    $data['locales']['en'] = rs_contact_locale_from_legacy_post($post_id, 'en');
    $data['shared']['hero_image_id'] = (int) get_post_meta($post_id, RS_CONTACT_HERO_IMAGE_KEY, true);
    $data['shared']['hero_video_id'] = (int) get_post_meta($post_id, RS_CONTACT_HERO_VIDEO_KEY, true);
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_contact_locale_from_legacy_post($pt_id, 'pt');
    }
    $data = rs_contact_i18n_normalize($data);
    if ((int) $data['shared']['hero_image_id'] <= 0 && function_exists('rs_page_heroes_get_image_id')) {
        $data['shared']['hero_image_id'] = (int) rs_page_heroes_get_image_id('contact');
    }
    if ((int) $data['shared']['hero_video_id'] <= 0 && function_exists('rs_page_heroes_get_video_id')) {
        $data['shared']['hero_video_id'] = (int) rs_page_heroes_get_video_id('contact');
    }
    return $data;
}

/**
 * Mantém a assinatura legada; agora lê o locale do documento bilíngue.
 */
function rs_contact_get_info(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_contact_i18n_get($post_id);
    return rs_contact_normalize_info(
        is_array($data['locales'][$locale]['info'] ?? null) ? $data['locales'][$locale]['info'] : [],
        $locale
    );
}

/**
 * @param array<string, string> $info
 * @return array<int, array{title: string, body: string}>
 */
function rs_contact_info_to_blocks(array $info): array {
    $phone_digits = preg_replace('/\D+/', '', $info['contact_phone_tel'] !== '' ? $info['contact_phone_tel'] : $info['contact_phone']);
    $phone_href = is_string($phone_digits) && $phone_digits !== '' ? 'tel:+' . $phone_digits : '';
    $groups = [
        [$info['contact_title'], [
            $info['contact_location'] !== '' ? esc_html($info['contact_location']) : '',
            $info['contact_phone'] !== '' ? ($phone_href !== '' ? '<a href="' . esc_url($phone_href) . '">' . esc_html($info['contact_phone']) . '</a>' : esc_html($info['contact_phone'])) : '',
            $info['contact_email'] !== '' ? '<a href="' . esc_url('mailto:' . $info['contact_email']) . '">' . esc_html($info['contact_email']) . '</a>' : '',
        ]],
        [$info['address_title'], [
            $info['address_location'] !== '' ? esc_html($info['address_location']) : '',
            $info['address_street'] !== '' ? esc_html($info['address_street']) : '',
        ]],
        [$info['jobs_title'], [
            $info['jobs_text'] !== '' ? esc_html($info['jobs_text']) : '',
            $info['jobs_email'] !== '' ? '<a href="' . esc_url('mailto:' . $info['jobs_email']) . '">' . esc_html($info['jobs_email']) . '</a>' : '',
        ]],
        [$info['internship_title'], [
            $info['internship_text'] !== '' ? esc_html($info['internship_text']) : '',
            $info['internship_email'] !== '' ? '<a href="' . esc_url('mailto:' . $info['internship_email']) . '">' . esc_html($info['internship_email']) . '</a>' : '',
        ]],
    ];
    $blocks = [];
    foreach ($groups as [$title, $lines]) {
        $lines = array_values(array_filter($lines));
        if ($title !== '' && $lines) {
            $blocks[] = ['title' => $title, 'body' => '<p>' . implode('<br>', $lines) . '</p>'];
        }
    }
    return $blocks;
}

function rs_contact_get_blocks(int $post_id, string $locale = 'en'): array {
    return rs_contact_info_to_blocks(rs_contact_get_info($post_id, $locale));
}

function rs_contact_meta_to_payload(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_contact_i18n_get($post_id);
    $loc = $data['locales'][$locale] ?? rs_contact_default_locale($locale);
    $shared = $data['shared'];
    $image_id = (int) ($shared['hero_image_id'] ?? 0);
    $video_id = (int) ($shared['hero_video_id'] ?? 0);

    return [
        'heroImage' => $image_id > 0 ? (string) wp_get_attachment_url($image_id) : '',
        'heroVideo' => $video_id > 0 ? (string) wp_get_attachment_url($video_id) : '',
        'headline'  => (string) ($loc['headline'] ?? ''),
        'blocks'    => rs_contact_info_to_blocks(rs_contact_normalize_info((array) ($loc['info'] ?? []), $locale)),
    ];
}

function rs_contact_get_post_id_by_locale(string $locale = 'en'): int {
    return function_exists('rs_section_i18n_canonical_id') ? rs_section_i18n_canonical_id('contact') : 0;
}

function rs_contact_sync_legacy_meta(int $post_id, array $data): void {
    $en = $data['locales']['en'] ?? rs_contact_default_locale('en');
    $shared = $data['shared'] ?? [];
    update_post_meta($post_id, RS_CONTACT_HEADLINE_KEY, (string) ($en['headline'] ?? ''));
    update_post_meta($post_id, RS_CONTACT_HERO_IMAGE_KEY, (string) max(0, (int) ($shared['hero_image_id'] ?? 0)));
    update_post_meta($post_id, RS_CONTACT_HERO_VIDEO_KEY, (string) max(0, (int) ($shared['hero_video_id'] ?? 0)));
    $info = rs_contact_normalize_info((array) ($en['info'] ?? []), 'en');
    $blocks = rs_contact_info_to_blocks($info);
    if (function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, RS_CONTACT_INFO_KEY, $info);
        rs_meta_update_array($post_id, RS_CONTACT_BLOCKS_KEY, $blocks);
    } else {
        update_post_meta($post_id, RS_CONTACT_INFO_KEY, $info);
        update_post_meta($post_id, RS_CONTACT_BLOCKS_KEY, $blocks);
    }
}

function rs_contact_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }
    $id = rs_section_i18n_migrate_twins(
        'contact',
        RS_CONTACT_I18N_KEY,
        'rs_contact_i18n_migrated_v1',
        'Contact',
        static fn(int $post_id, string $locale): array => rs_contact_locale_from_legacy_post($post_id, $locale),
        'rs_contact_i18n_normalize'
    );
    if ($id > 0) {
        rs_contact_sync_legacy_meta($id, rs_contact_i18n_get($id));
    }
}

add_action('init', function () {
    register_post_meta('contact', RS_CONTACT_I18N_KEY, [
        'single' => true, 'type' => 'array', 'show_in_rest' => false,
        'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
    ]);
    foreach ([RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY, RS_CONTACT_HEADLINE_KEY, RS_CONTACT_BLOCKS_KEY, RS_CONTACT_INFO_KEY] as $key) {
        register_post_meta('contact', $key, [
            'single' => true, 'type' => 'string', 'show_in_rest' => false,
            'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
        ]);
    }
}, 20);
add_action('init', 'rs_contact_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('contact', 'contact_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request')
                ? rs_section_i18n_locale_from_request($request)
                : 'en';
            return rs_contact_meta_to_payload((int) $post['id'], $locale);
        },
        'schema' => ['description' => 'Dados estruturados da página Contato', 'type' => 'object', 'context' => ['view', 'edit']],
    ]);
});

add_action('add_meta_boxes_contact', function () {
    add_meta_box('rs_contact_fields', 'Conteúdo da página Contato', 'rs_contact_render_meta_box', 'contact', 'normal', 'high');
    remove_meta_box('postcustom', 'contact', 'normal');
}, 10);

function rs_contact_render_text_field(string $name, string $label, string $value, string $field_key, string $locale): void {
    $id = 'rs_contact_' . $locale . '_' . $field_key;
    echo '<p class="rs-admin-text-field" style="margin:0 0 12px;"><label for="' . esc_attr($id) . '" style="display:block;font-weight:500;margin-bottom:4px;">' . esc_html($label) . '</label>';
    if (rs_contact_info_is_plain_key($field_key)) {
        echo '<input type="text" class="widefat" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
    } else {
        $profile = str_contains($field_key, '_text') ? 'paragraph' : 'compact';
        rs_render_rich_text_field($id, $name, $value, $profile);
    }
    echo '</p>';
}

function rs_contact_render_locale_fields(string $locale, array $loc): void {
    $info = rs_contact_normalize_info((array) ($loc['info'] ?? []), $locale);
    $prefix = 'rs_contact_i18n_input[' . $locale . ']';
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Headline</strong></legend>';
    rs_render_rich_text_field('rs_contact_headline_' . $locale, $prefix . '[headline]', (string) ($loc['headline'] ?? ''), 'compact');
    echo '</fieldset>';

    $groups = [
        'Contato' => ['contact_title' => 'Título', 'contact_location' => 'Cidade / localização', 'contact_phone' => 'Telefone (exibição)', 'contact_phone_tel' => 'Telefone para o link (só números)', 'contact_email' => 'E-mail'],
        'Endereço' => ['address_title' => 'Título', 'address_location' => 'Cidade / localização', 'address_street' => 'Rua / endereço'],
        'Vagas' => ['jobs_title' => 'Título', 'jobs_text' => 'Texto', 'jobs_email' => 'E-mail'],
        'Estágio' => ['internship_title' => 'Título', 'internship_text' => 'Texto', 'internship_email' => 'E-mail'],
    ];

    echo '<div data-rs-accordion class="rs-contact-accordion">';
    $index = 0;
    foreach ($groups as $label => $fields) {
        rs_metabox_accordion_item_open($label, $index === 0);
        foreach ($fields as $key => $field_label) {
            rs_contact_render_text_field($prefix . '[info][' . $key . ']', $field_label, $info[$key], $key, $locale);
        }
        rs_metabox_accordion_item_close();
        $index++;
    }
    echo '</div>';
}

function rs_contact_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_contact_save', 'rs_contact_nonce');
    $id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id((int) $post->ID) : (int) $post->ID;
    $data = rs_contact_i18n_get($id);
    echo '<p style="margin-top:0;color:#646970;">Um único post. Hero compartilhado; textos em English e Português. ' . (function_exists('rs_plugin_version_markup') ? rs_plugin_version_markup() : '') . '</p>';
    $shared = $data['shared'];
    echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Hero compartilhado</strong></legend>';
    rs_render_media_field('rs_contact_shared[hero_image_id]', 'Imagem', (int) $shared['hero_image_id'], 'rs_contact_hero_image_shared', true, 'image');
    rs_render_media_field('rs_contact_shared[hero_video_id]', 'Vídeo (mp4) — opcional', (int) $shared['hero_video_id'], 'rs_contact_hero_video_shared', true, 'video');
    echo '</fieldset>';
    echo '<div class="rs-metabox-tabs" data-rs-tabs><div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button></div>';
    echo '<div class="rs-metabox-tabpanel is-active" data-tab="en" role="tabpanel">';
    rs_contact_render_locale_fields('en', $data['locales']['en']);
    echo '</div><div class="rs-metabox-tabpanel" data-tab="pt" role="tabpanel" hidden>';
    rs_contact_render_locale_fields('pt', $data['locales']['pt']);
    echo '</div></div>';
}

add_action('save_post_contact', function (int $post_id) {
    if (!isset($_POST['rs_contact_nonce']) || !wp_verify_nonce($_POST['rs_contact_nonce'], 'rs_contact_save')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)
        || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $data = rs_contact_i18n_get($post_id);
    $shared = isset($_POST['rs_contact_shared']) && is_array($_POST['rs_contact_shared']) ? wp_unslash($_POST['rs_contact_shared']) : [];
    $data['shared'] = [
        'hero_image_id' => max(0, (int) ($shared['hero_image_id'] ?? 0)),
        'hero_video_id' => max(0, (int) ($shared['hero_video_id'] ?? 0)),
    ];
    $raw = isset($_POST['rs_contact_i18n_input']) && is_array($_POST['rs_contact_i18n_input'])
        ? wp_unslash($_POST['rs_contact_i18n_input'])
        : [];
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        $info_raw = is_array($loc['info'] ?? null) ? $loc['info'] : [];
        $clean_info = [];
        foreach (array_keys(rs_contact_default_info($locale)) as $key) {
            $raw_val = (string) ($info_raw[$key] ?? '');
            $clean_info[$key] = rs_contact_info_is_plain_key($key)
                ? sanitize_text_field($raw_val)
                : wp_kses_post($raw_val);
        }
        $data['locales'][$locale] = [
            'headline' => wp_kses_post((string) ($loc['headline'] ?? '')),
            'info' => $clean_info,
        ];
    }
    $data = rs_contact_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_CONTACT_I18N_KEY, $data);
    } else {
        update_post_meta($post_id, RS_CONTACT_I18N_KEY, $data);
    }
    rs_contact_sync_legacy_meta($post_id, $data);
}, 10);

function rs_contact_ensure_locale_posts(): void {
    // Legado no-op: a migração mantém um único post.
}

function rs_contact_reconcile_duplicate_locales_once(): void {
    // Legado no-op: rs_section_i18n_migrate_twins trata os gêmeos.
}

function rs_copy_contact_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}

if (function_exists('rs_enqueue_admin_media_picker')) {
    rs_enqueue_admin_media_picker(['contact']);
}
