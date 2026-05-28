<?php
/**
 * Plugin Name:       The Events Calendar Extension: Event Short URLs
 * Description:       Adds customizable short URLs for single events, e.g. /e/abc123 → full event URL.
 * Version:           0.1.0
 * Author:            TEC Labs
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tribe-ext-event-short-urls
 * Domain Path:       /lang
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define main plugin file for later references.
if ( ! defined( 'TRIBE_EXTENSION_EVENT_SHORT_URLS_FILE' ) ) {
	define( 'TRIBE_EXTENSION_EVENT_SHORT_URLS_FILE', __FILE__ );
}

/**
 * Register the autoloader namespace and service provider when Tribe Common is ready.
 */
function tribe_extension_event_short_urls_boot() {
	// When we don't have autoloader from common we bail.
	if ( ! \class_exists( 'Tribe__Autoloader' ) ) {
		return;
	}

	// Register the namespace so our service provider can be resolved.
	\Tribe__Autoloader::instance()->register_prefix(
		'\\Tribe\\Extensions\\Event_Short_URLs\\',
		__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Tribe',
		'tribe-ext-event-short-urls'
	);

	// Register the provider.
	if ( \function_exists( 'tribe_register_provider' ) ) {
		\tribe_register_provider( '\\Tribe\\Extensions\\Event_Short_URLs\\Plugin' );
	}

	// Wire rewrite hooks.
	\add_action( 'init', [ '\\Tribe\\Extensions\\Event_Short_URLs\\Rewrite', 'add_rewrite' ] );
	\add_action( 'template_redirect', [ '\\Tribe\\Extensions\\Event_Short_URLs\\Rewrite', 'maybe_redirect' ] );

	// Wire cron processor.
	\add_action( \Tribe\Extensions\Event_Short_URLs\Cron::HOOK, [ '\\Tribe\\Extensions\\Event_Short_URLs\\Cron', 'process_batch' ] );

	// Register WP-CLI command when in CLI.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		\Tribe\Extensions\Event_Short_URLs\WPCLI::register();
	}
}
\add_action( 'tribe_common_loaded', 'tribe_extension_event_short_urls_boot' );

/**
 * Activation: add rewrite rules and flush; schedule backfill.
 */
function tribe_extension_event_short_urls_activate() {
	// Add rewrite directly here (autoloader might not be ready on activation).
	\add_rewrite_tag( '%tec_short_event%', '([^&]+)' );
	$base = (string) \apply_filters( 'tec_event_short_urls_base', 'e' );
	$base = trim( $base, "/\t\n\r\0\x0B" );
	if ( '' === $base ) {
		$base = 'e';
	}
	$base = preg_replace( '/[^a-z0-9\-_.~]/i', '', $base );
	\add_rewrite_rule( '^' . $base . '/([^/]+)/?$', 'index.php?tec_short_event=$matches[1]', 'top' );
	\flush_rewrite_rules();

	// Schedule first backfill batch without referencing classes.
	if ( ! \wp_next_scheduled( 'tec_event_short_urls_backfill_batch' ) ) {
		\wp_schedule_single_event( time() + 5, 'tec_event_short_urls_backfill_batch' );
	}
}
\register_activation_hook( __FILE__, 'tribe_extension_event_short_urls_activate' );

/**
 * Deactivation: flush rules.
 */
function tribe_extension_event_short_urls_deactivate() {
	\flush_rewrite_rules();
	// Clear any pending backfill job to be safe.
	$ts = \wp_next_scheduled( 'tec_event_short_urls_backfill_batch' );
	if ( $ts ) {
		\wp_unschedule_event( $ts, 'tec_event_short_urls_backfill_batch' );
	}
}
\register_deactivation_hook( __FILE__, 'tribe_extension_event_short_urls_deactivate' );


