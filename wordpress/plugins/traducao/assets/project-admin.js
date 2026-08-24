/* global jQuery */
jQuery(function ($) {
	function bindSlugPreview() {
		const slugInput = document.getElementById('post_name');
		const preview = document.getElementById('rs-project-slug-preview');
		if (!slugInput || !preview) {
			return;
		}

		const sync = function () {
			preview.textContent = slugInput.value.trim() || '…';
		};

		slugInput.addEventListener('input', sync);
		sync();
	}

	bindSlugPreview();

	$('[data-rs-tabs]').on('rs-metabox-tabchange', function () {
		bindSlugPreview();
	});
});
