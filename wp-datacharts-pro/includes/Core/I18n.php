<?php
/**
 * Internationalisation loader
 *
 * @package WPDCP\Core
 */

declare(strict_types=1);

namespace WPDCP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class I18n
 *
 * Loads the plugin text domain so all translatable strings are available.
 */
class I18n {

    /**
     * Load the plugin text domain for translation.
     */
    public function load(): void {
        load_plugin_textdomain(
            'wp-datacharts-pro',
            false,
            dirname( WPDCP_PLUGIN_BASENAME ) . '/languages'
        );
    }
}
