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

function rs_translate_find_existing(int $source_id, string $lang, string $target_type): int {
    $opposite = rs_translate_opposite_lang($lang);
    $direct_id = (int) get_post_meta($source_id, $lang, true);
    if ($direct_id > 0) {
        $linked = get_post($direct_id);
        if ($linked && $linked->post_type === $target_type && $linked->post_status !== 'trash') {
            $back = (int) get_post_meta($direct_id, $opposite, true);
            // Só reutiliza se o gêmeo aponta de volta (ou ainda não tem vínculo).
            if ($back === 0 || $back === $source_id) {
                return $direct_id;
            }
        }
        delete_post_meta($source_id, $lang);
    }

    $reverse = get_posts([
        'post_type'      => $target_type,
        'post_status'    => 'any',
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

function rs_translate_link_pair(int $source_id, string $lang, int $target_id): void {
    $opposite = rs_translate_opposite_lang($lang);
    $back = (int) get_post_meta($target_id, $opposite, true);
    // Não “roubar” um gêmeo que já pertence a outro post.
    if ($back > 0 && $back !== $source_id) {
        return;
    }

    $previous = (int) get_post_meta($source_id, $lang, true);
    if ($previous > 0 && $previous !== $target_id) {
        $prev_back = (int) get_post_meta($previous, $opposite, true);
        if ($prev_back === $source_id) {
            delete_post_meta($previous, $opposite);
        }
    }

    update_post_meta($source_id, $lang, $target_id);
    update_post_meta($target_id, $opposite, $source_id);
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

function translate_proxy($request) {
    $parans = $request->get_params();
    $source_id = (int) $parans['id'];
    $lang = strtoupper((string) $parans['lang']);
    $the_post = get_post($source_id);

    if (!$the_post) {
        return new WP_Error('not_found', 'Post não encontrado', ['status' => 404]);
    }

    $target_type = rs_translate_target_post_type($the_post);
    $post_translate_id = rs_translate_find_existing($source_id, $lang, $target_type);

    if ($post_translate_id === 0) {
        $locale_slug = strtolower($lang);
        // Projetos: não copiar post_content legado (misturava imagens/texto de outro case)
        // e não usar slug "pt"/"en" (colidia entre traduções).
        $is_project = $the_post->post_type === 'project';
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

        rs_translate_link_pair($source_id, $lang, $new_post_id);
        rs_copy_translation_fields($source_id, $new_post_id, $target_type);

        if (function_exists('rs_apply_locale_slug')) {
            rs_apply_locale_slug($new_post_id);
        }

        $parans['go'] = get_site_url() . "/wp-admin/post.php?post={$new_post_id}&action=edit";
        $parans['action'] = 'create';
        $parans['post_translate_id'] = $new_post_id;
    } else {
        rs_translate_link_pair($source_id, $lang, $post_translate_id);

        // Re-sincroniza campos do projeto a partir do EN quando ?sync=1
        // (útil quando a PT ficou com conteúdo antigo/errado).
        if (
            $the_post->post_type === 'project'
            && !empty($parans['sync'])
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

        $parans['go'] = get_site_url() . "/wp-admin/post.php?post={$post_translate_id}&action=edit";
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
            'method'   => 'GET',
            'callback' => 'translate_proxy',
        ],
    ]);
});
