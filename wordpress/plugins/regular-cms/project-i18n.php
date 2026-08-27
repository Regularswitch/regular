<?php
/**
 * Projetos bilíngues em um único post (EN + PT + mídia compartilhada).
 */

if (defined('RS_PROJECT_I18N_LOADED')) {
    return;
}
define('RS_PROJECT_I18N_LOADED', true);

const RS_PROJECT_I18N_KEY = 'rs_project_i18n';
const RS_PROJECT_I18N_VERSION = 1;

/**
 * @return array<string, mixed>
 */
function rs_project_i18n_default_locale(): array {
    return [
        'title'     => '',
        'excerpt'   => '',
        'accordion' => [],
        'youtube'   => [],
    ];
}

/**
 * @return array<string, mixed>
 */
function rs_project_i18n_default(): array {
    return [
        'v'       => RS_PROJECT_I18N_VERSION,
        'shared'  => [
            'hero_id'              => 0,
            'logo_id'              => 0,
            'gallery_ids'          => '',
            'gallery_featured_ids' => '',
            'featured_home'        => false,
            'show_vignette'        => true,
        ],
        'locales' => [
            'en' => rs_project_i18n_default_locale(),
            'pt' => rs_project_i18n_default_locale(),
        ],
    ];
}

function rs_project_i18n_normalize_locale_key(string $locale): string {
    return strtolower($locale) === 'pt' ? 'pt' : 'en';
}

/**
 * @param array<string, mixed> $raw
 * @return array<string, mixed>
 */
function rs_project_i18n_normalize(array $raw): array {
    $base = rs_project_i18n_default();
    $shared = is_array($raw['shared'] ?? null) ? $raw['shared'] : [];

    $base['shared']['hero_id'] = (int) ($shared['hero_id'] ?? 0);
    $base['shared']['logo_id'] = (int) ($shared['logo_id'] ?? 0);
    $base['shared']['gallery_ids'] = trim((string) ($shared['gallery_ids'] ?? ''));
    $base['shared']['gallery_featured_ids'] = trim((string) ($shared['gallery_featured_ids'] ?? ''));
    $base['shared']['featured_home'] = !empty($shared['featured_home']);
    $base['shared']['show_vignette'] = !array_key_exists('show_vignette', $shared) || !empty($shared['show_vignette']);

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($raw['locales'][$locale] ?? null) ? $raw['locales'][$locale] : [];
        $base['locales'][$locale]['title'] = trim((string) ($loc['title'] ?? ''));
        $base['locales'][$locale]['excerpt'] = trim((string) ($loc['excerpt'] ?? ''));
        $base['locales'][$locale]['accordion'] = rs_project_normalize_accordion_sections(
            is_array($loc['accordion'] ?? null) ? $loc['accordion'] : []
        );
        $base['locales'][$locale]['youtube'] = rs_project_normalize_youtube_videos(
            is_array($loc['youtube'] ?? null) ? $loc['youtube'] : []
        );
    }

    return $base;
}

function rs_project_i18n_is_migrated(int $post_id): bool {
    $raw = get_post_meta($post_id, RS_PROJECT_I18N_KEY, true);
    if (is_array($raw) && $raw !== []) {
        return true;
    }

    return is_string($raw) && $raw !== '';
}

/**
 * Decodifica meta antiga em JSON (com recuperação de stripslashes).
 *
 * @return array<string, mixed>|null
 */
function rs_project_i18n_decode_json_string(string $raw): ?array {
    $candidates = array_unique([$raw, wp_unslash($raw), stripslashes($raw)]);

    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || $candidate === '') {
            continue;
        }
        $decoded = json_decode($candidate, true);
        if (is_array($decoded) && isset($decoded['locales']) && is_array($decoded['locales'])) {
            return $decoded;
        }
    }

    // Meta corrompida por update_post_meta → wp_unslash no JSON (\" → ", \n → n).
    $repaired = rs_project_i18n_repair_stripslashed_json($raw);
    if (is_array($repaired)) {
        return $repaired;
    }

    return null;
}

/**
 * Tenta recuperar JSON gravado sem wp_slash (aspas de atributos HTML quebram o decode).
 *
 * @return array<string, mixed>|null
 */
