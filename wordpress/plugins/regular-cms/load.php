<?php
/**
 * Bootstrap do Regular CMS — ordem de carregamento dos módulos.
 */

if (defined('RS_PLUGIN_LOADED')) {
    return;
}
define('RS_PLUGIN_LOADED', true);

$rs_plugin_dir = RS_PLUGIN_DIR;

// CPTs e tipos de conteúdo
require_once $rs_plugin_dir . '/custon_post.php';

// Campos por página / seção
require_once $rs_plugin_dir . '/intro-fields.php';
require_once $rs_plugin_dir . '/rich-text-fields.php';
require_once $rs_plugin_dir . '/media-fields.php';
require_once $rs_plugin_dir . '/meta-storage.php';
require_once $rs_plugin_dir . '/debug-log.php';
require_once $rs_plugin_dir . '/section-i18n.php';
require_once $rs_plugin_dir . '/footer-fields.php';
require_once $rs_plugin_dir . '/capabilities-fields.php';
require_once $rs_plugin_dir . '/page-heroes-fields.php';
require_once $rs_plugin_dir . '/section-hero-fields.php';
require_once $rs_plugin_dir . '/about-fields.php';
require_once $rs_plugin_dir . '/education-fields.php';
require_once $rs_plugin_dir . '/contact-fields.php';
require_once $rs_plugin_dir . '/legal-fields.php';
require_once $rs_plugin_dir . '/projects-page-fields.php';
require_once $rs_plugin_dir . '/site-ui-fields.php';
require_once $rs_plugin_dir . '/blob-visual-fields.php';
require_once $rs_plugin_dir . '/header-menus.php';

// Projetos (CPT + i18n + meta boxes)
require_once $rs_plugin_dir . '/project-cpt.php';
require_once $rs_plugin_dir . '/project-fields.php';
require_once $rs_plugin_dir . '/project-i18n.php';
require_once $rs_plugin_dir . '/project-media-protect.php';

// Admin, i18n e REST
require_once $rs_plugin_dir . '/hide-themerain-meta.php';
require_once $rs_plugin_dir . '/admin-dashboard.php';
require_once $rs_plugin_dir . '/content-repair.php';
require_once $rs_plugin_dir . '/metabox-ui.php';
require_once $rs_plugin_dir . '/sync-media-en-to-pt.php';
require_once $rs_plugin_dir . '/slug-language.php';
require_once $rs_plugin_dir . '/rest-translate.php';
require_once $rs_plugin_dir . '/rest-all-posts.php';
require_once $rs_plugin_dir . '/proxy.php';
require_once $rs_plugin_dir . '/mult-language.php';
require_once $rs_plugin_dir . '/redirect-front-to-login.php';
require_once $rs_plugin_dir . '/admin-bar-frontend.php';
