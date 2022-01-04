<?php
/**
Plugin Name: Cyprus Pharmacies
Description: An easy way to show the all-night pharmacies of Cyprus per city.
Author: Savvas
Author URI: https://profiles.wordpress.org/savvasha/
Version: 1.0.0
Requires at least: 5.3
Requires PHP: 7.2
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Cyprus_Pharmacies' ) ) :

	/**
	 * Main Cyprus Pharmacies Class
	 *
	 * @class Cyprus_Pharmacies
	 * @version 1.1.0
	 */
	class Cyprus_Pharmacies {

		/**
		 * Constructor
		 */
		public function __construct() {
			// Define constants
			$this->define_constants();

			// Include required files
			$this->includes();

		}

		/**
		 * Define constants
		 */
		private function define_constants() {
			if ( ! defined( 'cypharm_PLUGIN_BASE' ) ) {
				define( 'cypharm_PLUGIN_BASE', plugin_basename( __FILE__ ) );
			}
		}

		/**
		 * Include required files
		 */
		private function includes() {
			// load the needed frontend files
			include dirname( __FILE__ ) . '/includes/class-cypharm-front.php';
		}
	}

endif;

new Cyprus_Pharmacies();
