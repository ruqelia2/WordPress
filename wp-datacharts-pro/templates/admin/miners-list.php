<?php
/**
 * Admin Template: Miners Data List
 *
 * @package WP_DataCharts_Pro
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Miners\MinersDatabase;
use WPDCP\Miners\MinersListTable;

$db         = new MinersDatabase();
$list_table = new MinersListTable();
$list_table->prepare_items();

// Filter dropdown values.
$brands     = $db->getDistinctValues( 'brand' );
$coins      = $db->getDistinctValues( 'top_coin' );
$algorithms = $db->getDistinctValues( 'algorithm' );
$categories = $db->getDistinctValues( 'category' );

// phpcs:disable WordPress.Security.NonceVerification
$filter_brand = sanitize_text_field( wp_unslash( $_REQUEST['filter_brand'] ?? '' ) );
$filter_coin  = sanitize_text_field( wp_unslash( $_REQUEST['filter_coin'] ?? '' ) );
$filter_algo  = sanitize_text_field( wp_unslash( $_REQUEST['filter_algo'] ?? '' ) );
$filter_cat   = sanitize_text_field( wp_unslash( $_REQUEST['filter_cat'] ?? '' ) );
// phpcs:enable
?>
<div class="wrap wpdcp-miners-list-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Miners Data', 'wp-datacharts-pro' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpdcp-miners-import' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Import JSON', 'wp-datacharts-pro' ); ?>
    </a>
    <hr class="wp-header-end">

    <form id="wpdcp-miners-filter" method="get">
        <input type="hidden" name="page" value="wpdcp-miners-list">

        <!-- Search box -->
        <?php $list_table->search_box( __( 'Search Miners', 'wp-datacharts-pro' ), 'miner' ); ?>

        <!-- Filter dropdowns -->
        <div class="wpdcp-filter-row alignleft actions">

            <select name="filter_brand">
                <option value=""><?php esc_html_e( '— All Brands —', 'wp-datacharts-pro' ); ?></option>
                <?php foreach ( $brands as $brand ) : ?>
                    <option value="<?php echo esc_attr( $brand ); ?>" <?php selected( $filter_brand, $brand ); ?>>
                        <?php echo esc_html( $brand ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_coin">
                <option value=""><?php esc_html_e( '— All Coins —', 'wp-datacharts-pro' ); ?></option>
                <?php foreach ( $coins as $coin ) : ?>
                    <option value="<?php echo esc_attr( $coin ); ?>" <?php selected( $filter_coin, $coin ); ?>>
                        <?php echo esc_html( $coin ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_algo">
                <option value=""><?php esc_html_e( '— All Algorithms —', 'wp-datacharts-pro' ); ?></option>
                <?php foreach ( $algorithms as $algo ) : ?>
                    <option value="<?php echo esc_attr( $algo ); ?>" <?php selected( $filter_algo, $algo ); ?>>
                        <?php echo esc_html( $algo ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_cat">
                <option value=""><?php esc_html_e( '— All Categories —', 'wp-datacharts-pro' ); ?></option>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_cat, $cat ); ?>>
                        <?php echo esc_html( $cat ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wp-datacharts-pro' ); ?>">

            <?php if ( $filter_brand || $filter_coin || $filter_algo || $filter_cat ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpdcp-miners-list' ) ); ?>" class="button button-link">
                    <?php esc_html_e( 'Reset', 'wp-datacharts-pro' ); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php $list_table->display(); ?>
    </form>
</div>
