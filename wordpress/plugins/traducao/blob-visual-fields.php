<?php
/**
 * Cores do blob da home — paleta única para EN e PT.
 */

if (defined('RS_BLOB_VISUAL_LOADED')) {
    return;
}
define('RS_BLOB_VISUAL_LOADED', true);

const RS_BLOB_DEFAULT_COLOR1 = '#fe4857';
const RS_BLOB_DEFAULT_COLOR2 = '#4af117';
const RS_BLOB_DEFAULT_PALETTE = '#7B00FF,#D400FF,#FF5FAF,#304FFE,#FFD500,#4af117,#fe4857';

const RS_BLOB_META_COLOR1 = 'rs_blob_color1';
const RS_BLOB_META_COLOR2 = 'rs_blob_color2';
const RS_BLOB_META_PALETTE = 'rs_blob_palette';

function rs_blob_default_palette(): array {
    return array_map('trim', explode(',', RS_BLOB_DEFAULT_PALETTE));
}

function rs_blob_normalize_hex(string $value, string $fallback): string {
    $value = trim($value);

    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
        return $value;
    }

    return $fallback;
}

function rs_blob_parse_palette(string $raw, array $fallback): array {
    $parts = preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
    $colors = [];

    foreach ($parts as $part) {
        $part = trim($part);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $part)) {
            $colors[] = $part;
        }
    }

    return count($colors) >= 2 ? $colors : $fallback;
}

