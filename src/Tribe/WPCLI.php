<?php
/**
 * WP-CLI command for backfilling / regenerating short codes.
 *
 * @since TBD
 */

namespace Tribe\Extensions\Event_Short_URLs;

class WPCLI {

    public static function register() {
        if ( ! \class_exists( '\\WP_CLI' ) ) {
            return;
        }

        \WP_CLI::add_command( 'tec-short-urls backfill', [ static::class, 'cmd_backfill' ] );
    }

    /**
     * Backfill short codes for events.
     *
     * ## OPTIONS
     *
     * [--regenerate]
     * : Regenerate codes even if they exist.
     *
     * [--batch-size=<num>]
     * : Batch size per query. Default 500.
     *
     * [--dry-run]
     * : Do not write changes; only report.
     *
     * ## EXAMPLES
     *
     *     wp tec-short-urls backfill --batch-size=1000
     *     wp tec-short-urls backfill --regenerate
     *
     * @since TBD
     */
    public static function cmd_backfill( $args, $assoc_args ) {
        $regenerate = \WP_CLI\Utils\get_flag_value( $assoc_args, 'regenerate', false );
        $dry_run    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
        $batch      = (int) ( $assoc_args['batch-size'] ?? 500 );

        $paged   = 1;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        do {
            $q = new \WP_Query( [
                'post_type'      => 'tribe_events',
                'posts_per_page' => $batch,
                'paged'          => $paged,
                'post_status'    => 'any',
                'fields'         => 'ids',
                'no_found_rows'  => false,
            ] );

            foreach ( $q->posts as $post_id ) {
                $current  = \get_post_meta( $post_id, Admin::META_KEY, true );
                $desired  = Admin::generate_default_code( $post_id );
                $ensured  = Admin::ensure_unique_code( $desired, $post_id );

                if ( empty( $current ) ) {
                    $created++;
                    if ( ! $dry_run ) {
                        \update_post_meta( $post_id, Admin::META_KEY, $ensured );
                    }
                } elseif ( $regenerate && $current !== $ensured ) {
                    $updated++;
                    if ( ! $dry_run ) {
                        \update_post_meta( $post_id, Admin::META_KEY, $ensured );
                    }
                } else {
                    $skipped++;
                }
            }

            $paged++;
        } while ( $q->max_num_pages >= $paged );

        \WP_CLI::success( sprintf( 'Created: %d, Updated: %d, Skipped: %d', $created, $updated, $skipped ) );
    }
}


