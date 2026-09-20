<?php
/**
 * Leitura/gravação resiliente de meta arrays (evita corrupção de JSON via wp_unslash).
 */

if (defined('RS_META_STORAGE_LOADED')) {
    return;
}
define('RS_META_STORAGE_LOADED', true);

/**
 * Decodifica JSON de postmeta com recuperação de stripslashes.
 *
 * @return array<mixed>|null
 */
function rs_meta_decode_json_string(string $raw): ?array {
    $candidates = array_unique([$raw, wp_unslash($raw), stripslashes($raw)]);

    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || $candidate === '') {
            continue;
        }
        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $repaired = rs_meta_repair_stripslashed_json($raw);
    return is_array($repaired) ? $repaired : null;
}

/**
 * Recupera JSON gravado sem wp_slash (aspas de atributos HTML quebram o decode).
 *
 * @return array<mixed>|null
 */
function rs_meta_repair_stripslashed_json(string $raw): ?array {
    if ($raw === '' || ($raw[0] !== '{' && $raw[0] !== '[')) {
        return null;
    }

    $fixed = $raw;
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

    $fixed = str_replace(
        ['</p>n<p>', '</p>n</', '>n<p>', '</h1>n', '</h2>n', '</h3>n', '</li>n', '</ul>n', '</ol>n'],
        ['</p>\n<p>', '</p>\n</', '>\n<p>', '</h1>\n', '</h2>\n', '</h3>\n', '</li>\n', '</ul>\n', '</ol>\n'],
        $fixed
    );

    $decoded = json_decode($fixed, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Lê meta como array (formato novo) ou JSON legado.
 *
 * @return array<mixed>|null null se vazio / inválido
 */
function rs_meta_get_array(int $post_id, string $key): ?array {
    $raw = get_post_meta($post_id, $key, true);

    if (is_array($raw)) {
        return $raw;
    }

    if (is_string($raw) && $raw !== '') {
        return rs_meta_decode_json_string($raw);
    }

    return null;
}

/**
 * Grava meta como array (serialização nativa do WP — sem stripslashes no JSON).
 * Nunca usa delete+add_unique (no Hostinger a meta pode sumir se o add falhar).
 *
 * @param array<mixed> $value
 */
function rs_meta_update_array(int $post_id, string $key, array $value): void {
    global $wpdb;

    $existing_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC",
        $post_id,
        $key
    ));

    if (count($existing_ids) > 1) {
        array_shift($existing_ids);
        foreach ($existing_ids as $meta_id) {
            $wpdb->delete($wpdb->postmeta, ['meta_id' => (int) $meta_id], ['%d']);
        }
        wp_cache_delete($post_id, 'post_meta');
    }

    if (!metadata_exists('post', $post_id, $key)) {
        $added = add_post_meta($post_id, $key, $value, true);
        if ($added === false && !metadata_exists('post', $post_id, $key)) {
            // Fallback direto no DB (evita meta ausente após falha silenciosa).
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id'    => $post_id,
                    'meta_key'   => $key,
                    'meta_value' => maybe_serialize($value),
                ],
                ['%d', '%s', '%s']
            );
            wp_cache_delete($post_id, 'post_meta');
        }
        return;
    }

    update_post_meta($post_id, $key, $value);

    if (!metadata_exists('post', $post_id, $key)) {
        add_post_meta($post_id, $key, $value, true);
    }
}
