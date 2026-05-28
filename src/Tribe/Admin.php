<?php
/**
 * Admin UI for managing event short URLs.
 *
 * @since   TBD
 */

namespace Tribe\Extensions\Event_Short_URLs;

use TEC\Common\Contracts\Service_Provider;

class Admin extends Service_Provider {

    /**
     * Meta key for short code.
     *
     * @since TBD
     * @var string
     */
    const META_KEY = '_tec_short_event_code';

    /**
     * @since TBD
     */
    public function register() {
        $this->container->singleton( static::class, $this );
        \add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
        \add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
        \add_filter( 'manage_tribe_events_posts_columns', [ $this, 'add_admin_column' ] );
        \add_action( 'manage_tribe_events_posts_custom_column', [ $this, 'render_admin_column' ], 10, 2 );
        \add_filter( 'get_shortlink', [ $this, 'filter_get_shortlink' ], 10, 3 );
        // Keep domain same; expose shortlink via get_shortlink, no permalink override required.
        \add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
        \add_action( 'admin_notices', [ $this, 'maybe_show_pretty_permalinks_notice' ] );
    }

    /**
     * Add meta box on event editor.
     *
     * @since TBD
     */
    public function add_meta_box() {
        \add_meta_box(
            'tec_event_short_url',
            \__( 'Event Short URL', 'tribe-ext-event-short-urls' ),
            [ $this, 'render_meta_box' ],
            'tribe_events',
            'side',
            'default'
        );
    }

    /**
     * Render the meta box.
     *
     * @since TBD
     *
     * @param \WP_Post $post The event post.
     */
    public function render_meta_box( $post ) {
        $code     = \get_post_meta( $post->ID, self::META_KEY, true );
        if ( '' === $code ) {
            $code = $this->generate_default_code( $post->ID );
        }
        \wp_nonce_field( 'tec_short_url_save', 'tec_short_url_nonce' );
        $short_base = \home_url( '/e/' );
        $short_url  = \trailingslashit( $short_base . $code );
        echo '<p>' . \esc_html__( 'Share a short link for this event.', 'tribe-ext-event-short-urls' ) . '</p>';
        echo '<p><code>' . \esc_html( \home_url() ) . '/e/</code><input type="text" name="tec_short_code" value="' . \esc_attr( $code ) . '" style="width:100%" /></p>';
        echo '<p><a href="' . \esc_url( $short_url ) . '" target="_blank" rel="noopener">' . \esc_html__( 'Open short URL', 'tribe-ext-event-short-urls' ) . '</a></p>';
        echo '<p><button type="button" class="button" id="tec-copy-short-url" data-url="' . \esc_attr( $short_url ) . '">' . \esc_html__( 'Copy short URL', 'tribe-ext-event-short-urls' ) . '</button></p>';
    }

    /**
     * Save the short code.
     *
     * @since TBD
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post.
     */
    public function save_meta( $post_id, $post ) {
        if ( 'tribe_events' !== $post->post_type ) {
            return;
        }
        if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! isset( $_POST['tec_short_url_nonce'] ) || ! \wp_verify_nonce( $_POST['tec_short_url_nonce'], 'tec_short_url_save' ) ) {
            return;
        }
        if ( ! \current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $requested = isset( $_POST['tec_short_code'] ) ? \sanitize_title( \wp_unslash( $_POST['tec_short_code'] ) ) : '';
        if ( '' === $requested ) {
            $requested = $this->generate_default_code( $post_id );
        }
        $code = $this->ensure_unique_code( $requested, $post_id );
        \update_post_meta( $post_id, self::META_KEY, $code );
    }

    /**
     * Add column.
     *
     * @since TBD
     */
    public function add_admin_column( $columns ) {
        $columns['tec_short_url'] = \__( 'Short URL', 'tribe-ext-event-short-urls' );
        return $columns;
    }

    /**
     * Render column.
     *
     * @since TBD
     */
    public function render_admin_column( $column, $post_id ) {
        if ( 'tec_short_url' !== $column ) {
            return;
        }
        $code = \get_post_meta( $post_id, self::META_KEY, true );
        if ( empty( $code ) ) {
            $code = $this->generate_default_code( $post_id );
        }
        $short_url = \trailingslashit( \home_url( '/e/' . $code ) );
        echo '<a href="' . \esc_url( $short_url ) . '" target="_blank" rel="noopener">' . \esc_html( $short_url ) . '</a>';
    }

    /**
     * Provide `get_shortlink()` for events.
     *
     * @since TBD
     */
    public function filter_get_shortlink( $shortlink, $id, $context ) {
        $post = \get_post( $id );
        if ( ! $post || 'tribe_events' !== $post->post_type ) {
            return $shortlink;
        }
        $code = \get_post_meta( $id, self::META_KEY, true );
        if ( empty( $code ) ) {
            $code = $this->generate_default_code( $id );
        }
        return \trailingslashit( \home_url( '/e/' . $code ) );
    }

    /**
     * Optionally swap event permalink shown in TEC models to shortlink in certain contexts.
     *
     * @since TBD
     */
    // Removed unused filter_event_permalink.

    /**
     * Enqueue admin JS for copy action.
     *
     * @since TBD
     */
    public function enqueue_admin( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }
        \wp_add_inline_script( 'jquery-core', "jQuery(document).on('click', '#tec-copy-short-url', function(){ const url = this.getAttribute('data-url'); if(navigator.clipboard){ navigator.clipboard.writeText(url); } else { const t=document.createElement('textarea'); t.value=url; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t);} this.textContent='" . \esc_js( \__( 'Copied!', 'tribe-ext-event-short-urls' ) ) . "'; });" );
    }

    /**
     * Show a notice if pretty permalinks are disabled, since /e/{code} requires them.
     *
     * @since TBD
     */
    public function maybe_show_pretty_permalinks_notice() {
        if ( ! \current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( '' !== \get_option( 'permalink_structure' ) ) {
            return;
        }
        // Only show on TEC screens or general admin where it makes sense.
        $screen = \function_exists( 'get_current_screen' ) ? \get_current_screen() : null;
        if ( $screen && ! in_array( $screen->id, [ 'tribe_events', 'edit-tribe_events', 'settings_page_permalinks' ], true ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p>'
            . \esc_html__( 'Event Short URLs require pretty permalinks. Enable a permalink structure under Settings → Permalinks.', 'tribe-ext-event-short-urls' )
            . '</p></div>';
    }

    /**
     * Generate a default code from the post ID.
     *
     * @since TBD
     */
    public static function generate_default_code( $post_id ) {
        return \base_convert( (string) \absint( $post_id ), 10, 36 );
    }

    /**
     * Ensure the code is unique across events.
     *
     * @since TBD
     */
    public static function ensure_unique_code( $requested, $post_id ) {
        $code   = $requested;
        $suffix = 0;
        while ( $conflict = self::find_conflict( $code, $post_id ) ) {
            $suffix++;
            $code = $requested . $suffix;
        }
        return $code;
    }

    /**
     * Check for existing usage of the code.
     *
     * @since TBD
     */
    protected static function find_conflict( $code, $post_id ) {
        $existing = Rewrite::lookup_event_id_by_code( $code );
        return ( $existing && (int) $existing !== (int) $post_id );
    }
}