function rs_project_i18n_repair_stripslashed_json(string $raw): ?array {
    if ($raw === '' || ($raw[0] !== '{' && $raw[0] !== '[')) {
        return null;
    }

    $fixed = $raw;

    // Aspas de atributos HTML comuns no corpo do TinyMCE/ProseMirror.
    $attr_fixes = [
        '/\bdata-pm-slice="([^"]*)"/' => 'data-pm-slice=\"$1\"',
        '/\sclass="([^"]*)"/'         => ' class=\"$1\"',
        '/\sid="([^"]*)"/'            => ' id=\"$1\"',
        '/\shref="([^"]*)"/'          => ' href=\"$1\"',
        '/\ssrc="([^"]*)"/'           => ' src=\"$1\"',
        '/\starget="([^"]*)"/'        => ' target=\"$1\"',
        '/\srel="([^"]*)"/'           => ' rel=\"$1\"',
        '/\sstyle="([^"]*)"/'         => ' style=\"$1\"',
        '/\stitle="([^"]*)"/'         => ' title=\"$1\"',
        '/\salt="([^"]*)"/'           => ' alt=\"$1\"',
        '/\swidth="([^"]*)"/'         => ' width=\"$1\"',
        '/\sheight="([^"]*)"/'        => ' height=\"$1\"',
    ];
    foreach ($attr_fixes as $pattern => $replacement) {
        $next = preg_replace($pattern, $replacement, $fixed);
        if (is_string($next)) {
            $fixed = $next;
        }
    }

    // \n viraram literal "n" entre tags.
    $fixed = str_replace(
        ['</p>n<p>', '</p>n</', '>n<p>', '</h1>n', '</h2>n', '</h3>n', '</li>n', '</ul>n', '</ol>n'],
        ['</p>\n<p>', '</p>\n</', '>\n<p>', '</h1>\n', '</h2>\n', '</h3>\n', '</li>\n', '</ul>\n', '</ol>\n'],
        $fixed
    );

    $decoded = json_decode($fixed, true);
    if (is_array($decoded) && isset($decoded['locales']) && is_array($decoded['locales'])) {
        return $decoded;
    }

    return null;
}

/**
 * @return array<string, mixed>
 */
function rs_project_i18n_get(int $post_id): array {
    $raw = get_post_meta($post_id, RS_PROJECT_I18N_KEY, true);

    // Formato novo: array serializado pelo WordPress (evita corrupção de JSON).
    if (is_array($raw)) {
        return rs_project_i18n_normalize($raw);
    }

    // Formato legado: string JSON.
    if (is_string($raw) && $raw !== '') {
        $decoded = rs_project_i18n_decode_json_string($raw);
        if (is_array($decoded)) {
            return rs_project_i18n_normalize($decoded);
        }
    }

    return rs_project_i18n_from_legacy_post($post_id);
}

/**
 * Se a meta ainda for JSON (possivelmente corrompido e recuperado), regrava como array.
 * Nunca sobrescreve mídia compartilhada vazia se o legado ainda tiver hero/galeria.
 */
function rs_project_i18n_maybe_migrate_storage(int $post_id): void {
    $raw = get_post_meta($post_id, RS_PROJECT_I18N_KEY, true);
    if (!is_string($raw) || $raw === '') {
        return;
    }

    $decoded = rs_project_i18n_decode_json_string($raw);
    if (!is_array($decoded)) {
        return;
    }

    $data = rs_project_i18n_normalize($decoded);
    $legacy_hero = function_exists('rs_project_get_hero_id') ? rs_project_get_hero_id($post_id) : 0;
    $legacy_gallery = function_exists('rs_project_get_gallery_ids')
        ? implode(',', rs_project_get_gallery_ids($post_id))
        : '';
    $legacy_logo = (int) get_post_meta($post_id, defined('RS_PROJECT_LOGO_KEY') ? RS_PROJECT_LOGO_KEY : 'rs_project_logo_id', true);

    if ((int) ($data['shared']['hero_id'] ?? 0) <= 0 && $legacy_hero > 0) {
        $data['shared']['hero_id'] = $legacy_hero;
    }
    if (trim((string) ($data['shared']['gallery_ids'] ?? '')) === '' && $legacy_gallery !== '') {
        $data['shared']['gallery_ids'] = $legacy_gallery;
    }
    if ((int) ($data['shared']['logo_id'] ?? 0) <= 0 && $legacy_logo > 0) {
        $data['shared']['logo_id'] = $legacy_logo;
    }

    rs_project_i18n_save($post_id, $data);
}

/**
 * Monta i18n a partir das meta keys legadas (pré-migração ou leitura temporária).
 *
 * @return array<string, mixed>
 */
