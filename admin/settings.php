<?php
/**
 * Settings page and updater admin actions.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Livescore Settings page under Settings > Livescore.
 */
function wp_livescore_la_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage livescore settings.', 'wp-livescore-la' ) );
	}

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
	if ( ! in_array( $active_tab, array( 'general', 'updater' ), true ) ) {
		$active_tab = 'general';
	}

	$last_response = get_option( 'wp_livescore_la_last_updater_response', array() );
	$sportsdb_links     = wp_livescore_la_get_sportsdb_import_links();
	$sofascore_links    = wp_livescore_la_get_sofascore_import_links();
	?>
	<div class="wrap wp-livescore-la-admin">
		<h1><?php esc_html_e( 'Livescore Settings', 'wp-livescore-la' ); ?></h1>
		<?php wp_livescore_la_render_admin_notices(); ?>

		<nav class="nav-tab-wrapper">
			<a class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-livescore-la-settings&tab=general' ) ); ?>">
				<?php esc_html_e( 'General Settings', 'wp-livescore-la' ); ?>
			</a>
			<a class="nav-tab <?php echo 'updater' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-livescore-la-settings&tab=updater' ) ); ?>">
				<?php esc_html_e( 'Create / Update Livescore Custom Posts', 'wp-livescore-la' ); ?>
			</a>
		</nav>

		<?php if ( 'general' === $active_tab ) : ?>
				<h2><?php esc_html_e( 'Sports Data', 'wp-livescore-la' ); ?></h2>
					<p><?php esc_html_e( 'Permanently delete all League, Team, and Match posts and Season data.', 'wp-livescore-la' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wp_livescore_la_delete_sports_data', 'wp_livescore_la_delete_sports_data_nonce' ); ?>
					<input type="hidden" name="action" value="wp_livescore_la_delete_sports_data" />
					<?php submit_button( __( 'Delete Sports Data', 'wp-livescore-la' ), 'delete', 'submit', false ); ?>
				</form>
			<?php else : ?>
			<h2><?php esc_html_e( 'CSV Team Import', 'wp-livescore-la' ); ?></h2>
			<p><?php esc_html_e( 'Upload a CSV file with headers: team_id, team_name, team_slug.', 'wp-livescore-la' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wp_livescore_la_import_teams_csv', 'wp_livescore_la_import_teams_csv_nonce' ); ?>
				<input type="hidden" name="action" value="wp_livescore_la_import_teams_csv" />
				<input type="file" name="teams_csv" accept=".csv,text/csv" required />
				<?php submit_button( __( 'Import Teams CSV', 'wp-livescore-la' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'SportsDB Imports', 'wp-livescore-la' ); ?></h2>
			<p><?php esc_html_e( 'SportsDB import links are configured in import-api/sportsdb.php.', 'wp-livescore-la' ); ?></p>

			<?php if ( ! empty( $sportsdb_links ) ) : ?>
				<div class="wp-livescore-la-import-buttons">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wp_livescore_la_run_import_api', 'wp_livescore_la_import_api_nonce' ); ?>
						<input type="hidden" name="action" value="wp_livescore_la_run_import_api" />
						<input type="hidden" name="provider" value="sportsdb" />
						<input type="hidden" name="import_key" value="all" />
						<?php submit_button( __( 'Import All SportsDB Links', 'wp-livescore-la' ), 'primary', 'submit', false ); ?>
					</form>

					<?php foreach ( $sportsdb_links as $link ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'wp_livescore_la_run_import_api', 'wp_livescore_la_import_api_nonce' ); ?>
							<input type="hidden" name="action" value="wp_livescore_la_run_import_api" />
							<input type="hidden" name="provider" value="sportsdb" />
							<input type="hidden" name="import_key" value="<?php echo esc_attr( $link['key'] ); ?>" />
							<button type="submit" class="button">
								<?php
								printf(
									/* translators: %s: import label. */
									esc_html__( 'Import %s', 'wp-livescore-la' ),
									esc_html( $link['label'] )
								);
								?>
							</button>
							<?php if ( ! empty( $link['url'] ) ) : ?>
								<code><?php echo esc_html( $link['url'] ); ?></code>
							<?php endif; ?>
						</form>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'SofaScore Imports', 'wp-livescore-la' ); ?></h2>
			<p><?php esc_html_e( 'SofaScore import links are configured in import-api/sofascore.php.', 'wp-livescore-la' ); ?></p>
			<?php if ( function_exists( 'wp_livescore_la_get_import_queue_counts' ) ) : ?>
				<?php $queue_counts = wp_livescore_la_get_import_queue_counts(); ?>
				<p>
					<strong><?php esc_html_e( 'Player Queue:', 'wp-livescore-la' ); ?></strong>
					<?php
					printf(
						/* translators: 1: pending count, 2: processing count, 3: done count, 4: failed count. */
						esc_html__( 'Pending: %1$d. Processing: %2$d. Done: %3$d. Failed: %4$d.', 'wp-livescore-la' ),
						(int) $queue_counts['pending'],
						(int) $queue_counts['processing'],
						(int) $queue_counts['done'],
						(int) $queue_counts['failed']
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $sofascore_links ) ) : ?>
				<div class="wp-livescore-la-import-buttons">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wp_livescore_la_run_import_api', 'wp_livescore_la_import_api_nonce' ); ?>
						<input type="hidden" name="action" value="wp_livescore_la_run_import_api" />
						<input type="hidden" name="provider" value="sofascore" />
						<input type="hidden" name="import_key" value="all" />
						<?php submit_button( __( 'Import All SofaScore Links', 'wp-livescore-la' ), 'primary', 'submit', false ); ?>
					</form>

					<?php foreach ( $sofascore_links as $link ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'wp_livescore_la_run_import_api', 'wp_livescore_la_import_api_nonce' ); ?>
							<input type="hidden" name="action" value="wp_livescore_la_run_import_api" />
							<input type="hidden" name="provider" value="sofascore" />
							<input type="hidden" name="import_key" value="<?php echo esc_attr( $link['key'] ); ?>" />
							<button type="submit" class="button">
								<?php
								printf(
									/* translators: %s: import label. */
									esc_html__( 'Import %s', 'wp-livescore-la' ),
									esc_html( $link['label'] )
								);
								?>
							</button>
							<?php if ( ! empty( $link['url'] ) ) : ?>
								<code><?php echo esc_html( $link['url'] ); ?></code>
							<?php endif; ?>
						</form>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'No SofaScore import targets are configured yet.', 'wp-livescore-la' ); ?></p>
			<?php endif; ?>

				<?php if ( ! empty( $last_response ) ) : ?>
					<h2><?php esc_html_e( 'Last API JSON Response', 'wp-livescore-la' ); ?></h2>
					<p>
						<strong><?php esc_html_e( 'URL:', 'wp-livescore-la' ); ?></strong>
						<code><?php echo esc_html( isset( $last_response['url'] ) ? $last_response['url'] : '' ); ?></code>
					</p>
					<p>
						<strong><?php esc_html_e( 'HTTP Status:', 'wp-livescore-la' ); ?></strong>
						<?php echo esc_html( isset( $last_response['status_code'] ) ? (string) $last_response['status_code'] : '' ); ?>
					</p>
					<textarea class="large-text code wp-livescore-la-json-viewer" rows="18" readonly><?php echo esc_textarea( isset( $last_response['body'] ) ? $last_response['body'] : '' ); ?></textarea>
				<?php endif; ?>
			<?php endif; ?>
	</div>
	<?php
}

