<?php
/**
 * Chart Repository — CRUD operations for the wpdcp_charts table
 *
 * @package WPDCP\Database
 */

declare(strict_types=1);

namespace WPDCP\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ChartRepository
 *
 * Provides create, read, update, delete, duplicate, and count operations
 * for chart records. All queries use $wpdb->prepare().
 */
class ChartRepository {

    /** @var string Fully-qualified table name. */
    private string $table;

    public function __construct() {
        $this->table = Schema::getTableName( 'charts' );
    }

    /**
     * Find a single chart by its ID.
     *
     * @param int $id Chart ID.
     * @return object|null Chart row as a stdClass object, or null if not found.
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
     * Find all charts matching the given arguments.
     *
     * @param array<string, mixed> $args {
     *     Optional. Query arguments.
     *
     *     @type int    $page      Page number (1-based). Default 1.
     *     @type int    $per_page  Records per page. Default 20.
     *     @type string $orderby   Column to order by. Default 'created_at'.
     *     @type string $order     ASC or DESC. Default 'DESC'.
     *     @type string $status    Filter by status.
     *     @type string $chart_type Filter by chart type.
     *     @type string $engine    Filter by rendering engine.
     *     @type string $search    Search term for title.
     * }
     * @return object[] Array of stdClass objects.
     */
    public function findAll( array $args = [] ): array {
        global $wpdb;

        $page     = max( 1, (int) ( $args['page'] ?? 1 ) );
        $per_page = max( 1, min( 100, (int) ( $args['per_page'] ?? 20 ) ) );
        $offset   = ( $page - 1 ) * $per_page;

        $allowed_orderby = [ 'id', 'title', 'chart_type', 'engine', 'status', 'created_at', 'updated_at' ];
        $orderby         = in_array( $args['orderby'] ?? 'created_at', $allowed_orderby, true )
            ? ( $args['orderby'] ?? 'created_at' )
            : 'created_at';

        $order = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

        $where        = [];
        $placeholders = [];

        if ( ! empty( $args['status'] ) ) {
            $where[]        = 'status = %s';
            $placeholders[] = sanitize_text_field( $args['status'] );
        }

        if ( ! empty( $args['chart_type'] ) ) {
            $where[]        = 'chart_type = %s';
            $placeholders[] = sanitize_text_field( $args['chart_type'] );
        }

        if ( ! empty( $args['engine'] ) ) {
            $where[]        = 'engine = %s';
            $placeholders[] = sanitize_text_field( $args['engine'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $where[]        = 'title LIKE %s';
            $placeholders[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
        $sql = "SELECT * FROM `{$this->table}` {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        $final_placeholders = array_merge( $placeholders, [ $per_page, $offset ] );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            $wpdb->prepare( $sql, ...$final_placeholders )
        );
        // phpcs:enable

        return is_array( $results ) ? $results : [];
    }

    /**
     * Insert a new chart record.
     *
     * @param array<string, mixed> $data Column => value pairs.
     * @return int|false The new record ID, or false on failure.
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
     * Update an existing chart record.
     *
     * @param int                  $id   Chart ID.
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
     * Delete a chart record by ID.
     *
     * @param int $id Chart ID.
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );

        return false !== $result;
    }

    /**
     * Duplicate a chart by ID.
     *
     * @param int $id Source chart ID.
     * @return int|false New chart ID, or false on failure.
     */
    public function duplicate( int $id ): int|false {
        $original = $this->find( $id );

        if ( null === $original ) {
            return false;
        }

        $data = (array) $original;
        unset( $data['id'], $data['created_at'], $data['updated_at'] );

        $data['title']  = $data['title'] . __( ' (Copy)', 'wp-datacharts-pro' );
        $data['slug']   = $data['slug'] . '-copy-' . time();
        $data['status'] = 'draft';

        return $this->create( $data );
    }

    /**
     * Count charts matching the given arguments.
     *
     * @param array<string, mixed> $args Same filter keys as findAll(), without pagination.
     * @return int Number of matching records.
     */
    public function count( array $args = [] ): int {
        global $wpdb;

        $where        = [];
        $placeholders = [];

        if ( ! empty( $args['status'] ) ) {
            $where[]        = 'status = %s';
            $placeholders[] = sanitize_text_field( $args['status'] );
        }

        if ( ! empty( $args['chart_type'] ) ) {
            $where[]        = 'chart_type = %s';
            $placeholders[] = sanitize_text_field( $args['chart_type'] );
        }

        if ( ! empty( $args['engine'] ) ) {
            $where[]        = 'engine = %s';
            $placeholders[] = sanitize_text_field( $args['engine'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $where[]        = 'title LIKE %s';
            $placeholders[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
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
