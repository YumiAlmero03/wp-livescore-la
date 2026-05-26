<?php
/**
 * SofaScore import provider.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * SofaScore API settings.
 *
 * Targets can be endpoint paths such as "leagues/list" or full URLs.
 */
$wp_livescore_la_sofascore_api_link = 'https://sofascore.p.rapidapi.com/';
$wp_livescore_la_sofascore_api_key  = 'a78f96f654msh3f4258754057e32p1656c1jsnd337b330fe68';
$wp_livescore_la_sofascore_api_host = 'sofascore.p.rapidapi.com';
$wp_livescore_la_sofascore_import_links = array(
	// Add SofaScore endpoint paths or full URLs here.
	array(
		'label'  => 'Leagues',
		'target' => 'search?q=FIFA World Cup&type=all&page=0',
		'type'   => 'leagues',
	),
	array(
		'label'  => 'Leagues',
		'target' => 'search?q=Philippines Football League&type=all&page=0',
		'type'   => 'leagues',
	),
	array(
		'label'  => 'Update Seasons',
		'target' => 'tournaments/get-seasons?tournamentId={api_id}',
		'type'   => 'seasons',
	),
	array(
		'label'  => 'Players',
		'target' => 'teams/get-squad?teamId={team_api_id}',
		'type'   => 'players',
	),
	array(
		'label'  => 'Next Matches',
		'target' => 'tournaments/get-next-matches?tournamentId=16&seasonId=58210&pageIndex={loop}',
		'type'   => 'matches',
	),
	array(
		'label'  => 'Next Matches',
		'target' => 'tournaments/get-next-matches?tournamentId=1654&seasonId=81520&pageIndex={loop}',
		'type'   => 'matches',
	),
);

/**
 * Get SofaScore import links.
 *
 * @return array
 */
function wp_livescore_la_get_sofascore_import_links() {
	global $wp_livescore_la_sofascore_import_links;

	$links = array();

	if ( ! is_array( $wp_livescore_la_sofascore_import_links ) ) {
		return $links;
	}

	foreach ( $wp_livescore_la_sofascore_import_links as $index => $link ) {
		if ( ! is_array( $link ) || empty( $link['target'] ) ) {
			continue;
		}

		$target = trim( (string) $link['target'] );
		$label  = isset( $link['label'] ) && '' !== trim( (string) $link['label'] ) ? trim( (string) $link['label'] ) : $target;
		$key    = sanitize_key( sanitize_title( $index . '-' . $label . '-' . $target ) );

		$links[ $key ] = array(
			'key'    => $key,
			'label'  => sanitize_text_field( $label ),
			'target' => $target,
			'type'   => isset( $link['type'] ) ? sanitize_key( $link['type'] ) : 'leagues',
			'url'    => wp_livescore_la_resolve_sofascore_target_url( $target ),
		);
	}

	return $links;
}

/**
 * Resolve a full URL or endpoint path to a SofaScore URL.
 *
 * @param string $target Full URL or endpoint path.
 * @return string
 */
function wp_livescore_la_resolve_sofascore_target_url( $target ) {
	$target = trim( (string) $target );

	if ( preg_match( '#^https?://#i', $target ) ) {
		return esc_url_raw( $target );
	}

	$api_link = wp_livescore_la_get_sofascore_api_link();
	$target   = ltrim( $target, " \t\n\r\0\x0B/" );

	if ( '' === $api_link || '' === $target ) {
		return '';
	}

	return esc_url_raw( trailingslashit( $api_link ) . $target );
}

/**
 * Get SofaScore API link.
 *
 * @return string
 */
function wp_livescore_la_get_sofascore_api_link() {
	global $wp_livescore_la_sofascore_api_link;

	return isset( $wp_livescore_la_sofascore_api_link ) ? esc_url_raw( $wp_livescore_la_sofascore_api_link ) : '';
}

/**
 * Get SofaScore request headers.
 *
 * @return array
 */
function wp_livescore_la_get_sofascore_headers() {
	global $wp_livescore_la_sofascore_api_host, $wp_livescore_la_sofascore_api_key;

	return array(
		'Content-Type'    => 'application/json',
		'x-rapidapi-host' => isset( $wp_livescore_la_sofascore_api_host ) ? sanitize_text_field( $wp_livescore_la_sofascore_api_host ) : '',
		'x-rapidapi-key'  => isset( $wp_livescore_la_sofascore_api_key ) ? sanitize_text_field( $wp_livescore_la_sofascore_api_key ) : '',
	);
}