/**
 * Store the latest raw API response for admin inspection.
 *
 * @param string $url         Request URL.
 * @param int    $status_code HTTP status code.
 * @param string $body        Raw response body.
 */
function wp_livescore_la_store_last_updater_response( $url, $status_code, $body ) {
	$decoded = json_decode( $body, true );
	if ( JSON_ERROR_NONE === json_last_error() ) {
		$pretty_body = wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	} else {
		$pretty_body = $body;
	}

	update_option(
		'wp_livescore_la_last_updater_response',
		array(
			'url'         => esc_url_raw( $url ),
			'status_code' => (int) $status_code,
			'body'        => is_string( $pretty_body ) ? substr( $pretty_body, 0, 1000000 ) : '',
		),
		false
	);
}

/**
 * Render admin notices from redirected actions.
 */
function wp_livescore_la_render_admin_notices() {
	$notice_code = isset( $_GET['wp_livescore_la_notice'] ) ? sanitize_key( wp_unslash( $_GET['wp_livescore_la_notice'] ) ) : '';
	$message     = isset( $_GET['wp_livescore_la_message'] ) ? sanitize_text_field( wp_unslash( $_GET['wp_livescore_la_message'] ) ) : '';

	if ( '' === $notice_code ) {
		return;
	}

	$type = in_array( $notice_code, array( 'settings_saved', 'sports_data_deleted', 'import_success' ), true ) ? 'success' : 'error';
	if ( '' === $message ) {
		$messages = array(
			'settings_saved' => __( 'Settings saved.', 'wp-livescore-la' ),
			'sports_data_deleted' => __( 'Sports data deleted.', 'wp-livescore-la' ),
			'import_success' => __( 'Livescore posts updated.', 'wp-livescore-la' ),
			'import_error'   => __( 'Unable to update livescore posts.', 'wp-livescore-la' ),
		);
		$message = isset( $messages[ $notice_code ] ) ? $messages[ $notice_code ] : '';
	}

	if ( '' !== $message ) {
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}
}

