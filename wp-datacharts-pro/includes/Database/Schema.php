<?php
/**
 * Database Schema Manager
 *
 * @package WPDCP\Database
 */

declare(strict_types=1);

namespace WPDCP\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Schema
 *
 * Creates and drops custom database tables for the plugin.
 */
class Schema {

    /**
     * Create or upgrade all custom plugin tables using dbDelta().
     */
    public static function createTables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = self::getTableSchemata( $charset_collate );

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }

    /**
     * Drop all custom plugin tables.
     */
    public static function dropTables(): void {
        global $wpdb;

        $tables = [
            self::getTableName( 'analytics' ),
            self::getTableName( 'templates' ),
            self::getTableName( 'charts' ),
            self::getTableName( 'data_sources' ),
        ];

        foreach ( $tables as $table ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be parameterized.
            $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        }
    }

    /**
     * Get the full table name for a given table key.
     *
     * @param string $table Short table name without prefix (e.g. 'charts').
     * @return string Full table name including WordPress prefix.
     */
    public static function getTableName( string $table ): string {
        global $wpdb;
        return $wpdb->prefix . 'wpdcp_' . $table;
    }

    /**
     * Return an array of CREATE TABLE SQL statements.
     *
     * @param string $charset_collate WordPress charset/collation string.
     * @return string[] Array of SQL statements.
     */
    private static function getTableSchemata( string $charset_collate ): array {
        $charts        = self::getTableName( 'charts' );
        $data_sources  = self::getTableName( 'data_sources' );
        $templates     = self::getTableName( 'templates' );
        $analytics     = self::getTableName( 'analytics' );

        return [
            // Charts table.
            "CREATE TABLE {$charts} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL DEFAULT '',
                slug VARCHAR(255) NOT NULL DEFAULT '',
                chart_type VARCHAR(50) NOT NULL DEFAULT 'line',
                engine VARCHAR(50) NOT NULL DEFAULT 'chartjs',
                config LONGTEXT NOT NULL DEFAULT '',
                data_source_id BIGINT UNSIGNED NULL DEFAULT NULL,
                inline_data LONGTEXT NULL DEFAULT NULL,
                style_config LONGTEXT NULL DEFAULT NULL,
                responsive TINYINT(1) NOT NULL DEFAULT 1,
                cache_enabled TINYINT(1) NOT NULL DEFAULT 1,
                cache_ttl INT NOT NULL DEFAULT 3600,
                auto_refresh TINYINT(1) NOT NULL DEFAULT 0,
                refresh_interval INT NOT NULL DEFAULT 60,
                status VARCHAR(20) NOT NULL DEFAULT 'published',
                author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_status (status),
                KEY idx_chart_type (chart_type),
                KEY idx_author_id (author_id)
            ) {$charset_collate};",

            // Data sources table.
            "CREATE TABLE {$data_sources} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL DEFAULT '',
                type VARCHAR(50) NOT NULL DEFAULT '',
                connection LONGTEXT NOT NULL DEFAULT '',
                query_config LONGTEXT NULL DEFAULT NULL,
                transform_rules LONGTEXT NULL DEFAULT NULL,
                cache_ttl INT NOT NULL DEFAULT 3600,
                last_synced_at DATETIME NULL DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_type (type),
                KEY idx_status (status),
                KEY idx_author_id (author_id)
            ) {$charset_collate};",

            // Templates table.
            "CREATE TABLE {$templates} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL DEFAULT '',
                chart_type VARCHAR(50) NOT NULL DEFAULT '',
                engine VARCHAR(50) NOT NULL DEFAULT 'chartjs',
                config_template LONGTEXT NOT NULL DEFAULT '',
                thumbnail_url VARCHAR(500) NULL DEFAULT NULL,
                is_system TINYINT(1) NOT NULL DEFAULT 0,
                category VARCHAR(100) NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_chart_type (chart_type),
                KEY idx_is_system (is_system),
                KEY idx_category (category)
            ) {$charset_collate};",

            // Analytics table.
            "CREATE TABLE {$analytics} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                chart_id BIGINT UNSIGNED NOT NULL,
                page_url VARCHAR(500) NULL DEFAULT NULL,
                views INT NOT NULL DEFAULT 0,
                interactions INT NOT NULL DEFAULT 0,
                recorded_date DATE NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_chart_date (chart_id, recorded_date),
                KEY idx_chart_id (chart_id),
                KEY idx_recorded_date (recorded_date)
            ) {$charset_collate};",
        ];
    }
}
