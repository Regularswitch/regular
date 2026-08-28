<?php
/**
 * CPT site-ui — post único bilíngue (EN + PT).
 */

if (defined('RS_SITE_UI_FIELDS_LOADED')) {
    return;
}
define('RS_SITE_UI_FIELDS_LOADED', true);

const RS_SITE_UI_I18N_KEY = 'rs_site_ui_i18n';

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

function rs_site_ui_default_shared(): array {
    return [
        'homeColumns'          => 2,
        'projectsInitialCount' => 7,
        'latestCount'          => 6,
    ];
}

function rs_site_ui_default_locale(): array {
    return ['labels' => array_fill_keys(array_column(RS_SITE_UI_LABEL_KEYS, 0), '')];
}

function rs_site_ui_i18n_default(): array {
    return [
        'v'       => 1,
        'shared'  => rs_site_ui_default_shared(),
        'locales' => [
            'en' => rs_site_ui_default_locale(),
            'pt' => rs_site_ui_default_locale(),
        ],
    ];
}

function rs_site_ui_normalize_layout(array $raw): array {
    $columns = (int) ($raw['homeColumns'] ?? 2);
    $initial = (int) ($raw['projectsInitialCount'] ?? 7);
    $latest = (int) ($raw['latestCount'] ?? 6);
    if ($initial < 1) {
        $initial = 5;
    }

    return [
        'homeColumns'          => in_array($columns, [1, 2, 3], true) ? $columns : 2,
        'projectsInitialCount' => min(100, $initial),
        'latestCount'          => in_array($latest, [4, 6, 8, 12], true) ? $latest : 6,
    ];
}

function rs_site_ui_i18n_normalize(array $raw): array {
    $data = rs_site_ui_i18n_default();
    $shared = is_array($raw['shared'] ?? null) ? $raw['shared'] : [];
    $en_raw = is_array($raw['locales']['en'] ?? null) ? $raw['locales']['en'] : [];
    $data['shared'] = rs_site_ui_normalize_layout([
        'homeColumns' => $shared['homeColumns'] ?? $en_raw['homeColumns'] ?? 2,
        'projectsInitialCount' => $shared['projectsInitialCount'] ?? $en_raw['projectsInitialCount'] ?? 7,
        'latestCount' => $shared['latestCount'] ?? $en_raw['latestCount'] ?? 6,
    ]);

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $labels = is_array($loc['labels'] ?? null) ? $loc['labels'] : $loc;
        foreach (RS_SITE_UI_LABEL_KEYS as $config) {
            $field = $config[0];
            $data['locales'][$locale]['labels'][$field] = trim((string) ($labels[$field] ?? ''));
        }
    }

    return $data;
}

function rs_site_ui_locale_suffix(string $lang): string {
    return $lang === 'pt' ? '_pt' : '_en';
}

function rs_site_ui_all_meta_keys(): array {
    $keys = [];
    foreach (RS_SITE_UI_LABEL_KEYS as $base_key => $config) {
        $keys["{$base_key}_en"] = $config[1] . ' (EN)';
        $keys["{$base_key}_pt"] = $config[2] . ' (PT)';
    }
    return $keys;
}

function rs_site_ui_get_post_id_by_locale(string $lang = 'en'): int {
    return function_exists('rs_section_i18n_canonical_id')
        ? rs_section_i18n_canonical_id('site-ui')
        : 0;
}

function rs_site_ui_locale_from_legacy_post(int $post_id, string $locale): array {
    $suffix = rs_site_ui_locale_suffix($locale);
    $labels = [];
    foreach (RS_SITE_UI_LABEL_KEYS as $base_key => $config) {
        $labels[$config[0]] = $post_id > 0
            ? trim((string) get_post_meta($post_id, "{$base_key}{$suffix}", true))
            : '';
    }

    $locale_data = ['labels' => $labels];
    if ($locale === 'en' && $post_id > 0) {
        $locale_data += [
            'homeColumns' => (int) get_post_meta($post_id, RS_SITE_UI_HOME_COLUMNS_KEY, true),
            'projectsInitialCount' => (int) get_post_meta($post_id, RS_SITE_UI_PROJECTS_INITIAL_KEY, true),
            'latestCount' => (int) get_post_meta($post_id, RS_SITE_UI_LATEST_COUNT_KEY, true),
        ];
    }

    return $locale_data;
}

