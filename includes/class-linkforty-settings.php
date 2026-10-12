<?php
/**
 * Settings screen: Settings → LinkForty.
 *
 * @package LinkForty
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the settings page and validates saved values.
 */
class LinkForty_Settings {

	const PAGE_SLUG = 'linkforty';

	const DASHBOARD_URL = 'https://app.linkforty.com/settings';

	const DOCS_URL = 'https://docs.linkforty.com/sdks/wordpress';

	/**
	 * Registers admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_notices', array( __CLASS__, 'missing_site_key_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LINKFORTY_PLUGIN_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Adds Settings → LinkForty.
	 */
	public static function add_menu() {
		add_options_page(
			__( 'LinkForty', 'linkforty' ),
			__( 'LinkForty', 'linkforty' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Adds a Settings link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'linkforty' ) . '</a>' );
		return $links;
	}

	/**
	 * Registers the option, sections and fields.
	 */
	public static function register_settings() {
		register_setting(
			self::PAGE_SLUG,
			LinkForty_Options::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => LinkForty_Options::defaults(),
			)
		);

		add_settings_section( 'linkforty_tracking', __( 'Web tracking', 'linkforty' ), array( __CLASS__, 'tracking_section_intro' ), self::PAGE_SLUG );
		self::add_field( 'site_key', __( 'Site key', 'linkforty' ), 'linkforty_tracking', 'text' );
		self::add_field( 'tracking_enabled', __( 'Enable web tracking', 'linkforty' ), 'linkforty_tracking', 'checkbox' );
		self::add_field( 'visitor_id', __( 'Enable visitor ID', 'linkforty' ), 'linkforty_tracking', 'checkbox' );

		add_settings_section( 'linkforty_woocommerce', __( 'WooCommerce', 'linkforty' ), array( __CLASS__, 'woocommerce_section_intro' ), self::PAGE_SLUG );
		self::add_field( 'woocommerce_events', __( 'Track WooCommerce events', 'linkforty' ), 'linkforty_woocommerce', 'checkbox' );

