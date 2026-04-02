<?php
/**
 * Custom PSR-4 Autoloader for the WPDCP namespace.
 *
 * Maps the WPDCP\ namespace prefix to this directory so that, for example,
 * WPDCP\Core\Plugin resolves to includes/Core/Plugin.php.
 *
 * This autoloader does not depend on Composer and is always available when
 * the plugin is installed from a ZIP file without running `composer install`.
 *
 * @package WPDCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register( function ( $class ) {
	$prefix   = 'WPDCP\\';
	$base_dir = __DIR__ . '/';

	$len = strlen( $prefix );
	if ( strncmp( $class, $prefix, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );
