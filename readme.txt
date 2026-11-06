=== Custom Classic ===
Contributors: martinbekkelund
Tags: classic editor, quicktags, toolbar, keyboard shortcuts, text editor
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize the Text tab toolbar in the classic editor: choose and order buttons, add your own tag buttons and assign keyboard shortcuts.

== Description ==

The Text tab in the classic editor gives you full control over your HTML, but the toolbar is fixed and has no keyboard shortcuts. Custom Classic lets you:

* Drag the buttons into the order you want.
* Hide the buttons you do not use.
* Change the label and tags of WordPress' own buttons, for example make the `b` button insert `<b>` instead of `<strong>`.
* Add your own buttons, for example `<h3>`, `<blockquote>` or `<abbr>`.
* Assign a keyboard shortcut to any button, for example Cmd/Ctrl+B for bold.

Everything is set in one list on one settings page, and the input is checked before it is saved. The plugin has no external libraries, no tracking and no external requests. It uses the official Quicktags API in WordPress core, so it works with the Classic Editor plugin and anywhere else WordPress shows the Text tab toolbar. The Visual tab is not changed.

== Installation ==

1. Install and activate the plugin.
2. Go to Settings → Custom Classic.
3. Adjust the list and save.

== Frequently Asked Questions ==

= How do I add a button? =

Click "Add button", fill in an ID (for example `h3`), a label, a start tag and an end tag, and save. Leave the end tag empty for single tags such as `<hr>`.

= How do I set a shortcut? =

Click the shortcut field and press the keys, for example Cmd+3 on Mac or Ctrl+3 on other systems. Backspace clears it. Every shortcut must include Cmd or Ctrl, so normal typing is never affected.

= Why does a shortcut not work? =

Some combinations, such as Cmd/Ctrl+W or Cmd/Ctrl+T, are reserved by the browser and cannot be overridden. The settings page warns you about the most common ones.

= Can I change WordPress' own buttons? =

You can change the label, position, visibility and shortcut of all of them. The tags can be changed on the buttons that insert tags. The link, image, close tags and fullscreen buttons have their own behaviour in WordPress, so only their label, position, visibility and shortcut can be changed.

= How do I go back to the WordPress default? =

Click "Reset to WordPress default" on the settings page. You can also deactivate the plugin: the toolbar is then back to the WordPress default at once, and your settings are kept in case you activate it again.

= Do the settings apply per user? =

No. The settings apply to the whole site, and only administrators can change them.

= What happens when I delete the plugin? =

All its settings are removed from the database, on every site in a multisite network. The plugin stores nothing else.

== Changelog ==

= 1.0.0 =
* First version.
