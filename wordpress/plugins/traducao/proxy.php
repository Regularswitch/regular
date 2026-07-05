<?php

function rs_translate_same_type_cpts(): array {
    return ['footer', 'intro', 'brand', 'project'];
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
    $direct_id = (int) get_post_meta($source_id, $lang, true);
    if ($direct_id > 0) {
        $linked = get_post($direct_id);
        if ($linked && $linked->post_type === $target_type) {
            return $direct_id;
        }
    }

    $opposite = rs_translate_opposite_lang($lang);
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
    update_post_meta($source_id, $lang, $target_id);
    update_post_meta($target_id, rs_translate_opposite_lang($lang), $source_id);
}

function rs_copy_translation_fields(int $from_id, int $to_id, string $post_type): void {
    if ($post_type === 'footer' && function_exists('rs_footer_get_meta')) {
        foreach (rs_footer_get_meta($from_id) as $key => $value) {
            update_post_meta($to_id, $key, $value);
        }
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
        $new_post_id = wp_insert_post([
            'post_title'   => wp_strip_all_tags($the_post->post_title),
            'post_content' => $the_post->post_content,
            'post_excerpt' => $the_post->post_excerpt ?? '',
            'post_status'  => 'publish',
            'post_author'  => (int) $the_post->post_author ?: 1,
            'post_type'    => $target_type,
        ]);

        if (is_wp_error($new_post_id)) {
            return $new_post_id;
        }

        rs_copy_translation_fields($source_id, $new_post_id, $target_type);
        rs_translate_link_pair($source_id, $lang, $new_post_id);

        $parans['go'] = get_site_url() . "/wp-admin/post.php?post={$new_post_id}&action=edit";
        $parans['action'] = 'create';
        $parans['post_translate_id'] = $new_post_id;
    } else {
        rs_translate_link_pair($source_id, $lang, $post_translate_id);

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
