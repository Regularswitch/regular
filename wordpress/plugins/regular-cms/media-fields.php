<?php
/**
 * Campo de upload de mídia reutilizável (meta boxes).
 * Apresentação DS — names/IDs/data-attrs intactos para o JS.
 */

if (defined('RS_MEDIA_FIELDS_LOADED')) {
    return;
}
define('RS_MEDIA_FIELDS_LOADED', true);

/**
 * @param 'image'|'video'|'media' $library
 */
function rs_render_media_field(
    string $name,
    string $label,
    int $attachment_id,
    ?string $field_id = null,
    bool $include_name = true,
    string $library = 'image'
): void {
    $field_id = $field_id ?? $name;
    $url = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : '';
    $mime = $attachment_id > 0 ? (string) get_post_mime_type($attachment_id) : '';
    $is_video = $mime !== '' && str_starts_with($mime, 'video/');
    $has_file = $url !== '';

    $pick_label = match ($library) {
        'video' => 'Selecionar vídeo',
        'media' => 'Selecionar mídia',
        default => 'Selecionar imagem',
    };
    $hint = match ($library) {
        'video' => 'MP4 ou vídeo da biblioteca',
        'media' => 'Imagem, GIF ou vídeo',
        default => 'JPG, PNG, GIF ou WebP',
    };
    $zone_class = 'rs-ds-dropzone rs-media-field' . ($has_file ? ' is-filled' : '');
    ?>
    <div class="<?php echo esc_attr($zone_class); ?>" data-rs-media-zone="<?php echo esc_attr($field_id); ?>">
        <?php if ($label !== '') : ?>
            <p class="rs-ds-label" style="align-self:stretch;text-align:left;margin:0 0 4px;"><?php echo esc_html($label); ?></p>
        <?php endif; ?>
        <input
            type="hidden"
            <?php if ($include_name) : ?>name="<?php echo esc_attr($name); ?>"<?php endif; ?>
            id="<?php echo esc_attr($field_id); ?>"
            value="<?php echo esc_attr((string) $attachment_id); ?>"
            data-rs-cap-image="1"
            data-rs-library="<?php echo esc_attr($library); ?>"
            data-rs-cleared="0"
        />
        <?php if ($include_name) : ?>
            <input type="hidden" name="<?php echo esc_attr($name); ?>_cleared" id="<?php echo esc_attr($field_id); ?>_cleared" value="0" />
        <?php endif; ?>

        <span class="rs-media-preview" data-target="<?php echo esc_attr($field_id); ?>">
            <?php if ($url && $is_video) : ?>
                <video src="<?php echo esc_url($url); ?>" class="rs-ds-dropzone__preview-media" muted playsinline controls></video>
            <?php elseif ($url) : ?>
                <img src="<?php echo esc_url($url); ?>" alt="" class="rs-ds-dropzone__preview-media" />
            <?php else : ?>
                <span class="rs-ds-dropzone__icon" aria-hidden="true">↑</span>
                <p class="rs-ds-dropzone__title"><?php echo esc_html($pick_label); ?></p>
                <p class="rs-ds-dropzone__hint">Clique para abrir a biblioteca</p>
                <p class="rs-ds-dropzone__meta"><?php echo esc_html($hint); ?></p>
            <?php endif; ?>
        </span>

        <p class="rs-ds-actions rs-ds-actions--row" style="margin-top:var(--rs-space-3);">
            <button
                type="button"
                class="button button-secondary rs-media-pick"
                data-target="<?php echo esc_attr($field_id); ?>"
                data-library="<?php echo esc_attr($library); ?>"
            ><?php echo esc_html($pick_label); ?></button>
            <button type="button" class="button rs-media-clear" data-target="<?php echo esc_attr($field_id); ?>"<?php echo $has_file ? '' : ' style="display:none;"'; ?>>Remover</button>
        </p>
    </div>
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
    function rsSyncMediaMirrorForSource(sourceId) {
        const el = document.getElementById(String(sourceId));
        if (!el) {
            return;
        }

        document.querySelectorAll('[data-rs-mirror-of="' + sourceId + '"]').forEach(function (mirror) {
            mirror.value = el.value || '0';
        });
        document.querySelectorAll('[data-rs-mirror-cleared-of="' + sourceId + '"]').forEach(function (mirror) {
            mirror.value = el.dataset.rsCleared === '1' ? '1' : '0';
        });
    }

    function rsSyncAllMediaMirrors() {
        document.querySelectorAll('[data-rs-mirror-of]').forEach(function (mirror) {
            rsSyncMediaMirrorForSource(mirror.getAttribute('data-rs-mirror-of'));
        });
    }

    function zoneFor(target) {
        return $('[data-rs-media-zone="' + target + '"]');
    }

    function setPreview(target, attachment) {
        const preview = $('.rs-media-preview[data-target="' + target + '"]');
        const clearBtn = $('.rs-media-clear[data-target="' + target + '"]');
        const zone = zoneFor(target);

        if (!attachment || !attachment.url) {
            const library = $('#' + target).data('rs-library') || 'image';
            const title = library === 'video' ? 'Selecionar vídeo' : (library === 'media' ? 'Selecionar mídia' : 'Selecionar imagem');
            const meta = library === 'video' ? 'MP4 ou vídeo da biblioteca' : (library === 'media' ? 'Imagem, GIF ou vídeo' : 'JPG, PNG, GIF ou WebP');
            preview.html(
                '<span class="rs-ds-dropzone__icon" aria-hidden="true">↑</span>' +
                '<p class="rs-ds-dropzone__title">' + title + '</p>' +
                '<p class="rs-ds-dropzone__hint">Clique para abrir a biblioteca</p>' +
                '<p class="rs-ds-dropzone__meta">' + meta + '</p>'
            );
            clearBtn.hide();
            zone.removeClass('is-filled');
            return;
        }

        const mime = attachment.mime || '';
        if (mime.indexOf('video/') === 0) {
            preview.html(
                '<video src="' + attachment.url + '" class="rs-ds-dropzone__preview-media" muted playsinline controls></video>'
            );
        } else {
            const thumb = (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url)
                || attachment.url;
            preview.html('<img src="' + thumb + '" alt="" class="rs-ds-dropzone__preview-media" />');
        }
        clearBtn.show();
        zone.addClass('is-filled');
    }

    function openPicker(button) {
        const target = $(button).data('target');
        const library = $(button).data('library') || $('#' + target).data('rs-library') || 'image';

        const frameOptions = {
            title: library === 'video' ? 'Selecionar vídeo' : (library === 'media' ? 'Selecionar mídia' : 'Selecionar imagem'),
            button: { text: 'Usar este arquivo' },
            multiple: false
        };

        if (library === 'image' || library === 'video') {
            frameOptions.library = { type: library };
        }

        const frame = wp.media(frameOptions);

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            const el = document.getElementById(String(target));
            if (el) {
                el.value = String(attachment.id);
                el.setAttribute('value', String(attachment.id));
                el.dataset.rsCleared = '0';
            }
            const cleared = document.getElementById(String(target) + '_cleared');
            if (cleared) {
                cleared.value = '0';
            }
            const valuePost = document.getElementById(String(target) + '_post');
            if (valuePost) {
                valuePost.value = String(attachment.id);
            }
            const clearedPost = document.getElementById(String(target) + '_cleared_post');
            if (clearedPost) {
                clearedPost.value = '0';
            }
            rsSyncMediaMirrorForSource(target);
            setPreview(target, attachment);
        });

        frame.open();
    }

    $(document).on('click', '.rs-media-pick, .rs-project-pick-media', function (event) {
        event.preventDefault();
        openPicker(this);
    });

    $(document).on('click', '.rs-ds-dropzone .rs-media-preview', function (event) {
        if ($(event.target).closest('video, a, button').length) {
            return;
        }
        const target = $(this).data('target');
        const pick = $('.rs-media-pick[data-target="' + target + '"]').get(0);
        if (pick) {
            openPicker(pick);
        }
    });

    $(document).on('click', '.rs-media-clear, .rs-project-clear-media', function (event) {
        event.preventDefault();
        const target = $(this).data('target');
        const el = document.getElementById(String(target));
        if (el) {
            el.value = '';
            el.setAttribute('value', '');
            el.dataset.rsCleared = '1';
        }
        const cleared = document.getElementById(String(target) + '_cleared');
        if (cleared) {
            cleared.value = '1';
        }
        const valuePost = document.getElementById(String(target) + '_post');
        if (valuePost) {
            valuePost.value = '0';
        }
        const clearedPost = document.getElementById(String(target) + '_cleared_post');
        if (clearedPost) {
            clearedPost.value = '1';
        }
        rsSyncMediaMirrorForSource(target);
        setPreview(target, null);
    });

    $('#post').on('submit', rsSyncAllMediaMirrors);
    $(document).on('click', '#publish, #save-post', function () {
        window.setTimeout(rsSyncAllMediaMirrors, 0);
    });
});
JS
        );
    });
}
