<?php
/**
 * Admin Template: Data Sources
 *
 * @package WP_DataCharts_Pro
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Database\DataSourceRepository;

$repo    = new DataSourceRepository();
$sources = $repo->findAll( [ 'per_page' => 20, 'page' => max( 1, (int) ( $_GET['paged'] ?? 1 ) ) ] ); // phpcs:ignore WordPress.Security.NonceVerification
$total   = $repo->count();
?>
<div class="wrap wpdcp-admin-data-sources">
    <h1 class="wp-heading-inline"><?php echo esc_html__( 'Data Sources', 'wp-datacharts-pro' ); ?></h1>
    <hr class="wp-header-end">

    <?php if ( empty( $sources ) ) : ?>
        <p><?php echo esc_html__( 'No data sources found.', 'wp-datacharts-pro' ); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Name', 'wp-datacharts-pro' ); ?></th>
                    <th><?php echo esc_html__( 'Type', 'wp-datacharts-pro' ); ?></th>
                    <th><?php echo esc_html__( 'Status', 'wp-datacharts-pro' ); ?></th>
                    <th><?php echo esc_html__( 'Last Synced', 'wp-datacharts-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $sources as $source ) : ?>
                    <tr>
                        <td><?php echo esc_html( $source->name ); ?></td>
                        <td><?php echo esc_html( $source->type ); ?></td>
                        <td><?php echo esc_html( $source->status ); ?></td>
                        <td><?php echo esc_html( $source->last_synced_at ?? __( 'Never', 'wp-datacharts-pro' ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p class="description">
        <?php
        printf(
            /* translators: %d: total number of data sources */
            esc_html__( 'Total: %d data source(s)', 'wp-datacharts-pro' ),
            (int) $total
        );
        ?>
    </p>
</div>
