<?php
/**
 * Campos editáveis do CPT site-ui (labels de UI).
 * Cada post (slug en / pt) edita somente o seu idioma.
 * O menu do header é editado em Aparência → Menus (header-menus.php).
 */

if (defined('RS_SITE_UI_FIELDS_LOADED')) {
    return;
}
define('RS_SITE_UI_FIELDS_LOADED', true);

const RS_SITE_UI_LABEL_KEYS = [
    'rs_site_ui_selected_projects'  => ['selectedProjects', 'Selected Projects', 'Projetos Selecionados'],
    'rs_site_ui_latest_projects'    => ['latestProjects', 'The Latest', 'Últimos'],
    'rs_site_ui_brands_marquee'     => ['brandsMarquee', 'Brands marquee', 'Marcas'],
    'rs_site_ui_see_more_projects'  => ['seeMoreProjects', 'See more projects', 'Veja mais projetos'],
    'rs_site_ui_see_more_work'      => ['seeMoreWork', 'See more work', 'Veja mais trabalhos'],
    'rs_site_ui_whats_new_label'    => ['whatsNewLabel', "What's New (label)", 'Novidades (label)'],
    'rs_site_ui_whats_new_title'    => ['whatsNewTitle', "What's New (title)", 'Novidades (título)'],
    'rs_site_ui_whats_new_subtitle' => ['whatsNewSubtitle', "What's New (subtitle)", 'Novidades (subtítulo)'],
];

const RS_SITE_UI_HOME_COLUMNS_KEY = 'rs_site_ui_home_columns';
const RS_SITE_UI_PROJECTS_INITIAL_KEY = 'rs_site_ui_projects_initial_count';
const RS_SITE_UI_LATEST_COUNT_KEY = 'rs_site_ui_latest_count';

function rs_site_ui_layout_keys(): array {
    return [
        RS_SITE_UI_HOME_COLUMNS_KEY,
        RS_SITE_UI_PROJECTS_INITIAL_KEY,
        RS_SITE_UI_LATEST_COUNT_KEY,
    ];
}

function rs_site_ui_get_layout(): array {
    $en_id = rs_site_ui_get_post_id_by_locale('en');
    $columns = $en_id > 0 ? (int) get_post_meta($en_id, RS_SITE_UI_HOME_COLUMNS_KEY, true) : 2;
    $initial = $en_id > 0 ? (int) get_post_meta($en_id, RS_SITE_UI_PROJECTS_INITIAL_KEY, true) : 5;
    $latest = $en_id > 0 ? (int) get_post_meta($en_id, RS_SITE_UI_LATEST_COUNT_KEY, true) : 4;

    if (!in_array($columns, [1, 2, 3], true)) {
        $columns = 2;
    }
    if ($initial < 1) {
        $initial = 5;
    }
    if (!in_array($latest, [3, 4], true)) {
        $latest = 4;
    }

    return [
        'homeColumns'          => $columns,
        'projectsInitialCount' => $initial,
        'latestCount'          => $latest,
    ];
}

function rs_site_ui_locale_suffix(string $lang): string {
    return $lang === 'pt' ? '_pt' : '_en';
}

function rs_site_ui_all_meta_keys(): array {
    $keys = [];

    foreach (array_keys(RS_SITE_UI_LABEL_KEYS) as $base_key) {
        $config = RS_SITE_UI_LABEL_KEYS[$base_key];
        $keys["{$base_key}_en"] = $config[1] . ' (EN)';
        $keys["{$base_key}_pt"] = $config[2] . ' (PT)';
    }

    return $keys;
}

/** Meta keys editáveis no post do idioma informado. */
function rs_site_ui_editable_keys_for_locale(string $lang): array {
    $suffix = rs_site_ui_locale_suffix($lang);
    $keys = [];

    foreach (array_keys(RS_SITE_UI_LABEL_KEYS) as $base_key) {
        $keys[] = "{$base_key}{$suffix}";
    }

    return $keys;
}

