<?php
/**
 * Plugin settings storage and validation.
 *
 * @package LinkForty
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads the single `linkforty_settings` option with defaults applied.
 */
class LinkForty_Options {

	const OPTION_NAME = 'linkforty_settings';

	const SITE_KEY_PATTERN = '/^ws_[0-9a-f]{32}$/';

	const DEFAULT_PIXEL_URL = 'https://cdn.linkforty.com/pixel.js';

	/**
	 * Default values for every setting.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'site_key'               => '',
			'tracking_enabled'       => true,
			'woocommerce_events'     => true,
			'visitor_id'             => false,
			'exclude_administrators' => false,
			'endpoint'               => '',
			'link_domains'           => '',
		);
	}

	/**
	 * All settings merged over defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		$stored = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting name.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : null;
	}

	/**
	 * Whether a string is a well-formed LinkForty site key (`ws_` + 32 hex).
	 *
	 * @param mixed $key Candidate value.
	 * @return bool
	 */
	public static function is_valid_site_key( $key ) {
		return is_string( $key ) && 1 === preg_match( self::SITE_KEY_PATTERN, $key );
	}

	/**
	 * The configured site key, or '' when missing or malformed.
	 *
	 * @return string
	 */
	public static function site_key() {
		$key = self::get( 'site_key' );
		return self::is_valid_site_key( $key ) ? $key : '';
	}

	/**
	 * URL of the pixel script. Filterable for self-hosted or pinned builds.
	 *
	 * @return string
	 */
	public static function pixel_url() {
		return (string) apply_filters( 'linkforty_pixel_url', self::DEFAULT_PIXEL_URL );
	}

	/**
	 * Normalizes a comma-separated host list ("Store.com, shop.example/") into
	 * lowercase bare hostnames joined by ", ". Returns '' when nothing survives.
	 *
	 * @param string $raw User input.
	 * @return string
	 */
	public static function sanitize_host_list( $raw ) {
		$hosts = array();
		foreach ( explode( ',', (string) $raw ) as $part ) {
			$part = strtolower( trim( $part ) );
			if ( '' === $part ) {
				continue;
			}
			// Accept a pasted URL and keep only its host.
			if ( false !== strpos( $part, '/' ) ) {
				$parsed = wp_parse_url( ( false === strpos( $part, '://' ) ? 'https://' : '' ) . $part );
				$part   = isset( $parsed['host'] ) ? $parsed['host'] : '';
			}
			if ( '' !== $part && 1 === preg_match( '/^[a-z0-9.-]+$/', $part ) && ! in_array( $part, $hosts, true ) ) {
				$hosts[] = $part;
			}
		}
		return implode( ', ', $hosts );
	}
}
