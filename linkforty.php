<?php
/**
 * Plugin Name:          LinkForty
 * Plugin URI:           https://docs.linkforty.com/sdks/wordpress
 * Description:          Web analytics and conversion tracking for LinkForty links. Adds the LinkForty web pixel to every page and reports WooCommerce purchases as conversions attributed to the link that drove the visit.
 * Version:              0.2.0
 * Requires at least:    6.3
 * Requires PHP:         7.4
 * Author:               LinkForty
 * Author URI:           https://linkforty.com
 * License:              MIT
 * License URI:          https://opensource.org/licenses/MIT
 * Text Domain:          linkforty
 * Domain Path:          /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.9
 *
 * @package LinkForty
 */

defined( 'ABSPATH' ) || exit;

define( 'LINKFORTY_VERSION', '0.2.0' );
define( 'LINKFORTY_PLUGIN_FILE', __FILE__ );
define( 'LINKFORTY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LINKFORTY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once LINKFORTY_PLUGIN_DIR . 'includes/class-linkforty-options.php';
require_once LINKFORTY_PLUGIN_DIR . 'includes/class-linkforty-events.php';
require_once LINKFORTY_PLUGIN_DIR . 'includes/class-linkforty-pixel.php';
require_once LINKFORTY_PLUGIN_DIR . 'includes/class-linkforty-woocommerce.php';
require_once LINKFORTY_PLUGIN_DIR . 'includes/class-linkforty-settings.php';
require_once LINKFORTY_PLUGIN_DIR . 'includes/class-linkforty.php';

LinkForty::instance();
