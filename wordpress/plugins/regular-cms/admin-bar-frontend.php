<?php
/**
 * Admin bar: "Visitar site" / nome do site apontam para o front na Vercel.
 *
 * Override: define('RS_FRONTEND_URL', 'https://...');
 */

if (defined('RS_ADMIN_BAR_FRONTEND_LOADED')) {
    return;
}
define('RS_ADMIN_BAR_FRONTEND_LOADED', true);

/**
 * URL pública do site headless (Next/Vercel).
 */
function rs_frontend_site_url(): string {
    if (defined('RS_FRONTEND_URL') && is_string(RS_FRONTEND_URL) && RS_FRONTEND_URL !== '') {
        return untrailingslashit(esc_url_raw(RS_FRONTEND_URL));
    }

    $host = strtolower((string) (wp_parse_url(home_url(), PHP_URL_HOST) ?: ''));

    if (
        str_contains($host, 'staging-wp')
        || str_contains($host, '.local')
        || $host === 'localhost'
        || $host === '127.0.0.1'
    ) {
        $url = 'https://dev.regularswitch.com.br';
    } else {
        $url = 'https://regularswitch.com';
    }

    /**
     * @param string $url
     * @param string $host Host do WordPress.
     */
    return untrailingslashit((string) apply_filters('rs_frontend_site_url', $url, $host));
}

add_action('admin_bar_menu', function (WP_Admin_Bar $bar): void {
    $front = rs_frontend_site_url();
    if ($front === '') {
        return;
    }

    $href = trailingslashit($front);

    foreach (['site-name', 'view-site'] as $id) {
        $node = $bar->get_node($id);
        if (!$node) {
            continue;
        }

        $node->href = $href;
        if ($id === 'view-site') {
            $node->meta = array_merge((array) ($node->meta ?? []), [
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
            ]);
        }
        $bar->add_node((array) $node);
    }
}, 999);
