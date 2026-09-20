<?php
/**
 * Aplica tradução EN/PT nas respostas da REST API (?translate=PT).
 */

function rs_rest_get_lang(WP_REST_Request $request): ?string {
    $lang = $request->get_param('translate');
    if (!$lang && function_exists('_getLang')) {
        $lang = _getLang();
    }

    if (!$lang) {
        return null;
    }

    return strtoupper((string) $lang);
}

function rs_rest_apply_translation(WP_REST_Response $response, WP_Post $post, WP_REST_Request $request): WP_REST_Response {
    $lang = rs_rest_get_lang($request);
    if (!$lang) {
        return $response;
    }

    $translated_id = (int) get_post_meta($post->ID, $lang, true);
    if ($translated_id <= 0) {
        return $response;
    }

    $translated = get_post($translated_id);
    if (!$translated || $translated->post_status !== 'publish') {
        return $response;
    }

    $data = $response->get_data();

    if (isset($data['title']['rendered'])) {
        $data['title']['rendered'] = $translated->post_title;
    }

    if (isset($data['content']['rendered'])) {
        $data['content']['rendered'] = apply_filters('the_content', $translated->post_content);
    }

    if (isset($data['excerpt']['rendered'])) {
        $data['excerpt']['rendered'] = $translated->post_excerpt !== ''
            ? apply_filters('the_excerpt', $translated->post_excerpt)
            : '';
    }

    $response->set_data($data);

    return $response;
}

// brand não usa tradução (logo + título compartilhados).
foreach (['intro', 'post', 'page', 'footer', 'capabilities'] as $post_type) {
    add_filter("rest_prepare_{$post_type}", 'rs_rest_apply_translation', 10, 3);
}

add_filter('rest_prepare_project', function (WP_REST_Response $response, WP_Post $post, WP_REST_Request $request): WP_REST_Response {
    $lang = rs_rest_get_lang($request);
    if (!$lang || $lang !== 'PT' || !function_exists('rs_project_i18n_get_locale_text')) {
        return $response;
    }

    $canonical_id = function_exists('rs_project_resolve_canonical_id')
        ? rs_project_resolve_canonical_id((int) $post->ID)
        : (int) $post->ID;
    $texts = rs_project_i18n_get_locale_text($canonical_id, 'pt');
    $data = $response->get_data();

    if ($texts['title'] !== '' && isset($data['title']['rendered'])) {
        $data['title']['rendered'] = $texts['title'];
    }

    if ($texts['excerpt'] !== '' && isset($data['excerpt']['rendered'])) {
        $data['excerpt']['rendered'] = apply_filters('the_excerpt', $texts['excerpt']);
    }

    $response->set_data($data);

    return $response;
}, 10, 3);

// Categorias de projeto (nomes traduzidos).
add_filter('rest_prepare_project-category', function ($response, $item, $request) {
    $lang = rs_rest_get_lang($request);
    if (!$lang) {
        return $response;
    }

    $translated_id = (int) get_term_meta($item->term_id, $lang, true);
    if ($translated_id <= 0) {
        return $response;
    }

    $translated = get_term($translated_id, 'project-category');
    if (!$translated || is_wp_error($translated)) {
        return $response;
    }

    $data = $response->get_data();
    if (isset($data['name'])) {
        $data['name'] = $translated->name;
    }
    if (isset($data['description'])) {
        $data['description'] = $translated->description;
    }

    $response->set_data($data);

    return $response;
}, 10, 3);
