/* global jQuery */
jQuery(function ($) {
	function initNoticeBell() {
		var $wrap = $('.wrap').first();
		if (!$wrap.length) {
			return;
		}

		if ($wrap.find('.rs-admin-notice-bell').length) {
			return;
		}

		var $notices = $wrap
			.children('.notice, .updated, .error, .update-nag')
			.add('#wpbody-content > .notice, #wpbody-content > .updated, #wpbody-content > .error')
			.not('.inline, .hidden, .rs-admin-notice-panel .notice');

		$notices = $notices.filter(function () {
			return !$(this).closest('.rs-admin-notice-panel').length;
		});

		if (!$notices.length) {
			return;
		}

		var count = $notices.length;
		var $bell = $(
			'<button type="button" class="rs-admin-notice-bell" aria-expanded="false" aria-controls="rs-admin-notice-panel" title="Avisos">' +
				'<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">' +
				'<path d="M12 22a2.2 2.2 0 0 0 2.2-2.2h-4.4A2.2 2.2 0 0 0 12 22Zm8-6.2V11a8 8 0 0 0-6.4-7.8V2.4a1.6 1.6 0 1 0-3.2 0v.8A8 8 0 0 0 4 11v4.8L2 18v1h20v-1l-2-2.2Z"/>' +
				'</svg>' +
				'<span class="rs-admin-notice-count">' +
				count +
				'</span>' +
				'<span class="screen-reader-text">Avisos (' +
				count +
				')</span>' +
				'</button>'
		);
		var $panel = $('<div id="rs-admin-notice-panel" class="rs-admin-notice-panel" hidden></div>');

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

		var $toast = $('<div class="rs-admin-toast" role="status"></div>').text(text);
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
});
