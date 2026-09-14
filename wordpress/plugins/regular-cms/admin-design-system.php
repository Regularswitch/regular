<?php
/**
 * Design System do Regular CMS (admin).
 * FASE 2: tokens light/dark + componentes base — sem redesign de telas de conteúdo.
 */

if (defined('RS_ADMIN_DS_LOADED')) {
	return;
}
define('RS_ADMIN_DS_LOADED', true);

/**
 * Cookie / localStorage key for theme preference.
 */
const RS_ADMIN_THEME_COOKIE = 'rs_admin_theme';

/**
 * @return 'light'|'dark'|'system'
 */
function rs_admin_theme_preference(): string {
	if (!empty($_COOKIE[RS_ADMIN_THEME_COOKIE])) {
		$value = sanitize_key((string) $_COOKIE[RS_ADMIN_THEME_COOKIE]);
		if (in_array($value, ['light', 'dark', 'system'], true)) {
			return $value;
		}
	}
	return 'system';
}

/**
 * Resolve effective theme for SSR class on <html>.
 *
 * @return 'light'|'dark'
 */
function rs_admin_theme_resolved(): string {
	$pref = rs_admin_theme_preference();
	if ($pref === 'light' || $pref === 'dark') {
		return $pref;
	}
	// system: default light for editors; JS can flip if prefers-color-scheme: dark
	return 'light';
}

add_action('admin_enqueue_scripts', function (): void {
	$base = plugin_dir_url(__FILE__);
	$ver = rs_plugin_version();

	wp_enqueue_style(
		'rs-design-system',
		$base . 'assets/rs-design-system.css',
		[],
		$ver
	);

	wp_enqueue_script(
		'rs-design-system',
		$base . 'assets/rs-design-system.js',
		[],
		$ver,
		true
	);

	wp_localize_script('rs-design-system', 'rsAdminDs', [
		'cookie' => RS_ADMIN_THEME_COOKIE,
		'pref'   => rs_admin_theme_preference(),
		'i18n'   => [
			'theme'  => 'Tema',
			'light'  => 'Claro',
			'dark'   => 'Escuro',
			'system' => 'Sistema',
		],
	]);
}, 1);

/** Classe no body para escopo do DS (não força visual do core ainda). */
add_filter('admin_body_class', function (string $classes): string {
	$theme = rs_admin_theme_resolved();
	return $classes . ' rs-ds-ready rs-theme-' . $theme;
});

/** Boot early: evita flash e aplica prefers-color-scheme quando pref=system. */
add_action('admin_head', function (): void {
	$pref = rs_admin_theme_preference();
	$cookie = RS_ADMIN_THEME_COOKIE;
	?>
	<script>
	(function () {
		try {
			var KEY = <?php echo wp_json_encode($cookie); ?>;
			var pref = <?php echo wp_json_encode($pref); ?>;
			var stored = '';
			try { stored = localStorage.getItem(KEY) || ''; } catch (e) {}
			if (stored === 'light' || stored === 'dark' || stored === 'system') pref = stored;
			var theme = pref;
			if (pref === 'system') {
				theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
			}
			var root = document.documentElement;
			root.classList.remove('rs-theme-light', 'rs-theme-dark');
			root.classList.add('rs-theme-' + theme);
			root.setAttribute('data-rs-theme', theme);
			root.setAttribute('data-rs-theme-pref', pref);
		} catch (e) {}
	})();
	</script>
	<?php
}, 0);

/** Toggle no admin bar. */
add_action('admin_bar_menu', function (WP_Admin_Bar $bar): void {
	if (!is_admin() || !current_user_can('edit_posts')) {
		return;
	}

	$bar->add_node([
		'id'    => 'rs-admin-theme',
		'title' => '<span class="ab-icon dashicons-before dashicons-admin-appearance" style="font-family:dashicons!important;margin-top:2px;"></span><span class="ab-label">Tema</span>',
		'href'  => '#',
		'meta'  => [
			'class' => 'rs-ds-theme-root',
			'title' => 'Tema do Regular CMS',
		],
	]);

	foreach (
		[
			'light'  => 'Claro',
			'dark'   => 'Escuro',
			'system' => 'Sistema',
		] as $value => $label
	) {
		$bar->add_node([
			'id'     => 'rs-admin-theme-' . $value,
			'parent' => 'rs-admin-theme',
			'title'  => $label,
			'href'   => '#rs-theme-' . $value,
			'meta'   => [
				'class' => 'rs-ds-theme-option',
			],
		]);
	}
}, 80);

/** Página de preview do Design System — registrada no menu Sistema (admin-shell). */

