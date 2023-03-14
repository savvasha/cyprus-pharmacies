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

		$cities_ids = array(
			'Paphos'    => 'f2a52fa8-7132-4cf2-b897-beb7e0cb4250',
			'Limassol'  => '747a375f-4848-4fd0-82cf-509ea5cf72ae',
			'Nicosia'   => '2eef2142-75f6-496e-83d4-157e7cb00eeb',
			'Larnaca'   => '84f26551-984e-419a-88ee-200a1a3aea44',
			'Paralimni' => 'bb863934-b05d-4316-93bf-ffc8b0fb2194',
		);

		// Get correct city_id.
		$city_id = isset( $cities_ids[ $atts['city'] ] ) ? $cities_ids[ $atts['city'] ] : false;

		// Create the main url to call.
		if ( $city_id ) {
			$main_url = 'https://www.data.gov.cy/api/action/datastore/search.json?resource_id=' . $city_id;
		} else {
			return 'Wrong City selected';
		}

		// Get today pharmacies.
		$today_pharmacies = array();
		$today            = gmdate( 'j/n/Y' );
		$today_url        = $main_url . '&filters[date]=' . $today;
		$response         = wp_remote_get( $today_url, array( 'timeout' => 15 ) );
		if ( ! is_wp_error( $response ) ) {
			$contents         = wp_remote_retrieve_body( $response );
			$results          = json_decode( $contents );
			$today_pharmacies = $results->result->records;
		}

		// Get tomorrow pharmacies.
		$tomorrow_pharmacies = array();
		$tomorrow            = gmdate( 'j/n/Y', strtotime( '+1 day' ) );
		$tomorrow_url        = $main_url . '&filters[date]=' . $tomorrow;
		$response            = wp_remote_get( $tomorrow_url, array( 'timeout' => 15 ) );
		if ( ! is_wp_error( $response ) ) {
			$contents            = wp_remote_retrieve_body( $response );
			$results             = json_decode( $contents );
			$tomorrow_pharmacies = $results->result->records;
		}

		// Temporary code to receive the correct data of the pharmacies.
		$cities_greek    = array(
			'Paphos'    => 'Πάφος',
			'Limassol'  => 'Λεμεσός',
			'Nicosia'   => 'Λευκωσία',
			'Larnaca'   => 'Λάρνακα',
			'Paralimni' => 'Παραλίμνι',
		);
		$temp_pharmacies = array();
		$temp_url        = 'https://www.data.gov.cy/api/action/datastore/search.json?resource_id=82326f44-28f8-4de8-9367-2f6148db02f7&limit=1000&filters[district]=' . $cities_greek[ $atts['city'] ];
		$response        = wp_remote_get( $temp_url, array( 'timeout' => 15 ) );
		if ( ! is_wp_error( $response ) ) {
			$contents        = wp_remote_retrieve_body( $response );
			$results         = json_decode( $contents );
			$temp_pharmacies = $results->result->records;
		}
		$pharmacies = array();
		foreach ( $temp_pharmacies as $temp_pharmacy ) {
			$pharmacies[ $temp_pharmacy->reg_no ]['municipalitycommunity'] = $temp_pharmacy->municipalitycommunity;
			$pharmacies[ $temp_pharmacy->reg_no ]['pharmacy_tel_no']       = $temp_pharmacy->pharmacy_tel_no;
			$pharmacies[ $temp_pharmacy->reg_no ]['house_tel_no']          = $temp_pharmacy->house_tel_no;
		}
		// End temporary code.

		$greekmonths = array( 'Ιανουαρίου', 'Φεβρουαρίου', 'Μαρτίου', 'Απριλίου', 'Μαΐου', 'Ιουνίου', 'Ιουλίου', 'Αυγούστου', 'Σεπτεμβρίου', 'Οκτωβρίου', 'Νοεμβρίου', 'Δεκεμβρίου' );

		$coordinates = CyPharm_Coordinates::$coordinates;

		$output = '';

		// Show title.
		if ( $atts['title'] ) {

			$output .= '<h1>' . $atts['title'] . '</h1>';
		}

		// Show today pharmacies.
		$output .= '<h3>' . date_i18n( 'l' ) . ' , ' . date_i18n( 'j' ) . ' ' . $greekmonths[ date_i18n( 'n' ) - 1 ] . ' <h3>';
		foreach ( $today_pharmacies as $today_pharmacy ) {
			if ( isset( $coordinates[ $atts['city'] ][ $today_pharmacy->reg_no ] ) ) {
				$lat              = $coordinates[ $atts['city'] ][ $today_pharmacy->reg_no ]['Latitude'];
				$long             = $coordinates[ $atts['city'] ][ $today_pharmacy->reg_no ]['Longitude'];
				$pharmacy_address = '<a href="http://www.google.com/maps/place/' . $lat . ',' . $long . '" target="_blank">' . $today_pharmacy->address . '</a>';
			} else {
				$pharmacy_address = $today_pharmacy->address;
			}
			$output .= '<h4>' . $today_pharmacy->surname . ' ' . $today_pharmacy->name . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>Διεύθυνση</strong>: ' . $pharmacy_address . '</li>';
			$output .= '<li><strong>Περιοχή</strong>: ' . $today_pharmacy->additional_address_info . '</li>';
			// Temporary code to receive the correct data of the pharmacies.
			$municipalitycommunity = null;
			if ( isset( $pharmacies[ $today_pharmacy->reg_no ] ) ) {
				$municipalitycommunity = $pharmacies[ $today_pharmacy->reg_no ]['municipalitycommunity'];
			} elseif ( isset( $today_pharmacy->muniuciplity__community ) ) {
				$municipalitycommunity = $today_pharmacy->muniuciplity__community;
			} elseif ( isset( $today_pharmacy->municipality_community ) ) {
				$municipalitycommunity = $today_pharmacy->municipality_community;
			}
			// End temporary code.
			$output .= '<li><strong>Δήμος/Κοινότητα</strong>: ' . $municipalitycommunity . '</li>';
			// Temporary code to receive the correct data of the pharmacies.
			if ( isset( $pharmacies[ $today_pharmacy->reg_no ] ) ) {
				$pharmacy_tel_no = $pharmacies[ $today_pharmacy->reg_no ]['pharmacy_tel_no'];
			} else {
				$pharmacy_tel_no = $today_pharmacy->pharmacy_tel_no;
			}
			// End temporary code.
			$output .= '<li><strong>Τηλέφωνο Φαρμακείου</strong>: <a href="tel:' . $pharmacy_tel_no . '"> ' . $pharmacy_tel_no . ' </a></li>';
			// Temporary code to receive the correct data of the pharmacies.
			$house_tel_no = null;
			if ( isset( $pharmacies[ $today_pharmacy->reg_no ] ) ) {
				$house_tel_no = $pharmacies[ $today_pharmacy->reg_no ]['house_tel_no'];
			} elseif ( isset( $today_pharmacy->home_tel_no ) ) {
				$house_tel_no = $today_pharmacy->home_tel_no;
			} elseif ( isset( $today_pharmacy->house_tel_no ) ) {
				$house_tel_no = $today_pharmacy->house_tel_no;
			}
			// End temporary code.
			$output .= '<li><strong>Τηλέφωνο Οικίας</strong>: <a href="tel:' . $house_tel_no . '"> ' . $house_tel_no . ' </a></li>';
			$output .= '</ul>';
		}

		$output .= '<hr>';

		// Show tomorrow pharmacies.
		$output .= '<h3>' . date_i18n( 'l', strtotime( '+1 day' ) ) . ' , ' . date_i18n( 'j', strtotime( '+1 day' ) ) . ' ' . $greekmonths[ date_i18n( 'n', strtotime( '+1 day' ) ) - 1 ] . ' <h3>';
		foreach ( $tomorrow_pharmacies as $tomorrow_pharmacy ) {
			if ( isset( $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg_no ] ) ) {
				$lat              = $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg_no ]['Latitude'];
				$long             = $coordinates[ $atts['city'] ][ $tomorrow_pharmacy->reg_no ]['Longitude'];
				$pharmacy_address = '<a href="http://www.google.com/maps/place/' . $lat . ',' . $long . '" target="_blank">' . $tomorrow_pharmacy->address . '</a>';
			} else {
				$pharmacy_address = $tomorrow_pharmacy->address;
			}
			$output .= '<h4>' . $tomorrow_pharmacy->surname . ' ' . $tomorrow_pharmacy->name . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>Διεύθυνση</strong>: ' . $pharmacy_address . '</li>';
			$output .= '<li><strong>Περιοχή</strong>: ' . $tomorrow_pharmacy->additional_address_info . '</li>';
			// Temporary code to receive the correct data of the pharmacies.
			$municipalitycommunity = null;
			if ( isset( $pharmacies[ $tomorrow_pharmacy->reg_no ] ) ) {
				$municipalitycommunity = $pharmacies[ $tomorrow_pharmacy->reg_no ]['municipalitycommunity'];
			} elseif ( isset( $tomorrow_pharmacy->muniuciplity__community ) ) {
				$municipalitycommunity = $tomorrow_pharmacy->muniuciplity__community;
			} elseif ( isset( $tomorrow_pharmacy->municipality_community ) ) {
				$municipalitycommunity = $tomorrow_pharmacy->municipality_community;
			}
			// End temporary code.
			$output .= '<li><strong>Δήμος/Κοινότητα</strong>: ' . $municipalitycommunity . '</li>';
			// Temporary code to receive the correct data of the pharmacies.
			if ( isset( $pharmacies[ $tomorrow_pharmacy->reg_no ] ) ) {
				$pharmacy_tel_no = $pharmacies[ $tomorrow_pharmacy->reg_no ]['pharmacy_tel_no'];
			} else {
				$pharmacy_tel_no = $tomorrow_pharmacy->pharmacy_tel_no;
			}
			// End temporary code.
			$output .= '<li><strong>Τηλέφωνο Φαρμακείου</strong>: <a href="tel:' . $pharmacy_tel_no . '"> ' . $pharmacy_tel_no . ' </a></li>';
			// Temporary code to receive the correct data of the pharmacies.
			$house_tel_no = null;
			if ( isset( $pharmacies[ $tomorrow_pharmacy->reg_no ] ) ) {
				$house_tel_no = $pharmacies[ $tomorrow_pharmacy->reg_no ]['house_tel_no'];
			} elseif ( isset( $tomorrow_pharmacy->home_tel_no ) ) {
				$house_tel_no = $tomorrow_pharmacy->home_tel_no;
			} elseif ( isset( $tomorrow_pharmacy->house_tel_no ) ) {
				$house_tel_no = $tomorrow_pharmacy->house_tel_no;
			}
			// End temporary code.
			$output .= '<li><strong>Τηλέφωνο Οικίας</strong>: <a href="tel:' . $house_tel_no . '"> ' . $house_tel_no . ' </a></li>';
			$output .= '</ul>';
		}

		return $output;
	}

}

new CyPharm_Front();
