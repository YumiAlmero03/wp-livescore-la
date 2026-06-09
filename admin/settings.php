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
	$kadario_links      = wp_livescore_la_get_kadario_import_links();
	$kadario_queue      = get_option( 'wp_livescore_la_kadario_prediction_queue_status', array() );
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
				<h2><?php esc_html_e( 'Google Image Search', 'wp-livescore-la' ); ?></h2>
				<p><?php esc_html_e( 'Used by Import Images to find featured images for Players.', 'wp-livescore-la' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wp_livescore_la_save_settings', 'wp_livescore_la_settings_nonce' ); ?>
					<input type="hidden" name="action" value="wp_livescore_la_save_settings" />
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><label for="wp_livescore_la_google_search_api_key"><?php esc_html_e( 'Google Search API Key', 'wp-livescore-la' ); ?></label></th>
								<td>
									<input type="password" id="wp_livescore_la_google_search_api_key" name="wp_livescore_la_google_search_api_key" value="<?php echo esc_attr( get_option( 'wp_livescore_la_google_search_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="wp_livescore_la_google_search_cx"><?php esc_html_e( 'Google Search Engine ID', 'wp-livescore-la' ); ?></label></th>
								<td>
									<input type="text" id="wp_livescore_la_google_search_cx" name="wp_livescore_la_google_search_cx" value="<?php echo esc_attr( get_option( 'wp_livescore_la_google_search_cx', '' ) ); ?>" class="regular-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="wp_livescore_la_website_name_header"><?php esc_html_e( 'X-Website-Name Header', 'wp-livescore-la' ); ?></label></th>
								<td>
									<input type="text" id="wp_livescore_la_website_name_header" name="wp_livescore_la_website_name_header" value="<?php echo esc_attr( get_option( 'wp_livescore_la_website_name_header', 'worldcuppredictnet' ) ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Sent with livescore AI API requests such as /api/teams/groups.', 'wp-livescore-la' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="wp_livescore_la_kadario_url"><?php esc_html_e( 'Kadario API URL', 'wp-livescore-la' ); ?></label></th>
								<td>
									<input type="url" id="wp_livescore_la_kadario_url" name="wp_livescore_la_kadario_url" value="<?php echo esc_attr( get_option( 'wp_livescore_la_kadario_url', '' ) ); ?>" class="regular-text" placeholder="https://livescore-ai-635955947416.asia-east1.run.app" />
									<p class="description"><?php esc_html_e( 'Base URL for Kadario API endpoints.', 'wp-livescore-la' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="wp_livescore_la_kadario_api_key"><?php esc_html_e( 'Kadario API Key', 'wp-livescore-la' ); ?></label></th>
								<td>
									<input type="password" id="wp_livescore_la_kadario_api_key" name="wp_livescore_la_kadario_api_key" value="<?php echo esc_attr( get_option( 'wp_livescore_la_kadario_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
									<p class="description"><?php esc_html_e( 'Used as the Bearer token for Kadario import requests.', 'wp-livescore-la' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="wp_livescore_la_origin_domain"><?php esc_html_e( 'Origin Domain', 'wp-livescore-la' ); ?></label></th>
								<td>
									<input type="text" id="wp_livescore_la_origin_domain" name="wp_livescore_la_origin_domain" value="<?php echo esc_attr( get_option( 'wp_livescore_la_origin_domain', '' ) ); ?>" class="regular-text" placeholder="https://example.com" />
									<p class="description"><?php esc_html_e( 'Sent as the Origin header in Kadario API requests.', 'wp-livescore-la' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button( __( 'Save Settings', 'wp-livescore-la' ), 'primary', 'submit', false ); ?>
				</form>
				<h2><?php esc_html_e( 'Sports Data', 'wp-livescore-la' ); ?></h2>
					<p><?php esc_html_e( 'Permanently delete all League, Team, Player, and Match posts and Season data.', 'wp-livescore-la' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wp_livescore_la_delete_sports_data', 'wp_livescore_la_delete_sports_data_nonce' ); ?>
					<input type="hidden" name="action" value="wp_livescore_la_delete_sports_data" />
					<?php submit_button( __( 'Delete Sports Data', 'wp-livescore-la' ), 'delete', 'submit', false ); ?>
				</form>
				<h2><?php esc_html_e( 'Predictions', 'wp-livescore-la' ); ?></h2>
				<p><?php esc_html_e( 'Permanently delete all Prediction posts imported from Kadario.', 'wp-livescore-la' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wp_livescore_la_empty_predictions', 'wp_livescore_la_empty_predictions_nonce' ); ?>
					<input type="hidden" name="action" value="wp_livescore_la_empty_predictions" />
					<?php submit_button( __( 'Empty Predictions', 'wp-livescore-la' ), 'delete', 'submit', false ); ?>
				</form>
				<h2><?php esc_html_e( 'Images', 'wp-livescore-la' ); ?></h2>
				<p><?php esc_html_e( 'Update featured images for Players, Teams, and Countries. Teams and Countries use flags; Players use Google image search when configured.', 'wp-livescore-la' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wp_livescore_la_update_images', 'wp_livescore_la_update_images_nonce' ); ?>
					<input type="hidden" name="action" value="wp_livescore_la_update_images" />
					<?php submit_button( __( 'Import Images', 'wp-livescore-la' ), 'secondary', 'submit', false ); ?>
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

			<h2><?php esc_html_e( 'Kadario Imports', 'wp-livescore-la' ); ?></h2>
			<p><?php esc_html_e( 'Kadario imports AI-generated Match updates, linked Team data, Players, and Prediction posts. Import links are configured in import-api/kadario.php.', 'wp-livescore-la' ); ?></p>
			<?php if ( is_array( $kadario_queue ) && ! empty( $kadario_queue ) ) : ?>
				<p>
					<strong><?php esc_html_e( 'Prediction Cron Queue:', 'wp-livescore-la' ); ?></strong>
					<?php
					printf(
						/* translators: 1: total queued count, 2: pending count, 3: created count, 4: updated count, 5: skipped count. */
						esc_html__( 'Total: %1$d. Pending: %2$d. Created: %3$d. Updated: %4$d. Skipped: %5$d.', 'wp-livescore-la' ),
						(int) ( isset( $kadario_queue['total'] ) ? $kadario_queue['total'] : 0 ),
						(int) ( isset( $kadario_queue['pending'] ) ? $kadario_queue['pending'] : 0 ),
						(int) ( isset( $kadario_queue['created'] ) ? $kadario_queue['created'] : 0 ),
						(int) ( isset( $kadario_queue['updated'] ) ? $kadario_queue['updated'] : 0 ),
						(int) ( isset( $kadario_queue['skipped'] ) ? $kadario_queue['skipped'] : 0 )
					);
					?>
					<?php if ( ! empty( $kadario_queue['last_run'] ) ) : ?>
						<?php
						printf(
							/* translators: %s: last run time. */
							esc_html__( 'Last run: %s.', 'wp-livescore-la' ),
							esc_html( $kadario_queue['last_run'] )
						);
						?>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wp-livescore-la-inline-form">
				<?php wp_nonce_field( 'wp_livescore_la_create_kadario_sample_prediction', 'wp_livescore_la_kadario_sample_nonce' ); ?>
				<input type="hidden" name="action" value="wp_livescore_la_create_kadario_sample_prediction" />
				<?php submit_button( __( 'Create Sample', 'wp-livescore-la' ), 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( ! empty( $kadario_links ) ) : ?>
				<div class="wp-livescore-la-import-buttons">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wp_livescore_la_run_import_api', 'wp_livescore_la_import_api_nonce' ); ?>
						<input type="hidden" name="action" value="wp_livescore_la_run_import_api" />
						<input type="hidden" name="provider" value="kadario" />
						<input type="hidden" name="import_key" value="all" />
						<?php submit_button( __( 'Import All Kadario Links', 'wp-livescore-la' ), 'primary', 'submit', false ); ?>
					</form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wp_livescore_la_run_import_api', 'wp_livescore_la_import_api_nonce' ); ?>
						<input type="hidden" name="action" value="wp_livescore_la_run_import_api" />
						<input type="hidden" name="provider" value="kadario" />
						<input type="hidden" name="import_key" value="all" />
						<input type="hidden" name="import_mode" value="cron_predictions" />
						<?php submit_button( __( 'Import Predictions', 'wp-livescore-la' ), 'secondary', 'submit', false ); ?>
					</form>

					<?php foreach ( $kadario_links as $link ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'wp_livescore_la_run_import_api', 'wp_livescore_la_import_api_nonce' ); ?>
							<input type="hidden" name="action" value="wp_livescore_la_run_import_api" />
							<input type="hidden" name="provider" value="kadario" />
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
				<p><?php esc_html_e( 'No Kadario import targets are configured yet.', 'wp-livescore-la' ); ?></p>
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

	if ( (int) $status_code >= 400 && function_exists( 'wp_livescore_la_log' ) ) {
		wp_livescore_la_log(
			'api_response',
			'API request returned an error status.',
			array(
				'url'         => esc_url_raw( $url ),
				'status_code' => (int) $status_code,
				'body_sample'  => substr( wp_strip_all_tags( (string) $body ), 0, 500 ),
			)
		);
	}
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

	$type = in_array( $notice_code, array( 'settings_saved', 'sports_data_deleted', 'predictions_emptied', 'import_success' ), true ) ? 'success' : 'error';
	if ( '' === $message ) {
		$messages = array(
			'settings_saved' => __( 'Settings saved.', 'wp-livescore-la' ),
			'sports_data_deleted' => __( 'Sports data deleted.', 'wp-livescore-la' ),
			'predictions_emptied' => __( 'Predictions emptied.', 'wp-livescore-la' ),
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

	$api_key = isset( $_POST['wp_livescore_la_google_search_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_google_search_api_key'] ) ) : '';
	$cx      = isset( $_POST['wp_livescore_la_google_search_cx'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_google_search_cx'] ) ) : '';
	$website_name_header = isset( $_POST['wp_livescore_la_website_name_header'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_website_name_header'] ) ) : 'worldcuppredictnet';
	$kadario_url         = isset( $_POST['wp_livescore_la_kadario_url'] ) ? esc_url_raw( wp_unslash( $_POST['wp_livescore_la_kadario_url'] ) ) : '';
	$kadario_api_key     = isset( $_POST['wp_livescore_la_kadario_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_kadario_api_key'] ) ) : '';
	$origin_domain       = isset( $_POST['wp_livescore_la_origin_domain'] ) ? esc_url_raw( wp_unslash( $_POST['wp_livescore_la_origin_domain'] ) ) : '';

	update_option( 'wp_livescore_la_google_search_api_key', $api_key, false );
	update_option( 'wp_livescore_la_google_search_cx', $cx, false );
	update_option( 'wp_livescore_la_website_name_header', $website_name_header, false );
	update_option( 'wp_livescore_la_kadario_url', $kadario_url, false );
	update_option( 'wp_livescore_la_kadario_api_key', $kadario_api_key, false );
	update_option( 'wp_livescore_la_origin_domain', $origin_domain, false );

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
 * Delete all League, Team, Player, Match, and Season data.
 */
function wp_livescore_la_handle_delete_sports_data() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to delete sports data.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_delete_sports_data', 'wp_livescore_la_delete_sports_data_nonce' );

	$deleted_counts = array(
		'league' => 0,
		'team'   => 0,
		'player' => 0,
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
					/* translators: 1: total posts deleted, 2: leagues deleted, 3: teams deleted, 4: players deleted, 5: matches deleted, 6: seasons deleted. */
					__( 'Deleted %1$d sports data posts. Leagues: %2$d. Teams: %3$d. Players: %4$d. Matches: %5$d. Seasons: %6$d.', 'wp-livescore-la' ),
					$total_deleted,
					$deleted_counts['league'],
					$deleted_counts['team'],
					$deleted_counts['player'],
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
 * Delete all Prediction posts.
 */
function wp_livescore_la_handle_empty_predictions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to empty predictions.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_empty_predictions', 'wp_livescore_la_empty_predictions_nonce' );

	$deleted_count = 0;
	$post_ids      = get_posts(
		array(
			'post_type'      => 'prediction',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
		)
	);

	foreach ( $post_ids as $post_id ) {
		$deleted_post = wp_delete_post( (int) $post_id, true );
		if ( $deleted_post instanceof WP_Post ) {
			$deleted_count++;
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                    => 'wp-livescore-la-settings',
				'tab'                     => 'general',
				'wp_livescore_la_notice'  => 'predictions_emptied',
				'wp_livescore_la_message' => sprintf(
					/* translators: %d: deleted prediction count. */
					_n( 'Deleted %d prediction.', 'Deleted %d predictions.', $deleted_count, 'wp-livescore-la' ),
					$deleted_count
				),
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_post_wp_livescore_la_empty_predictions', 'wp_livescore_la_handle_empty_predictions' );

/**
 * Update featured images for sports data posts.
 */
function wp_livescore_la_handle_update_images() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to update images.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_update_images', 'wp_livescore_la_update_images_nonce' );

	$result = wp_livescore_la_update_sports_data_images();

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                    => 'wp-livescore-la-settings',
				'tab'                     => 'general',
				'wp_livescore_la_notice'  => 'images_updated',
				'wp_livescore_la_message' => sprintf(
					/* translators: 1: updated count, 2: skipped count, 3: countries updated, 4: teams updated, 5: players updated. */
					__( 'Updated %1$d featured images. Skipped %2$d items. Countries: %3$d. Teams: %4$d. Players: %5$d.', 'wp-livescore-la' ),
					(int) $result['updated'],
					(int) $result['skipped'],
					(int) $result['country'],
					(int) $result['team'],
					(int) $result['player']
				),
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_post_wp_livescore_la_update_images', 'wp_livescore_la_handle_update_images' );

/**
 * Update images for Players, Teams, and Countries.
 *
 * @return array
 */
function wp_livescore_la_update_sports_data_images() {
	$result = array(
		'updated' => 0,
		'skipped' => 0,
		'country' => 0,
		'team'    => 0,
		'player'  => 0,
	);

	foreach ( array( 'country', 'team', 'player' ) as $post_type ) {
		$post_ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);

		foreach ( $post_ids as $post_id ) {
			$image_url = wp_livescore_la_image_updater_url_for_post( (int) $post_id, $post_type );

			if ( '' === $image_url ) {
				$result['skipped']++;
				continue;
			}

			if ( wp_livescore_la_update_featured_image_from_url( (int) $post_id, $image_url ) ) {
				$result['updated']++;
				$result[ $post_type ]++;
			} else {
				$result['skipped']++;
			}
		}
	}

	return $result;
}

/**
 * Get an image URL for a supported post.
 *
 * @param int    $post_id   Post ID.
 * @param string $post_type Post type.
 * @return string
 */
function wp_livescore_la_image_updater_url_for_post( $post_id, $post_type ) {
	if ( 'country' === $post_type ) {
		$flag_url = wp_livescore_la_flag_image_url_from_code( get_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true ) );
		if ( '' === $flag_url ) {
			$flag_url = wp_livescore_la_flag_image_url_from_country_name( get_the_title( $post_id ) );
		}
		if ( '' !== $flag_url ) {
			update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_flag_url', $flag_url );
		}

		return $flag_url;
	}

	if ( 'team' === $post_type ) {
		$country_code = get_post_meta( $post_id, '_team_country_code', true );
		if ( '' === (string) $country_code ) {
			$country_id = (int) get_post_meta( $post_id, '_team_country_id', true );
			if ( $country_id > 0 && 'country' === get_post_type( $country_id ) ) {
				$country_code = get_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true );
			}
		}

		$flag_url = wp_livescore_la_flag_image_url_from_code( $country_code );
		if ( '' === $flag_url ) {
			$country_name = sanitize_text_field( get_post_meta( $post_id, '_team_country_name', true ) );
			if ( '' === $country_name ) {
				$country_name = get_the_title( $post_id );
			}
			$flag_url = wp_livescore_la_flag_image_url_from_country_name( $country_name );
		}
		if ( '' !== $flag_url ) {
			update_post_meta( $post_id, '_team_logo', $flag_url );
		}

		return $flag_url;
	}

	if ( 'player' === $post_type ) {
		$team_name = sanitize_text_field( get_post_meta( $post_id, '_player_team_name', true ) );
		$query     = trim( get_the_title( $post_id ) . ' ' . $team_name . ' football player' );

		return wp_livescore_la_google_image_search_url( $query );
	}

	return '';
}

/**
 * Build a FlagCDN WebP image URL from a 2-letter country code.
 *
 * @param string $code Country code.
 * @return string
 */
function wp_livescore_la_flag_image_url_from_code( $code ) {
	$code = strtolower( sanitize_text_field( (string) $code ) );
	$code = preg_replace( '/[^a-z]/', '', $code );

	return 2 === strlen( $code ) ? esc_url_raw( 'https://flagcdn.com/w320/' . $code . '.webp' ) : '';
}

/**
 * Resolve a flag URL from a country name using Rest Countries.
 *
 * @param string $country_name Country name.
 * @return string
 */
function wp_livescore_la_flag_image_url_from_country_name( $country_name ) {
	$country_name = sanitize_text_field( trim( (string) $country_name ) );
	if ( '' === $country_name ) {
		return '';
	}

	$cache_key = 'wp_livescore_la_flag_' . md5( strtolower( $country_name ) );
	$cached    = get_transient( $cache_key );
	if ( is_string( $cached ) ) {
		return esc_url_raw( $cached );
	}

	$response = wp_remote_get(
		'https://restcountries.com/v3.1/name/' . rawurlencode( $country_name ) . '?fields=flags,cca2,name',
		array(
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		set_transient( $cache_key, '', DAY_IN_SECONDS );
		return '';
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $payload ) || empty( $payload[0] ) || ! is_array( $payload[0] ) ) {
		set_transient( $cache_key, '', DAY_IN_SECONDS );
		return '';
	}

	$flag_url = ! empty( $payload[0]['cca2'] ) ? wp_livescore_la_flag_image_url_from_code( $payload[0]['cca2'] ) : '';

	set_transient( $cache_key, $flag_url, WEEK_IN_SECONDS );

	return $flag_url;
}

/**
 * Get the first Google Programmable Search image result.
 *
 * @param string $query Search query.
 * @return string
 */
function wp_livescore_la_google_image_search_url( $query ) {
	$query = sanitize_text_field( $query );
	$api_key = get_option( 'wp_livescore_la_google_search_api_key', '' );
	$cx      = get_option( 'wp_livescore_la_google_search_cx', '' );

	if ( '' === $api_key && defined( 'WP_LIVESCORE_LA_GOOGLE_SEARCH_API_KEY' ) ) {
		$api_key = WP_LIVESCORE_LA_GOOGLE_SEARCH_API_KEY;
	}
	if ( '' === $cx && defined( 'WP_LIVESCORE_LA_GOOGLE_SEARCH_CX' ) ) {
		$cx = WP_LIVESCORE_LA_GOOGLE_SEARCH_CX;
	}

	if ( '' === $query || '' === $api_key || '' === $cx ) {
		return '';
	}

	$url = add_query_arg(
		array(
			'key'        => $api_key,
			'cx'         => $cx,
			'searchType' => 'image',
			'num'        => 1,
			'safe'       => 'active',
			'q'          => $query,
		),
		'https://www.googleapis.com/customsearch/v1'
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return '';
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );

	return isset( $payload['items'][0]['link'] ) ? esc_url_raw( $payload['items'][0]['link'] ) : '';
}

/**
 * Set a featured image and report whether a thumbnail is present afterwards.
 *
 * @param int    $post_id   Post ID.
 * @param string $image_url Image URL.
 * @return bool
 */
function wp_livescore_la_update_featured_image_from_url( $post_id, $image_url ) {
	if ( ! function_exists( 'wp_livescore_la_set_featured_image_from_url' ) ) {
		return false;
	}

	wp_livescore_la_set_featured_image_from_url( $post_id, $image_url );

	return has_post_thumbnail( $post_id );
}

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
	$import_mode = isset( $_POST['import_mode'] ) ? sanitize_key( wp_unslash( $_POST['import_mode'] ) ) : '';

	if ( 'sportsdb' === $provider ) {
		$result = wp_livescore_la_run_sportsdb_import( $import_key );
	} elseif ( 'sofascore' === $provider ) {
		$result = wp_livescore_la_run_sofascore_import( $import_key );
	} elseif ( 'kadario' === $provider ) {
		if ( 'cron_predictions' === $import_mode && function_exists( 'wp_livescore_la_queue_kadario_prediction_import' ) ) {
			$result = wp_livescore_la_queue_kadario_prediction_import( $import_key );
		} else {
			$result = wp_livescore_la_run_kadario_import( $import_key );
		}
	} else {
		wp_livescore_la_redirect_notice( 'import_error', __( 'The selected import provider is not supported.', 'wp-livescore-la' ) );
	}

	if ( is_wp_error( $result ) ) {
		wp_livescore_la_redirect_notice( 'import_error', $result->get_error_message() );
	}

	$provider_labels = array(
		'sportsdb'  => __( 'SportsDB', 'wp-livescore-la' ),
		'sofascore' => __( 'SofaScore', 'wp-livescore-la' ),
		'kadario'   => __( 'Kadario', 'wp-livescore-la' ),
	);
	$provider_label = isset( $provider_labels[ $provider ] ) ? $provider_labels[ $provider ] : $provider;
	if ( ! empty( $result['queued_predictions'] ) ) {
		$message = sprintf(
			/* translators: 1: provider name, 2: fetched count, 3: queued prediction count. */
			__( '%1$s prediction cron queued. Links fetched: %2$d. Predictions queued: %3$d. WP-Cron will import one prediction per minute.', 'wp-livescore-la' ),
			$provider_label,
			$result['fetched'],
			$result['queued_predictions']
		);
	} elseif ( ! empty( $result['queued'] ) ) {
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
 * Create one sample Kadario Prediction from bundled test data.
 */
function wp_livescore_la_handle_create_kadario_sample_prediction() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to create sample predictions.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_create_kadario_sample_prediction', 'wp_livescore_la_kadario_sample_nonce' );

	if ( ! function_exists( 'wp_livescore_la_create_kadario_sample_prediction' ) ) {
		wp_livescore_la_redirect_notice( 'import_error', __( 'Kadario sample importer is not available.', 'wp-livescore-la' ) );
	}

	$sample_json = isset( $_POST['wp_livescore_la_kadario_sample_json'] ) ? wp_unslash( $_POST['wp_livescore_la_kadario_sample_json'] ) : '';
	$result      = wp_livescore_la_create_kadario_sample_prediction( (string) trim( $sample_json ) );

	if ( is_wp_error( $result ) ) {
		wp_livescore_la_redirect_notice( 'import_error', $result->get_error_message() );
	}

	$message = sprintf(
		/* translators: 1: created count, 2: updated count, 3: skipped count. */
		__( 'Kadario sample complete. Created: %1$d. Updated: %2$d. Skipped: %3$d.', 'wp-livescore-la' ),
		isset( $result['created'] ) ? (int) $result['created'] : 0,
		isset( $result['updated'] ) ? (int) $result['updated'] : 0,
		isset( $result['skipped'] ) ? (int) $result['skipped'] : 0
	);

	wp_livescore_la_redirect_notice( 'import_success', $message );
}
add_action( 'admin_post_wp_livescore_la_create_kadario_sample_prediction', 'wp_livescore_la_handle_create_kadario_sample_prediction' );

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
