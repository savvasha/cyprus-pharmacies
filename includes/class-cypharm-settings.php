<?php
/**
 * Settings and admin page.
 *
 * @package Cyprus Pharmacies
 * @subpackage Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings (data source, custom API credentials) and the admin page.
 *
 * @package Cyprus Pharmacies
 * @subpackage Settings
 * @author Savvas
 */
class CyPharm_Settings {

	/**
	 * Admin page slug (kept for backwards compatible links).
	 *
	 * @var string
	 */
	const PAGE = 'cyprus-pharmacies-cache';

	/**
	 * Settings API option group.
	 *
	 * @var string
	 */
	const GROUP = 'cypharm_settings';

	/**
	 * Option names.
	 *
	 * @var string
	 */
	const OPT_SOURCE        = 'cypharm_source';
	const OPT_API_URL       = 'cypharm_api_url';
	const OPT_CLIENT_ID     = 'cypharm_api_client_id';
	const OPT_CLIENT_SECRET = 'cypharm_api_client_secret';

	/**
	 * Data sources.
	 *
	 * @var string
	 */
	const SOURCE_DATAGOV = 'datagov';
	const SOURCE_API     = 'custom_api';

	/**
	 * Placeholder shown in the API URL field.
	 *
	 * @var string
	 */
	const API_URL_PLACEHOLDER = 'https://api.example.com';

