<?php
/**
 * Chrome global (sino + toast) e módulo reutilizável de abas/acordeão no admin.
 */

if (defined('RS_METABOX_UI_LOADED')) {
    return;
}
define('RS_METABOX_UI_LOADED', true);

/**
 * CPTs com meta boxes customizados do plugin Tradução.
 *
 * @return array<int, string>
 */
function rs_metabox_ui_post_types(): array {
    return ['project', 'capabilities', 'education', 'contact', 'about'];
}

function rs_metabox_ui_asset_version(): string {
    return '1.2.33';
}

add_action('admin_enqueue_scripts', function (string $hook): void {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->post_type, rs_metabox_ui_post_types(), true)) {
        return;
    }

    $base = plugin_dir_url(__FILE__);
    $ver = rs_metabox_ui_asset_version();

    wp_enqueue_style('rs-admin-chrome', $base . 'assets/rs-admin-chrome.css', [], $ver);
    wp_enqueue_script('rs-admin-chrome', $base . 'assets/rs-admin-chrome.js', ['jquery'], $ver, true);

    wp_enqueue_style('rs-metabox-ui', $base . 'assets/rs-metabox-ui.css', [], $ver);
    wp_enqueue_script('rs-metabox-ui', $base . 'assets/rs-metabox-ui.js', ['jquery', 'jquery-ui-sortable'], $ver, true);
}, 5);
