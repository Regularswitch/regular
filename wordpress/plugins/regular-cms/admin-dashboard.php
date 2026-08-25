<?php
/**
 * Painel do WordPress — widgets úteis do Regular CMS (sem widgets padrão).
 */

if (defined('RS_ADMIN_DASHBOARD_LOADED')) {
    return;
}
define('RS_ADMIN_DASHBOARD_LOADED', true);

/**
 * @return list<array{type: string, label: string, icon: string, single: bool}>
 */
function rs_dashboard_content_links(): array {
    return [
        ['type' => 'intro', 'label' => 'Intro', 'icon' => 'dashicons-text-page', 'single' => true],
        ['type' => 'home-visual', 'label' => 'Visual da home', 'icon' => 'dashicons-art', 'single' => true],
        ['type' => 'site-ui', 'label' => 'Interface do site', 'icon' => 'dashicons-admin-generic', 'single' => true],
        ['type' => 'about', 'label' => 'Sobre Nós', 'icon' => 'dashicons-groups', 'single' => true],
        ['type' => 'projects-page', 'label' => 'Página de projetos', 'icon' => 'dashicons-portfolio', 'single' => true],
        ['type' => 'project', 'label' => 'Projetos', 'icon' => 'dashicons-images-alt2', 'single' => false],
        ['type' => 'capabilities', 'label' => 'Capacidades', 'icon' => 'dashicons-hammer', 'single' => true],
        ['type' => 'education', 'label' => 'Educação', 'icon' => 'dashicons-welcome-learn-more', 'single' => true],
        ['type' => 'brand', 'label' => 'Marcas', 'icon' => 'dashicons-awards', 'single' => false],
        ['type' => 'contact', 'label' => 'Contato', 'icon' => 'dashicons-email', 'single' => true],
        ['type' => 'footer', 'label' => 'Footer', 'icon' => 'dashicons-table-row-after', 'single' => true],
        ['type' => 'legal', 'label' => 'Privacidade & Cookies', 'icon' => 'dashicons-privacy', 'single' => true],
    ];
}

function rs_dashboard_edit_url_for_type(string $post_type, bool $single): string {
    if (!post_type_exists($post_type)) {
        return admin_url();
    }

    if (!$single) {
        return admin_url('edit.php?post_type=' . rawurlencode($post_type));
    }

    if (function_exists('rs_section_i18n_canonical_id') && function_exists('rs_section_i18n_is_migrated_type') && rs_section_i18n_is_migrated_type($post_type)) {
        $id = (int) rs_section_i18n_canonical_id($post_type);
        if ($id > 0) {
            return get_edit_post_link($id, 'raw') ?: admin_url('edit.php?post_type=' . rawurlencode($post_type));
        }
    }

    $posts = get_posts([
        'post_type'      => $post_type,
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ]);

    if (!empty($posts[0])) {
        return get_edit_post_link((int) $posts[0], 'raw') ?: admin_url('edit.php?post_type=' . rawurlencode($post_type));
    }

    return admin_url('post-new.php?post_type=' . rawurlencode($post_type));
}

/**
 * @return array{projects: int, brands: int, projects_no_media: list<array{id: int, title: string, edit: string}>, featured_home: int}
 */
