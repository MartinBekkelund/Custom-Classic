<?php
/**
 * Plugin Name:       Custom Classic
 * Plugin URI:        https://github.com/MartinBekkelund/Custom-Classic
 * Description:       Customize the Text tab toolbar in the classic editor: choose and order buttons, add your own tag buttons and assign keyboard shortcuts.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Martin Koksrud Bekkelund
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-classic
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

const CUSTOM_CLASSIC_VERSION = '1.0.0';
const CUSTOM_CLASSIC_OPTION  = 'custom_classic';
const CUSTOM_CLASSIC_SCHEMA  = 1;   // Version of the stored data. Bump and migrate in custom_classic_get() if the format changes.
const CUSTOM_CLASSIC_MAX     = 100; // Most buttons that can be saved, to keep the option small.

/**
 * WordPress' own Text tab buttons: id => array( label, start tag, end tag ).
 * Buttons without a start tag have their own behaviour in WordPress (link dialog,
 * image prompt, closing open tags, fullscreen), so only their label can be changed.
 */
function custom_classic_core() {
	return array(
		'strong' => array( 'b', '<strong>', '</strong>' ),
		'em'     => array( 'i', '<em>', '</em>' ),
		'link'   => array( 'link', '', '' ),
		'block'  => array( 'b-quote', '<blockquote>', '</blockquote>' ),
		'del'    => array( 'del', '<del datetime="…">', '</del>' ),
		'ins'    => array( 'ins', '<ins datetime="…">', '</ins>' ),
		'img'    => array( 'img', '', '' ),
		'ul'     => array( 'ul', '<ul>', '</ul>' ),
		'ol'     => array( 'ol', '<ol>', '</ol>' ),
		'li'     => array( 'li', '<li>', '</li>' ),
		'code'   => array( 'code', '<code>', '</code>' ),
		'more'   => array( 'more', '<!--more-->', '' ),
		'close'  => array( 'close tags', '', '' ),
		'dfw'    => array( '', '', '' ),
	);
}

/**
 * Default settings: WordPress' own toolbar, plus three common shortcuts.
 */
function custom_classic_defaults() {
	$keys = array(
		'strong' => 'mod+b',
		'em'     => 'mod+i',
		'link'   => 'mod+k',
	);
	$rows = array();
	foreach ( array_keys( custom_classic_core() ) as $id ) {
		$rows[] = array(
			'id'    => $id,
			'show'  => true,
			'label' => '',
			'start' => '',
			'end'   => '',
			'key'   => isset( $keys[ $id ] ) ? $keys[ $id ] : '',
		);
	}
	return array(
		'schema' => CUSTOM_CLASSIC_SCHEMA,
		'rows'   => $rows,
	);
}

/**
 * Saved settings, or the defaults. The saved data is never trusted blindly: every row is
 * normalized, so a damaged or older option cannot break the editor. Buttons that WordPress
 * adds in a future version are appended, so they show up after an update.
 */
function custom_classic_get() {
	$saved = get_option( CUSTOM_CLASSIC_OPTION );
	if ( ! is_array( $saved ) || ! isset( $saved['rows'] ) || ! is_array( $saved['rows'] ) ) {
		return custom_classic_defaults();
	}
	$core = custom_classic_core();
	$rows = array();
	$seen = array();
	foreach ( $saved['rows'] as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['id'] ) || ! is_string( $row['id'] ) || isset( $seen[ $row['id'] ] ) ) {
			continue;
		}
		$seen[ $row['id'] ] = true;
		$rows[]             = array(
			'id'    => $row['id'],
			'show'  => ! empty( $row['show'] ),
			'label' => custom_classic_field( $row, 'label', 30 ),
			'start' => custom_classic_field( $row, 'start', 200 ),
			'end'   => custom_classic_field( $row, 'end', 200 ),
			'key'   => custom_classic_field( $row, 'key', 20 ),
		);
	}
	foreach ( array_keys( $core ) as $id ) {
		if ( ! isset( $seen[ $id ] ) ) {
			$rows[] = array(
				'id'    => $id,
				'show'  => true,
				'label' => '',
				'start' => '',
				'end'   => '',
				'key'   => '',
			);
		}
	}
	return array(
		'schema' => CUSTOM_CLASSIC_SCHEMA,
		'rows'   => $rows,
	);
}

