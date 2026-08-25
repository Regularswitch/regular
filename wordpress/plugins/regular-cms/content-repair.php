<?php
/**
 * Reparos one-shot de conteúdo após migração i18n (metas vazias / mídia perdida).
 */

if (defined('RS_CONTENT_REPAIR_LOADED')) {
    return;
}
define('RS_CONTENT_REPAIR_LOADED', true);

/**
 * @return array{title: string, headline: string, emptyMessage: string}
 */
function rs_repair_projects_page_defaults(string $locale): array {
    if ($locale === 'pt') {
        return [
            'title'        => 'Projetos selecionados',
            'headline'     => 'Criando <strong>identidades visuais</strong>, <strong>experiências digitais</strong> e <strong>narrativas culturais</strong> para marcas, instituições e projetos que transitam entre <strong>estratégia</strong>, <strong>criatividade</strong> e <strong>impacto contemporâneo</strong>.',
            'emptyMessage' => 'Nenhum projeto encontrado.',
        ];
    }

    return [
        'title'        => 'Selected projects',
        'headline'     => 'Creating <strong>visual identities</strong>, <strong>digital experiences</strong> and <strong>cultural narratives</strong> for brands, institutions and projects that move between <strong>strategy</strong>, <strong>creativity</strong> and <strong>contemporary impact</strong>.',
        'emptyMessage' => 'No projects found.',
    ];
}

/**
 * @return array{headline: string, body: string, sections: array<int, array{title: string, text: string, image_id: int}>}
 */
function rs_repair_about_pt_defaults(): array {
    return [
        'headline' => 'Criando <strong>conexões culturais</strong> entre <strong>estratégia, criatividade</strong> e <strong>experiências contemporâneas</strong>.',
        'body'     => '<p>A RegularSwitch nasceu da conexão entre culturas distintas — com raízes no Brasil e na França. Atuamos na interseção entre branding, conteúdo e experiências digitais, desenvolvendo sistemas visuais, narrativas e ecossistemas criativos para marcas e instituições.</p><p>Nossa abordagem combina pensamento estratégico, sensibilidade cultural e experimentação criativa. Do conceito à execução, construímos projetos que aproximam marcas, pessoas e territórios de forma relevante e contemporânea.</p><p>Acreditamos no design como ferramenta de conexão — capaz de traduzir propósito em linguagem visual e ampliar o impacto de marcas e projetos culturais em diferentes contextos.</p>',
        'sections' => [
            ['title' => 'NOSSA ABORDAGEM', 'text' => '<p>Combinamos pensamento estratégico, sensibilidade cultural e experimentação criativa para desenvolver projetos que conectam marcas, pessoas e territórios de forma relevante e contemporânea.</p>', 'image_id' => 0],
            ['title' => 'NOSSO TIME', 'text' => '<p>Somos um time multicultural com raízes no Brasil e na França — designers, diretores de arte e estrategistas que atuam de forma colaborativa em projetos de branding, digital e cultura visual.</p>', 'image_id' => 0],
            ['title' => 'NOSSA METODOLOGIA DE TRABALHO', 'text' => '<p>Trabalhamos em ciclos de pesquisa, concepção e execução, integrando narrativa, sistema visual e aplicação em diferentes contextos — do impresso ao digital, do institucional ao cultural.</p>', 'image_id' => 0],
            ['title' => 'NOSSOS VALORES', 'text' => '<p>Acreditamos em colaboração, rigor gráfico, abertura cultural e impacto contemporâneo. Buscamos projetos com propósito, estética e consistência ao longo do tempo.</p>', 'image_id' => 0],
            ['title' => 'NÃO NEGOCIÁVEIS', 'text' => '<p>Qualidade de execução, honestidade criativa, respeito às pessoas e compromisso com a relevância cultural dos projetos que desenvolvemos.</p>', 'image_id' => 0],
        ],
    ];
}

function rs_repair_education_headline_defaults(string $locale): string {
    if ($locale === 'pt') {
        return 'Acreditamos que a educação é um espaço de troca e experimentação criativa. <strong>Entre França e Brasil</strong>, desenvolvemos workshops, palestras e projetos colaborativos que conectam culturas e novas formas de pensar o <strong>design contemporâneo</strong>.';
    }

    return 'We believe education is a space for exchange and creative experimentation. <strong>Between France and Brazil</strong>, we develop workshops, talks and collaborative projects that connect cultures and new ways of thinking about <strong>contemporary design</strong>.';
}

function rs_repair_dedupe_post_meta(int $post_id, string $key): void {
    global $wpdb;
    if ($post_id <= 0 || $key === '') {
        return;
    }

    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC",
        $post_id,
        $key
    ));

    if (count($ids) <= 1) {
        return;
    }

    array_shift($ids);
    foreach ($ids as $meta_id) {
        $wpdb->delete($wpdb->postmeta, ['meta_id' => (int) $meta_id], ['%d']);
    }
}