function rs_site_ui_get_post_id_by_locale(string $lang): int {
    $posts = get_posts([
        'post_type'      => 'site-ui',
        'post_status'    => 'publish',
        'name'           => $lang,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    if (!empty($posts[0])) {
        return (int) $posts[0];
    }

    return 0;
}

function rs_site_ui_resolve_locale(int $post_id): string {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'site-ui') {
        if (function_exists('rs_normalize_locale')) {
            $from_slug = rs_normalize_locale($post->post_name);
            if ($from_slug) {
                return $from_slug;
            }
        } elseif (in_array($post->post_name, ['en', 'pt'], true)) {
            return $post->post_name;
        }
    }

    return function_exists('rs_detect_post_locale')
        ? rs_detect_post_locale($post_id)
        : 'en';
}

function rs_site_ui_get_locale_meta(int $post_id, string $locale): array {
    $suffix = rs_site_ui_locale_suffix($locale);
    $meta = [];

    foreach (array_keys(RS_SITE_UI_LABEL_KEYS) as $base_key) {
        $key = "{$base_key}{$suffix}";
        $meta[$key] = (string) get_post_meta($post_id, $key, true);
    }

    return $meta;
}

function rs_site_ui_get_meta(int $post_id): array {
    $data = [];
    foreach (array_keys(rs_site_ui_all_meta_keys()) as $key) {
        $data[$key] = (string) get_post_meta($post_id, $key, true);
    }

    return $data;
}

function rs_site_ui_merged_meta(): array {
    $en_id = rs_site_ui_get_post_id_by_locale('en');
    $pt_id = rs_site_ui_get_post_id_by_locale('pt');

    $meta = array_fill_keys(array_keys(rs_site_ui_all_meta_keys()), '');

    if ($en_id > 0) {
        $meta = array_merge($meta, rs_site_ui_get_meta($en_id));
    }

    if ($pt_id > 0) {
        foreach (rs_site_ui_get_meta($pt_id) as $key => $value) {
            if ($value === '') {
                continue;
            }
            if (str_ends_with($key, '_pt')) {
                $meta[$key] = $value;
            }
        }
    }

    return $meta;
}

function rs_site_ui_build_labels(array $meta, string $lang): array {
    $suffix = rs_site_ui_locale_suffix($lang);
    $labels = [];

    foreach (RS_SITE_UI_LABEL_KEYS as $base_key => $config) {
        $field = $config[0];
        $labels[$field] = trim($meta["{$base_key}{$suffix}"] ?? '');
    }

    return $labels;
}

function rs_site_ui_meta_to_payload(?array $meta = null): array {
    $meta = $meta ?? rs_site_ui_merged_meta();

    return [
        'en' => ['labels' => rs_site_ui_build_labels($meta, 'en')],
        'pt' => ['labels' => rs_site_ui_build_labels($meta, 'pt')],
        'layout' => rs_site_ui_get_layout(),
    ];
}

function rs_site_ui_label_for_locale(string $base_key, string $lang): string {
    $config = RS_SITE_UI_LABEL_KEYS[$base_key] ?? null;
    if (!$config) {
        return $base_key;
    }

    return $lang === 'pt' ? $config[2] : $config[1];
}

add_action('init', function () {
    foreach (array_keys(rs_site_ui_all_meta_keys()) as $key) {
        register_post_meta('site-ui', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    foreach (rs_site_ui_layout_keys() as $key) {
        register_post_meta('site-ui', $key, [
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => false,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
});

add_action('rest_api_init', function () {
    register_rest_field('site-ui', 'site_ui_data', [
        'get_callback' => function () {
            return rs_site_ui_meta_to_payload();
        },
        'schema' => [
            'description' => 'Labels de UI (EN/PT combinados)',
            'type'        => 'object',
            'context'     => ['view', 'edit'],
        ],
    ]);
});

add_action('add_meta_boxes_site-ui', function () {
    add_meta_box(
        'rs_site_ui_fields',
        'Textos de interface',
        'rs_site_ui_render_meta_box',
        'site-ui',
        'normal',
        'high'
    );

    remove_meta_box('postcustom', 'site-ui', 'normal');
}, 10);

function rs_site_ui_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_site_ui_save', 'rs_site_ui_nonce');

    $locale = rs_site_ui_resolve_locale($post->ID);
    $meta = rs_site_ui_get_locale_meta($post->ID, $locale);
    $lang_label = $locale === 'pt' ? 'Português' : 'English';
    $menus_url = admin_url('nav-menus.php');

    echo '<p style="margin-top:0;color:#646970;">';
    echo 'Este post edita somente os textos em <strong>' . esc_html($lang_label) . '</strong>. ';
    echo 'Campos vazios usam o fallback do código Next.js.';
    echo '</p>';
    echo '<p style="margin:0 0 16px;color:#646970;">';
    echo 'O menu do header é editado em <a href="' . esc_url($menus_url) . '">Aparência → Menus</a>.';
    echo '</p>';

    echo '<fieldset style="margin:0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
    echo '<legend style="font-weight:600;padding:0 6px;"><strong>Labels de seção (' . esc_html(strtoupper($locale)) . ')</strong></legend>';

    foreach (RS_SITE_UI_LABEL_KEYS as $base_key => $config) {
        $key = "{$base_key}_" . rs_site_ui_locale_suffix($locale);
        $value = $meta[$key] ?? '';
        $label = rs_site_ui_label_for_locale($base_key, $locale);

        echo '<p style="margin:0 0 12px;">';
        echo '<label for="' . esc_attr($key) . '" style="display:block;font-weight:500;margin-bottom:4px;">' . esc_html($label) . '</label>';
        echo '<input type="text" style="width:100%;" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        echo '</p>';
    }

    echo '</fieldset>';

    if ($locale === 'en') {
        $layout = rs_site_ui_get_layout();
        echo '<fieldset style="margin:16px 0 0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">';
        echo '<legend style="font-weight:600;padding:0 6px;"><strong>Layout (global)</strong></legend>';
        echo '<p style="margin:0 0 10px;color:#646970;font-size:12px;">Valores compartilhados EN/PT. Editáveis só no post <code>en</code>.</p>';

        echo '<p style="margin:0 0 12px;"><label for="' . esc_attr(RS_SITE_UI_HOME_COLUMNS_KEY) . '" style="display:block;font-weight:500;margin-bottom:4px;">Colunas na home (Selected Projects)</label>';
        echo '<select id="' . esc_attr(RS_SITE_UI_HOME_COLUMNS_KEY) . '" name="' . esc_attr(RS_SITE_UI_HOME_COLUMNS_KEY) . '">';
        foreach ([1, 2, 3] as $cols) {
            echo '<option value="' . $cols . '"' . selected($layout['homeColumns'], $cols, false) . '>' . $cols . '</option>';
        }
        echo '</select></p>';

        echo '<p style="margin:0 0 12px;"><label for="' . esc_attr(RS_SITE_UI_PROJECTS_INITIAL_KEY) . '" style="display:block;font-weight:500;margin-bottom:4px;">Projetos ao abrir /projects (antes do “see more”)</label>';
        echo '<input type="number" min="1" max="100" style="width:100px;" id="' . esc_attr(RS_SITE_UI_PROJECTS_INITIAL_KEY) . '" name="' . esc_attr(RS_SITE_UI_PROJECTS_INITIAL_KEY) . '" value="' . esc_attr((string) $layout['projectsInitialCount']) . '" /></p>';

        echo '<p style="margin:0;"><label for="' . esc_attr(RS_SITE_UI_LATEST_COUNT_KEY) . '" style="display:block;font-weight:500;margin-bottom:4px;">Cards no “The Latest”</label>';
        echo '<select id="' . esc_attr(RS_SITE_UI_LATEST_COUNT_KEY) . '" name="' . esc_attr(RS_SITE_UI_LATEST_COUNT_KEY) . '">';
        foreach ([3, 4] as $n) {
            echo '<option value="' . $n . '"' . selected($layout['latestCount'], $n, false) . '>' . $n . '</option>';
        }
        echo '</select></p>';
        echo '</fieldset>';
    }
}

add_action('save_post_site-ui', function (int $post_id) {
    if (!isset($_POST['rs_site_ui_nonce']) || !wp_verify_nonce($_POST['rs_site_ui_nonce'], 'rs_site_ui_save')) {
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

    $locale = rs_site_ui_resolve_locale($post_id);

    foreach (rs_site_ui_editable_keys_for_locale($locale) as $key) {
        if (!array_key_exists($key, $_POST)) {
            continue;
        }

        $value = sanitize_text_field(wp_unslash($_POST[$key]));
        update_post_meta($post_id, $key, $value);
    }

    if ($locale === 'en') {
        if (isset($_POST[RS_SITE_UI_HOME_COLUMNS_KEY])) {
            $cols = (int) $_POST[RS_SITE_UI_HOME_COLUMNS_KEY];
            update_post_meta($post_id, RS_SITE_UI_HOME_COLUMNS_KEY, in_array($cols, [1, 2, 3], true) ? (string) $cols : '2');
        }
        if (isset($_POST[RS_SITE_UI_PROJECTS_INITIAL_KEY])) {
            $initial = max(1, min(100, (int) $_POST[RS_SITE_UI_PROJECTS_INITIAL_KEY]));
            update_post_meta($post_id, RS_SITE_UI_PROJECTS_INITIAL_KEY, (string) $initial);
        }
        if (isset($_POST[RS_SITE_UI_LATEST_COUNT_KEY])) {
            $latest = (int) $_POST[RS_SITE_UI_LATEST_COUNT_KEY];
            update_post_meta($post_id, RS_SITE_UI_LATEST_COUNT_KEY, in_array($latest, [3, 4], true) ? (string) $latest : '4');
        }
    }
});

function rs_copy_site_ui_fields(int $from_id, int $to_id): void {
    $from_locale = function_exists('rs_detect_post_locale') ? rs_detect_post_locale($from_id) : 'en';
    $to_locale = function_exists('rs_detect_post_locale') ? rs_detect_post_locale($to_id) : 'pt';

    foreach (rs_site_ui_editable_keys_for_locale($from_locale) as $key) {
        $target_key = str_replace(rs_site_ui_locale_suffix($from_locale), rs_site_ui_locale_suffix($to_locale), $key);
        if (!in_array($target_key, rs_site_ui_editable_keys_for_locale($to_locale), true)) {
            continue;
        }

        update_post_meta($to_id, $target_key, get_post_meta($from_id, $key, true));
    }
}

function rs_site_ui_ensure_locale_posts(): void {
    if (get_option('rs_site_ui_posts_ensured_v1')) {
        return;
    }

    $en_id = rs_site_ui_get_post_id_by_locale('en');
    $pt_id = rs_site_ui_get_post_id_by_locale('pt');

    if ($en_id > 0 && $pt_id === 0) {
        $en_post = get_post($en_id);
        if ($en_post) {
            $pt_id = (int) wp_insert_post([
                'post_title'  => $en_post->post_title,
                'post_status' => 'publish',
                'post_type'   => 'site-ui',
                'post_name'   => 'pt',
                'post_author' => (int) $en_post->post_author ?: 1,
            ], true);

            if (!is_wp_error($pt_id) && $pt_id > 0) {
                foreach (rs_site_ui_editable_keys_for_locale('pt') as $key) {
                    $value = get_post_meta($en_id, $key, true);
                    if ($value !== '') {
                        update_post_meta($pt_id, $key, $value);
                    }
                }

                if (function_exists('rs_translate_link_pair')) {
                    rs_translate_link_pair($en_id, 'PT', $pt_id);
                }

                if (function_exists('rs_apply_locale_slug')) {
                    rs_apply_locale_slug($pt_id);
                }
            }
        }
    }

    update_option('rs_site_ui_posts_ensured_v1', 1);
}

add_action('init', 'rs_site_ui_ensure_locale_posts', 25);
