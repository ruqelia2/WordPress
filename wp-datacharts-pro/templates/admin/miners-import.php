<?php
/**
 * Admin Template: Miners JSON Import
 *
 * @package WP_DataCharts_Pro
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Miners\MinersImporter;
use WPDCP\Miners\MinersDatabase;

$importer = new MinersImporter();
$db       = new MinersDatabase();
$history  = array_reverse( $importer->getImportHistory() );
$total    = $db->count();
?>
<div class="wrap wpdcp-miners-import-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Miners JSON Import', 'wp-datacharts-pro' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpdcp-miners-list' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'View Miners Data', 'wp-datacharts-pro' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ( $total > 0 ) : ?>
        <div class="notice notice-info inline">
            <p>
                <?php
                printf(
                    /* translators: %d: total miner records in database */
                    esc_html__( 'Current database contains %d miner record(s).', 'wp-datacharts-pro' ),
                    (int) $total
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="wpdcp-import-container">

        <!-- ====== IMPORT MODE ====== -->
        <div class="wpdcp-import-mode postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'Import Options', 'wp-datacharts-pro' ); ?></h2>
            </div>
            <div class="inside">
                <fieldset>
                    <legend class="screen-reader-text"><?php esc_html_e( 'Import Mode', 'wp-datacharts-pro' ); ?></legend>
                    <label>
                        <input type="radio" name="wpdcp_import_mode" value="append" checked>
                        <strong><?php esc_html_e( 'Append', 'wp-datacharts-pro' ); ?></strong> —
                        <?php esc_html_e( 'Add new records to existing data.', 'wp-datacharts-pro' ); ?>
                    </label>
                    <br>
                    <label>
                        <input type="radio" name="wpdcp_import_mode" value="overwrite">
                        <strong><?php esc_html_e( 'Overwrite', 'wp-datacharts-pro' ); ?></strong> —
                        <?php esc_html_e( 'Clear ALL existing records and import fresh data.', 'wp-datacharts-pro' ); ?>
                    </label>
                </fieldset>
            </div>
        </div>

        <!-- ====== TAB NAVIGATION ====== -->
        <div class="wpdcp-import-tabs nav-tab-wrapper">
            <a href="#wpdcp-tab-paste" class="nav-tab nav-tab-active" data-tab="paste">
                <span class="dashicons dashicons-clipboard"></span>
                <?php esc_html_e( 'Paste JSON', 'wp-datacharts-pro' ); ?>
            </a>
            <a href="#wpdcp-tab-upload" class="nav-tab" data-tab="upload">
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e( 'Upload File', 'wp-datacharts-pro' ); ?>
            </a>
        </div>

        <!-- ====== TAB: PASTE ====== -->
        <div id="wpdcp-tab-paste" class="wpdcp-tab-content postbox">
            <div class="inside">
                <p class="description">
                    <?php esc_html_e( 'Paste a JSON array of miner objects into the text area below.', 'wp-datacharts-pro' ); ?>
                </p>
                <textarea id="wpdcp-json-paste"
                          class="wpdcp-json-textarea large-text code"
                          rows="16"
                          placeholder='[{"model":"Example Miner","brand":"Brand","top_coin":"BTC","algorithm":"SHA-256","power_w":3250,"hashrate":"110Th/s","price_val":3500,"daily_income_val":12.50, ...}]'></textarea>
                <p>
                    <button type="button" id="wpdcp-btn-validate" class="button button-secondary">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e( 'Validate JSON', 'wp-datacharts-pro' ); ?>
                    </button>
                    <button type="button" id="wpdcp-btn-import-paste" class="button button-primary" disabled>
                        <span class="dashicons dashicons-database-import"></span>
                        <?php esc_html_e( 'Import Data', 'wp-datacharts-pro' ); ?>
                    </button>
                    <button type="button" id="wpdcp-btn-clear-paste" class="button button-link-delete">
                        <?php esc_html_e( 'Clear', 'wp-datacharts-pro' ); ?>
                    </button>
                </p>
            </div>
        </div>

        <!-- ====== TAB: UPLOAD ====== -->
        <div id="wpdcp-tab-upload" class="wpdcp-tab-content postbox" style="display:none;">
            <div class="inside">
                <p class="description">
                    <?php esc_html_e( 'Upload a .json file containing an array of miner records.', 'wp-datacharts-pro' ); ?>
                </p>

                <!-- Drag-drop zone -->
                <div id="wpdcp-drop-zone" class="wpdcp-drop-zone">
                    <span class="dashicons dashicons-media-code wpdcp-drop-icon"></span>
                    <p><?php esc_html_e( 'Drag &amp; drop a .json file here', 'wp-datacharts-pro' ); ?></p>
                    <p class="description"><?php esc_html_e( 'or', 'wp-datacharts-pro' ); ?></p>
                    <label class="button" for="wpdcp-file-input">
                        <?php esc_html_e( 'Choose File', 'wp-datacharts-pro' ); ?>
                    </label>
                    <input type="file" id="wpdcp-file-input" accept=".json,application/json" class="screen-reader-text">
                    <p id="wpdcp-file-name" class="wpdcp-file-name"></p>
                </div>

                <p>
                    <button type="button" id="wpdcp-btn-import-file" class="button button-primary" disabled>
                        <span class="dashicons dashicons-database-import"></span>
                        <?php esc_html_e( 'Import File', 'wp-datacharts-pro' ); ?>
                    </button>
                </p>
            </div>
        </div>

        <!-- ====== VALIDATION RESULTS ====== -->
        <div id="wpdcp-validation-result" class="wpdcp-validation-box" style="display:none;"></div>

        <!-- ====== PROGRESS BAR ====== -->
        <div id="wpdcp-progress-wrap" class="wpdcp-progress-wrap" style="display:none;">
            <div class="wpdcp-progress-label"><?php esc_html_e( 'Importing…', 'wp-datacharts-pro' ); ?></div>
            <div class="wpdcp-progress-bar">
                <div class="wpdcp-progress-fill"></div>
            </div>
        </div>

        <!-- ====== IMPORT RESULT ====== -->
        <div id="wpdcp-import-result" style="display:none;"></div>

        <!-- ====== IMPORT HISTORY ====== -->
        <?php if ( ! empty( $history ) ) : ?>
        <div class="wpdcp-import-history postbox">
            <div class="postbox-header">
                <h2><?php esc_html_e( 'Import History', 'wp-datacharts-pro' ); ?></h2>
            </div>
            <div class="inside">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date / Time', 'wp-datacharts-pro' ); ?></th>
                            <th><?php esc_html_e( 'Records', 'wp-datacharts-pro' ); ?></th>
                            <th><?php esc_html_e( 'Mode', 'wp-datacharts-pro' ); ?></th>
                            <th><?php esc_html_e( 'Imported by', 'wp-datacharts-pro' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $history as $entry ) : ?>
                            <tr>
                                <td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry['imported_at'] ) ); ?></td>
                                <td><?php echo (int) $entry['count']; ?></td>
                                <td><?php echo esc_html( ucfirst( $entry['mode'] ) ); ?></td>
                                <td><?php
                                    $import_user = get_userdata( (int) $entry['user_id'] );
                                    echo esc_html( $import_user ? $import_user->display_name : '—' );
                                ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- .wpdcp-import-container -->