/**
 * Import one or all configured SofaScore targets.
 *
 * @param string $selected_key Import target key, or all.
 * @return array|WP_Error
 */
	function wp_livescore_la_run_sofascore_import( $selected_key = 'all' ) {
	$links = wp_livescore_la_get_sofascore_import_links();

	if ( empty( $links ) ) {
		return new WP_Error( 'wp_livescore_la_no_links', __( 'No SofaScore import links are configured.', 'wp-livescore-la' ) );
	}

	if ( 'all' !== $selected_key ) {
		if ( ! isset( $links[ $selected_key ] ) ) {
			return new WP_Error( 'wp_livescore_la_missing_link', __( 'The selected SofaScore import link was not found.', 'wp-livescore-la' ) );
		}
		$links = array( $selected_key => $links[ $selected_key ] );
	}

	$total = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'fetched' => 0,
	);

	foreach ( $links as $link ) {
		if ( 'seasons' === $link['type'] && false !== strpos( $link['target'], '{api_id}' ) ) {
			$result = wp_livescore_la_import_sofascore_seasons_for_all_leagues( $link['target'] );

			$total['created'] += (int) $result['created'];
			$total['updated'] += (int) $result['updated'];
			$total['skipped'] += (int) $result['skipped'];
			$total['fetched'] += (int) $result['fetched'];
			continue;
		}

			if ( 'players' === $link['type'] && false !== strpos( $link['target'], '{team_api_id}' ) ) {
				$result = wp_livescore_la_queue_sofascore_players_for_all_teams( $link['target'] );

				$total['created'] += (int) $result['created'];
				$total['updated'] += (int) $result['updated'];
				$total['skipped'] += (int) $result['skipped'];
				$total['fetched'] += (int) $result['fetched'];
				$total['queued']   = isset( $total['queued'] ) ? (int) $total['queued'] + (int) $result['queued'] : (int) $result['queued'];
				continue;
			}

		if ( 'matches' === $link['type'] && false !== strpos( $link['target'], '{loop}' ) ) {
			$result = wp_livescore_la_import_sofascore_paged_matches( $link['target'] );

			$total['created'] += (int) $result['created'];
			$total['updated'] += (int) $result['updated'];
			$total['skipped'] += (int) $result['skipped'];
			$total['fetched'] += (int) $result['fetched'];
			continue;
		}

		if ( empty( $link['url'] ) ) {
			return new WP_Error( 'wp_livescore_la_invalid_url', __( 'Please set SofaScore import targets in import-api/sofascore.php.', 'wp-livescore-la' ) );
		}

		$response = wp_remote_get(
			$link['url'],
			array(
				'timeout' => 30,
				'headers' => wp_livescore_la_get_sofascore_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		wp_livescore_la_store_last_updater_response( $link['url'], $status_code, $body );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				'wp_livescore_la_http_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: URL. */
					__( 'SofaScore returned HTTP status %1$d. %2$s', 'wp-livescore-la' ),
					$status_code,
					$link['url']
				)
			);
		}

		$payload = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'wp_livescore_la_invalid_json', __( 'SofaScore returned invalid JSON.', 'wp-livescore-la' ) );
		}

		if ( 'seasons' === $link['type'] ) {
			$result = wp_livescore_la_import_sofascore_seasons( $payload, $link['target'] );
		} elseif ( 'players' === $link['type'] ) {
			$result = wp_livescore_la_import_sofascore_players( $payload, $link['target'] );
		} elseif ( 'matches' === $link['type'] ) {
			$records = wp_livescore_la_extract_sofascore_match_records( $payload );
			if ( empty( $records ) ) {
				$total['skipped']++;
				continue;
			}

			$result = wp_livescore_la_import_matches( $records, 'sofascore' );
		} elseif ( 'teams' === $link['type'] ) {
			$records = wp_livescore_la_extract_team_records( $payload );
			if ( empty( $records ) ) {
				$total['skipped']++;
				continue;
			}

			$result = wp_livescore_la_import_teams( $records, 'sofascore' );
		} else {
			$records = wp_livescore_la_extract_sofascore_league_records( $payload );
			if ( empty( $records ) ) {
				$total['skipped']++;
				continue;
			}

			$result = wp_livescore_la_import_leagues( $records, '', '', 'sofascore' );
		}

		$total['created'] += (int) $result['created'];
		$total['updated'] += (int) $result['updated'];
		$total['skipped'] += (int) $result['skipped'];
		$total['fetched']++;
	}

	return $total;
}

/**
 * Import paged SofaScore match endpoints until hasNextPage is false.
 *
 * @param string $target_template Target containing {loop}.
 * @return array
 */
function wp_livescore_la_import_sofascore_paged_matches( $target_template ) {
	$total = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'fetched' => 0,
	);

	for ( $page_index = 0; $page_index < 100; $page_index++ ) {
		$target = str_replace( '{loop}', rawurlencode( (string) $page_index ), $target_template );
		$url    = wp_livescore_la_resolve_sofascore_target_url( $target );

		if ( '' === $url ) {
			$total['skipped']++;
			break;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => wp_livescore_la_get_sofascore_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$total['skipped']++;
			break;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		wp_livescore_la_store_last_updater_response( $url, $status_code, $body );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$total['skipped']++;
			break;
		}

		$payload = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
			$total['skipped']++;
			break;
		}

		$records = wp_livescore_la_extract_sofascore_match_records( $payload );
		if ( empty( $records ) ) {
			$total['skipped']++;
		} else {
			$result = wp_livescore_la_import_matches( $records, 'sofascore' );

			$total['created'] += (int) $result['created'];
			$total['updated'] += (int) $result['updated'];
			$total['skipped'] += (int) $result['skipped'];
		}

		$total['fetched']++;

		if ( ! wp_livescore_la_sofascore_has_next_page( $payload ) ) {
			break;
		}
	}

	return $total;
}

/**
 * Decide whether a SofaScore paged response has another page.
 *
 * @param array $payload Decoded response.
 * @return bool
 */
function wp_livescore_la_sofascore_has_next_page( $payload ) {
	if ( isset( $payload['hasNextPage'] ) ) {
		return (bool) $payload['hasNextPage'];
	}

	if ( isset( $payload['pagination']['hasNextPage'] ) ) {
		return (bool) $payload['pagination']['hasNextPage'];
	}

	return false;
}

/**
 * Import SofaScore squads for Teams with Team API IDs.
 *
 * @param string $target_template Target containing {team_api_id}.
 * @return array
 */
