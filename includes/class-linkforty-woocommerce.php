<?php
/**
 * WooCommerce conversion events.
 *
 * @package LinkForty
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reports purchases, checkout starts and add-to-cart actions as conversions.
 *
 * Purchases fire on the order-received page, never on a "Buy" button: a click
 * is intent, an order is revenue. Each order is reported once, guarded by
 * order meta, so refreshing the page does not double count.
 */
class LinkForty_WooCommerce {

	const PURCHASE_META = '_linkforty_purchase_tracked';

	const SESSION_KEY = 'linkforty_pending_events';

	const SCRIPT_HANDLE = 'linkforty-woocommerce';

	/**
	 * Registers hooks. Safe to call when WooCommerce is absent.
	 */
	public static function init() {
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_compatibility' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'register' ), 20 );
	}

	/**
	 * Whether WooCommerce is active on this site.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Declares High-Performance Order Storage compatibility: the plugin only
	 * touches orders through the CRUD API.
	 */
	public static function declare_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', LINKFORTY_PLUGIN_FILE, true );
		}
	}

	/**
	 * Wires the event hooks once every plugin has loaded. Hooking
	 * `plugins_loaded` rather than `woocommerce_loaded` keeps this working
	 * whatever order the two plugins are loaded in.
	 */
	public static function register() {
		if ( ! self::is_active() ) {
			return;
		}

		// Keep attribution through the add-to-cart redirect regardless of the
		// events toggle: it costs nothing and fixes a known attribution gap.
		add_filter( 'woocommerce_add_to_cart_redirect', array( __CLASS__, 'preserve_attribution_on_redirect' ), 10, 1 );

		if ( ! LinkForty_Options::get( 'woocommerce_events' ) ) {
			return;
		}

		add_action( 'woocommerce_thankyou', array( __CLASS__, 'track_purchase' ), 10, 1 );
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'queue_add_to_cart' ), 10, 4 );
		add_action( 'template_redirect', array( __CLASS__, 'flush_pending_events' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'track_begin_checkout' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_script' ) );
	}

	/**
	 * Reports a completed order once.
	 *
	 * @param int $order_id Order id from the order-received page.
	 */
	public static function track_purchase( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		/**
		 * Filters the order statuses that count as a purchase. The page can
		 * render before an off-site gateway confirms payment; such orders are
		 * `pending` and are reported on a later visit once they move on.
		 *
		 * @param string[] $statuses Status slugs without the `wc-` prefix.
		 * @param WC_Order $order    The order.
		 */
		$statuses = apply_filters( 'linkforty_purchase_order_statuses', array( 'processing', 'completed', 'on-hold' ), $order );
		if ( ! $order->has_status( $statuses ) ) {
			return;
		}

		if ( $order->get_meta( self::PURCHASE_META ) ) {
			return;
		}

		$coupons    = $order->get_coupon_codes();
		$properties = array(
			'order_id'       => (string) $order->get_id(),
			'revenue'        => (float) $order->get_total(),
			'currency'       => $order->get_currency(),
			'item_count'     => (int) $order->get_item_count(),
			'payment_method' => (string) $order->get_payment_method(),
		);
		if ( ! empty( $coupons ) ) {
			$properties['coupon'] = (string) reset( $coupons );
		}

		/**
		 * Filters the properties sent with the `purchase` event.
		 *
		 * @param array<string, mixed> $properties Event properties.
		 * @param WC_Order             $order      The order.
		 */
		$properties = apply_filters( 'linkforty_purchase_properties', $properties, $order );

		$order->update_meta_data( self::PURCHASE_META, time() );
		$order->save();

		LinkForty_Events::track( 'purchase', $properties );
	}

	/**
	 * Reports the start of checkout on each checkout page view.
	 */
	public static function track_begin_checkout() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}
		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			return;
		}

		LinkForty_Events::track(
			'begin_checkout',
			array(
				'value'      => (float) $cart->get_total( 'edit' ),
				'currency'   => get_woocommerce_currency(),
				'item_count' => (int) $cart->get_cart_contents_count(),
			)
		);
	}

	/**
	 * Records a non-AJAX add-to-cart (a product-page form submit) in the
	 * WooCommerce session so it can be reported on the page that renders
	 * next. AJAX and Store API adds are reported by the browser script.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id    Product id.
	 * @param int    $quantity      Quantity added.
	 * @param int    $variation_id  Variation id, 0 for simple products.
	 */
	public static function queue_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id ) {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! WC()->session ) {
			return;
		}

		$product    = wc_get_product( $variation_id ? $variation_id : $product_id );
		$properties = array(
			'product_id' => (int) $product_id,
			'quantity'   => (int) $quantity,
		);
		if ( $variation_id ) {
			$properties['variation_id'] = (int) $variation_id;
		}
		if ( $product instanceof WC_Product ) {
			$properties['product_name'] = $product->get_name();
			$properties['price']        = (float) $product->get_price();
			$properties['currency']     = get_woocommerce_currency();
			if ( $product->get_sku() ) {
				$properties['sku'] = $product->get_sku();
			}
		}

		$pending   = (array) WC()->session->get( self::SESSION_KEY, array() );
		$pending[] = array( 'add_to_cart', $properties );
		WC()->session->set( self::SESSION_KEY, array_slice( $pending, -20 ) );
	}

	/**
	 * Moves session-queued events into the footer collector.
	 */
	public static function flush_pending_events() {
		if ( ! WC()->session ) {
			return;
		}
		$pending = WC()->session->get( self::SESSION_KEY );
		if ( empty( $pending ) || ! is_array( $pending ) ) {
			return;
		}
		WC()->session->set( self::SESSION_KEY, array() );
		foreach ( $pending as $event ) {
			if ( is_array( $event ) && isset( $event[0] ) ) {
				LinkForty_Events::track( (string) $event[0], isset( $event[1] ) && is_array( $event[1] ) ? $event[1] : array() );
			}
		}
	}

	/**
	 * Carries the LinkForty click id and UTM parameters through WooCommerce's
	 * "redirect to the cart page after successful addition". Without this, a
	 * link landing on `?add-to-cart=123` redirects before the pixel can read
	 * `lf_click`, and the session is never attributed.
	 *
	 * @param string $url Redirect target.
	 * @return string
	 */
	public static function preserve_attribution_on_redirect( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		$carry = array();
		foreach ( array( 'lf_click', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' ) as $key ) {
			if ( isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only passthrough of public tracking parameters.
				$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( '' !== $value ) {
					$carry[ $key ] = $value;
				}
			}
		}
		return empty( $carry ) ? $url : add_query_arg( $carry, $url );
	}

	/**
	 * Browser-side listener for AJAX and block add-to-cart events.
	 */
	public static function enqueue_script() {
		if ( ! LinkForty_Pixel::should_track() ) {
			return;
		}
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			LINKFORTY_PLUGIN_URL . 'assets/js/linkforty-woocommerce.js',
			array( 'jquery' ),
			LINKFORTY_VERSION,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
}
