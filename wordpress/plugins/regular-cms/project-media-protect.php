<?php
/**
 * Protege mídia de projetos: delete/trash não apaga arquivos ainda usados por outros projects.
 */

if (defined('RS_PROJECT_MEDIA_PROTECT_LOADED')) {
    return;
}
define('RS_PROJECT_MEDIA_PROTECT_LOADED', true);

/**
 * @param array<string, mixed> $data
 * @return list<int>
 */
function rs_project_i18n_collect_media_ids(array $data): array {
    $ids = [];
    $shared = is_array($data['shared'] ?? null) ? $data['shared'] : [];

    foreach (['hero_id', 'logo_id'] as $key) {
        $id = (int) ($shared[$key] ?? 0);
        if ($id > 0) {
            $ids[$id] = true;
        }
    }

    foreach (['gallery_ids', 'gallery_featured_ids'] as $key) {
        $raw = trim((string) ($shared[$key] ?? ''));
        if ($raw === '') {
            continue;
        }
        foreach (explode(',', $raw) as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
    }

    return array_map('intval', array_keys($ids));
}

/**
 * IDs de mídia referenciados pelo project (i18n + meta legada).
 *
 * @return list<int>
 */
function rs_project_referenced_media_ids(int $post_id): array {
    $ids = [];

    if ($post_id <= 0) {
        return [];
    }

    if (function_exists('rs_project_i18n_get')) {
        foreach (rs_project_i18n_collect_media_ids(rs_project_i18n_get($post_id)) as $id) {
            $ids[$id] = true;
        }
    }

    foreach (['rs_project_hero_id', 'rs_project_logo_id', 'etc_upload_image'] as $key) {
        $id = (int) get_post_meta($post_id, $key, true);
        if ($id > 0) {
            $ids[$id] = true;
        }
    }

    $thumb = (int) get_post_thumbnail_id($post_id);
    if ($thumb > 0) {
        $ids[$thumb] = true;
    }

    $gallery = trim((string) get_post_meta($post_id, 'rs_project_gallery', true));
    if ($gallery !== '') {
        foreach (explode(',', $gallery) as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
    }

    return array_map('intval', array_keys($ids));
}

/**
 * True se outro project (≠ $except_post_id) ainda referencia o attachment.
 */
function rs_attachment_used_by_other_projects(int $attachment_id, int $except_post_id = 0): bool {
    if ($attachment_id <= 0) {
        return false;
    }

    $project_ids = get_posts([
        'post_type'              => 'project',
        'post_status'            => ['publish', 'draft', 'pending', 'private', 'future', 'trash', 'auto-draft'],
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    foreach ($project_ids as $pid) {
        $pid = (int) $pid;
        if ($pid <= 0 || $pid === $except_post_id) {
            continue;
        }
        if (in_array($attachment_id, rs_project_referenced_media_ids($pid), true)) {
            return true;
        }
    }

    return false;
}

/**
 * Desanexa attachments filhos do project (post_parent → 0) para o WP não cascatear delete.
 */
function rs_project_detach_child_attachments(int $post_id): void {
    if ($post_id <= 0) {
        return;
    }

    $children = get_posts([
        'post_type'      => 'attachment',
        'post_parent'    => $post_id,
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    foreach ($children as $att_id) {
        $att_id = (int) $att_id;
        if ($att_id <= 0) {
            continue;
        }
        wp_update_post([
            'ID'          => $att_id,
            'post_parent' => 0,
        ]);
    }
}

/**
 * Trash e delete permanente: desanexa mídia antes de qualquer plugin apagar arquivos.
 */
function rs_project_protect_media_on_remove(int $post_id): void {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'project') {
        return;
    }

    rs_project_detach_child_attachments($post_id);
}

add_action('wp_trash_post', 'rs_project_protect_media_on_remove', 1);
add_action('before_delete_post', 'rs_project_protect_media_on_remove', 1);

/**
 * Bloqueia exclusão de attachment ainda referenciado por algum project.
 *
 * @param mixed   $check
 * @param WP_Post $post
 * @return mixed
 */
add_filter('pre_delete_attachment', function ($check, $post, $force_delete) {
    if ($check !== null) {
        return $check;
    }

    if (!($post instanceof WP_Post)) {
        return $check;
    }

    $attachment_id = (int) $post->ID;
    $parent_id = (int) $post->post_parent;

    if (!rs_attachment_used_by_other_projects($attachment_id, 0)) {
        // Mesmo sem outro project: se o parent é project, desanexa em vez de apagar
        // quando a exclusão veio do cascade do post (force via delete post).
        // Só bloqueamos se outro project usa — senão permitir limpeza normal da Library.
        return $check;
    }

    if ($parent_id > 0) {
        wp_update_post([
            'ID'          => $attachment_id,
            'post_parent' => 0,
        ]);
    }

    // Impede wp_delete_attachment (arquivo permanece na Media Library).
    return false;
}, 5, 3);
