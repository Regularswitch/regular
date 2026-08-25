<?php
/**
 * Campos editáveis do CPT projects-page (listagem /projects).
 */

if (defined('RS_PROJECTS_PAGE_FIELDS_LOADED')) {
    return;
}
define('RS_PROJECTS_PAGE_FIELDS_LOADED', true);

const RS_PROJECTS_PAGE_TITLE_KEY = 'rs_projects_page_title';
const RS_PROJECTS_PAGE_HEADLINE_KEY = 'rs_projects_page_headline';
const RS_PROJECTS_PAGE_EMPTY_KEY = 'rs_projects_page_empty_message';

function rs_projects_page_meta_to_payload(int $post_id): array {
    return [
        'title'         => trim((string) get_post_meta($post_id, RS_PROJECTS_PAGE_TITLE_KEY, true)),
        'headline'      => trim((string) get_post_meta($post_id, RS_PROJECTS_PAGE_HEADLINE_KEY, true)),
        'emptyMessage'  => trim((string) get_post_meta($post_id, RS_PROJECTS_PAGE_EMPTY_KEY, true)),
    ];
}

function rs_projects_page_get_post_id_by_locale(string $locale): int {
    $posts = get_posts([
        'post_type'      => 'projects-page',
        'post_status'    => 'publish',
        'name'           => $locale,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

function rs_projects_page_ensure_locale_posts(): void {
    if (get_option('rs_projects_page_posts_ensured_v1')) {
        return;
    }

    foreach (['en', 'pt'] as $locale) {
        if (rs_projects_page_get_post_id_by_locale($locale) > 0) {
            continue;
        }

        wp_insert_post([
            'post_title'  => $locale === 'pt' ? 'Projetos (PT)' : 'Projects (EN)',
            'post_status' => 'publish',
            'post_type'   => 'projects-page',
            'post_name'   => $locale,
            'post_author' => 1,
        ], true);
    }

    update_option('rs_projects_page_posts_ensured_v1', 1);
}

add_action('init', function () {
    foreach ([RS_PROJECTS_PAGE_TITLE_KEY, RS_PROJECTS_PAGE_HEADLINE_KEY, RS_PROJECTS_PAGE_EMPTY_KEY] as $key) {
        register_post_meta('projects-page', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);

add_action('init', 'rs_projects_page_ensure_locale_posts', 25);

add_action('rest_api_init', function () {
    register_rest_field('projects-page', 'projects_page_data', [
        'get_callback' => function (array $post) {
            return rs_projects_page_meta_to_payload((int) $post['id']);
        },
        'schema' => [
            'description' => 'Dados estruturados da página de projetos',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_projects-page', function () {
    add_meta_box(
        'rs_projects_page_fields',
        'Conteúdo da página de projetos',
        'rs_projects_page_render_meta_box',
        'projects-page',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'projects-page', 'normal');
}, 10);

function rs_projects_page_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_projects_page_save', 'rs_projects_page_nonce');

    $title = (string) get_post_meta($post->ID, RS_PROJECTS_PAGE_TITLE_KEY, true);
    $headline = (string) get_post_meta($post->ID, RS_PROJECTS_PAGE_HEADLINE_KEY, true);
    $empty = (string) get_post_meta($post->ID, RS_PROJECTS_PAGE_EMPTY_KEY, true);

    echo '<p style="margin-top:0;color:#646970;">Um post por idioma (slug <code>en</code> / <code>pt</code>). Campos vazios usam o fallback do Next.js.</p>';

    echo '<p style="margin:0 0 12px;">';
    echo '<label for="' . esc_attr(RS_PROJECTS_PAGE_TITLE_KEY) . '" style="display:block;font-weight:500;margin-bottom:4px;">Título da seção</label>';
    echo '<input type="text" style="width:100%;" id="' . esc_attr(RS_PROJECTS_PAGE_TITLE_KEY) . '" name="' . esc_attr(RS_PROJECTS_PAGE_TITLE_KEY) . '" value="' . esc_attr($title) . '" placeholder="Selected projects / Projetos selecionados" />';
    echo '</p>';

    echo '<fieldset style="margin:0 0 16px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Headline</strong></legend>';
    rs_render_rich_text_field(RS_PROJECTS_PAGE_HEADLINE_KEY, RS_PROJECTS_PAGE_HEADLINE_KEY, $headline, 'inline');
    echo '<p style="margin:8px 0 0;color:#646970;font-size:12px;">Use o botão <strong>B</strong> para destacar palavras.</p>';
    echo '</fieldset>';

    echo '<p style="margin:0;">';
    echo '<label for="' . esc_attr(RS_PROJECTS_PAGE_EMPTY_KEY) . '" style="display:block;font-weight:500;margin-bottom:4px;">Mensagem quando não há projetos</label>';
    echo '<input type="text" style="width:100%;" id="' . esc_attr(RS_PROJECTS_PAGE_EMPTY_KEY) . '" name="' . esc_attr(RS_PROJECTS_PAGE_EMPTY_KEY) . '" value="' . esc_attr($empty) . '" />';
    echo '</p>';
}

add_action('save_post_projects-page', function (int $post_id) {
    if (!isset($_POST['rs_projects_page_nonce']) || !wp_verify_nonce($_POST['rs_projects_page_nonce'], 'rs_projects_page_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST[RS_PROJECTS_PAGE_TITLE_KEY])) {
        update_post_meta($post_id, RS_PROJECTS_PAGE_TITLE_KEY, sanitize_text_field(wp_unslash($_POST[RS_PROJECTS_PAGE_TITLE_KEY])));
    }

    if (isset($_POST[RS_PROJECTS_PAGE_HEADLINE_KEY])) {
        update_post_meta($post_id, RS_PROJECTS_PAGE_HEADLINE_KEY, wp_kses_post(wp_unslash($_POST[RS_PROJECTS_PAGE_HEADLINE_KEY])));
    }

    if (isset($_POST[RS_PROJECTS_PAGE_EMPTY_KEY])) {
        update_post_meta($post_id, RS_PROJECTS_PAGE_EMPTY_KEY, sanitize_text_field(wp_unslash($_POST[RS_PROJECTS_PAGE_EMPTY_KEY])));
    }
});

function rs_copy_projects_page_fields(int $from_id, int $to_id): void {
    update_post_meta($to_id, RS_PROJECTS_PAGE_TITLE_KEY, get_post_meta($from_id, RS_PROJECTS_PAGE_TITLE_KEY, true));
    update_post_meta($to_id, RS_PROJECTS_PAGE_HEADLINE_KEY, get_post_meta($from_id, RS_PROJECTS_PAGE_HEADLINE_KEY, true));
    update_post_meta($to_id, RS_PROJECTS_PAGE_EMPTY_KEY, get_post_meta($from_id, RS_PROJECTS_PAGE_EMPTY_KEY, true));
}
