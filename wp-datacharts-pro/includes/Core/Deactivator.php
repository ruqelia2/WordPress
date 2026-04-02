<?php
/**
 * Plugin Deactivator
 *
 * @package WPDCP\Core
 */

declare(strict_types=1);

namespace WPDCP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Deactivator
 *
 * Handles tasks to perform when the plugin is deactivated.
 */
class Deactivator {

    /**
     * Run plugin deactivation routines.
     *
     * Removes all wpdcp_ transients and flushes rewrite rules.
     * Does NOT delete data — that is handled by uninstall.php.
     */
    public static function deactivate(): void {
        self::deleteTransients();
        flush_rewrite_rules();
    }

    /**
     * Delete all wpdcp_ prefixed transients from the options table.
     */
    private static function deleteTransients(): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_wpdcp_%'
                OR option_name LIKE '_transient_timeout_wpdcp_%'"
        );
    }
}
