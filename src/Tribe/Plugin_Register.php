<?php
/**
 * Handles the Extension plugin dependency manifest registration.
 *
 * @since TBD
 */

namespace Tribe\Extensions\Event_Short_URLs;

use \Tribe__Abstract_Plugin_Register as Abstract_Plugin_Register;

class Plugin_Register extends Abstract_Plugin_Register {
    protected $base_dir     = Plugin::FILE;
    protected $version      = Plugin::VERSION;
    protected $main_class   = Plugin::class;
    protected $dependencies = [
        'parent-dependencies' => [
            'Tribe__Events__Main' => '6.1.2',
        ],
    ];
}


