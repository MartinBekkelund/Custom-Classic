<?php
/**
 * Runs when the plugin is deleted under Plugins. Removes everything the plugin has stored:
 * one option, on every site in a multisite network.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $custom_classic_site ) {
		switch_to_blog( $custom_classic_site );
		delete_option( 'custom_classic' );
		restore_current_blog();
	}
} else {
	delete_option( 'custom_classic' );
}