function rs_dashboard_stats(): array {
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $projects = post_type_exists('project') ? (int) wp_count_posts('project')->publish : 0;
    $brands = post_type_exists('brand') ? (int) wp_count_posts('brand')->publish : 0;

    $no_media = [];
    $featured = 0;

    if (post_type_exists('project')) {
        $ids = get_posts([
            'post_type'      => 'project',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        foreach ($ids as $id) {
            $id = (int) $id;
            $canonical = function_exists('rs_project_resolve_canonical_id')
                ? rs_project_resolve_canonical_id($id)
                : $id;

            // Evita listar o mesmo canônico duas vezes.
            if ($canonical !== $id) {
                continue;
            }

            $hero = 0;
            $gallery = '';
            if (function_exists('rs_project_i18n_get')) {
                $i18n = rs_project_i18n_get($canonical);
                $hero = (int) ($i18n['shared']['hero_id'] ?? 0);
                $gallery = trim((string) ($i18n['shared']['gallery_ids'] ?? ''));
                if (!empty($i18n['shared']['featured_home'])) {
                    $featured++;
                }
            }
            if ($hero <= 0 && function_exists('rs_project_get_hero_id')) {
                $hero = rs_project_get_hero_id($canonical);
            }
            if ($gallery === '' && defined('RS_PROJECT_GALLERY_KEY')) {
                $gallery = trim((string) get_post_meta($canonical, RS_PROJECT_GALLERY_KEY, true));
            }

            if ($hero <= 0 && $gallery === '') {
                $title = get_the_title($canonical) ?: ('#' . $canonical);
                $edit = get_edit_post_link($canonical, 'raw') ?: '';
                $no_media[] = [
                    'id'    => $canonical,
                    'title' => $title,
                    'edit'  => $edit,
                ];
            }
        }
    }

    $cached = [
        'projects'          => $projects,
        'brands'            => $brands,
        'projects_no_media' => $no_media,
        'featured_home'     => $featured,
    ];

    return $cached;
}

function rs_dashboard_environment_label(): string {
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    $host = is_string($host) ? strtolower($host) : '';

    if ($host === '' || str_contains($host, 'local') || $host === 'localhost' || $host === '127.0.0.1') {
        return 'Local';
    }
    if (str_contains($host, 'staging')) {
        return 'Staging';
    }

    return 'Produção';
}

function rs_dashboard_remove_default_widgets(): void {
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
    remove_meta_box('dashboard_secondary', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}
add_action('wp_dashboard_setup', 'rs_dashboard_remove_default_widgets', 20);

function rs_dashboard_register_widgets(): void {
    if (!current_user_can('edit_posts')) {
        return;
    }

    wp_add_dashboard_widget(
        'rs_dashboard_overview',
        'Regular CMS',
        'rs_dashboard_render_overview'
    );

    wp_add_dashboard_widget(
        'rs_dashboard_shortcuts',
        'Conteúdo do site',
        'rs_dashboard_render_shortcuts'
    );

    wp_add_dashboard_widget(
        'rs_dashboard_media',
        'Atenção — mídia de projetos',
        'rs_dashboard_render_media_alerts'
    );
}
add_action('wp_dashboard_setup', 'rs_dashboard_register_widgets', 30);

/** Painel de boas-vindas padrão do WP — desnecessário no CMS headless. */
remove_action('welcome_panel', 'wp_welcome_panel');

function rs_dashboard_render_overview(): void {
    $stats = rs_dashboard_stats();
    $env = rs_dashboard_environment_label();
    $version = function_exists('rs_plugin_version_label') ? rs_plugin_version_label() : 'Regular CMS';
    $wp_url = home_url('/');
    $missing = count($stats['projects_no_media']);

    echo '<div class="rs-dash">';
    echo '<p class="rs-dash-lead">CMS headless do site Regular Switch — edite seções e projetos aqui; o front consome a REST API.</p>';

    echo '<div class="rs-dash-stats">';
    echo '<div class="rs-dash-stat"><strong>' . (int) $stats['projects'] . '</strong><span>Projetos</span></div>';
    echo '<div class="rs-dash-stat"><strong>' . (int) $stats['brands'] . '</strong><span>Marcas</span></div>';
    echo '<div class="rs-dash-stat"><strong>' . (int) $stats['featured_home'] . '</strong><span>Destaque home</span></div>';
    echo '<div class="rs-dash-stat' . ($missing > 0 ? ' is-warn' : '') . '"><strong>' . (int) $missing . '</strong><span>Sem mídia</span></div>';
    echo '</div>';

    echo '<ul class="rs-dash-meta">';
    echo '<li><strong>Ambiente:</strong> ' . esc_html($env) . '</li>';
    echo '<li><strong>Plugin:</strong> ' . esc_html($version) . '</li>';
    echo '<li><strong>WP:</strong> <a href="' . esc_url($wp_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($wp_url) . '</a></li>';
    echo '<li><strong>Idioma:</strong> seções e projetos em <em>post único</em> (EN · PT nas abas). Marcas sem tradução.</li>';
    echo '</ul>';
    echo '</div>';
}

function rs_dashboard_render_shortcuts(): void {
    echo '<div class="rs-dash">';
    echo '<div class="rs-dash-grid">';

    foreach (rs_dashboard_content_links() as $item) {
        if (!post_type_exists($item['type'])) {
            continue;
        }
        $url = rs_dashboard_edit_url_for_type($item['type'], $item['single']);
        echo '<a class="rs-dash-tile" href="' . esc_url($url) . '">';
        echo '<span class="dashicons ' . esc_attr($item['icon']) . '" aria-hidden="true"></span>';
        echo '<span class="rs-dash-tile-label">' . esc_html($item['label']) . '</span>';
        echo '</a>';
    }

    echo '</div>';
    echo '<p class="rs-dash-hint">Seções de página abrem o post canônico. Projetos e Marcas abrem a lista.</p>';
    echo '</div>';
}

function rs_dashboard_render_media_alerts(): void {
    $stats = rs_dashboard_stats();
    $list = $stats['projects_no_media'];

    echo '<div class="rs-dash">';

    if (!$list) {
        echo '<p class="rs-dash-ok">Todos os projetos publicados têm hero ou galeria.</p>';
        echo '</div>';
        return;
    }

    echo '<p class="rs-dash-warn">Estes projetos estão sem hero e sem galeria — o front mostra placeholders vazios:</p>';
    echo '<ul class="rs-dash-list">';
    $shown = 0;
    foreach ($list as $row) {
        if ($shown >= 25) {
            break;
        }
        $shown++;
        $title = $row['title'];
        if ($row['edit'] !== '') {
            echo '<li><a href="' . esc_url($row['edit']) . '">' . esc_html($title) . '</a></li>';
        } else {
            echo '<li>' . esc_html($title) . '</li>';
        }
    }
    echo '</ul>';

    $extra = count($list) - $shown;
    if ($extra > 0) {
        echo '<p class="rs-dash-hint">+ ' . (int) $extra . ' outros. Abra <a href="' . esc_url(admin_url('edit.php?post_type=project')) . '">Projetos</a> para revisar.</p>';
    }

    echo '</div>';
}

add_action('admin_enqueue_scripts', function (string $hook): void {
    if ($hook !== 'index.php') {
        return;
    }

    $base = plugin_dir_url(__FILE__);
    $ver = function_exists('rs_plugin_version') ? rs_plugin_version() : '1.0';
    wp_enqueue_style('rs-admin-dashboard', $base . 'assets/rs-admin-dashboard.css', [], $ver);

    $bg_url = rs_dashboard_current_bg_url();
    if ($bg_url === '') {
        return;
    }

    $css = sprintf(
        'body.index-php #wpwrap{background-color:#ebebeb;background-image:url(%1$s);background-repeat:no-repeat;background-position:right bottom;background-size:cover;background-attachment:fixed}'
        . 'body.index-php #wpcontent{background:transparent}'
        . 'body.index-php #wpbody-content{background:transparent}'
        . 'body.index-php #dashboard-widgets .postbox{background:rgba(255,255,255,0.92);backdrop-filter:blur(2px)}'
        . 'body.index-php .wrap>h1{text-shadow:0 1px 0 rgba(255,255,255,0.8)}',
        esc_url($bg_url)
    );
    wp_add_inline_style('rs-admin-dashboard', $css);
});

/**
 * Arquivos de fundo do painel (rodízio a cada login).
 *
 * @return list<string>
 */
function rs_dashboard_bg_files(): array {
    return ['bg-01.jpg', 'bg-02.jpg', 'bg-03.jpg'];
}

function rs_dashboard_bg_user_meta_key(): string {
    return 'rs_dashboard_bg_index';
}

/**
 * URL do fundo atual do usuário (ou string vazia).
 */
function rs_dashboard_current_bg_url(): string {
    $files = rs_dashboard_bg_files();
    if ($files === []) {
        return '';
    }

    $user_id = get_current_user_id();
    $index = 0;
    if ($user_id > 0) {
        $stored = get_user_meta($user_id, rs_dashboard_bg_user_meta_key(), true);
        if (is_numeric($stored)) {
            $index = (int) $stored;
        }
    }

    $count = count($files);
    $index = (($index % $count) + $count) % $count;
    $file = $files[$index];
    $path = plugin_dir_path(__FILE__) . 'assets/dashboard-bg/' . $file;
    if (!is_readable($path)) {
        return '';
    }

    return plugin_dir_url(__FILE__) . 'assets/dashboard-bg/' . rawurlencode($file);
}

/**
 * A cada login, avança para o próximo fundo do painel.
 */
function rs_dashboard_rotate_bg_on_login(string $user_login, $user): void {
    unset($user_login);
    $user_id = 0;
    if ($user instanceof WP_User) {
        $user_id = (int) $user->ID;
    } elseif (is_numeric($user)) {
        $user_id = (int) $user;
    }
    if ($user_id <= 0) {
        return;
    }

    $files = rs_dashboard_bg_files();
    $count = count($files);
    if ($count < 1) {
        return;
    }

    $current = get_user_meta($user_id, rs_dashboard_bg_user_meta_key(), true);
    $index = is_numeric($current) ? (int) $current : -1;
    $next = ($index + 1) % $count;
    update_user_meta($user_id, rs_dashboard_bg_user_meta_key(), $next);
}
add_action('wp_login', 'rs_dashboard_rotate_bg_on_login', 10, 2);

/**
 * Título amigável na home do painel.
 */
add_filter('admin_title', function (string $admin_title, string $title): string {
    global $pagenow;
    if ($pagenow === 'index.php') {
        return 'Regular CMS — Painel';
    }
    return $admin_title;
}, 10, 2);

add_action('admin_head-index.php', function (): void {
    echo '<style>#dashboard-widgets .postbox .handle-actions .handle-order-higher,#dashboard-widgets .postbox .handle-actions .handle-order-lower{display:none!important}</style>';
});