function rs_project_i18n_from_legacy_post(int $post_id, int $pt_id = 0): array {
    $data = rs_project_i18n_default();
    $post = get_post($post_id);
    if (!$post) {
        return $data;
    }

    if ($pt_id <= 0) {
        $pt_id = (int) get_post_meta($post_id, 'PT', true);
    }

    $data['shared']['hero_id'] = rs_project_get_hero_id($post_id);
    $data['shared']['logo_id'] = (int) get_post_meta($post_id, RS_PROJECT_LOGO_KEY, true);
    $data['shared']['gallery_ids'] = implode(',', rs_project_get_gallery_ids($post_id));
    $data['shared']['gallery_featured_ids'] = implode(',', rs_project_get_gallery_featured_ids($post_id));
    $data['shared']['featured_home'] = (bool) get_post_meta($post_id, RS_PROJECT_FEATURED_KEY, true);
    $vignette = get_post_meta($post_id, RS_PROJECT_VIGNETTE_KEY, true);
    $data['shared']['show_vignette'] = $vignette === '' ? true : (bool) $vignette;

    $data['locales']['en']['title'] = (string) $post->post_title;
    $data['locales']['en']['excerpt'] = (string) $post->post_excerpt;
    $data['locales']['en']['accordion'] = rs_project_get_accordion_sections($post_id);
    $data['locales']['en']['youtube'] = rs_project_get_youtube_videos($post_id);

    if ($pt_id > 0) {
        $pt_post = get_post($pt_id);
        if ($pt_post) {
            $data['locales']['pt']['title'] = (string) $pt_post->post_title;
            $data['locales']['pt']['excerpt'] = (string) $pt_post->post_excerpt;
            $data['locales']['pt']['accordion'] = rs_project_get_accordion_sections($pt_id);
            $data['locales']['pt']['youtube'] = rs_project_get_youtube_videos($pt_id);
        }
    }

    return rs_project_i18n_normalize($data);
}

/**
 * True se shared/acordeão/youtube estão vazios (possível wipe acidental).
 *
 * @param array<string, mixed> $data
 */
function rs_project_i18n_is_effectively_empty(array $data): bool {
    $shared = is_array($data['shared'] ?? null) ? $data['shared'] : [];
    if ((int) ($shared['hero_id'] ?? 0) > 0) {
        return false;
    }
    if ((int) ($shared['logo_id'] ?? 0) > 0) {
        return false;
    }
    if (trim((string) ($shared['gallery_ids'] ?? '')) !== '') {
        return false;
    }

    foreach (['en', 'pt'] as $locale) {
        $loc = is_array($data['locales'][$locale] ?? null) ? $data['locales'][$locale] : [];
        if (!empty($loc['accordion']) && is_array($loc['accordion'])) {
            return false;
        }
        if (!empty($loc['youtube']) && is_array($loc['youtube'])) {
            return false;
        }
    }

    return true;
}

/**
 * Evita sobrescrever conteúdo rico com payload vazio (POST truncado / delete / autosave).
 *
 * @param array<string, mixed> $incoming
 * @param array<string, mixed> $previous
 * @return array<string, mixed>
 */
function rs_project_i18n_guard_against_wipe(array $incoming, array $previous): array {
    if (rs_project_i18n_is_effectively_empty($previous)) {
        return $incoming;
    }
    if (!rs_project_i18n_is_effectively_empty($incoming)) {
        return $incoming;
    }

    $explicit_clear = !empty($_POST['rs_project_hero_id_cleared'])
        || !empty($_POST['rs_project_logo_id_cleared'])
        || !empty($_POST['rs_project_gallery_cleared'])
        || !empty($_POST['rs_project_accordion_en_cleared'])
        || !empty($_POST['rs_project_accordion_pt_cleared']);

    if ($explicit_clear) {
        return $incoming;
    }

    // Mantém mídia + acordeão anteriores; atualiza só flags/títulos do incoming.
    $incoming['shared']['hero_id'] = (int) ($previous['shared']['hero_id'] ?? 0);
    $incoming['shared']['logo_id'] = (int) ($previous['shared']['logo_id'] ?? 0);
    $incoming['shared']['gallery_ids'] = (string) ($previous['shared']['gallery_ids'] ?? '');
    $incoming['shared']['gallery_featured_ids'] = (string) ($previous['shared']['gallery_featured_ids'] ?? '');
    $incoming['locales']['en']['accordion'] = $previous['locales']['en']['accordion'] ?? [];
    $incoming['locales']['pt']['accordion'] = $previous['locales']['pt']['accordion'] ?? [];
    $incoming['locales']['en']['youtube'] = $previous['locales']['en']['youtube'] ?? [];
    $incoming['locales']['pt']['youtube'] = $previous['locales']['pt']['youtube'] ?? [];

    return $incoming;
}