function rs_site_ui_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;
    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_SITE_UI_I18N_KEY)
        : get_post_meta($post_id, RS_SITE_UI_I18N_KEY, true);
    if (is_array($raw)) {
        return rs_site_ui_i18n_normalize($raw);
    }

    $data = rs_site_ui_i18n_default();
    $data['locales']['en'] = rs_site_ui_locale_from_legacy_post($post_id, 'en');
    $data['shared'] = rs_site_ui_normalize_layout($data['locales']['en']);
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_site_ui_locale_from_legacy_post($pt_id, 'pt');
    }
    return rs_site_ui_i18n_normalize($data);
}

function rs_site_ui_get_layout(?int $post_id = null): array {
    $post_id = $post_id ?? rs_site_ui_get_post_id_by_locale('en');
    return rs_site_ui_i18n_get($post_id)['shared'];
}

function rs_site_ui_meta_to_payload(?array $data = null, ?int $post_id = null): array {
    $post_id = $post_id ?? rs_site_ui_get_post_id_by_locale('en');
    $data = $data ?? rs_site_ui_i18n_get($post_id);
    return [
        'en' => ['labels' => $data['locales']['en']['labels']],
        'pt' => ['labels' => $data['locales']['pt']['labels']],
        'layout' => $data['shared'],
    ];
}

function rs_site_ui_label_for_locale(string $base_key, string $lang): string {
    $config = RS_SITE_UI_LABEL_KEYS[$base_key] ?? null;
    if (!$config) {
        return $base_key;
    }

    return $lang === 'pt' ? $config[2] : $config[1];
}

function rs_site_ui_sync_legacy_meta(int $post_id, array $data): void {
    foreach (['en', 'pt'] as $locale) {
        $suffix = rs_site_ui_locale_suffix($locale);
        $labels = is_array($data['locales'][$locale]['labels'] ?? null)
            ? $data['locales'][$locale]['labels']
            : [];
        foreach (RS_SITE_UI_LABEL_KEYS as $base_key => $config) {
            update_post_meta($post_id, "{$base_key}{$suffix}", (string) ($labels[$config[0]] ?? ''));
        }
    }

    $layout = rs_site_ui_normalize_layout((array) ($data['shared'] ?? []));
    update_post_meta($post_id, RS_SITE_UI_HOME_COLUMNS_KEY, (string) $layout['homeColumns']);
    update_post_meta($post_id, RS_SITE_UI_PROJECTS_INITIAL_KEY, (string) $layout['projectsInitialCount']);
    update_post_meta($post_id, RS_SITE_UI_LATEST_COUNT_KEY, (string) $layout['latestCount']);
}

function rs_site_ui_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }

    $id = rs_section_i18n_migrate_twins(
        'site-ui',
        RS_SITE_UI_I18N_KEY,
        'rs_site_ui_i18n_migrated_v1',
        'Site UI',
        static function (int $post_id, string $locale): array {
            return rs_site_ui_locale_from_legacy_post($post_id, $locale);
        },
        'rs_site_ui_i18n_normalize'
    );

    if ($id > 0) {
        rs_site_ui_sync_legacy_meta($id, rs_site_ui_i18n_get($id));
    }
}

add_action('init', function () {
    register_post_meta('site-ui', RS_SITE_UI_I18N_KEY, [
        'single'        => true,
        'type'          => 'array',
        'show_in_rest'  => false,
        'auth_callback' => static function (): bool {
            return current_user_can('edit_posts');
        },
    ]);

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
}, 20);

