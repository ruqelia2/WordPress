/**
 * Miners JSON Import — Admin JavaScript
 * WP DataCharts Pro
 *
 * Handles: tab switching, JSON validation, file drag-drop / upload, AJAX import.
 */
/* global wpdcpMinersImport, jQuery */
(function ( $ ) {
    'use strict';

    var cfg = window.wpdcpMinersImport || {};

    // ── cached jQuery objects ──────────────────────────────────────
    var $textarea       = $( '#wpdcp-json-paste' );
    var $btnValidate    = $( '#wpdcp-btn-validate' );
    var $btnImportPaste = $( '#wpdcp-btn-import-paste' );
    var $btnClear       = $( '#wpdcp-btn-clear-paste' );
    var $btnImportFile  = $( '#wpdcp-btn-import-file' );
    var $validResult    = $( '#wpdcp-validation-result' );
    var $progressWrap   = $( '#wpdcp-progress-wrap' );
    var $progressFill   = $( '.wpdcp-progress-fill' );
    var $importResult   = $( '#wpdcp-import-result' );
    var $dropZone       = $( '#wpdcp-drop-zone' );
    var $fileInput      = $( '#wpdcp-file-input' );
    var $fileName       = $( '#wpdcp-file-name' );

    var pendingJson = '';   // holds the last validated / loaded JSON string

    // ── helpers ────────────────────────────────────────────────────

    /**
     * Replace %d with a number in a translated string.
     *
     * @param {string} tmpl
     * @param {number} n
     * @returns {string}
     */
    function formatWithCount( tmpl, n ) {
        return tmpl.replace( '%d', n );
    }

    function getImportMode() {
        return $( 'input[name="wpdcp_import_mode"]:checked' ).val() || 'append';
    }

    function showValidation( type, message, extras ) {
        $validResult
            .removeClass( 'is-success is-error is-warning' )
            .addClass( 'is-' + type )
            .show();

        var html = '<p>' + message + '</p>';
        if ( extras && extras.length ) {
            html += '<ul>';
            extras.slice( 0, 10 ).forEach( function ( e ) {
                html += '<li>' + escHtml( e ) + '</li>';
            } );
            if ( extras.length > 10 ) {
                html += '<li>… and ' + ( extras.length - 10 ) + ' more</li>';
            }
            html += '</ul>';
        }
        $validResult.html( html );
    }

    function escHtml( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' );
    }

    function showProgress() {
        $progressWrap.show();
        $progressFill.css( 'width', '30%' );
        $importResult.hide();
    }

    function finishProgress() {
        $progressFill.css( 'width', '100%' );
        setTimeout( function () {
            $progressWrap.hide();
            $progressFill.css( 'width', '0' );
        }, 500 );
    }

    function showResult( success, data ) {
        var cls     = success ? 'notice-success' : 'notice-error';
        var message = success
            ? escHtml( data.message || cfg.i18n.success )
            : escHtml( ( data && data.message ) || cfg.i18n.error );

        var html = '<div class="notice ' + cls + ' is-dismissible"><p>' + message + '</p>';

        if ( success && data.errors && data.errors.length ) {
            html += '<p><strong>' + data.errors.length + ' record(s) skipped:</strong></p><ul>';
            data.errors.slice( 0, 5 ).forEach( function ( e ) {
                html += '<li>' + escHtml( e ) + '</li>';
            } );
            html += '</ul>';
        }

        if ( success ) {
            html += '<p>Total records in database: <strong>' + ( data.total || 0 ) + '</strong></p>';
        }

        html += '</div>';
        $importResult.html( html ).show();
    }

    // ── JSON validation ────────────────────────────────────────────

    function validateJson( jsonStr ) {
        var parsed;
        try {
            parsed = JSON.parse( jsonStr );
        } catch ( e ) {
            showValidation( 'error', escHtml( cfg.i18n.invalidJson ) + escHtml( e.message ) );
            $btnImportPaste.prop( 'disabled', true );
            pendingJson = '';
            return;
        }

        if ( ! Array.isArray( parsed ) ) {
            if ( typeof parsed === 'object' && parsed !== null ) {
                parsed = [ parsed ];
            } else {
                showValidation( 'error', 'JSON must be an array of miner objects.' );
                $btnImportPaste.prop( 'disabled', true );
                pendingJson = '';
                return;
            }
        }

        var required = [ 'model', 'brand', 'top_coin', 'algorithm', 'power_w', 'hashrate', 'price_val', 'daily_income_val' ];
        var errors   = [];

        parsed.forEach( function ( item, i ) {
            if ( typeof item !== 'object' || item === null ) {
                errors.push( 'Item #' + i + ' is not an object.' );
                return;
            }
            required.forEach( function ( f ) {
                if ( ! ( f in item ) || item[ f ] === null || item[ f ] === '' ) {
                    errors.push( 'Item #' + i + ' missing field: ' + f );
                }
            } );
        } );

        if ( errors.length ) {
            showValidation( 'warning',
                formatWithCount( cfg.i18n.valid, parsed.length ) + ' — ' + errors.length + ' record(s) have issues.',
                errors
            );
        } else {
            showValidation( 'success', formatWithCount( cfg.i18n.valid, parsed.length ) );
        }

        pendingJson = jsonStr;
        $btnImportPaste.prop( 'disabled', false );
    }

    // ── AJAX import ────────────────────────────────────────────────

    function doImport( jsonStr, sourceName ) {
        if ( ! jsonStr ) { return; }

        var mode = getImportMode();

        if ( mode === 'overwrite' ) {
            if ( ! window.confirm( cfg.i18n.confirmOver ) ) { return; }
        }

        showProgress();
        $btnImportPaste.prop( 'disabled', true );
        $btnImportFile.prop( 'disabled', true );

        $.ajax( {
            url  : cfg.ajaxUrl,
            type : 'POST',
            data : {
                action      : 'wpdcp_miners_import',
                nonce       : cfg.nonce,
                json_data   : jsonStr,
                import_mode : mode,
            },
            success : function ( response ) {
                finishProgress();
                if ( response.success ) {
                    showResult( true, response.data );
                    // Reset file input after successful import.
                    $fileInput.val( '' );
                    $fileName.text( '' );
                    pendingJson = '';
                    $btnImportPaste.prop( 'disabled', true );
                    $btnImportFile.prop( 'disabled', true );
                } else {
                    showResult( false, response.data );
                    $btnImportPaste.prop( 'disabled', false );
                    $btnImportFile.prop( 'disabled', false );
                }
            },
            error : function () {
                finishProgress();
                showResult( false, { message: 'Server error. Please try again.' } );
                $btnImportPaste.prop( 'disabled', false );
                $btnImportFile.prop( 'disabled', false );
            },
        } );
    }

    // ── File reading ───────────────────────────────────────────────

    function readFile( file ) {
        if ( ! file ) { return; }

        if ( file.type !== 'application/json' && ! file.name.match( /\.json$/i ) ) {
            showValidation( 'error', 'Only .json files are supported.' );
            return;
        }

        var reader = new FileReader();
        reader.onload = function ( e ) {
            var content = e.target.result;
            $fileName.text( file.name + ' (' + formatBytes( file.size ) + ')' );
            try {
                JSON.parse( content ); // basic check
                pendingJson = content;
                $btnImportFile.prop( 'disabled', false );
                showValidation( 'success', formatWithCount( 'File loaded: %d bytes ready to import.', file.size ) );
            } catch ( ex ) {
                showValidation( 'error', 'File contains invalid JSON: ' + ex.message );
                $btnImportFile.prop( 'disabled', true );
            }
        };
        reader.readAsText( file );
    }

    function formatBytes( bytes ) {
        if ( bytes < 1024 ) { return bytes + ' B'; }
        if ( bytes < 1048576 ) { return ( bytes / 1024 ).toFixed( 1 ) + ' KB'; }
        return ( bytes / 1048576 ).toFixed( 2 ) + ' MB';
    }

    // ── Tab switching ──────────────────────────────────────────────

    $( '.wpdcp-import-tabs .nav-tab' ).on( 'click', function ( e ) {
        e.preventDefault();
        var tab = $( this ).data( 'tab' );

        $( '.wpdcp-import-tabs .nav-tab' ).removeClass( 'nav-tab-active' );
        $( this ).addClass( 'nav-tab-active' );

        $( '.wpdcp-tab-content' ).hide();
        $( '#wpdcp-tab-' + tab ).show();

        $validResult.hide();
        $importResult.hide();
    } );

    // ── Paste tab events ───────────────────────────────────────────

    $btnValidate.on( 'click', function () {
        var val = $textarea.val().trim();
        if ( ! val ) {
            showValidation( 'error', 'Please paste JSON data first.' );
            return;
        }
        validateJson( val );
    } );

    $btnImportPaste.on( 'click', function () {
        doImport( pendingJson || $textarea.val().trim(), 'paste' );
    } );

    $btnClear.on( 'click', function () {
        $textarea.val( '' );
        $validResult.hide();
        $importResult.hide();
        pendingJson = '';
        $btnImportPaste.prop( 'disabled', true );
    } );

    // Enable import button automatically when textarea already has content
    // (e.g., pasted from clipboard shortcut).
    $textarea.on( 'input', function () {
        $btnImportPaste.prop( 'disabled', true );
        pendingJson = '';
    } );

    // ── Upload tab events ──────────────────────────────────────────

    $fileInput.on( 'change', function () {
        readFile( this.files[ 0 ] );
    } );

    $btnImportFile.on( 'click', function () {
        doImport( pendingJson, 'file' );
    } );

    // Drag-drop events on the drop zone.
    $dropZone.on( 'dragover dragenter', function ( e ) {
        e.preventDefault();
        e.stopPropagation();
        $( this ).addClass( 'wpdcp-drag-over' );
    } );

    $dropZone.on( 'dragleave dragend drop', function ( e ) {
        e.preventDefault();
        e.stopPropagation();
        $( this ).removeClass( 'wpdcp-drag-over' );
    } );

    $dropZone.on( 'drop', function ( e ) {
        var files = e.originalEvent.dataTransfer.files;
        if ( files && files.length ) {
            readFile( files[ 0 ] );
        }
    } );

    // Also accept click on the whole zone to trigger file picker.
    $dropZone.on( 'click', function ( e ) {
        // Prevent double-trigger if clicking the label/button inside.
        if ( ! $( e.target ).is( 'label, input, button' ) ) {
            $fileInput.trigger( 'click' );
        }
    } );

}( jQuery ) );
