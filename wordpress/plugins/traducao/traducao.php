<?php
/**
 * Plugin Name: Tradução
 * Description: Facilitar A tradução
 * Version: 1.0.0
 * Author: Undefined
 * Author URI: undefined.com
 */

include __DIR__ . "/custon_post.php";
include __DIR__ . "/intro-fields.php";
include __DIR__ . "/footer-fields.php";
include __DIR__ . "/project-fields.php";
include __DIR__ . "/rest-translate.php";
include __DIR__ . "/proxy.php";
include __DIR__ . "/mult-language.php";

function rs_add_language_column(array $cols): array {
    $cols['language'] = 'Language';
    return $cols;
}

function rs_render_language_column(string $column_name, int $post_ID): void {
    if ($column_name !== 'language') {
        return;
    }

    $file = __DIR__ . '/config.json';
    $data = [
        'type'      => 'TEXT',
        'languages' => [],
    ];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
    }

    foreach ($data['languages'] as $slug) {
        $slug = strtoupper($slug);
        $url = get_site_url() . "/wp-json/translate/proxy?id={$post_ID}&lang={$slug}";
        echo "<a href=\"#\" data-href=\"{$url}\" onclick=\"rs_link_translate(event, this)\" title=\"Translate {$slug}\">{$slug}</a> ";
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

foreach (['post', 'footer', 'intro', 'brand', 'project'] as $post_type) {
    add_filter("manage_{$post_type}_posts_columns", 'rs_add_language_column');
    add_action("manage_{$post_type}_posts_custom_column", 'rs_render_language_column', 10, 2);
}

add_action('admin_footer', 'rs_link_translate_script');

add_action( 'admin_menu', function() {
    add_menu_page(
        'Tradutor',
        'Tradutor',
        'manage_options',
        'traducao/admin.php',
        '',
        'dashicons-translation',
        6
    );
} );

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

