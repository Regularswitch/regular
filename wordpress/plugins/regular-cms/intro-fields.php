<?php
/**
 * CPT intro — post único bilíngue (EN + PT).
 *
 * content = headline, excerpt = body, balloons = pills da home.
 * O editor WP nativo foi removido — só o metabox EN/PT.
 */

if (defined('RS_INTRO_FIELDS_LOADED')) {
    return;
}
define('RS_INTRO_FIELDS_LOADED', true);

const RS_INTRO_I18N_KEY = 'rs_intro_i18n';

/**
 * @return list<string>
 */
function rs_intro_default_balloons(string $locale): array {
    if ($locale === 'pt') {
        return [
            'Estratégia',
            'Narrativa',
            'Branding',
            'Sistemas Visuais',
            'Experiências Digitais',
            'Conteúdo',
            'Campanhas',
            'Design Generativo',
            'Motion Design',
            'Design Editorial',
            'Expografia',
        ];
    }

    return [
        'Strategy',
        'Narrative',
        'Branding',
        'Visual Systems',
        'Digital Experiences',
        'Content',
        'Campaigns',
        'Generative Design',
        'Motion Design',
        'Editorial Design',
        'Expography',
    ];
}

/**
 * @param mixed $raw
 * @return list<string>
 */
function rs_intro_normalize_balloons($raw, string $locale = 'en', bool $fallback_defaults = false): array {
    if (!is_array($raw)) {
        return $fallback_defaults ? rs_intro_default_balloons($locale) : [];
    }

    $out = [];
    foreach ($raw as $item) {
        if (is_array($item)) {
            $label = trim(wp_strip_all_tags((string) ($item['label'] ?? $item['title'] ?? $item['text'] ?? '')));
        } else {
            $label = trim(wp_strip_all_tags((string) $item));
        }
        if ($label === '') {
            continue;
        }
        $out[] = $label;
    }

    if ($out === [] && $fallback_defaults) {
        return rs_intro_default_balloons($locale);
    }

    return array_values($out);
}

function rs_intro_default_locale(string $locale = 'en'): array {
    return [
        'content'  => '',
        'excerpt'  => '',
        'balloons' => rs_intro_default_balloons($locale),
    ];
}

function rs_intro_i18n_default(): array {
    return [
        'v' => 2,
        'shared' => [],
        'locales' => [
            'en' => rs_intro_default_locale('en'),
            'pt' => rs_intro_default_locale('pt'),
        ],
    ];
}

function rs_intro_i18n_normalize(array $raw): array {
    $data = rs_intro_i18n_default();
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $has_balloons_key = array_key_exists('balloons', $loc);
        $data['locales'][$locale] = [
            'content'  => wp_kses_post((string) ($loc['content'] ?? $loc['headline'] ?? '')),
            'excerpt'  => wp_kses_post((string) ($loc['excerpt'] ?? $loc['body'] ?? '')),
            'balloons' => rs_intro_normalize_balloons(
                $loc['balloons'] ?? null,
                $locale,
                !$has_balloons_key
            ),
        ];
    }
    return $data;
}

function rs_intro_locale_from_legacy_post(int $post_id, string $locale = 'en'): array {
    $post = $post_id > 0 ? get_post($post_id) : null;
    if (!$post) {
        return rs_intro_default_locale($locale);
    }

    return [
        'content'  => (string) $post->post_content,
        'excerpt'  => (string) $post->post_excerpt,
        'balloons' => rs_intro_default_balloons($locale),
    ];
}

function rs_intro_i18n_get(int $post_id): array {
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $raw = function_exists('rs_section_i18n_get_raw')
        ? rs_section_i18n_get_raw($post_id, RS_INTRO_I18N_KEY)
        : get_post_meta($post_id, RS_INTRO_I18N_KEY, true);
    if (is_array($raw)) {
        return rs_intro_i18n_normalize($raw);
    }
    $data = rs_intro_i18n_default();
    $data['locales']['en'] = rs_intro_locale_from_legacy_post($post_id, 'en');
    $pt_id = (int) get_post_meta($post_id, 'PT', true);
    if ($pt_id > 0) {
        $data['locales']['pt'] = rs_intro_locale_from_legacy_post($pt_id, 'pt');
    }
    return rs_intro_i18n_normalize($data);
}

/**
 * @return array{headline: string, body: string, balloons: list<string>}
 */
