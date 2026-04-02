<?php
/**
 * Data Source Repository — CRUD operations for the wpdcp_data_sources table
 *
 * @package WPDCP\Database
 */

declare(strict_types=1);

namespace WPDCP\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DataSourceRepository
 *
 * Provides create, read, update, delete, and count operations
 * for data source records. All queries use $wpdb->prepare().
 */
class DataSourceRepository {

    /** @var string Fully-qualified table name. */
    private string $table;

    public function __construct() {
        $this->table = Schema::getTableName( 'data_sources' );
    }

    /**
     * Find a single data source by its ID.
     *
     * @param int $id Data source ID.
     * @return object|null Row as stdClass, or null if not found.
     */
    public function find( int $id ): ?object {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT * FROM `{$this->table}` WHERE id = %d LIMIT 1",
                $id
            )
        );

        return $row instanceof \stdClass ? $row : null;
    }

    /**
     * Find all data sources matching the given arguments.
     *
     * @param array<string, mixed> $args {
     *     Optional. Query arguments.
     *
     *     @type int    $page     Page number (1-based). Default 1.
     *     @type int    $per_page Records per page. Default 20.
     *     @type string $orderby  Column to order by. Default 'created_at'.
     *     @type string $order    ASC or DESC. Default 'DESC'.
     *     @type string $type     Filter by data source type.
     *     @type string $status   Filter by status.
     *     @type string $search   Search term for name.
     * }
     * @return object[] Array of stdClass objects.
     */
    public function findAll( array $args = [] ): array {
        global $wpdb;

        $page     = max( 1, (int) ( $args['page'] ?? 1 ) );
        $per_page = max( 1, min( 100, (int) ( $args['per_page'] ?? 20 ) ) );
        $offset   = ( $page - 1 ) * $per_page;

        $allowed_orderby = [ 'id', 'name', 'type', 'status', 'last_synced_at', 'created_at', 'updated_at' ];
        $orderby         = in_array( $args['orderby'] ?? 'created_at', $allowed_orderby, true )
            ? ( $args['orderby'] ?? 'created_at' )
            : 'created_at';

        $order = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

        $where        = [];
        $placeholders = [];

        if ( ! empty( $args['type'] ) ) {
            $where[]        = 'type = %s';
            $placeholders[] = sanitize_text_field( $args['type'] );
        }

        if ( ! empty( $args['status'] ) ) {
            $where[]        = 'status = %s';
            $placeholders[] = sanitize_text_field( $args['status'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $where[]        = 'name LIKE %s';
            $placeholders[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT * FROM `{$this->table}` {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        $final_placeholders = array_merge( $placeholders, [ $per_page, $offset ] );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->prepare( $sql, ...$final_placeholders )
        );

        return is_array( $results ) ? $results : [];
    }

    /**
     * Insert a new data source record.
     *
     * @param array<string, mixed> $data Column => value pairs.
     * @return int|false New record ID, or false on failure.
     */
    public function create( array $data ): int|false {
        global $wpdb;

        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->insert( $this->table, $data );

        if ( false === $result ) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing data source record.
     *
     * @param int                  $id   Data source ID.
     * @param array<string, mixed> $data Column => value pairs to update.
     * @return bool True on success, false on failure.
     */
    public function update( int $id, array $data ): bool {
        global $wpdb;

        $data['updated_at'] = current_time( 'mysql' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->update( $this->table, $data, [ 'id' => $id ] );

        return false !== $result;
    }

    /**
     * Delete a data source record by ID.
     *
     * @param int $id Data source ID.
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );

        return false !== $result;
    }

    /**
     * Count data sources matching the given arguments.
     *
     * @param array<string, mixed> $args Same filter keys as findAll(), without pagination.
     * @return int Number of matching records.
     */
    public function count( array $args = [] ): int {
        global $wpdb;

        $where        = [];
        $placeholders = [];

        if ( ! empty( $args['type'] ) ) {
            $where[]        = 'type = %s';
            $placeholders[] = sanitize_text_field( $args['type'] );
        }

        if ( ! empty( $args['status'] ) ) {
            $where[]        = 'status = %s';
            $placeholders[] = sanitize_text_field( $args['status'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $where[]        = 'name LIKE %s';
            $placeholders[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT COUNT(*) FROM `{$this->table}` {$where_sql}";

        if ( $placeholders ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $count = $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->prepare( $sql, ...$placeholders )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
            $count = $wpdb->get_var( $sql );
        }

        return (int) $count;
    }
}
