<img src=".wordpress-org/icon.svg" alt="" width="96" height="96">

# Custom Classic

A small WordPress plugin that customizes the Text tab toolbar in the classic editor.

- Drag the buttons into the order you want, and hide the ones you do not use.
- Change the label and tags of WordPress' own buttons.
- Add your own tag buttons, for example `<h3>`.
- Assign a keyboard shortcut to any button, for example Cmd/Ctrl+B for bold.

No external libraries, no tracking, no external requests. It uses the official Quicktags API in WordPress core, so it works with the [Classic Editor](https://wordpress.org/plugins/classic-editor/) plugin and anywhere else WordPress shows the Text tab toolbar. The Visual tab is not changed.

## Installation

Copy the folder to `wp-content/plugins/custom-classic`, activate the plugin and go to **Settings → Custom Classic**.

## Settings

All buttons are in one list, in the order they appear in the toolbar.

| Column | What it does |
|---|---|
| ⠿ | Drag the row to move the button. |
| Show | Untick to hide the button. |
| ID | Fixed for WordPress' own buttons. For your own: a–z, 0–9, `-` and `_`. |
| Label | The text on the button. Empty means the WordPress default. |
| Start tag / End tag | What the button inserts. Empty end tag gives a single tag such as `<hr>`. Not available for `link`, `img`, `close` and `dfw`, which have their own behaviour. |
| Shortcut | Click and press the keys, for example Cmd+3. Backspace clears. Must include Cmd (Mac) or Ctrl. |

The input is checked in the browser before saving, and again on the server. Rows that cannot be saved are reported at the top of the page. **Reset to WordPress default** restores the original toolbar (plus the Cmd/Ctrl+B, I and K shortcuts that the plugin starts with).

Some combinations, such as Cmd/Ctrl+W, are reserved by the browser and cannot be overridden.

## Removing the plugin

- **Deactivate:** the toolbar is back to the WordPress default at once. Your settings are kept.
- **Delete:** `uninstall.php` removes the plugin's only stored data, the `custom_classic` option, on every site in a multisite network.

## Files

| File | Purpose |
|---|---|
| `custom-classic.php` | Settings, validation, settings page and loading of the scripts. |
| `admin.js` | Settings page: sorting, recording shortcuts, checking input. |
| `editor.js` | The toolbar: overrides, custom buttons, order and shortcuts. |
| `uninstall.php` | Deletes the settings when the plugin is deleted. |
| `.wordpress-org/` | Plugin icon (`icon.svg` plus 128 and 256 px PNG) for the wordpress.org directory. Not part of the plugin zip. |
| `languages/` | Norwegian Bokmål translation. Edit the `.po` file and regenerate the `.l10n.php` file. |

## License

GPLv2 or later.
