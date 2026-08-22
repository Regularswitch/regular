<?php
/**
 * Plugin Name: Tradução
 * Description: Facilitar A tradução
 * Version: 1.2.30
 * Author: Undefined
 * Author URI: undefined.com
 */

include __DIR__ . "/custon_post.php";
include __DIR__ . "/intro-fields.php";
include __DIR__ . "/rich-text-fields.php";
include __DIR__ . "/media-fields.php";
include __DIR__ . "/footer-fields.php";
include __DIR__ . "/capabilities-fields.php";
include __DIR__ . "/page-heroes-fields.php";
include __DIR__ . "/section-hero-fields.php";
include __DIR__ . "/about-fields.php";
include __DIR__ . "/education-fields.php";
include __DIR__ . "/contact-fields.php";
include __DIR__ . "/legal-fields.php";
include __DIR__ . "/projects-page-fields.php";
include __DIR__ . "/site-ui-fields.php";
include __DIR__ . "/blob-visual-fields.php";
include __DIR__ . "/header-menus.php";
include __DIR__ . "/project-fields.php";
include __DIR__ . "/hide-themerain-meta.php";
include __DIR__ . "/filter-project-translations.php";
include __DIR__ . "/sync-media-en-to-pt.php";
include __DIR__ . "/slug-language.php";
include __DIR__ . "/rest-translate.php";
include __DIR__ . "/proxy.php";
include __DIR__ . "/mult-language.php";
include __DIR__ . "/redirect-front-to-login.php";
include __DIR__ . "/admin-bar-frontend.php";

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
    echo '<strong style="margin-right:8px;">' . esc_html($badge) . '</strong>';

    foreach (['en', 'pt'] as $slug) {
        $slug = strtoupper($slug);
        $url = get_site_url() . "/wp-json/translate/proxy?id={$post_ID}&lang={$slug}";
        echo "<a href=\"#\" data-href=\"" . esc_url($url) . "\" onclick=\"rs_link_translate(event, this)\" title=\"Abrir {$slug}\">{$slug}</a> ";
    }

    // Reimporta acordeão/galeria do EN na PT (não sobrescreve ao só abrir PT).
    if (get_post_type($post_ID) === 'project' && $badge === 'EN' && (int) get_post_meta($post_ID, 'PT', true) > 0) {
        $sync_url = add_query_arg(
            ['id' => $post_ID, 'lang' => 'PT', 'sync' => '1'],
            get_site_url() . '/wp-json/translate/proxy'
        );
        echo '<a href="#" data-href="' . esc_url($sync_url) . '" onclick="rs_link_translate(event, this)" title="Copiar campos do EN para a PT" style="margin-left:4px;color:#b32d2e;">↻ sync PT</a>';
    }
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
            const url = el.getAttribute('data-href');
            fetch(url)
                .then((res) => res.json())
                .then((res) => {
                    window.location.href = res.go;
                });
        }
    </script>
    <?php
}

foreach (['post', 'footer', 'intro', 'brand', 'project', 'capabilities', 'about', 'education', 'contact', 'legal', 'projects-page', 'site-ui'] as $post_type) {
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

