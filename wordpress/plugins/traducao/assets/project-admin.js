/* global jQuery */
jQuery(function ($) {
	function initTabs() {
		var $tabs = $('.rs-project-tabs').first();
		if (!$tabs.length) {
			return;
		}

		$tabs.on('click', '.rs-project-tab', function (event) {
			event.preventDefault();
			var tab = $(this).data('tab');
			$tabs.find('.rs-project-tab').removeClass('is-active').attr('aria-selected', 'false');
			$(this).addClass('is-active').attr('aria-selected', 'true');
			$tabs.find('.rs-project-tabpanel').each(function () {
				var $panel = $(this);
				var active = $panel.data('tab') === tab;
				$panel.toggleClass('is-active', active);
				$panel.prop('hidden', !active);
			});

			if (tab === 'accordion' && typeof tinymce !== 'undefined') {
				window.setTimeout(function () {
					tinymce.editors.forEach(function (editor) {
						if (!editor || String(editor.id).indexOf('rs_project_accordion_body_') !== 0) {
							return;
						}
						try {
							editor.fire('ResizeEditor');
						} catch (err) {
							/* ignore */
						}
					});
				}, 50);
			}

			var slugInput = document.getElementById('post_name');
			var preview = document.getElementById('rs-project-slug-preview');
			if (slugInput && preview) {
				preview.textContent = slugInput.value.trim() || '…';
			}
		});

		var slugInput = document.getElementById('post_name');
		var preview = document.getElementById('rs-project-slug-preview');
		if (slugInput && preview) {
			slugInput.addEventListener('input', function () {
				preview.textContent = slugInput.value.trim() || '…';
			});
		}
	}

	function updateAccordionTabCount() {
		var count = $('#rs-project-accordion-list .rs-project-accordion-row').length;
		$('.rs-project-tab[data-tab="accordion"]').text('Acordeão (' + count + ')');
	}

	function openAccordionRow($row) {
		if (!$row || !$row.length) {
			return;
		}
		$('#rs-project-accordion-list .rs-project-accordion-row')
			.not($row)
			.removeClass('is-open')
			.find('.rs-project-accordion-toggle')
			.attr('aria-expanded', 'false');
		$row.addClass('is-open').find('.rs-project-accordion-toggle').attr('aria-expanded', 'true');
	}

	function initAccordionUi() {
		$(document).on('click', '.rs-project-accordion-toggle', function (event) {
			event.preventDefault();
			var $row = $(this).closest('.rs-project-accordion-row');
			if ($row.hasClass('is-open')) {
				$row.removeClass('is-open');
				$(this).attr('aria-expanded', 'false');
				return;
			}
			openAccordionRow($row);
		});

		$(document).on('input', '.rs-project-accordion-title', function () {
			var title = ($(this).val() || '').trim() || 'Seção do acordeão';
			$(this).closest('.rs-project-accordion-row').find('.rs-project-accordion-head-title').text(title);
		});
	}

	initTabs();
	initAccordionUi();
});