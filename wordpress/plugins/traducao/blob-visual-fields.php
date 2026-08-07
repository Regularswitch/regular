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
const RS_BLOB_MAX_COLORS = 8;

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
    $seen = [];

    foreach ($parts as $part) {
        $normalized = rs_blob_normalize_hex($part, '');
        if ($normalized === '' || isset($seen[$normalized])) {
            continue;
        }
        $seen[$normalized] = true;
        $colors[] = $normalized;
        if (count($colors) >= RS_BLOB_MAX_COLORS) {
            break;
        }
    }

    return count($colors) >= 2 ? $colors : $fallback;
}

/** Garante que a cor escolhida exista na paleta. */
function rs_blob_clamp_to_palette(string $color, array $palette, string $fallback): string {
    $color = rs_blob_normalize_hex($color, '');
    if ($color !== '' && in_array($color, $palette, true)) {
        return $color;
    }
    $fallback = rs_blob_normalize_hex($fallback, '');
    if ($fallback !== '' && in_array($fallback, $palette, true)) {
        return $fallback;
    }
    return $palette[0] ?? RS_BLOB_DEFAULT_COLOR1;
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
    $palette = rs_blob_parse_palette($meta[RS_BLOB_META_PALETTE], $defaults_palette);

    return [
        'color1'  => rs_blob_clamp_to_palette($meta[RS_BLOB_META_COLOR1], $palette, RS_BLOB_DEFAULT_COLOR1),
        'color2'  => rs_blob_clamp_to_palette($meta[RS_BLOB_META_COLOR2], $palette, RS_BLOB_DEFAULT_COLOR2),
        'palette' => $palette,
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
 * Toolbar de swatches — só seleção a partir da paleta (sem +).
 */
function rs_blob_render_color_picker_row(string $input_id, string $input_name, string $value, array $palette, string $label): void {
    $value = rs_blob_clamp_to_palette($value, $palette, $palette[0] ?? RS_BLOB_DEFAULT_COLOR1);

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

    echo '</div>';
    echo '</div>';
}

function rs_blob_visual_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_blob_visual_save', 'rs_blob_visual_nonce');

    $meta = rs_blob_visual_get_meta($post->ID);
    $palette = rs_blob_parse_palette(
        trim($meta[RS_BLOB_META_PALETTE]) !== '' ? $meta[RS_BLOB_META_PALETTE] : RS_BLOB_DEFAULT_PALETTE,
        rs_blob_default_palette()
    );
    $color1 = rs_blob_clamp_to_palette(
        (string) $meta[RS_BLOB_META_COLOR1],
        $palette,
        RS_BLOB_DEFAULT_COLOR1
    );
    $color2 = rs_blob_clamp_to_palette(
        (string) $meta[RS_BLOB_META_COLOR2],
        $palette,
        RS_BLOB_DEFAULT_COLOR2
    );

    echo '<p class="rs-blob-help">';
    echo 'Paleta única para a home em <strong>inglês e português</strong>. ';
    echo 'Novas cores só na paleta (máx. ' . (int) RS_BLOB_MAX_COLORS . '). ';
    echo 'Principal e secundária são escolhidas a partir dela. ';
    echo '<em>(Plugin Tradução v1.2.19)</em>';
    echo '</p>';

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
    echo '<p class="rs-blob-hint">Clique para editar · × para remover · + para adicionar (máx. ' . (int) RS_BLOB_MAX_COLORS . '). ';
    echo '<button type="button" class="button button-small" id="rs-blob-palette-reset" style="margin-left:6px;">Restaurar paleta padrão</button>';
    echo '</p>';
    echo '</div>';

    rs_blob_render_color_picker_row(RS_BLOB_META_COLOR1, RS_BLOB_META_COLOR1, $color1, $palette, 'Cor principal');
    rs_blob_render_color_picker_row(RS_BLOB_META_COLOR2, RS_BLOB_META_COLOR2, $color2, $palette, 'Cor secundária');

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
            max-width: 100%;
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
            position: fixed;
            left: -9999px;
            top: 0;
            width: 32px;
            height: 32px;
            opacity: 0;
        }
    </style>
    <script>
    (function () {
        if (window.__rsBlobColorPickerInit) return;
        window.__rsBlobColorPickerInit = true;

        var DEFAULT_PALETTE = <?php echo wp_json_encode(rs_blob_default_palette()); ?>;
        var MAX_COLORS = <?php echo (int) RS_BLOB_MAX_COLORS; ?>;
        var COLOR1_ID = '<?php echo esc_js(RS_BLOB_META_COLOR1); ?>';
        var COLOR2_ID = '<?php echo esc_js(RS_BLOB_META_COLOR2); ?>';
        var paletteInput = document.getElementById('<?php echo esc_js(RS_BLOB_META_PALETTE); ?>');
        var paletteList = document.getElementById('rs-blob-palette-list');
        var paletteAdd = document.getElementById('rs-blob-palette-add');
        var paletteNative = document.getElementById('rs-blob-palette-native');
        var mode = 'idle';
        var editIndex = -1;

        function normalizeHex(value) {
            value = String(value || '').trim();
            if (/^#[0-9a-fA-F]{6}$/.test(value)) return value.toLowerCase();
            if (/^#[0-9a-fA-F]{3}$/.test(value)) {
                return ('#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3]).toLowerCase();
            }
            return '';
        }

        function uniqueColors(list) {
            var out = [];
            var seen = {};
            (list || []).forEach(function (item) {
                var color = normalizeHex(item);
                if (!color || seen[color]) return;
                seen[color] = true;
                out.push(color);
            });
            return out;
        }

        function getPalette() {
            if (!paletteInput) return DEFAULT_PALETTE.slice();
            return uniqueColors(String(paletteInput.value || '').split(/[\s,]+/));
        }

        function clampToPalette(color, palette, fallback) {
            color = normalizeHex(color);
            if (color && palette.indexOf(color) !== -1) return color;
            fallback = normalizeHex(fallback);
            if (fallback && palette.indexOf(fallback) !== -1) return fallback;
            return palette[0] || '#7b00ff';
        }

        function syncPrimaryColors(palette, mapOldToNew) {
            [COLOR1_ID, COLOR2_ID].forEach(function (id, idx) {
                var input = document.getElementById(id);
                if (!input) return;
                var current = normalizeHex(input.value);
                if (mapOldToNew && mapOldToNew[current]) {
                    current = mapOldToNew[current];
                }
                var fallback = palette[Math.min(idx, palette.length - 1)] || palette[0];
                input.value = clampToPalette(current, palette, fallback);
            });
        }

        function setPalette(colors, mapOldToNew) {
            if (!paletteInput) return;
            colors = uniqueColors(colors).slice(0, MAX_COLORS);
            if (colors.length < 2) colors = DEFAULT_PALETTE.slice();
            paletteInput.value = colors.join(',');
            syncPrimaryColors(colors, mapOldToNew || null);
            renderPalette();
            refreshPickers();
        }

        function renderPalette() {
            if (!paletteList || !paletteAdd) return;
            var colors = getPalette();
            paletteList.querySelectorAll('.rs-blob-swatch:not(.rs-blob-swatch--add)').forEach(function (el) {
                el.remove();
            });
            colors.forEach(function (color, index) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'rs-blob-swatch';
                btn.setAttribute('data-palette-index', String(index));
                btn.setAttribute('data-color', color);
                btn.style.setProperty('--swatch', color);
                btn.title = color + ' — clique para editar';
                btn.innerHTML = '<span class="rs-blob-swatch-remove" title="Remover" aria-label="Remover">×</span>';
                paletteList.insertBefore(btn, paletteAdd);
            });
            if (paletteAdd) {
                paletteAdd.style.display = colors.length >= MAX_COLORS ? 'none' : '';
            }
        }

        function refreshPickers() {
            var palette = getPalette();
            document.querySelectorAll('[data-rs-blob-picker]').forEach(function (field) {
                var targetId = field.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var toolbar = field.querySelector('.rs-blob-toolbar');
                if (!input || !toolbar) return;

                var current = clampToPalette(input.value, palette, palette[0]);
                input.value = current;

                toolbar.innerHTML = '';
                palette.forEach(function (color) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'rs-blob-swatch' + (color === current ? ' is-active' : '');
                    btn.setAttribute('data-color', color);
                    btn.title = color;
                    btn.style.setProperty('--swatch', color);
                    btn.innerHTML = '<span class="rs-blob-swatch-dot" aria-hidden="true"></span>';
                    toolbar.appendChild(btn);
                });
            });
        }

        function setPickerValue(field, color) {
            color = normalizeHex(color);
            if (!color) return;
            if (getPalette().indexOf(color) === -1) return;
            var targetId = field.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (input) input.value = color;
            field.querySelectorAll('.rs-blob-swatch').forEach(function (el) {
                el.classList.toggle('is-active', el.getAttribute('data-color') === color);
            });
        }

        function openNativeColor(native, startColor) {
            if (!native) return;
            var hex = normalizeHex(startColor) || '#7b00ff';
            native.defaultValue = hex;
            native.value = hex;
            try {
                if (typeof native.showPicker === 'function') {
                    native.showPicker();
                } else {
                    native.click();
                }
            } catch (err) {
                native.click();
            }
        }

        document.querySelectorAll('[data-rs-blob-picker]').forEach(function (field) {
            field.addEventListener('click', function (event) {
                var swatch = event.target.closest('.rs-blob-swatch');
                if (!swatch || !field.contains(swatch)) return;
                event.preventDefault();
                setPickerValue(field, swatch.getAttribute('data-color'));
            });
        });

        if (paletteList) {
            paletteList.addEventListener('click', function (event) {
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
                    mode = 'idle';
                    editIndex = -1;
                    setPalette(colors);
                    return;
                }

                if (event.target.closest('#rs-blob-palette-add')) {
                    event.preventDefault();
                    if (getPalette().length >= MAX_COLORS) {
                        window.alert('Máximo de ' + MAX_COLORS + ' cores na paleta.');
                        return;
                    }
                    mode = 'add';
                    editIndex = -1;
                    openNativeColor(paletteNative, '#7b00ff');
                    return;
                }

                var edit = event.target.closest('.rs-blob-swatch:not(.rs-blob-swatch--add)');
                if (edit) {
                    event.preventDefault();
                    mode = 'edit';
                    editIndex = parseInt(edit.getAttribute('data-palette-index') || '-1', 10);
                    openNativeColor(paletteNative, edit.getAttribute('data-color'));
                }
            });
        }

        if (paletteNative) {
            paletteNative.addEventListener('input', function () {
                if (mode !== 'edit' || editIndex < 0) return;
                var color = normalizeHex(paletteNative.value);
                if (!color) return;
                var swatch = paletteList && paletteList.querySelector('[data-palette-index="' + editIndex + '"]');
                if (swatch) {
                    swatch.style.setProperty('--swatch', color);
                }
            });

            paletteNative.addEventListener('change', function () {
                var color = normalizeHex(paletteNative.value);
                if (!color) return;
                var colors = getPalette();

                if (mode === 'edit' && editIndex >= 0 && editIndex < colors.length) {
                    var oldColor = colors[editIndex];
                    var map = {};
                    map[oldColor] = color;
                    colors[editIndex] = color;
                    setPalette(colors, map);
                    editIndex = getPalette().indexOf(color);
                    if (editIndex < 0) mode = 'idle';
                    return;
                }

                if (mode === 'add') {
                    colors.push(color);
                    setPalette(colors);
                    mode = 'edit';
                    editIndex = getPalette().indexOf(color);
                    if (editIndex < 0) mode = 'idle';
                }
            });
        }

        var resetBtn = document.getElementById('rs-blob-palette-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (!window.confirm('Restaurar a paleta padrão? Isso remove as cores extras.')) return;
                mode = 'idle';
                editIndex = -1;
                setPalette(DEFAULT_PALETTE.slice());
            });
        }

        setPalette(getPalette());
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

    $palette_raw = isset($_POST[RS_BLOB_META_PALETTE])
        ? sanitize_text_field(wp_unslash($_POST[RS_BLOB_META_PALETTE]))
        : RS_BLOB_DEFAULT_PALETTE;
    $palette = rs_blob_parse_palette($palette_raw, rs_blob_default_palette());

    $color1 = rs_blob_clamp_to_palette(
        isset($_POST[RS_BLOB_META_COLOR1]) ? sanitize_text_field(wp_unslash($_POST[RS_BLOB_META_COLOR1])) : '',
        $palette,
        RS_BLOB_DEFAULT_COLOR1
    );
    $color2 = rs_blob_clamp_to_palette(
        isset($_POST[RS_BLOB_META_COLOR2]) ? sanitize_text_field(wp_unslash($_POST[RS_BLOB_META_COLOR2])) : '',
        $palette,
        RS_BLOB_DEFAULT_COLOR2
    );

    update_post_meta($post_id, RS_BLOB_META_COLOR1, $color1);
    update_post_meta($post_id, RS_BLOB_META_COLOR2, $color2);
    update_post_meta($post_id, RS_BLOB_META_PALETTE, implode(',', $palette));
});
