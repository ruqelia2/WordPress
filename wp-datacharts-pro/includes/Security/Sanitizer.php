<?php
/**
 * Input Sanitizer
 *
 * @package WPDCP\Security
 */

declare(strict_types=1);

namespace WPDCP\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Sanitizer
 *
 * Provides static helper methods to sanitize various types of user input
 * before persisting or processing it.
 */
class Sanitizer {

    /**
     * Sanitize a plain-text string (strips all HTML tags).
     *
     * @param string $input Raw input.
     * @return string Sanitized string.
     */
    public static function text( string $input ): string {
        return sanitize_text_field( $input );
    }

    /**
     * Sanitize a string that may contain allowed HTML (uses wp_kses_post).
     *
     * @param string $input Raw input.
     * @return string Sanitized HTML string.
     */
    public static function html( string $input ): string {
        return wp_kses_post( $input );
    }

    /**
     * Sanitize and cast a value to an integer.
     *
     * @param mixed $input Raw input.
     * @return int Sanitized integer.
     */
    public static function int( mixed $input ): int {
        return (int) $input;
    }

    /**
     * Sanitize an email address.
     *
     * @param string $input Raw email address.
     * @return string Sanitized email address, or empty string if invalid.
     */
    public static function email( string $input ): string {
        return sanitize_email( $input );
    }

    /**
     * Sanitize a URL.
     *
     * @param string $input Raw URL.
     * @return string Sanitized URL.
     */
    public static function url( string $input ): string {
        return esc_url_raw( $input );
    }

    /**
     * Sanitize a JSON string.
     *
     * Validates that the string is valid JSON and returns the original string if
     * valid, or an empty JSON object string if not.
     *
     * @param string $input Raw JSON string.
     * @return string Valid JSON string.
     */
    public static function json( string $input ): string {
        $decoded = json_decode( $input, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return '{}';
        }

        $re_encoded = wp_json_encode( $decoded );

        return is_string( $re_encoded ) ? $re_encoded : '{}';
    }

    /**
     * Sanitize an array using a schema that maps keys to sanitization types.
     *
     * Supported schema types: 'text', 'html', 'int', 'email', 'url', 'json', 'bool'.
     *
     * @param array<string, mixed>  $input  Input array.
     * @param array<string, string> $schema Map of key => sanitization type.
     * @return array<string, mixed> Sanitized array.
     */
    public static function array( array $input, array $schema ): array {
        $sanitized = [];

        foreach ( $schema as $key => $type ) {
            if ( ! array_key_exists( $key, $input ) ) {
                continue;
            }

            $value = $input[ $key ];

            $sanitized[ $key ] = match ( $type ) {
                'text'  => self::text( (string) $value ),
                'html'  => self::html( (string) $value ),
                'int'   => self::int( $value ),
                'email' => self::email( (string) $value ),
                'url'   => self::url( (string) $value ),
                'json'  => self::json( (string) $value ),
                'bool'  => (bool) $value,
                default => self::text( (string) $value ),
            };
        }

        return $sanitized;
    }

    /**
     * Sanitize a chart configuration array.
     *
     * Recursively sanitizes string values while preserving structure.
     *
     * @param array<string, mixed> $config Chart configuration array.
     * @return array<string, mixed> Sanitized configuration array.
     */
    public static function chartConfig( array $config ): array {
        $sanitized = [];

        foreach ( $config as $key => $value ) {
            $safe_key = self::text( (string) $key );

            if ( is_array( $value ) ) {
                $sanitized[ $safe_key ] = self::chartConfig( $value );
            } elseif ( is_string( $value ) ) {
                $sanitized[ $safe_key ] = self::text( $value );
            } elseif ( is_int( $value ) || is_float( $value ) || is_bool( $value ) ) {
                $sanitized[ $safe_key ] = $value;
            } else {
                $sanitized[ $safe_key ] = self::text( (string) $value );
            }
        }

        return $sanitized;
    }
}