/**
 * Restaura hero/galeria a partir de thumbnail + attachments filhos + meta legada.
 */
function rs_repair_project_media_from_attachments(int $post_id): bool {
    if ($post_id <= 0 || !function_exists('rs_project_i18n_get') || !function_exists('rs_project_i18n_save')) {
        return false;
    }

    $data = rs_project_i18n_get($post_id);
    $hero = (int) ($data['shared']['hero_id'] ?? 0);
    $gallery = trim((string) ($data['shared']['gallery_ids'] ?? ''));

    // Tenta meta legada antes de attachments.
    if ($hero <= 0 && defined('RS_PROJECT_HERO_KEY')) {
        $hero = (int) get_post_meta($post_id, RS_PROJECT_HERO_KEY, true);
    }
    if ($hero <= 0) {
        $hero = (int) get_post_meta($post_id, 'etc_upload_image', true);
    }
    if ($gallery === '' && defined('RS_PROJECT_GALLERY_KEY')) {
        $gallery = trim((string) get_post_meta($post_id, RS_PROJECT_GALLERY_KEY, true));
    }

    if ($hero > 0 || $gallery !== '') {
        $changed = false;
        if ((int) ($data['shared']['hero_id'] ?? 0) <= 0 && $hero > 0) {
            $data['shared']['hero_id'] = $hero;
            $changed = true;
        }
        if (trim((string) ($data['shared']['gallery_ids'] ?? '')) === '' && $gallery !== '') {
            $data['shared']['gallery_ids'] = $gallery;
            $changed = true;
        }
        if ($changed) {
            rs_project_i18n_save($post_id, $data);
            return true;
        }
        return false;
    }

    $thumb = (int) get_post_thumbnail_id($post_id);
    $children = get_posts([
        'post_type'      => 'attachment',
        'post_parent'    => $post_id,
        'post_status'    => 'inherit',
        'posts_per_page' => 100,
        'orderby'        => 'menu_order ID',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ]);

    $image_ids = [];
    foreach ($children as $att_id) {
        $att_id = (int) $att_id;
        $mime = (string) get_post_mime_type($att_id);
        if ($mime !== '' && (str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/'))) {
            $image_ids[] = $att_id;
        }
    }

    if ($thumb <= 0 && $image_ids) {
        $thumb = $image_ids[0];
    }

    if ($thumb <= 0 && !$image_ids) {
        return false;
    }

    $gallery_ids = array_values(array_filter($image_ids, static function (int $id) use ($thumb): bool {
        return $id !== $thumb;
    }));
    if (!$gallery_ids && $thumb > 0) {
        $gallery_ids = [$thumb];
    }

    $data['shared']['hero_id'] = $thumb;
    $data['shared']['gallery_ids'] = implode(',', $gallery_ids);

    rs_project_i18n_save($post_id, $data);
    return true;
}

function rs_content_repair_v154_once(): void {
    if (get_option('rs_content_repair_v154')) {
        return;
    }

    // --- projects-page ---
    if (function_exists('rs_section_i18n_canonical_id') && function_exists('rs_projects_page_i18n_get')) {
        $pp_id = rs_section_i18n_canonical_id('projects-page');
        if ($pp_id > 0) {
            rs_repair_dedupe_post_meta($pp_id, 'rs_projects_page_i18n');
            $data = rs_projects_page_i18n_get($pp_id);
            $changed = false;
            foreach (['en', 'pt'] as $locale) {
                $loc = $data['locales'][$locale] ?? [];
                if (trim((string) ($loc['title'] ?? '')) === '' && trim((string) ($loc['headline'] ?? '')) === '') {
                    $data['locales'][$locale] = rs_repair_projects_page_defaults($locale);
                    $changed = true;
                }
            }
            if ($changed) {
                if (function_exists('rs_section_i18n_save')) {
                    rs_section_i18n_save($pp_id, 'rs_projects_page_i18n', rs_projects_page_i18n_normalize($data));
                }
                if (function_exists('rs_projects_page_sync_legacy_meta')) {
                    rs_projects_page_sync_legacy_meta($pp_id, rs_projects_page_i18n_normalize($data));
                } else {
                    $en = $data['locales']['en'];
                    update_post_meta($pp_id, 'rs_projects_page_title', $en['title']);
                    update_post_meta($pp_id, 'rs_projects_page_headline', $en['headline']);
                    update_post_meta($pp_id, 'rs_projects_page_empty_message', $en['emptyMessage']);
                }
            }
        }
    }

    // --- about PT ---
    if (function_exists('rs_section_i18n_canonical_id') && function_exists('rs_about_i18n_get')) {
        $about_id = rs_section_i18n_canonical_id('about');
        if ($about_id > 0) {
            rs_repair_dedupe_post_meta($about_id, 'rs_about_i18n');
            $data = rs_about_i18n_get($about_id);
            $pt = $data['locales']['pt'] ?? [];
            if (trim((string) ($pt['headline'] ?? '')) === '' && trim((string) ($pt['body'] ?? '')) === '') {
                $data['locales']['pt'] = rs_repair_about_pt_defaults();
                $normalized = rs_about_i18n_normalize($data);
                if (function_exists('rs_section_i18n_save')) {
                    rs_section_i18n_save($about_id, 'rs_about_i18n', $normalized);
                }
                if (function_exists('rs_about_sync_legacy_meta')) {
                    rs_about_sync_legacy_meta($about_id, $normalized);
                }
            }
        }
    }

    // --- education headline "Teste" / empty PT ---
    if (function_exists('rs_section_i18n_canonical_id') && function_exists('rs_education_i18n_get')) {
        $edu_id = rs_section_i18n_canonical_id('education');
        if ($edu_id > 0) {
            rs_repair_dedupe_post_meta($edu_id, 'rs_education_i18n');
            $data = rs_education_i18n_get($edu_id);
            $changed = false;
            $en_h = trim(wp_strip_all_tags((string) ($data['locales']['en']['headline'] ?? '')));
            if ($en_h === '' || strcasecmp($en_h, 'Teste') === 0 || strcasecmp($en_h, 'Inglês') === 0) {
                $data['locales']['en']['headline'] = rs_repair_education_headline_defaults('en');
                $changed = true;
            }
            $pt_h = trim(wp_strip_all_tags((string) ($data['locales']['pt']['headline'] ?? '')));
            if ($pt_h === '' || strcasecmp($pt_h, 'Teste') === 0) {
                $data['locales']['pt']['headline'] = rs_repair_education_headline_defaults('pt');
                $changed = true;
            }
            // Vídeo: tenta page-heroes se shared vazio.
            if ((int) ($data['shared']['hero_video_id'] ?? 0) <= 0 && function_exists('rs_page_heroes_get_video_id')) {
                $vid = (int) rs_page_heroes_get_video_id('education');
                if ($vid > 0) {
                    $data['shared']['hero_video_id'] = $vid;
                    $changed = true;
                }
            }
            if ($changed) {
                $normalized = rs_education_i18n_normalize($data);
                if (function_exists('rs_section_i18n_save')) {
                    rs_section_i18n_save($edu_id, 'rs_education_i18n', $normalized);
                }
                if (function_exists('rs_education_sync_legacy_meta')) {
                    rs_education_sync_legacy_meta($edu_id, $normalized);
                }
            }
        }
    }

    // --- project media ---
    if (post_type_exists('project')) {
        $ids = get_posts([
            'post_type'      => 'project',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
        ]);
        foreach ($ids as $id) {
            $id = (int) $id;
            if (function_exists('rs_project_resolve_canonical_id') && rs_project_resolve_canonical_id($id) !== $id) {
                continue;
            }
            rs_repair_project_media_from_attachments($id);
        }
    }

    // Publica páginas privacy/cookies se existirem como draft (fallback legado).
    foreach (['privacy-policy', 'cookies-policy'] as $slug) {
        $pages = get_posts([
            'post_type'      => 'page',
            'name'           => $slug,
            'post_status'    => ['draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);
        if (!empty($pages[0])) {
            wp_update_post(['ID' => (int) $pages[0], 'post_status' => 'publish']);
        }
    }

    update_option('rs_content_repair_v154', 1, false);
}
add_action('init', 'rs_content_repair_v154_once', 40);

/**
 * Re-roda restauração de mídia de projetos (save admin podia zerar hero/galeria).
 */
function rs_content_repair_v156_once(): void {
    if (get_option('rs_content_repair_v156')) {
        return;
    }
    if (!post_type_exists('project')) {
        update_option('rs_content_repair_v156', 1, false);
        return;
    }

    $ids = get_posts([
        'post_type'      => 'project',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 300,
        'fields'         => 'ids',
    ]);

    foreach ($ids as $id) {
        $id = (int) $id;
        if (function_exists('rs_project_resolve_canonical_id') && rs_project_resolve_canonical_id($id) !== $id) {
            continue;
        }
        if (function_exists('rs_repair_dedupe_post_meta')) {
            rs_repair_dedupe_post_meta($id, 'rs_project_i18n');
        }
        rs_repair_project_media_from_attachments($id);
    }

    update_option('rs_content_repair_v156', 1, false);
}
add_action('init', 'rs_content_repair_v156_once', 41);
