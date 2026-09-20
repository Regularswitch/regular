<?php
/**
 * Remove caixas legadas do ThemeRain / ETC (não usadas pelo site headless).
 */

if (defined('RS_HIDE_THEMERAIN_META_LOADED')) {
    return;
}
define('RS_HIDE_THEMERAIN_META_LOADED', true);

/** IDs das meta boxes do tema antigo (filtro themerain_meta_boxes). */
function rs_themerain_legacy_meta_ids(): array {
    return ['project', 'page', 'portfolio', 'blog'];
}

/**
 * Não registra Project Settings, Page Settings, etc.
 */
add_filter('themerain_meta_boxes', function ($sections) {
    if (!is_array($sections)) {
        return [];
    }

    $blocked = rs_themerain_legacy_meta_ids();

    return array_values(array_filter($sections, static function ($section) use ($blocked) {
        if (!is_array($section)) {
            return false;
        }

        $id = (string) ($section['id'] ?? '');
        return $id === '' || !in_array($id, $blocked, true);
    }));
}, 100);

/**
 * Fallback: remove caixas se o filtro não rodou a tempo.
 */
add_action('add_meta_boxes', function () {
    $screens = ['project', 'page', 'post'];
    foreach (rs_themerain_legacy_meta_ids() as $id) {
        foreach ($screens as $screen) {
            remove_meta_box('themerain_meta_' . $id, $screen, 'normal');
            remove_meta_box('themerain_meta_' . $id, $screen, 'side');
            remove_meta_box('themerain_meta_' . $id, $screen, 'advanced');
        }
    }
}, 99);
