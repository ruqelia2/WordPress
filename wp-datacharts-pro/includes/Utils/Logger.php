<?php
/**
 * Logger Utility
 *
 * @package WPDCP\Utils
 */

declare(strict_types=1);

namespace WPDCP\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Logger
 *
 * Simple static logger that writes to PHP's error log.
 * Only active when WP_DEBUG is true.
 */
class Logger {

    /**
     * Log an informational message.
     *
     * @param string               $message Log message.
     * @param array<string, mixed> $context Additional context data.
     */
    public static function info( string $message, array $context = [] ): void {
        self::log( 'INFO', $message, $context );
    }

    /**
     * Log a warning message.
     *
     * @param string               $message Log message.
     * @param array<string, mixed> $context Additional context data.
     */
    public static function warning( string $message, array $context = [] ): void {
        self::log( 'WARNING', $message, $context );
    }

    /**
     * Log an error message.
     *
     * @param string               $message Log message.
     * @param array<string, mixed> $context Additional context data.
     */
    public static function error( string $message, array $context = [] ): void {
        self::log( 'ERROR', $message, $context );
    }

    /**
     * Log a debug message.
     *
     * @param string               $message Log message.
     * @param array<string, mixed> $context Additional context data.
     */
    public static function debug( string $message, array $context = [] ): void {
        self::log( 'DEBUG', $message, $context );
    }

    /**
     * Write a formatted log entry to the PHP error log.
     *
     * @param string               $level   Log level label.
     * @param string               $message Log message.
     * @param array<string, mixed> $context Additional context data.
     */
    private static function log( string $level, string $message, array $context = [] ): void {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $date           = gmdate( 'Y-m-d H:i:s' );
        $context_string = $context ? ' ' . wp_json_encode( $context ) : '';

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log(
            sprintf(
                '[WP DataCharts Pro] [%s] %s - %s%s',
                $level,
                $date,
                $message,
                $context_string
            )
        );
    }
}
