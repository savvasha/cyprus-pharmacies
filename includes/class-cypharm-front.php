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

		if ( 'Pafos' === $atts['city'] ) {
			$atts['city'] = 'Paphos';
		}

		// Initialize Pharmacy Data Class.
		$cypharm = new CyPharm_Data();

		// Get today pharmacies.
		$cypharm->date    = gmdate( 'j/n/y' );
		$cypharm->city    = $atts['city'];
		$today_pharmacies = $cypharm->data();

		// Get tomorrow pharmacies.
		$cypharm->date       = gmdate( 'j/n/y', strtotime( '+1 day' ) );
		$cypharm->city       = $atts['city'];
		$tomorrow_pharmacies = $cypharm->data();

		// Get available coordinates.
		$coordinates = CyPharm_Coordinates::$coordinates;

		$output = '';

		// Show title if set.
		if ( $atts['title'] ) {

			$output .= '<h1>' . $atts['title'] . '</h1>';
		}

		// Show today pharmacies.
		$output .= '<h3>' . date_i18n( 'l' ) . ' , ' . date_i18n( 'j' ) . ' ' . date_i18n( 'M' ) . ' ' . date_i18n( 'Y' ) . '<h3>';

		foreach ( $today_pharmacies as $today_pharmacy ) {
			if ( isset( $today_pharmacy->reg_no ) && isset( $coordinates[ $atts['city'] ][ $today_pharmacy->reg_no ] ) && !isset( $today_pharmacy->geolocation ) ) {
				$lat              = $coordinates[ $atts['city'] ][ $today_pharmacy->reg_no ]['Latitude'];
				$long             = $coordinates[ $atts['city'] ][ $today_pharmacy->reg_no ]['Longitude'];
				$pharmacy_address = '<a href="http://www.google.com/maps/place/' . $lat . ',' . $long . '" target="_blank">' . $today_pharmacy->address . '</a>';
			} else {
				$pharmacy_address = $today_pharmacy->address;
			}
			$output .= '<h4>' . $today_pharmacy->surname . ' ' . $today_pharmacy->name . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>' . esc_html__( 'Address:', 'cyprus-pharmacies' ) . '</strong> ' . $pharmacy_address . '</li>';
			if ( isset ( $today_pharmacy->additional_address ) ) {
				$output .= '<li><strong>' . esc_html__( 'Additional Address info:', 'cyprus-pharmacies' ) . '</strong> ' . $today_pharmacy->additional_address . '</li>';
			}
			if ( isset ( $today_pharmacy->municipalitycommunity ) ) {
				$output .= '<li><strong>' . esc_html__( 'Municipality/Community:', 'cyprus-pharmacies' ) . '</strong> ' . $today_pharmacy->municipalitycommunity . '</li>';
			}
			if ( isset ( $today_pharmacy->pharmacy_tel_no ) ) {
				$output .= '<li><strong>' . esc_html__( 'Pharmacy Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="tel:' . $today_pharmacy->pharmacy_tel_no . '"> ' . $today_pharmacy->pharmacy_tel_no . ' </a></li>';
			}
			if ( isset ( $today_pharmacy->house_tel_no ) ) {
				$output .= '<li><strong>' . esc_html__( 'Home Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="tel:' . $today_pharmacy->house_tel_no . '"> ' . $today_pharmacy->house_tel_no . ' </a></li>';
			}
			$output .= '</ul>';
		}

		$output .= '<hr>';

		// Show tomorrow pharmacies.
		$output .= '<h3>' . date_i18n( 'l', strtotime( '+1 day' ) ) . ' , ' . date_i18n( 'j', strtotime( '+1 day' ) ) . ' ' . date_i18n( 'M', strtotime( '+1 day' ) ) . ' ' . date_i18n( 'Y', strtotime( '+1 day' ) ) . '<h3>';

		foreach ( $tomorrow_pharmacies as $tomorrow_pharmacy ) {
			if ( isset( $tomorrow_pharmacy->reg_no ) &&  isset( $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg_no ] ) ) {
				$lat              = $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg_no ]['Latitude'];
				$long             = $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg_no ]['Longitude'];
				$pharmacy_address = '<a href="http://www.google.com/maps/place/' . $lat . ',' . $long . '" target="_blank">' . $tomorrow_pharmacy->address . '</a>';
			} else {
				$pharmacy_address = $tomorrow_pharmacy->address;
			}
			$output .= '<h4>' . $tomorrow_pharmacy->surname . ' ' . $tomorrow_pharmacy->name . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>' . esc_html__( 'Address:', 'cyprus-pharmacies' ) . '</strong> ' . $pharmacy_address . '</li>';
			if ( isset ( $tomorrow_pharmacy->additional_address ) ) {
				$output .= '<li><strong>' . esc_html__( 'Additional Address info:', 'cyprus-pharmacies' ) . '</strong> ' . $tomorrow_pharmacy->additional_address . '</li>';
			}
			if ( isset ( $tomorrow_pharmacy->municipalitycommunity ) ) {
				$output .= '<li><strong>' . esc_html__( 'Municipality/Community:', 'cyprus-pharmacies' ) . '</strong> ' . $tomorrow_pharmacy->municipalitycommunity . '</li>';
			}
			if ( isset ( $tomorrow_pharmacy->pharmacy_tel_no ) ) {
				$output .= '<li><strong>' . esc_html__( 'Pharmacy Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="tel:' . $tomorrow_pharmacy->pharmacy_tel_no . '"> ' . $tomorrow_pharmacy->pharmacy_tel_no . ' </a></li>';
			}
			if ( isset ( $tomorrow_pharmacy->house_tel_no ) ) {
				$output .= '<li><strong>' . esc_html__( 'Home Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="tel:' . $tomorrow_pharmacy->house_tel_no . '"> ' . $tomorrow_pharmacy->house_tel_no . ' </a></li>';
			}
			$output .= '</ul>';
		}

		return $output;
	}

}

new CyPharm_Front();
