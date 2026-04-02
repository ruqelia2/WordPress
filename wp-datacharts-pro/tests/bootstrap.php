<?php
/**
 * PHPUnit Bootstrap for WP DataCharts Pro
 *
 * @package WPDCP\Tests
 */

declare(strict_types=1);

// Load Composer autoloader.
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
    require_once $autoloader;
}

// Define minimal WordPress stubs if the test suite is not running inside a
// full WordPress install (pure unit tests that do not need WP functions).
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'WPDCP_VERSION' ) ) {
    define( 'WPDCP_VERSION', '1.0.0' );
}

if ( ! defined( 'WPDCP_PLUGIN_FILE' ) ) {
    define( 'WPDCP_PLUGIN_FILE', dirname( __DIR__ ) . '/wp-datacharts-pro.php' );
}

if ( ! defined( 'WPDCP_PLUGIN_DIR' ) ) {
    define( 'WPDCP_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'WPDCP_PLUGIN_URL' ) ) {
    define( 'WPDCP_PLUGIN_URL', 'https://example.com/wp-content/plugins/wp-datacharts-pro/' );
}

if ( ! defined( 'WPDCP_PLUGIN_BASENAME' ) ) {
    define( 'WPDCP_PLUGIN_BASENAME', 'wp-datacharts-pro/wp-datacharts-pro.php' );
}

if ( ! defined( 'WPDCP_DB_VERSION' ) ) {
    define( 'WPDCP_DB_VERSION', '1.0.0' );
}

// Try to load WordPress test suite bootstrap if available.
$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( $wp_tests_dir && file_exists( $wp_tests_dir . '/includes/bootstrap.php' ) ) {
    require_once $wp_tests_dir . '/includes/bootstrap.php';
} else {
    // Stub out WordPress functions required by the classes under test.
    if ( ! function_exists( 'sanitize_text_field' ) ) {
        function sanitize_text_field( string $str ): string {
            return strip_tags( $str );
        }
    }

    if ( ! function_exists( 'sanitize_email' ) ) {
        function sanitize_email( string $email ): string {
            return filter_var( $email, FILTER_SANITIZE_EMAIL ) ?: '';
        }
    }

    if ( ! function_exists( 'esc_url_raw' ) ) {
        function esc_url_raw( string $url ): string {
            return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
        }
    }

    if ( ! function_exists( 'wp_kses_post' ) ) {
        function wp_kses_post( string $html ): string {
            return strip_tags( $html, '<p><a><strong><em><ul><ol><li><br><img>' );
        }
    }

    if ( ! function_exists( 'wp_json_encode' ) ) {
        function wp_json_encode( mixed $data, int $options = 0 ): string|false {
            return json_encode( $data, $options );
        }
    }

    if ( ! function_exists( 'sanitize_title' ) ) {
        function sanitize_title( string $title ): string {
            return strtolower( preg_replace( '/[^a-z0-9-]/i', '-', $title ) ?? '' );
        }
    }

    if ( ! function_exists( 'apply_filters' ) ) {
        function apply_filters( string $tag, mixed $value, mixed ...$args ): mixed {
            return $value;
        }
    }

    if ( ! function_exists( 'sanitize_html_class' ) ) {
        function sanitize_html_class( string $class ): string {
            return preg_replace( '/[^a-z0-9_-]/i', '', $class ) ?? '';
        }
    }
}
