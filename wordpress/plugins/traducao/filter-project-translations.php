<?php
/**
 * Evita que versões PT de projetos apareçam como posts duplicados na REST e no admin.
 * O site lista só o post “canônico” (EN); ?translate=PT troca o conteúdo via meta.
 */

if (defined('RS_FILTER_PROJECT_TRANSLATIONS_LOADED')) {
    return;
}
define('RS_FILTER_PROJECT_TRANSLATIONS_LOADED', true);

/**
 * Post de tradução (ex.: PT) aponta de volta para o EN via meta `EN`.
 */
function rs_project_is_translation_twin(int $post_id): bool {
    return (int) get_post_meta($post_id, 'EN', true) > 0;
}

/**
 * Exclui gêmeos de tradução das listagens REST de /project.
 * Pedidos por slug específico continuam encontrando o post pedido.
 */
add_filter('rest_project_query', function (array $args, WP_REST_Request $request) {
    $slug = $request->get_param('slug');
    if (!empty($slug)) {
        return $args;
    }

    $exclude = get_posts([
        'post_type'      => 'project',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'EN',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    $exclude = array_values(array_filter(array_map('intval', $exclude)));
    if (!$exclude) {
        return $args;
    }

    $existing = isset($args['post__not_in']) ? (array) $args['post__not_in'] : [];
    $args['post__not_in'] = array_values(array_unique(array_merge($existing, $exclude)));

    return $args;
}, 20, 2);

/**
 * No admin, esconde versões PT por padrão (evita “vários projetos iguais”).
 * Use o filtro “Traduções PT” para vê-las.
 */
add_action('pre_get_posts', function (WP_Query $query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'edit-project') {
        return;
    }

    if (!empty($_GET['rs_show_translations'])) {
        return;
    }

    $meta_query = (array) $query->get('meta_query');
    $meta_query[] = [
        'key'     => 'EN',
        'compare' => 'NOT EXISTS',
    ];
    $query->set('meta_query', $meta_query);
});

add_filter('views_edit-project', function (array $views) {
    $url = add_query_arg('rs_show_translations', '1', admin_url('edit.php?post_type=project'));
    $active = !empty($_GET['rs_show_translations']) ? ' class="current"' : '';
    $views['rs_translations'] = '<a href="' . esc_url($url) . '"' . $active . '>Traduções PT</a>';
    return $views;
});
