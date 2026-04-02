<?php
/**
 * Cache Manager
 *
 * @package WPDCP\Cache
 */

declare(strict_types=1);

namespace WPDCP\Cache;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CacheManager
 *
 * Two-layer caching: object cache (wp_cache_*) as L1,
 * transients as L2 persistent fallback.
 */
class CacheManager {

    /** WordPress object-cache group for all plugin entries. */
    public const CACHE_GROUP = 'wpdcp';

    /** Transient key prefix. */
    public const TRANSIENT_PREFIX = 'wpdcp_';

    // -------------------------------------------------------------------------
    // Chart data
    // -------------------------------------------------------------------------

    /**
     * Retrieve cached chart data.
     *
     * @param int $chartId Chart ID.
     * @return mixed Cached data, or null on cache miss.
     */
    public function getChartData( int $chartId ): mixed {
        $key = $this->chartKey( $chartId );

        $data = wp_cache_get( $key, self::CACHE_GROUP );
        if ( false !== $data ) {
            return $data;
        }

        $data = get_transient( self::TRANSIENT_PREFIX . $key );
        if ( false !== $data ) {
            wp_cache_set( $key, $data, self::CACHE_GROUP );
            return $data;
        }

        return null;
    }

    /**
     * Store chart data in both cache layers.
     *
     * @param int   $chartId Chart ID.
     * @param mixed $data    Data to cache.
     * @param int   $ttl     Time-to-live in seconds.
     */
    public function setChartData( int $chartId, mixed $data, int $ttl = 3600 ): void {
        $key = $this->chartKey( $chartId );

        wp_cache_set( $key, $data, self::CACHE_GROUP, $ttl );
        set_transient( self::TRANSIENT_PREFIX . $key, $data, $ttl );
    }

    /**
     * Invalidate all cached data for a specific chart.
     *
     * @param int $chartId Chart ID.
     */
    public function invalidateChart( int $chartId ): void {
        $key = $this->chartKey( $chartId );

        wp_cache_delete( $key, self::CACHE_GROUP );
        delete_transient( self::TRANSIENT_PREFIX . $key );

        do_action( 'wpdcp_chart_cache_invalidated', $chartId );
    }

    // -------------------------------------------------------------------------
    // Data source data
    // -------------------------------------------------------------------------

    /**
     * Retrieve cached data source data.
     *
     * @param int $dsId Data source ID.
     * @return mixed Cached data, or null on cache miss.
     */
    public function getDataSourceData( int $dsId ): mixed {
        $key = $this->dataSourceKey( $dsId );

        $data = wp_cache_get( $key, self::CACHE_GROUP );
        if ( false !== $data ) {
            return $data;
        }

        $data = get_transient( self::TRANSIENT_PREFIX . $key );
        if ( false !== $data ) {
            wp_cache_set( $key, $data, self::CACHE_GROUP );
            return $data;
        }

        return null;
    }

    /**
     * Store data source data in both cache layers.
     *
     * @param int   $dsId Data source ID.
     * @param mixed $data Data to cache.
     * @param int   $ttl  Time-to-live in seconds.
     */
    public function setDataSourceData( int $dsId, mixed $data, int $ttl = 3600 ): void {
        $key = $this->dataSourceKey( $dsId );

        wp_cache_set( $key, $data, self::CACHE_GROUP, $ttl );
        set_transient( self::TRANSIENT_PREFIX . $key, $data, $ttl );
    }

    /**
     * Invalidate all cached data for a specific data source.
     *
     * @param int $dsId Data source ID.
     */
    public function invalidateDataSource( int $dsId ): void {
        $key = $this->dataSourceKey( $dsId );

        wp_cache_delete( $key, self::CACHE_GROUP );
        delete_transient( self::TRANSIENT_PREFIX . $key );

        do_action( 'wpdcp_data_source_cache_invalidated', $dsId );
    }

    // -------------------------------------------------------------------------
    // Global flush
    // -------------------------------------------------------------------------

    /**
     * Flush all plugin cache entries.
     *
     * Flushes the object cache group and removes all wpdcp_ transients.
     */
    public function flushAll(): void {
        wp_cache_flush_group( self::CACHE_GROUP );
        $this->deleteAllTransients();

        do_action( 'wpdcp_cache_flushed' );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a cache key for chart data.
     *
     * @param int $chartId Chart ID.
     * @return string Cache key.
     */
    private function chartKey( int $chartId ): string {
        return 'chart_' . $chartId;
    }

    /**
     * Build a cache key for data source data.
     *
     * @param int $dsId Data source ID.
     * @return string Cache key.
     */
    private function dataSourceKey( int $dsId ): string {
        return 'ds_' . $dsId;
    }

    /**
     * Delete all wpdcp_ transients from the options table.
     */
    private function deleteAllTransients(): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_wpdcp_%'
                OR option_name LIKE '_transient_timeout_wpdcp_%'"
        );
    }
}
