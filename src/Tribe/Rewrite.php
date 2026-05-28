<?php
/**
 * Short URL rewrites and redirect handling.
 *
 * @since   TBD
 */

namespace Tribe\Extensions\Event_Short_URLs;

class Rewrite {

    /**
     * Add the rewrite rule /e/{code} → index.php?tec_short_event={code}.
     *
     * @since TBD
     */
    public static function add_rewrite() {
        \add_rewrite_tag( '%tec_short_event%', '([^&]+)' );
        $base = (string) \apply_filters( 'tec_event_short_urls_base', 'e' );
        $base = trim( $base, "/\t\n\r\0\x0B" );
        if ( '' === $base ) {
            $base = 'e';
        }
        $base = preg_replace( '/[^a-z0-9\-_.~]/i', '', $base );
        \add_rewrite_rule( '^' . $base . '/([^/]+)/?$', 'index.php?tec_short_event=$matches[1]', 'top' );
    }

    /**
     * Handle redirects when a short URL is requested.
     *
     * @since TBD
     */
    public static function maybe_redirect() {
        $code = \get_query_var( 'tec_short_event' );
        if ( empty( $code ) && isset( $_GET['tec_short_event'] ) ) {
            $code = (string) $_GET['tec_short_event'];
        }
        if ( empty( $code ) ) {
            return;
        }

        $event_id = self::lookup_event_id_by_code( $code );
        if ( ! $event_id ) {
            \status_header( 404 );
            \nocache_headers();
            include \get_query_template( '404' );
            exit;
        }

        $url = \get_permalink( $event_id );
        if ( empty( $url ) ) {
            \status_header( 404 );
            \nocache_headers();
            include \get_query_template( '404' );
            exit;
        }

        \wp_safe_redirect( $url, 301 );
        exit;
    }

    /**
     * Find event ID by short code.
     *
     * @since TBD
     *
     * @param string $code Short code.
     *
     * @return int|false Event ID on success, false on failure.
     */
    public static function lookup_event_id_by_code( $code ) {
        global $wpdb;
        $meta_key   = Admin::META_KEY;
        $prepared   = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s LIMIT 1", $meta_key, $code );
        $post_id    = (int) $wpdb->get_var( $prepared );
        if ( $post_id > 0 ) {
            return $post_id;
        }

        // Fallback: decode base36 default codes to support existing events without saved meta.
        $code       = strtolower( (string) $code );
        $decoded_id = 0;
        // Validate code characters to avoid unexpected big numbers.
        if ( preg_match( '/^[a-z0-9]+$/', $code ) ) {
            $decoded_id = (int) \base_convert( $code, 36, 10 );
        }
        if ( $decoded_id > 0 ) {
            $post_type = \get_post_type( $decoded_id );
            if ( 'tribe_events' === $post_type ) {
                // Persist mapping for faster future lookups.
                $existing = \get_post_meta( $decoded_id, Admin::META_KEY, true );
                if ( empty( $existing ) ) {
                    \update_post_meta( $decoded_id, Admin::META_KEY, $code );
                }
                return $decoded_id;
            }
        }

        return false;
    }
}


