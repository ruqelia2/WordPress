<?php
/**
 * Import page template.
 *
 * @package MinersDataImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Show import summary after a successful import.
$summary = get_transient( 'mdi_import_summary_' . get_current_user_id() );
if ( $summary ) {
	delete_transient( 'mdi_import_summary_' . get_current_user_id() );
}

// Show error message.
$error_message = get_transient( 'mdi_import_error_' . get_current_user_id() );
if ( $error_message ) {
	delete_transient( 'mdi_import_error_' . get_current_user_id() );
}
?>
<div class="wrap mdi-wrap">
	<h1><?php esc_html_e( 'Import Miners JSON', 'miners-data-importer' ); ?></h1>

	<?php if ( $error_message ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error_message ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $summary ) : ?>
		<div class="notice notice-success is-dismissible mdi-summary-notice">
			<h3><?php esc_html_e( 'Import Complete', 'miners-data-importer' ); ?></h3>
			<ul>
				<li><?php printf( /* translators: %d number */ esc_html__( 'Imported: %d', 'miners-data-importer' ), (int) $summary['imported'] ); ?></li>
				<li><?php printf( /* translators: %d number */ esc_html__( 'Updated: %d', 'miners-data-importer' ), (int) $summary['updated'] ); ?></li>
				<li><?php printf( /* translators: %d number */ esc_html__( 'Skipped (duplicates): %d', 'miners-data-importer' ), (int) $summary['skipped'] ); ?></li>
				<?php if ( ! empty( $summary['errors'] ) ) : ?>
					<li>
						<?php printf( /* translators: %d number */ esc_html__( 'Errors: %d', 'miners-data-importer' ), count( $summary['errors'] ) ); ?>
						<ul>
							<?php foreach ( $summary['errors'] as $err ) : ?>
								<li><?php echo esc_html( $err ); ?></li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; ?>
			</ul>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=miners-list' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'View All Miners', 'miners-data-importer' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<!-- ===== Upload / Paste Panel ===== -->
	<div class="mdi-card mdi-input-panel">
		<h2><?php esc_html_e( 'Load JSON Data', 'miners-data-importer' ); ?></h2>

		<!-- Drop zone -->
		<div id="mdi-drop-zone" class="mdi-drop-zone" tabindex="0" role="button"
			 aria-label="<?php esc_attr_e( 'Drop JSON file here or click to select', 'miners-data-importer' ); ?>">
			<span class="dashicons dashicons-upload mdi-upload-icon"></span>
			<p><?php esc_html_e( 'Drag &amp; drop a .json file here', 'miners-data-importer' ); ?></p>
			<p><?php esc_html_e( 'or', 'miners-data-importer' ); ?></p>
			<label for="mdi-file-input" class="button"><?php esc_html_e( 'Choose File', 'miners-data-importer' ); ?></label>
			<input type="file" id="mdi-file-input" accept=".json" class="mdi-hidden-file-input" />
			<p id="mdi-file-name" class="mdi-file-name"></p>
		</div>

		<div class="mdi-or-divider"><span><?php esc_html_e( 'OR paste JSON directly', 'miners-data-importer' ); ?></span></div>

		<!-- Paste area -->
		<textarea id="mdi-json-textarea"
				  class="mdi-json-textarea"
				  placeholder="<?php esc_attr_e( 'Paste your JSON here…', 'miners-data-importer' ); ?>"
				  rows="8"
				  spellcheck="false"></textarea>

		<p>
			<button type="button" id="mdi-preview-btn" class="button button-secondary">
				<?php esc_html_e( 'Preview', 'miners-data-importer' ); ?>
			</button>
			<button type="button" id="mdi-clear-btn" class="button">
				<?php esc_html_e( 'Clear', 'miners-data-importer' ); ?>
			</button>
		</p>

		<div id="mdi-parse-error" class="mdi-parse-error hidden"></div>
	</div>

	<!-- ===== Preview / Filter Panel ===== -->
	<div id="mdi-preview-panel" class="mdi-card mdi-preview-panel hidden">
		<h2><?php esc_html_e( 'Preview & Filter', 'miners-data-importer' ); ?></h2>

		<div class="mdi-filter-bar">
			<label for="mdi-filter-brand"><?php esc_html_e( 'Brand', 'miners-data-importer' ); ?></label>
			<select id="mdi-filter-brand">
				<option value=""><?php esc_html_e( 'All Brands', 'miners-data-importer' ); ?></option>
			</select>

			<label for="mdi-filter-coin"><?php esc_html_e( 'Coin', 'miners-data-importer' ); ?></label>
			<select id="mdi-filter-coin">
				<option value=""><?php esc_html_e( 'All Coins', 'miners-data-importer' ); ?></option>
			</select>

			<label for="mdi-filter-category"><?php esc_html_e( 'Category', 'miners-data-importer' ); ?></label>
			<select id="mdi-filter-category">
				<option value=""><?php esc_html_e( 'All Categories', 'miners-data-importer' ); ?></option>
			</select>

			<label for="mdi-filter-algorithm"><?php esc_html_e( 'Algorithm', 'miners-data-importer' ); ?></label>
			<select id="mdi-filter-algorithm">
				<option value=""><?php esc_html_e( 'All Algorithms', 'miners-data-importer' ); ?></option>
			</select>

			<button type="button" id="mdi-apply-filters-btn" class="button">
				<?php esc_html_e( 'Apply Filters', 'miners-data-importer' ); ?>
			</button>
			<button type="button" id="mdi-reset-filters-btn" class="button">
				<?php esc_html_e( 'Reset', 'miners-data-importer' ); ?>
			</button>
		</div>

		<div class="mdi-selection-bar">
			<button type="button" id="mdi-select-all-btn" class="button">
				<?php esc_html_e( 'Select All', 'miners-data-importer' ); ?>
			</button>
			<button type="button" id="mdi-deselect-all-btn" class="button">
				<?php esc_html_e( 'Deselect All', 'miners-data-importer' ); ?>
			</button>
			<span id="mdi-selected-count" class="mdi-selected-count"></span>
		</div>

		<div class="mdi-table-wrapper">
			<table id="mdi-preview-table" class="wp-list-table widefat fixed striped mdi-preview-table">
				<thead>
					<tr>
						<th class="check-column"><input type="checkbox" id="mdi-check-all" /></th>
						<th><?php esc_html_e( 'Model', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'Brand', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'Top Coin', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'Algorithm', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'Hashrate', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'Power (W)', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'Price', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'Daily Income', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'ROI', 'miners-data-importer' ); ?></th>
						<th><?php esc_html_e( 'Category', 'miners-data-importer' ); ?></th>
					</tr>
				</thead>
				<tbody id="mdi-preview-tbody">
				</tbody>
			</table>
		</div>

		<!-- ===== Import Form ===== -->
		<form id="mdi-import-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'mdi_import_action', 'mdi_import_nonce' ); ?>
			<input type="hidden" name="action" value="mdi_import" />
			<input type="hidden" name="mdi_json_text" id="mdi-hidden-json" />
			<div id="mdi-selected-ids-container"></div>

			<div class="mdi-import-options">
				<label><?php esc_html_e( 'Duplicate handling:', 'miners-data-importer' ); ?></label>
				<label class="mdi-radio-label">
					<input type="radio" name="duplicate_mode" value="skip" checked />
					<?php esc_html_e( 'Skip duplicates', 'miners-data-importer' ); ?>
				</label>
				<label class="mdi-radio-label">
					<input type="radio" name="duplicate_mode" value="update" />
					<?php esc_html_e( 'Update duplicates', 'miners-data-importer' ); ?>
				</label>
			</div>

			<!-- Progress bar (shown during import) -->
			<div id="mdi-progress-wrap" class="mdi-progress-wrap hidden">
				<div class="mdi-progress-bar">
					<div id="mdi-progress-inner" class="mdi-progress-inner" style="width:0%"></div>
				</div>
				<p id="mdi-progress-label"><?php esc_html_e( 'Importing…', 'miners-data-importer' ); ?></p>
			</div>

			<p class="mdi-import-btn-row">
				<button type="submit" id="mdi-import-btn" class="button button-primary button-large">
					<?php esc_html_e( 'Import Selected Miners', 'miners-data-importer' ); ?>
				</button>
			</p>
		</form>
	</div>
</div>