/**
 * @param array<string, mixed> $data
 */
function rs_project_i18n_save(int $post_id, array $data): void {
    $previous = rs_project_i18n_get($post_id);
    $normalized = rs_project_i18n_normalize(
        rs_project_i18n_guard_against_wipe(rs_project_i18n_normalize($data), $previous)
    );

    // Array nativo + dedupe (evita linhas duplicadas de rs_project_i18n).
    if (function_exists('rs_meta_update_array')) {
        rs_meta_update_array($post_id, RS_PROJECT_I18N_KEY, $normalized);
    } else {
        update_post_meta($post_id, RS_PROJECT_I18N_KEY, $normalized);
    }

    // Se a meta sumiu (Hostinger / object cache), força rewrite.
    if (!metadata_exists('post', $post_id, RS_PROJECT_I18N_KEY)) {
        add_post_meta($post_id, RS_PROJECT_I18N_KEY, $normalized, true);
    }

    rs_project_i18n_sync_legacy_meta($post_id, $normalized);
}

/**
 * Garante no máximo um projeto com "Destaque na home".
 * Atualiza meta legada + blob i18n sem reentrar no save completo.
 */
function rs_project_clear_other_featured_home(int $keep_post_id): void {
    static $running = false;
    if ($running || $keep_post_id <= 0) {
        return;
    }
    $running = true;

    // Varre todos os projetos: meta legada e blob i18n podem divergir.
    $other_ids = get_posts([
        'post_type'              => 'project',
        'post_status'            => 'any',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'post__not_in'           => [$keep_post_id],
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ]);

    foreach ($other_ids as $other_id) {
        $other_id = (int) $other_id;
        if ($other_id <= 0) {
            continue;
        }

        $legacy_featured = (bool) get_post_meta($other_id, RS_PROJECT_FEATURED_KEY, true);
        $data = rs_project_i18n_get($other_id);
        $i18n_featured = !empty($data['shared']['featured_home']);

        if (!$legacy_featured && !$i18n_featured) {
            continue;
        }

        update_post_meta($other_id, RS_PROJECT_FEATURED_KEY, 0);

        if ($i18n_featured) {
            $data['shared']['featured_home'] = false;
            $normalized = rs_project_i18n_normalize($data);
            if (function_exists('rs_meta_update_array')) {
                rs_meta_update_array($other_id, RS_PROJECT_I18N_KEY, $normalized);
            } else {
                update_post_meta($other_id, RS_PROJECT_I18N_KEY, $normalized);
            }
        }
    }

    $running = false;
}

/**
 * Mantém meta legadas alinhadas ao EN + mídia compartilhada (api-etc / fallbacks).
 *
 * @param array<string, mixed> $data
 */
function rs_project_i18n_sync_legacy_meta(int $post_id, array $data): void {
    $shared = $data['shared'] ?? [];
    $en = $data['locales']['en'] ?? rs_project_i18n_default_locale();

    $hero_id = (int) ($shared['hero_id'] ?? 0);
    if ($hero_id > 0) {
        update_post_meta($post_id, RS_PROJECT_HERO_KEY, $hero_id);
        update_post_meta($post_id, 'etc_upload_image', $hero_id);
    } else {
        delete_post_meta($post_id, RS_PROJECT_HERO_KEY);
        delete_post_meta($post_id, 'etc_upload_image');
    }

    $logo_id = (int) ($shared['logo_id'] ?? 0);
    if ($logo_id > 0) {
        update_post_meta($post_id, RS_PROJECT_LOGO_KEY, $logo_id);
        // Não chama set_post_thumbnail: a imagem destacada é só para home/listagem.
    } else {
        delete_post_meta($post_id, RS_PROJECT_LOGO_KEY);
    }

    update_post_meta($post_id, RS_PROJECT_GALLERY_KEY, (string) ($shared['gallery_ids'] ?? ''));
    update_post_meta($post_id, RS_PROJECT_GALLERY_FEATURED_KEY, (string) ($shared['gallery_featured_ids'] ?? ''));
    $is_featured_home = !empty($shared['featured_home']);
    update_post_meta($post_id, RS_PROJECT_FEATURED_KEY, $is_featured_home ? 1 : 0);
    update_post_meta($post_id, RS_PROJECT_VIGNETTE_KEY, !empty($shared['show_vignette']) ? 1 : 0);

    if ($is_featured_home) {
        rs_project_clear_other_featured_home($post_id);
    }

    $accordion = is_array($en['accordion'] ?? null) ? $en['accordion'] : [];
    // wp_slash: update_post_meta faz wp_unslash e corromperia o JSON do accordion.
    update_post_meta($post_id, RS_PROJECT_ACCORDION_KEY, wp_slash(wp_json_encode($accordion, JSON_UNESCAPED_UNICODE) ?: '[]'));
    update_post_meta($post_id, RS_PROJECT_YOUTUBE_KEY, wp_slash(wp_json_encode($en['youtube'] ?? [], JSON_UNESCAPED_SLASHES) ?: '[]'));

    foreach (array_keys(RS_PROJECT_LEGACY_ACCORDION_LABELS) as $index) {
        $legacy_body = $accordion[$index - 1]['body'] ?? '';
        update_post_meta($post_id, "rs_project_acc_{$index}_body", $legacy_body);
    }
}

