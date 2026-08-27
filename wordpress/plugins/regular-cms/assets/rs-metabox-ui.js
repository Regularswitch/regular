/* global jQuery */
(function ($, window) {
	'use strict';

	function parseEditorIds($item) {
		var raw = $item.attr('data-rs-editor-ids') || '';
		return raw
			.split(',')
			.map(function (part) {
				return part.trim();
			})
			.filter(Boolean);
	}

	function resizeEditors(ids) {
		if (!ids || !ids.length || typeof tinymce === 'undefined') {
			return;
		}
		ids.forEach(function (id) {
			var editor = tinymce.get(id);
			if (!editor) {
				return;
			}
			try {
				editor.fire('ResizeEditor');
			} catch (err) {
				/* ignore */
			}
		});
	}

	function initTabs(root) {
		var $root = $(root);
		if (!$root.length || $root.data('rsTabsInit')) {
			return $root;
		}
		$root.data('rsTabsInit', true);

		$root.on('click', '.rs-metabox-tab', function (event) {
			event.preventDefault();
			var tab = $(this).data('tab');
			$root.find('.rs-metabox-tab').removeClass('is-active').attr('aria-selected', 'false');
			$(this).addClass('is-active').attr('aria-selected', 'true');
			$root.find('.rs-metabox-tabpanel').each(function () {
				var $panel = $(this);
				var active = $panel.data('tab') === tab;
				$panel.toggleClass('is-active', active);
				$panel.prop('hidden', !active);
			});
			$root.trigger('rs-metabox-tabchange', [tab]);
		});

		return $root;
	}

	function initAccordion(root, options) {
		options = $.extend(
			{
				listSelector: '[data-rs-accordion-list]',
				itemSelector: '.rs-metabox-accordion-item',
				toggleSelector: '.rs-metabox-accordion-toggle',
				titleInputSelector: '.rs-metabox-accordion-title',
				headTitleSelector: '.rs-metabox-accordion-head-title',
				dragSelector: '.rs-metabox-accordion-drag',
				removeSelector: '.rs-metabox-accordion-remove',
				sortable: true,
				singleOpen: true,
				defaultTitle: 'Seção',
				onExpand: null,
				onCollapse: null,
				onRemove: null,
				onSortUpdate: null,
			},
			options || {}
		);

		var $root = $(root);
		if (!$root.length || $root.data('rsAccordionInit')) {
			return { $root: $root, options: options };
		}
		$root.data('rsAccordionInit', true);

		// Lista explícita (educação/capabilities) ou o próprio root (footer/contact/legal).
		var $list = $root.find(options.listSelector).first();
		if (!$list.length) {
			$list = $root;
			options.sortable = false;
		}

		function openItem($item) {
			if (!$item || !$item.length) {
				return;
			}
			if (options.singleOpen) {
				$list.find(options.itemSelector).not($item).each(function () {
					var $other = $(this);
					if (!$other.hasClass('is-open')) {
						return;
					}
					$other.removeClass('is-open').find(options.toggleSelector).attr('aria-expanded', 'false');
					if (typeof options.onCollapse === 'function') {
						options.onCollapse($other, parseEditorIds($other));
					}
				});
			}
			$item.addClass('is-open').find(options.toggleSelector).attr('aria-expanded', 'true');
			if (typeof options.onExpand === 'function') {
				options.onExpand($item, parseEditorIds($item));
			} else {
				resizeEditors(parseEditorIds($item));
				// TinyMCE dentro de painéis que estavam com height 0 precisa de refresh.
				window.setTimeout(function () {
					resizeEditors(
						$item
							.find('textarea.wp-editor-area')
							.map(function () {
								return this.id;
							})
							.get()
					);
				}, 50);
			}
		}

		$root.on('click', options.toggleSelector, function (event) {
			event.preventDefault();
			var $item = $(this).closest(options.itemSelector);
			if ($item.hasClass('is-open')) {
				$item.removeClass('is-open');
				$(this).attr('aria-expanded', 'false');
				if (typeof options.onCollapse === 'function') {
					options.onCollapse($item, parseEditorIds($item));
				}
				return;
			}
			openItem($item);
		});

		$root.on('input', options.titleInputSelector, function () {
			var title = ($(this).val() || '').trim() || options.defaultTitle;
			$(this).closest(options.itemSelector).find(options.headTitleSelector).text(title);
		});

		if (options.removeSelector) {
			$root.on('click', options.removeSelector, function (event) {
				if (typeof options.onRemove === 'function') {
					options.onRemove(event, $(this).closest(options.itemSelector));
				}
			});
		}

		if (options.sortable && $.fn.sortable && $list.length) {
			$list.sortable({
				handle: options.dragSelector,
				items: options.itemSelector,
				placeholder: 'rs-metabox-accordion-placeholder',
				forcePlaceholderSize: true,
				tolerance: 'pointer',
				start: function () {
					// Flush TinyMCE → textarea antes de mover o DOM (evita perder HTML).
					if (typeof tinymce !== 'undefined' && tinymce.triggerSave) {
						try {
							tinymce.triggerSave();
						} catch (err) {
							/* ignore */
						}
					}
				},
				update: function () {
					if (typeof options.onSortUpdate === 'function') {
						options.onSortUpdate($list);
					}
				},
			});
		}

		return {
			$root: $root,
			$list: $list,
			options: options,
			openItem: openItem,
			parseEditorIds: parseEditorIds,
			resizeEditors: resizeEditors,
		};
	}

	window.RsMetaboxUi = {
		initTabs: initTabs,
		initAccordion: initAccordion,
		parseEditorIds: parseEditorIds,
		resizeEditors: resizeEditors,
	};

	jQuery(function () {
		$('[data-rs-tabs]').each(function () {
			initTabs(this);
		});
		// Footer/contact/legal/site-ui: acordeão estático (sem data-rs-accordion-list).
		// Educação/capabilities têm lista e chamam initAccordion com handlers próprios.
		$('[data-rs-accordion]').each(function () {
			if ($(this).find('[data-rs-accordion-list]').length) {
				return;
			}
			initAccordion(this, { sortable: false });
		});
	});
})(jQuery, window);
