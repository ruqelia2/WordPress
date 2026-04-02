<?php
/**
 * General Helper Utilities
 *
 * @package WPDCP\Utils
 */

declare(strict_types=1);

namespace WPDCP\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Helpers
 *
 * A collection of static utility methods used throughout the plugin.
 */
class Helpers {

    /**
     * Return the full URL to a plugin asset.
     *
     * @param string $path Relative path from the plugin root (e.g. 'assets/css/admin.css').
     * @return string Absolute URL.
     */
    public static function pluginUrl( string $path = '' ): string {
        return WPDCP_PLUGIN_URL . ltrim( $path, '/' );
    }

    /**
     * Return the absolute filesystem path to a plugin file or directory.
     *
     * @param string $path Relative path from the plugin root.
     * @return string Absolute filesystem path.
     */
    public static function pluginDir( string $path = '' ): string {
        return WPDCP_PLUGIN_DIR . ltrim( $path, '/' );
    }

    /**
     * Return the absolute filesystem path to the plugin uploads directory.
     *
     * @param string $path Optional sub-path within uploads/wpdcp/.
     * @return string Absolute filesystem path.
     */
    public static function uploadDir( string $path = '' ): string {
        $upload_dir = wp_upload_dir();
        $base       = trailingslashit( $upload_dir['basedir'] ) . 'wpdcp';

        return $path ? $base . '/' . ltrim( $path, '/' ) : $base;
    }

    /**
     * Return the public URL to the plugin uploads directory.
     *
     * @param string $path Optional sub-path within uploads/wpdcp/.
     * @return string Absolute URL.
     */
    public static function uploadUrl( string $path = '' ): string {
        $upload_dir = wp_upload_dir();
        $base       = trailingslashit( $upload_dir['baseurl'] ) . 'wpdcp';

        return $path ? $base . '/' . ltrim( $path, '/' ) : $base;
    }

    /**
     * Determine whether the current admin screen belongs to this plugin.
     *
     * @return bool True when viewing a plugin admin page.
     */
    public static function isPluginAdminPage(): bool {
        if ( ! is_admin() ) {
            return false;
        }

        $screen = get_current_screen();

        if ( null === $screen ) {
            return false;
        }

        return str_contains( $screen->id, 'wpdcp' )
            || str_contains( $screen->id, 'datacharts' );
    }

    /**
     * Format a byte count as a human-readable string.
     *
     * @param int $bytes     Number of bytes.
     * @param int $precision Number of decimal places.
     * @return string Formatted string, e.g. "1.23 MB".
     */
    public static function formatBytes( int $bytes, int $precision = 2 ): string {
        if ( $bytes <= 0 ) {
            return '0 B';
        }

        $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
        $power = (int) floor( log( $bytes, 1024 ) );
        $power = min( $power, count( $units ) - 1 );

        $value = $bytes / ( 1024 ** $power );

        return round( $value, $precision ) . ' ' . $units[ $power ];
    }

    /**
     * Generate a URL-safe slug from a plain-text title.
     *
     * @param string $title Source text.
     * @return string URL-safe slug.
     */
    public static function generateSlug( string $title ): string {
        return sanitize_title( $title );
    }
}
