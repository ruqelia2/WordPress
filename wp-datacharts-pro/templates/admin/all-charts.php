<?php
/**
 * Admin Template: All Charts
 *
 * @package WP_DataCharts_Pro
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Database\ChartRepository;

$repo   = new ChartRepository();
$charts = $repo->findAll( [ 'per_page' => 20, 'page' => max( 1, (int) ( $_GET['paged'] ?? 1 ) ) ] ); // phpcs:ignore WordPress.Security.NonceVerification
$total  = $repo->count();
?>
<div class="wrap wpdcp-admin-charts">
    <h1 class="wp-heading-inline"><?php echo esc_html__( 'All Charts', 'wp-datacharts-pro' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpdcp-add-chart' ) ); ?>" class="page-title-action">
        <?php echo esc_html__( 'Add New', 'wp-datacharts-pro' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ( empty( $charts ) ) : ?>
        <p><?php echo esc_html__( 'No charts found. Create your first chart!', 'wp-datacharts-pro' ); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Title', 'wp-datacharts-pro' ); ?></th>
                    <th><?php echo esc_html__( 'Type', 'wp-datacharts-pro' ); ?></th>
                    <th><?php echo esc_html__( 'Engine', 'wp-datacharts-pro' ); ?></th>
                    <th><?php echo esc_html__( 'Status', 'wp-datacharts-pro' ); ?></th>
                    <th><?php echo esc_html__( 'Created', 'wp-datacharts-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $charts as $chart ) : ?>
                    <tr>
                        <td><?php echo esc_html( $chart->title ); ?></td>
                        <td><?php echo esc_html( $chart->chart_type ); ?></td>
                        <td><?php echo esc_html( $chart->engine ); ?></td>
                        <td><?php echo esc_html( $chart->status ); ?></td>
                        <td><?php echo esc_html( $chart->created_at ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p class="description">
        <?php
        printf(
            /* translators: %d: total number of charts */
            esc_html__( 'Total: %d chart(s)', 'wp-datacharts-pro' ),
            (int) $total
        );
        ?>
    </p>
</div>
