<?php
/**
 * Uninstall — remove all plugin data from the database.
 *
 * This file is called automatically by WordPress when the user
 * deletes the plugin via the Plugins screen.
 *
 * @package MinersDataImporter
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop the custom table.
$table = $wpdb->prefix . 'miners_data';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Remove plugin options.
delete_option( 'mdi_db_version' );
