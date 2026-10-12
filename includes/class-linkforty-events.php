<?php
/**
 * Collects conversion events raised during a request and hands them to the
 * browser pixel in the footer.
 *
 * @package LinkForty
 */

defined( 'ABSPATH' ) || exit;

/**
 * Server-side code calls `LinkForty_Events::track()`; the footer prints one
 * inline script that forwards every collected event to `window.LinkForty`.
 *
 * Events go through the pixel rather than straight to the ingestion endpoint
 * so they carry the browser session: the click id and UTM parameters the pixel
 * captured when the visitor landed. That is what attributes a purchase to the
 * link that drove it. If the pixel has not loaded yet, events are pushed onto
 * its pre-load queue and replayed when it does.
 */
class LinkForty_Events {

	/**
	 * Events collected for the current request: [ name, properties ] pairs.
	 *
	 * @var array<int, array{0: string, 1: array<string, mixed>}>
	 */
	private static $events = array();

	/**
	 * Registers the footer printer.
	 */
	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'print_events' ), 100 );
	}

	/**
	 * Queues a conversion for the current page.
	 *
	 * @param string               $name       Event name, e.g. `purchase`.
	 * @param array<string, mixed> $properties Flat properties. Use `revenue`
	 *                                         for a monetary value so it rolls
	 *                                         up in revenue reporting.
	 */
	public static function track( $name, array $properties = array() ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return;
		}
		/**
		 * Filters an event before it is handed to the pixel. Return an empty
		 * array to drop it.
		 *
		 * @param array<string, mixed> $properties Event properties.
		 * @param string               $name       Event name.
		 */
		$properties = apply_filters( 'linkforty_event_properties', $properties, $name );
		if ( ! is_array( $properties ) ) {
			return;
		}
		self::$events[] = array( substr( $name, 0, 255 ), $properties );
	}

	/**
	 * Whether anything is waiting to be printed.
	 *
	 * @return bool
	 */
	public static function has_events() {
		return ! empty( self::$events );
	}

	/**
	 * Prints the collected events as one inline script. Does nothing when the
	 * pixel is not on the page, since there would be nothing to receive them.
	 */
	public static function print_events() {
		if ( empty( self::$events ) || ! LinkForty_Pixel::should_track() ) {
			return;
		}

		$payload      = wp_json_encode( array_values( self::$events ) );
		self::$events = array();
		if ( false === $payload ) {
			return;
		}

		$script = '(function(){var l=window.LinkForty=window.LinkForty||{q:[]};'
			. 'var e=' . $payload . ';'
			. 'for(var i=0;i<e.length;i++){'
			. 'if(typeof l.track==="function"){l.track(e[i][0],e[i][1]);}'
			. 'else{(l.q=l.q||[]).push(e[i]);}'
			. '}})();';

		wp_print_inline_script_tag( $script, array( 'id' => 'linkforty-events' ) );
	}
}
