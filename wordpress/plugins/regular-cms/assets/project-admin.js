/* global jQuery, wp */
jQuery(function ($) {
	let slugManuallyEdited = false;
	let slugBindingsBound = false;

	function slugifyTitle(title) {
		const value = (title || '').trim();
		if (!value || /^auto[\s-]?draft$/i.test(value)) {
			return '';
		}

		if (window.wp && wp.url && typeof wp.url.cleanForSlug === 'function') {
			return wp.url.cleanForSlug(value);
		}

		return value
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	function bindSlugPreview() {
		const slugInput = document.getElementById('post_name');
		const preview = document.getElementById('rs-project-slug-preview');
		const titleInput = document.getElementById('title');
		if (!slugInput || !preview) {
			return;
		}

		const syncPreview = function () {
			preview.textContent = slugInput.value.trim() || '…';
		};

		const syncSlugFromTitle = function () {
			if (!titleInput || slugManuallyEdited) {
				return;
			}

			const nextSlug = slugifyTitle(titleInput.value);
			if (nextSlug === slugInput.value.trim()) {
				syncPreview();
				return;
			}

			slugInput.value = nextSlug;
			syncPreview();
		};

		syncPreview();

		if (slugBindingsBound) {
			syncSlugFromTitle();
			return;
		}

		slugBindingsBound = true;

		const initialSlug = slugInput.value.trim();
		if (initialSlug !== '') {
			slugManuallyEdited = true;
		}

		slugInput.addEventListener('input', function () {
			const value = slugInput.value.trim();
			slugManuallyEdited = value !== '';
			syncPreview();
		});

		if (titleInput) {
			titleInput.addEventListener('input', syncSlugFromTitle);
			titleInput.addEventListener('change', syncSlugFromTitle);
		}

		syncSlugFromTitle();
	}

	bindSlugPreview();

	$('[data-rs-tabs]').on('rs-metabox-tabchange', function () {
		bindSlugPreview();
	});
});