function wp_livescore_la_import_sofascore_players_for_all_teams( $target_template ) {
	$total = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'fetched' => 0,
	);

	$team_ids = get_posts(
		array(
			'post_type'      => 'team',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_team_api_id',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( empty( $team_ids ) ) {
		$total['skipped']++;
		return $total;
	}

	/**
	 * Get the custom import queue table name.
	 *
	 * @return string
	 */
	function wp_livescore_la_import_queue_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wp_livescore_la_import_queue';
	}

	/**
	 * Create or update the custom import queue table.
	 */
	function wp_livescore_la_install_import_queue_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = wp_livescore_la_import_queue_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			provider varchar(50) NOT NULL DEFAULT '',
			job_type varchar(50) NOT NULL DEFAULT '',
			team_id bigint(20) unsigned NOT NULL DEFAULT 0,
			team_api_id varchar(100) NOT NULL DEFAULT '',
			target_template text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts int(10) unsigned NOT NULL DEFAULT 0,
			message text NULL,
			created_count int(10) unsigned NOT NULL DEFAULT 0,
			updated_count int(10) unsigned NOT NULL DEFAULT 0,
			skipped_count int(10) unsigned NOT NULL DEFAULT 0,
			fetched_count int(10) unsigned NOT NULL DEFAULT 0,
			available_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			started_at datetime NULL,
			finished_at datetime NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY status_available (status, available_at),
			KEY job_lookup (provider, job_type, team_id),
			KEY team_api_id (team_api_id)
		) {$charset_collate};";

		dbDelta( $sql );
		update_option( 'wp_livescore_la_import_queue_db_version', '1.0.0', false );
	}

	/**
	 * Ensure the import queue table exists after updates.
	 */
	function wp_livescore_la_maybe_install_import_queue_table() {
		if ( '1.0.0' === get_option( 'wp_livescore_la_import_queue_db_version' ) ) {
			return;
		}

		wp_livescore_la_install_import_queue_table();
	}
	add_action( 'init', 'wp_livescore_la_maybe_install_import_queue_table', 20 );

	/**
	 * Queue SofaScore player imports for Teams with Team API IDs.
	 *
	 * @param string $target_template Target containing {team_api_id}.
	 * @return array
	 */
	function wp_livescore_la_queue_sofascore_players_for_all_teams( $target_template ) {
		$total = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'fetched' => 0,
			'queued'  => 0,
		);

		wp_livescore_la_maybe_install_import_queue_table();

		$team_ids = get_posts(
			array(
				'post_type'      => 'team',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_team_api_id',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( empty( $team_ids ) ) {
			$total['skipped']++;
			return $total;
		}

		foreach ( $team_ids as $team_id ) {
			$team_api_id = get_post_meta( (int) $team_id, '_team_api_id', true );

			if ( '' === $team_api_id ) {
				$total['skipped']++;
				continue;
			}

			$queued = wp_livescore_la_enqueue_sofascore_player_import_job( (int) $team_id, $team_api_id, $target_template );

			if ( $queued ) {
				$total['queued']++;
			} else {
				$total['skipped']++;
			}
		}

		if ( $total['queued'] > 0 ) {
			wp_livescore_la_schedule_import_queue_processing();
		}

		return $total;
	}

	/**
	 * Add one SofaScore player import job to the queue.
	 *
	 * @param int    $team_id         Team post ID.
	 * @param string $team_api_id     Team API ID.
	 * @param string $target_template Target containing {team_api_id}.
	 * @return bool
	 */
	function wp_livescore_la_enqueue_sofascore_player_import_job( $team_id, $team_api_id, $target_template ) {
		global $wpdb;

		$table_name      = wp_livescore_la_import_queue_table_name();
		$team_api_id     = sanitize_text_field( (string) $team_api_id );
		$target_template = sanitize_text_field( (string) $target_template );
		$now             = current_time( 'mysql', true );

		if ( $team_id <= 0 || '' === $team_api_id || '' === $target_template ) {
			return false;
		}

		$existing_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE provider = %s AND job_type = %s AND team_id = %d AND team_api_id = %s AND target_template = %s AND status IN ('pending', 'processing') ORDER BY id DESC LIMIT 1",
				'sofascore',
				'players',
				$team_id,
				$team_api_id,
				$target_template
			)
		);

		if ( $existing_id > 0 ) {
			$updated = $wpdb->update(
				$table_name,
				array(
					'status'       => 'pending',
					'available_at' => $now,
					'updated_at'   => $now,
				),
				array( 'id' => $existing_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			return false !== $updated;
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'provider'        => 'sofascore',
				'job_type'        => 'players',
				'team_id'         => $team_id,
				'team_api_id'     => $team_api_id,
				'target_template' => $target_template,
				'status'          => 'pending',
				'available_at'    => $now,
				'created_at'      => $now,
				'updated_at'      => $now,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $inserted;
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
	 * Check if pending import jobs remain.
	 *
	 * @return bool
	 */
	function wp_livescore_la_import_queue_has_pending_jobs() {
		global $wpdb;

		$table_name = wp_livescore_la_import_queue_table_name();
		$count      = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND available_at <= %s",
				'pending',
				current_time( 'mysql', true )
			)
		);

		return $count > 0;
	}

	/**
	 * Get queue counts grouped by status for admin display.
	 *
	 * @return array
	 */
	function wp_livescore_la_get_import_queue_counts() {
		global $wpdb;

		wp_livescore_la_maybe_install_import_queue_table();

		$table_name = wp_livescore_la_import_queue_table_name();
		$counts     = array(
			'pending'    => 0,
			'processing' => 0,
			'done'       => 0,
			'failed'     => 0,
		);

		$rows = $wpdb->get_results(
			"SELECT status, COUNT(*) AS total FROM {$table_name} GROUP BY status",
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			$status = isset( $row['status'] ) ? sanitize_key( $row['status'] ) : '';
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ] = isset( $row['total'] ) ? absint( $row['total'] ) : 0;
			}
		}

		return $counts;
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
	 * Process queued SofaScore player imports.
	 *
	 * @param int $limit Batch size.
	 */
	function wp_livescore_la_process_sofascore_player_queue_batch( $limit = 5 ) {
		global $wpdb;

		$table_name = wp_livescore_la_import_queue_table_name();
		$jobs       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE provider = %s AND job_type = %s AND status = %s AND available_at <= %s ORDER BY id ASC LIMIT %d",
				'sofascore',
				'players',
				'pending',
				current_time( 'mysql', true ),
				max( 1, (int) $limit )
			),
			ARRAY_A
		);

		foreach ( $jobs as $job ) {
			wp_livescore_la_process_sofascore_player_queue_job( $job );
		}
	}

	/**
	 * Process one queued SofaScore player import job.
	 *
	 * @param array $job Queue row.
	 */
	function wp_livescore_la_process_sofascore_player_queue_job( $job ) {
		global $wpdb;

		$table_name = wp_livescore_la_import_queue_table_name();
		$job_id     = isset( $job['id'] ) ? (int) $job['id'] : 0;
		$team_id    = isset( $job['team_id'] ) ? (int) $job['team_id'] : 0;
		$team_api_id = isset( $job['team_api_id'] ) ? sanitize_text_field( $job['team_api_id'] ) : '';
		$attempts   = isset( $job['attempts'] ) ? (int) $job['attempts'] + 1 : 1;
		$now        = current_time( 'mysql', true );

		if ( $job_id <= 0 ) {
			return;
		}

		$wpdb->update(
			$table_name,
			array(
				'status'     => 'processing',
				'attempts'   => $attempts,
				'started_at' => $now,
				'updated_at' => $now,
			),
			array( 'id' => $job_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( $team_id <= 0 || 'team' !== get_post_type( $team_id ) || '' === $team_api_id ) {
			wp_livescore_la_finish_import_queue_job( $job_id, 'failed', array(), __( 'Missing Team or Team API ID.', 'wp-livescore-la' ) );
			return;
		}

		$target = str_replace( '{team_api_id}', rawurlencode( $team_api_id ), isset( $job['target_template'] ) ? $job['target_template'] : '' );
		$url    = wp_livescore_la_resolve_sofascore_target_url( $target );

		if ( '' === $url ) {
			wp_livescore_la_finish_import_queue_job( $job_id, 'failed', array(), __( 'Invalid SofaScore squad URL.', 'wp-livescore-la' ) );
			return;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => wp_livescore_la_get_sofascore_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_livescore_la_retry_or_fail_import_queue_job( $job, $attempts, $response->get_error_message() );
			return;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		wp_livescore_la_store_last_updater_response( $url, $status_code, $body );

		if ( $status_code < 200 || $status_code >= 300 ) {
			wp_livescore_la_retry_or_fail_import_queue_job(
				$job,
				$attempts,
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'SofaScore returned HTTP status %d.', 'wp-livescore-la' ),
					$status_code
				)
			);
			return;
		}

		$payload = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
			wp_livescore_la_retry_or_fail_import_queue_job( $job, $attempts, __( 'SofaScore returned invalid JSON.', 'wp-livescore-la' ) );
			return;
		}

		$records = wp_livescore_la_extract_sofascore_player_records( $payload );
		$result  = empty( $records )
			? array( 'created' => 0, 'updated' => 0, 'skipped' => 1 )
			: wp_livescore_la_import_players( $records, $team_id, 'sofascore' );

		wp_livescore_la_finish_import_queue_job( $job_id, 'done', $result, __( 'Player import complete.', 'wp-livescore-la' ) );
	}

	/**
	 * Retry a queue job or mark it failed after enough attempts.
	 *
	 * @param array  $job      Queue row.
	 * @param int    $attempts Current attempt count.
	 * @param string $message  Failure message.
	 */
	function wp_livescore_la_retry_or_fail_import_queue_job( $job, $attempts, $message ) {
		global $wpdb;

		$table_name = wp_livescore_la_import_queue_table_name();
		$job_id     = isset( $job['id'] ) ? (int) $job['id'] : 0;
		$attempts   = max( 1, (int) $attempts );
		$now        = current_time( 'mysql', true );

		if ( $job_id <= 0 ) {
			return;
		}

		if ( $attempts >= 3 ) {
			wp_livescore_la_finish_import_queue_job( $job_id, 'failed', array(), $message );
			return;
		}

		$wpdb->update(
			$table_name,
			array(
				'status'       => 'pending',
				'attempts'     => $attempts,
				'message'      => sanitize_text_field( $message ),
				'available_at' => gmdate( 'Y-m-d H:i:s', time() + ( 5 * MINUTE_IN_SECONDS ) ),
				'updated_at'   => $now,
			),
			array( 'id' => $job_id ),
			array( '%s', '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Mark a queue job complete or failed.
	 *
	 * @param int    $job_id  Queue ID.
	 * @param string $status  Final status.
	 * @param array  $result  Import counts.
	 * @param string $message Status message.
	 */
	function wp_livescore_la_finish_import_queue_job( $job_id, $status, $result = array(), $message = '' ) {
		global $wpdb;

		$table_name = wp_livescore_la_import_queue_table_name();
		$now        = current_time( 'mysql', true );

		$wpdb->update(
			$table_name,
			array(
				'status'        => sanitize_key( $status ),
				'message'       => sanitize_text_field( $message ),
				'created_count' => isset( $result['created'] ) ? absint( $result['created'] ) : 0,
				'updated_count' => isset( $result['updated'] ) ? absint( $result['updated'] ) : 0,
				'skipped_count' => isset( $result['skipped'] ) ? absint( $result['skipped'] ) : 0,
				'fetched_count' => 1,
				'finished_at'   => $now,
				'updated_at'    => $now,
			),
			array( 'id' => absint( $job_id ) ),
			array( '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	foreach ( $team_ids as $team_id ) {
		$team_api_id = get_post_meta( (int) $team_id, '_team_api_id', true );

		if ( '' === $team_api_id ) {
			$total['skipped']++;
			continue;
		}

		$target = str_replace( '{team_api_id}', rawurlencode( $team_api_id ), $target_template );
		$url    = wp_livescore_la_resolve_sofascore_target_url( $target );

		if ( '' === $url ) {
			$total['skipped']++;
			continue;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => wp_livescore_la_get_sofascore_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$total['skipped']++;
			continue;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		wp_livescore_la_store_last_updater_response( $url, $status_code, $body );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$total['skipped']++;
			continue;
		}

		$payload = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
			$total['skipped']++;
			continue;
		}

		$records = wp_livescore_la_extract_sofascore_player_records( $payload );
		if ( empty( $records ) ) {
			$total['skipped']++;
		} else {
			$result = wp_livescore_la_import_players( $records, (int) $team_id, 'sofascore' );

			$total['created'] += (int) $result['created'];
			$total['updated'] += (int) $result['updated'];
			$total['skipped'] += (int) $result['skipped'];
		}

		$total['fetched']++;
	}

	return $total;
}

/**
 * Import a direct SofaScore squad target.
 *
 * @param array  $payload Decoded squad payload.
 * @param string $target  Request target.
 * @return array
 */
function wp_livescore_la_import_sofascore_players( $payload, $target ) {
	$team_api_id = wp_livescore_la_get_query_arg_from_target( $target, 'teamId' );
	$team_id     = '' !== $team_api_id ? wp_livescore_la_find_team_post( $team_api_id, '' ) : 0;

	if ( $team_id <= 0 ) {
		return array( 'created' => 0, 'updated' => 0, 'skipped' => 1 );
	}

	return wp_livescore_la_import_players( wp_livescore_la_extract_sofascore_player_records( $payload ), $team_id, 'sofascore' );
}

/**
 * Extract SofaScore squad players into Player import records.
 *
 * @param mixed $payload Decoded payload.
 * @return array
 */
function wp_livescore_la_extract_sofascore_player_records( $payload ) {
	$items   = isset( $payload['players'] ) && is_array( $payload['players'] ) ? $payload['players'] : array();
	$records = array();

	foreach ( $items as $item ) {
		$player = isset( $item['player'] ) && is_array( $item['player'] ) ? $item['player'] : array();

		if ( empty( $player['name'] ) ) {
			continue;
		}

		$country = isset( $player['country'] ) && is_array( $player['country'] ) ? $player['country'] : array();

		$records[] = array(
			'name'           => sanitize_text_field( $player['name'] ),
			'jersey'         => isset( $player['jerseyNumber'] ) ? sanitize_text_field( (string) $player['jerseyNumber'] ) : '',
			'api_id'         => isset( $player['id'] ) ? sanitize_text_field( (string) $player['id'] ) : '',
			'country'        => isset( $country['name'] ) ? sanitize_text_field( $country['name'] ) : '',
			'birthday'       => isset( $player['dateOfBirth'] ) ? wp_livescore_la_normalize_sofascore_player_birthday( $player['dateOfBirth'] ) : '',
			'preferred_foot' => isset( $player['preferredFoot'] ) ? sanitize_text_field( (string) $player['preferredFoot'] ) : '',
			'height'         => isset( $player['height'] ) ? sanitize_text_field( (string) $player['height'] ) : '',
			'weight'         => isset( $player['weight'] ) ? sanitize_text_field( (string) $player['weight'] ) : '',
			'gender'         => isset( $player['gender'] ) ? sanitize_text_field( (string) $player['gender'] ) : '',
			'position'       => isset( $player['position'] ) ? sanitize_text_field( (string) $player['position'] ) : '',
		);
	}

	return $records;
}

/**
 * Normalize SofaScore date of birth values for Player Profile date fields.
 *
 * @param mixed $birthday Date string or Unix timestamp.
 * @return string
 */
function wp_livescore_la_normalize_sofascore_player_birthday( $birthday ) {
	if ( is_numeric( $birthday ) && (int) $birthday > 0 ) {
		return gmdate( 'Y-m-d', (int) $birthday );
	}

	$birthday = sanitize_text_field( (string) $birthday );

	return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $birthday ) ? $birthday : '';
}

/**
 * Extract and normalize SofaScore tournament-like records.
 *
 * @param mixed $payload Decoded JSON payload.
 * @return array
 */
function wp_livescore_la_extract_sofascore_league_records( $payload ) {
	$items = wp_livescore_la_find_sofascore_items( $payload );
	$records = array();

	foreach ( $items as $item ) {
		$record = wp_livescore_la_normalize_sofascore_league_record( $item );
		if ( ! empty( $record ) ) {
			$records[] = $record;
		}
	}

	return $records;
}

/**
 * Extract and normalize SofaScore event records for Match imports.
 *
 * @param mixed $payload Decoded JSON payload.
 * @return array
 */
function wp_livescore_la_extract_sofascore_match_records( $payload ) {
	$items = wp_livescore_la_find_sofascore_match_items( $payload );
	$records = array();

	foreach ( $items as $item ) {
		$record = wp_livescore_la_normalize_sofascore_match_record( $item );
		if ( ! empty( $record ) ) {
			$records[] = $record;
		}
	}

	return $records;
}

/**
 * Find event-like items inside a SofaScore payload.
 *
 * @param mixed $value Payload fragment.
 * @return array
 */
function wp_livescore_la_find_sofascore_match_items( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$items = array();

	if ( isset( $value['event'] ) && is_array( $value['event'] ) ) {
		$items[] = $value['event'];
	}

	if ( isset( $value['id'], $value['homeTeam'], $value['awayTeam'] ) ) {
		$items[] = $value;
	}

	foreach ( array( 'events', 'matches', 'results', 'data', 'items' ) as $key ) {
		if ( isset( $value[ $key ] ) && is_array( $value[ $key ] ) ) {
			foreach ( $value[ $key ] as $child ) {
				$items = array_merge( $items, wp_livescore_la_find_sofascore_match_items( $child ) );
			}
		}
	}

	return $items;
}

/**
 * Normalize a SofaScore event into the shared Match importer shape.
 *
 * @param array $item SofaScore event.
 * @return array
 */
function wp_livescore_la_normalize_sofascore_match_record( $item ) {
	if ( ! is_array( $item ) || empty( $item['id'] ) ) {
		return array();
	}

	$home_team  = isset( $item['homeTeam'] ) && is_array( $item['homeTeam'] ) ? $item['homeTeam'] : array();
	$away_team  = isset( $item['awayTeam'] ) && is_array( $item['awayTeam'] ) ? $item['awayTeam'] : array();
	$tournament = isset( $item['tournament'] ) && is_array( $item['tournament'] ) ? $item['tournament'] : array();
	$season     = isset( $item['season'] ) && is_array( $item['season'] ) ? $item['season'] : array();
	$status     = isset( $item['status'] ) && is_array( $item['status'] ) ? $item['status'] : array();
	$home_score = isset( $item['homeScore'] ) && is_array( $item['homeScore'] ) ? $item['homeScore'] : array();
	$away_score = isset( $item['awayScore'] ) && is_array( $item['awayScore'] ) ? $item['awayScore'] : array();
	$category   = isset( $tournament['category'] ) && is_array( $tournament['category'] ) ? $tournament['category'] : array();
	$sport      = isset( $category['sport'] ) && is_array( $category['sport'] ) ? $category['sport'] : array();
	$venue      = isset( $item['venue'] ) && is_array( $item['venue'] ) ? $item['venue'] : array();
	$group      = isset( $item['group'] ) && is_array( $item['group'] ) ? $item['group'] : array();

	$home_name = isset( $home_team['name'] ) ? sanitize_text_field( $home_team['name'] ) : '';
	$away_name = isset( $away_team['name'] ) ? sanitize_text_field( $away_team['name'] ) : '';

	if ( '' === $home_name || '' === $away_name ) {
		return array();
	}

	if ( wp_livescore_la_sofascore_team_name_has_number( $home_name ) || wp_livescore_la_sofascore_team_name_has_number( $away_name ) ) {
		return array();
	}

	$skip_country = wp_livescore_la_sofascore_is_placeholder_team_name( $home_name ) || wp_livescore_la_sofascore_is_placeholder_team_name( $away_name );
	$timestamp = isset( $item['startTimestamp'] ) ? (int) $item['startTimestamp'] : 0;
	$date      = $timestamp > 0 ? gmdate( 'Y-m-d', $timestamp ) : '';
	$time      = $timestamp > 0 ? gmdate( 'H:i:s', $timestamp ) : '';
	$datetime  = $timestamp > 0 ? gmdate( 'Y-m-d H:i:s', $timestamp ) : '';
	$sport_slug = isset( $sport['slug'] ) && '' !== $sport['slug'] ? sanitize_title( $sport['slug'] ) : sanitize_title( isset( $sport['name'] ) ? $sport['name'] : '' );
	$sportscore_slug = wp_livescore_la_sanitize_sportscore_slug(
		implode(
			'/',
			array_filter(
				array(
					$sport_slug,
					sanitize_title( $home_name ) . '-vs-' . sanitize_title( $away_name ),
				)
			)
		)
	);

	return array(
		'api_id'             => sanitize_text_field( (string) $item['id'] ),
		'sportscore_slug'    => $sportscore_slug,
		'strEvent'           => $home_name . ' vs ' . $away_name,
		'strSport'           => isset( $sport['name'] ) ? sanitize_text_field( $sport['name'] ) : '',
		'strCountry'         => ! $skip_country && isset( $category['name'] ) ? sanitize_text_field( $category['name'] ) : '',
		'strCountryCode'     => ! $skip_country && isset( $category['alpha2'] ) ? sanitize_text_field( $category['alpha2'] ) : '',
		'skipMatchCountry'   => $skip_country ? '1' : '',
		'idLeague'           => isset( $tournament['uniqueTournament']['id'] ) ? sanitize_text_field( (string) $tournament['uniqueTournament']['id'] ) : ( isset( $tournament['id'] ) ? sanitize_text_field( (string) $tournament['id'] ) : '' ),
		'strLeague'          => isset( $tournament['name'] ) ? sanitize_text_field( $tournament['name'] ) : '',
		'strSeason'          => wp_livescore_la_sofascore_season_name( $season ),
		'strHomeTeam'        => $home_name,
		'idHomeTeam'         => isset( $home_team['id'] ) ? sanitize_text_field( (string) $home_team['id'] ) : '',
		'strAwayTeam'        => $away_name,
		'idAwayTeam'         => isset( $away_team['id'] ) ? sanitize_text_field( (string) $away_team['id'] ) : '',
		'dateEvent'          => $date,
		'strTime'            => $time,
		'strTimestamp'       => $datetime,
		'timezone'           => 'UTC',
		'groupName'          => wp_livescore_la_sofascore_match_group_name( $item, $group ),
		'strVenue'           => isset( $venue['name'] ) ? sanitize_text_field( $venue['name'] ) : '',
		'intHomeScore'       => isset( $home_score['current'] ) ? sanitize_text_field( (string) $home_score['current'] ) : '',
		'intAwayScore'       => isset( $away_score['current'] ) ? sanitize_text_field( (string) $away_score['current'] ) : '',
		'strStatus'          => wp_livescore_la_normalize_sofascore_match_status( $status ),
	);
}

/**
 * Check SofaScore placeholder team labels used before a team is known.
 *
 * @param string $name Team name.
 * @return bool
 */
function wp_livescore_la_sofascore_is_placeholder_team_name( $name ) {
	return 'W*' === strtoupper( trim( (string) $name ) );
}

/**
 * Check placeholder SofaScore team names which still contain a draw number.
 *
 * @param string $name Team name.
 * @return bool
 */
function wp_livescore_la_sofascore_team_name_has_number( $name ) {
	return 1 === preg_match( '/\d/', (string) $name );
}

/**
 * Read a Group Name from common SofaScore event group shapes.
 *
 * @param array $item  SofaScore event.
 * @param array $group Event group.
 * @return string
 */
function wp_livescore_la_sofascore_match_group_name( $item, $group ) {
	foreach ( array( 'groupName', 'name' ) as $key ) {
		if ( isset( $group[ $key ] ) && '' !== (string) $group[ $key ] ) {
			return sanitize_text_field( (string) $group[ $key ] );
		}
	}

	foreach ( array( 'groupName', 'strGroup' ) as $key ) {
		if ( isset( $item[ $key ] ) && '' !== (string) $item[ $key ] ) {
			return sanitize_text_field( (string) $item[ $key ] );
		}
	}

	return '';
}

/**
 * Normalize SofaScore event status into plugin match statuses.
 *
 * @param array $status SofaScore status object.
 * @return string
 */
function wp_livescore_la_normalize_sofascore_match_status( $status ) {
	$type        = isset( $status['type'] ) ? sanitize_key( $status['type'] ) : '';
	$description = isset( $status['description'] ) ? strtolower( sanitize_text_field( $status['description'] ) ) : '';

	if ( in_array( $type, array( 'inprogress', 'live' ), true ) ) {
		return 'live';
	}

	if ( in_array( $type, array( 'finished', 'ended' ), true ) ) {
		return 'fulltime';
	}

	if ( false !== strpos( $description, 'postponed' ) ) {
		return 'postponed';
	}

	if ( false !== strpos( $description, 'cancel' ) ) {
		return 'cancelled';
	}

	if ( false !== strpos( $description, 'abandon' ) ) {
		return 'abandoned';
	}

	if ( false !== strpos( $description, 'delay' ) ) {
		return 'delayed';
	}

	if ( false !== strpos( $description, 'half' ) ) {
		return 'halftime';
	}

	return 'scheduled';
}

/**
 * Import SofaScore seasons for every imported SofaScore League.
 *
 * @param string $target_template Target containing {api_id}.
 * @return array
 */
function wp_livescore_la_import_sofascore_seasons_for_all_leagues( $target_template ) {
	$total = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'fetched' => 0,
	);

	$league_ids = get_posts(
		array(
			'post_type'      => 'league',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => WP_LIVESCORE_LA_META_PREFIX . 'api_source',
					'value' => 'sofascore',
				),
				array(
					'key'     => WP_LIVESCORE_LA_META_PREFIX . 'api_id',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( empty( $league_ids ) ) {
		$total['skipped']++;
		return $total;
	}

	foreach ( $league_ids as $league_id ) {
		$api_id = get_post_meta( (int) $league_id, WP_LIVESCORE_LA_META_PREFIX . 'api_id', true );

		if ( '' === $api_id ) {
			$total['skipped']++;
			continue;
		}

		$target = str_replace( '{api_id}', rawurlencode( $api_id ), $target_template );
		$url    = wp_livescore_la_resolve_sofascore_target_url( $target );

		if ( '' === $url ) {
			$total['skipped']++;
			continue;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => wp_livescore_la_get_sofascore_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			$total['skipped']++;
			continue;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		wp_livescore_la_store_last_updater_response( $url, $status_code, $body );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$total['skipped']++;
			continue;
		}

		$payload = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$total['skipped']++;
			continue;
		}

		$result = wp_livescore_la_import_sofascore_seasons_for_league( $payload, (int) $league_id );

		$total['created'] += (int) $result['created'];
		$total['updated'] += (int) $result['updated'];
		$total['skipped'] += (int) $result['skipped'];
		$total['fetched']++;
	}

	return $total;
}

/**
 * Import SofaScore seasons for a tournament target.
 *
 * @param array  $payload Decoded JSON payload.
 * @param string $target  Import target.
 * @return array
 */
function wp_livescore_la_import_sofascore_seasons( $payload, $target ) {
	$result = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
	);

	$tournament_id = wp_livescore_la_get_query_arg_from_target( $target, 'tournamentId' );

	if ( '' === $tournament_id ) {
		$result['skipped']++;
		return $result;
	}

	$league_id = wp_livescore_la_find_league_post( $tournament_id, '' );

	if ( $league_id <= 0 ) {
		$result['skipped']++;
		return $result;
	}

	return wp_livescore_la_import_sofascore_seasons_for_league( $payload, $league_id );
}

/**
 * Import SofaScore seasons for one League.
 *
 * @param array $payload   Decoded JSON payload.
 * @param int   $league_id League post ID.
 * @return array
 */
function wp_livescore_la_import_sofascore_seasons_for_league( $payload, $league_id ) {
	$result = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
	);

	$seasons = wp_livescore_la_extract_sofascore_season_records( $payload );

	if ( empty( $seasons ) ) {
		$result['skipped']++;
		return $result;
	}

	usort(
		$seasons,
		function ( $a, $b ) {
			return wp_livescore_la_sofascore_season_sort_value( $b ) <=> wp_livescore_la_sofascore_season_sort_value( $a );
		}
	);

	$current_set = false;

	foreach ( $seasons as $season ) {
		$season_name = wp_livescore_la_sofascore_season_name( $season );
		if ( '' === $season_name ) {
			$result['skipped']++;
			continue;
		}

		$slug = sanitize_title( $season_name . ' - ' . get_the_title( $league_id ) );
		$term = get_term_by( 'slug', $slug, 'league_season' );

		wp_livescore_la_sync_league_season_term( $league_id, $season_name, ! $current_set, true );
		$current_set = true;

		if ( $term instanceof WP_Term ) {
			$result['updated']++;
		} else {
			$result['created']++;
		}
	}

	return $result;
}