/**
 * @return array{title: string, excerpt: string}
 */
function rs_project_i18n_get_locale_text(int $post_id, string $locale): array {
    $locale = rs_project_i18n_normalize_locale_key($locale);
    $data = rs_project_i18n_get($post_id);
    $post = get_post($post_id);
    $loc = $data['locales'][$locale] ?? rs_project_i18n_default_locale();

    $title = trim((string) ($loc['title'] ?? ''));
    $excerpt = trim((string) ($loc['excerpt'] ?? ''));

    if ($locale === 'en' && $post) {
        if ($title === '') {
            $title = (string) $post->post_title;
        }
        if ($excerpt === '') {
            $excerpt = (string) $post->post_excerpt;
        }
    }

    return [
        'title'   => $title,
        'excerpt' => $excerpt,
    ];
}

function rs_project_meta_to_payload_for_locale(int $post_id, string $locale = 'en'): array {
    $locale = rs_project_i18n_normalize_locale_key($locale);
    $data = rs_project_i18n_get($post_id);
    $shared = $data['shared'];
    $loc = $data['locales'][$locale] ?? rs_project_i18n_default_locale();

    $accordion = [];
    $index = 1;
    foreach ($loc['accordion'] as $section) {
        $body = trim((string) ($section['body'] ?? ''));
        if ($body === '') {
            continue;
        }
        $accordion[] = [
            'index' => $index,
            'title' => trim((string) ($section['title'] ?? '')),
            'body'  => wpautop($body),
        ];
        $index += 1;
    }

    // Fallback EN → PT para campos textuais vazios.
    if ($locale === 'pt' && $accordion === []) {
        $en_loc = $data['locales']['en'] ?? rs_project_i18n_default_locale();
        foreach ($en_loc['accordion'] as $section) {
            $body = trim((string) ($section['body'] ?? ''));
            if ($body === '') {
                continue;
            }
            $accordion[] = [
                'index' => $index,
                'title' => trim((string) ($section['title'] ?? '')),
                'body'  => wpautop($body),
            ];
            $index += 1;
        }
    }

    $gallery_ids = rs_project_parse_csv_ids((string) ($shared['gallery_ids'] ?? ''));
    $featured_ids = array_flip(rs_project_parse_csv_ids((string) ($shared['gallery_featured_ids'] ?? '')));
    $gallery = [];
    foreach ($gallery_ids as $attachment_id) {
        $info = rs_project_attachment_info($attachment_id);
        if ($info && !empty($info['url'])) {
            $info['featured'] = isset($featured_ids[$attachment_id]);
            $gallery[] = $info;
        }
    }

    $youtube = $loc['youtube'] ?? [];
    if ($locale === 'pt' && $youtube === []) {
        $youtube = $data['locales']['en']['youtube'] ?? [];
    }

    $hero_id = (int) ($shared['hero_id'] ?? 0);
    $logo_id = (int) ($shared['logo_id'] ?? 0);
    $thumb_id = (int) get_post_thumbnail_id($post_id);

    return [
        'heroImage'      => rs_project_attachment_info($hero_id > 0 ? $hero_id : rs_project_get_hero_id($post_id)),
        'logoImage'      => rs_project_attachment_info($logo_id > 0 ? $logo_id : (int) get_post_meta($post_id, RS_PROJECT_LOGO_KEY, true)),
        // Imagem destacada (home/listagem) — URL direta, sem depender do embed /media.
        'featuredImage'  => rs_project_attachment_info($thumb_id),
        'accordion'      => $accordion,
        'gallery'        => $gallery,
        'youtubeVideos'  => $youtube,
        'featuredOnHome' => !empty($shared['featured_home']),
        'showVignette'   => !array_key_exists('show_vignette', $shared) || !empty($shared['show_vignette']),
    ];
}

