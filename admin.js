/**
 * Custom Classic: the settings page. Sorting, adding and removing rows,
 * recording shortcuts and checking the input before it is saved.
 * Everything is checked again on the server.
 */
jQuery(function ($) {
	'use strict';

	var cfg = window.customClassicAdmin,
		t = cfg.i18n,
		mac = /Mac|iPhone|iPad/.test(navigator.platform),
		reserved = ['mod+w', 'mod+t', 'mod+n', 'mod+q', 'mod+r', 'mod+l'],
		$form = $('#cc-form'),
		$rows = $('#cc-rows'),
		next = $rows.children().length;

	// Shows mod+shift+3 as ⌘⇧3 on Mac and Ctrl+Shift+3 elsewhere.
	function pretty(combo) {
		var parts, key, names;
		if (!combo) {
			return '';
		}
		parts = combo.split('+');
		key = parts.pop().toUpperCase();
		names = mac ? { mod: '⌘', alt: '⌥', shift: '⇧' } : { mod: 'Ctrl+', alt: 'Alt+', shift: 'Shift+' };
		return parts.map(function (m) { return names[m]; }).join('') + key;
	}

	// The last closing tag should match the first opening tag: <ul><li> … </li></ul>.
	function tagsMatch(start, end) {
		var open = /^<([a-z][a-z0-9-]*)/i.exec(start),
			close = /<\/([a-z][a-z0-9-]*)>\s*$/i.exec(end);
		return !open || !close || open[1].toLowerCase() === close[1].toLowerCase();
	}

	function check() {
		var ids = {},
			keys = {};

		$rows.children('tr').each(function () {
			var $r = $(this),
				custom = $r.find('input[type=text].cc-id').length > 0,
				$id = $r.find('.cc-id'),
				$label = $r.find('.cc-label'),
				$start = $r.find('.cc-start'),
				$end = $r.find('.cc-end'),
				$key = $r.find('.cc-key'),
				id = $.trim($id.val()),
				key = $r.find('.cc-key-value').val(),
				start = $.trim($start.val()),
				end = $.trim($end.val()),
				warn = [];

			$r.find('input').each(function () { this.setCustomValidity(''); });
			$r.toggleClass('cc-hidden', !$r.find('.cc-show input').prop('checked'));

			if (custom) {
				if (!/^[a-z0-9_-]+$/.test(id)) {
					$id[0].setCustomValidity(t.idFormat);
				} else if (cfg.core.indexOf(id) !== -1 || ids[id]) {
					$id[0].setCustomValidity(t.idTaken);
				}
				if (!$.trim($label.val())) {
					$label[0].setCustomValidity(t.required);
				}
				if (!start) {
					$start[0].setCustomValidity(t.required);
				}
			} else if (end && !start && !$start.prop('disabled')) {
				$start[0].setCustomValidity(t.endNeedsStart);
			}
			ids[id] = true;

			if (key) {
				if (keys[key]) {
					$key[0].setCustomValidity(t.keyTaken);
				}
				keys[key] = true;
				if (reserved.indexOf(key) !== -1) {
					warn.push(t.keyReserved);
				}
			}
			if (start && end && !tagsMatch(start, end)) {
				warn.push(t.tagMismatch);
			}
			$r.find('.cc-warn').text(warn.join(' '));
		});
	}

	$rows.sortable({
		handle: '.cc-handle',
		axis: 'y',
		placeholder: 'cc-placeholder',
		forcePlaceholderSize: true,
		// Keep the column widths while a table row is dragged.
		helper: function (e, $tr) {
			$tr.children().each(function () {
				$(this).width($(this).width());
			});
			return $tr;
		},
		stop: function (e, ui) {
			ui.item.children().css('width', '');
		},
		update: check
	});

	// Record a shortcut: click the field and press the keys. Tab moves on, Backspace/Delete clears.
	$rows.on('keydown', '.cc-key', function (e) {
		var key, m;
		if (e.key === 'Tab') {
			return;
		}
		e.preventDefault();
		if (e.key === 'Backspace' || e.key === 'Delete') {
			$(this).val('').next('.cc-key-value').val('');
			check();
			return;
		}
		key = (e.key || '').toLowerCase();
		if (!/^[a-z0-9]$/.test(key)) {
			m = /^(?:Key|Digit)(\w)$/.exec(e.code || '');
			key = m ? m[1].toLowerCase() : '';
		}
		if (!key || !(mac ? e.metaKey : e.ctrlKey) || (mac ? e.ctrlKey : e.metaKey)) {
			return; // A shortcut must include Cmd (Mac) or Ctrl (elsewhere).
		}
		key = 'mod+' + (e.altKey ? 'alt+' : '') + (e.shiftKey ? 'shift+' : '') + key;
		$(this).val(pretty(key)).next('.cc-key-value').val(key);
		check();
	});

	$rows.on('input change', 'input', check);

	$rows.on('click', '.cc-remove', function () {
		$(this).closest('tr').remove();
		check();
	});

	$('#cc-add').on('click', function () {
		var html = $('#cc-template').html().replace(/__i__/g, String(next++)),
			$row = $(html);
		$rows.append($row);
		$row.find('.cc-id').trigger('focus');
		check();
	});

	// The browser checks the fields before the submit event, so check them on the click first.
	$('#submit').on('click', check);

	$form.on('submit', function (e) {
		var submitter = e.originalEvent && e.originalEvent.submitter;
		if (submitter && submitter.id === 'cc-reset') {
			if (!window.confirm(t.confirmReset)) {
				e.preventDefault();
			}
			return;
		}
		check();
		if (!this.checkValidity()) {
			e.preventDefault();
			this.reportValidity();
		}
	});

	$rows.find('.cc-key').each(function () {
		$(this).val(pretty($(this).next('.cc-key-value').val()));
	});
	check();
});
