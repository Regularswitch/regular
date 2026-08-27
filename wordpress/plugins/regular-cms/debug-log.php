<?php
/**
 * Log de diagnóstico do Regular CMS (admin + debug.log do WordPress).
 *
 * Ativar no staging (escolha uma):
 * 1. Ferramentas → Regular CMS Log → marcar "Registrar no admin"
 * 2. wp-config.php: define('RS_CMS_DEBUG', true);
 * 3. wp-config.php: define('WP_DEBUG', true); define('WP_DEBUG_LOG', true);
 */

if (defined('RS_CMS_DEBUG_LOG_LOADED')) {
    return;
}
define('RS_CMS_DEBUG_LOG_LOADED', true);

const RS_CMS_DEBUG_LOG_OPTION = 'rs_cms_debug_log';
const RS_CMS_DEBUG_ENABLED_OPTION = 'rs_cms_debug_enabled';
const RS_CMS_DEBUG_LOG_MAX = 150;

function rs_cms_debug_enabled(): bool {
    if (defined('RS_CMS_DEBUG') && RS_CMS_DEBUG) {
        return true;
    }

    if ((bool) get_option(RS_CMS_DEBUG_ENABLED_OPTION, false)) {
        return true;
    }

    return defined('WP_DEBUG') && WP_DEBUG
        && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
}

/**
 * @param array<string, mixed> $context
 */
function rs_cms_log(string $event, array $context = [], string $level = 'info'): void {
    if (!rs_cms_debug_enabled()) {
        return;
    }

    $entry = [
        't' => time(),
        'e' => $event,
        'l' => $level,
        'c' => $context,
        'u' => function_exists('get_current_user_id') ? (int) get_current_user_id() : 0,
    ];

    $line = '[Regular CMS][' . strtoupper($level) . '] ' . $event;
    if ($context !== []) {
        $json = wp_json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $line .= ' ' . (is_string($json) ? $json : '{}');
    }
    error_log($line);

    $log = get_option(RS_CMS_DEBUG_LOG_OPTION, []);
    if (!is_array($log)) {
        $log = [];
    }

    array_unshift($log, $entry);
    if (count($log) > RS_CMS_DEBUG_LOG_MAX) {
        $log = array_slice($log, 0, RS_CMS_DEBUG_LOG_MAX);
    }

    update_option(RS_CMS_DEBUG_LOG_OPTION, $log, false);
}

function rs_cms_count_request_inputs(): int {
    if (!isset($_POST) || !is_array($_POST)) {
        return 0;
    }

    $count = 0;
    $stack = [$_POST];
    while ($stack !== []) {
        $chunk = array_pop($stack);
        if (!is_array($chunk)) {
            continue;
        }
        foreach ($chunk as $value) {
            ++$count;
            if (is_array($value)) {
                $stack[] = $value;
            }
        }
    }

    return $count;
}

/**
 * @return array<string, mixed>
 */
function rs_cms_project_save_snapshot(int $post_id, array $data): array {
    $shared = is_array($data['shared'] ?? null) ? $data['shared'] : [];
    $gallery_csv = trim((string) ($shared['gallery_ids'] ?? ''));
    $en_acc = is_array($data['locales']['en']['accordion'] ?? null) ? $data['locales']['en']['accordion'] : [];
    $pt_acc = is_array($data['locales']['pt']['accordion'] ?? null) ? $data['locales']['pt']['accordion'] : [];

    return [
        'post_id'      => $post_id,
        'hero_id'      => (int) ($shared['hero_id'] ?? 0),
        'logo_id'      => (int) ($shared['logo_id'] ?? 0),
        'gallery'      => $gallery_csv === '' ? 0 : count(array_filter(explode(',', $gallery_csv))),
        'accordion_en' => count($en_acc),
        'accordion_pt' => count($pt_acc),
        'featured'     => !empty($shared['featured_home']) ? 1 : 0,
    ];
}

add_action('admin_menu', function (): void {
    add_management_page(
        'Regular CMS Log',
        'Regular CMS Log',
        'manage_options',
        'rs-cms-debug-log',
        'rs_cms_debug_log_render_page'
    );
});

