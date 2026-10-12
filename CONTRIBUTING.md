# Contributing to the LinkForty WordPress Plugin

Thank you for considering a contribution. This document covers how to set up a development site, the standards the code is held to, how to test a change, and how a pull request gets merged.

## Code of Conduct

Be respectful and considerate in every interaction. Report unacceptable behavior to the maintainers at support@linkforty.com.

## How Can I Contribute?

### Reporting Bugs

Check the existing issues first. When you open a bug report, use the bug template and include:

- **WordPress, WooCommerce, PHP, and plugin versions**
- **Steps to reproduce**, including whether checkout is classic or block-based
- **Expected behavior** versus what happened
- **What the browser sent**: the `POST` to `/api/pixel/v1/event` from the network tab, with the site key redacted if you prefer
- **Other plugins that touch the front end**: caching, optimization, consent, page builders

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. Include the use case, the proposed behavior, and any alternatives you considered. Changes to which events fire or what they contain get extra scrutiny, because every store on the plugin reports with the same names.

### Pull Requests

1. Fork the repository and create a branch (`fix/pending-order-refresh`, `feature/subscription-events`)
2. Make your change, following the standards below
3. Run `composer lint` and `composer phpcs`
4. Test on a real WordPress site (see [Testing](#testing))
5. Add a line under `[Unreleased]` in `CHANGELOG.md`
6. Open a pull request using the template

## Development Setup

### Prerequisites

- PHP 7.4 or newer and [Composer](https://getcomposer.org/)
- Docker, if you want the one-command local site below
- A LinkForty workspace and its site key, from Workspace Settings → Web Pixel

### Install the tooling

```bash
git clone https://github.com/YOUR_USERNAME/wordpress-plugin.git linkforty
cd linkforty
composer install
```

### A local WordPress with WooCommerce

The repository ships a `.wp-env.json`, so a complete site with WooCommerce and this plugin takes one command:

```bash
npx @wordpress/env start
```

It prints the site URL (usually `http://localhost:8888`, admin `admin` / `password`). The plugin directory is mounted live, so edits show up on reload. `npx @wordpress/env stop` shuts it down, `npx @wordpress/env destroy` resets it.

Then open **Settings → LinkForty**, paste your site key, and leave **Exclude administrators** off while you test.

Any other local WordPress works too: symlink or copy this directory to `wp-content/plugins/linkforty`.

### Project Structure

```
linkforty/
├── linkforty.php                         # Plugin header, constants, bootstrap
├── uninstall.php                         # Removes settings on delete
├── includes/
│   ├── class-linkforty.php               # Wires the parts together
│   ├── class-linkforty-options.php       # Settings storage and validation
│   ├── class-linkforty-settings.php      # Settings → LinkForty screen
│   ├── class-linkforty-pixel.php         # Enqueues the pixel with its data-* attributes
│   ├── class-linkforty-events.php        # Collects events, prints them in the footer
│   └── class-linkforty-woocommerce.php   # purchase, begin_checkout, add_to_cart
├── assets/js/linkforty-woocommerce.js    # Browser-side add-to-cart listener
├── readme.txt                            # WordPress.org readme
└── .github/workflows/                    # CI and the tag-driven release
```

## Coding Standards

### PHP

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/), enforced by `composer phpcs` with the `WordPress-Extra`, `WordPress-Docs`, and `PHPCompatibilityWP` rulesets in `phpcs.xml.dist`. `composer phpcbf` fixes most formatting findings.
- Tabs for indentation, spaces inside parentheses, Yoda conditions: the WordPress style, not PSR-12.
- Everything global is prefixed: classes `LinkForty_*`, functions, hooks, options, meta keys, and script handles `linkforty_*` or `linkforty-*`.
- PHP 7.4 compatible. No typed properties, no `match`, no named arguments. CI runs on 7.4 and 8.3.
- Escape on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`) and sanitize on input. Every string shown to a user goes through `__()` or `esc_html__()` with the `linkforty` text domain.
- WooCommerce orders are read and written only through the CRUD API (`wc_get_order`, `$order->get_meta()`, `$order->update_meta_data()`), never through post meta functions. That is what keeps the High-Performance Order Storage declaration true.
- Nothing calls LinkForty from the server. See the next section for why.
- One class per file, named `class-linkforty-<thing>.php`, with a file-level and class-level docblock. Document every hook with a `@param` list in the docblock above the `apply_filters()` call.

### JavaScript

- Plain ES5 in `assets/js`, no build step and no dependencies beyond jQuery where WooCommerce already loads it.
- Never call the ingestion endpoint directly. Hand events to `window.LinkForty.track()` and fall back to `window.LinkForty.q.push()` when the pixel has not loaded yet.

### The event contract

These rules exist because every store running the plugin reports with the same event names, and the LinkForty dashboard treats them as one vocabulary.

- **Events go through the browser pixel, never server to server.** The pixel attaches the session's click id and UTM parameters, which is the only thing that attributes a purchase to a link. A server-side request has neither.
- **A purchase is a confirmed order on the order-received page**, never a button click. Button clicks are intent.
- **Each order is reported once.** The `_linkforty_purchase_tracked` order meta is the guard, and the ingestion endpoint does not deduplicate for us.
- **Event names and properties are stable.** `purchase`, `begin_checkout`, and `add_to_cart` and their documented properties are an interface. Renaming or removing one is a breaking change that needs a major version and a changelog entry explaining the migration. Adding a property is fine.
- **Properties are flat.** Primitives and arrays of primitives only; the pixel flattens nested objects and caps the payload, so keep to what is documented in the README.
- **Monetary values go in `revenue`** for events that should count toward revenue, and in `value` for events that should not.

## Testing

There is no automated test suite yet; contributions that add one are welcome. Until then, every change that touches tracking is tested on a real site, and the pull request says what was tested.

### Manual checklist

Run through whichever rows your change can affect, with the browser network tab open and filtered to `pixel/v1/event`:

- [ ] Pixel tag present in `<head>` on the home page, a product page, cart, and checkout, with the expected `data-*` attributes
- [ ] A page view reaches the endpoint and returns `204`
- [ ] Place an order with the classic checkout. Exactly one `purchase` with the correct `order_id`, `revenue`, and `currency`
- [ ] Place an order with the block checkout. Same result
- [ ] Refresh the order-received page. No second `purchase`
- [ ] An order left `pending` (for example, a gateway that does not return) sends nothing; after it moves to `processing`, a visit to the order-received page sends one `purchase`
- [ ] `begin_checkout` on the checkout page, not on the order-received page
- [ ] `add_to_cart` from an archive AJAX button, from a product page form submit, and from the block cart
- [ ] WooCommerce's "Redirect to the cart page after successful addition" on: arrive on `?add-to-cart=123&lf_click=test`, confirm `lf_click` is still on the cart URL
- [ ] High-Performance Order Storage on and off, if you touched order code
- [ ] **Exclude administrators** on: no pixel while logged in as an admin
- [ ] With a WP Consent API plugin installed and statistics consent denied, no `data-visitors` attribute even when the toggle is on
- [ ] Deactivate and reactivate: settings survive. Delete: settings are gone

### Static checks

```bash
composer lint    # PHP syntax on every file
composer phpcs   # coding standards
composer build   # produces build/linkforty.zip from .distignore
```

CI runs the first two on PHP 7.4 and 8.3, and checks that the version in `linkforty.php`, `readme.txt`, and `CHANGELOG.md` agree.

## Pull Request Process

### Commit messages

Follow conventional commits:

```
type(scope): description
```

Types: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`. Scopes that fit this plugin: `pixel`, `woocommerce`, `settings`, `events`, `release`.

```
fix(woocommerce): report pending orders once they move to processing
feat(settings): add an autocapture toggle
docs(readme): document the purchase properties
```

### Before you open the PR

- [ ] `composer lint` and `composer phpcs` pass
- [ ] Tested on a real site; the relevant checklist rows are in the PR description
- [ ] `CHANGELOG.md` has a line under `[Unreleased]`
- [ ] README and `readme.txt` updated if you changed a setting, an event, or a hook
- [ ] No new event names or renamed properties without discussion in an issue first

### Review

A maintainer reviews every pull request. Expect questions about attribution and double counting on anything near the purchase path; those are the two failure modes that cost a store real money in their reports. Small, focused pull requests get merged faster than large ones.

## Versioning and Releases

Maintainers cut releases. The plugin follows [semantic versioning](https://semver.org/):

- **Patch** for fixes
- **Minor** for new settings, hooks, events, or properties
- **Major** for a renamed or removed event, property, setting, or hook

To release, the version is bumped in `linkforty.php` (header and constant), `readme.txt` (`Stable tag`) and `CHANGELOG.md` (the `[Unreleased]` section becomes the new version), then a matching tag such as `v0.2.0` is pushed. The release workflow lints, builds the zip, and attaches it to a GitHub release with the changelog section as notes. Versions below 1.0 are marked pre-release.

## Security

Do not report security problems in public issues. Email security@linkforty.com; see [SECURITY.md](.github/SECURITY.md).

## Questions?

- **GitHub Issues** for bugs and feature requests
- **GitHub Discussions** for questions about using the plugin
- **support@linkforty.com** for anything about your LinkForty workspace

## License

By contributing, you agree that your contributions are licensed under the [MIT License](LICENSE).
