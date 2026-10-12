<?php
/**
 * Removes the plugin's settings when it is deleted from the Plugins screen.
 *
 * Order meta written by the purchase guard (`_linkforty_purchase_tracked`) is
 * left in place: it is tiny, and deleting it would make a reinstall re-report
 * historical orders if a customer revisits an old order-received page.
 *
 * @package LinkForty
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'linkforty_settings' );

if ( is_multisite() ) {
	$linkforty_site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $linkforty_site_ids as $linkforty_site_id ) {
		switch_to_blog( $linkforty_site_id );
		delete_option( 'linkforty_settings' );
		restore_current_blog();
	}
}
