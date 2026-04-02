<?php
/**
 * Cache Strategy Interface
 *
 * @package WPDCP\Cache
 */

declare(strict_types=1);

namespace WPDCP\Cache;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface CacheStrategy
 *
 * Defines the contract for cache drivers used by the plugin.
 */
interface CacheStrategy {

    /**
     * Retrieve a value from the cache.
     *
     * @param string $key Cache key.
     * @return mixed Cached value, or null/false if not found.
     */
    public function get( string $key ): mixed;

    /**
     * Store a value in the cache.
     *
     * @param string $key   Cache key.
     * @param mixed  $value Value to cache.
     * @param int    $ttl   Time-to-live in seconds.
     */
    public function set( string $key, mixed $value, int $ttl ): void;

    /**
     * Delete a value from the cache.
     *
     * @param string $key Cache key.
     */
    public function delete( string $key ): void;

    /**
     * Flush all entries managed by this strategy.
     */
    public function flush(): void;
}
