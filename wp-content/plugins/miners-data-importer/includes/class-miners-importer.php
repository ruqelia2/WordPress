<?php
/**
 * Core import logic: parse, validate, and store miner records.
 *
 * @package MinersDataImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Miners_Importer
 */
class Miners_Importer {

	/**
	 * Required fields for a valid miner record.
	 *
	 * @var string[]
	 */
	private $required_fields = array(
		'model',
		'brand',
		'top_coin',
		'algorithm',
		'power_w',
		'hashrate',
		'price_usd',
		'price_val',
		'category',
	);

	/**
	 * Parse and validate a JSON string.
	 *
	 * @param string $json Raw JSON string.
	 * @return array|WP_Error Decoded array on success, WP_Error on failure.
	 */
	public function parse_json( $json ) {
		if ( empty( trim( $json ) ) ) {
			return new WP_Error( 'empty_json', __( 'No JSON data provided.', 'miners-data-importer' ) );
		}

		$data = json_decode( $json, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'invalid_json',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Invalid JSON: %s', 'miners-data-importer' ),
					json_last_error_msg()
				)
			);
		}

		// Accept both a single object and an array of objects.
		if ( isset( $data['model'] ) ) {
			$data = array( $data );
		}

		if ( ! is_array( $data ) || empty( $data ) ) {
			return new WP_Error( 'empty_data', __( 'JSON must contain at least one miner record.', 'miners-data-importer' ) );
		}

		return $data;
	}

	/**
	 * Validate a single miner record.
	 *
	 * @param array $record Raw record array.
	 * @param int   $index  Record index for error messages.
	 * @return true|WP_Error
	 */
	public function validate_record( $record, $index = 0 ) {
		foreach ( $this->required_fields as $field ) {
			if ( ! array_key_exists( $field, $record ) ) {
				return new WP_Error(
					'missing_field',
					sprintf(
						/* translators: 1: field name, 2: record index */
						__( 'Record #%2$d is missing required field "%1$s".', 'miners-data-importer' ),
						$field,
						$index + 1
					)
				);
			}
		}
		return true;
	}

	/**
	 * Sanitize a single miner record.
	 *
	 * @param array $record Raw record.
	 * @return array Sanitized record.
	 */
	public function sanitize_record( $record ) {
		return array(
			'model'            => sanitize_text_field( $record['model'] ?? '' ),
			'brand'            => sanitize_text_field( $record['brand'] ?? '' ),
			'top_coin'         => sanitize_text_field( $record['top_coin'] ?? '' ),
			'all_coins'        => sanitize_text_field( $record['all_coins'] ?? '' ),
			'algorithm'        => sanitize_text_field( $record['algorithm'] ?? '' ),
			'power_w'          => absint( $record['power_w'] ?? 0 ),
			'hashrate'         => sanitize_text_field( $record['hashrate'] ?? '' ),
			'price_usd'        => sanitize_text_field( $record['price_usd'] ?? '' ),
			'price_val'        => floatval( $record['price_val'] ?? 0 ),
			'roi'              => sanitize_text_field( $record['roi'] ?? '' ),
			'daily_income_usd' => sanitize_text_field( $record['daily_income_usd'] ?? '' ),
			'daily_income_val' => floatval( $record['daily_income_val'] ?? 0 ),
			'value'            => floatval( $record['value'] ?? 0 ),
			'category'         => sanitize_text_field( $record['category'] ?? '' ),
			'value_class'      => sanitize_html_class( $record['value_class'] ?? '' ),
			'value_na'         => (int) ( $record['value_na'] ?? 0 ),
			'permalink'        => esc_url_raw( $record['permalink'] ?? '' ),
		);
	}

	/**
	 * Import an array of miner records into the database.
	 *
	 * @param array  $records        Array of raw miner records.
	 * @param string $duplicate_mode 'skip' or 'update'.
	 * @return array Summary: { imported, skipped, updated, errors }.
	 */
	public function import_records( $records, $duplicate_mode = 'skip' ) {
		global $wpdb;

		$table   = $wpdb->prefix . 'miners_data';
		$summary = array(
			'imported' => 0,
			'skipped'  => 0,
			'updated'  => 0,
			'errors'   => array(),
		);

		foreach ( $records as $index => $record ) {
			$valid = $this->validate_record( $record, $index );
			if ( is_wp_error( $valid ) ) {
				$summary['errors'][] = $valid->get_error_message();
				continue;
			}

			$data = $this->sanitize_record( $record );

			// Check for duplicate by model.
			$existing_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( "SELECT id FROM {$table} WHERE model = %s LIMIT 1", $data['model'] ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);

			if ( $existing_id ) {
				if ( 'update' === $duplicate_mode ) {
					$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$table,
						array_merge( $data, array( 'imported_at' => current_time( 'mysql' ) ) ),
						array( 'id' => $existing_id ),
						null,
						array( '%d' )
					);
					if ( false !== $result ) {
						++$summary['updated'];
					} else {
						$summary['errors'][] = sprintf(
							/* translators: %s: model name */
							__( 'Failed to update record for model "%s".', 'miners-data-importer' ),
							$data['model']
						);
					}
				} else {
					++$summary['skipped'];
				}
				continue;
			}

			$data['imported_at'] = current_time( 'mysql' );
			$result              = $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( false !== $result ) {
				++$summary['imported'];
			} else {
				$summary['errors'][] = sprintf(
					/* translators: %s: model name */
					__( 'Failed to insert record for model "%s".', 'miners-data-importer' ),
					$data['model']
				);
			}
		}

		return $summary;
	}
}
