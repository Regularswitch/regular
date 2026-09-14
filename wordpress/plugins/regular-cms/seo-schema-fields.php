<?php
/**
 * Schema.org ProfessionalService (global) — editável no CMS.
 * Armazenado no post site-ui; REST GET /rs/v1/seo-schema.
 */

if (defined('RS_SEO_SCHEMA_FIELDS_LOADED')) {
    return;
}
define('RS_SEO_SCHEMA_FIELDS_LOADED', true);

const RS_SEO_SCHEMA_META_KEY = 'rs_seo_org_schema';

function rs_seo_schema_defaults(): array {
    return [
        'name'         => 'RegularSwitch',
        'descriptionEn'=> 'Franco-Brazilian design studio based in São Paulo, founded in 2013. Brand strategy, visual identity, branding and generative design for brands and cultural institutions.',
        'descriptionPt'=> 'Estúdio de design franco-brasileiro em São Paulo, fundado em 2013. Estratégia de marca, identidade visual, branding e design generativo para marcas e instituições.',
        'foundingDate' => '2013',
        'url'          => 'https://regularswitch.com.br',
        'telephone'    => '+5511945408448',
        'email'        => 'contact@regularswitch.com',
        'locality'     => 'São Paulo',
        'region'       => 'SP',
        'country'      => 'BR',
        'areaServed'   => "BR\nFR",
        'knowsAbout'   => "Identidade visual\nBranding\nRebranding\nDesign generativo\nDesign editorial\nExpografia\nExperiências digitais\nPlataforma de marca",
        'sameAs'       => "https://www.instagram.com/regular.switch/\nhttps://www.behance.net/regular-switch",
    ];
}

/**
 * @return list<string>
 */
function rs_seo_schema_lines(string $raw): array {
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function rs_seo_schema_normalize(array $raw): array {
    $defaults = rs_seo_schema_defaults();
    $data = [];
    foreach ($defaults as $key => $default) {
        $data[$key] = sanitize_textarea_field((string) ($raw[$key] ?? $default));
        if (in_array($key, ['name', 'foundingDate', 'url', 'telephone', 'email', 'locality', 'region', 'country'], true)) {
            $data[$key] = sanitize_text_field($data[$key]);
        }
    }
    return $data;
}

function rs_seo_schema_get_post_id(): int {
    if (function_exists('rs_section_i18n_canonical_id')) {
        return (int) rs_section_i18n_canonical_id('site-ui');
    }
    return 0;
}

function rs_seo_schema_get(): array {
    $post_id = rs_seo_schema_get_post_id();
    if ($post_id <= 0) {
        return rs_seo_schema_normalize([]);
    }
    $raw = get_post_meta($post_id, RS_SEO_SCHEMA_META_KEY, true);
    return rs_seo_schema_normalize(is_array($raw) ? $raw : []);
}

/**
 * Payload JSON-LD ProfessionalService (locale en|pt).
 *
 * @return array<string, mixed>
 */
function rs_seo_schema_jsonld(string $locale = 'en'): array {
    $locale = strtolower($locale) === 'pt' ? 'pt' : 'en';
    $data = rs_seo_schema_get();
    $description = $locale === 'pt' ? $data['descriptionPt'] : $data['descriptionEn'];

    $payload = [
        '@context'     => 'https://schema.org',
        '@type'        => 'ProfessionalService',
        'name'         => $data['name'],
        'description'  => $description,
        'foundingDate' => $data['foundingDate'],
        'url'          => $data['url'],
        'telephone'    => $data['telephone'],
        'email'        => $data['email'],
        'address'      => [
            '@type'           => 'PostalAddress',
            'addressLocality' => $data['locality'],
            'addressRegion'   => $data['region'],
            'addressCountry'  => $data['country'],
        ],
        'areaServed'   => rs_seo_schema_lines($data['areaServed']),
        'knowsAbout'   => rs_seo_schema_lines($data['knowsAbout']),
        'sameAs'       => rs_seo_schema_lines($data['sameAs']),
    ];

    return $payload;
}

/**
 * Payload REST (campos crus + jsonLdEn/jsonLdPt).
 */
function rs_seo_schema_rest_payload(): array {
    $data = rs_seo_schema_get();
    return [
        'fields'   => $data,
        'jsonLdEn' => rs_seo_schema_jsonld('en'),
        'jsonLdPt' => rs_seo_schema_jsonld('pt'),
    ];
}

add_action('rest_api_init', function () {
    register_rest_route('rs/v1', '/seo-schema', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => static function () {
            return rs_seo_schema_rest_payload();
        },
    ]);
});

add_action('add_meta_boxes_site-ui', function () {
    add_meta_box(
        'rs_seo_schema_fields',
        'SEO · Schema da organização (JSON-LD)',
        'rs_seo_schema_render_meta_box',
        'site-ui',
        'normal',
        'default'
    );
}, 25);

function rs_seo_schema_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_seo_schema_save', 'rs_seo_schema_nonce');
    $data = rs_seo_schema_get();

    echo '<p style="margin-top:0;color:#646970;">Dados estruturados <code>ProfessionalService</code> em todas as páginas. Usado por Google e IAs. ';
    if (function_exists('rs_plugin_version_markup')) {
        echo rs_plugin_version_markup();
    }
    echo '</p>';

    $fields = [
        'name'          => ['Nome', 'text'],
        'descriptionEn' => ['Descrição (EN)', 'textarea'],
        'descriptionPt' => ['Descrição (PT)', 'textarea'],
        'foundingDate'  => ['Ano de fundação', 'text'],
        'url'           => ['URL do site', 'text'],
        'telephone'     => ['Telefone (E.164)', 'text'],
        'email'         => ['E-mail', 'text'],
        'locality'      => ['Cidade', 'text'],
        'region'        => ['Estado/região', 'text'],
        'country'       => ['País (código)', 'text'],
        'areaServed'    => ['Áreas atendidas (um por linha, ex: BR)', 'textarea'],
        'knowsAbout'    => ['knowsAbout — serviços/termos (um por linha)', 'textarea'],
        'sameAs'        => ['sameAs — redes sociais (URLs, uma por linha)', 'textarea'],
    ];

    foreach ($fields as $key => [$label, $type]) {
        $id = 'rs_seo_schema_' . $key;
        $name = 'rs_seo_schema[' . $key . ']';
        $value = (string) ($data[$key] ?? '');
        echo '<p style="margin:0 0 12px;">';
        echo '<label for="' . esc_attr($id) . '" style="display:block;font-weight:600;margin-bottom:4px;">' . esc_html($label) . '</label>';
        if ($type === 'textarea') {
            echo '<textarea class="large-text" rows="3" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input type="text" class="large-text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
        }
        echo '</p>';
    }
}

add_action('save_post_site-ui', function (int $post_id) {
    if (!isset($_POST['rs_seo_schema_nonce']) || !wp_verify_nonce($_POST['rs_seo_schema_nonce'], 'rs_seo_schema_save')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;

    $raw = isset($_POST['rs_seo_schema']) && is_array($_POST['rs_seo_schema'])
        ? wp_unslash($_POST['rs_seo_schema'])
        : [];

    update_post_meta($post_id, RS_SEO_SCHEMA_META_KEY, rs_seo_schema_normalize($raw));
}, 30);
