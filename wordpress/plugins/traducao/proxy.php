<?php

function rs_translate_same_type_cpts(): array {
    return ['footer', 'intro', 'brand', 'project', 'capabilities', 'about', 'education', 'contact', 'legal', 'projects-page', 'site-ui'];
}

function rs_translate_target_post_type(WP_Post $source): string {
    return in_array($source->post_type, rs_translate_same_type_cpts(), true)
        ? $source->post_type
        : 'und_translate';
}

function rs_translate_opposite_lang(string $lang): string {
    $lang = strtoupper($lang);
    return $lang === 'PT' ? 'EN' : 'PT';
}

/**
 * Idioma “real” do post: PT se aponta para um EN; senão EN.
 */
function rs_translate_post_lang(int $post_id): string {
    if (function_exists('rs_project_locale_badge')) {
        return strtoupper(rs_project_locale_badge($post_id));
    }

    return (int) get_post_meta($post_id, 'EN', true) > 0 ? 'PT' : 'EN';
}

/**
 * Gêmeo já ligado nos dois sentidos (EN↔PT).
 * Projetos: nunca reaproveita órfão (meta de volta vazia) — isso roubava o PT de outro case.
 */
function rs_translate_find_existing(int $source_id, string $lang, string $target_type): int {
    $lang = strtoupper($lang);
    $opposite = rs_translate_opposite_lang($lang);
    $require_back = $target_type === 'project';

    $direct_id = (int) get_post_meta($source_id, $lang, true);
    if ($direct_id > 0) {
        $linked = get_post($direct_id);
        if ($linked && $linked->post_type === $target_type && $linked->post_status !== 'trash') {
            $back = (int) get_post_meta($direct_id, $opposite, true);
            if ($back === $source_id || (!$require_back && $back === 0)) {
                if ($back === 0) {
                    update_post_meta($direct_id, $opposite, $source_id);
                }
                return $direct_id;
            }
        }
        // Ponteiro quebrado/roubado: limpa só o lado do source (não apaga EN do gêmeo alheio).
        delete_post_meta($source_id, $lang);
    }

    $reverse = get_posts([
        'post_type'      => $target_type,
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => $opposite,
                'value'   => $source_id,
                'compare' => '=',
            ],
        ],
    ]);

    if (!empty($reverse[0])) {
        $found = (int) $reverse[0];
        update_post_meta($source_id, $lang, $found);
        return $found;
    }

    return 0;
}

/**
 * Liga o par. Nunca rouba gêmeo que já pertence a outro post.
 * Não apaga meta EN/PT do gêmeo anterior (órfãos viravam “EN” em Todos).
 */
function rs_translate_link_pair(int $source_id, string $lang, int $target_id): bool {
    $lang = strtoupper($lang);
    $opposite = rs_translate_opposite_lang($lang);
    $back = (int) get_post_meta($target_id, $opposite, true);

    if ($back > 0 && $back !== $source_id) {
        return false;
    }

    update_post_meta($source_id, $lang, $target_id);
    update_post_meta($target_id, $opposite, $source_id);

    return true;
}

function rs_copy_translation_fields(int $from_id, int $to_id, string $post_type): void {
    if ($post_type === 'footer' && function_exists('rs_footer_get_meta')) {
        foreach (rs_footer_get_meta($from_id) as $key => $value) {
            update_post_meta($to_id, $key, $value);
        }
        return;
    }

    if ($post_type === 'capabilities' && function_exists('rs_copy_capabilities_fields')) {
        rs_copy_capabilities_fields($from_id, $to_id);
        return;
    }

    if ($post_type === 'about' && function_exists('rs_copy_about_fields')) {
        rs_copy_about_fields($from_id, $to_id);
        return;
    }

    if ($post_type === 'education' && function_exists('rs_copy_education_fields')) {
        rs_copy_education_fields($from_id, $to_id);
        return;
    }

    if ($post_type === 'contact' && function_exists('rs_copy_contact_fields')) {
        rs_copy_contact_fields($from_id, $to_id);
        return;
    }

    if ($post_type === 'legal' && function_exists('rs_copy_legal_fields')) {
        rs_copy_legal_fields($from_id, $to_id);
        return;
    }

    if ($post_type === 'projects-page' && function_exists('rs_copy_projects_page_fields')) {
        rs_copy_projects_page_fields($from_id, $to_id);
        return;
    }

    if ($post_type === 'site-ui' && function_exists('rs_copy_site_ui_fields')) {
        rs_copy_site_ui_fields($from_id, $to_id);
        return;
    }

    if ($post_type === 'intro') {
        $from = get_post($from_id);
        if ($from) {
            wp_update_post([
                'ID'           => $to_id,
                'post_content' => $from->post_content,
                'post_excerpt' => $from->post_excerpt,
            ]);
        }
        return;
    }

    if ($post_type === 'brand') {
        $thumb = get_post_thumbnail_id($from_id);
        if ($thumb) {
            set_post_thumbnail($to_id, $thumb);
        }
        return;
    }

    if ($post_type === 'project' && function_exists('rs_copy_project_fields')) {
        rs_copy_project_fields($from_id, $to_id);
    }
}

/**
 * Permission callback da rota translate/proxy.
 * Sem isso a rota fica pública: qualquer GET (bot, scanner, refresh) podia criar posts.
 */
function rs_translate_proxy_permission(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_Error('rest_forbidden', 'Faça login para traduzir.', ['status' => 401]);
    }

    $source_id = (int) $request->get_param('id');
    if ($source_id > 0 && !current_user_can('edit_post', $source_id)) {
        return new WP_Error('rest_forbidden', 'Sem permissão para editar este post.', ['status' => 403]);
    }

    if (!current_user_can('edit_posts')) {
        return new WP_Error('rest_forbidden', 'Sem permissão.', ['status' => 403]);
    }

    return true;
}