function rs_intro_get_fields(int $post_id, string $locale = 'en'): array {
    $locale = function_exists('rs_section_i18n_normalize_locale')
        ? rs_section_i18n_normalize_locale($locale)
        : (strtolower($locale) === 'pt' ? 'pt' : 'en');
    $data = rs_intro_i18n_get($post_id);
    $loc = $data['locales'][$locale] ?? rs_intro_default_locale($locale);
    return [
        'headline' => (string) ($loc['content'] ?? ''),
        'body'     => (string) ($loc['excerpt'] ?? ''),
        'balloons' => rs_intro_normalize_balloons($loc['balloons'] ?? [], $locale, false),
    ];
}

function rs_intro_sync_legacy_post(int $post_id, array $data): void {
    global $wpdb;
    $en = $data['locales']['en'] ?? rs_intro_default_locale('en');
    $content = (string) ($en['content'] ?? '');
    $excerpt = (string) ($en['excerpt'] ?? '');
    $post = get_post($post_id);
    if (!$post || ($post->post_content === $content && $post->post_excerpt === $excerpt)) {
        return;
    }
    $wpdb->update(
        $wpdb->posts,
        ['post_content' => $content, 'post_excerpt' => $excerpt],
        ['ID' => $post_id],
        ['%s', '%s'],
        ['%d']
    );
    clean_post_cache($post_id);
}

function rs_intro_save_fields(int $post_id, string $headline, string $body): void {
    $data = rs_intro_i18n_get($post_id);
    $data['locales']['en']['content'] = $headline;
    $data['locales']['en']['excerpt'] = $body;
    $data = rs_intro_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_INTRO_I18N_KEY, $data);
    } else {
        update_post_meta($post_id, RS_INTRO_I18N_KEY, $data);
    }
    rs_intro_sync_legacy_post($post_id, $data);
}

function rs_intro_meta_to_payload(int $post_id, string $locale = 'en'): array {
    return rs_intro_get_fields($post_id, $locale);
}

function rs_intro_resolve_post_id(int $post_id): int {
    return function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
}

function rs_intro_get_post_id_by_locale(string $locale = 'en'): int {
    return function_exists('rs_section_i18n_canonical_id') ? rs_section_i18n_canonical_id('intro') : 0;
}

function rs_intro_migrate_to_i18n_once(): void {
    if (!function_exists('rs_section_i18n_migrate_twins')) {
        return;
    }
    $id = rs_section_i18n_migrate_twins(
        'intro', RS_INTRO_I18N_KEY, 'rs_intro_i18n_migrated_v1', 'Intro',
        static fn(int $post_id, string $locale): array => rs_intro_locale_from_legacy_post($post_id, $locale),
        'rs_intro_i18n_normalize'
    );
    if ($id > 0) {
        rs_intro_sync_legacy_post($id, rs_intro_i18n_get($id));
    }
}

add_action('init', function () {
    register_post_meta('intro', RS_INTRO_I18N_KEY, [
        'single' => true,
        'type' => 'array',
        'show_in_rest' => false,
        'auth_callback' => static fn(): bool => current_user_can('edit_posts'),
    ]);
}, 20);
add_action('init', 'rs_intro_migrate_to_i18n_once', 30);

add_action('rest_api_init', function () {
    register_rest_field('intro', 'intro_data', [
        'get_callback' => function (array $post, $attr, $request) {
            $locale = function_exists('rs_section_i18n_locale_from_request')
                ? rs_section_i18n_locale_from_request($request)
                : 'en';
            return rs_intro_meta_to_payload((int) $post['id'], $locale);
        },
        'schema' => [
            'description' => 'Conteúdo estruturado da intro (headline + body + balloons)',
            'type' => 'object',
            'context' => ['view', 'edit'],
        ],
    ]);
});

add_action('init', function () {
    remove_post_type_support('intro', 'editor');
    remove_post_type_support('intro', 'excerpt');
}, 100);

add_action('add_meta_boxes_intro', function () {
    add_meta_box('rs_intro_fields', 'Conteúdo da Intro (home)', 'rs_intro_render_meta_box', 'intro', 'normal', 'high');
    remove_meta_box('postexcerpt', 'intro', 'normal');
    remove_meta_box('postexcerpt', 'intro', 'side');
}, 10);

/**
 * @param list<string> $balloons
 */
function rs_intro_render_balloon_chip(string $locale, string $label): void {
    $prefix = 'rs_intro_i18n_input[' . $locale . '][balloons][]';
    echo '<div class="rs-intro-balloon-chip" title="Arraste para reordenar · duplo clique para editar">';
    echo '<button type="button" class="rs-intro-balloon-remove" aria-label="Remover">×</button>';
    echo '<span class="rs-intro-balloon-label" contenteditable="true" spellcheck="false">' . esc_html($label) . '</span>';
    echo '<input type="hidden" class="rs-intro-balloon-value" name="' . esc_attr($prefix) . '" value="' . esc_attr($label) . '" />';
    echo '</div>';
}

/**
 * @param list<string> $balloons
 */
