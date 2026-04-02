<?php
/**
 * Uninstall WP DataCharts Pro
 *
 * Cleans up all plugin data including custom tables, options, transients,
 * uploaded files, and custom capabilities.
 *
 * @package WP_DataCharts_Pro
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables.
$tables = [
    $wpdb->prefix . 'wpdcp_charts',
    $wpdb->prefix . 'wpdcp_data_sources',
    $wpdb->prefix . 'wpdcp_templates',
    $wpdb->prefix . 'wpdcp_analytics',
    $wpdb->prefix . 'miners_data',
];

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be parameterized.
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// Delete all wpdcp_ prefixed options (covers miners import history too).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wpdcp_%'"
);

// Delete all wpdcp_ prefixed transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpdcp_%' OR option_name LIKE '_transient_timeout_wpdcp_%'"
);

// Remove uploads/wpdcp/ directory.
$upload_dir = wp_upload_dir();
$wpdcp_dir  = trailingslashit( $upload_dir['basedir'] ) . 'wpdcp';

if ( is_dir( $wpdcp_dir ) ) {
    wpdcp_uninstall_delete_dir( $wpdcp_dir );
}

/**
 * Recursively delete a directory and its contents.
 *
 * @param string $dir Absolute path to the directory.
 */
function wpdcp_uninstall_delete_dir( string $dir ): void {
    if ( ! is_dir( $dir ) ) {
        return;
    }

    $items = array_diff( (array) scandir( $dir ), [ '.', '..' ] );

    foreach ( $items as $item ) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if ( is_dir( $path ) ) {
            wpdcp_uninstall_delete_dir( $path );
        } else {
            wp_delete_file( $path );
        }
    }

    rmdir( $dir );
}

// Remove custom capabilities from all roles.
$custom_caps = [
    'manage_wpdcp_charts',
    'edit_wpdcp_charts',
    'view_wpdcp_charts',
    'manage_wpdcp_data_sources',
    'manage_wpdcp_settings',
];

global $wp_roles;

if ( ! isset( $wp_roles ) ) {
    $wp_roles = new WP_Roles();
}

foreach ( $wp_roles->roles as $role_name => $role_info ) {
    $role = get_role( $role_name );
    if ( $role instanceof WP_Role ) {
        foreach ( $custom_caps as $cap ) {
            $role->remove_cap( $cap );
        }
    }
}
