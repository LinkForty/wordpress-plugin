## Description

<!-- What changes, and why. Link the issue if there is one. -->

Fixes #

## Type of change

- [ ] Bug fix
- [ ] New feature (setting, hook, event, or property)
- [ ] Breaking change (renamed or removed event, property, setting, or hook)
- [ ] Documentation
- [ ] Refactor or tooling

## Event contract

<!-- Delete this section if the change does not touch tracking. -->

- [ ] No event name or documented property was renamed or removed
- [ ] Events still go through the browser pixel, not server to server
- [ ] A purchase is still reported exactly once per order

## Testing

<!-- Which rows of the manual checklist in CONTRIBUTING.md did you run, on which WordPress / WooCommerce / PHP versions? -->

- WordPress:
- WooCommerce:
- PHP:
- Checkout: classic / block

- [ ] `composer lint` passes
- [ ] `composer phpcs` passes

## Checklist

- [ ] The title is a conventional commit (it becomes the squash commit and the changelog line); a breaking change has a `!` and a `BREAKING CHANGE:` paragraph above
- [ ] README and `readme.txt` updated for any changed setting, event, or hook
- [ ] `CHANGELOG.md` and the version are untouched; the release on merge writes them
