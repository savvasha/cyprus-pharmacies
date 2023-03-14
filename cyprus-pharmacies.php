<?php
/**
Plugin Name: Cyprus Pharmacies
Description: An easy way to show the all-night pharmacies of Cyprus per city.
Author: Savvas
Author URI: https://profiles.wordpress.org/savvasha/
Text Domain: cyprus-pharmacies
Version: 1.1.2
Requires at least: 5.3
Requires PHP: 7.2
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
// load the needed frontend files.
require dirname( __FILE__ ) . '/includes/class-cypharm-front.php';
// load the needed coordinates.
require dirname( __FILE__ ) . '/includes/class-cypharm-coordinates.php';
