<?php
/**
 * Handles the update functionality of the plugin.
 *
 * @since TBD
 */

namespace Tribe\Extensions\Event_Short_URLs;

use TEC\Common\Contracts\Service_Provider;

class PUE extends Service_Provider {

    /** @var string */
    private static $pue_slug = 'tribe-ext-event-short-urls';

    /** @var bool */
    public static $is_active = false;

    /** @var string */
    private $update_url = 'https://theeventscalendar.com/';

    /**
     * Register PUE hooks.
     *
     * @since TBD
     */
    public function register() {
        $this->container->singleton( static::class, $this );
        $this->container->singleton( 'extension.event_short_urls.pue', $this );

        if ( ! static::$is_active ) {
            return;
        }

        \add_action( 'tribe_helper_activation_complete', [ $this, 'load_plugin_update_engine' ] );
        \register_uninstall_hook( Plugin::FILE, [ static::class, 'uninstall' ] );
    }

    /**
     * Initialize the update engine if available.
     *
     * @since TBD
     */
    public function load_plugin_update_engine() {
        $pue_enabled = \apply_filters( 'tribe_enable_pue', true, static::get_slug() );
        if ( ! ( $pue_enabled && \class_exists( 'Tribe__PUE__Checker' ) ) ) {
            return;
        }

        new \Tribe__PUE__Checker(
            $this->update_url,
            static::get_slug(),
            [],
            \plugin_basename( Plugin::FILE )
        );
    }

    /**
     * Get the PUE slug.
     *
     * @since TBD
     */
    public static function get_slug() {
        return static::$pue_slug;
    }

    /**
     * Cleanup on uninstall.
     *
     * @since TBD
     */
    public static function uninstall() {
        $slug = \str_replace( '-', '_', static::get_slug() );
        \delete_option( 'pue_install_key_' . $slug );
        \delete_option( 'pu_dismissed_upgrade_' . $slug );
    }
}


