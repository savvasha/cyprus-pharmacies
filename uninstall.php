<?php
/**
 * Uninstall: remove all options and cached data of the plugin.
 *
 * @package Cyprus Pharmacies
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Removes the plugin's options and transients from the current site.
 *
 * @return void
 */
function cypharm_uninstall_site() {
	global $wpdb;

	$options = array(
		'cypharm_source',
		'cypharm_api_url',
		'cypharm_api_client_id',
		'cypharm_api_client_secret',
		'cypharm_cache_gen',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Direct query is required to bulk-remove transients by name; there is nothing to cache.
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_cypharm_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_cypharm_' ) . '%'
		)
	);
}

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $cypharm_site_id ) {
		switch_to_blog( $cypharm_site_id );
		cypharm_uninstall_site();
		restore_current_blog();
	}
} else {
	cypharm_uninstall_site();
}