/**
 * Save General Settings.
 */
function wp_livescore_la_handle_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save livescore settings.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_save_settings', 'wp_livescore_la_settings_nonce' );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                   => 'wp-livescore-la-settings',
				'tab'                    => 'general',
				'wp_livescore_la_notice' => 'settings_saved',
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_post_wp_livescore_la_save_settings', 'wp_livescore_la_handle_save_settings' );

/**
 * Delete all League, Team, Match, and Season data.
 */
function wp_livescore_la_handle_delete_sports_data() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to delete sports data.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_delete_sports_data', 'wp_livescore_la_delete_sports_data_nonce' );

	$deleted_counts = array(
		'league' => 0,
		'team'   => 0,
		'match'  => 0,
	);

	foreach ( array_keys( $deleted_counts ) as $post_type ) {
		$post_ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);

		foreach ( $post_ids as $post_id ) {
			$deleted_post = wp_delete_post( (int) $post_id, true );
			if ( $deleted_post instanceof WP_Post ) {
				$deleted_counts[ $post_type ]++;
			}
		}
	}

	$deleted_seasons = 0;
	$season_terms = get_terms(
		array(
			'taxonomy'   => 'league_season',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( ! is_wp_error( $season_terms ) ) {
		foreach ( $season_terms as $season_term_id ) {
			$deleted_term = wp_delete_term( (int) $season_term_id, 'league_season' );
			if ( true === $deleted_term ) {
				$deleted_seasons++;
			}
		}
	}

	$total_deleted = array_sum( $deleted_counts );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                    => 'wp-livescore-la-settings',
				'tab'                     => 'general',
				'wp_livescore_la_notice'  => 'sports_data_deleted',
				'wp_livescore_la_message' => sprintf(
					/* translators: 1: total posts deleted, 2: leagues deleted, 3: teams deleted, 4: matches deleted, 5: seasons deleted. */
					__( 'Deleted %1$d sports data posts. Leagues: %2$d. Teams: %3$d. Matches: %4$d. Seasons: %5$d.', 'wp-livescore-la' ),
					$total_deleted,
					$deleted_counts['league'],
					$deleted_counts['team'],
					$deleted_counts['match'],
					$deleted_seasons
				),
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_post_wp_livescore_la_delete_sports_data', 'wp_livescore_la_handle_delete_sports_data' );

/**
 * Import Teams from an uploaded CSV file.
 */
function wp_livescore_la_handle_import_teams_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to import teams.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_import_teams_csv', 'wp_livescore_la_import_teams_csv_nonce' );

	if ( empty( $_FILES['teams_csv'] ) || ! is_array( $_FILES['teams_csv'] ) ) {
		wp_livescore_la_redirect_notice( 'import_error', __( 'Please choose a CSV file.', 'wp-livescore-la' ) );
	}

	$file = $_FILES['teams_csv'];
	if ( ! empty( $file['error'] ) ) {
		wp_livescore_la_redirect_notice( 'import_error', __( 'The CSV upload failed.', 'wp-livescore-la' ) );
	}

	$tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
	$name     = isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '';

	if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) || 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
		wp_livescore_la_redirect_notice( 'import_error', __( 'Please upload a valid CSV file.', 'wp-livescore-la' ) );
	}

	$records = wp_livescore_la_read_teams_csv_records( $tmp_name );
	if ( is_wp_error( $records ) ) {
		wp_livescore_la_redirect_notice( 'import_error', $records->get_error_message() );
	}

	if ( empty( $records ) ) {
		wp_livescore_la_redirect_notice( 'import_error', __( 'No valid team rows were found in the CSV.', 'wp-livescore-la' ) );
	}

	$result = wp_livescore_la_import_teams( $records, 'csv' );

	$message = sprintf(
		/* translators: 1: row count, 2: created count, 3: updated count, 4: skipped count. */
		__( 'Teams CSV import complete. Rows: %1$d. Created: %2$d. Updated: %3$d. Skipped: %4$d.', 'wp-livescore-la' ),
		count( $records ),
		$result['created'],
		$result['updated'],
		$result['skipped']
	);

	wp_livescore_la_redirect_notice( 'import_success', $message );
}
add_action( 'admin_post_wp_livescore_la_import_teams_csv', 'wp_livescore_la_handle_import_teams_csv' );

/**
 * Read Team rows from a CSV file.
 *
 * @param string $file_path Uploaded CSV temporary path.
 * @return array|WP_Error
 */
