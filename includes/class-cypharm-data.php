<?php
/**
 * Data functionality.
 *
 * @package Cyprus Pharmacies
 * @subpackage Data
 */

defined( 'ABSPATH' ) || exit;

/**
 * Data functionality
 *
 * @package Cyprus Pharmacies
 * @subpackage Data
 * @author Savvas
 */
class CyPharm_Data {

	/**
	 * Timezone the on-duty schedule is defined in.
	 *
	 * @var string
	 */
	const TIMEZONE = 'Asia/Nicosia';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @var int
	 */
	const DEFAULT_CACHE_DURATION = 43200;

	/**
	 * Option holding the cache generation; bumping it invalidates all cached data.
	 *
	 * @var string
	 */
	const CACHE_GEN_OPTION = 'cypharm_cache_gen';

	/**
	 * Supported cities and their data.gov.cy resource IDs.
	 *
	 * @var array
	 */
	const CITIES = array(
		'Paphos'    => '802df2db-2b28-4bb3-b355-e437acdf728d',
		'Limassol'  => '97282b19-bc01-48e4-983e-5ff65a1fb135',
		'Nicosia'   => '468b39e8-811d-4586-8e5e-37533d801575',
		'Larnaca'   => '84ff41ba-65b8-4ec7-9f31-8130fbe2d1b1',
		'Paralimni' => 'cdf7ff43-1928-4e6a-a3b9-ff4228cefbfe',
	);

	/**
	 * The city for which we want the data (one of the CITIES keys).
	 *
	 * @var string
	 */
	public $city;

	/**
	 * The date for which we want the data, as Y-m-d.
	 *
	 * @var string
	 */
	public $date;

