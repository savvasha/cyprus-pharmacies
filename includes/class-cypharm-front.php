<?php

/**
 * Frontend functionality
 *
 * @package Cyprus Pharmacies
 * @subpackage Front
 * @author Savvas
 */

class cypharm_Front {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_shortcode( 'cypharm', array( $this, 'cypharm_shortcode' ) );
	}

	public function cypharm_shortcode( $atts ) {

		// Attributes
		$atts = shortcode_atts(
			array(
				'city'  => 'Paphos',
				'title' => false,
			),
			$atts,
			'cypharm'
		);

		$cities_ids = array(
			'Paphos'    => 'f2a52fa8-7132-4cf2-b897-beb7e0cb4250',
			'Pafos'     => 'f2a52fa8-7132-4cf2-b897-beb7e0cb4250',
			'Limassol'  => '747a375f-4848-4fd0-82cf-509ea5cf72ae',
			'Nicosia'   => '2eef2142-75f6-496e-83d4-157e7cb00eeb',
			'Larnaca'   => '84f26551-984e-419a-88ee-200a1a3aea44',
			'Paralimni' => 'bb863934-b05d-4316-93bf-ffc8b0fb2194',
		);

		// Get correct city_id
		$city_id = isset( $cities_ids[ $atts['city'] ] ) ? $cities_ids[ $atts['city'] ] : false;

		// Create the main url to call
		if ( $city_id ) {
			$main_url = 'https://www.data.gov.cy/api/action/datastore/search.json?resource_id=' . $city_id;
		} else {
			return 'Wrong City selected';
		}

		// Get today pharmacies
		$today            = date( 'j/n/Y' );
		$today_url        = $main_url . '&filters[date]=' . $today;
		$contents         = file_get_contents( $today_url );
		$contents         = utf8_encode( $contents );
		$results          = json_decode( $contents );
		$today_pharmacies = $results->result->records;

		// Get tomorrow pharmacies
		$tomorrow            = date( 'j/n/Y', strtotime( '+1 day' ) );
		$tomorrow_url        = $main_url . '&filters[date]=' . $tomorrow;
		$contents            = file_get_contents( $tomorrow_url );
		$contents            = utf8_encode( $contents );
		$results             = json_decode( $contents );
		$tomorrow_pharmacies = $results->result->records;

		$greekmonths = array( 'Ιανουαρίου', 'Φεβρουαρίου', 'Μαρτίου', 'Απριλίου', 'Μαΐου', 'Ιουνίου', 'Ιουλίου', 'Αυγούστου', 'Σεπτεμβρίου', 'Οκτωβρίου', 'Νοεμβρίου', 'Δεκεμβρίου' );

		$output = '';

		// Show title
		if ( $atts['title'] ) {

			$output .= '<h1>' . $atts['title'] . '</h1>';
		}

		// Show today pharmacies
		$output .= '<h3>' . date_i18n( 'l' ) . ' , ' . date_i18n( 'j' ) . ' ' . $greekmonths[ date_i18n( 'n' ) - 1 ] . ' <h3>';
		foreach ( $today_pharmacies as $today_pharmacy ) {
			$output .= '<h4>' . $today_pharmacy->surmame . ' ' . $today_pharmacy->name . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>Διεύθυνση</strong>: ' . $today_pharmacy->address . '</li>';
			$output .= '<li><strong>Περιοχή</strong>: ' . $today_pharmacy->additional_address_info . '</li>';
			$output .= '<li><strong>Δήμος/Κοινότητα</strong>: ' . $today_pharmacy->muniuciplity__community . '</li>';
			$output .= '<li><strong>Τηλέφωνο Φαρμακείου</strong>: <a href="tel:' . $today_pharmacy->pharmacy_tel_no . '"> ' . $today_pharmacy->pharmacy_tel_no . ' </a></li>';
			$output .= '<li><strong>Τηλέφωνο Οικίας</strong>: <a href="tel:' . $today_pharmacy->house_tel_no . '"> ' . $today_pharmacy->house_tel_no . ' </a></li>';
			$output .= '</ul>';
		}

		$output .= '<hr>';

		// Show tomorrow pharmacies
		$output .= '<h3>' . date_i18n( 'l', strtotime( '+1 day' ) ) . ' , ' . date_i18n( 'j', strtotime( '+1 day' ) ) . ' ' . $greekmonths[ date_i18n( 'n', strtotime( '+1 day' ) ) - 1 ] . ' <h3>';
		foreach ( $tomorrow_pharmacies as $tomorrow_pharmacy ) {
			$output .= '<h4>' . $tomorrow_pharmacy->surmame . ' ' . $tomorrow_pharmacy->name . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>Διεύθυνση</strong>: ' . $tomorrow_pharmacy->address . '</li>';
			$output .= '<li><strong>Περιοχή</strong>: ' . $tomorrow_pharmacy->additional_address_info . '</li>';
			$output .= '<li><strong>Δήμος/Κοινότητα</strong>: ' . $tomorrow_pharmacy->muniuciplity__community . '</li>';
			$output .= '<li><strong>Τηλέφωνο Φαρμακείου</strong>: <a href="tel:' . $tomorrow_pharmacy->pharmacy_tel_no . '"> ' . $tomorrow_pharmacy->pharmacy_tel_no . ' </a></li>';
			$output .= '<li><strong>Τηλέφωνο Οικίας</strong>: <a href="tel:' . $tomorrow_pharmacy->house_tel_no . '"> ' . $tomorrow_pharmacy->house_tel_no . ' </a></li>';
			$output .= '</ul>';
		}

		return $output;
	}

}

new cypharm_Front();