/**
 * Cleans one text field from the form.
 */
function custom_classic_field( $row, $name, $max ) {
	// Counts characters, not bytes, so letters such as æ, ø and å are never cut in half.
	return isset( $row[ $name ] ) && is_string( $row[ $name ] ) ? mb_substr( trim( $row[ $name ] ), 0, $max, 'UTF-8' ) : '';
}

/**
 * Validates and cleans the settings form. The same rules are checked in admin.js,
 * but the browser can be bypassed, so everything is checked again here.
 * Rows or fields that fail are dropped, and the user is told why.
 */
function custom_classic_sanitize( $input ) {
	// WordPress runs this twice when the option is first created. Both runs check everything,
	// but only the first one reports, so messages are not shown twice.
	static $runs = 0;
	$quiet = $runs++ > 0;

	if ( ! is_array( $input ) ) {
		return custom_classic_defaults();
	}
	if ( ! empty( $input['reset'] ) ) {
		if ( ! $quiet ) {
			add_settings_error( CUSTOM_CLASSIC_OPTION, 'reset', esc_html__( 'The toolbar has been reset to the WordPress default.', 'custom-classic' ), 'success' );
		}
		return custom_classic_defaults();
	}

	$core      = custom_classic_core();
	$rows      = array();
	$seen_ids  = array();
	$seen_keys = array();
	$errors    = 0;
	$n         = 0;
	$html      = current_user_can( 'unfiltered_html' );
	$error     = function ( $n, $message ) use ( &$errors, $quiet ) {
		++$errors;
		if ( $quiet ) {
			return;
		}
		/* translators: 1: position of the button in the list, 2: what is wrong. */
		add_settings_error( CUSTOM_CLASSIC_OPTION, 'row-' . $n . '-' . $errors, esc_html( sprintf( __( 'Button %1$d: %2$s', 'custom-classic' ), $n, $message ) ), 'warning' );
	};

	foreach ( isset( $input['rows'] ) && is_array( $input['rows'] ) ? $input['rows'] : array() as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		++$n;
		if ( count( $rows ) >= CUSTOM_CLASSIC_MAX ) {
			/* translators: %d: the maximum number of buttons. */
			$error( $n, sprintf( __( 'not saved. You can have at most %d buttons.', 'custom-classic' ), CUSTOM_CLASSIC_MAX ) );
			break;
		}
		$id      = custom_classic_field( $row, 'id', 30 );
		$is_core = isset( $core[ $id ] );
		$label   = sanitize_text_field( custom_classic_field( $row, 'label', 30 ) );
		$start   = custom_classic_field( $row, 'start', 200 );
		$end     = custom_classic_field( $row, 'end', 200 );
		$key     = strtolower( custom_classic_field( $row, 'key', 20 ) );

		// Users who may not post raw HTML (for example site admins on multisite) may not
		// define buttons that insert it either. Their tags are filtered like post content.
		if ( ! $html ) {
			$start = wp_kses_post( $start );
			$end   = wp_kses_post( $end );
		}

		if ( ! $is_core ) {
			if ( '' === $id && '' === $label && '' === $start && '' === $end ) {
				continue; // An empty new row is simply ignored.
			}
			if ( ! preg_match( '/^[a-z0-9_-]+$/', $id ) ) {
				/* translators: %s: the ID the user typed. */
				$error( $n, sprintf( __( 'not saved. The ID "%s" may only contain a–z, 0–9, - and _.', 'custom-classic' ), $id ) );
				continue;
			}
			if ( isset( $seen_ids[ $id ] ) ) {
				/* translators: %s: the ID the user typed. */
				$error( $n, sprintf( __( 'not saved. The ID "%s" is already in use.', 'custom-classic' ), $id ) );
				continue;
			}
			if ( '' === $label || '' === $start ) {
				$error( $n, __( 'not saved. A custom button needs a label and a start tag.', 'custom-classic' ) );
				continue;
			}
		} else {
			if ( isset( $seen_ids[ $id ] ) ) {
				/* translators: %s: the ID the user typed. */
				$error( $n, sprintf( __( 'not saved. The ID "%s" is already in use.', 'custom-classic' ), $id ) );
				continue;
			}
			if ( '' === $core[ $id ][1] ) {
				$start = '';
				$end   = '';
			}
			if ( 'dfw' === $id ) {
				$label = '';
			}
			if ( '' === $start && '' !== $end ) {
				$error( $n, __( 'the end tag was removed, because it needs a start tag.', 'custom-classic' ) );
				$end = '';
			}
		}

		if ( '' !== $key ) {
			if ( ! preg_match( '/^mod\+(alt\+)?(shift\+)?[a-z0-9]$/', $key ) ) {
				$error( $n, __( 'the shortcut was removed, because it is not valid.', 'custom-classic' ) );
				$key = '';
			} elseif ( isset( $seen_keys[ $key ] ) ) {
				$error( $n, __( 'the shortcut was removed, because another button already uses it.', 'custom-classic' ) );
				$key = '';
			} else {
				$seen_keys[ $key ] = true;
			}
		}

		$seen_ids[ $id ] = true;
		$rows[]          = array(
			'id'    => $id,
			'show'  => ! empty( $row['show'] ),
			'label' => $label,
			'start' => $start,
			'end'   => $end,
			'key'   => $key,
		);
	}

	// WordPress' own buttons are always in the list. Any that went missing are added back, hidden.
	foreach ( array_keys( $core ) as $id ) {
		if ( ! isset( $seen_ids[ $id ] ) ) {
			$rows[] = array(
				'id'    => $id,
				'show'  => false,
				'label' => '',
				'start' => '',
				'end'   => '',
				'key'   => '',
			);
		}
	}

	if ( $errors && ! $quiet ) {
		add_settings_error( CUSTOM_CLASSIC_OPTION, 'saved', esc_html__( 'Everything else was saved.', 'custom-classic' ), 'success' );
	}
	return array(
		'schema' => CUSTOM_CLASSIC_SCHEMA,
		'rows'   => $rows,
	);
}