/**
 * Resolve o ID canônico (post único) a partir de qualquer ID legado EN ou PT twin.
 */
function rs_project_resolve_canonical_id(int $post_id): int {
    if ($post_id <= 0) {
        return 0;
    }

    $en_id = (int) get_post_meta($post_id, 'EN', true);
    if ($en_id > 0) {
        return $en_id;
    }

    return $post_id;
}

function rs_project_is_legacy_twin(int $post_id): bool {
    return (int) get_post_meta($post_id, 'EN', true) > 0;
}

/**
 * Migra pares EN/PT legados para rs_project_i18n e remove gêmeos PT.
 */
function rs_project_migrate_to_i18n_once(): void {
    if (get_option('rs_project_i18n_migrated_v1')) {
        return;
    }

    $processed = [];

    $canonical_ids = get_posts([
        'post_type'      => 'project',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'EN',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);

    foreach ($canonical_ids as $raw_id) {
        $en_id = (int) $raw_id;
        if ($en_id <= 0 || isset($processed[$en_id])) {
            continue;
        }

        $pt_id = (int) get_post_meta($en_id, 'PT', true);
        $pt_post = $pt_id > 0 ? get_post($pt_id) : null;
        if (!$pt_post || $pt_post->post_type !== 'project' || $pt_post->post_status === 'trash') {
            $pt_id = 0;
        }

        $i18n = rs_project_i18n_from_legacy_post($en_id, $pt_id);
        rs_project_i18n_save($en_id, $i18n);

        if ($pt_id > 0) {
            delete_post_meta($en_id, 'PT');
            delete_post_meta($pt_id, 'EN');
            wp_trash_post($pt_id);
        }

        $processed[$en_id] = true;
    }

    // Gêmeos PT órfãos (EN meta aponta para post inexistente ou já migrado).
    $twins = get_posts([
        'post_type'      => 'project',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'EN',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    foreach ($twins as $raw_id) {
        $pt_id = (int) $raw_id;
        $en_id = (int) get_post_meta($pt_id, 'EN', true);
        if ($en_id > 0 && !rs_project_i18n_is_migrated($en_id)) {
            $i18n = rs_project_i18n_from_legacy_post($en_id, $pt_id);
            rs_project_i18n_save($en_id, $i18n);
            delete_post_meta($en_id, 'PT');
            delete_post_meta($pt_id, 'EN');
        }
        wp_trash_post($pt_id);
    }

    // Normaliza slugs *-pt no canônico.
    $all = get_posts([
        'post_type'      => 'project',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($all as $raw_id) {
        $post_id = (int) $raw_id;
        $post = get_post($post_id);
        if (!$post || !preg_match('/-pt$/i', (string) $post->post_name)) {
            continue;
        }
        $new_slug = preg_replace('/-pt$/i', '', (string) $post->post_name);
        if ($new_slug !== '' && $new_slug !== $post->post_name) {
            wp_update_post([
                'ID'        => $post_id,
                'post_name' => sanitize_title($new_slug),
            ]);
        }
    }

    update_option('rs_project_i18n_migrated_v1', 1);
}

add_action('init', 'rs_project_migrate_to_i18n_once', 15);

add_action('init', function () {
    register_post_meta('project', RS_PROJECT_I18N_KEY, [
        'single'        => true,
        'type'          => 'string',
        'show_in_rest'  => false,
        'auth_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
}, 21);

/**
 * Lê JSON de array do POST. String vazia = JS não coletou → não altera.
 *
 * @return array<int|string, mixed>|null
 */
function rs_project_i18n_posted_json_array(string $field_name): ?array {
    if (!array_key_exists($field_name, $_POST)) {
        return null;
    }

    $raw = trim((string) wp_unslash($_POST[$field_name]));
    if ($raw === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return null;
    }

    return $decoded;
}

/**
 * @return array<int, array{title: string, body: string}>
 */
function rs_project_i18n_parse_accordion_json_field(string $field_name): array {
    $decoded = rs_project_i18n_posted_json_array($field_name);
    if ($decoded === null) {
        return [];
    }

    return rs_project_normalize_accordion_sections($decoded);
}

/**
 * @return array<int, array{id: string, url: string}>
 */
function rs_project_i18n_parse_youtube_json_field(string $field_name): array {
    $decoded = rs_project_i18n_posted_json_array($field_name);
    if ($decoded === null) {
        return [];
    }

    return rs_project_normalize_youtube_videos($decoded);
}

/**
 * @return array<string, mixed>
 */
function rs_project_i18n_parse_from_request(int $post_id): array {
    $data = rs_project_i18n_get($post_id);
    $post = get_post($post_id);

    $prev_hero = (int) ($data['shared']['hero_id'] ?? 0);
    $prev_logo = (int) ($data['shared']['logo_id'] ?? 0);
    $prev_gallery = trim((string) ($data['shared']['gallery_ids'] ?? ''));
    $prev_featured_gallery = trim((string) ($data['shared']['gallery_featured_ids'] ?? ''));
    $prev_en_accordion = is_array($data['locales']['en']['accordion'] ?? null) ? $data['locales']['en']['accordion'] : [];
    $prev_pt_accordion = is_array($data['locales']['pt']['accordion'] ?? null) ? $data['locales']['pt']['accordion'] : [];
    $prev_en_youtube = is_array($data['locales']['en']['youtube'] ?? null) ? $data['locales']['en']['youtube'] : [];
    $prev_pt_youtube = is_array($data['locales']['pt']['youtube'] ?? null) ? $data['locales']['pt']['youtube'] : [];

    // Hero/logo: POST=0 sem flag de clear NÃO apaga mídia existente (bug recorrente ao salvar).
    if (array_key_exists('rs_project_hero_id', $_POST)) {
        $posted_hero = (int) $_POST['rs_project_hero_id'];
        $hero_cleared = !empty($_POST['rs_project_hero_id_cleared']);
        if ($posted_hero > 0) {
            $data['shared']['hero_id'] = $posted_hero;
        } elseif ($hero_cleared) {
            $data['shared']['hero_id'] = 0;
        } else {
            $data['shared']['hero_id'] = $prev_hero;
        }
    }

    if (array_key_exists('rs_project_logo_id', $_POST)) {
        $posted_logo = (int) $_POST['rs_project_logo_id'];
        $logo_cleared = !empty($_POST['rs_project_logo_id_cleared']);
        if ($posted_logo > 0) {
            $data['shared']['logo_id'] = $posted_logo;
        } elseif ($logo_cleared) {
            $data['shared']['logo_id'] = 0;
        } else {
            $data['shared']['logo_id'] = $prev_logo;
        }
    }

    $gallery_ids = rs_project_i18n_posted_json_array('rs_project_gallery_json');
    $featured_ids = rs_project_i18n_posted_json_array('rs_project_gallery_featured_json');

    if ($gallery_ids !== null) {
        $next_gallery = implode(',', array_values(array_filter(array_map('intval', $gallery_ids))));
        // [] acidental não apaga galeria existente.
        if ($next_gallery !== '' || $prev_gallery === '' || !empty($_POST['rs_project_gallery_cleared'])) {
            $data['shared']['gallery_ids'] = $next_gallery;
        }
    }
    if ($featured_ids !== null) {
        $next_featured = implode(',', array_values(array_filter(array_map('intval', $featured_ids))));
        if ($next_featured !== '' || $prev_featured_gallery === '' || !empty($_POST['rs_project_gallery_cleared'])) {
            $data['shared']['gallery_featured_ids'] = $next_featured;
        }
    }

    // Checkboxes do form de projeto (sempre no metabox quando o nonce está presente).
    $data['shared']['featured_home'] = !empty($_POST['rs_project_featured_home']);
    $data['shared']['show_vignette'] = !empty($_POST['rs_project_show_vignette']);

    $data['locales']['en']['title'] = $post ? (string) $post->post_title : '';

    if (array_key_exists('excerpt', $_POST)) {
        $data['locales']['en']['excerpt'] = wp_kses_post(wp_unslash((string) $_POST['excerpt']));
    }

    $en_accordion = rs_project_i18n_posted_json_array('rs_project_accordion_en_json');
    if ($en_accordion !== null) {
        $data['locales']['en']['accordion'] = rs_project_i18n_merge_accordion_preserve(
            rs_project_normalize_accordion_sections($en_accordion),
            $prev_en_accordion,
            !empty($_POST['rs_project_accordion_en_cleared'])
        );
    }

    $en_youtube = rs_project_i18n_posted_json_array('rs_project_youtube_en_json');
    if ($en_youtube !== null) {
        $normalized_yt = rs_project_normalize_youtube_videos($en_youtube);
        if ($normalized_yt !== [] || $prev_en_youtube === [] || !empty($_POST['rs_project_youtube_en_cleared'])) {
            $data['locales']['en']['youtube'] = $normalized_yt;
        }
    }

    if (array_key_exists('rs_project_pt_title', $_POST)) {
        $data['locales']['pt']['title'] = sanitize_text_field(wp_unslash((string) $_POST['rs_project_pt_title']));
    }

    if (array_key_exists('rs_project_pt_excerpt', $_POST)) {
        $data['locales']['pt']['excerpt'] = wp_kses_post(wp_unslash((string) $_POST['rs_project_pt_excerpt']));
    }

    $pt_accordion = rs_project_i18n_posted_json_array('rs_project_accordion_pt_json');
    if ($pt_accordion !== null) {
        $data['locales']['pt']['accordion'] = rs_project_i18n_merge_accordion_preserve(
            rs_project_normalize_accordion_sections($pt_accordion),
            $prev_pt_accordion,
            !empty($_POST['rs_project_accordion_pt_cleared'])
        );
    }

    $pt_youtube = rs_project_i18n_posted_json_array('rs_project_youtube_pt_json');
    if ($pt_youtube !== null) {
        $normalized_yt = rs_project_normalize_youtube_videos($pt_youtube);
        if ($normalized_yt !== [] || $prev_pt_youtube === [] || !empty($_POST['rs_project_youtube_pt_cleared'])) {
            $data['locales']['pt']['youtube'] = $normalized_yt;
        }
    }

    return rs_project_i18n_normalize($data);
}

/**
 * Evita apagar acordeão/bodies quando TinyMCE em aba oculta devolve HTML vazio.
 *
 * @param list<array{title?: string, body?: string}> $incoming
 * @param list<array{title?: string, body?: string}> $previous
 * @return list<array{title?: string, body?: string}>
 */
function rs_project_i18n_merge_accordion_preserve(array $incoming, array $previous, bool $cleared): array {
    if ($cleared) {
        return $incoming;
    }
    if ($incoming === [] && $previous !== []) {
        return $previous;
    }

    foreach ($incoming as $i => $section) {
        $new_body = trim((string) ($section['body'] ?? ''));
        $old_body = trim((string) ($previous[$i]['body'] ?? ''));
        if ($new_body === '' && $old_body !== '') {
            $incoming[$i]['body'] = $previous[$i]['body'];
        }
        $new_title = trim((string) ($section['title'] ?? ''));
        $old_title = trim((string) ($previous[$i]['title'] ?? ''));
        if ($new_title === '' && $old_title !== '') {
            $incoming[$i]['title'] = $previous[$i]['title'];
        }
    }

    return $incoming;
}

add_filter('redirect_post_location', function (string $location, int $post_id): string {
    if (get_post_type($post_id) !== 'project') {
        return $location;
    }

    if (empty($_POST['rs_project_nonce']) || !wp_verify_nonce($_POST['rs_project_nonce'], 'rs_project_save')) {
        return $location;
    }

    $tab = sanitize_key((string) ($_POST['rs_project_active_tab'] ?? ''));
    if (!in_array($tab, ['general', 'en', 'pt', 'media'], true)) {
        return $location;
    }

    return add_query_arg('rs_project_tab', $tab, $location);
}, 10, 2);

add_action('load-post.php', function (): void {
    if (!isset($_GET['post'], $_GET['action']) || $_GET['action'] !== 'edit') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'project') {
        return;
    }

    $post_id = (int) $_GET['post'];
    if (!function_exists('rs_project_is_legacy_twin') || !rs_project_is_legacy_twin($post_id)) {
        return;
    }

    $canonical = rs_project_resolve_canonical_id($post_id);
    if ($canonical <= 0 || $canonical === $post_id) {
        return;
    }

    wp_safe_redirect(
        add_query_arg('rs_project_tab', 'pt', admin_url('post.php?post=' . $canonical . '&action=edit'))
    );
    exit;
});
