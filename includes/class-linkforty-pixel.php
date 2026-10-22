<?php
/**
 * Puts the LinkForty web pixel on every front-end page.
 *
 * @package LinkForty
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the pixel script with its `data-*` configuration attributes.
 */
class LinkForty_Pixel {

	const HANDLE = 'linkforty-pixel';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 1 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'add_attributes' ), 10, 2 );
	}

	/**
	 * Whether the pixel should be printed for the current request.
	 *
	 * @return bool
	 */
	public static function should_track() {
		static $decision = null;
		if ( null !== $decision ) {
			return $decision;
		}

		$enabled = (bool) LinkForty_Options::get( 'tracking_enabled' )
			&& '' !== LinkForty_Options::site_key()
			&& ! is_admin()
			&& ! is_feed()
			&& ! is_customize_preview();

		if ( $enabled && LinkForty_Options::get( 'exclude_administrators' ) && current_user_can( 'manage_options' ) ) {
			$enabled = false;
		}

		/**
		 * Filters whether the pixel (and therefore any conversion) is emitted
		 * for this request.
		 *
		 * @param bool $enabled Current decision.
		 */
		$decision = (bool) apply_filters( 'linkforty_should_track', $enabled );
		return $decision;
	}

	/**
	 * Whether the persistent visitor id may be enabled for this visitor.
	 *
	 * Honours the WP Consent API when a consent plugin provides it: the visitor
	 * id is the one durable identifier the pixel can create, so it needs the
	 * "statistics" consent category.
	 *
	 * @return bool
	 */
	public static function visitor_id_allowed() {
		$allowed = (bool) LinkForty_Options::get( 'visitor_id' );
		if ( $allowed && function_exists( 'wp_has_consent' ) ) {
			$allowed = (bool) wp_has_consent( 'statistics' );
		}
		/**
		 * Filters whether `data-visitors="true"` is added to the pixel tag.
		 *
		 * @param bool $allowed Current decision.
		 */
		return (bool) apply_filters( 'linkforty_visitor_id_allowed', $allowed );
	}

	/**
	 * Enqueues the pixel in the head, async, with the pre-load queue bootstrap
	 * printed before it so early `track()` calls are never lost.
	 */
	public static function enqueue() {
		if ( ! self::should_track() ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			LinkForty_Options::pixel_url(),
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- the CDN controls versioning.
			array(
				'strategy'  => 'async',
				'in_footer' => false,
			)
		);
		wp_add_inline_script( self::HANDLE, 'window.LinkForty=window.LinkForty||{q:[]};', 'before' );
	}

	/**
	 * The `data-*` attributes for the pixel tag.
	 *
	 * @return array<string, string>
	 */
	public static function attributes() {
		$attributes = array(
			'data-workspace' => LinkForty_Options::site_key(),
		);

		if ( self::visitor_id_allowed() ) {
			$attributes['data-visitors'] = 'true';
		}

		$endpoint = (string) LinkForty_Options::get( 'endpoint' );
		if ( '' !== $endpoint ) {
			$attributes['data-endpoint'] = $endpoint;
		}

		$link_domains = (string) LinkForty_Options::get( 'link_domains' );
		if ( '' !== $link_domains ) {
			$attributes['data-link-domains'] = $link_domains;
		}

		/**
		 * Filters the `data-*` attributes placed on the pixel script tag.
		 *
		 * @param array<string, string> $attributes Attribute name => value.
		 */
		return (array) apply_filters( 'linkforty_pixel_attributes', $attributes );
	}

	/**
	 * Adds the configuration attributes to the enqueued script tag.
	 *
	 * `$tag` also holds the inline bootstrap printed before the pixel, so the
	 * attributes must go on the pixel's own `<script src>` tag: the pixel reads
	 * them from `document.currentScript` and sends nothing without a site key.
	 *
	 * @param string $tag    The `<script>` HTML, inline scripts included.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public static function add_attributes( $tag, $handle ) {
		if ( self::HANDLE !== $handle ) {
			return $tag;
		}

		$extra = '';
		foreach ( self::attributes() as $name => $value ) {
			if ( 1 !== preg_match( '/^[a-z][a-z0-9-]*$/', $name ) ) {
				continue;
			}
			$extra .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
		}

		// WordPress gives the pixel tag the id `{handle}-js`, quoted with ' up to
		// 6.3 and " from 6.4. Fall back to the first tag with a src if an
		// optimization plugin has rewritten the id.
		$patterns = array(
			'/<script\b(?=[^>]*\sid=([\'"])' . preg_quote( self::HANDLE . '-js', '/' ) . '\1)/',
			'/<script\b(?=[^>]*\ssrc=)/',
		);
		foreach ( $patterns as $pattern ) {
			$count  = 0;
			$tagged = preg_replace( $pattern, '<script' . $extra, $tag, 1, $count );
			if ( $count > 0 ) {
				return $tagged;
			}
		}

		return $tag;
	}
}
