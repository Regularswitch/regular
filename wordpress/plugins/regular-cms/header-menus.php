<?php
/**
 * Menus nativos do WordPress para o header (EN / PT).
 * Editável em Aparência → Menus.
 */

if (defined('RS_HEADER_MENUS_LOADED')) {
    return;
}
define('RS_HEADER_MENUS_LOADED', true);

const RS_HEADER_MENU_LOCATION_EN = 'header-en';
const RS_HEADER_MENU_LOCATION_PT = 'header-pt';
const RS_HEADER_MENU_LEGACY_SLOTS = 5;

add_action('after_setup_theme', function () {
    register_nav_menus([
        RS_HEADER_MENU_LOCATION_EN => 'Header — English',
        RS_HEADER_MENU_LOCATION_PT => 'Header — Português',
    ]);
});

/**
 * @return array<int, array{label: string, href: string}>
 */
function rs_header_nav_default_items(string $locale): array {
    if ($locale === 'pt') {
        return [
            ['label' => 'Projetos', 'href' => '/projects'],
            ['label' => 'Capacidades', 'href' => '/capabilities'],
            ['label' => 'Educação', 'href' => '/education'],
            ['label' => 'Sobre Nós', 'href' => '/about-us'],
            ['label' => 'Contato', 'href' => '/contact'],
        ];
    }

    return [
        ['label' => 'Projects', 'href' => '/projects'],
        ['label' => 'Capabilities', 'href' => '/capabilities'],
        ['label' => 'Education', 'href' => '/education'],
        ['label' => 'About', 'href' => '/about-us'],
        ['label' => 'Contact', 'href' => '/contact'],
    ];
}

function rs_header_nav_normalize_href(string $url): string {
    $url = trim($url);
    if ($url === '' || $url === '#') {
        return '';
    }

    if (preg_match('#^(mailto:|tel:)#i', $url)) {
        return $url;
    }

    if (preg_match('#^https?://#i', $url)) {
        $home = home_url();
        if (str_starts_with($url, $home)) {
            $path = wp_parse_url($url, PHP_URL_PATH) ?? '/';
            $url = $path ?: '/';
        } else {
            return $url;
        }
    }

    if (!str_starts_with($url, '/')) {
        $url = '/' . $url;
    }

    if (str_starts_with($url, '/PT/')) {
        $url = substr($url, 3) ?: '/';
    } else    if ($url === '/PT') {
        $url = '/';
    }

    // Alias legado: rotas antigas usavam /work
    if ($url === '/work') {
        $url = '/projects';
    }

    return $url;
}

/**
 * @return array<int, array{label: string, href: string}>
 */
function rs_header_nav_legacy_items(string $locale): array {
    $en_id = function_exists('rs_site_ui_get_post_id_by_locale') ? rs_site_ui_get_post_id_by_locale('en') : 0;
    $pt_id = function_exists('rs_site_ui_get_post_id_by_locale') ? rs_site_ui_get_post_id_by_locale('pt') : 0;
    $suffix = $locale === 'pt' ? '_pt' : '_en';
    $label_post_id = $locale === 'pt' && $pt_id > 0 ? $pt_id : $en_id;
    $items = [];

    for ($i = 1; $i <= RS_HEADER_MENU_LEGACY_SLOTS; $i++) {
        $href = $en_id > 0
            ? trim((string) get_post_meta($en_id, "rs_site_ui_nav_{$i}_href", true))
            : '';
        $label = $label_post_id > 0
            ? trim((string) get_post_meta($label_post_id, "rs_site_ui_nav_{$i}_label{$suffix}", true))
            : '';

        if ($href === '') {
            continue;
        }

        if ($label === '') {
            $label = $href;
        }

        $items[] = [
            'label' => $label,
            'href'  => rs_header_nav_normalize_href($href),
        ];
    }

    return $items;
}

/**
 * @return array<int, array{label: string, href: string}>
 */
function rs_header_nav_from_wp_menu(string $locale): array {
    $location = $locale === 'pt' ? RS_HEADER_MENU_LOCATION_PT : RS_HEADER_MENU_LOCATION_EN;
    $locations = get_nav_menu_locations();

    if (empty($locations[$location])) {
        return [];
    }

    $items = wp_get_nav_menu_items((int) $locations[$location]);
    if (!$items || is_wp_error($items)) {
        return [];
    }

    usort($items, static function ($a, $b) {
        return (int) $a->menu_order <=> (int) $b->menu_order;
    });

    $nav = [];
    foreach ($items as $item) {
        if ((int) $item->menu_item_parent !== 0) {
            continue;
        }

        $href = rs_header_nav_normalize_href((string) ($item->url ?? ''));
        if ($href === '') {
            continue;
        }

        $label = trim((string) ($item->title ?? ''));
        if ($label === '') {
            $label = $href;
        }

        $nav[] = [
            'label' => $label,
            'href'  => $href,
        ];
    }

    return $nav;
}

/**
 * @return array<int, array{label: string, href: string}>
 */
