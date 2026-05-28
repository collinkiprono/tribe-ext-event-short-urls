### The Events Calendar Extension: Event Short URLs ###

This extension adds a short URL for each event that redirects to the full event permalink.

- New metabox on the event editor to view/customize the short code.
- Copy-to-clipboard button.
- Works with existing events out of the box using base36(post_id) codes.
- `/e/{code}` short paths redirect to the full event URL (301).
- `get_shortlink( $event_id )` returns the short URL for integrations.

### Installation ###

1. Upload the plugin to `/wp-content/plugins/`.
2. Activate via Plugins.
3. Visit Settings → Permalinks once (recommended) to confirm rewrite rules.

### Frequently Asked Questions ###

#### Do I need to update each event to get a short URL? ####
No. Existing events work immediately. Visiting a short URL will also persist the mapping.

#### Can I customize the short code? ####
Yes, from the Event Short URL metabox on the event edit screen.