function wp_livescore_la_read_teams_csv_records( $file_path ) {
	$handle = fopen( $file_path, 'r' );
	if ( false === $handle ) {
		return new WP_Error( 'wp_livescore_la_csv_open_failed', __( 'Unable to read the CSV file.', 'wp-livescore-la' ) );
	}

	$header = fgetcsv( $handle );
	if ( ! is_array( $header ) ) {
		fclose( $handle );
		return new WP_Error( 'wp_livescore_la_csv_missing_header', __( 'The CSV file is missing a header row.', 'wp-livescore-la' ) );
	}

	$header = array_map(
		function ( $column ) {
			$column = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $column );
			return sanitize_key( trim( $column ) );
		},
		$header
	);

	$required = array( 'team_id', 'team_name', 'team_slug' );
	foreach ( $required as $column ) {
		if ( ! in_array( $column, $header, true ) ) {
			fclose( $handle );
			return new WP_Error(
				'wp_livescore_la_csv_missing_column',
				sprintf(
					/* translators: %s: required CSV column. */
					__( 'The CSV file is missing the %s column.', 'wp-livescore-la' ),
					$column
				)
			);
		}
	}

	$records = array();
	while ( false !== ( $row = fgetcsv( $handle ) ) ) {
		if ( ! is_array( $row ) || empty( array_filter( $row ) ) ) {
			continue;
		}

		$row = array_pad( $row, count( $header ), '' );
		$data = array_combine( $header, array_slice( $row, 0, count( $header ) ) );
		if ( ! is_array( $data ) ) {
			continue;
		}

		$team_id   = isset( $data['team_id'] ) ? sanitize_text_field( $data['team_id'] ) : '';
		$team_name = isset( $data['team_name'] ) ? sanitize_text_field( $data['team_name'] ) : '';
		$team_slug = isset( $data['team_slug'] ) ? sanitize_title( $data['team_slug'] ) : '';

		if ( '' === $team_id || '' === $team_name ) {
			continue;
		}

		$records[] = array(
			'api_id' => $team_id,
			'name'   => $team_name,
			'slug'   => $team_slug,
		);
	}

	fclose( $handle );

	return $records;
}

/**
 * Run an import API provider.
 */
function wp_livescore_la_handle_run_import_api() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to update livescore posts.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_run_import_api', 'wp_livescore_la_import_api_nonce' );

	$provider   = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
	$import_key = isset( $_POST['import_key'] ) ? sanitize_key( wp_unslash( $_POST['import_key'] ) ) : 'all';

	if ( 'sportsdb' === $provider ) {
		$result = wp_livescore_la_run_sportsdb_import( $import_key );
	} elseif ( 'sofascore' === $provider ) {
		$result = wp_livescore_la_run_sofascore_import( $import_key );
	} else {
		wp_livescore_la_redirect_notice( 'import_error', __( 'The selected import provider is not supported.', 'wp-livescore-la' ) );
	}

	if ( is_wp_error( $result ) ) {
		wp_livescore_la_redirect_notice( 'import_error', $result->get_error_message() );
	}

	$provider_label = 'sofascore' === $provider ? __( 'SofaScore', 'wp-livescore-la' ) : __( 'SportsDB', 'wp-livescore-la' );
	if ( ! empty( $result['queued'] ) ) {
		$message = sprintf(
			/* translators: 1: provider name, 2: queued count, 3: skipped count. */
			__( '%1$s player import queued. Teams queued: %2$d. Skipped: %3$d. The queue will run in small background batches using WP-Cron.', 'wp-livescore-la' ),
			$provider_label,
			$result['queued'],
			$result['skipped']
		);
	} else {
		$message = sprintf(
			/* translators: 1: provider name, 2: fetched count, 3: created count, 4: updated count, 5: skipped count. */
			__( '%1$s import complete. Links fetched: %2$d. Created: %3$d. Updated: %4$d. Skipped: %5$d.', 'wp-livescore-la' ),
			$provider_label,
			$result['fetched'],
			$result['created'],
			$result['updated'],
			$result['skipped']
		);
	}

	wp_livescore_la_redirect_notice( 'import_success', $message );
}
add_action( 'admin_post_wp_livescore_la_run_import_api', 'wp_livescore_la_handle_run_import_api' );

/**
 * Redirect back to the updater tab with a notice.
 *
 * @param string $code    Notice code.
 * @param string $message Notice message.
 */
function wp_livescore_la_redirect_notice( $code, $message ) {
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                    => 'wp-livescore-la-settings',
				'tab'                     => 'updater',
				'wp_livescore_la_notice'  => sanitize_key( $code ),
				'wp_livescore_la_message' => wp_strip_all_tags( $message ),
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
