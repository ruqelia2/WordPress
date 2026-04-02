/**
 * Miners Data Importer — Admin Import JS
 *
 * Handles: drag-drop file loading, file picker, JSON textarea,
 * AJAX preview, filter controls, row checkboxes, progress bar
 * animation on form submit.
 *
 * Depends on: jQuery (bundled with WordPress), mdiAdmin (wp_localize_script)
 */
/* global mdiAdmin, jQuery */
( function ( $ ) {
	'use strict';

	/* ---------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------- */
	var allRecords  = [];   // Full parsed dataset from AJAX preview
	var visibleRows = [];   // Indices currently shown after filtering

	/* ---------------------------------------------------------------
	 * DOM references (assigned after DOM ready)
	 * ------------------------------------------------------------- */
	var $dropZone, $fileInput, $fileName, $textarea, $previewBtn, $clearBtn,
		$parseError, $previewPanel, $filterBrand, $filterCoin, $filterCategory,
		$filterAlgorithm, $applyFiltersBtn, $resetFiltersBtn,
		$selectAllBtn, $deselectAllBtn, $selectedCount, $checkAll,
		$previewTbody, $previewTable, $importForm, $hiddenJson,
		$selectedIdsContainer, $progressWrap, $progressInner, $progressLabel,
		$importBtn;

	/* ---------------------------------------------------------------
	 * Initialise
	 * ------------------------------------------------------------- */
	$( document ).ready( function () {
		$dropZone            = $( '#mdi-drop-zone' );
		$fileInput           = $( '#mdi-file-input' );
		$fileName            = $( '#mdi-file-name' );
		$textarea            = $( '#mdi-json-textarea' );
		$previewBtn          = $( '#mdi-preview-btn' );
		$clearBtn            = $( '#mdi-clear-btn' );
		$parseError          = $( '#mdi-parse-error' );
		$previewPanel        = $( '#mdi-preview-panel' );
		$filterBrand         = $( '#mdi-filter-brand' );
		$filterCoin          = $( '#mdi-filter-coin' );
		$filterCategory      = $( '#mdi-filter-category' );
		$filterAlgorithm     = $( '#mdi-filter-algorithm' );
		$applyFiltersBtn     = $( '#mdi-apply-filters-btn' );
		$resetFiltersBtn     = $( '#mdi-reset-filters-btn' );
		$selectAllBtn        = $( '#mdi-select-all-btn' );
		$deselectAllBtn      = $( '#mdi-deselect-all-btn' );
		$selectedCount       = $( '#mdi-selected-count' );
		$checkAll            = $( '#mdi-check-all' );
		$previewTbody        = $( '#mdi-preview-tbody' );
		$previewTable        = $( '#mdi-preview-table' );
		$importForm          = $( '#mdi-import-form' );
		$hiddenJson          = $( '#mdi-hidden-json' );
		$selectedIdsContainer = $( '#mdi-selected-ids-container' );
		$progressWrap        = $( '#mdi-progress-wrap' );
		$progressInner       = $( '#mdi-progress-inner' );
		$progressLabel       = $( '#mdi-progress-label' );
		$importBtn           = $( '#mdi-import-btn' );

		bindDropZone();
		bindFileInput();
		bindButtons();
		bindFilters();
		bindCheckboxes();
		bindImportForm();
	} );

	/* ---------------------------------------------------------------
	 * Drop-zone
	 * ------------------------------------------------------------- */
	function bindDropZone() {
		$dropZone.on( 'click', function () {
			$fileInput.trigger( 'click' );
		} );

		$dropZone.on( 'keydown', function ( e ) {
			if ( 13 === e.which || 32 === e.which ) {
				$fileInput.trigger( 'click' );
			}
		} );

		$dropZone.on( 'dragover dragenter', function ( e ) {
			e.preventDefault();
			$dropZone.addClass( 'mdi-dragover' );
		} );

		$dropZone.on( 'dragleave dragend drop', function ( e ) {
			e.preventDefault();
			$dropZone.removeClass( 'mdi-dragover' );
		} );

		$dropZone.on( 'drop', function ( e ) {
			e.preventDefault();
			var files = e.originalEvent.dataTransfer.files;
			if ( files.length ) {
				loadFile( files[0] );
			}
		} );
	}

	/* ---------------------------------------------------------------
	 * File input
	 * ------------------------------------------------------------- */
	function bindFileInput() {
		$fileInput.on( 'change', function () {
			if ( this.files.length ) {
				loadFile( this.files[0] );
			}
		} );
	}

	function loadFile( file ) {
		if ( ! file.name.match( /\.json$/i ) ) {
			showParseError( mdiAdmin.i18n.invalidFile );
			return;
		}

		var reader = new FileReader();
		reader.onload = function ( e ) {
			$textarea.val( e.target.result );
			$fileName.text( file.name );
			hideParseError();
		};
		reader.readAsText( file );
	}

	/* ---------------------------------------------------------------
	 * Buttons
	 * ------------------------------------------------------------- */
	function bindButtons() {
		$previewBtn.on( 'click', requestPreview );

		$clearBtn.on( 'click', function () {
			$textarea.val( '' );
			$fileName.text( '' );
			$fileInput.val( '' );
			hideParseError();
			hidePreviewPanel();
			allRecords  = [];
			visibleRows = [];
		} );
	}

	/* ---------------------------------------------------------------
	 * AJAX preview
	 * ------------------------------------------------------------- */
	function requestPreview() {
		var json = $textarea.val().trim();
		if ( ! json ) {
			showParseError( mdiAdmin.i18n.noData );
			return;
		}

		hideParseError();
		$previewBtn.prop( 'disabled', true ).text( '…' );

		$.post(
			mdiAdmin.ajaxUrl,
			{
				action : 'mdi_preview_json',
				nonce  : mdiAdmin.nonce,
				json   : json
			},
			function ( response ) {
				$previewBtn.prop( 'disabled', false ).text( 'Preview' );

				if ( ! response.success ) {
					showParseError( response.data.message || mdiAdmin.i18n.parseError );
					return;
				}

				allRecords = response.data.records || [];

				if ( ! allRecords.length ) {
					showParseError( mdiAdmin.i18n.noData );
					return;
				}

				populateFilterDropdowns( allRecords );
				renderTable( allRecords );
				showPreviewPanel();
			}
		).fail( function () {
			$previewBtn.prop( 'disabled', false ).text( 'Preview' );
			showParseError( mdiAdmin.i18n.parseError );
		} );
	}

	/* ---------------------------------------------------------------
	 * Filter dropdowns
	 * ------------------------------------------------------------- */
	function populateFilterDropdowns( records ) {
		var brands     = unique( records, 'brand' );
		var coins      = unique( records, 'top_coin' );
		var categories = unique( records, 'category' );
		var algorithms = unique( records, 'algorithm' );

		populateSelect( $filterBrand,     brands,     'All Brands' );
		populateSelect( $filterCoin,      coins,      'All Coins' );
		populateSelect( $filterCategory,  categories, 'All Categories' );
		populateSelect( $filterAlgorithm, algorithms, 'All Algorithms' );
	}

	function populateSelect( $sel, values, placeholder ) {
		$sel.find( 'option:not(:first)' ).remove();
		$.each( values, function ( i, v ) {
			$sel.append( $( '<option>' ).val( v ).text( v ) );
		} );
	}

	function unique( records, key ) {
		var seen = {}, result = [];
		$.each( records, function ( i, r ) {
			var v = r[ key ] || '';
			if ( v && ! seen[ v ] ) {
				seen[ v ] = true;
				result.push( v );
			}
		} );
		result.sort();
		return result;
	}

	/* ---------------------------------------------------------------
	 * Filters
	 * ------------------------------------------------------------- */
	function bindFilters() {
		$applyFiltersBtn.on( 'click', applyFilters );
		$resetFiltersBtn.on( 'click', function () {
			$filterBrand.val( '' );
			$filterCoin.val( '' );
			$filterCategory.val( '' );
			$filterAlgorithm.val( '' );
			renderTable( allRecords );
		} );
	}

	function applyFilters() {
		var brand     = $filterBrand.val();
		var coin      = $filterCoin.val();
		var category  = $filterCategory.val();
		var algorithm = $filterAlgorithm.val();

		var filtered = allRecords.filter( function ( r ) {
			return ( ! brand     || r.brand     === brand )
				&& ( ! coin      || r.top_coin  === coin )
				&& ( ! category  || r.category  === category )
				&& ( ! algorithm || r.algorithm === algorithm );
		} );

		renderTable( filtered );
	}

	/* ---------------------------------------------------------------
	 * Table rendering
	 * ------------------------------------------------------------- */
	function renderTable( records ) {
		visibleRows = records;
		var html = '';

		if ( ! records.length ) {
			html = '<tr><td colspan="11">' + escHtml( mdiAdmin.i18n.noData ) + '</td></tr>';
		} else {
			$.each( records, function ( i, r ) {
				html += '<tr data-index="' + escAttr( String( r._index ) ) + '">'
					+ '<td class="check-column"><input type="checkbox" class="mdi-row-check" value="' + escAttr( String( r._index ) ) + '" checked /></td>'
					+ '<td class="mdi-model-cell">' + escHtml( r.model )           + '</td>'
					+ '<td>'                         + escHtml( r.brand )           + '</td>'
					+ '<td>'                         + escHtml( r.top_coin )        + '</td>'
					+ '<td>'                         + escHtml( r.algorithm )       + '</td>'
					+ '<td>'                         + escHtml( r.hashrate )        + '</td>'
					+ '<td>'                         + escHtml( String( r.power_w ) ) + '</td>'
					+ '<td>'                         + escHtml( r.price_usd )       + '</td>'
					+ '<td>'                         + escHtml( r.daily_income_usd ) + '</td>'
					+ '<td>'                         + escHtml( r.roi )             + '</td>'
					+ '<td>'                         + escHtml( r.category )        + '</td>'
					+ '</tr>';
			} );
		}

		$previewTbody.html( html );
		$checkAll.prop( 'checked', true );
		updateSelectedCount();
	}

	/* ---------------------------------------------------------------
	 * Checkboxes
	 * ------------------------------------------------------------- */
	function bindCheckboxes() {
		// Check-all header checkbox.
		$( document ).on( 'change', '#mdi-check-all', function () {
			$previewTbody.find( '.mdi-row-check' ).prop( 'checked', this.checked );
			updateSelectedCount();
		} );

		// Individual row checkboxes.
		$( document ).on( 'change', '.mdi-row-check', function () {
			var total   = $previewTbody.find( '.mdi-row-check' ).length;
			var checked = $previewTbody.find( '.mdi-row-check:checked' ).length;
			$checkAll.prop( 'checked', total === checked );
			updateSelectedCount();
		} );

		$selectAllBtn.on( 'click', function () {
			$previewTbody.find( '.mdi-row-check' ).prop( 'checked', true );
			$checkAll.prop( 'checked', true );
			updateSelectedCount();
		} );

		$deselectAllBtn.on( 'click', function () {
			$previewTbody.find( '.mdi-row-check' ).prop( 'checked', false );
			$checkAll.prop( 'checked', false );
			updateSelectedCount();
		} );
	}

	function updateSelectedCount() {
		var checked = $previewTbody.find( '.mdi-row-check:checked' ).length;
		$selectedCount.text( checked + ' ' + mdiAdmin.i18n.recordsFound );
	}

	/* ---------------------------------------------------------------
	 * Import form submission
	 * ------------------------------------------------------------- */
	function bindImportForm() {
		$importForm.on( 'submit', function ( e ) {
			// Populate hidden JSON field.
			$hiddenJson.val( $textarea.val() );

			// Populate selected indices as hidden inputs.
			$selectedIdsContainer.empty();
			$previewTbody.find( '.mdi-row-check:checked' ).each( function () {
				$selectedIdsContainer.append(
					$( '<input>' ).attr( {
						type  : 'hidden',
						name  : 'selected_ids[]',
						value : $( this ).val()
					} )
				);
			} );

			if ( ! $previewTbody.find( '.mdi-row-check:checked' ).length ) {
				e.preventDefault();
				showParseError( mdiAdmin.i18n.noData );
				return;
			}

			// Show progress animation.
			$progressWrap.removeClass( 'hidden' );
			$importBtn.prop( 'disabled', true );
			animateProgress();
		} );
	}

	function animateProgress() {
		var pct = 0;
		var interval = setInterval( function () {
			pct += Math.random() * 8;
			if ( pct >= 90 ) {
				pct = 90;
				clearInterval( interval );
			}
			$progressInner.css( 'width', pct.toFixed( 0 ) + '%' );
			$progressLabel.text( mdiAdmin.i18n.importing + ' ' + pct.toFixed( 0 ) + '%' );
		}, 200 );
	}

	/* ---------------------------------------------------------------
	 * Helpers: visibility
	 * ------------------------------------------------------------- */
	function showParseError( msg ) {
		$parseError.text( msg ).removeClass( 'hidden' );
	}

	function hideParseError() {
		$parseError.addClass( 'hidden' ).text( '' );
	}

	function showPreviewPanel() {
		$previewPanel.removeClass( 'hidden' );
	}

	function hidePreviewPanel() {
		$previewPanel.addClass( 'hidden' );
	}

	/* ---------------------------------------------------------------
	 * HTML escaping utilities
	 * ------------------------------------------------------------- */
	function escHtml( str ) {
		return String( str )
			.replace( /&/g,  '&amp;' )
			.replace( /</g,  '&lt;' )
			.replace( />/g,  '&gt;' )
			.replace( /"/g,  '&quot;' )
			.replace( /'/g,  '&#039;' );
	}

	function escAttr( str ) {
		return escHtml( str );
	}

}( jQuery ) );
