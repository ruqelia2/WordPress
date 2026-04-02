<?php
/**
 * Database Migrator
 *
 * @package WPDCP\Database
 */

declare(strict_types=1);

namespace WPDCP\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Migrator
 *
 * Compares the stored DB version with the current plugin DB version and runs
 * Schema::createTables() when an upgrade is needed.
 */
class Migrator {

    /**
     * Run pending migrations if the stored DB version differs from the current one.
     */
    public function run(): void {
        if ( ! $this->needsMigration() ) {
            return;
        }

        Schema::createTables();
        update_option( 'wpdcp_db_version', WPDCP_DB_VERSION );
    }

    /**
     * Determine whether the database needs to be migrated.
     *
     * @return bool True when the stored version differs from the current plugin DB version.
     */
    public function needsMigration(): bool {
        $stored = get_option( 'wpdcp_db_version', '0.0.0' );
        return version_compare( (string) $stored, WPDCP_DB_VERSION, '<' );
    }
}
