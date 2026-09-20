<?php
/**
 * Helpers de UI do Design System (admin).
 * Só apresentação — não altera names/nonces/save.
 */

if (defined('RS_UI_HELPERS_LOADED')) {
	return;
}
define('RS_UI_HELPERS_LOADED', true);

/**
 * Abre card/seção DS.
 */
function rs_ds_card_open(string $title = '', string $subtitle = '', string $extra_class = ''): void {
	$class = trim('rs-ds-card ' . $extra_class);
	echo '<section class="' . esc_attr($class) . '">';
	if ($title !== '' || $subtitle !== '') {
		echo '<div class="rs-ds-card__header">';
		if ($title !== '') {
			echo '<h2 class="rs-ds-card__title">' . esc_html($title) . '</h2>';
		}
		if ($subtitle !== '') {
			echo '<p class="rs-ds-card__subtitle">' . esc_html($subtitle) . '</p>';
		}
		echo '</div>';
	}
	echo '<div class="rs-ds-card__body">';
}

function rs_ds_card_close(): void {
	echo '</div></section>';
}

/**
 * Abre fieldset estilizado (seção interna).
 */
function rs_ds_fieldset_open(string $legend, string $extra_class = ''): void {
	$class = trim('rs-ds-fieldset ' . $extra_class);
	echo '<fieldset class="' . esc_attr($class) . '">';
	echo '<legend class="rs-ds-fieldset__legend">' . esc_html($legend) . '</legend>';
}

function rs_ds_fieldset_close(): void {
	echo '</fieldset>';
}

function rs_ds_help(string $text): void {
	echo '<p class="rs-ds-help">' . esc_html($text) . '</p>';
}

function rs_ds_alert(string $text, string $tone = 'info'): void {
	$tone = in_array($tone, ['info', 'success', 'warning', 'danger'], true) ? $tone : 'info';
	echo '<div class="rs-ds-alert rs-ds-alert--' . esc_attr($tone) . '">' . esc_html($text) . '</div>';
}

/**
 * Tabs EN/PT — mantém data-rs-tabs / classes rs-metabox-* para o JS existente.
 *
 * @param array<string, string> $tabs map key => label
 */
function rs_ds_locale_tabs_open(
	array $tabs = ['en' => 'English', 'pt' => 'Português'],
	string $active = 'en',
	string $aria_label = 'Idioma'
): void {
	echo '<div class="rs-metabox-tabs rs-ds-locale-tabs" data-rs-tabs>';
	echo '<div class="rs-metabox-tablist rs-ds-locale" role="tablist" aria-label="' . esc_attr($aria_label) . '">';
	foreach ($tabs as $key => $label) {
		$is_active = $key === $active;
		echo '<button type="button" class="rs-metabox-tab rs-ds-locale__btn' . ($is_active ? ' is-active' : '') . '"';
		echo ' role="tab" aria-selected="' . ($is_active ? 'true' : 'false') . '" data-tab="' . esc_attr($key) . '">';
		if ($key === 'pt') {
			echo '<span class="rs-ds-locale__flag" aria-hidden="true">🇧🇷</span>';
		} elseif ($key === 'en') {
			echo '<span class="rs-ds-locale__flag" aria-hidden="true">🇬🇧</span>';
		}
		echo esc_html($label);
		echo '</button>';
	}
	echo '</div>';
}

function rs_ds_locale_panel_open(string $locale, bool $active = false): void {
	echo '<div class="rs-metabox-tabpanel' . ($active ? ' is-active' : '') . '" data-tab="' . esc_attr($locale) . '" role="tabpanel"';
	if (!$active) {
		echo ' hidden';
	}
	echo '>';
}

function rs_ds_locale_panel_close(): void {
	echo '</div>';
}

function rs_ds_locale_tabs_close(): void {
	echo '</div>';
}
