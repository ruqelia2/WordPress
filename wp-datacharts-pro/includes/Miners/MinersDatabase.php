<?php
/**
 * Miners Database Operations
 *
 * @package WPDCP\Miners
 */

declare(strict_types=1);

namespace WPDCP\Miners;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MinersDatabase
 *
 * Provides CRUD operations for the wp_miners_data custom table.
 */
class MinersDatabase {

    /** @var string Full table name including WordPress prefix. */
    private string $table;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'miners_data';
    }

    /**
     * Create the miners_data table if it does not already exist.
     */
    public static function createTable(): void {
        global $wpdb;

        $table           = $wpdb->prefix . 'miners_data';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            model VARCHAR(255) NOT NULL DEFAULT '',
            brand VARCHAR(255) NOT NULL DEFAULT '',
            top_coin VARCHAR(100) NOT NULL DEFAULT '',
            all_coins TEXT NULL DEFAULT NULL,
            algorithm VARCHAR(100) NOT NULL DEFAULT '',
            power_w INT NOT NULL DEFAULT 0,
            hashrate VARCHAR(100) NOT NULL DEFAULT '',
            price_usd VARCHAR(50) NULL DEFAULT NULL,
            price_val DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            roi VARCHAR(50) NULL DEFAULT NULL,
            daily_income_usd VARCHAR(100) NULL DEFAULT NULL,
            daily_income_val DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
            value_score DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
            category VARCHAR(100) NULL DEFAULT NULL,
            value_class VARCHAR(50) NULL DEFAULT NULL,
            value_na TINYINT(1) NOT NULL DEFAULT 0,
            permalink VARCHAR(500) NULL DEFAULT NULL,
            imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_brand (brand),
            KEY idx_top_coin (top_coin),
            KEY idx_algorithm (algorithm),
            KEY idx_category (category),
            KEY idx_imported_at (imported_at)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Drop the miners_data table.
     */
    public static function dropTable(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'miners_data';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be parameterized.
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
    }

    /**
     * Count all miner records.
     *
     * @return int
     */
    public function count(): int {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table}`" );
    }

    /**
     * Delete all miner records (truncate).
     *
     * @return bool True on success.
     */
    public function truncate(): bool {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return false !== $wpdb->query( "TRUNCATE TABLE `{$this->table}`" );
    }

    /**
     * Insert a batch of miner records.
     *
     * @param array<int, array<string, mixed>> $miners Array of miner data arrays.
     * @return int Number of successfully inserted records.
     */
    public function insertBatch( array $miners ): int {
        global $wpdb;
        $inserted = 0;

        foreach ( $miners as $miner ) {
            $data = [
                'model'            => sanitize_text_field( (string) ( $miner['model'] ?? '' ) ),
                'brand'            => sanitize_text_field( (string) ( $miner['brand'] ?? '' ) ),
                'top_coin'         => sanitize_text_field( (string) ( $miner['top_coin'] ?? '' ) ),
                'all_coins'        => sanitize_text_field( (string) ( $miner['all_coins'] ?? '' ) ),
                'algorithm'        => sanitize_text_field( (string) ( $miner['algorithm'] ?? '' ) ),
                'power_w'          => (int) ( $miner['power_w'] ?? 0 ),
                'hashrate'         => sanitize_text_field( (string) ( $miner['hashrate'] ?? '' ) ),
                'price_usd'        => sanitize_text_field( (string) ( $miner['price_usd'] ?? '' ) ),
                'price_val'        => (float) ( $miner['price_val'] ?? 0 ),
                'roi'              => sanitize_text_field( (string) ( $miner['roi'] ?? '' ) ),
                'daily_income_usd' => sanitize_text_field( (string) ( $miner['daily_income_usd'] ?? '' ) ),
                'daily_income_val' => (float) ( $miner['daily_income_val'] ?? 0 ),
                'value_score'      => (float) ( $miner['value'] ?? 0 ),
                'category'         => sanitize_text_field( (string) ( $miner['category'] ?? '' ) ),
                'value_class'      => sanitize_text_field( (string) ( $miner['value_class'] ?? '' ) ),
                'value_na'         => (int) ( $miner['value_na'] ?? 0 ),
                'permalink'        => esc_url_raw( (string) ( $miner['permalink'] ?? '' ) ),
                'imported_at'      => current_time( 'mysql' ),
            ];

            $formats = [
                '%s', '%s', '%s', '%s', '%s',
                '%d', '%s', '%s', '%f', '%s',
                '%s', '%f', '%f', '%s', '%s',
                '%d', '%s', '%s',
            ];

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $result = $wpdb->insert( $this->table, $data, $formats );
            if ( false !== $result ) {
                ++$inserted;
            }
        }

        return $inserted;
    }

    /**
     * Get a paginated list of miners with optional filtering and searching.
     *
     * @param array<string, mixed> $args {
     *     @type int    $per_page  Rows per page. Default 20.
     *     @type int    $page      Page number (1-based). Default 1.
     *     @type string $orderby   Column to sort by. Default 'id'.
     *     @type string $order     'ASC' or 'DESC'. Default 'DESC'.
     *     @type string $search    Search term for model or brand.
     *     @type string $brand     Filter by brand.
     *     @type string $top_coin  Filter by top coin.
     *     @type string $algorithm Filter by algorithm.
     *     @type string $category  Filter by category.
     * }
     * @return array{items: array<int, object>, total: int}
     */
    public function getMiners( array $args = [] ): array {
        global $wpdb;

        $per_page = max( 1, (int) ( $args['per_page'] ?? 20 ) );
        $page     = max( 1, (int) ( $args['page'] ?? 1 ) );
        $offset   = ( $page - 1 ) * $per_page;

        $allowed_columns = [
            'id', 'model', 'brand', 'top_coin', 'algorithm',
            'power_w', 'price_val', 'daily_income_val', 'value_score',
            'category', 'imported_at',
        ];
        $orderby = in_array( $args['orderby'] ?? 'id', $allowed_columns, true )
            ? ( $args['orderby'] ?? 'id' )
            : 'id';
        $order   = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

        $where  = [];
        $params = [];

        if ( ! empty( $args['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where[]  = '(model LIKE %s OR brand LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        if ( ! empty( $args['brand'] ) ) {
            $where[]  = 'brand = %s';
            $params[] = sanitize_text_field( $args['brand'] );
        }

        if ( ! empty( $args['top_coin'] ) ) {
            $where[]  = 'top_coin = %s';
            $params[] = sanitize_text_field( $args['top_coin'] );
        }

        if ( ! empty( $args['algorithm'] ) ) {
            $where[]  = 'algorithm = %s';
            $params[] = sanitize_text_field( $args['algorithm'] );
        }

        if ( ! empty( $args['category'] ) ) {
            $where[]  = 'category = %s';
            $params[] = sanitize_text_field( $args['category'] );
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
        if ( $params ) {
            $count_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$this->table}` {$where_sql}",
                ...$params
            );
            $total = (int) $wpdb->get_var( $count_query );

            $data_query = $wpdb->prepare(
                "SELECT * FROM `{$this->table}` {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                ...array_merge( $params, [ $per_page, $offset ] )
            );
            $items = $wpdb->get_results( $data_query );
        } else {
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table}`" );
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$this->table}` ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                )
            );
        }
        // phpcs:enable

        return [
            'items' => $items ?: [],
            'total' => $total,
        ];
    }

    /**
     * Get distinct values for a given column (for filter dropdowns).
     *
     * @param string $column Column name.
     * @return string[] Distinct values.
     */
    public function getDistinctValues( string $column ): array {
        global $wpdb;

        $allowed = [ 'brand', 'top_coin', 'algorithm', 'category' ];
        if ( ! in_array( $column, $allowed, true ) ) {
            return [];
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_col(
            "SELECT DISTINCT `{$column}` FROM `{$this->table}` WHERE `{$column}` != '' ORDER BY `{$column}` ASC"
        );

        return $results ?: [];
    }

    /**
     * Delete miners by their IDs.
     *
     * @param int[] $ids Array of record IDs to delete.
     * @return int Number of deleted rows.
     */
    public function deleteByIds( array $ids ): int {
        global $wpdb;

        if ( empty( $ids ) ) {
            return 0;
        }

        $ids          = array_map( 'intval', $ids );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$this->table}` WHERE id IN ({$placeholders})",
                ...$ids
            )
        );
    }
}