add_action('init', 'rs_site_ui_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('site-ui', 'site_ui_data', [
        'get_callback' => function (array $post) {
            $post_id = function_exists('rs_section_i18n_resolve_id')
                ? rs_section_i18n_resolve_id((int) $post['id'])
                : (int) $post['id'];
            return rs_site_ui_meta_to_payload(null, $post_id);
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

    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id((int) $post->ID)
        : (int) $post->ID;
    $data = rs_site_ui_i18n_get($post_id);
    $layout = $data['shared'];
    $menus_url = admin_url('nav-menus.php');

    echo '<p style="margin-top:0;color:#646970;">Um único post. Layout compartilhado e labels em <strong>English</strong> e <strong>Português</strong>. Campos vazios usam o fallback do código Next.js. ' . (function_exists('rs_plugin_version_markup') ? rs_plugin_version_markup() : '') . '</p>';
    echo '<p style="margin:0 0 16px;color:#646970;">';
    echo 'O menu do header é editado em <a href="' . esc_url($menus_url) . '">Aparência → Menus</a>.';
    echo '</p>';

    echo '<fieldset class="rs-metabox-fieldset">';
    echo '<legend><strong>Geral — layout compartilhado</strong></legend>';
    echo '<p style="margin:0 0 12px;"><label for="rs_site_ui_home_columns_shared" style="display:block;font-weight:500;margin-bottom:4px;">Colunas na home (Selected Projects)</label>';
    echo '<select id="rs_site_ui_home_columns_shared" name="rs_site_ui_shared[homeColumns]">';
    foreach ([1, 2, 3] as $cols) {
        echo '<option value="' . $cols . '"' . selected($layout['homeColumns'], $cols, false) . '>' . $cols . '</option>';
    }
    echo '</select></p>';
    echo '<p style="margin:0 0 12px;"><label for="rs_site_ui_projects_initial_shared" style="display:block;font-weight:500;margin-bottom:4px;">Projetos ao abrir /projects (antes do “see more”)</label>';
    echo '<input type="number" min="1" max="100" style="width:100px;" id="rs_site_ui_projects_initial_shared" name="rs_site_ui_shared[projectsInitialCount]" value="' . esc_attr((string) $layout['projectsInitialCount']) . '" /></p>';
    echo '<p style="margin:0;"><label for="rs_site_ui_latest_count_shared" style="display:block;font-weight:500;margin-bottom:4px;">Itens no carrossel “The Latest”</label>';
    echo '<select id="rs_site_ui_latest_count_shared" name="rs_site_ui_shared[latestCount]">';
    foreach ([4, 6, 8, 12] as $n) {
        echo '<option value="' . $n . '"' . selected($layout['latestCount'], $n, false) . '>' . $n . '</option>';
    }
    echo '</select></p></fieldset>';

    echo '<div class="rs-metabox-tabs" data-rs-tabs><div class="rs-metabox-tablist" role="tablist">';
    echo '<button type="button" class="rs-metabox-tab is-active" role="tab" aria-selected="true" data-tab="en">English</button>';
    echo '<button type="button" class="rs-metabox-tab" role="tab" aria-selected="false" data-tab="pt">Português</button></div>';
    foreach (['en', 'pt'] as $locale) {
        $active = $locale === 'en';
        echo '<div class="rs-metabox-tabpanel' . ($active ? ' is-active' : '') . '" data-tab="' . esc_attr($locale) . '" role="tabpanel"' . ($active ? '' : ' hidden') . '>';
        echo '<fieldset class="rs-metabox-fieldset"><legend><strong>Labels de seção</strong></legend>';
        echo '<div data-rs-accordion class="rs-site-ui-accordion">';
        rs_metabox_accordion_item_open('Labels', true);
        foreach (RS_SITE_UI_LABEL_KEYS as $base_key => $config) {
            $field = $config[0];
            $label = rs_site_ui_label_for_locale($base_key, $locale);
            $id = 'rs_site_ui_' . $field . '_' . $locale;
            echo '<p class="rs-admin-text-field" style="margin:0 0 12px;"><label for="' . esc_attr($id) . '" style="display:block;font-weight:500;margin-bottom:4px;">' . esc_html($label) . '</label>';
            rs_render_rich_text_field(
                $id,
                'rs_site_ui_i18n_input[' . $locale . '][labels][' . $field . ']',
                (string) ($data['locales'][$locale]['labels'][$field] ?? ''),
                'compact'
            );
            echo '</p>';
        }
        rs_metabox_accordion_item_close();
        echo '</div></fieldset></div>';
    }
    echo '</div>';
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

    $post_id = function_exists('rs_section_i18n_resolve_id')
        ? rs_section_i18n_resolve_id($post_id)
        : $post_id;
    $data = rs_site_ui_i18n_get($post_id);
    $shared = isset($_POST['rs_site_ui_shared']) && is_array($_POST['rs_site_ui_shared'])
        ? wp_unslash($_POST['rs_site_ui_shared'])
        : [];
    $data['shared'] = rs_site_ui_normalize_layout($shared);
    $raw = isset($_POST['rs_site_ui_i18n_input']) && is_array($_POST['rs_site_ui_i18n_input'])
        ? wp_unslash($_POST['rs_site_ui_i18n_input'])
        : [];
    foreach (['en', 'pt'] as $locale) {
        $labels = is_array($raw[$locale]['labels'] ?? null) ? $raw[$locale]['labels'] : [];
        foreach (RS_SITE_UI_LABEL_KEYS as $config) {
            $field = $config[0];
            $data['locales'][$locale]['labels'][$field] = wp_kses_post((string) ($labels[$field] ?? ''));
        }
    }

    $data = rs_site_ui_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_SITE_UI_I18N_KEY, $data);
    } else {
        update_post_meta($post_id, RS_SITE_UI_I18N_KEY, $data);
    }
    rs_site_ui_sync_legacy_meta($post_id, $data);
});

function rs_copy_site_ui_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}

function rs_site_ui_ensure_locale_posts(): void {
    // Legado no-op: a migração mantém um único post.
}
