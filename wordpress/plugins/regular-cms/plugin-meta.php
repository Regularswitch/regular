<?php
/**
 * Metadados centralizados do Regular CMS.
 */

if (defined('RS_PLUGIN_META_LOADED')) {
    return;
}
define('RS_PLUGIN_META_LOADED', true);

define('RS_PLUGIN_VERSION', '1.5.16');
define('RS_PLUGIN_NAME', 'Regular CMS');
define('RS_PLUGIN_SLUG', 'regular-cms');
define('RS_PLUGIN_TEXT_DOMAIN', 'regular-cms');
define('RS_PLUGIN_FILE', __DIR__ . '/regular-cms.php');
define('RS_PLUGIN_DIR', __DIR__);
define('RS_PLUGIN_URL', plugin_dir_url(RS_PLUGIN_FILE));

function rs_plugin_version(): string {
    return RS_PLUGIN_VERSION;
}

function rs_plugin_name(): string {
    return RS_PLUGIN_NAME;
}

function rs_plugin_version_label(): string {
    return rs_plugin_name() . ' v' . rs_plugin_version();
}

function rs_plugin_version_markup(): string {
    return '<em>(' . esc_html(rs_plugin_version_label()) . ')</em>';
}
