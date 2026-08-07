<?php
/**
 * Opção D: ao salvar o post EN, sincroniza só a mídia para o PT ligado.
 * Textos (headline, acordeões, blocos) do PT não são alterados.
 *
 * Cobre: about, education, contact, capabilities, project.
 */

if (defined('RS_SYNC_MEDIA_EN_TO_PT_LOADED')) {
    return;
}
define('RS_SYNC_MEDIA_EN_TO_PT_LOADED', true);

/**
 * CPT que participam do sync de mídia EN → PT.
 *
 * @return array<int, string>
 */
function rs_sync_media_post_types(): array {
    return ['about', 'education', 'contact', 'project', 'capabilities'];
}

/**
 * Páginas de seção (slug en/pt) — entram no bootstrap automático.
 *
 * @return array<int, string>
 */
function rs_sync_media_section_post_types(): array {
    return ['about', 'education', 'contact', 'capabilities'];
}

function rs_sync_media_link_pair(int $en_id, int $pt_id): void {
    if (function_exists('rs_translate_link_pair')) {
        rs_translate_link_pair($en_id, 'PT', $pt_id);
        return;
    }

    update_post_meta($en_id, 'PT', $pt_id);
    update_post_meta($pt_id, 'EN', $en_id);
}

function rs_sync_media_is_en_source(int $post_id): bool {
    if ((int) get_post_meta($post_id, 'EN', true) > 0) {
        return false; // é o gêmeo PT
    }

    $post = get_post($post_id);
    if (!$post) {
        return false;
    }

    if ($post->post_name === 'pt') {
        return false;
    }

    if (function_exists('rs_detect_post_locale') && rs_detect_post_locale($post_id) === 'pt') {
        return false;
    }

    // Ligado via coluna Language → PT
    if ((int) get_post_meta($post_id, 'PT', true) > 0) {
        return true;
    }

    // CPTs de página com slug en/pt (About, Education, etc.)
    if ($post->post_name === 'en') {
        return true;
    }

    if (function_exists('rs_detect_post_locale') && rs_detect_post_locale($post_id) === 'en') {
        return true;
    }

    return false;
}

/**
 * Resolve o post PT gêmeo: meta PT, ou slug/locale `pt` no mesmo CPT.
 */
