<?php
/**
 * Plugin Name:       WP DataCharts Pro
 * Plugin URI:        https://github.com/ruqelia2/wp-datacharts-pro
 * Description:       强大的 WordPress 图表可视化插件，支持 20+ 种图表类型、多数据源、多渲染引擎。
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            ruqelia2
 * Author URI:        https://github.com/ruqelia2
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-datacharts-pro
 * Domain Path:       /languages
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPDCP_VERSION', '1.0.0' );
define( 'WPDCP_PLUGIN_FILE', __FILE__ );
define( 'WPDCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPDCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPDCP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPDCP_DB_VERSION', '1.0.0' );

// Always load the custom PSR-4 autoloader (works without Composer).
require_once WPDCP_PLUGIN_DIR . 'includes/autoload.php';

// Optionally load Composer autoloader if available (for dev dependencies).
if ( file_exists( WPDCP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once WPDCP_PLUGIN_DIR . 'vendor/autoload.php';
}

register_activation_hook( __FILE__, [ \WPDCP\Core\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \WPDCP\Core\Deactivator::class, 'deactivate' ] );

add_action( 'plugins_loaded', static function (): void {
    \WPDCP\Core\Plugin::getInstance()->init();
} );
