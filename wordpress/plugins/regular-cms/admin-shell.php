<?php
/**
 * Admin shell (FASE 3) — menu agrupado + chrome visual + header contextual.
 * Não altera contratos REST / meta keys / save_post.
 */

if (defined('RS_ADMIN_SHELL_LOADED')) {
	return;
}
define('RS_ADMIN_SHELL_LOADED', true);

/**
 * CPTs sob "Conteúdo".
 *
 * @return list<string>
 */
function rs_admin_shell_content_types(): array {
	return [
		'project',
		'intro',
		'about',
		'projects-page',
		'capabilities',
		'education',
		'brand',
		'contact',
	];
}

/**
 * CPTs sob "Sistema".
 *
 * @return list<string>
 */
function rs_admin_shell_system_types(): array {
	return [
		'home-visual',
		'site-ui',
		'footer',
		'legal',
	];
}

/**
 * @return list<string>
 */
function rs_admin_shell_all_types(): array {
	return array_merge(rs_admin_shell_content_types(), rs_admin_shell_system_types());
}

/**
 * Agrupa CPTs nos parents rs-content / rs-system.
 */
add_filter('register_post_type_args', function (array $args, string $post_type): array {
	if (in_array($post_type, rs_admin_shell_content_types(), true)) {
		$args['show_in_menu'] = 'rs-content';
		unset($args['menu_position']);
	}

	if (in_array($post_type, rs_admin_shell_system_types(), true)) {
		$args['show_in_menu'] = 'rs-system';
		unset($args['menu_position']);
	}

	return $args;
}, 20, 2);

/** Parents do menu — cedo, antes dos CPTs anexarem submenus. */
add_action('admin_menu', function (): void {
	add_menu_page(
		'Conteúdo',
		'Conteúdo',
		'edit_posts',
		'rs-content',
		'rs_admin_shell_render_content_hub',
		'dashicons-edit-page',
		3
	);

	add_menu_page(
		'Sistema',
		'Sistema',
		'edit_posts',
		'rs-system',
		'rs_admin_shell_render_system_hub',
		'dashicons-admin-settings',
		58
	);

	add_submenu_page(
		'rs-system',
		'Menus do header',
		'Menus do header',
		'edit_theme_options',
		'nav-menus.php'
	);

	if (current_user_can('manage_options')) {
		add_submenu_page(
			'rs-system',
			'Design System',
			'Design System',
			'manage_options',
			'rs-design-system',
			'rs_admin_ds_render_preview_page'
		);
	}
}, 1);

/** Remove o submenu duplicado automático (= slug do parent). */
add_action('admin_menu', function (): void {
	remove_submenu_page('rs-content', 'rs-content');
	remove_submenu_page('rs-system', 'rs-system');
}, 999);

/** Separadores legados (CPTs flat) — não são mais necessários. */
add_action('admin_menu', function (): void {
	global $menu;
	if (!is_array($menu)) {
		return;
	}
	foreach ($menu as $position => $item) {
		if (!is_array($item)) {
			continue;
		}
		$slug = (string) ($item[2] ?? '');
		if (str_contains($slug, 'separatorrs-before-site-content') || str_contains($slug, 'separatorrs-after-site-content')) {
			unset($menu[$position]);
		}
	}
}, PHP_INT_MAX);

/**
 * @param list<array{type: string, label: string, icon: string, single: bool}> $items
 */
