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
const RS_CONTACT_INFO_KEY = 'rs_contact_info';

/**
 * @return array<string, string>
 */
function rs_contact_default_info(string $locale): array {
    if ($locale === 'pt') {
        return [
            'contact_title'     => 'CONTATO',
            'contact_location'  => 'São Paulo – Brasil',
            'contact_phone'     => '+55 11 (9) 4540-8448',
            'contact_phone_tel' => '+5511945408448',
            'contact_email'     => 'contact@regularswitch.com',
            'address_title'     => 'ENDEREÇO',
            'address_location'  => 'São Paulo – Brasil',
            'address_street'    => 'Rua da Consolação, 65',
            'jobs_title'        => 'VAGAS',
            'jobs_text'         => 'No momento não estamos contratando.',
            'jobs_email'        => 'join-us@regularswitch.com',
            'internship_title'  => 'ESTÁGIO',
            'internship_text'   => 'Envie um e-mail para se candidatar.',
            'internship_email'  => 'join-us@regularswitch.com',
        ];
    }

    return [
        'contact_title'     => 'CONTACT',
        'contact_location'  => 'São Paulo – Brazil',
        'contact_phone'     => '+55 11 (9) 4540-8448',
        'contact_phone_tel' => '+5511945408448',
        'contact_email'     => 'contact@regularswitch.com',
        'address_title'     => 'ADDRESS',
        'address_location'  => 'São Paulo – Brazil',
        'address_street'    => 'Rua da Consolação, 65',
        'jobs_title'        => 'JOBS',
        'jobs_text'         => 'We are not hiring at the moment.',
        'jobs_email'        => 'join-us@regularswitch.com',
        'internship_title'  => 'INTERNSHIP',
        'internship_text'   => 'Send us an e-mail to apply.',
        'internship_email'  => 'join-us@regularswitch.com',
    ];
}

/**
 * @param array<string, mixed> $raw
 * @return array<string, string>
 */
function rs_contact_normalize_info(array $raw, string $locale = 'en'): array {
    $defaults = rs_contact_default_info($locale);
    $out = $defaults;

    foreach ($defaults as $key => $_default) {
        if (array_key_exists($key, $raw)) {
            $out[$key] = trim(wp_strip_all_tags((string) $raw[$key]));
        }
    }

    if ($out['contact_phone_tel'] === '' && $out['contact_phone'] !== '') {
        $digits = preg_replace('/\D+/', '', $out['contact_phone']);
        $out['contact_phone_tel'] = is_string($digits) ? $digits : '';
    }

    return $out;
}

/**
 * @return array<string, string>
 */
function rs_contact_get_info(int $post_id): array {
    $locale = get_post_field('post_name', $post_id) === 'pt' ? 'pt' : 'en';
    $raw = get_post_meta($post_id, RS_CONTACT_INFO_KEY, true);
    $decoded = [];

    if (is_string($raw) && $raw !== '') {
        $parsed = json_decode($raw, true);
        if (is_array($parsed)) {
            $decoded = $parsed;
        }
    } elseif (is_array($raw)) {
        $decoded = $raw;
    }

    if ($decoded) {
        return rs_contact_normalize_info($decoded, $locale);
    }

    // Migração: se só existirem blocos legados, usa defaults (conteúdo editável nos novos campos).
    return rs_contact_default_info($locale);
}

/**
 * @param array<string, string> $info
 * @return array<int, array{title: string, body: string}>
 */
