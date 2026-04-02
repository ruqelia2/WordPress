<?php
/**
 * Miners list page template.
 *
 * @package MinersDataImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Unique filter values for dropdowns.
$table      = $wpdb->prefix . 'miners_data';
$categories = $wpdb->get_col( "SELECT DISTINCT category FROM {$table} WHERE category != '' ORDER BY category" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$coins      = $wpdb->get_col( "SELECT DISTINCT top_coin FROM {$table} WHERE top_coin != '' ORDER BY top_coin" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$algorithms = $wpdb->get_col( "SELECT DISTINCT algorithm FROM {$table} WHERE algorithm != '' ORDER BY algorithm" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$list_table = new Miners_List_Table();
$list_table->prepare_items();

$filter_category  = isset( $_REQUEST['filter_category'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$filter_coin      = isset( $_REQUEST['filter_coin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_coin'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$filter_algorithm = isset( $_REQUEST['filter_algorithm'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_algorithm'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="wrap mdi-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'All Miners', 'miners-data-importer' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=miners-import' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Import JSON', 'miners-data-importer' ); ?>
	</a>
	<hr class="wp-header-end" />

	<?php settings_errors( 'mdi_messages' ); ?>

	<form method="get" class="mdi-filter-form">
		<input type="hidden" name="page" value="miners-list" />

		<!-- Search -->
		<?php $list_table->search_box( __( 'Search Miners', 'miners-data-importer' ), 'mdi-search' ); ?>

		<!-- Filters -->
		<div class="mdi-list-filters">
			<select name="filter_category">
				<option value=""><?php esc_html_e( 'All Categories', 'miners-data-importer' ); ?></option>
				<?php foreach ( $categories as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_category, $cat ); ?>>
						<?php echo esc_html( $cat ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="filter_coin">
				<option value=""><?php esc_html_e( 'All Coins', 'miners-data-importer' ); ?></option>
				<?php foreach ( $coins as $coin ) : ?>
					<option value="<?php echo esc_attr( $coin ); ?>" <?php selected( $filter_coin, $coin ); ?>>
						<?php echo esc_html( $coin ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="filter_algorithm">
				<option value=""><?php esc_html_e( 'All Algorithms', 'miners-data-importer' ); ?></option>
				<?php foreach ( $algorithms as $alg ) : ?>
					<option value="<?php echo esc_attr( $alg ); ?>" <?php selected( $filter_algorithm, $alg ); ?>>
						<?php echo esc_html( $alg ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'miners-data-importer' ); ?></button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=miners-list' ) ); ?>" class="button">
				<?php esc_html_e( 'Reset', 'miners-data-importer' ); ?>
			</a>
		</div>
	</form>

	<!-- Bulk actions form -->
	<form method="post">
		<?php wp_nonce_field( 'bulk-miners' ); ?>
		<input type="hidden" name="page" value="miners-list" />
		<?php
		$list_table->display();
		?>
	</form>
</div>
