<?php
/**
 * Campos editáveis do CPT intro (meta box + REST via content/excerpt).
 */

if (defined('RS_INTRO_FIELDS_LOADED')) {
    return;
}
define('RS_INTRO_FIELDS_LOADED', true);

function rs_intro_get_fields(int $post_id): array {
    $post = get_post($post_id);
    if (!$post) {
        return ['headline' => '', 'body' => ''];
    }

    return [
        'headline' => (string) $post->post_content,
        'body'     => (string) $post->post_excerpt,
    ];
}

function rs_intro_save_fields(int $post_id, string $headline, string $body): void {
    global $wpdb;

    $post = get_post($post_id);
    if (!$post) {
        return;
    }

    if ($post->post_content === $headline && $post->post_excerpt === $body) {
        return;
    }

    // Atualização direta evita loop infinito no hook save_post_intro.
    $wpdb->update(
        $wpdb->posts,
        [
            'post_content' => $headline,
            'post_excerpt' => $body,
        ],
        ['ID' => $post_id],
        ['%s', '%s'],
        ['%d']
    );

    clean_post_cache($post_id);
}

add_action('add_meta_boxes_intro', function () {
    add_meta_box(
        'rs_intro_fields',
        'Conteúdo da Intro (home)',
        'rs_intro_render_meta_box',
        'intro',
        'normal',
        'high'
    );

    remove_meta_box('postexcerpt', 'intro', 'normal');
}, 10);

function rs_intro_render_meta_box(WP_Post $post): void {
    wp_nonce_field('rs_intro_save', 'rs_intro_nonce');
    $fields = rs_intro_get_fields($post->ID);
    ?>
    <p style="margin-top:0;color:#646970;">
        Estes campos aparecem na seção de texto grande abaixo do hero na home.
        Use <code>&lt;strong&gt;texto&lt;/strong&gt;</code> para destacar palavras em negrito.
    </p>

    <fieldset style="margin:0 0 16px;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">
        <legend style="font-weight:600;padding:0 6px;"><strong>Título grande (headline)</strong></legend>
        <textarea
            id="rs_intro_headline"
            name="rs_intro_headline"
            style="width:100%;min-height:120px;font-family:monospace;font-size:13px;"
        ><?php echo esc_textarea($fields['headline']); ?></textarea>
        <p style="margin:8px 0 0;color:#646970;font-size:12px;">
            Exemplo EN: <code>Creating &lt;strong&gt;visual identities&lt;/strong&gt; and ...</code>
        </p>
    </fieldset>

    <fieldset style="margin:0;padding:12px 14px;border:1px solid #dcdcde;border-radius:4px;">
        <legend style="font-weight:600;padding:0 6px;"><strong>Parágrafo abaixo (body)</strong></legend>
        <textarea
            id="rs_intro_body"
            name="rs_intro_body"
            style="width:100%;min-height:100px;font-family:monospace;font-size:13px;"
        ><?php echo esc_textarea($fields['body']); ?></textarea>
        <p style="margin:8px 0 0;color:#646970;font-size:12px;">
            Texto menor abaixo do título. Pode usar <code>&lt;p&gt;...&lt;/p&gt;</code> ou só o texto.
        </p>
    </fieldset>
    <?php
}

add_action('save_post_intro', function (int $post_id) {
    if (!isset($_POST['rs_intro_nonce']) || !wp_verify_nonce($_POST['rs_intro_nonce'], 'rs_intro_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $headline = isset($_POST['rs_intro_headline'])
        ? wp_kses_post(wp_unslash($_POST['rs_intro_headline']))
        : '';
    $body = isset($_POST['rs_intro_body'])
        ? wp_kses_post(wp_unslash($_POST['rs_intro_body']))
        : '';

    rs_intro_save_fields($post_id, $headline, $body);
});
