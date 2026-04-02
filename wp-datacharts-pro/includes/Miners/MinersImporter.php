<?php
/**
 * Miners JSON Importer
 *
 * @package WPDCP\Miners
 */

declare(strict_types=1);

namespace WPDCP\Miners;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MinersImporter
 *
 * Handles JSON validation, parsing, and AJAX-driven import of miner records.
 */
class MinersImporter {

    /** Required fields that every miner record must contain. */
    private const REQUIRED_FIELDS = [
        'model',
        'brand',
        'top_coin',
        'algorithm',
        'power_w',
        'hashrate',
        'price_val',
        'daily_income_val',
    ];

    /** @var MinersDatabase Database handler instance. */
    private MinersDatabase $db;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->db = new MinersDatabase();
    }

    /**
     * Register WordPress AJAX hooks.
     */
    public function registerHooks(): void {
        add_action( 'wp_ajax_wpdcp_miners_import', [ $this, 'handleImport' ] );
        add_action( 'wp_ajax_wpdcp_miners_delete', [ $this, 'handleDelete' ] );
    }

    /**
     * AJAX handler: import JSON data.
     *
     * Expects POST fields:
     *   nonce       – wp_nonce value
     *   json_data   – raw JSON string
     *   import_mode – 'overwrite' | 'append'
     */
    public function handleImport(): void {
        // Verify nonce.
        if ( ! check_ajax_referer( 'wpdcp_miners_import', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'wp-datacharts-pro' ) ], 403 );
        }

        // Verify capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to import data.', 'wp-datacharts-pro' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification -- Already verified above.
        $raw_json    = isset( $_POST['json_data'] ) ? wp_unslash( $_POST['json_data'] ) : '';
        $import_mode = isset( $_POST['import_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['import_mode'] ) ) : 'append';

        if ( empty( $raw_json ) ) {
            wp_send_json_error( [ 'message' => __( 'No JSON data provided.', 'wp-datacharts-pro' ) ] );
        }

        $result = $this->import( (string) $raw_json, $import_mode );

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( [ 'message' => $result['error'], 'details' => $result['details'] ?? [] ] );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX handler: delete miner records.
     *
     * Expects POST fields:
     *   nonce – wp_nonce value
     *   ids   – comma-separated list of record IDs or 'all'
     */
    public function handleDelete(): void {
        if ( ! check_ajax_referer( 'wpdcp_miners_import', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'wp-datacharts-pro' ) ], 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to delete data.', 'wp-datacharts-pro' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification -- Already verified above.
        $raw_ids = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';

        if ( $raw_ids === 'all' ) {
            $this->db->truncate();
            wp_send_json_success( [ 'message' => __( 'All records deleted.', 'wp-datacharts-pro' ) ] );
        }

        $ids     = array_filter( array_map( 'intval', explode( ',', $raw_ids ) ) );
        $deleted = $this->db->deleteByIds( $ids );

        wp_send_json_success( [
            'message' => sprintf(
                /* translators: %d: number of deleted records */
                __( '%d record(s) deleted.', 'wp-datacharts-pro' ),
                $deleted
            ),
            'deleted' => $deleted,
        ] );
    }

    /**
     * Parse, validate, and store JSON miner data.
     *
     * @param string $raw_json    Raw JSON string.
     * @param string $import_mode 'overwrite' | 'append'.
     * @return array<string, mixed> Result array containing 'imported', 'skipped', 'errors', or 'error'.
     */
    public function import( string $raw_json, string $import_mode = 'append' ): array {
        // Decode JSON.
        $decoded = json_decode( $raw_json, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return [
                'error'   => __( 'Invalid JSON format.', 'wp-datacharts-pro' ),
                'details' => [ json_last_error_msg() ],
            ];
        }

        if ( ! is_array( $decoded ) ) {
            return [ 'error' => __( 'JSON must be an array of miner objects.', 'wp-datacharts-pro' ) ];
        }

        // If the JSON is an associative object (single item), wrap it.
        if ( ! isset( $decoded[0] ) && ! empty( $decoded ) ) {
            $decoded = [ $decoded ];
        }

        if ( empty( $decoded ) ) {
            return [ 'error' => __( 'JSON array is empty.', 'wp-datacharts-pro' ) ];
        }

        // Validate each record.
        $valid   = [];
        $errors  = [];
        $skipped = 0;

        foreach ( $decoded as $index => $item ) {
            if ( ! is_array( $item ) ) {
                $errors[] = sprintf(
                    /* translators: %d: zero-based index of invalid item */
                    __( 'Item #%d is not an object.', 'wp-datacharts-pro' ),
                    $index
                );
                ++$skipped;
                continue;
            }

            $missing = $this->getMissingFields( $item );
            if ( $missing ) {
                $errors[] = sprintf(
                    /* translators: 1: item index, 2: list of missing fields */
                    __( 'Item #%1$d missing required fields: %2$s.', 'wp-datacharts-pro' ),
                    $index,
                    implode( ', ', $missing )
                );
                ++$skipped;
                continue;
            }

            $valid[] = $item;
        }

        if ( empty( $valid ) ) {
            return [
                'error'   => __( 'No valid records to import.', 'wp-datacharts-pro' ),
                'details' => $errors,
            ];
        }

        // Overwrite mode: truncate existing data first.
        if ( $import_mode === 'overwrite' ) {
            $this->db->truncate();
        }

        $inserted = $this->db->insertBatch( $valid );

        // Record import history in options.
        $this->logImport( $inserted, $import_mode );

        return [
            'imported' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'total'    => $this->db->count(),
            'message'  => sprintf(
                /* translators: 1: imported count, 2: skipped count */
                __( 'Successfully imported %1$d record(s). Skipped: %2$d.', 'wp-datacharts-pro' ),
                $inserted,
                $skipped
            ),
        ];
    }

    /**
     * Return list of missing required fields for a record.
     *
     * @param array<string, mixed> $item Single miner record.
     * @return string[] Missing field names.
     */
    private function getMissingFields( array $item ): array {
        $missing = [];
        foreach ( self::REQUIRED_FIELDS as $field ) {
            if ( ! array_key_exists( $field, $item ) || $item[ $field ] === null || $item[ $field ] === '' ) {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    /**
     * Store import history in a WordPress option.
     *
     * @param int    $count       Number of records imported.
     * @param string $import_mode Import mode used.
     */
    private function logImport( int $count, string $import_mode ): void {
        $history   = get_option( 'wpdcp_miners_import_history', [] );
        $history[] = [
            'imported_at' => current_time( 'mysql' ),
            'count'       => $count,
            'mode'        => $import_mode,
            'user_id'     => get_current_user_id(),
        ];

        // Keep only the last 20 entries.
        if ( count( $history ) > 20 ) {
            $history = array_slice( $history, -20 );
        }

        update_option( 'wpdcp_miners_import_history', $history );
    }

    /**
     * Return stored import history.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getImportHistory(): array {
        return (array) get_option( 'wpdcp_miners_import_history', [] );
    }
}
