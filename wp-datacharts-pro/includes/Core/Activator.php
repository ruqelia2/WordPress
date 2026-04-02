<?php
/**
 * Plugin Activator
 *
 * @package WPDCP\Core
 */

declare(strict_types=1);

namespace WPDCP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Database\Schema;
use WPDCP\Security\CapabilityManager;

/**
 * Class Activator
 *
 * Handles tasks to perform when the plugin is activated.
 */
class Activator {

    /**
     * Run plugin activation routines.
     *
     * Checks server requirements, creates database tables, registers
     * capabilities, creates the uploads directory, and flushes rewrite rules.
     */
    public static function activate(): void {
        self::checkPhpVersion();
        self::checkWordPressVersion();

        Schema::createTables();
        CapabilityManager::addCapabilities();
        self::createUploadsDirectory();

        update_option( 'wpdcp_db_version', WPDCP_DB_VERSION );
        flush_rewrite_rules();
    }

    /**
     * Ensure the server meets the minimum PHP version requirement.
     */
    private static function checkPhpVersion(): void {
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            wp_die(
                esc_html__(
                    'WP DataCharts Pro requires PHP 8.0 or higher. Please upgrade PHP before activating this plugin.',
                    'wp-datacharts-pro'
                ),
                esc_html__( 'Plugin Activation Error', 'wp-datacharts-pro' ),
                [ 'back_link' => true ]
            );
        }
    }

    /**
     * Ensure the WordPress installation meets the minimum version requirement.
     */
    private static function checkWordPressVersion(): void {
        global $wp_version;

        if ( version_compare( $wp_version, '6.4', '<' ) ) {
            wp_die(
                esc_html__(
                    'WP DataCharts Pro requires WordPress 6.4 or higher. Please upgrade WordPress before activating this plugin.',
                    'wp-datacharts-pro'
                ),
                esc_html__( 'Plugin Activation Error', 'wp-datacharts-pro' ),
                [ 'back_link' => true ]
            );
        }
    }

    /**
     * Create the plugin uploads directory if it does not exist.
     */
    private static function createUploadsDirectory(): void {
        $upload_dir = wp_upload_dir();
        $wpdcp_dir  = trailingslashit( $upload_dir['basedir'] ) . 'wpdcp';

        if ( ! file_exists( $wpdcp_dir ) ) {
            wp_mkdir_p( $wpdcp_dir );
        }

        // Protect the directory with a basic .htaccess.
        $htaccess = $wpdcp_dir . '/.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $htaccess, "Options -Indexes\n" );
        }
    }
}