function rs_blob_visual_get_post_id(): int {
    $posts = get_posts([
        'post_type'      => 'home-visual',
        'post_status'    => 'publish',
        'name'           => 'default',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    return !empty($posts[0]) ? (int) $posts[0] : 0;
}

function rs_blob_visual_get_meta(int $post_id): array {
    return [
        RS_BLOB_META_COLOR1  => (string) get_post_meta($post_id, RS_BLOB_META_COLOR1, true),
        RS_BLOB_META_COLOR2  => (string) get_post_meta($post_id, RS_BLOB_META_COLOR2, true),
        RS_BLOB_META_PALETTE => (string) get_post_meta($post_id, RS_BLOB_META_PALETTE, true),
    ];
}

function rs_blob_visual_payload(?int $post_id = null): array {
    $post_id = $post_id ?: rs_blob_visual_get_post_id();
    $defaults_palette = rs_blob_default_palette();

    if ($post_id <= 0) {
        return [
            'color1'  => RS_BLOB_DEFAULT_COLOR1,
            'color2'  => RS_BLOB_DEFAULT_COLOR2,
            'palette' => $defaults_palette,
        ];
    }

    $meta = rs_blob_visual_get_meta($post_id);

    return [
        'color1'  => rs_blob_normalize_hex($meta[RS_BLOB_META_COLOR1], RS_BLOB_DEFAULT_COLOR1),
        'color2'  => rs_blob_normalize_hex($meta[RS_BLOB_META_COLOR2], RS_BLOB_DEFAULT_COLOR2),
        'palette' => rs_blob_parse_palette($meta[RS_BLOB_META_PALETTE], $defaults_palette),
    ];
}

function rs_blob_visual_ensure_post(): void {
    if (get_option('rs_blob_visual_post_ensured_v1')) {
        return;
    }

    if (rs_blob_visual_get_post_id() > 0) {
        update_option('rs_blob_visual_post_ensured_v1', 1);
        return;
    }

    $post_id = (int) wp_insert_post([
        'post_title'  => 'Visual da home',
        'post_status' => 'publish',
        'post_type'   => 'home-visual',
        'post_name'   => 'default',
        'post_author' => 1,
    ], true);

    if (!is_wp_error($post_id) && $post_id > 0) {
        update_post_meta($post_id, RS_BLOB_META_COLOR1, RS_BLOB_DEFAULT_COLOR1);
        update_post_meta($post_id, RS_BLOB_META_COLOR2, RS_BLOB_DEFAULT_COLOR2);
        update_post_meta($post_id, RS_BLOB_META_PALETTE, RS_BLOB_DEFAULT_PALETTE);
    }

    update_option('rs_blob_visual_post_ensured_v1', 1);
}

add_action('init', function () {
    foreach ([RS_BLOB_META_COLOR1, RS_BLOB_META_COLOR2, RS_BLOB_META_PALETTE] as $key) {
        register_post_meta('home-visual', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 20);

add_action('init', 'rs_blob_visual_ensure_post', 25);

add_action('rest_api_init', function () {
    register_rest_route('rs/v1', '/blob-visual', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            return rs_blob_visual_payload();
        },
    ]);
});

add_action('add_meta_boxes_home-visual', function () {
    add_meta_box(
        'rs_blob_visual_fields',
        'Cores do blob',
        'rs_blob_visual_render_meta_box',
        'home-visual',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'home-visual', 'normal');
}, 10);

function rs_blob_visual_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_blob_visual_save', 'rs_blob_visual_nonce');

    $meta = rs_blob_visual_get_meta($post->ID);
    $color1 = rs_blob_normalize_hex($meta[RS_BLOB_META_COLOR1], RS_BLOB_DEFAULT_COLOR1);
    $color2 = rs_blob_normalize_hex($meta[RS_BLOB_META_COLOR2], RS_BLOB_DEFAULT_COLOR2);
    $palette = trim($meta[RS_BLOB_META_PALETTE]) !== ''
        ? $meta[RS_BLOB_META_PALETTE]
        : RS_BLOB_DEFAULT_PALETTE;

    echo '<p style="margin-top:0;color:#646970;">';
    echo 'Paleta única para a home em <strong>inglês e português</strong>. Campos vazios usam o fallback do código Next.js.';
    echo '</p>';

    echo '<p style="margin:0 0 12px;">';
    echo '<label for="' . esc_attr(RS_BLOB_META_COLOR1) . '" style="display:block;font-weight:500;margin-bottom:4px;">Cor principal 1</label>';
    echo '<input type="color" id="' . esc_attr(RS_BLOB_META_COLOR1) . '_picker" value="' . esc_attr($color1) . '" style="vertical-align:middle;margin-right:8px;" />';
    echo '<input type="text" style="width:120px;" id="' . esc_attr(RS_BLOB_META_COLOR1) . '" name="' . esc_attr(RS_BLOB_META_COLOR1) . '" value="' . esc_attr($color1) . '" pattern="#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})" />';
    echo '</p>';

    echo '<p style="margin:0 0 12px;">';
    echo '<label for="' . esc_attr(RS_BLOB_META_COLOR2) . '" style="display:block;font-weight:500;margin-bottom:4px;">Cor principal 2</label>';
    echo '<input type="color" id="' . esc_attr(RS_BLOB_META_COLOR2) . '_picker" value="' . esc_attr($color2) . '" style="vertical-align:middle;margin-right:8px;" />';
    echo '<input type="text" style="width:120px;" id="' . esc_attr(RS_BLOB_META_COLOR2) . '" name="' . esc_attr(RS_BLOB_META_COLOR2) . '" value="' . esc_attr($color2) . '" pattern="#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})" />';
    echo '</p>';

    echo '<p style="margin:0;">';
    echo '<label for="' . esc_attr(RS_BLOB_META_PALETTE) . '" style="display:block;font-weight:500;margin-bottom:4px;">Paleta (uma cor por linha ou separadas por vírgula)</label>';
    echo '<textarea style="width:100%;min-height:120px;font-family:monospace;" id="' . esc_attr(RS_BLOB_META_PALETTE) . '" name="' . esc_attr(RS_BLOB_META_PALETTE) . '">' . esc_textarea($palette) . '</textarea>';
    echo '</p>';

    ?>
    <script>
    (function () {
        function sync(pickerId, inputId) {
            var picker = document.getElementById(pickerId);
            var input = document.getElementById(inputId);
            if (!picker || !input) return;
            picker.addEventListener('input', function () { input.value = picker.value; });
            input.addEventListener('input', function () {
                if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(input.value)) {
                    picker.value = input.value;
                }
            });
        }
        sync('<?php echo esc_js(RS_BLOB_META_COLOR1); ?>_picker', '<?php echo esc_js(RS_BLOB_META_COLOR1); ?>');
        sync('<?php echo esc_js(RS_BLOB_META_COLOR2); ?>_picker', '<?php echo esc_js(RS_BLOB_META_COLOR2); ?>');
    })();
    </script>
    <?php
}

add_action('save_post_home-visual', function (int $post_id) {
    if (!isset($_POST['rs_blob_visual_nonce']) || !wp_verify_nonce($_POST['rs_blob_visual_nonce'], 'rs_blob_visual_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    foreach ([RS_BLOB_META_COLOR1, RS_BLOB_META_COLOR2, RS_BLOB_META_PALETTE] as $key) {
        if (!array_key_exists($key, $_POST)) {
            continue;
        }

        $value = sanitize_text_field(wp_unslash($_POST[$key]));
        update_post_meta($post_id, $key, $value);
    }
});
