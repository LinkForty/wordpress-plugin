# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Fixed
- WooCommerce purchases record their revenue: the order total is sent as
  `value`, the property LinkForty sums as revenue. `revenue` is still sent with
  the same total.

### Changed
- **Breaking:** `begin_checkout` sends the cart total as `cart_total` instead of
  `value`, so a started checkout is not counted as revenue. Anything that reads
  `begin_checkout`'s `value` should read `cart_total`.

## [0.1.0] - 2026-10-11

Initial scaffold.

### Added
- Settings → LinkForty: site key, web tracking toggle, visitor ID toggle,
  WooCommerce events toggle, administrator exclusion, linked domains, and an
  ingestion endpoint override.
- The LinkForty web pixel is enqueued on every front-end page, async, with the
  pre-load queue bootstrapped before it.
- WooCommerce `purchase` conversion on the order-received page, reported once
  per order (guarded by order meta) with order id, revenue, currency, item
  count, payment method and coupon.
- WooCommerce `begin_checkout` and `add_to_cart` events; add-to-cart covers
  AJAX buttons, the block cart and product-page form submits.
- The LinkForty click id and UTM parameters survive WooCommerce's
  "redirect to the cart page after successful addition".
- WP Consent API support: the visitor ID is only set with statistics consent.
- High-Performance Order Storage compatibility declaration.
- Release workflow: pushing a `v*` tag builds the plugin zip and attaches it
  to a GitHub release.