function translate_proxy($request) {
    $parans = $request->get_params();
    $source_id = (int) $parans['id'];
    $lang = strtoupper((string) $parans['lang']);

    // Nonce: evita replay/CSRF em uma rota GET com efeito colateral (cria posts).
    $nonce = (string) ($parans['_wpnonce'] ?? '');
    if (!wp_verify_nonce($nonce, 'rs_translate_proxy_' . $source_id)) {
        return new WP_Error('bad_nonce', 'Link de tradução expirado, recarregue a página.', ['status' => 403]);
    }

    $the_post = get_post($source_id);

    if (!$the_post) {
        return new WP_Error('not_found', 'Post não encontrado', ['status' => 404]);
    }

    if (!in_array($lang, ['EN', 'PT'], true)) {
        return new WP_Error('bad_lang', 'Idioma inválido', ['status' => 400]);
    }

    $current_lang = rs_translate_post_lang($source_id);
    $target_type = rs_translate_target_post_type($the_post);
    $is_project = $the_post->post_type === 'project';
    $edit_url = static function (int $id): string {
        return get_site_url() . "/wp-admin/post.php?post={$id}&action=edit";
    };

    // Clicou no idioma atual → só abre o post (não cria outro EN/PT).
    if ($lang === $current_lang) {
        $parans['go'] = $edit_url($source_id);
        $parans['action'] = 'self';
        $parans['post_translate_id'] = $source_id;
        $parans['lang'] = $lang;
        $parans['post_type'] = $target_type;

        return rest_ensure_response($parans);
    }

    $post_translate_id = rs_translate_find_existing($source_id, $lang, $target_type);

    if ($post_translate_id === 0 && !$is_project && function_exists('rs_get_locale_cpt_post_id')) {
        $existing_locale_id = rs_get_locale_cpt_post_id($target_type, strtolower($lang));
        if ($existing_locale_id > 0 && $existing_locale_id !== $source_id) {
            if (rs_translate_link_pair($source_id, $lang, $existing_locale_id)) {
                if ($current_lang === 'EN' && $lang === 'PT') {
                    rs_copy_translation_fields($source_id, $existing_locale_id, $target_type);
                }
                if (function_exists('rs_apply_locale_slug')) {
                    rs_apply_locale_slug($existing_locale_id);
                }

                $parans['go'] = $edit_url($existing_locale_id);
                $parans['action'] = 'link';
                $parans['post_translate_id'] = $existing_locale_id;
                $parans['lang'] = $lang;
                $parans['post_type'] = $target_type;

                return rest_ensure_response($parans);
            }
        }
    }

    if ($post_translate_id === 0) {
        $locale_slug = strtolower($lang);
        // Projetos: não copiar post_content legado e não usar slug "pt"/"en".
        $post_name = $is_project
            ? sanitize_title(wp_strip_all_tags($the_post->post_title) . '-' . $locale_slug)
            : $locale_slug;

        $new_post_id = wp_insert_post([
            'post_title'   => wp_strip_all_tags($the_post->post_title),
            'post_content' => $is_project ? '' : $the_post->post_content,
            'post_excerpt' => $the_post->post_excerpt ?? '',
            'post_status'  => 'publish',
            'post_author'  => (int) $the_post->post_author ?: 1,
            'post_type'    => $target_type,
            'post_name'    => $post_name,
        ]);

        if (is_wp_error($new_post_id)) {
            return $new_post_id;
        }

        if (!rs_translate_link_pair($source_id, $lang, $new_post_id)) {
            wp_trash_post($new_post_id);

            return new WP_Error(
                'link_failed',
                'Não foi possível ligar a tradução (gêmeo já pertence a outro post).',
                ['status' => 409]
            );
        }

        // Só copia campos EN → PT (nunca o contrário na criação).
        if ($current_lang === 'EN' && $lang === 'PT') {
            rs_copy_translation_fields($source_id, $new_post_id, $target_type);
        }

        if (!$is_project && function_exists('rs_apply_locale_slug')) {
            rs_apply_locale_slug($new_post_id);
        }

        $parans['go'] = $edit_url($new_post_id);
        $parans['action'] = 'create';
        $parans['post_translate_id'] = $new_post_id;
    } else {
        // Já existe: só garante o vínculo se estiver incompleto (não reescreve título/mídia).
        rs_translate_link_pair($source_id, $lang, $post_translate_id);

        // Re-sincroniza campos do projeto a partir do EN quando ?sync=1.
        if (
            $the_post->post_type === 'project'
            && !empty($parans['sync'])
            && $current_lang === 'EN'
            && $lang === 'PT'
            && function_exists('rs_copy_project_fields')
        ) {
            rs_copy_project_fields($source_id, $post_translate_id, true);
            wp_update_post([
                'ID'           => $post_translate_id,
                'post_content' => '',
                'post_excerpt' => $the_post->post_excerpt ?? '',
                'post_title'   => wp_strip_all_tags($the_post->post_title),
            ]);
        }

        $parans['go'] = $edit_url($post_translate_id);
        $parans['action'] = 'edit';
        $parans['post_translate_id'] = $post_translate_id;
    }

    $parans['lang'] = $lang;
    $parans['post_type'] = $target_type;

    return rest_ensure_response($parans);
}

add_action('rest_api_init', function () {
    register_rest_route('translate', 'proxy', [
        [
            'method'              => 'GET',
            'callback'            => 'translate_proxy',
            'permission_callback' => 'rs_translate_proxy_permission',
        ],
    ]);
});
