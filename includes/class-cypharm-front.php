<?php

/**
 * Frontend functionality
 *
 * @package Cyprus Pharmacies
 * @subpackage Front
 * @author Savvas
 */
class CyPharm_Front {
	/**
	 * Constructor
	 */
	public function __construct() {

		add_shortcode( 'cypharm', array( $this, 'cypharm_shortcode' ) );
	}

	/**
	 * CyPharm shortcode
	 *
	 * @param mixed $atts The attributes of the shortcode.
	 * @return string
	 */
	public function cypharm_shortcode( $atts ) {

		// Attributes.
		$atts = shortcode_atts(
			array(
				'city'  => 'Paphos',
				'title' => false,
			),
			$atts,
			'cypharm'
		);

		// Validate city parameter against allowed values
		$allowed_cities = array( 'Paphos', 'Limassol', 'Nicosia', 'Larnaca', 'Paralimni' );
		if ( ! in_array( $atts['city'], $allowed_cities, true ) ) {
			$atts['city'] = 'Paphos';
		}

		// Initialize Pharmacy Data Class.
		$cypharm = new CyPharm_Data();

		// Get today pharmacies.
		$cypharm->date    = gmdate( 'j/n/Y' ) . ',' . gmdate( 'j/n/y' ) . ',' . gmdate( 'd/m/y' ) . ',' . gmdate( 'd/m/Y' );
		$cypharm->city    = $atts['city'];
		$today_pharmacies = $cypharm->data();

		// Get tomorrow pharmacies.
		$cypharm->date       = gmdate( 'j/n/Y', strtotime( '+1 day' ) ) . ',' . gmdate( 'j/n/y', strtotime( '+1 day' ) ) . ',' . gmdate( 'd/m/y', strtotime( '+1 day' ) ) . ',' . gmdate( 'd/m/Y', strtotime( '+1 day' ) );
		$cypharm->city       = $atts['city'];
		$tomorrow_pharmacies = $cypharm->data();

		// Get available coordinates.
		$coordinates = CyPharm_Coordinates::$coordinates;

		$output = '';

		// Show title if set.
		if ( $atts['title'] ) {
			$output .= '<h1>' . esc_html( $atts['title'] ) . '</h1>';
		}

		// Show today pharmacies.
		$output .= '<h3>' . date_i18n( 'l' ) . ' , ' . date_i18n( 'j' ) . ' ' . date_i18n( 'M' ) . ' ' . date_i18n( 'Y' ) . '<h3>';

		foreach ( $today_pharmacies as $today_pharmacy ) {
			if ( isset( $today_pharmacy->reg__no_ ) && isset( $coordinates[ $atts['city'] ][ $today_pharmacy->reg__no_ ] ) && ! isset( $today_pharmacy->geolocation ) ) {
				$lat              = $coordinates[ $atts['city'] ][ $today_pharmacy->reg__no_ ]['Latitude'];
				$long             = $coordinates[ $atts['city'] ][ $today_pharmacy->reg__no_ ]['Longitude'];
				$pharmacy_address = '<a href="http://www.google.com/maps/place/' . esc_attr( $lat ) . ',' . esc_attr( $long ) . '" target="_blank">' . esc_html( $today_pharmacy->address ) . '</a>';
			} else {
				$pharmacy_address = esc_html( $today_pharmacy->address );
			}
			$output .= '<h4>' . esc_html( $today_pharmacy->surmame ) . ' ' . esc_html( $today_pharmacy->name ) . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>' . esc_html__( 'Address:', 'cyprus-pharmacies' ) . '</strong> ' . $pharmacy_address . '</li>';
			if ( isset( $today_pharmacy->additional_address_info ) ) {
				$output .= '<li><strong>' . esc_html__( 'Additional Address info:', 'cyprus-pharmacies' ) . '</strong> ' . esc_html( $today_pharmacy->additional_address_info ) . '</li>';
			}
			if ( isset( $today_pharmacy->muniuciplity__community ) ) {
				$output .= '<li><strong>' . esc_html__( 'Municipality/Community:', 'cyprus-pharmacies' ) . '</strong> ' . esc_html( $today_pharmacy->muniuciplity__community ) . '</li>';
			}
			if ( isset( $today_pharmacy->pharmacy_tel__no_ ) ) {
				$output .= '<li><strong>' . esc_html__( 'Pharmacy Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="tel:' . esc_attr( $today_pharmacy->pharmacy_tel__no_ ) . '"> ' . esc_html( $today_pharmacy->pharmacy_tel__no_ ) . ' </a></li>';
			}
			if ( isset( $today_pharmacy->house_tel__no_ ) ) {
				$output .= '<li><strong>' . esc_html__( 'Home Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="tel:' . esc_attr( $today_pharmacy->house_tel__no_ ) . '"> ' . esc_html( $today_pharmacy->house_tel__no_ ) . ' </a></li>';
			}
			$output .= '</ul>';
		}

		$output .= '<hr>';

		// Show tomorrow pharmacies.
		$output .= '<h3>' . date_i18n( 'l', strtotime( '+1 day' ) ) . ' , ' . date_i18n( 'j', strtotime( '+1 day' ) ) . ' ' . date_i18n( 'M', strtotime( '+1 day' ) ) . ' ' . date_i18n( 'Y', strtotime( '+1 day' ) ) . '<h3>';

		foreach ( $tomorrow_pharmacies as $tomorrow_pharmacy ) {
			if ( isset( $tomorrow_pharmacy->reg__no_ ) && isset( $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg__no_ ] ) ) {
				$lat              = $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg__no_ ]['Latitude'];
				$long             = $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg__no_ ]['Longitude'];
				$pharmacy_address = '<a href="http://www.google.com/maps/place/' . esc_attr( $lat ) . ',' . esc_attr( $long ) . '" target="_blank">' . esc_html( $tomorrow_pharmacy->address ) . '</a>';
			} else {
				$pharmacy_address = esc_html( $tomorrow_pharmacy->address );
			}
			$output .= '<h4>' . esc_html( $tomorrow_pharmacy->surmame ) . ' ' . esc_html( $tomorrow_pharmacy->name ) . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>' . esc_html__( 'Address:', 'cyprus-pharmacies' ) . '</strong> ' . $pharmacy_address . '</li>';
			if ( isset( $tomorrow_pharmacy->additional_address_info ) ) {
				$output .= '<li><strong>' . esc_html__( 'Additional Address info:', 'cyprus-pharmacies' ) . '</strong> ' . esc_html( $tomorrow_pharmacy->additional_address_info ) . '</li>';
			}
			if ( isset( $tomorrow_pharmacy->muniuciplity__community ) ) {
				$output .= '<li><strong>' . esc_html__( 'Municipality/Community:', 'cyprus-pharmacies' ) . '</strong> ' . esc_html( $tomorrow_pharmacy->muniuciplity__community ) . '</li>';
			}
			if ( isset( $tomorrow_pharmacy->pharmacy_tel__no_ ) ) {
				$output .= '<li><strong>' . esc_html__( 'Pharmacy Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="tel:' . esc_attr( $tomorrow_pharmacy->pharmacy_tel__no_ ) . '"> ' . esc_html( $tomorrow_pharmacy->pharmacy_tel__no_ ) . ' </a></li>';
			}
			if ( isset( $tomorrow_pharmacy->house_tel__no_ ) ) {
				$output .= '<li><strong>' . esc_html__( 'Home Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="tel:' . esc_attr( $tomorrow_pharmacy->house_tel__no_ ) . '"> ' . esc_html( $tomorrow_pharmacy->house_tel__no_ ) . ' </a></li>';
			}
			$output .= '</ul>';
		}

		return $output;
	}

}

new CyPharm_Front();
