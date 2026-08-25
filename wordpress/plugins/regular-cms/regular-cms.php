<?php
/**
 * Plugin Name: Regular CMS
 * Plugin URI:  https://regularswitch.com
 * Description: CPTs, meta boxes, i18n EN/PT e REST (api-etc/v2/all-posts) do site Regular Switch.
 * Version:     1.4.8
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      Regular
 * Author URI:  https://regularswitch.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: regular-cms
 * Domain Path: /languages
 */

require_once __DIR__ . '/plugin-meta.php';
require_once __DIR__ . '/load.php';

add_action('admin_notices', function (): void {
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $legacy = [];

    if (is_plugin_active('traducao/traducao.php')) {
        $legacy[] = '<code>traducao</code>';
    }
    if (is_plugin_active('api-etc/api-etc.php')) {
        $legacy[] = '<code>api-etc</code>';
    }

    if (!$legacy) {
        return;
    }

    echo '<div class="notice notice-warning"><p><strong>Regular CMS:</strong> desative e remova os plugins legados '
        . implode(' e ', $legacy)
        . ' — tudo foi integrado em <code>regular-cms</code>.</p></div>';
});

function rs_add_language_column(array $cols): array {
    $cols['language'] = 'Language';
    return $cols;
}

function rs_project_locale_badge(int $post_id): string {
    if ((int) get_post_meta($post_id, 'EN', true) > 0) {
        return 'PT';
    }
    if ((int) get_post_meta($post_id, 'PT', true) > 0) {
        return 'EN';
    }
    return 'EN';
}

function rs_render_language_column(string $column_name, int $post_ID): void {
    if ($column_name !== 'language') {
        return;
    }

    $badge = rs_project_locale_badge($post_ID);
    $opposite = $badge === 'PT' ? 'EN' : 'PT';
    echo '<strong style="margin-right:8px;">' . esc_html($badge) . '</strong>';

    // Só o idioma oposto: clicar em EN no EN (ou PT no PT) criava posts duplicados.
    // "_wpnonce" (action wp_rest) é exigido pelo core da REST API pra reconhecer o
    // login via cookie; "rs_nonce" é nosso, específico pra este post/ação.
    $wp_rest_nonce = wp_create_nonce('wp_rest');
    $rs_nonce = wp_create_nonce('rs_translate_proxy_' . $post_ID);
    $url = get_site_url() . "/wp-json/translate/proxy?id={$post_ID}&lang={$opposite}&_wpnonce={$wp_rest_nonce}&rs_nonce={$rs_nonce}";
    $label = $badge === 'EN' ? 'Abrir/criar PT' : 'Abrir EN';
    echo '<a href="#" data-href="' . esc_url($url) . '" onclick="rs_link_translate(event, this)" title="' . esc_attr($label) . '">' . esc_html($opposite) . '</a> ';
}

function rs_link_translate_script(): void {
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;
    ?>
    <script>
        function rs_link_translate(event, el) {
            event.preventDefault();

            // Trava contra duplo clique / duplo fetch: sem isso dois cliques rápidos
            // podiam disparar duas criações de post antes da primeira terminar.
            if (el.dataset.rsBusy === '1') {
                return;
            }
            el.dataset.rsBusy = '1';
            const originalText = el.textContent;
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.5';
            el.textContent = '...';

            const restore = () => {
                el.dataset.rsBusy = '0';
                el.style.pointerEvents = '';
                el.style.opacity = '';
                el.textContent = originalText;
            };

            const url = el.getAttribute('data-href');
            fetch(url)
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.go) {
                        const msg = (data && data.message) ? data.message : 'Falha ao abrir tradução.';
                        window.alert(msg);
                        restore();
                        return;
                    }
                    window.location.href = data.go;
                })
                .catch(() => {
                    window.alert('Falha ao abrir tradução.');
                    restore();
                });
        }
    </script>
    <?php
}

foreach (['post', 'footer', 'intro', 'brand', 'capabilities', 'about', 'education', 'contact', 'legal', 'projects-page', 'site-ui'] as $post_type) {
    add_filter("manage_{$post_type}_posts_columns", 'rs_add_language_column');
    add_action("manage_{$post_type}_posts_custom_column", 'rs_render_language_column', 10, 2);
}

add_action('admin_footer', 'rs_link_translate_script');

/**
 * Separadores no menu admin: antes e depois dos CPTs do site.
 */
function rs_admin_menu_separator(int $position, string $slug): void {
    global $menu;

    $menu[$position] = ['', 'read', 'separator' . $slug, '', 'wp-menu-separator rs-admin-menu-separator'];
}

add_action('admin_menu', function () {
    rs_admin_menu_separator(26, 'rs-before-site-content');
    rs_admin_menu_separator(37, 'rs-after-site-content');
}, PHP_INT_MAX);

add_action('admin_head', function () {
    ?>
    <style>
        #adminmenu .wp-menu-separator.rs-admin-menu-separator {
            display: block;
            height: 1px;
            margin: 8px 0;
            padding: 0;
            cursor: default;
            pointer-events: none;
        }

        #adminmenu .wp-menu-separator.rs-admin-menu-separator .separator {
            display: block;
            height: 1px;
            margin: 0 8px;
            background: rgba(255, 255, 255, 0.12);
        }

        #adminmenu.folded .wp-menu-separator.rs-admin-menu-separator .separator {
            margin: 0 4px;
        }
    </style>
    <?php
});

add_filter( 'the_content', function( $content ) {
    if( _getLang() ) {
        $post_id_translate = get_post_meta(get_the_ID(), _getLang(), true);
        $the_post = get_post( $post_id_translate );
        if($the_post) {
            $content = $the_post->post_content;
        }
    }
    return $content;
}, 1 );

add_filter( 'the_title', function( $content ) {
    if( _getLang() && !is_admin() ) {
        $post_id_translate = get_post_meta(get_the_ID(), _getLang(), true);
        $the_post = get_post( $post_id_translate );
        if($the_post) {
            $content = $the_post->post_title;
        }
    }
    return $content;
}, 1 );

