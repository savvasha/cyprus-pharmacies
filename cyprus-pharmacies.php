<?php
/**
Plugin Name: Cyprus Pharmacies
Description: An easy way to show the all-night pharmacies of Cyprus per city.
Author: Savvas
Author URI: https://savvasha.com
Text Domain: cyprus-pharmacies
Version: 1.3.0
Requires at least: 5.3
Requires PHP: 8.2
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
require __DIR__ . '/includes/class-cypharm-data.php';
// load the settings and admin page.
require __DIR__ . '/includes/class-cypharm-settings.php';
// load the needed frontend files.
require __DIR__ . '/includes/class-cypharm-front.php';
// load the needed coordinates.
require __DIR__ . '/includes/class-cypharm-coordinates.php';

CyPharm_Settings::init();

register_activation_hook( __FILE__, 'cypharm_activate' );

/**
 * Plugin activation hook - create the options (autoload off) and clear any existing cache.
 */
function cypharm_activate() {
	CyPharm_Settings::ensure_options();
	$cypharm_data = new CyPharm_Data();
	$cypharm_data->clear_cache();
}

register_deactivation_hook( __FILE__, 'cypharm_deactivate' );

/**
 * Plugin deactivation hook - clear cache.
 */
function cypharm_deactivate() {
	$cypharm_data = new CyPharm_Data();
	$cypharm_data->clear_cache();
}
