<?php
/**
 * Plugin Activator — creates/upgrades the custom database table.
 *
 * @package MinersDataImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Miners_Activator
 */
class Miners_Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		self::create_table();
		add_option( 'mdi_db_version', MDI_VERSION );
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate() {
		// Nothing to do on deactivation.
	}

	/**
	 * Create (or upgrade) the miners_data table using dbDelta.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'miners_data';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			model         VARCHAR(255)        NOT NULL DEFAULT '',
			brand         VARCHAR(100)        NOT NULL DEFAULT '',
			top_coin      VARCHAR(100)        NOT NULL DEFAULT '',
			all_coins     TEXT                NOT NULL DEFAULT '',
			algorithm     VARCHAR(100)        NOT NULL DEFAULT '',
			power_w       INT(11)             NOT NULL DEFAULT 0,
			hashrate      VARCHAR(50)         NOT NULL DEFAULT '',
			price_usd     VARCHAR(50)         NOT NULL DEFAULT '',
			price_val     DECIMAL(15,4)       NOT NULL DEFAULT 0.0000,
			roi           VARCHAR(50)         NOT NULL DEFAULT '',
			daily_income_usd  VARCHAR(50)     NOT NULL DEFAULT '',
			daily_income_val  DECIMAL(15,4)   NOT NULL DEFAULT 0.0000,
			value         DECIMAL(15,4)       NOT NULL DEFAULT 0.0000,
			category      VARCHAR(100)        NOT NULL DEFAULT '',
			value_class   VARCHAR(100)        NOT NULL DEFAULT '',
			value_na      TINYINT(1)          NOT NULL DEFAULT 0,
			permalink     VARCHAR(500)        NOT NULL DEFAULT '',
			imported_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   model (model(191))
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
