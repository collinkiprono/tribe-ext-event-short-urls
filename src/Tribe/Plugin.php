<?php
/**
 * The main service provider for the extension.
 *
 * @since   TBD
 */

namespace Tribe\Extensions\Event_Short_URLs;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Plugin
 *
 * @since TBD
 */
class Plugin extends Service_Provider {

    /**
     * @since TBD
     * @var string
     */
    const VERSION = '0.1.0';

    /**
     * @since TBD
     * @var string
     */
    const FILE = TRIBE_EXTENSION_EVENT_SHORT_URLS_FILE;

    /** @var string */
    public $plugin_dir;
    /** @var string */
    public $plugin_path;
    /** @var string */
    public $plugin_url;

    /**
     * Setup the Extension's properties and register services.
     *
     * @since TBD
     */
    public function register() {
        $this->plugin_path = \trailingslashit( \dirname( static::FILE ) );
        $this->plugin_dir  = \trailingslashit( \basename( $this->plugin_path ) );
        $this->plugin_url  = \plugins_url( $this->plugin_dir, $this->plugin_path );

        $this->container->singleton( static::class, $this );
        $this->container->singleton( 'extension.event_short_urls', $this );

        // Register dependency manifest and PUE.
        $plugin_register = new Plugin_Register();
        $plugin_register->register_plugin();
        $this->container->singleton( Plugin_Register::class, $plugin_register );
        $this->container->singleton( 'extension.event_short_urls.register', $plugin_register );
        $this->container->register( PUE::class );

        // Bail if TEC not present.
        if ( ! \class_exists( '\\Tribe__Events__Main' ) ) {
            return;
        }

        $this->container->register( Admin::class );
    }
}


