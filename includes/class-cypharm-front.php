<?php
/**
 * Frontend functionality.
 *
 * @package Cyprus Pharmacies
 * @subpackage Front
 */

defined( 'ABSPATH' ) || exit;

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

		// Validate city parameter against allowed values.
		if ( ! is_string( $atts['city'] ) || ! array_key_exists( $atts['city'], CyPharm_Data::CITIES ) ) {
			$atts['city'] = 'Paphos';
		}

		// Today and tomorrow in the timezone the schedule is defined in.
		$today    = new DateTimeImmutable( 'today', new DateTimeZone( CyPharm_Data::TIMEZONE ) );
		$tomorrow = $today->modify( '+1 day' );

		$output = '';

		// Show title if set.
		if ( $atts['title'] ) {
			$output .= '<h1>' . esc_html( $atts['title'] ) . '</h1>';
		}

		$output .= $this->render_day( $atts['city'], $today );
		$output .= '<hr>';
		$output .= $this->render_day( $atts['city'], $tomorrow );

		return $output;
	}

	/**
	 * Renders the heading and the pharmacies of one day.
	 *
	 * @param string            $city City name (one of CyPharm_Data::CITIES keys).
	 * @param DateTimeImmutable $day  Midnight of the day, in the schedule timezone.
	 * @return string
	 */
	private function render_day( $city, $day ) {
		$cypharm       = new CyPharm_Data();
		$cypharm->city = $city;
		$cypharm->date = $day->format( 'Y-m-d' );
		$pharmacies    = $cypharm->data();

		$coordinates = CyPharm_Coordinates::get();

		/* translators: Date format of the day headings, see https://www.php.net/manual/datetime.format.php */
		$heading_format = __( 'l, j M Y', 'cyprus-pharmacies' );

		$output = '<h3>' . esc_html( wp_date( $heading_format, $day->getTimestamp(), $day->getTimezone() ) ) . '</h3>';

		foreach ( $pharmacies as $pharmacy ) {
			$reg_no  = self::field( $pharmacy, 'reg__no_' );
			$address = esc_html( self::field( $pharmacy, 'address' ) );

			if ( '' !== $reg_no && isset( $coordinates[ $city ][ $reg_no ]['Latitude'], $coordinates[ $city ][ $reg_no ]['Longitude'] ) && ! isset( $pharmacy->geolocation ) ) {
				$lat  = $coordinates[ $city ][ $reg_no ]['Latitude'];
				$long = $coordinates[ $city ][ $reg_no ]['Longitude'];
				if ( is_numeric( $lat ) && is_numeric( $long ) ) {
					$address = '<a href="' . esc_url( 'https://www.google.com/maps/place/' . $lat . ',' . $long ) . '" target="_blank" rel="noopener noreferrer">' . $address . '</a>';
				}
			}

			$output .= '<h4>' . esc_html( trim( self::field( $pharmacy, 'surmame' ) . ' ' . self::field( $pharmacy, 'name' ) ) ) . '</h4>';
			$output .= '<ul>';
			$output .= '<li><strong>' . esc_html__( 'Address:', 'cyprus-pharmacies' ) . '</strong> ' . $address . '</li>';

			$additional_info = self::field( $pharmacy, 'additional_address_info' );
			if ( '' !== $additional_info ) {
				$output .= '<li><strong>' . esc_html__( 'Additional Address info:', 'cyprus-pharmacies' ) . '</strong> ' . esc_html( $additional_info ) . '</li>';
			}

			$municipality = self::field( $pharmacy, 'muniuciplity__community' );
			if ( '' !== $municipality ) {
				$output .= '<li><strong>' . esc_html__( 'Municipality/Community:', 'cyprus-pharmacies' ) . '</strong> ' . esc_html( $municipality ) . '</li>';
			}

			$pharmacy_tel = self::field( $pharmacy, 'pharmacy_tel__no_' );
			if ( '' !== $pharmacy_tel ) {
				$output .= '<li><strong>' . esc_html__( 'Pharmacy Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="' . esc_url( 'tel:' . $pharmacy_tel, array( 'tel' ) ) . '"> ' . esc_html( $pharmacy_tel ) . ' </a></li>';
			}

			$house_tel = self::field( $pharmacy, 'house_tel__no_' );
			if ( '' !== $house_tel ) {
				$output .= '<li><strong>' . esc_html__( 'Home Telephone:', 'cyprus-pharmacies' ) . '</strong> <a href="' . esc_url( 'tel:' . $house_tel, array( 'tel' ) ) . '"> ' . esc_html( $house_tel ) . ' </a></li>';
			}

			$output .= '</ul>';
		}

		return $output;
	}

	/**
	 * Reads a scalar field of a record as a trimmed string.
	 *
	 * @param mixed  $record Pharmacy record.
	 * @param string $key    Field name.
	 * @return string Empty string when the field is missing or not scalar.
	 */
	private static function field( $record, $key ) {
		if ( ! is_object( $record ) || ! isset( $record->$key ) || ! is_scalar( $record->$key ) ) {
			return '';
		}

		return trim( (string) $record->$key );
	}
}

new CyPharm_Front();