add_action(
	'init',
	function () {
		load_plugin_textdomain( 'custom-classic', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'custom_classic',
			CUSTOM_CLASSIC_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'custom_classic_sanitize',
				'default'           => custom_classic_defaults(),
			)
		);
	}
);

/**
 * Hides WordPress' own buttons that are switched off. Editors that already limit
 * their buttons stay limited. Custom buttons are handled in editor.js.
 */
add_filter(
	'quicktags_settings',
	function ( $settings ) {
		$core     = array_keys( custom_classic_core() );
		$original = empty( $settings['buttons'] ) ? array_diff( $core, array( 'dfw' ) ) : explode( ',', $settings['buttons'] );
		$visible  = array();
		foreach ( custom_classic_get()['rows'] as $row ) {
			if ( $row['show'] && in_array( $row['id'], $core, true ) && in_array( $row['id'], $original, true ) ) {
				$visible[] = $row['id'];
			}
		}
		// An empty string means "all buttons" to Quicktags, so a lone comma is used to show none.
		$settings['buttons'] = $visible ? implode( ',', $visible ) : ',';
		return $settings;
	}
);

/**
 * Loads editor.js wherever WordPress has queued the Text tab toolbar, and nowhere else.
 * WordPress queues its editor scripts at priority 1 and prints them at priority 10.
 */
