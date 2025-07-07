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

	/** @var int Cache duration in seconds (12 hours). */
	private $cache_duration = 43200;

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
		// Allow developers to modify cache duration
		$this->cache_duration = apply_filters( 'cypharm_cache_duration', $this->cache_duration );
	}

	/**
	 * Returns formatted data with caching.
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

		// Create cache key based on city and date
		$cache_key = 'cypharm_data_' . sanitize_key( $this->city ) . '_' . sanitize_key( $date );
		
		// Try to get cached data first
		$cached_data = get_transient( $cache_key );
		
		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$url      = $main_url . '&filters[date]=' . $date;
		$response = wp_remote_get( $url, array( 
			'timeout' => 15,
			'sslverify' => true,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' )
		) );

		if ( ! is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			
			// Check if the response is successful
			if ( 200 === $response_code ) {
				$contents = wp_remote_retrieve_body( $response );
				$results  = json_decode( $contents );
				
				// Validate JSON decode success and object structure
				if ( json_last_error() === JSON_ERROR_NONE && isset( $results->result->records ) ) {
					$pharmacies = $results->result->records;
				} else {
					// Log error for debugging
					error_log( 'Cyprus Pharmacies: Invalid JSON response or missing records structure' );
					return $cyphar_data;
				}
			} else {
				// Log error for debugging
				error_log( 'Cyprus Pharmacies: API request failed with response code: ' . $response_code );
				return $cyphar_data;
			}
		} else {
			// Log error for debugging
			error_log( 'Cyprus Pharmacies: API request failed: ' . $response->get_error_message() );
			return $cyphar_data;
		}
		
		foreach ( $pharmacies as $pharmacy ) {
			$pharmacies_ids[] = $pharmacy->reg__no_;
		}
		//$cyphar_data = $this->cypharms( $pharmacies_ids );

		$cyphar_data = $pharmacies; //Bypass another change from data.gov...

		// Cache the data for future requests
		set_transient( $cache_key, $cyphar_data, $this->cache_duration );

		return $cyphar_data;
	}

	/**
	 * Returns an array with all registered private pharmacies with caching.
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

		// Create cache key for secondary API call
		$cache_key = 'cypharm_pharmacies_' . md5( $ids );
		
		// Try to get cached data first
		$cached_data = get_transient( $cache_key );
		
		if ( false !== $cached_data ) {
			return apply_filters( 'cypharm_pharmacies', $cached_data, $pharms_ids );
		}

		$pharmacies = array();
		$temp_pharmacies = array();
		$url             = 'https://www.data.gov.cy/api/action/datastore/search.json?resource_id=82326f44-28f8-4de8-9367-2f6148db02f7&limit=1000&filters[reg_no_]=' . $ids;
		$response        = wp_remote_get( $url, array( 
			'timeout' => 15,
			'sslverify' => true,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' )
		) );
		
		if ( is_wp_error( $response ) ) {
			// Log error for debugging
			error_log( 'Cyprus Pharmacies: Secondary API request failed: ' . $response->get_error_message() );
			return array();
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		
		// Check if the response is successful
		if ( 200 !== $response_code ) {
			// Log error for debugging
			error_log( 'Cyprus Pharmacies: Secondary API request failed with response code: ' . $response_code );
			return array();
		}
		
		$contents = wp_remote_retrieve_body( $response );
		$results  = json_decode( $contents );
		
		// Validate JSON decode success and object structure
		if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $results->result->records ) ) {
			// Log error for debugging
			error_log( 'Cyprus Pharmacies: Invalid JSON response or missing records structure in secondary API' );
			return array();
		}
		
		$temp_pharmacies = $results->result->records;
		
		foreach ( $temp_pharmacies as $temp_pharmacy ) {
			$pharmacies[ $temp_pharmacy->reg_no_ ] = $temp_pharmacy;
		}

		// Cache the data for future requests
		set_transient( $cache_key, $pharmacies, $this->cache_duration );

		return apply_filters( 'cypharm_pharmacies', $pharmacies, $pharms_ids );

	}

	/**
	 * Clear all cached data for this plugin.
	 *
	 * @access public
	 * @return void
	 */
	public function clear_cache() {
		global $wpdb;
		
		// Delete all transients that start with 'cypharm_'
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 
				'_transient_cypharm_%' 
			) 
		);
		
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 
				'_transient_timeout_cypharm_%' 
			) 
		);
	}

}
