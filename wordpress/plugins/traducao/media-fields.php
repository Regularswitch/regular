<?php
/**
 * Campo de upload de mídia reutilizável (meta boxes).
 */

if (defined('RS_MEDIA_FIELDS_LOADED')) {
    return;
}
define('RS_MEDIA_FIELDS_LOADED', true);

function rs_render_media_field(string $name, string $label, int $attachment_id, ?string $field_id = null, bool $include_name = true): void {
    $field_id = $field_id ?? $name;
    $url = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : '';
    ?>
    <p class="rs-media-field" style="margin:0 0 14px;">
        <label style="display:block;font-weight:500;margin-bottom:6px;"><?php echo esc_html($label); ?></label>
        <input
            type="hidden"
            <?php if ($include_name) : ?>name="<?php echo esc_attr($name); ?>"<?php endif; ?>
            id="<?php echo esc_attr($field_id); ?>"
            value="<?php echo esc_attr((string) $attachment_id); ?>"
            data-rs-cap-image="1"
        />
        <button type="button" class="button rs-media-pick" data-target="<?php echo esc_attr($field_id); ?>">Selecionar imagem</button>
        <button type="button" class="button rs-media-clear" data-target="<?php echo esc_attr($field_id); ?>">Remover</button>
        <span class="rs-media-preview" data-target="<?php echo esc_attr($field_id); ?>" style="display:block;margin-top:8px;">
            <?php if ($url) : ?>
                <img src="<?php echo esc_url($url); ?>" alt="" style="max-width:220px;height:auto;border-radius:4px;" />
            <?php endif; ?>
        </span>
    </p>
    <?php
}

function rs_enqueue_admin_media_picker(array $post_types): void {
    add_action('admin_enqueue_scripts', function (string $hook) use ($post_types) {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || !in_array($screen->post_type, $post_types, true)) {
            return;
        }

        wp_enqueue_media();

        static $script_added = false;
        if ($script_added) {
            return;
        }
        $script_added = true;

        wp_add_inline_script('jquery', <<<'JS'
jQuery(function ($) {
    function setPreview(target, attachment) {
        const preview = $('.rs-media-preview[data-target="' + target + '"]');
        if (!attachment || !attachment.url) {
            preview.empty();
            return;
        }
        preview.html('<img src="' + attachment.url + '" alt="" style="max-width:220px;height:auto;border-radius:4px;" />');
    }

    $(document).on('click', '.rs-media-pick, .rs-project-pick-media', function (event) {
        event.preventDefault();
        const target = $(this).data('target');
        const frame = wp.media({
            title: 'Selecionar imagem',
            button: { text: 'Usar esta imagem' },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#' + target).val(attachment.id);
            setPreview(target, attachment);
        });

        frame.open();
    });

    $(document).on('click', '.rs-media-clear, .rs-project-clear-media', function (event) {
        event.preventDefault();
        const target = $(this).data('target');
        $('#' + target).val('');
        setPreview(target, null);
    });
});
JS
        );
    });
}
