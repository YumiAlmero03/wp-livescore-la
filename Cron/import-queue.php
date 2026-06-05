<?php
/**
 * Import queue cron handlers.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule the queue processor to run soon.
 */
function wp_livescore_la_schedule_import_queue_processing() {
	if ( ! wp_next_scheduled( 'wp_livescore_la_process_import_queue' ) ) {
		wp_schedule_single_event( time() + 10, 'wp_livescore_la_process_import_queue' );
	}
}

/**
 * Unschedule the queue processor.
 */
function wp_livescore_la_unschedule_import_queue_processing() {
	$timestamp = wp_next_scheduled( 'wp_livescore_la_process_import_queue' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'wp_livescore_la_process_import_queue' );
	}

	wp_livescore_la_unschedule_kadario_prediction_queue_processing();
}

/**
 * Process a small batch of queued import jobs.
 */
function wp_livescore_la_process_import_queue() {
	if ( get_transient( 'wp_livescore_la_import_queue_lock' ) ) {
		wp_livescore_la_schedule_import_queue_processing();
		return;
	}

	set_transient( 'wp_livescore_la_import_queue_lock', 1, 5 * MINUTE_IN_SECONDS );
	wp_livescore_la_maybe_install_import_queue_table();
	wp_livescore_la_process_sofascore_player_queue_batch( 5 );
	delete_transient( 'wp_livescore_la_import_queue_lock' );

	if ( wp_livescore_la_import_queue_has_pending_jobs() ) {
		wp_livescore_la_schedule_import_queue_processing();
	}
}
add_action( 'wp_livescore_la_process_import_queue', 'wp_livescore_la_process_import_queue' );

/**
 * Queue Kadario predictions for one-per-minute background imports.
 *
 * @param string $selected_key Import target key, or all.
 * @return array|WP_Error
 */
function wp_livescore_la_queue_kadario_prediction_import( $selected_key = 'all' ) {
	if ( ! function_exists( 'wp_livescore_la_fetch_kadario_records' ) ) {
		return new WP_Error( 'wp_livescore_la_kadario_unavailable', __( 'Kadario importer is not available.', 'wp-livescore-la' ) );
	}

	$fetched = wp_livescore_la_fetch_kadario_records( $selected_key );
	if ( is_wp_error( $fetched ) ) {
		return $fetched;
	}

	$records = array_values(
		array_filter(
			(array) $fetched['records'],
			function ( $record ) {
				return is_array( $record );
			}
		)
	);

	update_option( 'wp_livescore_la_kadario_prediction_queue', $records, false );
	update_option(
		'wp_livescore_la_kadario_prediction_queue_status',
		array(
			'queued_at' => current_time( 'mysql' ),
			'total'     => count( $records ),
			'pending'   => count( $records ),
			'created'   => 0,
			'updated'   => 0,
			'skipped'   => 0,
			'last_run'  => '',
				'last_error' => '',
		),
		false
	);

	if ( ! empty( $records ) ) {
		wp_livescore_la_schedule_kadario_prediction_queue_processing();
	}

	return array(
		'queued_predictions' => count( $records ),
		'fetched'            => (int) $fetched['fetched'],
		'created'            => 0,
		'updated'            => 0,
		'skipped'            => 0,
	);
}

/**
 * Schedule the next Kadario prediction queue item.
 */
function wp_livescore_la_schedule_kadario_prediction_queue_processing() {
	if ( ! wp_next_scheduled( 'wp_livescore_la_process_kadario_prediction_queue' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'wp_livescore_la_process_kadario_prediction_queue' );
	}
}

/**
 * Unschedule queued Kadario prediction processing.
 */
function wp_livescore_la_unschedule_kadario_prediction_queue_processing() {
	$timestamp = wp_next_scheduled( 'wp_livescore_la_process_kadario_prediction_queue' );
	while ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'wp_livescore_la_process_kadario_prediction_queue' );
		$timestamp = wp_next_scheduled( 'wp_livescore_la_process_kadario_prediction_queue' );
	}
}

