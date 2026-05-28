=== The Events Calendar Extension: Event Short URLs ===
Contributors: tec-labs
Tags: events, the events calendar, shortlink, permalink, url
Requires at least: 6.3
Tested up to: 6.6
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds customizable short URLs for single events, e.g. /e/abc123 → full event URL.

== Description ==

This extension adds a short URL for each event that redirects to the full event permalink.

- New metabox on the event editor to view/customize the short code.
- Copy-to-clipboard button.
- Works with existing events out of the box using base36(post_id) codes.
- `/e/{code}` short paths redirect to the full event URL (301).
- `get_shortlink( $event_id )` returns the short URL for integrations.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`.
2. Activate via Plugins.
3. Visit Settings → Permalinks once (recommended) to confirm rewrite rules.

== Frequently Asked Questions ==

= Do I need to update each event to get a short URL? =
No. Existing events work immediately. Visiting a short URL will also persist the mapping.

= Can I customize the short code? =
Yes, from the Event Short URL metabox on the event edit screen.

== Changelog ==

= 0.1.0 =
* Initial release.
