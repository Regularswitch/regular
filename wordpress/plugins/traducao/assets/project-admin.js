/* global jQuery */
jQuery(function ($) {
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
});
