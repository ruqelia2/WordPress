<?php
/**
 * Plugin Name:       Miners Data Importer
 * Plugin URI:        https://github.com/ruqelia2/WordPress
 * Description:       Import cryptocurrency miner data from JSON files into WordPress admin.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ruqelia2
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       miners-data-importer
 * Domain Path:       /languages
 *
 * @package MinersDataImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MDI_VERSION', '1.0.0' );
define( 'MDI_PLUGIN_FILE', __FILE__ );
define( 'MDI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MDI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MDI_PLUGIN_DIR . 'includes/class-miners-activator.php';
require_once MDI_PLUGIN_DIR . 'includes/class-miners-importer.php';
require_once MDI_PLUGIN_DIR . 'includes/class-miners-list-table.php';
require_once MDI_PLUGIN_DIR . 'includes/class-miners-admin.php';

register_activation_hook( __FILE__, array( 'Miners_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Miners_Activator', 'deactivate' ) );

/**
 * Initialize the plugin.
 */
function mdi_init() {
	$admin = new Miners_Admin();
	$admin->init();
}
add_action( 'plugins_loaded', 'mdi_init' );
