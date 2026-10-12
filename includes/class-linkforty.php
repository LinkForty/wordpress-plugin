<?php
/**
 * Plugin bootstrap.
 *
 * @package LinkForty
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's parts together.
 */
final class LinkForty {

	/**
	 * Singleton instance.
	 *
	 * @var LinkForty|null
	 */
	private static $instance = null;

	/**
	 * Returns the single instance, creating it on first call.
	 *
	 * @return LinkForty
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers everything.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		LinkForty_Pixel::init();
		LinkForty_Events::init();
		LinkForty_WooCommerce::init();

		if ( is_admin() ) {
			LinkForty_Settings::init();
		}
	}

	/**
	 * Loads translations shipped in /languages.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'linkforty', false, dirname( plugin_basename( LINKFORTY_PLUGIN_FILE ) ) . '/languages' );
	}
}
