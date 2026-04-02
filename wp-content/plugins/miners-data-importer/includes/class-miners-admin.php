<?php
/**
 * Admin pages, menus, and AJAX/form handlers.
 *
 * @package MinersDataImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Miners_Admin
 */
class Miners_Admin {

	/**
	 * Boot all hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_mdi_import', array( $this, 'handle_import' ) );
		add_action( 'wp_ajax_mdi_preview_json', array( $this, 'ajax_preview_json' ) );

		// Ensure DB table exists after plugin update.
		$db_ver = get_option( 'mdi_db_version', '0' );
		if ( version_compare( $db_ver, MDI_VERSION, '<' ) ) {
			Miners_Activator::create_table();
			update_option( 'mdi_db_version', MDI_VERSION );
		}
	}

	/**
	 * Register admin menus.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'Miners Import', 'miners-data-importer' ),
			__( 'Miners Import', 'miners-data-importer' ),
			'manage_options',
			'miners-import',
			array( $this, 'render_import_page' ),
			'dashicons-database-import',
			56
		);

		add_submenu_page(
			'miners-import',
			__( 'Import JSON', 'miners-data-importer' ),
			__( 'Import JSON', 'miners-data-importer' ),
			'manage_options',
			'miners-import',
			array( $this, 'render_import_page' )
		);

		add_submenu_page(
			'miners-import',
			__( 'All Miners', 'miners-data-importer' ),
			__( 'All Miners', 'miners-data-importer' ),
			'manage_options',
			'miners-list',
			array( $this, 'render_list_page' )
		);
	}

	/**
	 * Enqueue admin assets only on our plugin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		$allowed_hooks = array(
			'toplevel_page_miners-import',
			'miners-import_page_miners-list',
		);

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'mdi-admin-style',
			MDI_PLUGIN_URL . 'assets/css/admin-style.css',
			array(),
			MDI_VERSION
		);

		wp_enqueue_script(
			'mdi-admin-import',
			MDI_PLUGIN_URL . 'assets/js/admin-import.js',
			array( 'jquery' ),
			MDI_VERSION,
			true
		);

		wp_localize_script(
			'mdi-admin-import',
			'mdiAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'mdi_preview_json' ),
				'i18n'      => array(
					'parseError'    => __( 'Failed to parse JSON. Please check the format.', 'miners-data-importer' ),
					'noData'        => __( 'No data found in the JSON.', 'miners-data-importer' ),
					'selectAll'     => __( 'Select All', 'miners-data-importer' ),
					'deselectAll'   => __( 'Deselect All', 'miners-data-importer' ),
					'importing'     => __( 'Importing…', 'miners-data-importer' ),
					'dropHere'      => __( 'Drop JSON file here or click to select', 'miners-data-importer' ),
					'invalidFile'   => __( 'Please select a valid .json file.', 'miners-data-importer' ),
					'recordsFound'  => __( 'records found', 'miners-data-importer' ),
					'confirmImport' => __( 'Import selected miners?', 'miners-data-importer' ),
				),
			)
		);
	}

	/**
	 * Render the import page.
	 */
	public function render_import_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'miners-data-importer' ) );
		}
		require_once MDI_PLUGIN_DIR . 'templates/import-page.php';
	}

	/**
	 * Render the miners list page.
	 */
	public function render_list_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'miners-data-importer' ) );
		}

		// Handle single delete.
		if ( isset( $_GET['action'], $_GET['miner_id'], $_GET['_wpnonce'] ) && 'delete' === $_GET['action'] ) {
			$miner_id = absint( $_GET['miner_id'] );
			if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'mdi_delete_miner_' . $miner_id ) ) {
				$this->delete_miners( array( $miner_id ) );
				add_settings_error( 'mdi_messages', 'mdi_deleted', __( 'Miner deleted.', 'miners-data-importer' ), 'updated' );
			} else {
				add_settings_error( 'mdi_messages', 'mdi_nonce_fail', __( 'Security check failed.', 'miners-data-importer' ), 'error' );
			}
		}

		// Handle bulk delete.
		if ( isset( $_POST['action'], $_POST['miner_ids'], $_POST['_wpnonce'] ) && 'delete' === $_POST['action'] ) {
			if ( wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'bulk-miners' ) ) {
				$ids = array_map( 'absint', (array) $_POST['miner_ids'] );
				$this->delete_miners( $ids );
				add_settings_error( 'mdi_messages', 'mdi_bulk_deleted', __( 'Selected miners deleted.', 'miners-data-importer' ), 'updated' );
			} else {
				add_settings_error( 'mdi_messages', 'mdi_nonce_fail', __( 'Security check failed.', 'miners-data-importer' ), 'error' );
			}
		}

		require_once MDI_PLUGIN_DIR . 'templates/list-page.php';
	}

	/**
	 * Handle the import form POST (admin-post action).
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'miners-data-importer' ) );
		}

		check_admin_referer( 'mdi_import_action', 'mdi_import_nonce' );

		$json           = '';
		$duplicate_mode = isset( $_POST['duplicate_mode'] ) && 'update' === $_POST['duplicate_mode'] ? 'update' : 'skip';

		// Priority 1: uploaded file.
		if ( ! empty( $_FILES['mdi_json_file']['tmp_name'] ) ) {
			$file = $_FILES['mdi_json_file'];

			// Validate MIME / extension (we accept only .json).
			$ext = strtolower( pathinfo( sanitize_file_name( $file['name'] ), PATHINFO_EXTENSION ) );
			if ( 'json' !== $ext ) {
				$this->redirect_with_error( __( 'Only .json files are accepted.', 'miners-data-importer' ) );
				return;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$json = file_get_contents( $file['tmp_name'] );
		} elseif ( ! empty( $_POST['mdi_json_text'] ) ) {
			// Priority 2: pasted textarea.
			$json = wp_unslash( $_POST['mdi_json_text'] );
		}

		if ( empty( $json ) ) {
			$this->redirect_with_error( __( 'Please provide JSON data via file upload or the text area.', 'miners-data-importer' ) );
			return;
		}

		// Selected IDs (optional filtering from preview).
		$selected_ids = isset( $_POST['selected_ids'] ) && is_array( $_POST['selected_ids'] )
			? array_map( 'absint', $_POST['selected_ids'] )
			: array();

		$importer = new Miners_Importer();
		$parsed   = $importer->parse_json( $json );

		if ( is_wp_error( $parsed ) ) {
			$this->redirect_with_error( $parsed->get_error_message() );
			return;
		}

		// Filter to selected indices if provided.
		if ( ! empty( $selected_ids ) ) {
			$filtered = array();
			foreach ( $selected_ids as $idx ) {
				if ( isset( $parsed[ $idx ] ) ) {
					$filtered[] = $parsed[ $idx ];
				}
			}
			$parsed = $filtered;
		}

		$summary = $importer->import_records( $parsed, $duplicate_mode );

		set_transient( 'mdi_import_summary_' . get_current_user_id(), $summary, 60 );

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'miners-import', 'mdi_imported' => '1' ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX: parse and return preview data from raw JSON (no DB write).
	 */
	public function ajax_preview_json() {
		check_ajax_referer( 'mdi_preview_json', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'miners-data-importer' ) ), 403 );
		}

		$json = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$importer = new Miners_Importer();
		$parsed   = $importer->parse_json( $json );

		if ( is_wp_error( $parsed ) ) {
			wp_send_json_error( array( 'message' => $parsed->get_error_message() ) );
		}

		// Return sanitized preview data only (no DB interaction).
		$preview = array();
		foreach ( $parsed as $idx => $record ) {
			$preview[] = array_merge(
				array( '_index' => $idx ),
				$importer->sanitize_record( $record )
			);
		}

		wp_send_json_success( array( 'records' => $preview ) );
	}

	/**
	 * Delete miners by ID array.
	 *
	 * @param int[] $ids Array of miner IDs.
	 */
	private function delete_miners( array $ids ) {
		global $wpdb;

		if ( empty( $ids ) ) {
			return;
		}

		$table       = $wpdb->prefix . 'miners_data';
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Redirect back to import page with an error message stored in a transient.
	 *
	 * @param string $message Error message.
	 */
	private function redirect_with_error( $message ) {
		set_transient( 'mdi_import_error_' . get_current_user_id(), $message, 60 );
		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'miners-import', 'mdi_error' => '1' ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