		add_settings_section( 'linkforty_advanced', __( 'Advanced', 'linkforty' ), '__return_false', self::PAGE_SLUG );
		self::add_field( 'exclude_administrators', __( 'Exclude administrators', 'linkforty' ), 'linkforty_advanced', 'checkbox' );
		self::add_field( 'link_domains', __( 'Linked domains', 'linkforty' ), 'linkforty_advanced', 'text' );
		self::add_field( 'endpoint', __( 'Ingestion endpoint', 'linkforty' ), 'linkforty_advanced', 'url' );
	}

	/**
	 * Registers one field.
	 *
	 * @param string $key     Setting name.
	 * @param string $label   Field label.
	 * @param string $section Section id.
	 * @param string $type    text|checkbox|url.
	 */
	private static function add_field( $key, $label, $section, $type ) {
		add_settings_field(
			'linkforty_' . $key,
			$label,
			array( __CLASS__, 'render_field' ),
			self::PAGE_SLUG,
			$section,
			array(
				'key'       => $key,
				'type'      => $type,
				'label_for' => 'checkbox' === $type ? null : 'linkforty_' . $key,
			)
		);
	}

	/**
	 * Intro copy for the tracking section.
	 */
	public static function tracking_section_intro() {
		printf(
			'<p>%s</p>',
			wp_kses(
				sprintf(
					/* translators: %s: URL of the LinkForty dashboard */
					__( 'Paste your workspace site key from <a href="%s" target="_blank" rel="noopener">Workspace Settings → Web Pixel</a> in the LinkForty dashboard. The pixel is then added to every page: page views and engagement are tracked with no further setup.', 'linkforty' ),
					esc_url( self::DASHBOARD_URL )
				),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			)
		);
	}

	/**
	 * Intro copy for the WooCommerce section.
	 */
	public static function woocommerce_section_intro() {
		if ( ! LinkForty_WooCommerce::is_active() ) {
			echo '<p>' . esc_html__( 'WooCommerce is not active on this site. Install and activate it to report purchases as conversions.', 'linkforty' ) . '</p>';
			return;
		}
		echo '<p>' . esc_html__( 'Reports a purchase conversion on the order-received page (once per order, with the order total as revenue), plus begin_checkout and add_to_cart. In the LinkForty dashboard, mark "purchase" as a goal under Web analytics so it counts as a conversion.', 'linkforty' ) . '</p>';
	}

	/**
	 * Renders one field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 */
	public static function render_field( $args ) {
		$key   = $args['key'];
		$value = LinkForty_Options::get( $key );
		$name  = LinkForty_Options::OPTION_NAME . '[' . $key . ']';
		$id    = 'linkforty_' . $key;

		switch ( $args['type'] ) {
			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (bool) $value, true, false ),
					esc_html( self::checkbox_label( $key ) )
				);
				break;
			case 'url':
				printf(
					'<input type="url" class="regular-text code" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( 'https://api.linkforty.com/api/pixel/v1/event' )
				);
				break;
			default:
				printf(
					'<input type="text" class="regular-text code" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" autocomplete="off" spellcheck="false" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( 'site_key' === $key ? 'ws_…' : 'store.example, shop.example' )
				);
		}

		$description = self::description( $key );
		if ( '' !== $description ) {
			echo '<p class="description">' . wp_kses_post( $description ) . '</p>';
		}
	}

	/**
	 * Inline label for a checkbox.
	 *
	 * @param string $key Setting name.
	 * @return string
	 */
	private static function checkbox_label( $key ) {
		switch ( $key ) {
			case 'tracking_enabled':
				return __( 'Add the LinkForty pixel to every page', 'linkforty' );
			case 'visitor_id':
				return __( 'Assign a persistent visitor ID for new-vs-returning analytics', 'linkforty' );
			case 'woocommerce_events':
				return __( 'Report purchases, checkout starts and add-to-cart actions', 'linkforty' );
			case 'exclude_administrators':
				return __( 'Do not track visits by logged-in administrators', 'linkforty' );
		}
		return '';
	}

	/**
	 * Help text under a field.
	 *
	 * @param string $key Setting name.
	 * @return string
	 */
	private static function description( $key ) {
		switch ( $key ) {
			case 'site_key':
				return __( 'Starts with <code>ws_</code>. It is public and safe to ship in your HTML; rotate it from the LinkForty dashboard if it is ever abused.', 'linkforty' );
			case 'visitor_id':
				return __( 'Consent-sensitive: the visitor ID is stored in the browser. When a consent plugin that supports the WP Consent API is installed, the ID is only set once the visitor has granted statistics consent.', 'linkforty' );
			case 'exclude_administrators':
				return __( 'Keeps test orders and admin browsing out of your numbers. Leave off while verifying the installation.', 'linkforty' );
			case 'link_domains':
				return __( 'Other hostnames you send visitors to, comma-separated. Links and forms pointing at them carry the visit\'s attribution across, so a purchase there still credits the original link. Install the pixel on those sites with the same site key.', 'linkforty' );
			case 'endpoint':
				return __( 'Leave blank unless LinkForty is reached through a custom domain or a self-hosted deployment.', 'linkforty' );
		}
		return '';
	}

	/**
	 * Validates submitted settings. Invalid values keep their previous value
	 * and show an error.
	 *
	 * @param mixed $input Raw submitted array.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		$current = LinkForty_Options::all();
		$input   = is_array( $input ) ? $input : array();
		$clean   = $current;

		foreach ( array( 'tracking_enabled', 'visitor_id', 'woocommerce_events', 'exclude_administrators' ) as $flag ) {
			$clean[ $flag ] = ! empty( $input[ $flag ] );
		}

		$site_key = isset( $input['site_key'] ) ? trim( (string) $input['site_key'] ) : '';
		if ( '' === $site_key || LinkForty_Options::is_valid_site_key( $site_key ) ) {
			$clean['site_key'] = $site_key;
		} else {
			add_settings_error( LinkForty_Options::OPTION_NAME, 'linkforty_site_key', __( 'That does not look like a LinkForty site key. It starts with ws_ followed by 32 hexadecimal characters.', 'linkforty' ) );
		}

		$endpoint = isset( $input['endpoint'] ) ? trim( (string) $input['endpoint'] ) : '';
		if ( '' === $endpoint ) {
			$clean['endpoint'] = '';
		} elseif ( 0 === strpos( $endpoint, 'https://' ) && filter_var( $endpoint, FILTER_VALIDATE_URL ) ) {
			$clean['endpoint'] = esc_url_raw( $endpoint );
		} else {
			add_settings_error( LinkForty_Options::OPTION_NAME, 'linkforty_endpoint', __( 'The ingestion endpoint must be an https:// URL.', 'linkforty' ) );
		}

		$clean['link_domains'] = LinkForty_Options::sanitize_host_list( isset( $input['link_domains'] ) ? $input['link_domains'] : '' );

		return $clean;
	}

	/**
	 * Nudges toward the settings page while tracking is on without a key.
	 */
	public static function missing_site_key_notice() {
		if ( ! current_user_can( 'manage_options' ) || '' !== LinkForty_Options::site_key() || ! LinkForty_Options::get( 'tracking_enabled' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'settings_page_' . self::PAGE_SLUG ), true ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: %s: settings page URL */
					__( 'LinkForty is not tracking yet: add your workspace site key under <a href="%s">Settings → LinkForty</a>.', 'linkforty' ),
					esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) )
				),
				array( 'a' => array( 'href' => array() ) )
			)
		);
	}

	/**
	 * Renders the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'LinkForty', 'linkforty' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PAGE_SLUG );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
			<p>
				<a href="<?php echo esc_url( self::DOCS_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Setup guide', 'linkforty' ); ?></a>
			</p>
		</div>
		<?php
	}
}