/**
 * Extract SofaScore season records.
 *
 * @param mixed $payload Decoded JSON payload.
 * @return array
 */
function wp_livescore_la_extract_sofascore_season_records( $payload ) {
	if ( ! is_array( $payload ) ) {
		return array();
	}

	foreach ( array( 'seasons', 'results', 'data', 'items' ) as $key ) {
		if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
			return $payload[ $key ];
		}
	}

	return wp_livescore_la_is_list( $payload ) ? $payload : array();
}

/**
 * Resolve a SofaScore season name.
 *
 * @param array $season Season record.
 * @return string
 */
function wp_livescore_la_sofascore_season_name( $season ) {
	if ( ! is_array( $season ) ) {
		return '';
	}

	foreach ( array( 'name', 'year', 'season', 'displayName' ) as $key ) {
		if ( isset( $season[ $key ] ) && '' !== (string) $season[ $key ] ) {
			return sanitize_text_field( (string) $season[ $key ] );
		}
	}

	return '';
}

/**
 * Get a comparable value for a SofaScore season.
 *
 * @param array $season Season record.
 * @return int
 */
function wp_livescore_la_sofascore_season_sort_value( $season ) {
	if ( ! is_array( $season ) ) {
		return 0;
	}

	foreach ( array( 'year', 'name', 'season', 'displayName' ) as $key ) {
		if ( ! isset( $season[ $key ] ) ) {
			continue;
		}

		preg_match_all( '/\d{2,4}/', (string) $season[ $key ], $matches );
		if ( empty( $matches[0] ) ) {
			continue;
		}

		$years = array_map(
			function ( $year ) {
				$year = (int) $year;
				return $year < 100 ? 2000 + $year : $year;
			},
			$matches[0]
		);

		return max( $years );
	}

	return isset( $season['id'] ) ? (int) $season['id'] : 0;
}

