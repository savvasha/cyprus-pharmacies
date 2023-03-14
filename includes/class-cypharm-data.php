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

		$data           = array();
		$pharmacies     = array();
		$pharmacies_ids = array();
		$cities_ids     = array(
			'Paphos'    => 'f2a52fa8-7132-4cf2-b897-beb7e0cb4250',
			'Limassol'  => '747a375f-4848-4fd0-82cf-509ea5cf72ae',
			'Nicosia'   => '2eef2142-75f6-496e-83d4-157e7cb00eeb',
			'Larnaca'   => '84f26551-984e-419a-88ee-200a1a3aea44',
			'Paralimni' => 'bb863934-b05d-4316-93bf-ffc8b0fb2194',
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
			$pharmacies_ids[] = $pharmacy->reg_no;
		}

		$data = $this->cypharms( $pharmacies_ids );

		return $data;
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

		$temp_pharmacies = array();
		$url             = 'https://www.data.gov.cy/api/action/datastore/search.json?resource_id=82326f44-28f8-4de8-9367-2f6148db02f7&limit=1000&filters[reg_no]=' . $ids;
		$response        = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( ! is_wp_error( $response ) ) {
			$contents        = wp_remote_retrieve_body( $response );
			$results         = json_decode( $contents );
			$temp_pharmacies = $results->result->records;
		}
		$pharmacies = array();
		foreach ( $temp_pharmacies as $temp_pharmacy ) {
			$pharmacies[ $temp_pharmacy->reg_no ] = $temp_pharmacy;
		}

		return $pharmacies;
	}

}
