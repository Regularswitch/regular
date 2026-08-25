<?php
/**
 * Em ambientes headless (staging / local), o front do WP redireciona para o login.
 * Preserva REST API, uploads, admin e wp-login.
 *
 * Forçar: define('RS_REDIRECT_FRONT_TO_LOGIN', true|false);
 */

if (defined('RS_REDIRECT_FRONT_TO_LOGIN_LOADED')) {
    return;
}
define('RS_REDIRECT_FRONT_TO_LOGIN_LOADED', true);

function rs_should_redirect_front_to_login(): bool {
    if (defined('RS_REDIRECT_FRONT_TO_LOGIN')) {
        return (bool) RS_REDIRECT_FRONT_TO_LOGIN;
    }

    $host = strtolower((string) (wp_parse_url(home_url(), PHP_URL_HOST) ?: ''));

    // Staging e Local (headless). Produção (wp.regularswitch.com) permanece pública.
    return str_contains($host, 'staging-wp')
        || str_contains($host, '.local')
        || $host === 'localhost'
        || $host === '127.0.0.1';
}

function rs_request_path(): string {
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (wp_parse_url($uri, PHP_URL_PATH) ?: '/');
    return $path === '' ? '/' : $path;
}

function rs_is_front_redirect_excluded_path(string $path): bool {
    $excluded_prefixes = [
        '/wp-admin',
        '/wp-login.php',
        '/wp-cron.php',
        '/wp-json',
        '/wp-content',
        '/wp-includes',
        '/xmlrpc.php',
    ];

    foreach ($excluded_prefixes as $prefix) {
        if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/')) {
            return true;
        }
        if (str_ends_with($prefix, '.php') && str_starts_with($path, $prefix)) {
            return true;
        }
    }

    return false;
}

add_action('template_redirect', function (): void {
    if (!rs_should_redirect_front_to_login()) {
        return;
    }

    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    $path = rs_request_path();
    if (rs_is_front_redirect_excluded_path($path)) {
        return;
    }

    // Já autenticado no admin → manda para o painel.
    if (is_user_logged_in() && current_user_can('read')) {
        wp_safe_redirect(admin_url(), 302);
        exit;
    }

    wp_safe_redirect(wp_login_url(admin_url()), 302);
    exit;
}, 0);