function rs_intro_render_balloons_fields(string $locale, array $balloons): void {
    $list_id = 'rs-intro-balloons-' . $locale;
    $board_id = 'rs-intro-balloons-board-' . $locale;

    rs_ds_fieldset_open('Balões (pills)');
    rs_ds_help('Arraste para reordenar · × remove · duplo clique edita o texto · Add no final.');

    echo '<div class="rs-intro-balloons-board" id="' . esc_attr($board_id) . '" data-locale="' . esc_attr($locale) . '">';
    echo '<input type="hidden" name="rs_intro_i18n_input[' . esc_attr($locale) . '][balloons_set]" value="1" />';
    echo '<div class="rs-intro-balloons" id="' . esc_attr($list_id) . '" data-locale="' . esc_attr($locale) . '">';

    foreach ($balloons as $label) {
        if (trim($label) === '') {
            continue;
        }
        rs_intro_render_balloon_chip($locale, $label);
    }

    echo '</div>';

    echo '<div class="rs-intro-balloons-composer">';
    echo '<input type="text" class="rs-ds-input rs-intro-balloon-composer-input" placeholder="Digite e pressione Enter…" autocomplete="off" />';
    echo '</div>';

    echo '<div class="rs-intro-balloons-footer">';
    echo '<button type="button" class="rs-intro-balloon-clear rs-ds-btn rs-ds-btn--ghost">Clear All</button>';
    echo '<button type="button" class="rs-intro-balloon-add rs-ds-btn rs-ds-btn--primary" data-locale="' . esc_attr($locale) . '">Add</button>';
    echo '</div>';
    echo '</div>';

    rs_ds_fieldset_close();
}

function rs_intro_render_locale_fields(string $locale, array $loc): void {
    $prefix = 'rs_intro_i18n_input[' . $locale . ']';
    $balloons = rs_intro_normalize_balloons($loc['balloons'] ?? [], $locale, false);

    rs_ds_fieldset_open('Título grande (headline)');
    rs_render_rich_text_field(
        'rs_intro_content_' . $locale,
        $prefix . '[content]',
        (string) ($loc['content'] ?? ''),
        'compact'
    );
    rs_ds_help('Coluna esquerda da home. Use negrito para sublinhar trechos.');
    rs_ds_fieldset_close();

    rs_ds_fieldset_open('Parágrafo (body)');
    rs_render_rich_text_field(
        'rs_intro_excerpt_' . $locale,
        $prefix . '[excerpt]',
        (string) ($loc['excerpt'] ?? ''),
        'paragraph'
    );
    rs_ds_help('Coluna direita da home.');
    rs_ds_fieldset_close();

    rs_intro_render_balloons_fields($locale, $balloons);
}

function rs_intro_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_intro_save', 'rs_intro_nonce');
    $id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id((int) $post->ID) : (int) $post->ID;
    $data = rs_intro_i18n_get($id);

    echo '<div class="rs-ds-editor rs-intro-editor">';
    rs_ds_alert('Um único post. Edite English e Português nas abas. Headline (esq.) + body (dir.) + balões.', 'info');

    rs_ds_locale_tabs_open(['en' => 'English', 'pt' => 'Português'], 'en');

    rs_ds_locale_panel_open('en', true);
    rs_intro_render_locale_fields('en', $data['locales']['en']);
    rs_ds_locale_panel_close();

    rs_ds_locale_panel_open('pt', false);
    rs_intro_render_locale_fields('pt', $data['locales']['pt']);
    rs_ds_locale_panel_close();

    rs_ds_locale_tabs_close();
    echo '</div>';
}

add_action('save_post_intro', function (int $post_id) {
    if (!isset($_POST['rs_intro_nonce']) || !wp_verify_nonce($_POST['rs_intro_nonce'], 'rs_intro_save')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)
        || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $post_id = function_exists('rs_section_i18n_resolve_id') ? rs_section_i18n_resolve_id($post_id) : $post_id;
    $data = rs_intro_i18n_get($post_id);
    $raw = isset($_POST['rs_intro_i18n_input']) && is_array($_POST['rs_intro_i18n_input'])
        ? wp_unslash($_POST['rs_intro_i18n_input'])
        : [];
    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw[$locale] ?? null) ? $raw[$locale] : [];
        $data['locales'][$locale] = [
            'content'  => wp_kses_post((string) ($loc['content'] ?? '')),
            'excerpt'  => wp_kses_post((string) ($loc['excerpt'] ?? '')),
            // balloons_set marca que o editor enviou a lista (mesmo vazia após Clear All).
            'balloons' => !empty($loc['balloons_set']) || array_key_exists('balloons', $loc)
                ? rs_intro_normalize_balloons($loc['balloons'] ?? [], $locale, false)
                : rs_intro_normalize_balloons($data['locales'][$locale]['balloons'] ?? [], $locale, false),
        ];
    }
    // Marca balloons explicitamente no normalize (já estão nas locales).
    $data = rs_intro_i18n_normalize($data);
    if (function_exists('rs_section_i18n_save')) {
        rs_section_i18n_save($post_id, RS_INTRO_I18N_KEY, $data);
    } else {
        update_post_meta($post_id, RS_INTRO_I18N_KEY, $data);
    }
    rs_intro_sync_legacy_post($post_id, $data);
}, 10);