	/**
	 * Admin-post action of the "Test connection" button.
	 *
	 * @var string
	 */
	const TEST_ACTION = 'cypharm_test_connection';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'load-options.php', array( __CLASS__, 'ensure_options' ) );
		add_action( 'admin_notices', array( __CLASS__, 'incomplete_config_notice' ) );
		add_action( 'admin_post_' . self::TEST_ACTION, array( __CLASS__, 'test_connection' ) );
		add_filter( 'plugin_action_links_' . CYPHARM_PLUGIN_BASE, array( __CLASS__, 'plugin_action_links' ) );

		// Changing the source or the credentials invalidates the cached data.
		foreach ( array_keys( self::defaults() ) as $option ) {
			add_action( 'add_option_' . $option, array( __CLASS__, 'invalidate_cache' ) );
			add_action( 'update_option_' . $option, array( __CLASS__, 'invalidate_cache' ) );
		}
	}

	/**
	 * Option names and their defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			self::OPT_SOURCE        => self::SOURCE_DATAGOV,
			self::OPT_API_URL       => '',
			self::OPT_CLIENT_ID     => '',
			self::OPT_CLIENT_SECRET => '',
		);
	}

	/**
	 * Creates the options with autoload off, so that the Settings API
	 * (which calls update_option() without an autoload flag) does not
	 * create them autoloaded.
	 *
	 * @return void
	 */
	public static function ensure_options() {
		foreach ( self::defaults() as $option => $default ) {
			// add_option() runs the sanitize callback even for existing options, so check first.
			if ( null === get_option( $option, null ) ) {
				add_option( $option, $default, '', false );
			}
		}
	}

	/**
	 * The selected data source.
	 *
	 * @return string
	 */
	public static function source() {
		return self::SOURCE_API === get_option( self::OPT_SOURCE, self::SOURCE_DATAGOV ) ? self::SOURCE_API : self::SOURCE_DATAGOV;
	}

	/**
	 * The saved custom API credentials, regardless of the selected source.
	 *
	 * @return array|null Array with url, client_id and client_secret, or null if incomplete.
	 */
	public static function saved_api_credentials() {
		$credentials = array(
			'url'           => self::normalize_api_url( (string) get_option( self::OPT_API_URL, '' ) ),
			'client_id'     => self::clean_token( get_option( self::OPT_CLIENT_ID, '' ) ),
			'client_secret' => self::clean_token( get_option( self::OPT_CLIENT_SECRET, '' ) ),
		);

		return in_array( '', $credentials, true ) ? null : $credentials;
	}

	/**
	 * The custom API credentials, if the custom API is selected and fully configured.
	 *
	 * @return array|null
	 */
	public static function api_config() {
		return self::SOURCE_API === self::source() ? self::saved_api_credentials() : null;
	}

	/**
	 * Whether the custom API is selected but not fully configured.
	 *
	 * @return bool
	 */
	public static function is_config_incomplete() {
		return self::SOURCE_API === self::source() && null === self::saved_api_credentials();
	}

	/**
	 * Invalidates all cached pharmacy data.
	 *
	 * @return void
	 */
	public static function invalidate_cache() {
		( new CyPharm_Data() )->clear_cache();
	}

	/**
	 * Normalizes an API base URL: https only, no trailing slash, no trailing /api/v1.
	 *
	 * @param string $value Raw URL.
	 * @return string Normalized URL, or '' if invalid.
	 */
	public static function normalize_api_url( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		if ( false === strpos( $value, '://' ) ) {
			$value = 'https://' . $value;
		}

		$url   = esc_url_raw( $value, array( 'https' ) );
		$parts = '' === $url ? false : wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! isset( $parts['scheme'] ) || 'https' !== strtolower( $parts['scheme'] )
			|| isset( $parts['query'] ) || isset( $parts['fragment'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}

		$path = isset( $parts['path'] ) ? rtrim( $parts['path'], '/' ) : '';
		if ( preg_match( '#/api/v1$#i', $path ) ) {
			$path = rtrim( substr( $path, 0, -strlen( '/api/v1' ) ), '/' );
		}

		return 'https://' . strtolower( $parts['host'] ) . ( isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '' ) . $path;
	}

	/**
	 * Keeps only printable, non-space ASCII (safe as an HTTP header value).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function clean_token( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return substr( (string) preg_replace( '/[^\x21-\x7E]/', '', (string) $value ), 0, 512 );
	}

	/**
	 * Adds the settings page.
	 *
	 * @return void
	 */
	public static function admin_menu() {
		$hook = add_options_page(
			__( 'Cyprus Pharmacies', 'cyprus-pharmacies' ),
			__( 'Cyprus Pharmacies', 'cyprus-pharmacies' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);

		if ( $hook ) {
			add_action( 'load-' . $hook, array( __CLASS__, 'ensure_options' ) );
		}
	}

	/**
	 * Adds the Settings link to the plugin action links.
	 *
	 * @param array $links The existing plugin action links.
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( self::page_url() ) . '">' . esc_html__( 'Settings', 'cyprus-pharmacies' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * URL of the settings page.
	 *
	 * @param array $args Extra query args.
	 * @return string
	 */
	public static function page_url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'options-general.php' ) );
	}

	/**
	 * Registers the settings, section and fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		// No 'default' argument: when the stored value equals a registered
		// default, update_option() re-adds the option and resets autoload.
		register_setting(
			self::GROUP,
			self::OPT_SOURCE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_source' ),
			)
		);
		register_setting(
			self::GROUP,
			self::OPT_API_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_api_url' ),
			)
		);
		register_setting(
			self::GROUP,
			self::OPT_CLIENT_ID,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'clean_token' ),
			)
		);
		register_setting(
			self::GROUP,
			self::OPT_CLIENT_SECRET,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_client_secret' ),
			)
		);

		add_settings_section(
			'cypharm_source_section',
			__( 'Data source', 'cyprus-pharmacies' ),
			array( __CLASS__, 'render_section' ),
			self::PAGE
		);

		add_settings_field( self::OPT_SOURCE, __( 'Source', 'cyprus-pharmacies' ), array( __CLASS__, 'render_source_field' ), self::PAGE, 'cypharm_source_section' );
		add_settings_field( self::OPT_API_URL, __( 'API URL', 'cyprus-pharmacies' ), array( __CLASS__, 'render_api_url_field' ), self::PAGE, 'cypharm_source_section', array( 'label_for' => self::OPT_API_URL ) );
		add_settings_field( self::OPT_CLIENT_ID, __( 'Client ID', 'cyprus-pharmacies' ), array( __CLASS__, 'render_client_id_field' ), self::PAGE, 'cypharm_source_section', array( 'label_for' => self::OPT_CLIENT_ID ) );
		add_settings_field( self::OPT_CLIENT_SECRET, __( 'Client Secret', 'cyprus-pharmacies' ), array( __CLASS__, 'render_client_secret_field' ), self::PAGE, 'cypharm_source_section', array( 'label_for' => self::OPT_CLIENT_SECRET ) );
	}

	/**
	 * Whether the current request is a submission of this plugin's settings form.
	 *
	 * The nonce is verified by options.php (check_admin_referer) before the
	 * sanitize callbacks run.
	 *
	 * @return bool
	 */
	private static function is_form_submission() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by options.php.
		return isset( $_POST['option_page'] ) && self::GROUP === sanitize_key( wp_unslash( $_POST['option_page'] ) );
	}

	/**
	 * Sanitizes the data source.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public static function sanitize_source( $value ) {
		return self::SOURCE_API === $value ? self::SOURCE_API : self::SOURCE_DATAGOV;
	}

	/**
	 * Sanitizes the API URL (https only, normalized).
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public static function sanitize_api_url( $value ) {
		$raw = is_scalar( $value ) ? trim( (string) $value ) : '';

		if ( '' === $raw ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by options.php.
			if ( self::is_form_submission() && isset( $_POST[ self::OPT_SOURCE ] ) && self::SOURCE_API === $_POST[ self::OPT_SOURCE ] ) {
				add_settings_error( self::OPT_API_URL, 'cypharm_api_url_required', __( 'The API URL is required when the custom API is selected. data.gov.cy will be used until it is set.', 'cyprus-pharmacies' ) );
			}
			return '';
		}

		$url = self::normalize_api_url( $raw );
		if ( '' === $url ) {
			add_settings_error( self::OPT_API_URL, 'cypharm_api_url_invalid', __( 'The API URL must be a valid https:// URL. The previous value was kept.', 'cyprus-pharmacies' ) );
			return (string) get_option( self::OPT_API_URL, '' );
		}

		return $url;
	}

	/**
	 * Sanitizes the client secret. On the settings form an empty value keeps
	 * the saved secret, unless the "remove" checkbox is ticked.
	 *
	 * @param mixed $value Submitted value.
	 * @return string
	 */
	public static function sanitize_client_secret( $value ) {
		$secret = self::clean_token( $value );

		if ( ! self::is_form_submission() ) {
			return $secret;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by options.php.
		if ( ! empty( $_POST['cypharm_api_client_secret_remove'] ) ) {
			return '';
		}

		return '' === $secret ? self::clean_token( get_option( self::OPT_CLIENT_SECRET, '' ) ) : $secret;
	}

	/**
	 * Section description.
	 *
	 * @return void
	 */
	public static function render_section() {
		echo '<p>' . esc_html__( 'By default the pharmacies are loaded from the National Opendata Portal (data.gov.cy). You can use an alternative API instead; if it fails, data.gov.cy is used for that request.', 'cyprus-pharmacies' ) . '</p>';
	}

	/**
	 * Source field.
	 *
	 * @return void
	 */
	public static function render_source_field() {
		$sources = array(
			self::SOURCE_DATAGOV => __( 'data.gov.cy (default)', 'cyprus-pharmacies' ),
			self::SOURCE_API     => __( 'Custom API', 'cyprus-pharmacies' ),
		);

		echo '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Source', 'cyprus-pharmacies' ) . '</legend>';
		foreach ( $sources as $value => $label ) {
			printf(
				'<label><input type="radio" name="%1$s" value="%2$s"%3$s> %4$s</label><br>',
				esc_attr( self::OPT_SOURCE ),
				esc_attr( $value ),
				checked( self::source(), $value, false ),
				esc_html( $label )
			);
		}
		echo '</fieldset>';
	}

	/**
	 * API URL field.
	 *
	 * @return void
	 */
	public static function render_api_url_field() {
		printf(
			'<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text code" placeholder="%3$s" pattern="https://.*">',
			esc_attr( self::OPT_API_URL ),
			esc_attr( (string) get_option( self::OPT_API_URL, '' ) ),
			esc_attr( self::API_URL_PLACEHOLDER )
		);
		echo '<p class="description">' . esc_html__( 'Required for the custom API. https:// only.', 'cyprus-pharmacies' ) . '</p>';
	}

	/**
	 * Client ID field.
	 *
	 * @return void
	 */
	public static function render_client_id_field() {
		printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text code" autocomplete="off" spellcheck="false">',
			esc_attr( self::OPT_CLIENT_ID ),
			esc_attr( self::clean_token( get_option( self::OPT_CLIENT_ID, '' ) ) )
		);
	}

	/**
	 * Client Secret field. The saved secret is never printed.
	 *
	 * @return void
	 */
	public static function render_client_secret_field() {
		$has_secret = '' !== self::clean_token( get_option( self::OPT_CLIENT_SECRET, '' ) );

		printf(
			'<input type="password" id="%1$s" name="%1$s" value="" class="regular-text code" autocomplete="new-password" spellcheck="false"%2$s>',
			esc_attr( self::OPT_CLIENT_SECRET ),
			$has_secret ? ' placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"' : ''
		);

		if ( $has_secret ) {
			echo '<p class="description">' . esc_html__( 'A secret is saved. Leave the field empty to keep it.', 'cyprus-pharmacies' ) . '</p>';
			echo '<p><label><input type="checkbox" name="cypharm_api_client_secret_remove" value="1"> ' . esc_html__( 'Remove the saved secret', 'cyprus-pharmacies' ) . '</label></p>';
		}
	}

	/**
	 * Admin notice when the custom API is selected but not fully configured.
	 *
	 * @return void
	 */
	public static function incomplete_config_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_config_incomplete() ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'Cyprus Pharmacies: the custom API is selected but its URL, Client ID or Client Secret is missing, so data.gov.cy is used.', 'cyprus-pharmacies' ),
			esc_url( self::page_url() ),
			esc_html__( 'Settings', 'cyprus-pharmacies' )
		);
	}

	/**
	 * Handles the "Test connection" button.
	 *
	 * @return void
	 */
	public static function test_connection() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'cyprus-pharmacies' ), 403 );
		}

		check_admin_referer( self::TEST_ACTION );

		$credentials = self::saved_api_credentials();
		$result      = null === $credentials ? 'incomplete' : CyPharm_Data::test_api( $credentials );

		wp_safe_redirect( self::page_url( array( 'cypharm_test' => $result ) ) );
		exit;
	}

	/**
	 * Shows the result of the connection test (a generic code, never the response body).
	 *
	 * @return void
	 */
	private static function render_test_result() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		$code = isset( $_GET['cypharm_test'] ) ? sanitize_key( wp_unslash( $_GET['cypharm_test'] ) ) : '';

		if ( '' === $code ) {
			return;
		}

		if ( 'ok' === $code ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Connection test: OK.', 'cyprus-pharmacies' ) . '</p></div>';
			return;
		}

		if ( ! preg_match( '/^(http_\d{3}|timeout|connection_failed|invalid_response|incomplete)$/', $code ) ) {
			return;
		}

		$hints = array(
			'incomplete' => __( 'Save the API URL, Client ID and Client Secret first.', 'cyprus-pharmacies' ),
			'http_302'   => __( 'Check the Client ID and Client Secret.', 'cyprus-pharmacies' ),
			'http_401'   => __( 'Check the Client ID and Client Secret.', 'cyprus-pharmacies' ),
			'http_403'   => __( 'Check the Client ID and Client Secret.', 'cyprus-pharmacies' ),
			'http_429'   => __( 'Too many requests; try again in a moment.', 'cyprus-pharmacies' ),
			'timeout'    => __( 'The API did not respond in time.', 'cyprus-pharmacies' ),
		);

		printf(
			'<div class="notice notice-error"><p>%1$s %2$s</p></div>',
			/* translators: %s: error code, e.g. http_403 or timeout. */
			esc_html( sprintf( __( 'Connection test failed (%s).', 'cyprus-pharmacies' ), $code ) ),
			esc_html( isset( $hints[ $code ] ) ? $hints[ $code ] : '' )
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['clear_cache'], $_POST['cypharm_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cypharm_nonce'] ) ), 'cypharm_clear_cache' ) ) {
			self::invalidate_cache();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Cache cleared successfully!', 'cyprus-pharmacies' ) . '</p></div>';
		}

		self::render_test_result();

		$cache_duration = CyPharm_Data::get_cache_duration();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cyprus Pharmacies', 'cyprus-pharmacies' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>

			<h2><?php esc_html_e( 'Test connection', 'cyprus-pharmacies' ); ?></h2>
			<p><?php esc_html_e( 'Requests today\'s pharmacies of Nicosia from the custom API, using the saved credentials. Save your changes first.', 'cyprus-pharmacies' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>">
				<?php wp_nonce_field( self::TEST_ACTION ); ?>
				<?php submit_button( __( 'Test connection', 'cyprus-pharmacies' ), 'secondary', 'cypharm_test_submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Cache', 'cyprus-pharmacies' ); ?></h2>
			<p><?php esc_html_e( 'Clear the cached pharmacy data if you need fresh information from the API.', 'cyprus-pharmacies' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'cypharm_clear_cache', 'cypharm_nonce' ); ?>
				<input type="submit" name="clear_cache" class="button button-primary" value="<?php esc_attr_e( 'Clear Cache', 'cyprus-pharmacies' ); ?>">
			</form>

			<h2><?php esc_html_e( 'Developer Information', 'cyprus-pharmacies' ); ?></h2>
			<p><?php esc_html_e( 'You can customize the cache duration using the following filter in your theme\'s functions.php file:', 'cyprus-pharmacies' ); ?></p>

			<pre style="background: #f1f1f1; padding: 15px; border-radius: 3px; overflow-x: auto;">
// Set cache duration to 24 hours (86400 seconds)
add_filter( 'cypharm_cache_duration', function() {
	return 86400; // 24 hours in seconds
});

// Or set cache duration to 1 hour (3600 seconds)
add_filter( 'cypharm_cache_duration', function() {
	return 3600; // 1 hour in seconds
});

// Or disable caching entirely (not recommended for production)
add_filter( 'cypharm_cache_duration', function() {
	return 0; // No caching
});
</pre>

			<p><strong><?php esc_html_e( 'Current cache duration:', 'cyprus-pharmacies' ); ?></strong>
				<?php echo esc_html( $cache_duration > 0 ? human_time_diff( 0, $cache_duration ) : __( 'Caching is disabled', 'cyprus-pharmacies' ) ); ?>
			</p>
		</div>
		<?php
	}
}
