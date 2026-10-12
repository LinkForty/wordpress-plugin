# Security Policy

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Email **security@linkforty.com** with:

- A description of the vulnerability and its impact
- Steps to reproduce
- The plugin, WordPress, WooCommerce, and PHP versions affected
- A suggested fix, if you have one

We acknowledge reports within 48 hours, keep you informed of progress, and credit you in the release notes with your permission.

## Scope

This plugin runs PHP in the WordPress admin and on the front end, and places a script tag on every page. Reports we care most about:

- Anything that lets a non-administrator change the plugin's settings or the pixel tag
- Output that is not escaped, on the settings screen or in the front-end inline scripts
- Anything that lets a visitor forge or replay a `purchase` for an order that is not theirs

The pixel script itself (`cdn.linkforty.com/pixel.js`) and the LinkForty ingestion endpoint are not part of this repository. Report problems with them to the same address and we will route them.

## Supported Versions

Security fixes go to the latest release. Update to the newest version before reporting.
