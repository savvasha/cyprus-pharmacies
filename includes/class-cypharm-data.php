<?php

/**
 * Data functionality
 *
 * @package Cyprus Pharmacies
 * @subpackage Data
 * @author Savvas
 */
class CyPharm_Data {

	/** @var string The url of data. */
	public $url;

	/** @var string The city for which we want the data. */
	public $city;

	/** @var string The date for which we want the data. */
	public $date;

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
	}

	/**
	 * Returns formatted data.
	 *
	 * @access public
	 * @return array
	 */
	public function data() {

		$cyphar_data    = array();
		$pharmacies     = array();
		$pharmacies_ids = array();
		$cities_ids     = array(
			'Paphos'    => '802df2db-2b28-4bb3-b355-e437acdf728d',
			'Limassol'  => '97282b19-bc01-48e4-983e-5ff65a1fb135',
			'Nicosia'   => '468b39e8-811d-4586-8e5e-37533d801575',
			'Larnaca'   => '84ff41ba-65b8-4ec7-9f31-8130fbe2d1b1',
			'Paralimni' => 'cdf7ff43-1928-4e6a-a3b9-ff4228cefbfe',
		);

		// Get correct city_id.
		$city_id = isset( $cities_ids[ $this->city ] ) ? $cities_ids[ $this->city ] : false;

		// Create the main url to call.
		if ( $city_id ) {
			$main_url = 'https://www.data.gov.cy/api/action/datastore/search.json?resource_id=' . $city_id;
		} else {
			return $cyphar_data;
		}	

		$date = $this->date;

		$url      = $main_url . '&filters[date]=' . $date;
		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( ! is_wp_error( $response ) ) {
			$contents   = wp_remote_retrieve_body( $response );
			$results    = json_decode( $contents );
			$pharmacies = $results->result->records;
		}
		foreach ( $pharmacies as $pharmacy ) {
			$pharmacies_ids[] = $pharmacy->reg__no_;
		}
		//$cyphar_data = $this->cypharms( $pharmacies_ids );

		$cyphar_data = $pharmacies; //Bypass another change from data.gov...

		return $cyphar_data;
	}

	/**
	 * Returns an array with all registered private pharmacies.
	 *
	 * @access public
	 * @param array $pharms_ids An array with the ids of pharmacies we need to get info.
	 * @return array
	 */
	public function cypharms( $pharms_ids = array() ) {

		if ( is_array( $pharms_ids ) ) {
			$ids = implode( ',', $pharms_ids );
		} else {
			$ids = array();
		}

		$pharmacies = array();
		$temp_pharmacies = array();
		$url             = 'https://www.data.gov.cy/api/action/datastore/search.json?resource_id=82326f44-28f8-4de8-9367-2f6148db02f7&limit=1000&filters[reg_no_]=' . $ids;
		$response        = wp_remote_get( $url, array( 'timeout' => 15 ) );
		
		if ( is_wp_error( $response ) ) {
			return array();
		}
		
		$contents        = wp_remote_retrieve_body( $response );
		$results         = json_decode( $contents );
		$temp_pharmacies = $results->result->records;
		
		foreach ( $temp_pharmacies as $temp_pharmacy ) {
			$pharmacies[ $temp_pharmacy->reg_no_ ] = $temp_pharmacy;
		}

		return apply_filters( 'cypharm_pharmacies', $pharmacies, $pharms_ids );

	}

}
