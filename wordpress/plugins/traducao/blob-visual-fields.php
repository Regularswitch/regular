<?php
/**
 * Cores do blob da home — paleta única para EN e PT.
 * Admin: swatches visuais (estilo toolbar) em vez de lista de hex.
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
        if (strlen($value) === 4) {
            $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
        }
        return strtolower($value);
    }

    return strtolower($fallback);
}

function rs_blob_parse_palette(string $raw, array $fallback): array {
    $parts = preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
    $colors = [];

    foreach ($parts as $part) {
        $normalized = rs_blob_normalize_hex($part, '');
        if ($normalized !== '') {
            $colors[] = $normalized;
        }
    }

    return count($colors) >= 2 ? array_values(array_unique($colors)) : $fallback;
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

/**
 * Renderiza uma toolbar de swatches (seleção de uma cor).
 */
function rs_blob_render_color_picker_row(string $input_id, string $input_name, string $value, array $palette, string $label): void {
    echo '<div class="rs-blob-field" data-rs-blob-picker data-target="' . esc_attr($input_id) . '">';
    echo '<label class="rs-blob-label">' . esc_html($label) . '</label>';
    echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" />';
    echo '<div class="rs-blob-toolbar" role="listbox" aria-label="' . esc_attr($label) . '">';

    foreach ($palette as $color) {
        $active = strtolower($color) === strtolower($value) ? ' is-active' : '';
        echo '<button type="button" class="rs-blob-swatch' . esc_attr($active) . '" role="option" data-color="' . esc_attr($color) . '" style="--swatch:' . esc_attr($color) . ';" aria-label="' . esc_attr($color) . '" title="' . esc_attr($color) . '">';
        echo '<span class="rs-blob-swatch-dot" aria-hidden="true"></span>';
        echo '</button>';
    }

    echo '<button type="button" class="rs-blob-swatch rs-blob-swatch--add" data-rs-blob-custom title="Cor personalizada" aria-label="Cor personalizada">';
    echo '<span class="rs-blob-swatch-plus" aria-hidden="true">+</span>';
    echo '</button>';
    echo '<input type="color" class="rs-blob-native-color" value="' . esc_attr($value) . '" tabindex="-1" aria-hidden="true" />';
    echo '</div>';
    echo '</div>';
}

