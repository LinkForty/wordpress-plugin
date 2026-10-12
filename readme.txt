=== LinkForty ===
Contributors: linkforty
Tags: analytics, conversion tracking, woocommerce, attribution, deep links
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Web analytics and conversion tracking for LinkForty links, including WooCommerce purchases, with no code and no theme editing.

== Description ==

LinkForty is a deep linking and attribution platform. This plugin adds the LinkForty web pixel to every page of your WordPress site and, when WooCommerce is active, reports purchases as conversions attributed to the LinkForty link that brought the customer.

* **No code.** Install, paste your workspace site key, done. Works with any theme and page builder because the pixel is added to the page head, not to individual buttons.
* **Real purchases, not clicks.** The `purchase` event fires on the order-received page with the order total as revenue. Each order is reported exactly once.
* **Attribution that survives the journey.** The pixel remembers the link a visitor arrived from for the whole session, so a purchase several pages later still credits that link. The plugin also carries the attribution through WooCommerce's add-to-cart redirect.
* **Privacy-first.** Honours Do Not Track and Global Privacy Control, never stores raw IP addresses, and only assigns a persistent visitor ID when you turn it on and the visitor has consented.

Events reported with WooCommerce enabled:

* `purchase` with `order_id`, `revenue`, `currency`, `item_count`, `payment_method`, `coupon`
* `begin_checkout` with `value`, `currency`, `item_count`
* `add_to_cart` with `product_id`, `quantity`, `sku`, and on form submits `product_name`, `price`, `variation_id`

Mark `purchase` as a goal under Web analytics in your LinkForty dashboard so it counts as a conversion.

== Installation ==

1. Install and activate the plugin.
2. In the LinkForty dashboard, open Workspace Settings → Web Pixel and copy your site key (it starts with `ws_`).
3. In WordPress, open Settings → LinkForty, paste the site key and save.
4. Optional: if you have a consent plugin that supports the WP Consent API, turn on "Enable visitor ID" for new-vs-returning analytics.

== Frequently Asked Questions ==

= Does this work with Elementor, Beaver Builder or WPBakery? =

Yes. The pixel is added through the theme head, which every page builder keeps. You do not need to tag buttons.

= Where do conversions show up? =

In the LinkForty dashboard under Analytics → Web, and on each link's own analytics once the visit is attributed to it.

= Why is a purchase missing? =

The order-received page must render in the customer's browser with the pixel active. Check that tracking is enabled, that the site key is correct, that an origin allowlist in LinkForty includes this hostname, and that the browser does not have Do Not Track enabled. Orders still `pending` payment are reported on a later visit once they move to processing or completed.

= Does this send data to LinkForty from my server? =

No. Everything is sent from the visitor's browser through the pixel, which is what ties a purchase to the visit.

== Changelog ==

= 0.1.0 =
* Initial release.
