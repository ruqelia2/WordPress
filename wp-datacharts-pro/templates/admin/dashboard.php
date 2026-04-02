<?php
/**
 * Admin Template: Dashboard
 *
 * @package WP_DataCharts_Pro
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Database\ChartRepository;
use WPDCP\Database\DataSourceRepository;

$chart_repo  = new ChartRepository();
$ds_repo     = new DataSourceRepository();
$total_charts  = $chart_repo->count();
$total_sources = $ds_repo->count();
?>
<div class="wrap wpdcp-admin-dashboard">
    <h1><?php echo esc_html__( 'WP DataCharts Pro Dashboard', 'wp-datacharts-pro' ); ?></h1>

    <div class="wpdcp-stats-grid">
        <div class="wpdcp-stat-card">
            <span class="wpdcp-stat-number"><?php echo esc_html( number_format_i18n( $total_charts ) ); ?></span>
            <span class="wpdcp-stat-label"><?php echo esc_html__( 'Total Charts', 'wp-datacharts-pro' ); ?></span>
        </div>

        <div class="wpdcp-stat-card">
            <span class="wpdcp-stat-number"><?php echo esc_html( number_format_i18n( $total_sources ) ); ?></span>
            <span class="wpdcp-stat-label"><?php echo esc_html__( 'Total Data Sources', 'wp-datacharts-pro' ); ?></span>
        </div>
    </div>

    <div class="wpdcp-quick-actions">
        <h2><?php echo esc_html__( 'Quick Actions', 'wp-datacharts-pro' ); ?></h2>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpdcp-add-chart' ) ); ?>" class="button button-primary">
            <?php echo esc_html__( 'Add New Chart', 'wp-datacharts-pro' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpdcp-data-sources' ) ); ?>" class="button">
            <?php echo esc_html__( 'Add Data Source', 'wp-datacharts-pro' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpdcp-settings' ) ); ?>" class="button">
            <?php echo esc_html__( 'Settings', 'wp-datacharts-pro' ); ?>
        </a>
    </div>
</div>