function custom_classic_editor_script() {
	if ( ! wp_script_is( 'quicktags', 'enqueued' ) ) {
		return;
	}
	$core   = custom_classic_core();
	$config = array(
		'order'     => array(),
		'buttons'   => array(),
		'overrides' => array(),
		'keys'      => array(),
	);
	foreach ( custom_classic_get()['rows'] as $row ) {
		if ( ! $row['show'] ) {
			continue;
		}
		$config['order'][] = $row['id'];
		if ( '' !== $row['key'] ) {
			$config['keys'][ $row['key'] ] = $row['id'];
		}
		if ( ! isset( $core[ $row['id'] ] ) ) {
			$config['buttons'][] = array( $row['id'], $row['label'], $row['start'], $row['end'] );
		} elseif ( '' !== $row['label'] || '' !== $row['start'] ) {
			$config['overrides'][ $row['id'] ] = array(
				'label' => $row['label'],
				'start' => $row['start'],
				'end'   => $row['end'],
			);
		}
	}
	$config['keys']      = (object) $config['keys'];
	$config['overrides'] = (object) $config['overrides'];

	wp_enqueue_script( 'custom-classic-editor', plugins_url( 'editor.js', __FILE__ ), array( 'quicktags', 'jquery' ), CUSTOM_CLASSIC_VERSION, true );
	wp_add_inline_script( 'custom-classic-editor', 'var customClassic = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
}
add_action( 'admin_print_footer_scripts', 'custom_classic_editor_script', 5 );
add_action( 'wp_print_footer_scripts', 'custom_classic_editor_script', 5 );

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Custom Classic', 'Custom Classic', 'manage_options', 'custom-classic', 'custom_classic_page' );
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'settings_page_custom-classic' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'custom-classic-admin', plugins_url( 'admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable' ), CUSTOM_CLASSIC_VERSION, true );
		wp_add_inline_script(
			'custom-classic-admin',
			'var customClassicAdmin = ' . wp_json_encode(
				array(
					'core' => array_keys( custom_classic_core() ),
					'i18n' => array(
						'confirmReset' => __( 'Reset the toolbar to the WordPress default? All your changes will be lost.', 'custom-classic' ),
						'idFormat'     => __( 'Use only a–z, 0–9, - and _.', 'custom-classic' ),
						'idTaken'      => __( 'This ID is already in use.', 'custom-classic' ),
						'required'     => __( 'Required for custom buttons.', 'custom-classic' ),
						'endNeedsStart' => __( 'An end tag needs a start tag.', 'custom-classic' ),
						'keyTaken'     => __( 'Another button already uses this shortcut.', 'custom-classic' ),
						'tagMismatch'  => __( 'The end tag does not match the start tag.', 'custom-classic' ),
						'keyReserved'  => __( 'The browser usually reserves this shortcut, so it may not work.', 'custom-classic' ),
					),
				),
				JSON_HEX_TAG | JSON_HEX_AMP
			) . ';',
			'before'
		);
		wp_register_style( 'custom-classic-admin', false, array(), CUSTOM_CLASSIC_VERSION );
		wp_enqueue_style( 'custom-classic-admin' );
		wp_add_inline_style(
			'custom-classic-admin',
			'#cc-table td{vertical-align:middle}#cc-table input[type=text]{width:100%}#cc-table .cc-handle{cursor:move;color:#8c8f94}'
			. '#cc-table .cc-hidden td:not(.cc-show){opacity:.5}#cc-table .cc-placeholder{height:46px;background:#f0f6fc}'
			. '#cc-table .cc-warn{display:block;color:#996800;font-size:12px}#cc-table .cc-warn:empty{display:none}'
		);
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=custom-classic' ) ) . '">' . esc_html__( 'Settings', 'custom-classic' ) . '</a>' );
		return $links;
	}
);

/**
 * Prints one row of the button list. $i is the row's form index; '__i__' in the template.
 */
