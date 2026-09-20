<?php
/**
 * Campos SEO / arquivo das tags (taxonomia project-category).
 * H1, intro e meta por termo — EN/PT via gêmeo (meta PT) + translate=PT.
 */

if (defined('RS_PROJECT_CATEGORY_FIELDS_LOADED')) {
    return;
}
define('RS_PROJECT_CATEGORY_FIELDS_LOADED', true);

const RS_CAT_SEO_TITLE_KEY = 'rs_cat_seo_title';
const RS_CAT_SEO_DESC_KEY  = 'rs_cat_seo_description';
const RS_CAT_H1_KEY        = 'rs_cat_h1';
const RS_CAT_INTRO_KEY     = 'rs_cat_intro';

/**
 * @return array{seoTitle: string, seoDescription: string, h1: string, intro: string}
 */
function rs_category_seo_defaults(): array {
    return [
        'seoTitle'       => '',
        'seoDescription' => '',
        'h1'             => '',
        'intro'          => '',
    ];
}

function rs_category_resolve_term_id_for_locale(int $term_id, string $locale): int {
    $locale = strtolower($locale) === 'pt' ? 'pt' : 'en';
    if ($locale === 'en') {
        return $term_id;
    }
    $translated = (int) get_term_meta($term_id, 'PT', true);
    return $translated > 0 ? $translated : $term_id;
}

/**
 * @return array{seoTitle: string, seoDescription: string, h1: string, intro: string}
 */
function rs_category_seo_get(int $term_id): array {
    if ($term_id <= 0) {
        return rs_category_seo_defaults();
    }

    return [
        'seoTitle'       => sanitize_text_field((string) get_term_meta($term_id, RS_CAT_SEO_TITLE_KEY, true)),
        'seoDescription' => sanitize_textarea_field((string) get_term_meta($term_id, RS_CAT_SEO_DESC_KEY, true)),
        'h1'             => sanitize_text_field((string) get_term_meta($term_id, RS_CAT_H1_KEY, true)),
        'intro'          => wp_kses_post((string) get_term_meta($term_id, RS_CAT_INTRO_KEY, true)),
    ];
}

/**
 * Payload REST (já no locale pedido).
 *
 * @return array{seoTitle: string, seoDescription: string, h1: string, intro: string}
 */
function rs_category_seo_payload(int $term_id, string $locale = 'en'): array {
    $resolved = rs_category_resolve_term_id_for_locale($term_id, $locale);
    return rs_category_seo_get($resolved);
}

add_action('rest_api_init', function () {
    register_rest_field('project-category', 'category_seo', [
        'get_callback' => function ($term, $attr, $request = null) {
            $term_id = (int) ($term['id'] ?? 0);
            $locale = 'en';
            if ($request instanceof WP_REST_Request) {
                $raw = $request->get_param('translate');
                if (is_string($raw) && strtoupper($raw) === 'PT') {
                    $locale = 'pt';
                }
            }
            return rs_category_seo_payload($term_id, $locale);
        },
        'schema' => [
            'description' => 'SEO e texto do arquivo da tag',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('project-category_add_form_fields', function () {
    ?>
    <div class="form-field rs-ds-term-field">
        <label class="rs-ds-label" for="rs_cat_h1">H1 do arquivo</label>
        <input type="text" class="rs-ds-input" name="rs_cat_h1" id="rs_cat_h1" value="" />
        <p class="rs-ds-help">Ex.: Projetos de identidade visual. Se vazio, usa o nome da tag.</p>
    </div>
    <div class="form-field rs-ds-term-field">
        <label class="rs-ds-label" for="rs_cat_intro">Introdução (parágrafo)</label>
        <textarea class="rs-ds-textarea" name="rs_cat_intro" id="rs_cat_intro" rows="4"></textarea>
        <p class="rs-ds-help">Texto curto com o termo + definição (AEO/GEO).</p>
    </div>
    <div class="form-field rs-ds-term-field">
        <label class="rs-ds-label" for="rs_cat_seo_title">SEO title</label>
        <input type="text" class="rs-ds-input" name="rs_cat_seo_title" id="rs_cat_seo_title" value="" />
    </div>
    <div class="form-field rs-ds-term-field">
        <label class="rs-ds-label" for="rs_cat_seo_description">SEO meta description</label>
        <textarea class="rs-ds-textarea" name="rs_cat_seo_description" id="rs_cat_seo_description" rows="3"></textarea>
    </div>
    <?php
});

add_action('project-category_edit_form_fields', function ($term) {
    $seo = rs_category_seo_get((int) $term->term_id);
    ?>
    <tr class="form-field rs-ds-term-field">
        <th scope="row"><label class="rs-ds-label" for="rs_cat_h1">H1 do arquivo</label></th>
        <td>
            <input type="text" name="rs_cat_h1" id="rs_cat_h1" value="<?php echo esc_attr($seo['h1']); ?>" class="rs-ds-input" />
            <p class="rs-ds-help">Ex.: Projetos de identidade visual. Se vazio, usa o nome da tag.</p>
        </td>
    </tr>
    <tr class="form-field rs-ds-term-field">
        <th scope="row"><label class="rs-ds-label" for="rs_cat_intro">Introdução (parágrafo)</label></th>
        <td>
            <textarea name="rs_cat_intro" id="rs_cat_intro" rows="4" class="rs-ds-textarea"><?php echo esc_textarea($seo['intro']); ?></textarea>
            <p class="rs-ds-help">Texto curto com o termo + definição. Preencha no termo EN e no gêmeo PT.</p>
        </td>
    </tr>
    <tr class="form-field rs-ds-term-field">
        <th scope="row"><label class="rs-ds-label" for="rs_cat_seo_title">SEO title</label></th>
        <td>
            <input type="text" name="rs_cat_seo_title" id="rs_cat_seo_title" value="<?php echo esc_attr($seo['seoTitle']); ?>" class="rs-ds-input" />
        </td>
    </tr>
    <tr class="form-field rs-ds-term-field">
        <th scope="row"><label class="rs-ds-label" for="rs_cat_seo_description">SEO meta description</label></th>
        <td>
            <textarea name="rs_cat_seo_description" id="rs_cat_seo_description" rows="3" class="rs-ds-textarea"><?php echo esc_textarea($seo['seoDescription']); ?></textarea>
        </td>
    </tr>
    <?php
});

/**
 * @param int $term_id
 */
function rs_category_seo_save_term($term_id): void {
    $term_id = (int) $term_id;
    if ($term_id <= 0 || !current_user_can('edit_term', $term_id)) {
        return;
    }

    if (isset($_POST['rs_cat_h1'])) {
        update_term_meta($term_id, RS_CAT_H1_KEY, sanitize_text_field(wp_unslash((string) $_POST['rs_cat_h1'])));
    }
    if (isset($_POST['rs_cat_intro'])) {
        update_term_meta($term_id, RS_CAT_INTRO_KEY, wp_kses_post(wp_unslash((string) $_POST['rs_cat_intro'])));
    }
    if (isset($_POST['rs_cat_seo_title'])) {
        update_term_meta($term_id, RS_CAT_SEO_TITLE_KEY, sanitize_text_field(wp_unslash((string) $_POST['rs_cat_seo_title'])));
    }
    if (isset($_POST['rs_cat_seo_description'])) {
        update_term_meta($term_id, RS_CAT_SEO_DESC_KEY, sanitize_textarea_field(wp_unslash((string) $_POST['rs_cat_seo_description'])));
    }
}

add_action('created_project-category', 'rs_category_seo_save_term');
add_action('edited_project-category', 'rs_category_seo_save_term');