function rs_admin_ds_render_preview_page(): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	?>
	<div class="wrap rs-ds-preview">
		<header class="rs-ds-page-header">
			<div>
				<p class="rs-ds-breadcrumb">Sistema / Design System</p>
				<h1 class="rs-ds-page-title">Regular CMS · Design System</h1>
				<p class="rs-ds-page-desc">FASE 2 — tokens e componentes base. Telas de conteúdo ainda não foram redesenhadas.</p>
			</div>
			<div class="rs-ds-page-actions">
				<button type="button" class="rs-ds-btn rs-ds-btn--secondary rs-ds-theme-cycle">Alternar tema</button>
			</div>
		</header>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Cores (marca)</h2>
				<p class="rs-ds-card__subtitle">Paleta oficial Regular — tipografia do admin usa system UI.</p>
			</div>
			<div class="rs-ds-card__body">
				<div class="rs-ds-swatches">
					<div class="rs-ds-swatch"><span style="background:#232323"></span><code>#232323</code><small>Ink</small></div>
					<div class="rs-ds-swatch"><span style="background:#0067FF"></span><code>#0067FF</code><small>Accent</small></div>
					<div class="rs-ds-swatch"><span style="background:#6E7174"></span><code>#6E7174</code><small>Muted</small></div>
					<div class="rs-ds-swatch"><span style="background:#E7E5E5"></span><code>#E7E5E5</code><small>Line</small></div>
					<div class="rs-ds-swatch"><span style="background:#F9F9F9"></span><code>#F9F9F9</code><small>Canvas</small></div>
				</div>
			</div>
		</section>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Tokens semânticos</h2>
			</div>
			<div class="rs-ds-card__body rs-ds-token-grid">
				<div class="rs-ds-token"><i style="background:var(--rs-bg)"></i><span>--rs-bg</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-bg-subtle)"></i><span>--rs-bg-subtle</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-surface)"></i><span>--rs-surface</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-border)"></i><span>--rs-border</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-text)"></i><span>--rs-text</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-text-secondary)"></i><span>--rs-text-secondary</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-text-muted)"></i><span>--rs-text-muted</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-primary)"></i><span>--rs-primary</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-success)"></i><span>--rs-success</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-warning)"></i><span>--rs-warning</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-danger)"></i><span>--rs-danger</span></div>
				<div class="rs-ds-token"><i style="background:var(--rs-info)"></i><span>--rs-info</span></div>
			</div>
		</section>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Botões</h2>
			</div>
			<div class="rs-ds-card__body rs-ds-row">
				<button type="button" class="rs-ds-btn rs-ds-btn--primary">Atualizar</button>
				<button type="button" class="rs-ds-btn rs-ds-btn--secondary">Visualizar</button>
				<button type="button" class="rs-ds-btn rs-ds-btn--ghost">Mais opções</button>
				<button type="button" class="rs-ds-btn rs-ds-btn--danger">Excluir</button>
				<button type="button" class="rs-ds-btn rs-ds-btn--primary" disabled>Desabilitado</button>
			</div>
		</section>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Campos</h2>
			</div>
			<div class="rs-ds-card__body rs-ds-stack">
				<label class="rs-ds-field">
					<span class="rs-ds-label">Título SEO</span>
					<input class="rs-ds-input" type="text" placeholder="RegularSwitch | Estúdio de Design…" />
					<span class="rs-ds-help">Recomendado até 60 caracteres.</span>
				</label>
				<label class="rs-ds-field">
					<span class="rs-ds-label">Meta description</span>
					<textarea class="rs-ds-textarea" rows="3" placeholder="Descrição curta para buscadores…"></textarea>
					<span class="rs-ds-help">Recomendado até 155 caracteres.</span>
				</label>
				<label class="rs-ds-field">
					<span class="rs-ds-label">Categoria</span>
					<select class="rs-ds-select">
						<option>Identidade visual</option>
						<option>Rebranding</option>
						<option>Design generativo</option>
					</select>
				</label>
				<label class="rs-ds-check">
					<input type="checkbox" checked />
					<span>Exibir na home</span>
				</label>
				<label class="rs-ds-switch">
					<input type="checkbox" checked />
					<span class="rs-ds-switch__track" aria-hidden="true"></span>
					<span>LiquidBlob3D ativo</span>
				</label>
			</div>
		</section>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Tabs · Badge · Alert</h2>
			</div>
			<div class="rs-ds-card__body rs-ds-stack">
				<div class="rs-ds-tabs" role="tablist">
					<button type="button" class="rs-ds-tab is-active" role="tab">Conteúdo</button>
					<button type="button" class="rs-ds-tab" role="tab">SEO</button>
					<button type="button" class="rs-ds-tab" role="tab">Configurações</button>
				</div>
				<div class="rs-ds-row">
					<span class="rs-ds-badge rs-ds-badge--success">Publicado</span>
					<span class="rs-ds-badge rs-ds-badge--warning">Rascunho</span>
					<span class="rs-ds-badge rs-ds-badge--info">PT</span>
					<span class="rs-ds-badge">EN</span>
				</div>
				<div class="rs-ds-alert rs-ds-alert--success">✓ Salvo agora</div>
				<div class="rs-ds-alert rs-ds-alert--warning">Alterações não salvas</div>
				<div class="rs-ds-alert rs-ds-alert--danger">Erro ao salvar. Tente novamente.</div>
				<div class="rs-ds-alert rs-ds-alert--info">Idioma: Português · tradução EN incompleta</div>
			</div>
		</section>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Locale switcher</h2>
			</div>
			<div class="rs-ds-card__body">
				<div class="rs-ds-locale" role="tablist" aria-label="Idioma">
					<button type="button" class="rs-ds-locale__btn is-active" aria-pressed="true">
						<span class="rs-ds-locale__flag" aria-hidden="true">🇧🇷</span>
						Português
						<span class="rs-ds-badge rs-ds-badge--success">OK</span>
					</button>
					<button type="button" class="rs-ds-locale__btn" aria-pressed="false">
						<span class="rs-ds-locale__flag" aria-hidden="true">🇬🇧</span>
						English
						<span class="rs-ds-badge rs-ds-badge--warning">Vazio</span>
					</button>
				</div>
			</div>
		</section>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Upload / Dropzone</h2>
			</div>
			<div class="rs-ds-card__body">
				<div class="rs-ds-dropzone" tabindex="0">
					<div class="rs-ds-dropzone__icon" aria-hidden="true">↑</div>
					<p class="rs-ds-dropzone__title">Arraste uma imagem aqui</p>
					<p class="rs-ds-dropzone__hint">ou <button type="button" class="rs-ds-link-btn">Selecionar imagem</button></p>
					<p class="rs-ds-dropzone__meta">PNG, JPG ou WebP · até 5MB</p>
				</div>
			</div>
		</section>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Accordion / Section</h2>
			</div>
			<div class="rs-ds-card__body rs-ds-stack">
				<details class="rs-ds-accordion" open>
					<summary class="rs-ds-accordion__summary">Estratégia &amp; Narrativa</summary>
					<div class="rs-ds-accordion__body">
						<p class="rs-ds-text-secondary">Conteúdo da seção — nas telas reais os campos existentes serão preservados.</p>
					</div>
				</details>
				<details class="rs-ds-accordion">
					<summary class="rs-ds-accordion__summary">Perguntas frequentes</summary>
					<div class="rs-ds-accordion__body">
						<p class="rs-ds-text-secondary">FAQ items…</p>
					</div>
				</details>
				<details class="rs-ds-accordion">
					<summary class="rs-ds-accordion__summary">SEO</summary>
					<div class="rs-ds-accordion__body">
						<p class="rs-ds-text-secondary">Title, meta, preview SERP…</p>
					</div>
				</details>
			</div>
		</section>

		<section class="rs-ds-card">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">Empty / Loading</h2>
			</div>
			<div class="rs-ds-card__body rs-ds-row" style="align-items:stretch">
				<div class="rs-ds-empty">
					<p class="rs-ds-empty__title">Nenhum projeto ainda</p>
					<p class="rs-ds-empty__desc">Crie o primeiro projeto para aparecer na listagem.</p>
					<button type="button" class="rs-ds-btn rs-ds-btn--primary">+ Novo projeto</button>
				</div>
				<div class="rs-ds-loading" aria-busy="true">
					<span class="rs-ds-spinner" aria-hidden="true"></span>
					<span>Carregando…</span>
				</div>
			</div>
		</section>

		<section class="rs-ds-card rs-ds-serp">
			<div class="rs-ds-card__header">
				<h2 class="rs-ds-card__title">SEO · Preview Google</h2>
			</div>
			<div class="rs-ds-card__body">
				<div class="rs-ds-serp__preview">
					<p class="rs-ds-serp__url">regularswitch.com › PT › capabilities</p>
					<p class="rs-ds-serp__title">Capacidades | Branding, Identidade Visual e Design Digital</p>
					<p class="rs-ds-serp__desc">Criação de marca, identidade visual, rebranding, design digital, conteúdo e design generativo para marcas e instituições.</p>
				</div>
			</div>
		</section>
	</div>
	<?php
}
