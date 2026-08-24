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
 * Contagens por status no admin, alinhadas ao filtro da lista.
 *
 * @return array{all:int,mine:int,publish:int,draft:int,pending:int,private:int,future:int,trash:int}
 */
function rs_project_admin_status_counts(bool $translations_only = false): array {
    global $wpdb;

    $empty = [
        'all'     => 0,
        'mine'    => 0,
        'publish' => 0,
        'draft'   => 0,
        'pending' => 0,
        'private' => 0,
        'future'  => 0,
        'trash'   => 0,
    ];

    if ($translations_only) {
        $join = "INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'EN'";
        $where_extra = '';
    } else {
        $join = "LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'EN'";
        $where_extra = 'AND pm.meta_id IS NULL';
    }

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- join/where_extra are fixed strings.
    $rows = $wpdb->get_results(
        "SELECT p.post_status, COUNT(*) AS num
         FROM {$wpdb->posts} p
         {$join}
         WHERE p.post_type = 'project'
         {$where_extra}
         GROUP BY p.post_status"
    );

    if (!is_array($rows)) {
        return $empty;
    }

    $by_status = $empty;
    foreach ($rows as $row) {
        $status = (string) $row->post_status;
        $num = (int) $row->num;
        if (isset($by_status[$status])) {
            $by_status[$status] = $num;
        }
        if (!in_array($status, ['trash', 'auto-draft', 'inherit'], true)) {
            $by_status['all'] += $num;
        }
    }

    $user_id = get_current_user_id();
    if ($user_id > 0) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $mine = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->posts} p
                 {$join}
                 WHERE p.post_type = 'project'
                 AND p.post_author = %d
                 AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit')
                 {$where_extra}",
                $user_id
            )
        );
        $by_status['mine'] = $mine;
    }

    return $by_status;
}

/**
 * Substitui o número dentro de <span class="count">(N)</span>.
 */
function rs_project_replace_view_count(string $html, int $count): string {
    $label = number_format_i18n($count);
    if (str_contains($html, 'class="count"')) {
        return (string) preg_replace(
            '/(<span class="count">\s*\()(.*?)(\)\s*<\/span>)/',
            '${1}' . $label . '${3}',
            $html,
            1
        );
    }

    return $html . ' <span class="count">(' . $label . ')</span>';
}

/**
 * Exclui gêmeos de tradução das listagens REST de /project.
 * Pedidos por slug específico continuam encontrando o post pedido.
 */
add_filter('rest_project_query', function (array $args, WP_REST_Request $request) {
    if (get_option('rs_project_i18n_migrated_v1')) {
        return $args;
    }

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

    if (get_option('rs_project_i18n_migrated_v1')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'edit-project') {
        return;
    }

    if (!empty($_GET['rs_show_translations'])) {
        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = [
            'key'     => 'EN',
            'compare' => 'EXISTS',
        ];
        $query->set('meta_query', $meta_query);
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
    $show_translations = !empty($_GET['rs_show_translations']);
    $counts = rs_project_admin_status_counts($show_translations);

    $map = [
        'all'     => 'all',
        'mine'    => 'mine',
        'publish' => 'publish',
        'draft'   => 'draft',
        'pending' => 'pending',
        'private' => 'private',
        'future'  => 'future',
        'trash'   => 'trash',
    ];

    foreach ($map as $view_key => $count_key) {
        if (!isset($views[$view_key])) {
            continue;
        }

        $html = rs_project_replace_view_count($views[$view_key], (int) ($counts[$count_key] ?? 0));

        // Mantém o filtro PT ao trocar de status (Todos / Lixos / etc.).
        if ($show_translations && preg_match('/href=([\'"])(.*?)\1/', $html, $match)) {
            $href = html_entity_decode($match[2], ENT_QUOTES);
            $href = add_query_arg('rs_show_translations', '1', $href);
            $html = (string) preg_replace(
                '/href=([\'"]).*?\1/',
                'href="' . esc_url($href) . '"',
                $html,
                1
            );
        }

        $views[$view_key] = $html;
    }

    $url = add_query_arg('rs_show_translations', '1', admin_url('edit.php?post_type=project'));
    $active = $show_translations ? ' class="current"' : '';

    // Total de gêmeos PT (lista + lixeira) para o atalho da view.
    $pt_counts = $show_translations ? $counts : rs_project_admin_status_counts(true);
    $pt_label = number_format_i18n((int) $pt_counts['all'] + (int) $pt_counts['trash']);
    $views['rs_translations'] = '<a href="' . esc_url($url) . '"' . $active . '>Traduções PT <span class="count">(' . $pt_label . ')</span></a>';

    return $views;
});
