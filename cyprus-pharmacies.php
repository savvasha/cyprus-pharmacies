<?php
/**
Plugin Name: Cyprus Pharmacies
Description: An easy way to show the all-night pharmacies of Cyprus per city.
Author: Savvas
Author URI: https://savvasha.com
Text Domain: cyprus-pharmacies
Version: 1.2.7
Requires at least: 5.3
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl.html
 *
@package Cyprus Pharmacies
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define constants
 */
if ( ! defined( 'CYPHARM_PLUGIN_BASE' ) ) {
	define( 'CYPHARM_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}

/**
 * Include required files
 */
// load the needed data files.
require dirname( __FILE__ ) . '/includes/class-cypharm-data.php';
// load the needed frontend files.
require dirname( __FILE__ ) . '/includes/class-cypharm-front.php';
// load the needed coordinates.
require dirname( __FILE__ ) . '/includes/class-cypharm-coordinates.php';

/**
 * Plugin activation hook - clear any existing cache
 */
register_activation_hook( __FILE__, 'cypharm_activate' );

function cypharm_activate() {
	$cypharm_data = new CyPharm_Data();
	$cypharm_data->clear_cache();
}

/**
 * Plugin deactivation hook - clear cache
 */
register_deactivation_hook( __FILE__, 'cypharm_deactivate' );

function cypharm_deactivate() {
	$cypharm_data = new CyPharm_Data();
	$cypharm_data->clear_cache();
}

/**
 * Add admin menu for cache management
 */
add_action( 'admin_menu', 'cypharm_admin_menu' );

function cypharm_admin_menu() {
	add_options_page(
		__( 'Cyprus Pharmacies Cache', 'cyprus-pharmacies' ),
		__( 'Cyprus Pharmacies', 'cyprus-pharmacies' ),
		'manage_options',
		'cyprus-pharmacies-cache',
		'cypharm_cache_page'
	);
}

/**
 * Admin page for cache management
 */
function cypharm_cache_page() {
	if ( isset( $_POST['clear_cache'] ) && wp_verify_nonce( $_POST['cypharm_nonce'], 'cypharm_clear_cache' ) ) {
		$cypharm_data = new CyPharm_Data();
		$cypharm_data->clear_cache();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Cache cleared successfully!', 'cyprus-pharmacies' ) . '</p></div>';
	}
	
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Cyprus Pharmacies Cache Management', 'cyprus-pharmacies' ); ?></h1>
		<p><?php esc_html_e( 'Clear the cached pharmacy data if you need fresh information from the API.', 'cyprus-pharmacies' ); ?></p>
		
		<form method="post">
			<?php wp_nonce_field( 'cypharm_clear_cache', 'cypharm_nonce' ); ?>
			<input type="submit" name="clear_cache" class="button button-primary" value="<?php esc_attr_e( 'Clear Cache', 'cyprus-pharmacies' ); ?>">
		</form>
		
		<h2><?php esc_html_e( 'Developer Information', 'cyprus-pharmacies' ); ?></h2>
		<p><?php esc_html_e( 'You can customize the cache duration using the following filter in your theme\'s functions.php file:', 'cyprus-pharmacies' ); ?></p>
		
		<pre style="background: #f1f1f1; padding: 15px; border-radius: 3px; overflow-x: auto;">
// Set cache duration to 24 hours (86400 seconds)
add_filter( 'cypharm_cache_duration', function() {
    return 86400; // 24 hours in seconds
});

// Or set cache duration to 1 hour (3600 seconds)
add_filter( 'cypharm_cache_duration', function() {
    return 3600; // 1 hour in seconds
});

// Or disable caching entirely (not recommended for production)
add_filter( 'cypharm_cache_duration', function() {
    return 0; // No caching
});
</pre>
		
		<p><strong><?php esc_html_e( 'Current cache duration:', 'cyprus-pharmacies' ); ?></strong> <?php echo esc_html( apply_filters( 'cypharm_cache_duration', 43200 ) / 3600 ); ?> <?php esc_html_e( 'hours', 'cyprus-pharmacies' ); ?></p>
	</div>
	<?php
}