/**
 * Import exactly one queued Kadario prediction, then schedule the next minute.
 */
function wp_livescore_la_process_kadario_prediction_queue() {
	if ( get_transient( 'wp_livescore_la_kadario_prediction_queue_lock' ) ) {
		wp_livescore_la_schedule_kadario_prediction_queue_processing();
		return;
	}

	$queue = get_option( 'wp_livescore_la_kadario_prediction_queue', array() );
	if ( ! is_array( $queue ) || empty( $queue ) ) {
		return;
	}

	$record = array_shift( $queue );
	update_option( 'wp_livescore_la_kadario_prediction_queue', $queue, false );

	$status = get_option( 'wp_livescore_la_kadario_prediction_queue_status', array() );
	$status = is_array( $status ) ? $status : array();

	set_transient( 'wp_livescore_la_kadario_prediction_queue_lock', 1, 5 * MINUTE_IN_SECONDS );

	if ( function_exists( 'wp_livescore_la_import_kadario_records' ) && is_array( $record ) ) {
		$result = wp_livescore_la_import_kadario_records( array( $record ) );
		if ( is_wp_error( $result ) ) {
			$status['last_error'] = $result->get_error_message();
		} else {
			$status['created'] = (int) ( isset( $status['created'] ) ? $status['created'] : 0 ) + (int) $result['created'];
			$status['updated'] = (int) ( isset( $status['updated'] ) ? $status['updated'] : 0 ) + (int) $result['updated'];
			$status['skipped'] = (int) ( isset( $status['skipped'] ) ? $status['skipped'] : 0 ) + (int) $result['skipped'];
		}
	} else {
		$status['skipped'] = (int) ( isset( $status['skipped'] ) ? $status['skipped'] : 0 ) + 1;
	}

	delete_transient( 'wp_livescore_la_kadario_prediction_queue_lock' );

	$status['pending']  = count( $queue );
	$status['last_run'] = current_time( 'mysql' );
	update_option( 'wp_livescore_la_kadario_prediction_queue_status', $status, false );

	if ( ! empty( $queue ) ) {
		wp_livescore_la_schedule_kadario_prediction_queue_processing();
	}
}
add_action( 'wp_livescore_la_process_kadario_prediction_queue', 'wp_livescore_la_process_kadario_prediction_queue' );

/**
 * Schedule the daily Kadario import.
 */
function wp_livescore_la_schedule_kadario_daily_import() {
	if ( ! wp_next_scheduled( 'wp_livescore_la_run_kadario_daily_import' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wp_livescore_la_run_kadario_daily_import' );
	}
}
add_action( 'init', 'wp_livescore_la_schedule_kadario_daily_import' );

/**
 * Unschedule the daily Kadario import.
 */
function wp_livescore_la_unschedule_kadario_daily_import() {
	$timestamp = wp_next_scheduled( 'wp_livescore_la_run_kadario_daily_import' );
	while ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'wp_livescore_la_run_kadario_daily_import' );
		$timestamp = wp_next_scheduled( 'wp_livescore_la_run_kadario_daily_import' );
	}
}

/**
 * Run Kadario's daily match and team updater.
 */
function wp_livescore_la_run_kadario_daily_import() {
	if ( get_transient( 'wp_livescore_la_kadario_daily_import_lock' ) ) {
		return;
	}

	if ( ! function_exists( 'wp_livescore_la_queue_kadario_prediction_import' ) ) {
		return;
	}

	set_transient( 'wp_livescore_la_kadario_daily_import_lock', 1, 30 * MINUTE_IN_SECONDS );
	$result = wp_livescore_la_queue_kadario_prediction_import( 'all' );
	delete_transient( 'wp_livescore_la_kadario_daily_import_lock' );

	update_option(
		'wp_livescore_la_last_kadario_daily_import',
		array(
			'ran_at' => current_time( 'mysql' ),
			'result' => is_wp_error( $result ) ? $result->get_error_message() : $result,
		),
		false
	);
}
add_action( 'wp_livescore_la_run_kadario_daily_import', 'wp_livescore_la_run_kadario_daily_import' );