function rs_admin_shell_render_hub(string $title, string $desc, array $items): void {
	if (!current_user_can('edit_posts')) {
		return;
	}
	?>
	<div class="wrap rs-shell-hub">
		<header class="rs-ds-page-header">
			<div>
				<p class="rs-ds-breadcrumb">Regular CMS</p>
				<h1 class="rs-ds-page-title"><?php echo esc_html($title); ?></h1>
				<p class="rs-ds-page-desc"><?php echo esc_html($desc); ?></p>
			</div>
		</header>

		<div class="rs-shell-hub-grid">
			<?php foreach ($items as $item) : ?>
				<?php
				$url = function_exists('rs_dashboard_edit_url_for_type')
					? rs_dashboard_edit_url_for_type($item['type'], $item['single'])
					: admin_url('edit.php?post_type=' . rawurlencode($item['type']));
				?>
				<a class="rs-shell-hub-card" href="<?php echo esc_url($url); ?>">
					<span class="rs-shell-hub-card__icon dashicons <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></span>
					<span class="rs-shell-hub-card__body">
						<strong><?php echo esc_html($item['label']); ?></strong>
						<small><?php echo $item['single'] ? 'Editar conteúdo' : 'Abrir listagem'; ?></small>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

function rs_admin_shell_render_content_hub(): void {
	rs_admin_shell_render_hub(
		'Conteúdo',
		'Páginas e peças editoriais do site. Escolha um item para editar.',
		[
			['type' => 'project', 'label' => 'Projetos', 'icon' => 'dashicons-portfolio', 'single' => false],
			['type' => 'intro', 'label' => 'Intro', 'icon' => 'dashicons-text-page', 'single' => true],
			['type' => 'about', 'label' => 'Sobre Nós', 'icon' => 'dashicons-groups', 'single' => true],
			['type' => 'projects-page', 'label' => 'Página de projetos', 'icon' => 'dashicons-images-alt2', 'single' => true],
			['type' => 'capabilities', 'label' => 'Capacidades', 'icon' => 'dashicons-hammer', 'single' => true],
			['type' => 'education', 'label' => 'Educação', 'icon' => 'dashicons-welcome-learn-more', 'single' => true],
			['type' => 'brand', 'label' => 'Marcas', 'icon' => 'dashicons-awards', 'single' => false],
			['type' => 'contact', 'label' => 'Contato', 'icon' => 'dashicons-email', 'single' => true],
		]
	);
}

function rs_admin_shell_render_system_hub(): void {
	rs_admin_shell_render_hub(
		'Sistema',
		'Configurações de interface, visual, rodapé e políticas.',
		[
			['type' => 'home-visual', 'label' => 'Visual da home', 'icon' => 'dashicons-art', 'single' => true],
			['type' => 'site-ui', 'label' => 'Interface do site', 'icon' => 'dashicons-admin-generic', 'single' => true],
			['type' => 'footer', 'label' => 'Footer', 'icon' => 'dashicons-table-row-after', 'single' => true],
			['type' => 'legal', 'label' => 'Privacidade & Cookies', 'icon' => 'dashicons-privacy', 'single' => true],
		]
	);
}

/**
 * Contexto da tela atual para o header shell.
 *
 * @return array{group: string, title: string, subtitle: string}|null
 */
function rs_admin_shell_screen_context(): ?array {
	if (!is_admin()) {
		return null;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	$page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';

	if ($page === 'rs-content') {
		return ['group' => 'Regular CMS', 'title' => 'Conteúdo', 'subtitle' => 'Hub de páginas e projetos'];
	}
	if ($page === 'rs-system') {
		return ['group' => 'Regular CMS', 'title' => 'Sistema', 'subtitle' => 'Hub de configurações'];
	}
	if ($page === 'rs-design-system') {
		return null; // preview já tem header próprio
	}

	if (!$screen) {
		return null;
	}

	$post_type = (string) ($screen->post_type ?? '');
	if ($post_type === '' || !in_array($post_type, rs_admin_shell_all_types(), true)) {
		return null;
	}

	$obj = get_post_type_object($post_type);
	$label = $obj && !empty($obj->labels->name) ? (string) $obj->labels->name : $post_type;
	$group = in_array($post_type, rs_admin_shell_system_types(), true) ? 'Sistema' : 'Conteúdo';

	$subtitle = '';
	if ($screen->base === 'post') {
		$subtitle = $screen->action === 'add' ? 'Novo' : 'Editar';
	} elseif ($screen->base === 'edit') {
		$subtitle = 'Listagem';
	} elseif ($screen->base === 'edit-tags' || $screen->base === 'term') {
		$subtitle = 'Categorias';
		$label = 'Categorias de projeto';
	}

	return [
		'group'    => $group,
		'title'    => $label,
		'subtitle' => $subtitle,
	];
}

add_filter('admin_body_class', function (string $classes): string {
	if (rs_admin_shell_screen_context() !== null) {
		$classes .= ' rs-shell-screen';
	}

	$page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
	if ($page === 'rs-content' || $page === 'rs-system') {
		$classes .= ' rs-shell-hub-screen';
	}

	return $classes;
});

add_action('all_admin_notices', function (): void {
	$ctx = rs_admin_shell_screen_context();
	if ($ctx === null) {
		return;
	}

	$crumbs = array_filter([$ctx['group'], $ctx['title'], $ctx['subtitle']]);
	?>
	<div class="rs-shell-topbar" role="region" aria-label="Contexto da página">
		<div class="rs-shell-topbar__inner">
			<p class="rs-shell-topbar__crumb"><?php echo esc_html(implode(' / ', $crumbs)); ?></p>
			<h1 class="rs-shell-topbar__title"><?php echo esc_html($ctx['title']); ?></h1>
			<?php if ($ctx['subtitle'] !== '') : ?>
				<p class="rs-shell-topbar__sub"><?php echo esc_html($ctx['subtitle']); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}, 0);

add_action('admin_enqueue_scripts', function (): void {
	$base = plugin_dir_url(__FILE__);
	$ver = rs_plugin_version();

	wp_enqueue_style(
		'rs-admin-shell',
		$base . 'assets/rs-admin-shell.css',
		['rs-design-system'],
		$ver
	);
}, 2);
