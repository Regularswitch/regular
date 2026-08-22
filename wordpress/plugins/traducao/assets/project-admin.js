/* global jQuery */
jQuery(function ($) {
	function initNoticeBell() {
		var $wrap = $('.wrap').first();
		if (!$wrap.length) {
			return;
		}

		var $notices = $wrap
			.children('.notice, .updated, .error, .update-nag')
			.add('#wpbody-content > .notice, #wpbody-content > .updated, #wpbody-content > .error')
			.not('.inline, .hidden, .rs-project-notice-panel .notice');

		$notices = $notices.filter(function () {
			return !$(this).closest('.rs-project-notice-panel').length;
		});

		if (!$notices.length) {
			return;
		}

		var count = $notices.length;
		var $bell = $(
			'<button type="button" class="rs-project-notice-bell" aria-expanded="false" aria-controls="rs-project-notice-panel" title="Avisos">' +
				'<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">' +
				'<path d="M12 22a2.2 2.2 0 0 0 2.2-2.2h-4.4A2.2 2.2 0 0 0 12 22Zm8-6.2V11a8 8 0 0 0-6.4-7.8V2.4a1.6 1.6 0 1 0-3.2 0v.8A8 8 0 0 0 4 11v4.8L2 18v1h20v-1l-2-2.2Z"/>' +
				'</svg>' +
				'<span class="rs-project-notice-count">' +
				count +
				'</span>' +
				'<span class="screen-reader-text">Avisos (' +
				count +
				')</span>' +
				'</button>'
		);
		var $panel = $('<div id="rs-project-notice-panel" class="rs-project-notice-panel" hidden></div>');

		$notices.detach().appendTo($panel);

		var $heading = $wrap.find('h1').first();
		if ($heading.length) {
			$heading.after($bell);
			$bell.after($panel);
		} else {
			$wrap.prepend($panel).prepend($bell);
		}

		$bell.on('click', function () {
			var willOpen = $panel.prop('hidden');
			$panel.prop('hidden', !willOpen);
			$bell.attr('aria-expanded', willOpen ? 'true' : 'false');
		});
	}

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

	function initSaveToast() {
		var params = new URLSearchParams(window.location.search);
		var message = params.get('message');
		var $msg = $('#message');
		if (!$msg.length) {
			return;
		}

		var successIds = ['1', '4', '6', '7', '8', '10'];
		var isSuccess =
			$msg.hasClass('notice-success') ||
			$msg.hasClass('updated') ||
			successIds.indexOf(String(message || '')) !== -1;
		if (!isSuccess) {
			return;
		}

		var text = $.trim($msg.find('p').first().text() || $msg.text());
		$msg.remove();
		if (!text) {
			return;
		}

		var $toast = $('<div class="rs-project-toast" role="status"></div>').text(text);
		$('body').append($toast);
		window.requestAnimationFrame(function () {
			$toast.addClass('is-visible');
		});
		window.setTimeout(function () {
			$toast.removeClass('is-visible');
			window.setTimeout(function () {
				$toast.remove();
			}, 280);
		}, 4000);
	}

	initSaveToast();
	initNoticeBell();
	initTabs();
	initAccordionUi();
});