function rs_sync_media_pt_twin_id(int $en_id): int {
    $pt_id = (int) get_post_meta($en_id, 'PT', true);
    if ($pt_id > 0) {
        $pt = get_post($pt_id);
        if ($pt && $pt->post_status !== 'trash') {
            return $pt_id;
        }
    }

    $en = get_post($en_id);
    if (!$en) {
        return 0;
    }

    // Páginas de seção: about/en + about/pt
    if ($en->post_name === 'en' || (function_exists('rs_detect_post_locale') && rs_detect_post_locale($en_id) === 'en')) {
        $siblings = get_posts([
            'post_type'      => $en->post_type,
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'name'           => 'pt',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);

        if (empty($siblings[0])) {
            // Fallback: post com locale/título PT no mesmo CPT
            $candidates = get_posts([
                'post_type'      => $en->post_type,
                'post_status'    => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 20,
                'fields'         => 'ids',
                'exclude'        => [$en_id],
            ]);
            foreach ($candidates as $candidate_id) {
                $candidate_id = (int) $candidate_id;
                if ((int) get_post_meta($candidate_id, 'EN', true) === $en_id) {
                    $siblings = [$candidate_id];
                    break;
                }
                $cand = get_post($candidate_id);
                if ($cand && $cand->post_name === 'pt') {
                    $siblings = [$candidate_id];
                    break;
                }
                if (function_exists('rs_detect_post_locale') && rs_detect_post_locale($candidate_id) === 'pt') {
                    $siblings = [$candidate_id];
                    break;
                }
            }
        }

        if (!empty($siblings[0])) {
            $found = (int) $siblings[0];
            rs_sync_media_link_pair($en_id, $found);
            return $found;
        }
    }

    return 0;
}

function rs_sync_media_notice_html(int $post_id): string {
    $post = get_post($post_id);
    $slug = $post ? (string) $post->post_name : '';
    $is_pt = (int) get_post_meta($post_id, 'EN', true) > 0
        || $slug === 'pt'
        || (function_exists('rs_detect_post_locale') && rs_detect_post_locale($post_id) === 'pt');
    $pt_twin = !$is_pt ? rs_sync_media_pt_twin_id($post_id) : 0;
    $has_link = (int) get_post_meta($post_id, 'PT', true) > 0 || $pt_twin > 0;

    if ($is_pt) {
        $en_id = (int) get_post_meta($post_id, 'EN', true);
        if ($en_id <= 0 && $post && ($slug === 'pt' || (function_exists('rs_detect_post_locale') && rs_detect_post_locale($post_id) === 'pt'))) {
            $ens = get_posts([
                'post_type'      => $post->post_type,
                'post_status'    => ['publish', 'draft', 'pending', 'private'],
                'name'           => 'en',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ]);
            $en_id = !empty($ens[0]) ? (int) $ens[0] : 0;
        }
        $edit = $en_id > 0 ? get_edit_post_link($en_id, 'raw') : '';
        $link = $edit
            ? ' <a href="' . esc_url($edit) . '">Abrir o EN</a>.'
            : '';

        return '<div class="notice notice-info inline" style="margin:0 0 14px;padding:8px 12px;">'
            . '<strong>Mídia sincronizada do EN.</strong> '
            . 'Imagens/vídeos deste post são atualizados automaticamente ao salvar a versão em inglês.'
            . $link
            . '</div>';
    }

    if ($has_link) {
        return '<div class="notice notice-success inline" style="margin:0 0 14px;padding:8px 12px;">'
            . '<strong>Sync de mídia ativo.</strong> '
            . 'Ao salvar este EN, hero/galerias/logos são copiados para o PT. Textos do PT não mudam.'
            . '</div>';
    }

    return '<div class="notice notice-warning inline" style="margin:0 0 14px;padding:8px 12px;">'
        . '<strong>Sync inativo.</strong> '
        . 'Este EN ainda não está ligado a um PT. Na lista, use a coluna <strong>Language → PT</strong> '
        . '(cria/abre o par). Depois salve o EN de novo para copiar a mídia.'
        . '</div>';
}

/**
 * Copia image_id do EN → PT por índice; preserva textos do PT.
 * Se o EN tiver mais itens, cria stubs no PT só com a mídia.
 *
 * @param array<int, array{title?: string, text?: string, image_id?: int}> $en
 * @param array<int, array{title?: string, text?: string, image_id?: int}> $pt
 * @return array<int, array{title: string, text: string, image_id: int}>
 */
function rs_sync_media_merge_section_images(array $en, array $pt): array {
    $out = [];

    foreach ($en as $i => $en_section) {
        $pt_section = is_array($pt[$i] ?? null) ? $pt[$i] : [];
        $out[] = [
            'title'    => (string) ($pt_section['title'] ?? ''),
            'text'     => (string) ($pt_section['text'] ?? ''),
            'image_id' => (int) ($en_section['image_id'] ?? 0),
        ];
    }

    // Mantém seções extras só no PT (sem apagar texto)
    for ($i = count($en); $i < count($pt); $i++) {
        if (!is_array($pt[$i])) {
            continue;
        }
        $out[] = [
            'title'    => (string) ($pt[$i]['title'] ?? ''),
            'text'     => (string) ($pt[$i]['text'] ?? ''),
            'image_id' => (int) ($pt[$i]['image_id'] ?? 0),
        ];
    }

    return $out;
}

function rs_sync_about_media(int $from_id, int $to_id): void {
    if (function_exists('rs_section_copy_hero_media')) {
        rs_section_copy_hero_media($from_id, $to_id, RS_ABOUT_HERO_IMAGE_KEY, RS_ABOUT_HERO_VIDEO_KEY);
    }

    if (!function_exists('rs_about_get_sections')) {
        return;
    }

    $merged = rs_sync_media_merge_section_images(
        rs_about_get_sections($from_id),
        rs_about_get_sections($to_id)
    );

    update_post_meta($to_id, RS_ABOUT_SECTIONS_KEY, wp_json_encode(array_values($merged), JSON_UNESCAPED_UNICODE));
}

function rs_sync_capabilities_media(int $from_id, int $to_id): void {
    if (!function_exists('rs_capabilities_get_sections')) {
        return;
    }

    $merged = rs_sync_media_merge_section_images(
        rs_capabilities_get_sections($from_id),
        rs_capabilities_get_sections($to_id)
    );

    update_post_meta($to_id, RS_CAPABILITIES_SECTIONS_KEY, wp_json_encode(array_values($merged), JSON_UNESCAPED_UNICODE));
}

function rs_sync_contact_media(int $from_id, int $to_id): void {
    if (function_exists('rs_section_copy_hero_media')) {
        rs_section_copy_hero_media($from_id, $to_id, RS_CONTACT_HERO_IMAGE_KEY, RS_CONTACT_HERO_VIDEO_KEY);
    }

    if (defined('RS_CONTACT_INFO_KEY')) {
        $en_info = get_post_meta($from_id, RS_CONTACT_INFO_KEY, true);
        if ($en_info !== '' && $en_info !== false) {
            $pt_info_raw = get_post_meta($to_id, RS_CONTACT_INFO_KEY, true);
            $en = [];
            $pt = [];
            if (is_string($en_info)) {
                $decoded = json_decode($en_info, true);
                if (is_array($decoded)) {
                    $en = $decoded;
                }
            }
            if (is_string($pt_info_raw)) {
                $decoded = json_decode($pt_info_raw, true);
                if (is_array($decoded)) {
                    $pt = $decoded;
                }
            }

            // Copia telefones/e-mails do EN; mantém títulos/textos do PT quando existirem.
            foreach (['contact_phone', 'contact_phone_tel', 'contact_email', 'address_street', 'jobs_email', 'internship_email'] as $key) {
                if (!empty($en[$key])) {
                    $pt[$key] = $en[$key];
                }
            }
            if (!empty($en['contact_location']) && empty($pt['contact_location'])) {
                $pt['contact_location'] = $en['contact_location'];
            }
            if (!empty($en['address_location']) && empty($pt['address_location'])) {
                $pt['address_location'] = $en['address_location'];
            }

            $normalized = function_exists('rs_contact_normalize_info')
                ? rs_contact_normalize_info($pt, 'pt')
                : $pt;
            update_post_meta($to_id, RS_CONTACT_INFO_KEY, wp_json_encode($normalized, JSON_UNESCAPED_UNICODE));
            if (function_exists('rs_contact_info_to_blocks')) {
                update_post_meta($to_id, RS_CONTACT_BLOCKS_KEY, wp_json_encode(rs_contact_info_to_blocks($normalized), JSON_UNESCAPED_UNICODE));
            }
        }
    }
}

function rs_sync_education_media(int $from_id, int $to_id): void {
    if (function_exists('rs_section_copy_hero_media')) {
        rs_section_copy_hero_media($from_id, $to_id, RS_EDUCATION_HERO_IMAGE_KEY, RS_EDUCATION_HERO_VIDEO_KEY);
    }

    if (!function_exists('rs_education_get_institutions_raw')) {
        return;
    }

    $en = rs_education_get_institutions_raw($from_id);
    $pt = rs_education_get_institutions_raw($to_id);
    $gallery_keys = ['midGallery', 'bottomGallery'];
    $out = [];

    foreach ($en as $i => $en_item) {
        if (!is_array($en_item)) {
            continue;
        }

        $pt_item = is_array($pt[$i] ?? null) ? $pt[$i] : [
            'name'          => '',
            'description'   => '',
            'logo_id'       => 0,
            'midGallery'    => ['layout' => 'triple', 'image_ids' => '', 'caption' => ''],
            'bottomGallery' => ['layout' => 'grid-2x2', 'image_ids' => '', 'caption' => ''],
        ];

        $entry = [
            'name'        => (string) ($pt_item['name'] ?? ''),
            'description' => (string) ($pt_item['description'] ?? ''),
            'logo_id'     => (int) ($en_item['logo_id'] ?? 0),
        ];

        foreach ($gallery_keys as $key) {
            $en_gal = is_array($en_item[$key] ?? null) ? $en_item[$key] : [];
            $pt_gal = is_array($pt_item[$key] ?? null) ? $pt_item[$key] : [];

            $entry[$key] = [
                'layout'    => (string) ($en_gal['layout'] ?? $pt_gal['layout'] ?? 'pair'),
                'image_ids' => (string) ($en_gal['image_ids'] ?? ''),
                'caption'   => (string) ($pt_gal['caption'] ?? ''),
            ];
        }

        $out[] = $entry;
    }

    // Instituições extras só no PT
    for ($i = count($en); $i < count($pt); $i++) {
        if (is_array($pt[$i])) {
            $out[] = $pt[$i];
        }
    }

    update_post_meta($to_id, RS_EDUCATION_INSTITUTIONS_KEY, wp_json_encode(array_values($out), JSON_UNESCAPED_UNICODE));
}

function rs_sync_project_media(int $from_id, int $to_id): void {
    $hero_id = function_exists('rs_project_get_hero_id') ? rs_project_get_hero_id($from_id) : (int) get_post_meta($from_id, RS_PROJECT_HERO_KEY, true);
    if ($hero_id > 0) {
        update_post_meta($to_id, RS_PROJECT_HERO_KEY, $hero_id);
        update_post_meta($to_id, 'etc_upload_image', $hero_id);
    } else {
        delete_post_meta($to_id, RS_PROJECT_HERO_KEY);
        delete_post_meta($to_id, 'etc_upload_image');
    }

    $logo_id = (int) get_post_meta($from_id, RS_PROJECT_LOGO_KEY, true);
    if ($logo_id > 0) {
        update_post_meta($to_id, RS_PROJECT_LOGO_KEY, $logo_id);
        set_post_thumbnail($to_id, $logo_id);
    } else {
        delete_post_meta($to_id, RS_PROJECT_LOGO_KEY);
    }

    update_post_meta($to_id, RS_PROJECT_GALLERY_KEY, get_post_meta($from_id, RS_PROJECT_GALLERY_KEY, true));
    update_post_meta($to_id, RS_PROJECT_FEATURED_KEY, get_post_meta($from_id, RS_PROJECT_FEATURED_KEY, true));
    update_post_meta($to_id, RS_PROJECT_VIGNETTE_KEY, get_post_meta($from_id, RS_PROJECT_VIGNETTE_KEY, true));
}

function rs_sync_media_en_to_pt(int $en_id, string $post_type): void {
    static $running = false;
    if ($running) {
        return;
    }

    if (!rs_sync_media_is_en_source($en_id)) {
        return;
    }

    $pt_id = rs_sync_media_pt_twin_id($en_id);
    if ($pt_id <= 0) {
        return;
    }

    $running = true;

    switch ($post_type) {
        case 'about':
            rs_sync_about_media($en_id, $pt_id);
            break;
        case 'education':
            rs_sync_education_media($en_id, $pt_id);
            break;
        case 'contact':
            rs_sync_contact_media($en_id, $pt_id);
            break;
        case 'project':
            rs_sync_project_media($en_id, $pt_id);
            break;
        case 'capabilities':
            rs_sync_capabilities_media($en_id, $pt_id);
            break;
    }

    $running = false;
}

/**
 * Liga pares en/pt e sincroniza mídia uma vez (Education, Contact, Capabilities, About + projects já ligados).
 */
function rs_sync_media_bootstrap_all(): int {
    $count = 0;

    foreach (rs_sync_media_section_post_types() as $post_type) {
        $ids = get_posts([
            'post_type'      => $post_type,
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 50,
            'fields'         => 'ids',
        ]);

        foreach ($ids as $post_id) {
            $post_id = (int) $post_id;
            if (!rs_sync_media_is_en_source($post_id)) {
                continue;
            }
            if (rs_sync_media_pt_twin_id($post_id) <= 0) {
                continue;
            }
            rs_sync_media_en_to_pt($post_id, $post_type);
            $count++;
        }
    }

    // Projetos: só os que já têm Language → PT
    $project_ids = get_posts([
        'post_type'      => 'project',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 200,
        'fields'         => 'ids',
        'meta_key'       => 'PT',
        'meta_compare'   => 'EXISTS',
    ]);

    foreach ($project_ids as $post_id) {
        $post_id = (int) $post_id;
        if (!rs_sync_media_is_en_source($post_id)) {
            continue;
        }
        if ((int) get_post_meta($post_id, 'PT', true) <= 0) {
            continue;
        }
        rs_sync_media_en_to_pt($post_id, 'project');
        $count++;
    }

    return $count;
}

foreach (rs_sync_media_post_types() as $post_type) {
    add_action("save_post_{$post_type}", function (int $post_id) use ($post_type) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        rs_sync_media_en_to_pt($post_id, $post_type);
    }, 99);
}

add_action('admin_init', function () {
    if (get_option('rs_sync_media_bootstrap_v123')) {
        return;
    }
    if (!current_user_can('edit_posts')) {
        return;
    }

    $count = rs_sync_media_bootstrap_all();
    update_option('rs_sync_media_bootstrap_v123', 1, false);
    set_transient('rs_sync_media_bootstrap_notice', $count, MINUTE_IN_SECONDS * 10);
});

add_action('admin_notices', function () {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $count = get_transient('rs_sync_media_bootstrap_notice');
    if ($count === false) {
        return;
    }

    delete_transient('rs_sync_media_bootstrap_notice');

    echo '<div class="notice notice-success is-dismissible"><p>'
        . esc_html(sprintf(
            'Sync de mídia EN → PT: %d post(s) atualizado(s) (About, Education, Contact, Capabilities e projetos ligados).',
            (int) $count
        ))
        . '</p></div>';
});