function rs_cms_debug_log_render_page(): void {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Sem permissão.', 'regular-cms'));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rs_cms_debug_action'])) {
        check_admin_referer('rs_cms_debug_log');

        if ((string) $_POST['rs_cms_debug_action'] === 'toggle') {
            $enabled = !empty($_POST['rs_cms_debug_enabled']);
            update_option(RS_CMS_DEBUG_ENABLED_OPTION, $enabled ? 1 : 0, false);
            rs_cms_log('debug.toggle', ['enabled' => $enabled ? 1 : 0]);
            echo '<div class="notice notice-success"><p>Log ' . ($enabled ? 'ativado' : 'desativado') . '.</p></div>';
        }

        if ((string) $_POST['rs_cms_debug_action'] === 'clear') {
            delete_option(RS_CMS_DEBUG_LOG_OPTION);
            rs_cms_log('debug.clear', []);
            echo '<div class="notice notice-success"><p>Log limpo.</p></div>';
        }
    }

    $enabled_option = (bool) get_option(RS_CMS_DEBUG_ENABLED_OPTION, false);
    $active = rs_cms_debug_enabled();
    $log = get_option(RS_CMS_DEBUG_LOG_OPTION, []);
    if (!is_array($log)) {
        $log = [];
    }

    $max_input = (int) ini_get('max_input_vars');
    $plugin_ver = function_exists('rs_plugin_version') ? rs_plugin_version() : '?';
    $debug_log_path = WP_CONTENT_DIR . '/debug.log';
    $debug_log_exists = is_readable($debug_log_path);

    ?>
    <div class="wrap">
        <h1>Regular CMS — Log de diagnóstico</h1>

        <p>Versão do plugin: <strong><?php echo esc_html($plugin_ver); ?></strong>
            · PHP <?php echo esc_html(PHP_VERSION); ?>
            · <code>max_input_vars=<?php echo esc_html((string) $max_input); ?></code>
            · Log ativo: <strong><?php echo $active ? 'sim' : 'não'; ?></strong></p>

        <div class="notice notice-info inline" style="padding:12px 16px;max-width:960px;">
            <p><strong>Como ativar no staging (Hostinger)</strong></p>
            <ol style="margin-left:1.2em;">
                <li>Marque abaixo <em>Registrar eventos no admin</em> e salve — funciona sem editar arquivos.</li>
                <li>Ou em <code>wp-config.php</code> (antes de <code>/* That's all */</code>):<br>
                    <code style="display:block;margin:8px 0;padding:8px;background:#f6f7f7;">define('RS_CMS_DEBUG', true);</code>
                    ou o log nativo do WordPress:<br>
                    <code style="display:block;margin:8px 0;padding:8px;background:#f6f7f7;">define('WP_DEBUG', true);<br>define('WP_DEBUG_LOG', true);<br>define('WP_DEBUG_DISPLAY', false);</code>
                </li>
                <li>Arquivo do WP: <code><?php echo esc_html($debug_log_path); ?></code>
                    <?php echo $debug_log_exists ? '(existe — filtre por <code>[Regular CMS]</code>)' : '(ainda não criado)'; ?>
                </li>
            </ol>
        </div>

        <form method="post" style="margin:16px 0;">
            <?php wp_nonce_field('rs_cms_debug_log'); ?>
            <input type="hidden" name="rs_cms_debug_action" value="toggle" />
            <label>
                <input type="checkbox" name="rs_cms_debug_enabled" value="1" <?php checked($enabled_option); ?> />
                Registrar eventos no admin (recomendado no staging)
            </label>
            <?php submit_button('Salvar', 'primary', 'submit', false); ?>
        </form>

        <form method="post" style="margin:0 0 24px;">
            <?php wp_nonce_field('rs_cms_debug_log'); ?>
            <input type="hidden" name="rs_cms_debug_action" value="clear" />
            <?php submit_button('Limpar log', 'secondary', 'submit', false); ?>
        </form>

        <h2>Últimos eventos (<?php echo count($log); ?>)</h2>
        <?php if ($log === []) : ?>
            <p>Nenhum evento ainda. Edite/salve um projeto com o log ativo.</p>
        <?php else : ?>
            <table class="widefat striped" style="max-width:1200px;">
                <thead>
                    <tr>
                        <th style="width:150px;">Quando</th>
                        <th style="width:70px;">Nível</th>
                        <th style="width:220px;">Evento</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($log as $row) :
                    if (!is_array($row)) {
                        continue;
                    }
                    $ts = (int) ($row['t'] ?? 0);
                    $when = $ts > 0 ? wp_date('Y-m-d H:i:s', $ts) : '—';
                    $level = esc_html((string) ($row['l'] ?? 'info'));
                    $event = esc_html((string) ($row['e'] ?? ''));
                    $ctx = $row['c'] ?? [];
                    $json = is_array($ctx) ? wp_json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '{}';
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($when); ?></code></td>
                        <td><?php echo $level; ?></td>
                        <td><code><?php echo $event; ?></code></td>
                        <td><pre style="margin:0;white-space:pre-wrap;font-size:11px;max-height:120px;overflow:auto;"><?php echo esc_html(is_string($json) ? $json : '{}'); ?></pre></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

add_action('admin_notices', function (): void {
    if (!rs_cms_debug_enabled() || !current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'project') {
        return;
    }

    $url = admin_url('tools.php?page=rs-cms-debug-log');
    echo '<div class="notice notice-info is-dismissible"><p><strong>Regular CMS debug</strong> — '
        . '<a href="' . esc_url($url) . '">Ver log de saves</a> (Ferramentas → Regular CMS Log).</p></div>';
});