/**
 * Get a query arg from a full or relative target URL.
 *
 * @param string $target Target URL/path.
 * @param string $key    Query key.
 * @return string
 */
function wp_livescore_la_get_query_arg_from_target( $target, $key ) {
	$query = wp_parse_url( $target, PHP_URL_QUERY );

	if ( ! is_string( $query ) || '' === $query ) {
		return '';
	}

	parse_str( $query, $args );

	return isset( $args[ $key ] ) ? sanitize_text_field( (string) $args[ $key ] ) : '';
}

/**
 * Find possible SofaScore league/tournament items inside a payload.
 *
 * @param mixed $value Payload fragment.
 * @return array
 */
function wp_livescore_la_find_sofascore_items( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$items = array();

	if ( isset( $value['entity'] ) && is_array( $value['entity'] ) ) {
		$entity = $value['entity'];
		if ( isset( $value['type'] ) ) {
			$entity['type'] = $value['type'];
		}
		$items[] = $entity;
	}

	if ( isset( $value['uniqueTournament'] ) && is_array( $value['uniqueTournament'] ) ) {
		$items[] = array_merge( $value['uniqueTournament'], array( 'category' => isset( $value['category'] ) ? $value['category'] : array() ) );
	}

	if ( isset( $value['id'], $value['name'] ) && ( isset( $value['category'] ) || isset( $value['sport'] ) || isset( $value['slug'] ) ) && ! isset( $value['entity'] ) ) {
		$items[] = $value;
	}

	foreach ( array( 'uniqueTournaments', 'tournaments', 'leagues', 'data', 'items', 'results' ) as $key ) {
		if ( isset( $value[ $key ] ) && is_array( $value[ $key ] ) ) {
			foreach ( $value[ $key ] as $child ) {
				$items = array_merge( $items, wp_livescore_la_find_sofascore_items( $child ) );
			}
		}
	}

	return $items;
}

/**
 * Normalize a SofaScore item into the shared League importer shape.
 *
 * @param array $item SofaScore item.
 * @return array
 */
function wp_livescore_la_normalize_sofascore_league_record( $item ) {
	$name = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
	$id   = isset( $item['id'] ) ? sanitize_text_field( (string) $item['id'] ) : '';

	if ( '' === $name || '' === $id ) {
		return array();
	}

	$category = isset( $item['category'] ) && is_array( $item['category'] ) ? $item['category'] : array();
	$sport    = isset( $item['sport'] ) && is_array( $item['sport'] ) ? $item['sport'] : array();

	if ( empty( $sport ) && isset( $category['sport'] ) && is_array( $category['sport'] ) ) {
		$sport = $category['sport'];
	}

	return array(
		'name'         => $name,
		'api_id'       => $id,
		'sport'        => isset( $sport['name'] ) ? sanitize_text_field( $sport['name'] ) : '',
		'country'      => isset( $category['name'] ) ? sanitize_text_field( $category['name'] ) : '',
		'country_code' => isset( $category['alpha2'] ) ? sanitize_text_field( $category['alpha2'] ) : '',
		'slug'         => isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '',
		'api_source'   => 'sofascore',
	);
}
