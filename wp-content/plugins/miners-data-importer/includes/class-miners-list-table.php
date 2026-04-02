<?php
/**
 * WP_List_Table implementation for displaying imported miner records.
 *
 * @package MinersDataImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Miners_List_Table
 */
class Miners_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Miner', 'miners-data-importer' ),
				'plural'   => __( 'Miners', 'miners-data-importer' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define the columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'              => '<input type="checkbox" />',
			'model'           => __( 'Model', 'miners-data-importer' ),
			'brand'           => __( 'Brand', 'miners-data-importer' ),
			'top_coin'        => __( 'Top Coin', 'miners-data-importer' ),
			'algorithm'       => __( 'Algorithm', 'miners-data-importer' ),
			'hashrate'        => __( 'Hashrate', 'miners-data-importer' ),
			'power_w'         => __( 'Power (W)', 'miners-data-importer' ),
			'price_usd'       => __( 'Price', 'miners-data-importer' ),
			'daily_income_usd' => __( 'Daily Income', 'miners-data-importer' ),
			'roi'             => __( 'ROI', 'miners-data-importer' ),
			'category'        => __( 'Category', 'miners-data-importer' ),
			'imported_at'     => __( 'Imported At', 'miners-data-importer' ),
			'permalink'       => __( 'Link', 'miners-data-importer' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'model'       => array( 'model', false ),
			'brand'       => array( 'brand', false ),
			'top_coin'    => array( 'top_coin', false ),
			'algorithm'   => array( 'algorithm', false ),
			'power_w'     => array( 'power_w', false ),
			'price_val'   => array( 'price_val', false ),
			'daily_income_val' => array( 'daily_income_val', false ),
			'category'    => array( 'category', false ),
			'imported_at' => array( 'imported_at', true ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'miners-data-importer' ),
		);
	}

	/**
	 * Checkbox column.
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="miner_ids[]" value="%d" />', absint( $item['id'] ) );
	}

	/**
	 * Default column renderer.
	 *
	 * @param array  $item        Row data.
	 * @param string $column_name Column slug.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'model':
				$delete_nonce = wp_create_nonce( 'mdi_delete_miner_' . $item['id'] );
				$delete_url   = add_query_arg(
					array(
						'page'        => 'miners-list',
						'action'      => 'delete',
						'miner_id'    => $item['id'],
						'_wpnonce'    => $delete_nonce,
					),
					admin_url( 'admin.php' )
				);
				$actions      = array(
					'delete' => sprintf(
						'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
						esc_url( $delete_url ),
						esc_js( __( 'Are you sure you want to delete this miner?', 'miners-data-importer' ) ),
						__( 'Delete', 'miners-data-importer' )
					),
				);
				return sprintf(
					'<strong>%s</strong>%s',
					esc_html( $item['model'] ),
					$this->row_actions( $actions )
				);
			case 'permalink':
				if ( ! empty( $item['permalink'] ) ) {
					return sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $item['permalink'] ), esc_html__( 'View', 'miners-data-importer' ) );
				}
				return '—';
			default:
				return esc_html( $item[ $column_name ] ?? '—' );
		}
	}

	/**
	 * Prepare items: query, sort, paginate.
	 */
	public function prepare_items() {
		global $wpdb;

		$table       = $wpdb->prefix . 'miners_data';
		$per_page    = 20;
		$current_page = $this->get_pagenum();

		// Search.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		// Filters.
		$filter_category  = isset( $_REQUEST['filter_category'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$filter_coin      = isset( $_REQUEST['filter_coin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_coin'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$filter_algorithm = isset( $_REQUEST['filter_algorithm'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_algorithm'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		// Sorting.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'imported_at'; // phpcs:ignore WordPress.Security.NonceVerification
		$order   = isset( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification

		// Allowlist sortable columns.
		$allowed_orderby = array( 'model', 'brand', 'top_coin', 'algorithm', 'power_w', 'price_val', 'daily_income_val', 'category', 'imported_at' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'imported_at';
		}

		// Build WHERE clause.
		$where  = array( '1=1' );
		$values = array();

		if ( $search ) {
			$where[]  = '( model LIKE %s OR brand LIKE %s )';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$values[] = $like;
			$values[] = $like;
		}
		if ( $filter_category ) {
			$where[]  = 'category = %s';
			$values[] = $filter_category;
		}
		if ( $filter_coin ) {
			$where[]  = 'top_coin = %s';
			$values[] = $filter_coin;
		}
		if ( $filter_algorithm ) {
			$where[]  = 'algorithm = %s';
			$values[] = $filter_algorithm;
		}

		$where_sql = implode( ' AND ', $where );

		// Count.
		if ( $values ) {
			$total_items = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $values ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			);
		} else {
			$total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$offset = ( $current_page - 1 ) * $per_page;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$all_values   = array_merge( $values, array( $per_page, $offset ) );
		$this->items  = $wpdb->get_results( $wpdb->prepare( $sql, $all_values ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Message when no items exist.
	 */
	public function no_items() {
		esc_html_e( 'No miners found.', 'miners-data-importer' );
	}
}