function custom_classic_row( $i, $row ) {
	$core    = custom_classic_core();
	$is_core = isset( $core[ $row['id'] ] );
	$def     = $is_core ? $core[ $row['id'] ] : array( '', '', '' );
	$special = $is_core && '' === $def[1];
	$name    = CUSTOM_CLASSIC_OPTION . '[rows][' . $i . ']';
	?>
	<tr class="<?php echo $row['show'] ? '' : 'cc-hidden'; ?>">
		<td class="cc-handle" title="<?php esc_attr_e( 'Drag to move', 'custom-classic' ); ?>"><span class="dashicons dashicons-menu" aria-hidden="true"></span></td>
		<td class="cc-show"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[show]" value="1" aria-label="<?php esc_attr_e( 'Show', 'custom-classic' ); ?>" <?php checked( $row['show'] ); ?>></td>
		<td>
			<?php if ( $is_core ) : ?>
				<code><?php echo esc_html( $row['id'] ); ?></code>
				<input type="hidden" class="cc-id" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $row['id'] ); ?>">
			<?php else : ?>
				<input type="text" class="cc-id code" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $row['id'] ); ?>" maxlength="30" placeholder="h3" required>
			<?php endif; ?>
		</td>
		<td><input type="text" class="cc-label" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $row['label'] ); ?>" maxlength="30" placeholder="<?php echo esc_attr( $is_core ? $def[0] : 'H3' ); ?>" <?php disabled( 'dfw', $row['id'] ); ?>></td>
		<td><input type="text" class="cc-start code" name="<?php echo esc_attr( $name ); ?>[start]" value="<?php echo esc_attr( $row['start'] ); ?>" maxlength="200" placeholder="<?php echo esc_attr( $is_core ? $def[1] : '<h3>' ); ?>" <?php disabled( $special ); ?>></td>
		<td><input type="text" class="cc-end code" name="<?php echo esc_attr( $name ); ?>[end]" value="<?php echo esc_attr( $row['end'] ); ?>" maxlength="200" placeholder="<?php echo esc_attr( $is_core ? $def[2] : '</h3>' ); ?>" <?php disabled( $special ); ?>></td>
		<td>
			<input type="text" class="cc-key code" value="<?php echo esc_attr( $row['key'] ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Shortcut', 'custom-classic' ); ?>">
			<input type="hidden" class="cc-key-value" name="<?php echo esc_attr( $name ); ?>[key]" value="<?php echo esc_attr( $row['key'] ); ?>">
			<span class="cc-warn"></span>
		</td>
		<td><?php if ( ! $is_core ) : ?><button type="button" class="button-link cc-remove"><?php esc_html_e( 'Remove', 'custom-classic' ); ?></button><?php endif; ?></td>
	</tr>
	<?php
}

/**
 * Renders the settings page.
 */
function custom_classic_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$empty = array(
		'id'    => '',
		'show'  => true,
		'label' => '',
		'start' => '',
		'end'   => '',
		'key'   => '',
	);
	?>
	<div class="wrap">
		<h1>Custom Classic</h1>
		<p><?php esc_html_e( 'Customize the toolbar in the Text tab of the classic editor. Drag the buttons into the order you want, untick the ones you do not use, and add your own. Leave a field empty to keep the WordPress default.', 'custom-classic' ); ?></p>
		<p><?php esc_html_e( 'To set a shortcut, click the shortcut field and press the keys, for example Cmd+3 on Mac or Ctrl+3 elsewhere. Backspace clears it.', 'custom-classic' ); ?></p>
		<form action="options.php" method="post" id="cc-form">
			<?php settings_fields( 'custom_classic' ); ?>
			<table class="widefat striped" id="cc-table">
				<thead>
					<tr>
						<th style="width:24px"><span class="screen-reader-text"><?php esc_html_e( 'Move', 'custom-classic' ); ?></span></th>
						<th style="width:40px"><?php esc_html_e( 'Show', 'custom-classic' ); ?></th>
						<th style="width:12%"><?php esc_html_e( 'ID', 'custom-classic' ); ?></th>
						<th style="width:12%"><?php esc_html_e( 'Label', 'custom-classic' ); ?></th>
						<th><?php esc_html_e( 'Start tag', 'custom-classic' ); ?></th>
						<th><?php esc_html_e( 'End tag', 'custom-classic' ); ?></th>
						<th style="width:16%"><?php esc_html_e( 'Shortcut', 'custom-classic' ); ?></th>
						<th style="width:60px"></th>
					</tr>
				</thead>
				<tbody id="cc-rows">
					<?php
					foreach ( custom_classic_get()['rows'] as $i => $row ) {
						custom_classic_row( $i, $row );
					}
					?>
				</tbody>
			</table>
			<template id="cc-template"><?php custom_classic_row( '__i__', $empty ); ?></template>
			<p><button type="button" class="button" id="cc-add"><?php esc_html_e( 'Add button', 'custom-classic' ); ?></button></p>
			<p class="submit">
				<?php submit_button( null, 'primary', 'submit', false ); ?>
				<button type="submit" class="button" id="cc-reset" name="<?php echo esc_attr( CUSTOM_CLASSIC_OPTION ); ?>[reset]" value="1" formnovalidate><?php esc_html_e( 'Reset to WordPress default', 'custom-classic' ); ?></button>
			</p>
		</form>
		<p class="description"><?php esc_html_e( 'When you delete the plugin, all its settings are deleted too.', 'custom-classic' ); ?></p>
	</div>
	<?php
}
