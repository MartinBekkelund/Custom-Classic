/**
 * Custom Classic: changes the Text tab toolbar in the classic editor.
 * Settings come from customClassic, printed by custom-classic.php.
 */
(function (cfg) {
	'use strict';

	if (!cfg || !window.QTags || !window.edButtons) {
		return;
	}

	// Changed labels and tags on WordPress' own buttons. Done before the toolbar is built,
	// so WordPress' own behaviour is kept, including that a second press closes the tag.
	window.edButtons.forEach(function (b) {
		var o = b && cfg.overrides[b.id];
		if (!o) {
			return;
		}
		if (o.label) {
			b.display = o.label;
		}
		if (o.start) {
			b.tagStart = o.start;
			b.tagEnd = o.end;
		}
	});

	// Custom buttons.
	cfg.buttons.forEach(function (b) {
		QTags.addButton(b[0], b[1], b[2], b[3], '', b[1]);
	});

	// Button order. WordPress signals each toolbar once it is built.
	if (window.jQuery) {
		jQuery(document).on('quicktags-init', function (e, ed) {
			var bar = ed.toolbar,
				others = Array.prototype.slice.call(bar.children);

			cfg.order.forEach(function (id) {
				var el = ed.getButtonElement(id);
				if (el) {
					bar.appendChild(el);
				}
			});
			// Buttons from other plugins keep their place after ours.
			others.forEach(function (el) {
				if (cfg.order.indexOf(el.id.replace(ed.name + '_', '')) === -1) {
					bar.appendChild(el);
				}
			});
		});
	}

	// Shortcuts. Capture phase, so a defined shortcut wins over global admin shortcuts
	// (such as the Cmd+K command palette) while the cursor is in the Text tab.
	// Everything else passes through untouched.
	var mac = /Mac|iPhone|iPad/.test(navigator.platform);

	window.addEventListener('keydown', function (e) {
		var t = e.target, key, m, combo, ed, el;

		if (!t.classList || !t.classList.contains('wp-editor-area') || (mac ? e.ctrlKey : e.metaKey)) {
			return;
		}
		key = (e.key || '').toLowerCase();
		if (!/^[a-z0-9]$/.test(key)) {
			m = /^(?:Key|Digit)(\w)$/.exec(e.code || '');
			key = m ? m[1].toLowerCase() : '';
		}
		combo = ((mac ? e.metaKey : e.ctrlKey) ? 'mod+' : '') + (e.altKey ? 'alt+' : '') + (e.shiftKey ? 'shift+' : '') + key;
		if (!key || !cfg.keys[combo] || !(ed = QTags.getInstance(t.id))) {
			return;
		}
		el = ed.getButtonElement(cfg.keys[combo]);
		if (el) {
			e.preventDefault();
			e.stopPropagation();
			el.click();
		}
	}, true);
})(window.customClassic);