	/**
	 * Cache duration in seconds; 0 disables caching.
	 *
	 * @var int
	 */
	private $cache_duration;

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->cache_duration = self::get_cache_duration();
	}

	/**
	 * Cache duration in seconds, as filtered by `cypharm_cache_duration`.
	 *
	 * @return int Seconds; 0 means caching is disabled.
	 */
	public static function get_cache_duration() {
		$duration = apply_filters( 'cypharm_cache_duration', self::DEFAULT_CACHE_DURATION );

		return is_numeric( $duration ) ? max( 0, (int) $duration ) : self::DEFAULT_CACHE_DURATION;
	}

	/**
	 * Returns the on-duty pharmacies for $this->city on $this->date, with caching.
	 *
	 * @access public
	 * @return array List of pharmacy records (objects).
	 */
	public function data() {
		if ( ! is_string( $this->city ) || ! array_key_exists( $this->city, self::CITIES ) || ! self::parse_date( $this->date ) ) {
			return array();
		}

		// Optional custom API; on any failure fall back to data.gov.cy for this request.
		$api = CyPharm_Settings::api_config();
		if ( null !== $api ) {
			$records = $this->cached(
				'api',
				function () use ( $api ) {
					return $this->fetch_api( $api );
				}
			);
			if ( null !== $records ) {
				return $records;
			}
		}

		$records = $this->cached( 'datagov', array( $this, 'fetch_datagov' ) );

		return null === $records ? array() : $records;
	}

	/**
	 * Returns records from cache, or fetches and caches them.
	 *
	 * Only non-empty successful results are cached: an empty result usually
	 * means the schedule for that date is not published yet.
	 *
	 * @param string   $source  Source identifier, used in the cache key.
	 * @param callable $fetcher Returns an array of records, or null on failure.
	 * @return array|null Records, or null on failure.
	 */
	private function cached( $source, $fetcher ) {
		$cache_key = $this->cache_key( $source );

		if ( $this->cache_duration > 0 ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$records = call_user_func( $fetcher );

		if ( ! empty( $records ) && $this->cache_duration > 0 ) {
			set_transient( $cache_key, $records, $this->cache_duration );
		}

		return $records;
	}

	/**
	 * Builds the transient key for the current city/date and the given source.
	 *
	 * @param string $source Source identifier.
	 * @return string
	 */
	private function cache_key( $source ) {
		$prefix = 'datagov' === $source ? 'cypharm_data_' : 'cypharm_data_' . sanitize_key( $source ) . '_';

		return $prefix . sanitize_key( $this->city ) . '_' . sanitize_key( $this->date ) . '_g' . (int) get_option( self::CACHE_GEN_OPTION, 0 );
	}

	/**
	 * Fetches the records for the current city/date from data.gov.cy.
	 *
	 * @return array|null Records, or null on failure.
	 */
	private function fetch_datagov() {
		$day = self::parse_date( $this->date );

		// data.gov.cy stores dates in several formats; ask for all of them.
		$dates = implode(
			',',
			array(
				$day->format( 'j/n/Y' ),
				$day->format( 'j/n/y' ),
				$day->format( 'd/m/y' ),
				$day->format( 'd/m/Y' ),
			)
		);

		$url = 'https://www.data.gov.cy/api/action/datastore/search.json?' . http_build_query(
			array(
				'resource_id' => self::CITIES[ $this->city ],
				'limit'       => 50,
				'filters'     => array( 'date' => $dates ),
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 8,
				'sslverify'  => true,
				'user-agent' => self::user_agent(),
			)
		);

		$result = self::parse_records_response( $response );

		if ( is_string( $result ) ) {
			self::log( 'data.gov.cy request failed (' . $result . ').' );
			return null;
		}

		return $result;
	}

	/**
	 * Fetches the records for the current city/date from the custom API.
	 *
	 * @param array $api Credentials from CyPharm_Settings::api_config().
	 * @return array|null Records, or null on failure.
	 */
	private function fetch_api( $api ) {
		$result = self::request_api( $api, $this->city, $this->date );

		if ( is_string( $result ) ) {
			// Only the error code is logged, never the credentials or headers.
			self::log( 'Custom API request failed (' . $result . '); falling back to data.gov.cy.' );
			return null;
		}

		return $result;
	}

	/**
	 * Requests the on-duty pharmacies from the custom API.
	 *
	 * Without valid credentials Cloudflare Access answers first (403, or a
	 * 302 to an HTML login page), so redirects are not followed and only a
	 * 200 with valid JSON and a `result.records` array is accepted.
	 *
	 * @param array  $api  Array with url, client_id and client_secret.
	 * @param string $city City (one of the CITIES keys).
	 * @param string $date Date as Y-m-d.
	 * @return array|string Records on success, or a short error code.
	 */
	private static function request_api( $api, $city, $date ) {
		$url = $api['url'] . '/api/v1/datagov?' . http_build_query(
			array(
				'city' => $city,
				'date' => $date,
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'sslverify'   => true,
				'user-agent'  => self::user_agent(),
				'headers'     => array(
					'Accept'                  => 'application/json',
					'CF-Access-Client-Id'     => $api['client_id'],
					'CF-Access-Client-Secret' => $api['client_secret'],
				),
			)
		);

		return self::parse_records_response( $response );
	}

	/**
	 * Tests the custom API with today's pharmacies of Nicosia.
	 *
	 * @param array $api Array with url, client_id and client_secret.
	 * @return string 'ok' or a short error code (never the response body).
	 */
	public static function test_api( $api ) {
		$result = self::request_api( $api, 'Nicosia', wp_date( 'Y-m-d', null, new DateTimeZone( self::TIMEZONE ) ) );

		return is_array( $result ) ? 'ok' : $result;
	}

	/**
	 * Validates a remote response and extracts `result.records`.
	 *
	 * @param array|WP_Error $response Response from wp_remote_get().
	 * @return array|string Records on success, or a short error code.
	 */
	protected static function parse_records_response( $response ) {
		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			if ( false !== stripos( $message, 'timed out' ) || false !== stripos( $message, 'cURL error 28' ) ) {
				return 'timeout';
			}
			return 'connection_failed';
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return 'http_' . $code;
		}

		$results = json_decode( wp_remote_retrieve_body( $response ) );
		if ( JSON_ERROR_NONE !== json_last_error() || ! isset( $results->result->records ) || ! is_array( $results->result->records ) ) {
			return 'invalid_response';
		}

		return array_values( array_filter( $results->result->records, 'is_object' ) );
	}

	/**
	 * Parses a Y-m-d date in the schedule timezone.
	 *
	 * @param mixed $date Date string.
	 * @return DateTimeImmutable|false
	 */
	public static function parse_date( $date ) {
		if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}

		$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, new DateTimeZone( self::TIMEZONE ) );

		return ( $day && $day->format( 'Y-m-d' ) === $date ) ? $day : false;
	}

	/**
	 * User-Agent sent with remote requests.
	 *
	 * @return string
	 */
	protected static function user_agent() {
		return 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' );
	}

	/**
	 * Writes a message to the debug log, only when WP_DEBUG_LOG is enabled.
	 *
	 * @param string $message Message; must never contain credentials.
	 * @return void
	 */
	protected static function log( $message ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( 'Cyprus Pharmacies: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Clear all cached data for this plugin.
	 *
	 * Bumping the cache generation invalidates every cached entry, including
	 * those held in a persistent object cache. Without an external object
	 * cache, the stale rows are also removed from the options table.
	 *
	 * @access public
	 * @return void
	 */
	public function clear_cache() {
		global $wpdb;

		update_option( self::CACHE_GEN_OPTION, (int) get_option( self::CACHE_GEN_OPTION, 0 ) + 1, false );

		if ( wp_using_ext_object_cache() ) {
			return;
		}

		// Direct query is required to bulk-remove transients by name; there is nothing to cache.
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_cypharm_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_cypharm_' ) . '%'
			)
		);
	}
}
