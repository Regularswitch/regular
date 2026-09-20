/**
 * Regular CMS Design System — theme preference (light / dark / system).
 */
(function () {
	'use strict';

	var KEY = (window.rsAdminDs && window.rsAdminDs.cookie) || 'rs_admin_theme';

	function readPref() {
		try {
			var stored = localStorage.getItem(KEY);
			if (stored === 'light' || stored === 'dark' || stored === 'system') return stored;
		} catch (e) {}
		return (window.rsAdminDs && window.rsAdminDs.pref) || 'system';
	}

	function resolveTheme(pref) {
		if (pref === 'light' || pref === 'dark') return pref;
		return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	}

	function persist(pref) {
		try {
			localStorage.setItem(KEY, pref);
		} catch (e) {}
		var maxAge = 60 * 60 * 24 * 365;
		document.cookie = KEY + '=' + encodeURIComponent(pref) + '; path=/; max-age=' + maxAge + '; SameSite=Lax';
	}

	function apply(pref) {
		var theme = resolveTheme(pref);
		var root = document.documentElement;
		var body = document.body;

		root.classList.remove('rs-theme-light', 'rs-theme-dark');
		root.classList.add('rs-theme-' + theme);
		root.setAttribute('data-rs-theme', theme);
		root.setAttribute('data-rs-theme-pref', pref);

		if (body) {
			body.classList.remove('rs-theme-light', 'rs-theme-dark');
			body.classList.add('rs-theme-' + theme);
		}

		persist(pref);
		syncAdminBar(pref);
	}

	function syncAdminBar(pref) {
		['light', 'dark', 'system'].forEach(function (value) {
			var node = document.getElementById('wp-admin-bar-rs-admin-theme-' + value);
			if (!node) return;
			if (value === pref) node.classList.add('current-menu-item', 'rs-ds-theme-current');
			else node.classList.remove('current-menu-item', 'rs-ds-theme-current');
		});
	}

	function cycle() {
		var order = ['light', 'dark', 'system'];
		var pref = readPref();
		var next = order[(order.indexOf(pref) + 1) % order.length];
		apply(next);
	}

	function bind() {
		document.addEventListener('click', function (event) {
			var target = event.target;
			if (!(target instanceof Element)) return;

			var option = target.closest('#wp-admin-bar-rs-admin-theme-light, #wp-admin-bar-rs-admin-theme-dark, #wp-admin-bar-rs-admin-theme-system');
			if (option) {
				event.preventDefault();
				var id = option.id || '';
				var value = id.replace('wp-admin-bar-rs-admin-theme-', '');
				if (value === 'light' || value === 'dark' || value === 'system') apply(value);
				return;
			}

			if (target.closest('.rs-ds-theme-cycle')) {
				event.preventDefault();
				cycle();
			}

			var root = target.closest('#wp-admin-bar-rs-admin-theme > .ab-item');
			if (root && target.closest('#wp-admin-bar-rs-admin-theme') && !option) {
				// let WP open the submenu; no-op
			}
		});

		var media = window.matchMedia('(prefers-color-scheme: dark)');
		var onScheme = function () {
			if (readPref() === 'system') apply('system');
		};
		if (typeof media.addEventListener === 'function') media.addEventListener('change', onScheme);
		else if (typeof media.addListener === 'function') media.addListener(onScheme);

		apply(readPref());
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}
})();
