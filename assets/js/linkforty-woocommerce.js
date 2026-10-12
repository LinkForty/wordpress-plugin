/**
 * Reports add-to-cart actions that happen without a page load: WooCommerce's
 * AJAX "Add to cart" buttons and the block-based cart. Form submits that reload
 * the page are reported server-side instead.
 */
( function () {
	'use strict';

	function send( name, properties ) {
		var lf = ( window.LinkForty = window.LinkForty || { q: [] } );
		if ( typeof lf.track === 'function' ) {
			lf.track( name, properties );
		} else {
			( lf.q = lf.q || [] ).push( [ name, properties ] );
		}
	}

	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'added_to_cart', function ( event, fragments, cartHash, $button ) {
			var properties = {};
			if ( $button && $button.length ) {
				var productId = $button.data( 'product_id' );
				var quantity = $button.data( 'quantity' );
				var sku = $button.data( 'product_sku' );
				if ( productId ) {
					properties.product_id = Number( productId );
				}
				properties.quantity = quantity ? Number( quantity ) : 1;
				if ( sku ) {
					properties.sku = String( sku );
				}
			}
			send( 'add_to_cart', properties );
		} );
	}

	document.body.addEventListener( 'wc-blocks_added_to_cart', function ( event ) {
		var detail = ( event && event.detail ) || {};
		var properties = {};
		if ( detail.productId ) {
			properties.product_id = Number( detail.productId );
		}
		if ( detail.quantity ) {
			properties.quantity = Number( detail.quantity );
		}
		send( 'add_to_cart', properties );
	} );
} )();