function rs_header_nav_get_items(string $locale): array {
    $from_menu = rs_header_nav_from_wp_menu($locale);
    if ($from_menu) {
        return $from_menu;
    }

    $legacy = rs_header_nav_legacy_items($locale);
    if ($legacy) {
        return $legacy;
    }

    return rs_header_nav_default_items($locale);
}

function rs_header_nav_find_or_create_menu(string $name, string $slug): int {
    $existing = wp_get_nav_menu_object($slug);
    if ($existing && !is_wp_error($existing)) {
        return (int) $existing->term_id;
    }

    foreach (wp_get_nav_menus() as $menu) {
        if ($menu->slug === $slug || $menu->name === $name) {
            return (int) $menu->term_id;
        }
    }

    $id = wp_create_nav_menu($name);

    return is_wp_error($id) ? 0 : (int) $id;
}

/**
 * @param array<int, array{label: string, href: string}> $items
 */
function rs_header_nav_seed_menu(int $menu_id, array $items): void {
    $position = 1;

    foreach ($items as $item) {
        $href = rs_header_nav_normalize_href($item['href'] ?? '');
        if ($href === '') {
            continue;
        }

        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'     => $item['label'] !== '' ? $item['label'] : $href,
            'menu-item-url'       => $href,
            'menu-item-status'    => 'publish',
            'menu-item-position'  => $position,
            'menu-item-type'      => 'custom',
        ]);

        $position++;
    }
}

function rs_header_nav_get_assigned_menu_id(string $location_key): int {
    $locations = get_nav_menu_locations();

    return !empty($locations[$location_key]) ? (int) $locations[$location_key] : 0;
}

function rs_header_nav_ensure_menus(): void {
    $locations = get_nav_menu_locations();
    $updated = is_array($locations) ? $locations : [];
    $changed = false;

    foreach (['en' => ['Header EN', 'header-en', RS_HEADER_MENU_LOCATION_EN], 'pt' => ['Header PT', 'header-pt', RS_HEADER_MENU_LOCATION_PT]] as $locale => $config) {
        [$menu_name, $menu_slug, $location_key] = $config;

        $menu_id = rs_header_nav_find_or_create_menu($menu_name, $menu_slug);
        if ($menu_id <= 0) {
            continue;
        }

        $existing_items = wp_get_nav_menu_items($menu_id);
        if (!$existing_items) {
            $seed = rs_header_nav_legacy_items($locale);
            if (!$seed) {
                $seed = rs_header_nav_default_items($locale);
            }
            rs_header_nav_seed_menu($menu_id, $seed);
            $changed = true;
        }

        if (($updated[$location_key] ?? 0) !== $menu_id) {
            $updated[$location_key] = $menu_id;
            $changed = true;
        }
    }

    if ($changed) {
        set_theme_mod('nav_menu_locations', $updated);
    }
}

add_action('init', 'rs_header_nav_ensure_menus', 30);

add_action('rest_api_init', function () {
    register_rest_route('rs/v1', '/header-nav', [
        'methods'             => 'GET',
        'callback'            => function () {
            return [
                'en' => rs_header_nav_get_items('en'),
                'pt' => rs_header_nav_get_items('pt'),
            ];
        },
        'permission_callback' => '__return_true',
    ]);
});

add_action('admin_notices', function () {
    $screen = get_current_screen();
    if (!$screen) {
        return;
    }

    $en_id = rs_header_nav_get_assigned_menu_id(RS_HEADER_MENU_LOCATION_EN);
    $pt_id = rs_header_nav_get_assigned_menu_id(RS_HEADER_MENU_LOCATION_PT);

    if ($screen->id === 'nav-menus') {
        echo '<div class="notice notice-info"><p><strong>Menus do site (Next.js)</strong> — edite diretamente: ';
        if ($en_id > 0) {
            echo '<a href="' . esc_url(admin_url('nav-menus.php?action=edit&menu=' . $en_id)) . '">Header EN</a>';
        } else {
            echo 'Header EN (será criado ao recarregar)';
        }
        echo ' · ';
        if ($pt_id > 0) {
            echo '<a href="' . esc_url(admin_url('nav-menus.php?action=edit&menu=' . $pt_id)) . '">Header PT</a>';
        } else {
            echo 'Header PT (será criado ao recarregar)';
        }
        echo '<br /><span style="color:#646970;">Use <em>Links personalizados</em> com paths como <code>/projects</code>, <code>/capabilities</code> — sem prefixo <code>/PT</code>.</span>';
        echo '</p></div>';
        return;
    }

    if ($screen->base !== 'post' || $screen->post_type !== 'site-ui') {
        return;
    }

    $url = admin_url('nav-menus.php');
    if ($en_id > 0) {
        $url = admin_url('nav-menus.php?action=edit&menu=' . $en_id);
    }

    echo '<div class="notice notice-info"><p>';
    echo 'O <strong>menu do header</strong> é editado em <a href="' . esc_url($url) . '">Aparência → Menus → Header EN</a> ';
    echo '(e <a href="' . esc_url($pt_id > 0 ? admin_url('nav-menus.php?action=edit&menu=' . $pt_id) : admin_url('nav-menus.php')) . '">Header PT</a>).';
    echo ' Use links sem <code>/PT</code> — o site adiciona automaticamente.';
    echo '</p></div>';
});