</div><!-- .wrap -->

<script type="text/javascript">
/* Inline data passed to admin-importer.js */
var wpdcpMinersImport = {
    ajaxUrl : <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
    nonce   : <?php echo wp_json_encode( wp_create_nonce( 'wpdcp_miners_import' ) ); ?>,
    i18n    : {
        validating  : <?php echo wp_json_encode( __( 'Validating…', 'wp-datacharts-pro' ) ); ?>,
        valid       : <?php echo wp_json_encode( __( 'JSON is valid. Found %d record(s) ready to import.', 'wp-datacharts-pro' ) ); ?>,
        invalidJson : <?php echo wp_json_encode( __( 'Invalid JSON: ', 'wp-datacharts-pro' ) ); ?>,
        importing   : <?php echo wp_json_encode( __( 'Importing…', 'wp-datacharts-pro' ) ); ?>,
        success     : <?php echo wp_json_encode( __( 'Import complete!', 'wp-datacharts-pro' ) ); ?>,
        error       : <?php echo wp_json_encode( __( 'Import failed:', 'wp-datacharts-pro' ) ); ?>,
        confirmOver : <?php echo wp_json_encode( __( 'Overwrite mode will delete ALL existing records. Continue?', 'wp-datacharts-pro' ) ); ?>,
        fileRead    : <?php echo wp_json_encode( __( 'File loaded: %s (%d bytes)', 'wp-datacharts-pro' ) ); ?>,
        dropHere    : <?php echo wp_json_encode( __( 'Drop file here', 'wp-datacharts-pro' ) ); ?>,
    }
};
</script>
