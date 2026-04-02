<?php
/**
 * Input Validator
 *
 * @package WPDCP\Security
 */

declare(strict_types=1);

namespace WPDCP\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Validator
 *
 * Provides static methods to validate user input against known allowed values
 * and common validation rules.
 */
class Validator {

    /**
     * Known valid chart types.
     */
    private const CHART_TYPES = [
        'line', 'bar', 'pie', 'doughnut', 'area', 'scatter', 'radar',
        'polar', 'bubble', 'heatmap', 'funnel', 'gauge', 'candlestick',
        'treemap', 'waterfall', 'sankey', 'boxplot', 'timeline', 'mixed', 'map',
    ];

    /**
     * Known valid rendering engines.
     */
    private const ENGINES = [
        'chartjs', 'highcharts', 'apexcharts', 'echarts',
    ];

    /**
     * Known valid data source types.
     */
    private const DATA_SOURCE_TYPES = [
        'mysql', 'wpdb', 'csv', 'excel', 'google_sheets',
        'rest_api', 'json', 'xml',
    ];

    /**
     * Check whether the given string is a valid chart type.
     *
     * @param string $type Chart type slug.
     * @return bool True if valid.
     */
    public static function chartType( string $type ): bool {
        return in_array( $type, self::CHART_TYPES, true );
    }

    /**
     * Check whether the given string is a valid rendering engine.
     *
     * @param string $engine Engine slug.
     * @return bool True if valid.
     */
    public static function engine( string $engine ): bool {
        return in_array( $engine, self::ENGINES, true );
    }

    /**
     * Check whether the given string is a valid data source type.
     *
     * @param string $type Data source type slug.
     * @return bool True if valid.
     */
    public static function dataSourceType( string $type ): bool {
        return in_array( $type, self::DATA_SOURCE_TYPES, true );
    }

    /**
     * Check whether the given string is valid JSON.
     *
     * @param string $json JSON string to validate.
     * @return bool True if valid JSON.
     */
    public static function json( string $json ): bool {
        json_decode( $json );
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check that all required fields are present and non-empty in a data array.
     *
     * @param array<string, mixed> $data   Input data.
     * @param string[]             $fields List of required field names.
     * @return string[] Array of missing field names (empty if all present).
     */
    public static function required( array $data, array $fields ): array {
        $missing = [];

        foreach ( $fields as $field ) {
            if ( ! isset( $data[ $field ] ) || '' === (string) $data[ $field ] ) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Validate an uploaded file against allowed MIME types and basic checks.
     *
     * @param array<string, mixed> $file         $_FILES entry.
     * @param string[]             $allowedMimes Allowed MIME type strings, e.g. ['text/csv'].
     * @return bool|\WP_Error True on success, WP_Error on failure.
     */
    public static function fileUpload( array $file, array $allowedMimes ): bool|\WP_Error {
        if ( ! isset( $file['tmp_name'], $file['name'], $file['error'] ) ) {
            return new \WP_Error(
                'invalid_file',
                __( 'Invalid file upload data.', 'wp-datacharts-pro' )
            );
        }

        if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
            return new \WP_Error(
                'upload_error',
                sprintf(
                    /* translators: %d: PHP upload error code */
                    __( 'File upload error (code %d).', 'wp-datacharts-pro' ),
                    (int) $file['error']
                )
            );
        }

        $file_type = wp_check_filetype( $file['name'] );
        $mime_type = $file_type['type'] ?? '';

        if ( ! in_array( $mime_type, $allowedMimes, true ) ) {
            return new \WP_Error(
                'invalid_mime',
                __( 'File type is not allowed.', 'wp-datacharts-pro' )
            );
        }

        return true;
    }
}