function rs_copy_intro_fields(int $from_id, int $to_id): void {
    // Legado no-op: post único.
}

add_action('admin_footer-post.php', 'rs_intro_render_admin_footer_script');
add_action('admin_footer-post-new.php', 'rs_intro_render_admin_footer_script');

function rs_intro_render_admin_footer_script(): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'intro') {
        return;
    }
    ?>
    <script>
    jQuery(function ($) {
        function makeChip(locale, label) {
            var name = 'rs_intro_i18n_input[' + locale + '][balloons][]';
            var $chip = $('<div class="rs-intro-balloon-chip" title="Arraste para reordenar · duplo clique para editar"></div>');
            var $remove = $('<button type="button" class="rs-intro-balloon-remove" aria-label="Remover">×</button>');
            var $label = $('<span class="rs-intro-balloon-label" contenteditable="true" spellcheck="false"></span>').text(label);
            var $value = $('<input type="hidden" class="rs-intro-balloon-value" />').attr('name', name).val(label);
            return $chip.append($remove, $label, $value);
        }

        function list(locale) {
            return $('#rs-intro-balloons-' + locale);
        }

        function board(locale) {
            return $('#rs-intro-balloons-board-' + locale);
        }

        function syncLabel($chip) {
            var text = ($chip.find('.rs-intro-balloon-label').text() || '').trim();
            $chip.find('.rs-intro-balloon-value').val(text);
            return text;
        }

        function initSortable(locale) {
            var $list = list(locale);
            if (!$list.length || !$.fn.sortable) {
                return;
            }
            if ($list.hasClass('ui-sortable')) {
                $list.sortable('refresh');
                return;
            }
            $list.sortable({
                items: '.rs-intro-balloon-chip',
                tolerance: 'pointer',
                placeholder: 'rs-intro-balloon-placeholder',
                forcePlaceholderSize: true,
                opacity: 0.92,
                cancel: 'input,textarea,button,[contenteditable="true"]',
            });
        }

        function addChip(locale, label) {
            label = (label || '').trim();
            if (!label) {
                return false;
            }
            list(locale).append(makeChip(locale, label));
            initSortable(locale);
            return true;
        }

        function commitComposer(locale) {
            var $input = board(locale).find('.rs-intro-balloon-composer-input');
            var value = ($input.val() || '').trim();
            if (!value) {
                $input.trigger('focus');
                return;
            }
            addChip(locale, value);
            $input.val('').trigger('focus');
        }

        ['en', 'pt'].forEach(initSortable);

        $(document).on('click', '.rs-intro-balloon-add', function (e) {
            e.preventDefault();
            commitComposer(String($(this).data('locale') || 'en'));
        });

        $(document).on('keydown', '.rs-intro-balloon-composer-input', function (e) {
            if (e.key !== 'Enter') {
                return;
            }
            e.preventDefault();
            var locale = String($(this).closest('.rs-intro-balloons-board').data('locale') || 'en');
            commitComposer(locale);
        });

        $(document).on('click', '.rs-intro-balloon-clear', function (e) {
            e.preventDefault();
            var $board = $(this).closest('.rs-intro-balloons-board');
            var locale = String($board.data('locale') || 'en');
            if (!$board.find('.rs-intro-balloon-chip').length) {
                return;
            }
            if (!window.confirm('Remover todos os balões deste idioma?')) {
                return;
            }
            list(locale).empty();
            initSortable(locale);
        });

        $(document).on('click', '.rs-intro-balloon-remove', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $list = $(this).closest('.rs-intro-balloons');
            var locale = String($list.data('locale') || 'en');
            $(this).closest('.rs-intro-balloon-chip').remove();
            initSortable(locale);
        });

        $(document).on('input blur', '.rs-intro-balloon-label', function () {
            var $chip = $(this).closest('.rs-intro-balloon-chip');
            var text = syncLabel($chip);
            if (!text) {
                $chip.remove();
            }
        });

        $(document).on('keydown', '.rs-intro-balloon-label', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).trigger('blur');
            }
        });
    });
    </script>
    <?php
}
