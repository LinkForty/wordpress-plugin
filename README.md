# LinkForty for WordPress

WordPress plugin for [LinkForty](https://linkforty.com) web analytics and conversion tracking. It adds the LinkForty web pixel to every page and, when WooCommerce is active, reports purchases as conversions attributed to the link that drove the visit. No code, no theme editing.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## Features

- Pixel on every page through `wp_head`, so any theme or page builder works
- WooCommerce `purchase` on the order-received page, once per order, with revenue
- `begin_checkout` and `add_to_cart` (AJAX buttons, block cart, form submits)
- Click id and UTM parameters carried through WooCommerce's add-to-cart redirect
- Cross-domain attribution via linked domains
- WP Consent API aware visitor ID
- High-Performance Order Storage compatible

## Install

1. Download `linkforty-<version>.zip` from the [latest release](https://github.com/LinkForty/wordpress-plugin/releases/latest) and upload it under Plugins → Add New → Upload Plugin. To run from source instead, clone this repository into `wp-content/plugins/linkforty`.
2. Activate the plugin.
3. Settings → LinkForty: paste the site key from Workspace Settings → Web Pixel in the LinkForty dashboard.

Then mark `purchase` as a goal under Web analytics in LinkForty so it counts as a conversion.

## Events

| Event | When | Properties |
|-------|------|------------|
| `purchase` | Order-received page, once per order, status processing / completed / on-hold | `order_id`, `value` (order total, counted as revenue), `revenue` (same total), `currency`, `item_count`, `payment_method`, `coupon` |
| `begin_checkout` | Checkout page view with a non-empty cart | `cart_total`, `currency`, `item_count` |
| `add_to_cart` | AJAX button, block cart, or product-page form submit | `product_id`, `quantity`, `sku`, plus `product_name`, `price`, `variation_id` on form submits |

Events are handed to the browser pixel (`window.LinkForty.track`), never sent server to server. The pixel attaches the session's click id and UTM parameters, which is what attributes the event to a link.

## Hooks

| Hook | Purpose |
|------|---------|
| `linkforty_should_track` (filter, bool) | Suppress the pixel and all events for a request |
| `linkforty_pixel_attributes` (filter, array) | Change the `data-*` attributes on the pixel tag |
| `linkforty_pixel_url` (filter, string) | Load the pixel from another URL |
| `linkforty_visitor_id_allowed` (filter, bool) | Override the consent decision for the visitor ID |
| `linkforty_event_properties` (filter, array, name) | Adjust or drop any event before it is sent |
| `linkforty_purchase_properties` (filter, array, order) | Adjust the `purchase` properties |
| `linkforty_purchase_order_statuses` (filter, string[], order) | Which order statuses count as a purchase |

Your own code can report conversions too:

```php
LinkForty_Events::track( 'newsletter_signup', array( 'list' => 'weekly' ) );
```

## Development

```bash
composer install
composer lint              # PHP syntax check
composer phpcs             # WordPress coding standards
npx @wordpress/env start   # local WordPress + WooCommerce with this plugin mounted (needs Docker)
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for the coding standards, the event contract, and the manual test checklist.

Build a distributable zip with `composer build`; it honours `.distignore`.

To release, bump the version in `linkforty.php` (header and constant), `readme.txt` and `CHANGELOG.md`, then push a matching tag such as `v0.2.0`. The release workflow lints, builds the zip and attaches it to a GitHub release. Versions starting with `0.` are marked pre-release.

## Requirements

- WordPress 6.3 or newer
- PHP 7.4 or newer
- WooCommerce 7.0 or newer for the e-commerce events (optional)

## License

MIT
