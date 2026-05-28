<?php
/**
 * Batched backfill via WP-Cron.
 *
 * @since TBD
 */

namespace Tribe\Extensions\Event_Short_URLs;

class Cron {

    const HOOK = 'tec_event_short_urls_backfill_batch';

    /**
     * Schedule a batched backfill.
     *
     * @since TBD
     */
    public static function schedule_backfill() {
        if ( ! \wp_next_scheduled( self::HOOK ) ) {
            \wp_schedule_single_event( time() + 5, self::HOOK );
        }
    }

    /**
     * Process one batch.
     *
     * @since TBD
     */
    public static function process_batch() {
        $batch_size = (int) \apply_filters( 'tec_event_short_urls_backfill_batch_size', 200 );
        $paged      = (int) \get_option( 'tec_event_short_urls_backfill_paged', 1 );

        $q = new \WP_Query( [
            'post_type'      => 'tribe_events',
            'posts_per_page' => $batch_size,
            'paged'          => $paged,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ] );

        if ( empty( $q->posts ) ) {
            \delete_option( 'tec_event_short_urls_backfill_paged' );
            return;
        }

        foreach ( $q->posts as $post_id ) {
            $code = \get_post_meta( $post_id, Admin::META_KEY, true );
            if ( empty( $code ) ) {
                $requested = Admin::generate_default_code( $post_id );
                $unique    = Admin::ensure_unique_code( $requested, $post_id );
                \update_post_meta( $post_id, Admin::META_KEY, $unique );
            }
        }

        \update_option( 'tec_event_short_urls_backfill_paged', $paged + 1 );
        \wp_schedule_single_event( time() + 5, self::HOOK );
    }
}