function rs_blob_visual_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_blob_visual_save', 'rs_blob_visual_nonce');

    $meta = rs_blob_visual_get_meta($post->ID);
    $color1 = rs_blob_normalize_hex($meta[RS_BLOB_META_COLOR1], RS_BLOB_DEFAULT_COLOR1);
    $color2 = rs_blob_normalize_hex($meta[RS_BLOB_META_COLOR2], RS_BLOB_DEFAULT_COLOR2);
    $palette = rs_blob_parse_palette(
        trim($meta[RS_BLOB_META_PALETTE]) !== '' ? $meta[RS_BLOB_META_PALETTE] : RS_BLOB_DEFAULT_PALETTE,
        rs_blob_default_palette()
    );

    // Garante que color1/color2 apareçam na toolbar mesmo se custom.
    $picker_palette = $palette;
    foreach ([$color1, $color2] as $extra) {
        if (!in_array($extra, array_map('strtolower', $picker_palette), true)
            && !in_array($extra, $picker_palette, true)) {
            $picker_palette[] = $extra;
        }
    }

    echo '<p class="rs-blob-help">';
    echo 'Paleta única para a home em <strong>inglês e português</strong>. ';
    echo 'A barra de progresso e o indicador do menu usam a mesma paleta. ';
    echo '<em>(Plugin Tradução v1.2.16)</em>';
    echo '</p>';

    rs_blob_render_color_picker_row(RS_BLOB_META_COLOR1, RS_BLOB_META_COLOR1, $color1, $picker_palette, 'Cor principal 1');
    rs_blob_render_color_picker_row(RS_BLOB_META_COLOR2, RS_BLOB_META_COLOR2, $color2, $picker_palette, 'Cor principal 2');

    echo '<div class="rs-blob-field" data-rs-blob-palette>';
    echo '<label class="rs-blob-label">Paleta (blob, menu e barra de progresso)</label>';
    echo '<input type="hidden" id="' . esc_attr(RS_BLOB_META_PALETTE) . '" name="' . esc_attr(RS_BLOB_META_PALETTE) . '" value="' . esc_attr(implode(',', $palette)) . '" />';
    echo '<div class="rs-blob-toolbar rs-blob-toolbar--palette" id="rs-blob-palette-list">';

    foreach ($palette as $index => $color) {
        echo '<button type="button" class="rs-blob-swatch" data-palette-index="' . esc_attr((string) $index) . '" data-color="' . esc_attr($color) . '" style="--swatch:' . esc_attr($color) . ';" title="' . esc_attr($color) . ' — clique para editar">';
        echo '<span class="rs-blob-swatch-remove" title="Remover" aria-label="Remover">×</span>';
        echo '</button>';
    }

    echo '<button type="button" class="rs-blob-swatch rs-blob-swatch--add" id="rs-blob-palette-add" title="Adicionar cor" aria-label="Adicionar cor">';
    echo '<span class="rs-blob-swatch-plus" aria-hidden="true">+</span>';
    echo '</button>';
    echo '</div>';
    echo '<input type="color" id="rs-blob-palette-native" class="rs-blob-native-color" value="#7b00ff" tabindex="-1" aria-hidden="true" />';
    echo '<p class="rs-blob-hint">Clique numa cor para editar · × para remover · + para adicionar. Mínimo 2 cores.</p>';
    echo '</div>';

    ?>
    <style>
        .rs-blob-help { margin: 0 0 16px; color: #646970; }
        .rs-blob-hint { margin: 8px 0 0; color: #646970; font-size: 12px; }
        .rs-blob-field { margin: 0 0 18px; }
        .rs-blob-label { display: block; font-weight: 600; margin-bottom: 8px; }
        .rs-blob-toolbar {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 999px;
            background: #18181b;
            box-shadow: 0 1px 0 rgb(0 0 0 / 0.08);
        }
        .rs-blob-toolbar--palette { border-radius: 20px; }
        .rs-blob-swatch {
            position: relative;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 8px;
            background: var(--swatch, #888);
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.12);
            transition: transform 0.12s ease;
        }
        .rs-blob-swatch:hover { transform: scale(1.06); }
        .rs-blob-swatch:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 2px;
        }
        .rs-blob-swatch-dot {
            display: none;
            position: absolute;
            inset: 0;
            margin: auto;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #111;
            box-shadow: 0 0 0 1px rgb(255 255 255 / 0.35);
        }
        .rs-blob-swatch.is-active .rs-blob-swatch-dot { display: block; }
        .rs-blob-swatch--add {
            background: conic-gradient(
                from 180deg,
                #ff5faf,
                #ffd500,
                #4af117,
                #304ffe,
                #7b00ff,
                #d400ff,
                #ff5faf
            );
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .rs-blob-swatch-plus {
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            line-height: 1;
            text-shadow: 0 1px 2px rgb(0 0 0 / 0.45);
        }
        .rs-blob-swatch-remove {
            display: none;
            position: absolute;
            top: -6px;
            right: -6px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #111;
            color: #fff;
            font-size: 11px;
            line-height: 15px;
            text-align: center;
            box-shadow: 0 0 0 1px rgb(255 255 255 / 0.25);
        }
        .rs-blob-toolbar--palette .rs-blob-swatch:hover .rs-blob-swatch-remove,
        .rs-blob-toolbar--palette .rs-blob-swatch:focus-within .rs-blob-swatch-remove {
            display: block;
        }
        .rs-blob-native-color {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }
    </style>
    <script>
    (function () {
        function normalizeHex(value) {
            value = String(value || '').trim();
            if (/^#[0-9a-fA-F]{6}$/.test(value)) return value.toLowerCase();
            if (/^#[0-9a-fA-F]{3}$/.test(value)) {
                return ('#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3]).toLowerCase();
            }
            return '';
        }

        function getPalette() {
            var input = document.getElementById('<?php echo esc_js(RS_BLOB_META_PALETTE); ?>');
            if (!input) return [];
            return String(input.value || '')
                .split(/[\s,]+/)
                .map(normalizeHex)
                .filter(Boolean);
        }

        function setPalette(colors) {
            var input = document.getElementById('<?php echo esc_js(RS_BLOB_META_PALETTE); ?>');
            if (!input) return;
            input.value = colors.join(',');
            renderPalette();
            refreshPickers();
        }

        function swatchHtml(color, index) {
            return (
                '<button type="button" class="rs-blob-swatch" data-palette-index="' + index + '" data-color="' + color + '" style="--swatch:' + color + ';" title="' + color + ' — clique para editar">' +
                '<span class="rs-blob-swatch-remove" title="Remover" aria-label="Remover">×</span>' +
                '</button>'
            );
        }

        function renderPalette() {
            var list = document.getElementById('rs-blob-palette-list');
            var addBtn = document.getElementById('rs-blob-palette-add');
            if (!list || !addBtn) return;
            var colors = getPalette();
            var html = colors.map(swatchHtml).join('');
            list.querySelectorAll('.rs-blob-swatch:not(.rs-blob-swatch--add)').forEach(function (el) {
                el.remove();
            });
            addBtn.insertAdjacentHTML('beforebegin', html);
        }

        function refreshPickers() {
            var palette = getPalette();
            document.querySelectorAll('[data-rs-blob-picker]').forEach(function (field) {
                var targetId = field.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var toolbar = field.querySelector('.rs-blob-toolbar');
                var addBtn = field.querySelector('.rs-blob-swatch--add');
                var native = field.querySelector('.rs-blob-native-color');
                if (!input || !toolbar || !addBtn) return;

                var current = normalizeHex(input.value) || palette[0] || '#7b00ff';
                input.value = current;
                if (native) native.value = current;

                var colors = palette.slice();
                if (colors.indexOf(current) === -1) colors.push(current);

                toolbar.querySelectorAll('.rs-blob-swatch:not(.rs-blob-swatch--add)').forEach(function (el) {
                    el.remove();
                });

                colors.forEach(function (color) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'rs-blob-swatch' + (color === current ? ' is-active' : '');
                    btn.setAttribute('role', 'option');
                    btn.setAttribute('data-color', color);
                    btn.setAttribute('aria-label', color);
                    btn.title = color;
                    btn.style.setProperty('--swatch', color);
                    btn.innerHTML = '<span class="rs-blob-swatch-dot" aria-hidden="true"></span>';
                    toolbar.insertBefore(btn, addBtn);
                });
            });
        }

        function setPickerValue(field, color) {
            color = normalizeHex(color);
            if (!color) return;
            var targetId = field.getAttribute('data-target');
            var input = document.getElementById(targetId);
            var native = field.querySelector('.rs-blob-native-color');
            if (input) input.value = color;
            if (native) native.value = color;
            field.querySelectorAll('.rs-blob-swatch:not(.rs-blob-swatch--add)').forEach(function (el) {
                el.classList.toggle('is-active', el.getAttribute('data-color') === color);
            });
        }

        document.querySelectorAll('[data-rs-blob-picker]').forEach(function (field) {
            var native = field.querySelector('.rs-blob-native-color');
            field.addEventListener('click', function (event) {
                var swatch = event.target.closest('.rs-blob-swatch');
                if (!swatch || !field.contains(swatch)) return;
                event.preventDefault();
                if (swatch.classList.contains('rs-blob-swatch--add')) {
                    if (native) {
                        native.style.pointerEvents = 'auto';
                        native.click();
                        native.style.pointerEvents = 'none';
                    }
                    return;
                }
                setPickerValue(field, swatch.getAttribute('data-color'));
            });
            if (native) {
                native.addEventListener('input', function () {
                    var color = normalizeHex(native.value);
                    if (!color) return;
                    var palette = getPalette();
                    if (palette.indexOf(color) === -1) {
                        palette.push(color);
                        setPalette(palette);
                    }
                    setPickerValue(field, color);
                    refreshPickers();
                });
            }
        });

        var paletteRoot = document.querySelector('[data-rs-blob-palette]');
        var paletteNative = document.getElementById('rs-blob-palette-native');
        var editingIndex = -1;

        if (paletteRoot) {
            paletteRoot.addEventListener('click', function (event) {
                var remove = event.target.closest('.rs-blob-swatch-remove');
                if (remove) {
                    event.preventDefault();
                    event.stopPropagation();
                    var swatch = remove.closest('.rs-blob-swatch');
                    var index = swatch ? parseInt(swatch.getAttribute('data-palette-index') || '-1', 10) : -1;
                    var colors = getPalette();
                    if (index < 0 || colors.length <= 2) {
                        window.alert('A paleta precisa de pelo menos 2 cores.');
                        return;
                    }
                    colors.splice(index, 1);
                    setPalette(colors);
                    return;
                }

                var add = event.target.closest('#rs-blob-palette-add');
                if (add) {
                    event.preventDefault();
                    editingIndex = -1;
                    if (paletteNative) {
                        paletteNative.value = '#7b00ff';
                        paletteNative.style.pointerEvents = 'auto';
                        paletteNative.click();
                        paletteNative.style.pointerEvents = 'none';
                    }
                    return;
                }

                var edit = event.target.closest('.rs-blob-swatch:not(.rs-blob-swatch--add)');
                if (edit && paletteRoot.contains(edit)) {
                    event.preventDefault();
                    editingIndex = parseInt(edit.getAttribute('data-palette-index') || '-1', 10);
                    if (paletteNative) {
                        paletteNative.value = edit.getAttribute('data-color') || '#7b00ff';
                        paletteNative.style.pointerEvents = 'auto';
                        paletteNative.click();
                        paletteNative.style.pointerEvents = 'none';
                    }
                }
            });
        }

        if (paletteNative) {
            paletteNative.addEventListener('input', function () {
                var color = normalizeHex(paletteNative.value);
                if (!color) return;
                var colors = getPalette();
                if (editingIndex >= 0 && editingIndex < colors.length) {
                    colors[editingIndex] = color;
                } else if (colors.indexOf(color) === -1) {
                    colors.push(color);
                }
                setPalette(colors);
            });
        }
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

    $color1 = isset($_POST[RS_BLOB_META_COLOR1])
        ? rs_blob_normalize_hex(sanitize_text_field(wp_unslash($_POST[RS_BLOB_META_COLOR1])), RS_BLOB_DEFAULT_COLOR1)
        : RS_BLOB_DEFAULT_COLOR1;
    $color2 = isset($_POST[RS_BLOB_META_COLOR2])
        ? rs_blob_normalize_hex(sanitize_text_field(wp_unslash($_POST[RS_BLOB_META_COLOR2])), RS_BLOB_DEFAULT_COLOR2)
        : RS_BLOB_DEFAULT_COLOR2;
    $palette_raw = isset($_POST[RS_BLOB_META_PALETTE])
        ? sanitize_text_field(wp_unslash($_POST[RS_BLOB_META_PALETTE]))
        : RS_BLOB_DEFAULT_PALETTE;
    $palette = rs_blob_parse_palette($palette_raw, rs_blob_default_palette());

    update_post_meta($post_id, RS_BLOB_META_COLOR1, $color1);
    update_post_meta($post_id, RS_BLOB_META_COLOR2, $color2);
    update_post_meta($post_id, RS_BLOB_META_PALETTE, implode(',', $palette));
});
