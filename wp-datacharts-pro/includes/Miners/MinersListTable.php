<?php
/**
 * Miners List Table
 *
 * @package WPDCP\Miners
 */

declare(strict_types=1);

namespace WPDCP\Miners;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class MinersListTable
 *
 * Displays imported miner records in a sortable, searchable, paginated table.
 */
class MinersListTable extends \WP_List_Table {

    /** @var MinersDatabase Database handler. */
    private MinersDatabase $db;

    /** @var int Total records after filtering. */
    private int $total_items = 0;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( [
            'singular' => 'miner',
            'plural'   => 'miners',
            'ajax'     => false,
        ] );

        $this->db = new MinersDatabase();
    }

    /**
     * Define table columns.
     *
     * @return array<string, string>
     */
    public function get_columns(): array {
        return [
            'cb'               => '<input type="checkbox" />',
            'model'            => __( 'Model', 'wp-datacharts-pro' ),
            'brand'            => __( 'Brand', 'wp-datacharts-pro' ),
            'top_coin'         => __( 'Coin', 'wp-datacharts-pro' ),
            'algorithm'        => __( 'Algorithm', 'wp-datacharts-pro' ),
            'hashrate'         => __( 'Hashrate', 'wp-datacharts-pro' ),
            'power_w'          => __( 'Power (W)', 'wp-datacharts-pro' ),
            'price_val'        => __( 'Price (USD)', 'wp-datacharts-pro' ),
            'daily_income_val' => __( 'Daily Income', 'wp-datacharts-pro' ),
            'roi'              => __( 'ROI', 'wp-datacharts-pro' ),
            'category'         => __( 'Category', 'wp-datacharts-pro' ),
            'imported_at'      => __( 'Imported At', 'wp-datacharts-pro' ),
        ];
    }

    /**
     * Define sortable columns.
     *
     * @return array<string, array{string, bool}>
     */
    protected function get_sortable_columns(): array {
        return [
            'model'            => [ 'model', false ],
            'brand'            => [ 'brand', false ],
            'top_coin'         => [ 'top_coin', false ],
            'algorithm'        => [ 'algorithm', false ],
            'power_w'          => [ 'power_w', false ],
            'price_val'        => [ 'price_val', false ],
            'daily_income_val' => [ 'daily_income_val', false ],
            'category'         => [ 'category', false ],
            'imported_at'      => [ 'imported_at', true ],
        ];
    }

    /**
     * Define bulk actions.
     *
     * @return array<string, string>
     */
    protected function get_bulk_actions(): array {
        return [
            'delete' => __( 'Delete', 'wp-datacharts-pro' ),
        ];
    }

    /**
     * Render the checkbox column.
     *
     * @param object $item Row data.
     * @return string
     */
    protected function column_cb( $item ): string {
        return sprintf(
            '<input type="checkbox" name="miner_ids[]" value="%s" />',
            (int) $item->id
        );
    }

    /**
     * Render the model column with a row-actions menu.
     *
     * @param object $item Row data.
     * @return string
     */
    protected function column_model( $item ): string {
        $delete_url = wp_nonce_url(
            add_query_arg(
                [
                    'page'      => 'wpdcp-miners-list',
                    'action'    => 'delete',
                    'miner_ids' => [ (int) $item->id ],
                ],
                admin_url( 'admin.php' )
            ),
            'bulk-miners'
        );

        $actions = [
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>',
                esc_url( $delete_url ),
                esc_js( __( 'Are you sure you want to delete this record?', 'wp-datacharts-pro' ) ),
                esc_html__( 'Delete', 'wp-datacharts-pro' )
            ),
        ];

        $model = esc_html( $item->model );
        if ( ! empty( $item->permalink ) ) {
            $model = sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                esc_url( $item->permalink ),
                esc_html( $item->model )
            );
        }

        return $model . $this->row_actions( $actions );
    }

    /**
     * Default column rendering.
     *
     * @param object $item        Row data.
     * @param string $column_name Column slug.
     * @return string
     */
    protected function column_default( $item, $column_name ): string {
        switch ( $column_name ) {
            case 'price_val':
                return '$' . number_format( (float) $item->price_val, 2 );
            case 'daily_income_val':
                return '$' . number_format( (float) $item->daily_income_val, 4 );
            case 'power_w':
                return number_format( (int) $item->power_w ) . ' W';
            case 'imported_at':
                return esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->imported_at ) );
            default:
                return esc_html( $item->$column_name ?? '' );
        }
    }

    /**
     * Message to display when there are no items.
     */
    public function no_items(): void {
        esc_html_e( 'No miners found. Use the import page to add data.', 'wp-datacharts-pro' );
    }

    /**
     * Prepare items for display.
     */
    public function prepare_items(): void {
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];

        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'miners_per_page', 20 );
        $current_page = $this->get_pagenum();

        // phpcs:disable WordPress.Security.NonceVerification
        $args = [
            'per_page'  => $per_page,
            'page'      => $current_page,
            'orderby'   => sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ?? 'imported_at' ) ),
            'order'     => sanitize_text_field( wp_unslash( $_REQUEST['order'] ?? 'DESC' ) ),
            'search'    => sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) ),
            'brand'     => sanitize_text_field( wp_unslash( $_REQUEST['filter_brand'] ?? '' ) ),
            'top_coin'  => sanitize_text_field( wp_unslash( $_REQUEST['filter_coin'] ?? '' ) ),
            'algorithm' => sanitize_text_field( wp_unslash( $_REQUEST['filter_algo'] ?? '' ) ),
            'category'  => sanitize_text_field( wp_unslash( $_REQUEST['filter_cat'] ?? '' ) ),
        ];
        // phpcs:enable

        $result            = $this->db->getMiners( $args );
        $this->items       = $result['items'];
        $this->total_items = $result['total'];

        $this->set_pagination_args( [
            'total_items' => $this->total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $this->total_items / $per_page ),
        ] );
    }

    /**
     * Process bulk delete action.
     */
    private function process_bulk_action(): void {
        $action = $this->current_action();

        if ( 'delete' !== $action ) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification
        if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'bulk-miners' ) ) {
            return;
        }

        $ids = isset( $_REQUEST['miner_ids'] ) ? array_map( 'intval', (array) $_REQUEST['miner_ids'] ) : [];
        // phpcs:enable

        if ( $ids ) {
            $this->db->deleteByIds( $ids );
        }
    }
}