function rs_contact_info_to_blocks(array $info): array {
    $phone_digits = preg_replace(
        '/\D+/',
        '',
        $info['contact_phone_tel'] !== '' ? $info['contact_phone_tel'] : $info['contact_phone']
    );
    $phone_href = (is_string($phone_digits) && $phone_digits !== '')
        ? 'tel:+' . $phone_digits
        : '';

    $contact_email = $info['contact_email'];
    $jobs_email = $info['jobs_email'];
    $internship_email = $info['internship_email'];

    $contact_lines = array_filter([
        $info['contact_location'] !== '' ? esc_html($info['contact_location']) : '',
        $info['contact_phone'] !== ''
            ? ($phone_href !== ''
                ? '<a href="' . esc_url($phone_href) . '">' . esc_html($info['contact_phone']) . '</a>'
                : esc_html($info['contact_phone']))
            : '',
        $contact_email !== ''
            ? '<a href="' . esc_url('mailto:' . $contact_email) . '">' . esc_html($contact_email) . '</a>'
            : '',
    ]);

    $address_lines = array_filter([
        $info['address_location'] !== '' ? esc_html($info['address_location']) : '',
        $info['address_street'] !== '' ? esc_html($info['address_street']) : '',
    ]);

    $jobs_lines = array_filter([
        $info['jobs_text'] !== '' ? esc_html($info['jobs_text']) : '',
        $jobs_email !== ''
            ? '<a href="' . esc_url('mailto:' . $jobs_email) . '">' . esc_html($jobs_email) . '</a>'
            : '',
    ]);

    $internship_lines = array_filter([
        $info['internship_text'] !== '' ? esc_html($info['internship_text']) : '',
        $internship_email !== ''
            ? '<a href="' . esc_url('mailto:' . $internship_email) . '">' . esc_html($internship_email) . '</a>'
            : '',
    ]);

    $blocks = [];

    if ($info['contact_title'] !== '' && $contact_lines) {
        $blocks[] = [
            'title' => $info['contact_title'],
            'body'  => '<p>' . implode('<br>', $contact_lines) . '</p>',
        ];
    }

    if ($info['address_title'] !== '' && $address_lines) {
        $blocks[] = [
            'title' => $info['address_title'],
            'body'  => '<p>' . implode('<br>', $address_lines) . '</p>',
        ];
    }

    if ($info['jobs_title'] !== '' && $jobs_lines) {
        $blocks[] = [
            'title' => $info['jobs_title'],
            'body'  => '<p>' . implode('<br>', $jobs_lines) . '</p>',
        ];
    }

    if ($info['internship_title'] !== '' && $internship_lines) {
        $blocks[] = [
            'title' => $info['internship_title'],
            'body'  => '<p>' . implode('<br>', $internship_lines) . '</p>',
        ];
    }

    return $blocks;
}

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_contact_get_blocks(int $post_id): array {
    // Preferência: campos estruturados.
    $info = rs_contact_get_info($post_id);
    $from_info = rs_contact_info_to_blocks($info);
    if ($from_info) {
        return $from_info;
    }

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

function rs_contact_seed_post(int $post_id, string $locale): void {
    $headline = $locale === 'pt'
        ? 'Vamos <strong>conversar</strong> sobre o seu <strong>próximo projeto</strong>.'
        : 'Let\'s <strong>talk</strong> about your <strong>next project</strong>.';

    if (trim((string) get_post_meta($post_id, RS_CONTACT_HEADLINE_KEY, true)) === '') {
        update_post_meta($post_id, RS_CONTACT_HEADLINE_KEY, $headline);
    }

    $info_raw = get_post_meta($post_id, RS_CONTACT_INFO_KEY, true);
    if ($info_raw === '' || $info_raw === false || $info_raw === null) {
        $info = rs_contact_default_info($locale);
        update_post_meta($post_id, RS_CONTACT_INFO_KEY, wp_json_encode($info, JSON_UNESCAPED_UNICODE));
        update_post_meta($post_id, RS_CONTACT_BLOCKS_KEY, wp_json_encode(rs_contact_info_to_blocks($info), JSON_UNESCAPED_UNICODE));
    }
}

function rs_contact_ensure_locale_posts(): void {
    foreach (['en', 'pt'] as $locale) {
        $post_id = rs_contact_get_post_id_by_locale($locale);
        if ($post_id <= 0) {
            $post_id = (int) wp_insert_post([
                'post_title'  => $locale === 'pt' ? 'Contato (PT)' : 'Contact (EN)',
                'post_status' => 'publish',
                'post_type'   => 'contact',
                'post_name'   => $locale,
                'post_author' => 1,
            ], true);
        }

        if ($post_id > 0 && !is_wp_error($post_id)) {
            rs_contact_seed_post($post_id, $locale);
        }
    }

    update_option('rs_contact_posts_ensured_v2', 1);
}

add_action('init', function () {
    foreach ([RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY, RS_CONTACT_HEADLINE_KEY, RS_CONTACT_BLOCKS_KEY, RS_CONTACT_INFO_KEY] as $key) {
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

/**
 * @param array<string, string> $info
 */
function rs_contact_render_text_field(string $name, string $label, string $value, string $placeholder = ''): void {
    ?>
    <p style="margin:0 0 12px;">
        <label style="display:block;font-weight:500;margin-bottom:4px;"><?php echo esc_html($label); ?></label>
        <input
            type="text"
            class="widefat"
            name="<?php echo esc_attr($name); ?>"
            value="<?php echo esc_attr($value); ?>"
            placeholder="<?php echo esc_attr($placeholder); ?>"
        />
    </p>
    <?php
}

function rs_contact_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_contact_save', 'rs_contact_nonce');

    $locale = $post->post_name === 'pt' ? 'pt' : 'en';
    rs_contact_seed_post((int) $post->ID, $locale);

    $headline = (string) get_post_meta($post->ID, RS_CONTACT_HEADLINE_KEY, true);
    $info = rs_contact_get_info((int) $post->ID);

    echo '<p style="margin-top:0;color:#646970;">Um post por idioma (slug <code>en</code> / <code>pt</code>). Edite telefone, e-mails e textos abaixo — o site monta a grade Contato / Endereço / Vagas / Estágio. <em>(Plugin Tradução v1.2.13)</em></p>';
    if (function_exists('rs_sync_media_notice_html')) {
        echo rs_sync_media_notice_html((int) $post->ID);
    }

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Headline</strong></legend>';
    rs_render_rich_text_field(RS_CONTACT_HEADLINE_KEY, RS_CONTACT_HEADLINE_KEY, $headline, 'inline');
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Use o botão <strong>B</strong> para destacar palavras.</p>';
    echo '</fieldset>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Contato</strong></legend>';
    rs_contact_render_text_field('rs_contact_info[contact_title]', 'Título', $info['contact_title'], 'CONTACT');
    rs_contact_render_text_field('rs_contact_info[contact_location]', 'Cidade / localização', $info['contact_location'], 'São Paulo – Brazil');
    rs_contact_render_text_field('rs_contact_info[contact_phone]', 'Telefone (exibição)', $info['contact_phone'], '+55 11 (9) 4540-8448');
    rs_contact_render_text_field('rs_contact_info[contact_phone_tel]', 'Telefone para o link (só números)', $info['contact_phone_tel'], '5511945408448');
    rs_contact_render_text_field('rs_contact_info[contact_email]', 'E-mail', $info['contact_email'], 'contact@regularswitch.com');
    echo '</fieldset>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Endereço</strong></legend>';
    rs_contact_render_text_field('rs_contact_info[address_title]', 'Título', $info['address_title'], 'ADDRESS');
    rs_contact_render_text_field('rs_contact_info[address_location]', 'Cidade / localização', $info['address_location'], 'São Paulo – Brazil');
    rs_contact_render_text_field('rs_contact_info[address_street]', 'Rua / endereço', $info['address_street'], 'Rua da Consolação, 65');
    echo '</fieldset>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Vagas</strong></legend>';
    rs_contact_render_text_field('rs_contact_info[jobs_title]', 'Título', $info['jobs_title'], 'JOBS');
    rs_contact_render_text_field('rs_contact_info[jobs_text]', 'Texto', $info['jobs_text'], 'We are not hiring at the moment.');
    rs_contact_render_text_field('rs_contact_info[jobs_email]', 'E-mail', $info['jobs_email'], 'join-us@regularswitch.com');
    echo '</fieldset>';

    echo '<fieldset style="margin:16px 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Estágio</strong></legend>';
    rs_contact_render_text_field('rs_contact_info[internship_title]', 'Título', $info['internship_title'], 'INTERNSHIP');
    rs_contact_render_text_field('rs_contact_info[internship_text]', 'Texto', $info['internship_text'], 'Send us an e-mail to apply.');
    rs_contact_render_text_field('rs_contact_info[internship_email]', 'E-mail', $info['internship_email'], 'join-us@regularswitch.com');
    echo '</fieldset>';
}

/**
 * @return array<string, string>
 */
function rs_contact_parse_info_from_request(string $locale): array {
    $raw = [];
    if (isset($_POST['rs_contact_info']) && is_array($_POST['rs_contact_info'])) {
        $raw = wp_unslash($_POST['rs_contact_info']);
    }

    $defaults = rs_contact_default_info($locale);
    $out = [];
    foreach ($defaults as $key => $_default) {
        $out[$key] = isset($raw[$key]) ? sanitize_text_field((string) $raw[$key]) : '';
    }

    return $out;
}

add_action('save_post_contact', function (int $post_id) {
    if (!isset($_POST['rs_contact_nonce']) || !wp_verify_nonce($_POST['rs_contact_nonce'], 'rs_contact_save')) {
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

    $locale = get_post_field('post_name', $post_id) === 'pt' ? 'pt' : 'en';

    $headline = isset($_POST[RS_CONTACT_HEADLINE_KEY])
        ? wp_kses_post(wp_unslash($_POST[RS_CONTACT_HEADLINE_KEY]))
        : '';
    update_post_meta($post_id, RS_CONTACT_HEADLINE_KEY, $headline);

    if (function_exists('rs_section_save_hero_media')) {
        rs_section_save_hero_media($post_id, RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY);
    }

    $info = rs_contact_parse_info_from_request($locale);
    update_post_meta($post_id, RS_CONTACT_INFO_KEY, wp_json_encode($info, JSON_UNESCAPED_UNICODE));

    $blocks = rs_contact_info_to_blocks(rs_contact_normalize_info($info, $locale));
    update_post_meta($post_id, RS_CONTACT_BLOCKS_KEY, wp_json_encode($blocks, JSON_UNESCAPED_UNICODE));
});

function rs_copy_contact_fields(int $from_id, int $to_id): void {
    update_post_meta($to_id, RS_CONTACT_HEADLINE_KEY, get_post_meta($from_id, RS_CONTACT_HEADLINE_KEY, true));
    update_post_meta($to_id, RS_CONTACT_BLOCKS_KEY, get_post_meta($from_id, RS_CONTACT_BLOCKS_KEY, true));
    update_post_meta($to_id, RS_CONTACT_INFO_KEY, get_post_meta($from_id, RS_CONTACT_INFO_KEY, true));
    if (function_exists('rs_section_copy_hero_media')) {
        rs_section_copy_hero_media($from_id, $to_id, RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY);
    }
}

if (function_exists('rs_enqueue_admin_media_picker')) {
    rs_enqueue_admin_media_picker(['contact']);
}
