<?php
/**
 * Admin Template: Settings
 *
 * @package WP_DataCharts_Pro
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Admin\Settings;
?>
<div class="wrap wpdcp-admin-settings">
    <h1><?php echo esc_html__( 'WP DataCharts Pro Settings', 'wp-datacharts-pro' ); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields( Settings::SETTINGS_GROUP );
        do_settings_sections( 'wpdcp-settings' );
        submit_button( __( 'Save Settings', 'wp-datacharts-pro' ) );
        ?>
    </form>
</div>
