<?php
/**
 * Kadario import provider.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Kadario API settings.
 */
$wp_livescore_la_kadario_api_link = 'https://livescore-ai-635955947416.asia-east1.run.app';
$wp_livescore_la_kadario_bearer   = 'f3%^&Gb3123x.G1';
$wp_livescore_la_kadario_import_links = array(
	array(
		'label'  => 'AI Generated Match Updates',
		'target' => 'api/ai-generated/',
		'type'   => 'ai-generated',
	),
);

/**
 * Get Kadario import links.
 *
 * @return array
 */
function wp_livescore_la_get_kadario_import_links() {
	global $wp_livescore_la_kadario_import_links;

	$links = array();

	if ( ! is_array( $wp_livescore_la_kadario_import_links ) ) {
		return $links;
	}

	foreach ( $wp_livescore_la_kadario_import_links as $index => $link ) {
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
			'type'   => isset( $link['type'] ) ? sanitize_key( $link['type'] ) : 'ai-generated',
			'url'    => wp_livescore_la_resolve_kadario_target_url( $target ),
		);
	}

	return $links;
}

/**
 * Resolve a Kadario target to a full URL.
 *
 * @param string $target Full URL or endpoint path.
 * @return string
 */
function wp_livescore_la_resolve_kadario_target_url( $target ) {
	$target = trim( (string) $target );

	if ( preg_match( '#^https?://#i', $target ) ) {
		return esc_url_raw( $target );
	}

	$api_link = wp_livescore_la_get_kadario_api_link();
	$target   = ltrim( $target, " \t\n\r\0\x0B/" );

	if ( '' === $api_link || '' === $target ) {
		return '';
	}

	return esc_url_raw( trailingslashit( $api_link ) . $target );
}

/**
 * Get Kadario API base URL.
 *
 * @return string
 */
function wp_livescore_la_get_kadario_api_link() {
	global $wp_livescore_la_kadario_api_link;

	return isset( $wp_livescore_la_kadario_api_link ) ? esc_url_raw( $wp_livescore_la_kadario_api_link ) : '';
}

/**
 * Get the website name value sent to livescore AI API headers.
 *
 * @return string
 */
function wp_livescore_la_get_website_name_header() {
	return sanitize_text_field( get_option( 'wp_livescore_la_website_name_header', 'worldcuppredictnet' ) );
}

/**
 * Get the Kadario API key used for Bearer authentication.
 *
 * @return string
 */
function wp_livescore_la_get_kadario_api_key() {
	global $wp_livescore_la_kadario_bearer;

	$api_key = sanitize_text_field( get_option( 'wp_livescore_la_kadario_api_key', '' ) );
	if ( '' !== $api_key ) {
		return $api_key;
	}

	return trim( isset( $wp_livescore_la_kadario_bearer ) ? (string) $wp_livescore_la_kadario_bearer : '' );
}

/**
 * Get Kadario request headers.
 *
 * @return array
 */
function wp_livescore_la_get_kadario_headers() {
	$api_key = wp_livescore_la_get_kadario_api_key();

	$headers = array(
		'Accept'        => 'application/json',
		'Authorization' => 'Bearer ' . $api_key,
	);

	$website_name = wp_livescore_la_get_website_name_header();
	if ( '' !== $website_name ) {
		$headers['X-Website-Name'] = $website_name;
	}

	return $headers;
}

/**
 * Import one or all configured Kadario targets.
 *
 * Kadario creates or updates Team, Player, and Match posts from generated content.
 *
 * @param string $selected_key Import target key, or all.
 * @return array|WP_Error
 */
function wp_livescore_la_run_kadario_import( $selected_key = 'all' ) {
	$fetched = wp_livescore_la_fetch_kadario_records( $selected_key );

	if ( is_wp_error( $fetched ) ) {
		return $fetched;
	}

	$total = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'fetched' => (int) $fetched['fetched'],
	);

	foreach ( $fetched['records_by_link'] as $records ) {
		if ( empty( $records ) ) {
			$total['skipped']++;
			continue;
		}

		$result = wp_livescore_la_import_kadario_records( $records );

		$total['created'] += (int) $result['created'];
		$total['updated'] += (int) $result['updated'];
		$total['skipped'] += (int) $result['skipped'];
	}

	return $total;
}

/**
 * Fetch Kadario prediction records without importing them.
 *
 * @param string $selected_key Import target key, or all.
 * @return array|WP_Error
 */
function wp_livescore_la_fetch_kadario_records( $selected_key = 'all' ) {
	$links = wp_livescore_la_get_kadario_import_links();

	if ( empty( $links ) ) {
		return new WP_Error( 'wp_livescore_la_no_links', __( 'No Kadario import links are configured.', 'wp-livescore-la' ) );
	}

	if ( 'all' !== $selected_key ) {
		if ( ! isset( $links[ $selected_key ] ) ) {
			return new WP_Error( 'wp_livescore_la_missing_link', __( 'The selected Kadario import link was not found.', 'wp-livescore-la' ) );
		}
		$links = array( $selected_key => $links[ $selected_key ] );
	}

	$fetched = array(
		'fetched'         => 0,
		'records'         => array(),
		'records_by_link' => array(),
	);

	foreach ( $links as $link ) {
		if ( empty( $link['url'] ) ) {
			return new WP_Error( 'wp_livescore_la_invalid_url', __( 'Please set Kadario import targets in import-api/kadario.php.', 'wp-livescore-la' ) );
		}

		$response = wp_remote_get(
			$link['url'],
			array(
				'timeout' => 60,
				'headers' => wp_livescore_la_get_kadario_headers(),
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
					__( 'Kadario returned HTTP status %1$d. %2$s', 'wp-livescore-la' ),
					$status_code,
					$link['url']
				)
			);
		}

		$payload = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'wp_livescore_la_invalid_json', __( 'Kadario returned invalid JSON.', 'wp-livescore-la' ) );
		}

		$records = wp_livescore_la_extract_kadario_records( $payload );
		$fetched['records_by_link'][] = $records;
		$fetched['records']           = array_merge( $fetched['records'], $records );
		$fetched['fetched']++;
	}

	return $fetched;
}

/**
 * Create or update one sample Kadario Prediction for testing.
 *
 * @return array Import result counts.
 */
function wp_livescore_la_create_kadario_sample_prediction() {
	return wp_livescore_la_import_kadario_records( array( wp_livescore_la_kadario_sample_prediction_record() ) );
}

/**
 * Get the Kadario sample Prediction record used by the admin test button.
 *
 * @return array
 */
function wp_livescore_la_kadario_sample_prediction_record() {
	return array(
		'match_id'          => 2520566,
		'image_url'         => 'https://livescore-ai-515206814373-ap-southeast-1-an.s3.ap-southeast-1.amazonaws.com/images/2520566.webp',
		'model'             => 'gpt-5.4-mini',
		'status'            => 'success',
		'created_at'        => '2026-05-23T23:31:55Z',
		'updated_at'        => '2026-05-23T23:47:54Z',
		'generated_content' => array(
			'ai_match_analysis' => "This matchup projects as a tight, tactically disciplined contest between two sides that can be difficult to break down when structured properly. With no previous head-to-head meetings on record and both teams entering with neutral-site conditions, the opening phase should be cautious, with each side testing the other’s press resistance and defensive spacing before taking risks. Algeria’s best route is likely to come through technical quality in wide areas and central combinations, while Austria will look to control territory through compactness, aggressive counter-pressing, and direct transitions into forward areas.\n\nAlgeria can lean on a creative core that includes Mahrez, Bennacer, and Amoura, giving them a blend of chance creation, ball retention, and attacking movement. Their ceiling rises if they can isolate fullbacks and get their front line into one-on-one situations, but they can be vulnerable when forced to defend repeated high-tempo transitions. Austria, meanwhile, usually bring greater collective intensity and a strong midfield engine, with Laimer and Schlager offering both pressure and progression. The most likely pattern is a balanced game with long stretches of containment, and the draw becomes attractive because both teams have enough quality to score but not enough separation to expect an open, high-scoring match.",
			'expected_lineups'  => array(
				'Algeria_expected_xi' => array(
					array( 'name' => 'Anthony Mandrea', 'position' => 'GK', 'shirt_number' => 1 ),
					array( 'name' => 'Aïssa Mandi', 'position' => 'RB', 'shirt_number' => 2 ),
					array( 'name' => 'Ramy Bensebaini', 'position' => 'CB', 'shirt_number' => 21 ),
					array( 'name' => 'Mohamed Amine Tougai', 'position' => 'CB', 'shirt_number' => 3 ),
					array( 'name' => 'Rayan Aït-Nouri', 'position' => 'LB', 'shirt_number' => 15 ),
					array( 'name' => 'Nabil Bentaleb', 'position' => 'CM', 'shirt_number' => 14 ),
					array( 'name' => 'Hicham Boudaoui', 'position' => 'CM', 'shirt_number' => 6 ),
					array( 'name' => 'Ismaël Bennacer', 'position' => 'CM', 'shirt_number' => 22 ),
					array( 'name' => 'Riyad Mahrez', 'position' => 'RW', 'shirt_number' => 7 ),
					array( 'name' => 'Mohamed Amoura', 'position' => 'ST', 'shirt_number' => 10 ),
					array( 'name' => 'Youssef Belaïli', 'position' => 'LW', 'shirt_number' => 8 ),
				),
				'Austria_expected_xi' => array(
					array( 'name' => 'Patrick Pentz', 'position' => 'GK', 'shirt_number' => 1 ),
					array( 'name' => 'Stefan Posch', 'position' => 'RB', 'shirt_number' => 5 ),
					array( 'name' => 'Kevin Danso', 'position' => 'CB', 'shirt_number' => 4 ),
					array( 'name' => 'Philipp Lienhart', 'position' => 'CB', 'shirt_number' => 15 ),
					array( 'name' => 'Philipp Mwene', 'position' => 'LB', 'shirt_number' => 16 ),
					array( 'name' => 'Konrad Laimer', 'position' => 'CM', 'shirt_number' => 24 ),
					array( 'name' => 'Xaver Schlager', 'position' => 'CM', 'shirt_number' => 4 ),
					array( 'name' => 'Marcel Sabitzer', 'position' => 'AM', 'shirt_number' => 9 ),
					array( 'name' => 'Christoph Baumgartner', 'position' => 'RW', 'shirt_number' => 19 ),
					array( 'name' => 'Michael Gregoritsch', 'position' => 'ST', 'shirt_number' => 11 ),
					array( 'name' => 'Marko Arnautović', 'position' => 'LW', 'shirt_number' => 7 ),
				),
				'injuries_suspensions' => array(),
			),
			'faq'               => array(
				array( 'question' => 'Who will win Algeria vs Austria?', 'answer' => 'The match is projected to end in a draw, with both teams close enough in quality that a 1-1 scoreline looks the most likely outcome.' ),
				array( 'question' => 'What time is Algeria vs Austria?', 'answer' => 'Algeria vs Austria is scheduled to kick off at 02:00 UTC on 2026-06-28.' ),
				array( 'question' => 'What is the predicted lineup?', 'answer' => 'Algeria are expected to build around Mahrez, Bennacer, and Amoura, while Austria should lean on Sabitzer, Laimer, Baumgartner, and Arnautović in a balanced 4-2-3-1 shape.' ),
				array( 'question' => 'Where to watch?', 'answer' => 'Broadcast and streaming availability depends on local rights holders, so fans should check the official competition broadcaster and their regional sports channels closer to kickoff.' ),
				array( 'question' => 'What is the best betting angle for this match?', 'answer' => 'Under 2.5 goals is the strongest betting angle because both teams are likely to prioritize structure and avoid an open game.' ),
			),
			'h2h'               => array(
				'Algeria_wins'            => 0,
				'Austria_wins'            => 0,
				'draws'                   => 0,
				'previous_meetings_count' => 0,
				'recent_meetings'         => array(),
			),
			'match_info'        => array(
				'competition'  => 'Competition ID 3',
				'date'         => '2026-06-28',
				'group_round'  => 'Group stage',
				'kickoff_time' => '02:00 UTC',
				'venue'        => 'TBD',
			),
			'news'              => array(
				'body'      => "Algeria and Austria are preparing for a closely matched group-stage encounter on 28 June 2026, with kickoff set for 02:00 UTC at a venue listed as TBD. The fixture carries added intrigue because the teams have no recorded head-to-head meetings in the available database, leaving little historical context and putting the emphasis squarely on present form, tactical organization, and individual quality.\n\nAlgeria’s route to control will likely come through possession and creativity. Riyad Mahrez remains the standout attacking reference point, while Ismaël Bennacer and Hicham Boudaoui give the side the ability to circulate the ball and manage tempo. If Algeria can draw Austria’s block out of shape and create isolated wide situations, they have the technical tools to generate chances, although the margin for error remains slim in a match of this type.\n\nAustria, by contrast, are expected to rely on compactness, pressing intensity, and strong midfield running. Konrad Laimer and Xaver Schlager can set the tone in central areas, while Marcel Sabitzer and Christoph Baumgartner offer the creative edge between the lines. With Michael Gregoritsch and Marko Arnautović providing finishing options, Austria have the profile to punish loose possession and quick transitions.",
				'headline'  => 'Algeria and Austria set for tight Group-stage battle in neutral venue',
				'image_url' => 'https://livescore-ai-515206814373-ap-southeast-1-an.s3.ap-southeast-1.amazonaws.com/images/2520566-news.webp',
				'summary'   => 'Algeria and Austria meet on 28 June 2026 in a neutral-site group-stage clash that looks finely balanced on paper.',
			),
			'quick_prediction'  => array(
				'best_betting_angle' => 'Under 2.5 goals',
				'correct_score_pick' => '1-1',
				'winner_prediction'  => 'Draw',
			),
			'recent_form'       => array(
				'Algeria_last_5' => array(),
				'Austria_last_5' => array(),
			),
			'team_writeups'     => array(
				'home_team_writeup' => "Algeria arrive as a team built around technical quality, control in possession, and moments of individual brilliance in the final third. Even without confirmed lineup data, the expected structure points toward a side that wants its wide players and midfield creators to decide the game, with Riyad Mahrez still the most natural source of incision, while Ismaël Bennacer and Hicham Boudaoui can help Algeria sustain attacks and resist pressure in central areas.\n\nDefensively, Algeria’s key challenge will be handling Austria’s energetic midfield and organized pressing. If the back line is pushed into repeated foot races or forced into hurried clearances, their shape can become stretched.",
				'away_team_writeup' => "Austria enter with a profile that is usually defined by structure, pace in transition, and a willingness to work hard without the ball. Their expected core includes strong defenders such as Kevin Danso and Philipp Lienhart, plus a midfield engine led by Konrad Laimer and Xaver Schlager, which gives them a solid platform to press, recover possession, and attack quickly.\n\nTheir main strength is collective cohesion: Austria often look more comfortable when the game becomes physical, compact, and transitional.",
			),
			'win_probability'   => array(
				'Algeria_win_percentage' => 32,
				'Austria_win_percentage' => 33,
				'draw_percentage'        => 35,
			),
			'seo_tags'          => array(
				'canonical_slug'   => 'algeria-vs-austria-preview',
				'h1'               => 'Algeria vs Austria: full group-stage preview, prediction, and expected lineups',
				'keywords'         => array( 'algeria vs austria', 'match preview', 'group stage analysis', 'predicted lineups', 'football prediction', 'under 2.5 goals', 'riyad mahrez', 'marcel sabitzer', 'neutral venue match', 'algeria football', 'austria football' ),
				'meta_description' => 'Algeria and Austria meet in a neutral-site group-stage clash that looks finely balanced. Read the latest preview, predicted lineups, key tactical themes, and a score prediction for kickoff on 28 June 2026.',
				'og_description'   => 'A balanced neutral-site group-stage showdown awaits as Algeria take on Austria. Get the predicted score, expected lineups, and the tactical angles that could decide it.',
				'og_title'         => 'Algeria vs Austria Preview: Prediction, Lineups and Tactical Breakdown',
				'title_tag'        => 'Algeria vs Austria Preview: Group-Stage Prediction, Lineups and Analysis',
			),
		),
	);
}

/**
 * Extract Kadario records from the API response.
 *
 * @param mixed $payload Decoded JSON payload.
 * @return array
 */
function wp_livescore_la_extract_kadario_records( $payload ) {
	if ( ! is_array( $payload ) ) {
		return array();
	}

	foreach ( array( 'records', 'data', 'items', 'matches' ) as $key ) {
		if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
			return wp_livescore_la_extract_kadario_records( $payload[ $key ] );
		}
	}

	return wp_livescore_la_is_list( $payload ) ? $payload : array( $payload );
}

/**
 * Import matches, teams, and players from Kadario records.
 *
 * @param array $records Kadario records.
 * @return array
 */
function wp_livescore_la_import_kadario_records( $records ) {
	$result = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
	);

	foreach ( $records as $record ) {
		if ( ! is_array( $record ) ) {
			$result['skipped']++;
			continue;
		}

		$generated    = wp_livescore_la_kadario_generated_content( $record );
		$match_api_id = wp_livescore_la_record_value( $record, array( 'match_id', 'api_id', 'idMatch', 'idEvent', 'id' ) );
		if ( '' === $match_api_id ) {
			$result['skipped']++;
			continue;
		}

		$teams = wp_livescore_la_kadario_match_team_names( $generated );
		if ( empty( $teams['home'] ) || empty( $teams['away'] ) ) {
			$result['skipped']++;
			continue;
		}

		$context = wp_livescore_la_kadario_import_context( $generated, $teams );
		if ( empty( $context['home_team_id'] ) || empty( $context['away_team_id'] ) ) {
			$result['skipped']++;
			continue;
		}

		$player_result = wp_livescore_la_import_kadario_players( $generated, $context );
		$result['created'] += (int) $player_result['created'];
		$result['updated'] += (int) $player_result['updated'];
		$result['skipped'] += (int) $player_result['skipped'];

		$match_id = wp_livescore_la_find_match_post(
			array(
				'api_id' => $match_api_id,
			)
		);
		$created_match = false;

		if ( $match_id <= 0 ) {
			$match_id = wp_insert_post(
				wp_slash(
					array(
						'post_type'   => 'match',
						'post_status' => 'publish',
						'post_title'  => sanitize_text_field( $teams['home'] . ' vs ' . $teams['away'] ),
					)
				),
				true
			);

			if ( is_wp_error( $match_id ) || $match_id <= 0 ) {
				$result['skipped']++;
				continue;
			}

			$created_match = true;
		}

		$context['match_api_id'] = $match_api_id;
		$updated                 = wp_livescore_la_update_kadario_match( $match_id, $record, $context );
		if ( $updated ) {
			$result[ $created_match ? 'created' : 'updated' ]++;
		} else {
			$result['skipped']++;
		}

		$prediction_result = wp_livescore_la_import_kadario_prediction( $match_id, $record, $context );
		if ( isset( $prediction_result['status'] ) && in_array( $prediction_result['status'], array( 'created', 'updated' ), true ) ) {
			$result[ $prediction_result['status'] ]++;
		} else {
			$result['skipped']++;
		}
	}

	return $result;
}

/**
 * Update a Match post from one Kadario record.
 *
 * @param int   $match_id Match post ID.
 * @param array $record   Kadario record.
 * @param array $context  Imported relationship context.
 * @return bool
 */
function wp_livescore_la_update_kadario_match( $match_id, $record, $context = array() ) {
	$generated = wp_livescore_la_kadario_generated_content( $record );
	$content   = wp_livescore_la_kadario_match_content( $generated );
	$excerpt   = wp_livescore_la_kadario_text_value( $generated, array( 'news.summary', 'seo_tags.meta_description' ) );
	$title     = '';

	if ( ! empty( $context['home_team_name'] ) && ! empty( $context['away_team_name'] ) ) {
		$title = $context['home_team_name'] . ' vs ' . $context['away_team_name'];
	}

	$post_data = array(
		'ID' => $match_id,
	);

	if ( '' !== $title ) {
		$post_data['post_title'] = sanitize_text_field( $title );
		$post_data['post_name']  = sanitize_title( $title );
	}

	if ( '' !== $content ) {
		$post_data['post_content'] = $content;
	}

	if ( '' !== $excerpt ) {
		$post_data['post_excerpt'] = sanitize_textarea_field( $excerpt );
	}

	if ( count( $post_data ) > 1 ) {
		$saved_id = wp_update_post( wp_slash( $post_data ), true );
		if ( is_wp_error( $saved_id ) || $saved_id <= 0 ) {
			return false;
		}
	}

	update_post_meta( $match_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', 'kadario' );
	if ( ! empty( $context['match_api_id'] ) ) {
		update_post_meta( $match_id, '_match_api_id', sanitize_text_field( $context['match_api_id'] ) );
	}

	if ( ! empty( $context['sport_id'] ) ) {
		wp_livescore_la_sync_match_sport_meta( $match_id, (int) $context['sport_id'] );
	}
	if ( ! empty( $context['country_id'] ) ) {
		wp_livescore_la_sync_match_country_meta( $match_id, (int) $context['country_id'] );
		update_post_meta( $match_id, '_match_country_name', 'World' );
		update_post_meta( $match_id, '_match_continent', 'world' );
	}
	if ( ! empty( $context['league_id'] ) ) {
		wp_livescore_la_sync_match_league_meta( $match_id, (int) $context['league_id'] );
	}
	if ( ! empty( $context['season_id'] ) ) {
		wp_livescore_la_sync_match_season_meta( $match_id, (int) $context['season_id'] );
	}
	if ( ! empty( $context['home_team_id'] ) ) {
		wp_livescore_la_sync_match_team_meta( $match_id, (int) $context['home_team_id'], 'home' );
	}
	if ( ! empty( $context['away_team_id'] ) ) {
		wp_livescore_la_sync_match_team_meta( $match_id, (int) $context['away_team_id'], 'away' );
	}

	$match_info = isset( $generated['match_info'] ) && is_array( $generated['match_info'] ) ? $generated['match_info'] : array();
	if ( ! empty( $match_info['date'] ) ) {
		update_post_meta( $match_id, '_match_date', sanitize_text_field( (string) $match_info['date'] ) );
	}
	if ( ! empty( $match_info['kickoff_time'] ) ) {
		wp_livescore_la_update_kadario_kickoff_meta( $match_id, (string) $match_info['kickoff_time'] );
	}
	$group_name = wp_livescore_la_kadario_text_value( $generated, array( 'match_group_name', 'match_info.group_round' ) );
	if ( '' !== $group_name ) {
		update_post_meta( $match_id, '_match_group_name', sanitize_text_field( $group_name ) );
	}
	if ( ! empty( $match_info['venue'] ) ) {
		update_post_meta( $match_id, '_match_venue', sanitize_text_field( (string) $match_info['venue'] ) );
	}

	$score = isset( $generated['live_score_widget'] ) && is_array( $generated['live_score_widget'] ) ? $generated['live_score_widget'] : array();
	if ( isset( $score['home_score'] ) && '' !== (string) $score['home_score'] ) {
		update_post_meta( $match_id, '_match_home_score', sanitize_text_field( (string) $score['home_score'] ) );
	}
	if ( isset( $score['away_score'] ) && '' !== (string) $score['away_score'] ) {
		update_post_meta( $match_id, '_match_away_score', sanitize_text_field( (string) $score['away_score'] ) );
	}
	if ( ! empty( $score['status'] ) ) {
		update_post_meta( $match_id, '_match_status', wp_livescore_la_normalize_kadario_status( (string) $score['status'] ) );
		update_post_meta( $match_id, '_match_status_visibility', 'active' );
	}

	wp_livescore_la_update_kadario_quick_prediction_meta( $match_id, $generated );
	wp_livescore_la_update_kadario_win_probability_meta( $match_id, $generated );

	$image_url = wp_livescore_la_kadario_text_value( $record, array( 'image_url', 'generated_content.news.image_url' ) );
	if ( '' !== $image_url ) {
		update_post_meta( $match_id, '_match_kadario_image_url', esc_url_raw( $image_url ) );
		wp_livescore_la_set_featured_image_from_url( $match_id, $image_url );
	}

	wp_livescore_la_update_kadario_match_extra_meta( $match_id, $generated, $record );

	return true;
}

/**
 * Get generated_content from a Kadario record.
 *
 * @param array $record Kadario record.
 * @return array
 */
function wp_livescore_la_kadario_generated_content( $record ) {
	if ( ! is_array( $record ) ) {
		return array();
	}

	$generated = array();

	if ( isset( $record['generated_content'] ) ) {
		if ( is_array( $record['generated_content'] ) ) {
			$generated = $record['generated_content'];
		}

		if ( empty( $generated ) && is_string( $record['generated_content'] ) && '' !== trim( $record['generated_content'] ) ) {
			$decoded = json_decode( $record['generated_content'], true );
			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
				$generated = $decoded;
			}
		}
	}

	if ( ! empty( $generated ) ) {
		return wp_livescore_la_kadario_normalize_generated_content( $generated );
	}

	foreach ( array( 'news', 'seo_tags', 'quick_prediction', 'match_info', 'team_writeups', 'expected_lineups', 'faq', 'ai_match_analysis' ) as $key ) {
		if ( array_key_exists( $key, $record ) ) {
			return wp_livescore_la_kadario_normalize_generated_content( $record );
		}
	}

	return array();
}

/**
 * Normalize alternate Kadario generated_content shapes.
 *
 * @param array $generated Generated content.
 * @return array
 */
function wp_livescore_la_kadario_normalize_generated_content( $generated ) {
	if ( ! is_array( $generated ) ) {
		return array();
	}

	if ( isset( $generated['team_writeups'] ) && is_array( $generated['team_writeups'] ) ) {
		foreach ( array( 'news', 'seo_tags', 'faq', 'live_score_widget' ) as $key ) {
			if ( empty( $generated[ $key ] ) && ! empty( $generated['team_writeups'][ $key ] ) ) {
				$generated[ $key ] = $generated['team_writeups'][ $key ];
			}
		}

		foreach ( array( 'news', 'seo_tags', 'faq', 'live_score_widget' ) as $key ) {
			if ( array_key_exists( $key, $generated['team_writeups'] ) ) {
				unset( $generated['team_writeups'][ $key ] );
			}
		}
	}

	return $generated;
}

/**
 * Prepare Kadario import context and ensure related posts exist.
 *
 * @param array $generated Generated content.
 * @param array $teams     Home and away names.
 * @return array
 */
function wp_livescore_la_kadario_import_context( $generated, $teams ) {
	$sport_id  = wp_livescore_la_get_or_create_sport_id( 'football' );
	$country_id = wp_livescore_la_get_or_create_country_id( 'World', '', 'International' );
	$league_id  = wp_livescore_la_kadario_find_fifa_world_cup_league();
	if ( $league_id <= 0 ) {
		$league_id = wp_livescore_la_kadario_ensure_league( $sport_id, $country_id );
	}
	$season_id  = $league_id > 0 ? wp_livescore_la_kadario_latest_league_season_id( $league_id ) : 0;
	$home_id    = wp_livescore_la_kadario_ensure_team( $teams['home'], $generated, 'home', $sport_id );
	$away_id    = wp_livescore_la_kadario_ensure_team( $teams['away'], $generated, 'away', $sport_id );

	return array(
		'sport_id'       => $sport_id,
		'country_id'     => $country_id,
		'league_id'      => $league_id,
		'season_id'      => $season_id,
		'home_team_id'   => $home_id,
		'away_team_id'   => $away_id,
		'home_team_name' => $teams['home'],
		'away_team_name' => $teams['away'],
	);
}

/**
 * Find the existing FIFA World Cup league in the local league list.
 *
 * @return int
 */
function wp_livescore_la_kadario_find_fifa_world_cup_league() {
	foreach ( array( 'FIFA World Cup 2026', 'FIFA World Cup' ) as $name ) {
		$league_id = wp_livescore_la_find_league_post( '', $name );
		if ( $league_id > 0 ) {
			return $league_id;
		}
	}

	$leagues = get_posts(
		array(
			'post_type'      => 'league',
			'post_status'    => 'any',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			's'              => 'fifa world cup',
		)
	);

	foreach ( $leagues as $league_id ) {
		$title = wp_livescore_la_kadario_probability_key_part( get_the_title( $league_id ) );
		if ( false !== strpos( $title, 'fifaworldcup' ) ) {
			return (int) $league_id;
		}
	}

	return 0;
}

/**
 * Get the latest assigned season for a league.
 *
 * @param int $league_id League post ID.
 * @return int
 */
function wp_livescore_la_kadario_latest_league_season_id( $league_id ) {
	$current_id = (int) get_post_meta( $league_id, '_league_current_season_term_id', true );
	if ( $current_id > 0 && get_term( $current_id, 'league_season' ) instanceof WP_Term ) {
		return $current_id;
	}

	$terms = wp_get_object_terms(
		$league_id,
		'league_season',
		array(
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return 0;
	}

	usort(
		$terms,
		function ( $a, $b ) {
			$a_year = wp_livescore_la_kadario_latest_year_from_text( $a->name );
			$b_year = wp_livescore_la_kadario_latest_year_from_text( $b->name );

			if ( $a_year !== $b_year ) {
				return $b_year <=> $a_year;
			}

			return (int) $b->term_id <=> (int) $a->term_id;
		}
	);

	return isset( $terms[0] ) ? (int) $terms[0]->term_id : 0;
}

/**
 * Extract the latest year-like value from season text.
 *
 * @param string $text Season text.
 * @return int
 */
function wp_livescore_la_kadario_latest_year_from_text( $text ) {
	if ( preg_match_all( '/\b(19|20)\d{2}\b/', (string) $text, $matches ) ) {
		return max( array_map( 'absint', $matches[0] ) );
	}

	return 0;
}

/**
 * Ensure the fixed Kadario World Cup league exists.
 *
 * @param int $sport_id   Sport term ID.
 * @param int $country_id Country post ID.
 * @return int
 */
function wp_livescore_la_kadario_ensure_league( $sport_id, $country_id ) {
	$name      = 'FIFA World Cup 2026';
	$league_id = wp_livescore_la_find_league_post( '', $name );

	if ( $league_id <= 0 ) {
		$league_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'   => 'league',
					'post_status' => 'publish',
					'post_title'  => $name,
				)
			),
			true
		);
	}

	if ( is_wp_error( $league_id ) || $league_id <= 0 ) {
		return 0;
	}

	update_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', 'kadario' );
	if ( function_exists( 'wp_livescore_la_sync_league_sport_meta' ) ) {
		wp_livescore_la_sync_league_sport_meta( $league_id, $sport_id, 'football' );
	}
	if ( function_exists( 'wp_livescore_la_sync_league_country_meta' ) ) {
		wp_livescore_la_sync_league_country_meta( $league_id, $country_id, 'World' );
	}

	return (int) $league_id;
}

/**
 * Ensure one Kadario Team exists and has mapped team fields.
 *
 * @param string $team_name Team name.
 * @param array  $generated Generated content.
 * @param string $side      home or away.
 * @param int    $sport_id  Sport term ID.
 * @return int
 */
function wp_livescore_la_kadario_ensure_team( $team_name, $generated, $side, $sport_id ) {
	$team_name = sanitize_text_field( $team_name );
	if ( '' === $team_name ) {
		return 0;
	}

	$team_id = wp_livescore_la_find_team_post( '', $team_name );
	if ( $team_id <= 0 ) {
		$team_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'   => 'team',
					'post_status' => 'publish',
					'post_title'  => $team_name,
				)
			),
			true
		);
	}

	if ( is_wp_error( $team_id ) || $team_id <= 0 ) {
		return 0;
	}

	$writeups    = isset( $generated['team_writeups'] ) && is_array( $generated['team_writeups'] ) ? $generated['team_writeups'] : array();
	$writeup_key = 'home' === $side ? 'home_team_writeup' : 'away_team_writeup';
	$writeup     = isset( $writeups[ $writeup_key ] ) ? trim( (string) $writeups[ $writeup_key ] ) : '';

	if ( '' !== $writeup ) {
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $team_id,
					'post_content' => wp_kses_post( $writeup ),
				)
			)
		);
		update_post_meta( $team_id, '_team_kadario_writeup', sanitize_textarea_field( $writeup ) );
	}

	$country_id = wp_livescore_la_get_or_create_country_id( $team_name, '', 'International' );
	wp_livescore_la_sync_team_sport_meta( $team_id, $sport_id );
	wp_livescore_la_sync_team_country_meta( $team_id, $country_id );
	update_post_meta( $team_id, '_team_status', 'active' );
	update_post_meta( $team_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', 'kadario' );

	$form_data = isset( $generated['recent_form'] ) && is_array( $generated['recent_form'] ) ? $generated['recent_form'] : array();
	$form      = wp_livescore_la_kadario_team_recent_form( $form_data, $team_name );
	if ( '' !== $form ) {
		update_post_meta( $team_id, '_team_recent_form', $form );
	}

	return (int) $team_id;
}

/**
 * Create or update players from Kadario expected_lineups.
 *
 * @param array $generated Generated content.
 * @param array $context   Import context.
 * @return array
 */
function wp_livescore_la_import_kadario_players( $generated, $context ) {
	$result  = array( 'created' => 0, 'updated' => 0, 'skipped' => 0 );
	$lineups = isset( $generated['expected_lineups'] ) && is_array( $generated['expected_lineups'] ) ? $generated['expected_lineups'] : array();

	foreach ( $lineups as $lineup_key => $players ) {
		if ( ! is_array( $players ) ) {
			$result['skipped']++;
			continue;
		}

		$team_name = wp_livescore_la_kadario_team_name_from_lineup_key( (string) $lineup_key );
		$team_id   = wp_livescore_la_kadario_context_team_id_for_name( $team_name, $context );

		if ( $team_id <= 0 ) {
			$result['skipped']++;
			continue;
		}

		$records = array();
		foreach ( $players as $player ) {
			if ( ! is_array( $player ) ) {
				continue;
			}

			$records[] = array(
				'name'         => wp_livescore_la_record_value( $player, array( 'name', 'player_name', 'title' ) ),
				'position'     => wp_livescore_la_kadario_full_position_name( wp_livescore_la_record_value( $player, array( 'position' ) ) ),
				'jerseyNumber' => wp_livescore_la_record_value( $player, array( 'shirt_number', 'jersey', 'jerseyNumber', 'number' ) ),
			);
		}

		if ( empty( $records ) ) {
			$result['skipped']++;
			continue;
		}

		$imported = wp_livescore_la_import_players( $records, $team_id, 'kadario' );
		$result['created'] += (int) $imported['created'];
		$result['updated'] += (int) $imported['updated'];
		$result['skipped'] += (int) $imported['skipped'];
	}

	return $result;
}

/**
 * Match a lineup team name to the current imported context.
 *
 * @param string $team_name Team name.
 * @param array  $context   Import context.
 * @return int
 */
function wp_livescore_la_kadario_context_team_id_for_name( $team_name, $context ) {
	$normalized = wp_livescore_la_kadario_probability_key_part( $team_name );

	foreach ( array( 'home', 'away' ) as $side ) {
		$name_key = $side . '_team_name';
		$id_key   = $side . '_team_id';
		if ( ! empty( $context[ $name_key ] ) && $normalized === wp_livescore_la_kadario_probability_key_part( $context[ $name_key ] ) ) {
			return isset( $context[ $id_key ] ) ? (int) $context[ $id_key ] : 0;
		}
	}

	return 0;
}

/**
 * Convert a Kadario expected XI key into a team name.
 *
 * @param string $key Lineup key.
 * @return string
 */
function wp_livescore_la_kadario_team_name_from_lineup_key( $key ) {
	$key = preg_replace( '/_expected_xi$/i', '', trim( $key ) );
	$key = str_replace( '_', ' ', (string) $key );

	return sanitize_text_field( $key );
}

/**
 * Convert compact player positions into complete names when needed.
 *
 * @param string $position Raw Kadario position.
 * @return string
 */
function wp_livescore_la_kadario_full_position_name( $position ) {
	$value = strtoupper( trim( (string) $position ) );
	$map   = array(
		'G'   => 'Goalkeeper',
		'GK'  => 'Goalkeeper',
		'D'   => 'Defender',
		'DF'  => 'Defender',
		'DEF' => 'Defender',
		'RB'  => 'Right Back',
		'RWB' => 'Right Wing Back',
		'LB'  => 'Left Back',
		'LWB' => 'Left Wing Back',
		'CB'  => 'Centre Back',
		'RCB' => 'Right Centre Back',
		'LCB' => 'Left Centre Back',
		'WB'  => 'Wing Back',
		'M'   => 'Midfielder',
		'MF'  => 'Midfielder',
		'MID' => 'Midfielder',
		'DM'  => 'Defensive Midfielder',
		'CDM' => 'Defensive Midfielder',
		'CM'  => 'Central Midfielder',
		'AM'  => 'Attacking Midfielder',
		'CAM' => 'Attacking Midfielder',
		'RM'  => 'Right Midfielder',
		'LM'  => 'Left Midfielder',
		'F'   => 'Forward',
		'FW'  => 'Forward',
		'ATT' => 'Forward',
		'RW'  => 'Right Winger',
		'LW'  => 'Left Winger',
		'ST'  => 'Striker',
		'CF'  => 'Centre Forward',
	);

	return isset( $map[ $value ] ) ? $map[ $value ] : sanitize_text_field( $position );
}

/**
 * Extract home and away names from Kadario content.
 *
 * @param array $generated Generated content.
 * @return array
 */
function wp_livescore_la_kadario_match_team_names( $generated ) {
	$parsed = wp_livescore_la_kadario_parse_correct_score_pick( wp_livescore_la_kadario_text_value( $generated, array( 'quick_prediction.correct_score_pick' ) ) );
	if ( ! empty( $parsed['home'] ) && ! empty( $parsed['away'] ) ) {
		return array(
			'home' => $parsed['home'],
			'away' => $parsed['away'],
		);
	}

	$h2h = isset( $generated['h2h']['recent_meetings'] ) && is_array( $generated['h2h']['recent_meetings'] ) ? $generated['h2h']['recent_meetings'] : array();
	if ( ! empty( $h2h[0]['home_team'] ) && ! empty( $h2h[0]['away_team'] ) ) {
		return array(
			'home' => sanitize_text_field( $h2h[0]['home_team'] ),
			'away' => sanitize_text_field( $h2h[0]['away_team'] ),
		);
	}

	$lineups = isset( $generated['expected_lineups'] ) && is_array( $generated['expected_lineups'] ) ? array_keys( $generated['expected_lineups'] ) : array();
	if ( count( $lineups ) >= 2 ) {
		return array(
			'home' => wp_livescore_la_kadario_team_name_from_lineup_key( $lineups[0] ),
			'away' => wp_livescore_la_kadario_team_name_from_lineup_key( $lineups[1] ),
		);
	}

	return array( 'home' => '', 'away' => '' );
}

/**
 * Parse "Team A 1-0 Team B" into team names.
 *
 * @param string $pick Correct score pick.
 * @return array
 */
function wp_livescore_la_kadario_parse_correct_score_pick( $pick ) {
	if ( preg_match( '/^\s*(.+?)\s+(\d+)\s*[-–]\s*(\d+)\s+(.+?)\s*$/u', trim( (string) $pick ), $matches ) ) {
		return array(
			'home'       => sanitize_text_field( $matches[1] ),
			'away'       => sanitize_text_field( $matches[4] ),
			'home_score' => sanitize_text_field( $matches[2] ),
			'away_score' => sanitize_text_field( $matches[3] ),
		);
	}

	return array();
}

/**
 * Update normalized prediction text fields from Kadario quick prediction data.
 *
 * @param int   $match_id  Match post ID.
 * @param array $generated Generated content.
 * @return void
 */
function wp_livescore_la_update_kadario_quick_prediction_meta( $match_id, $generated ) {
	$mapping = array(
		'_match_best_betting_angle' => 'quick_prediction.best_betting_angle',
		'_match_correct_score_pick' => 'quick_prediction.correct_score_pick',
		'_match_winner_prediction'  => 'quick_prediction.winner_prediction',
	);

	foreach ( $mapping as $meta_key => $path ) {
		$value = wp_livescore_la_kadario_text_value( $generated, array( $path ) );
		if ( '' !== $value ) {
			update_post_meta( $match_id, $meta_key, sanitize_textarea_field( $value ) );
		}
	}
}

/**
 * Update normalized prediction percentage fields from Kadario win probability data.
 *
 * @param int   $match_id  Match post ID.
 * @param array $generated Generated content.
 * @return void
 */
function wp_livescore_la_update_kadario_win_probability_meta( $match_id, $generated ) {
	if ( empty( $generated['win_probability'] ) || ! is_array( $generated['win_probability'] ) ) {
		return;
	}

	$probability = $generated['win_probability'];
	$home_name   = sanitize_text_field( get_post_meta( $match_id, '_match_home_team_name', true ) );
	$away_name   = sanitize_text_field( get_post_meta( $match_id, '_match_away_team_name', true ) );

	$home_percentage = wp_livescore_la_kadario_team_win_percentage( $probability, $home_name );
	$away_percentage = wp_livescore_la_kadario_team_win_percentage( $probability, $away_name );
	$draw_percentage = isset( $probability['draw_percentage'] ) ? $probability['draw_percentage'] : null;

	if ( null !== $home_percentage ) {
		update_post_meta( $match_id, '_match_home_win_percentage', wp_livescore_la_kadario_percentage_value( $home_percentage ) );
	}
	if ( null !== $away_percentage ) {
		update_post_meta( $match_id, '_match_away_win_percentage', wp_livescore_la_kadario_percentage_value( $away_percentage ) );
	}
	if ( null !== $draw_percentage ) {
		update_post_meta( $match_id, '_match_draw_percentage', wp_livescore_la_kadario_percentage_value( $draw_percentage ) );
	}
}

/**
 * Find a team win percentage in Kadario's team-name keyed probability object.
 *
 * @param array  $probability Win probability data.
 * @param string $team_name   Team name from local match meta.
 * @return int|string|null
 */
function wp_livescore_la_kadario_team_win_percentage( $probability, $team_name ) {
	if ( '' === $team_name ) {
		return null;
	}

	$candidates = array(
		$team_name . '_win_percentage',
		str_replace( ' ', '_', $team_name ) . '_win_percentage',
		sanitize_title( $team_name ) . '_win_percentage',
	);

	foreach ( $candidates as $key ) {
		if ( array_key_exists( $key, $probability ) ) {
			return $probability[ $key ];
		}
	}

	$normalized_team = wp_livescore_la_kadario_probability_key_part( $team_name );
	foreach ( $probability as $key => $value ) {
		if ( 'draw_percentage' === $key ) {
			continue;
		}

		$key_part = preg_replace( '/_win_percentage$/', '', (string) $key );
		if ( $normalized_team === wp_livescore_la_kadario_probability_key_part( $key_part ) ) {
			return $value;
		}
	}

	return null;
}

/**
 * Normalize team names and Kadario probability keys for comparison.
 *
 * @param string $value Team name or probability key.
 * @return string
 */
function wp_livescore_la_kadario_probability_key_part( $value ) {
	$value = str_replace( array( '_', '-' ), ' ', (string) $value );
	$value = remove_accents( $value );
	$value = strtolower( $value );

	return preg_replace( '/[^a-z0-9]+/', '', $value );
}

/**
 * Clamp a Kadario percentage value to 0-100.
 *
 * @param mixed $value Raw percentage.
 * @return int
 */
function wp_livescore_la_kadario_percentage_value( $value ) {
	return max( 0, min( 100, absint( $value ) ) );
}

/**
 * Build Kadario match post content.
 *
 * @param array $generated Generated content.
 * @return string
 */
function wp_livescore_la_kadario_match_content( $generated ) {
	$news_body = wp_livescore_la_kadario_text_value( $generated, array( 'news.body' ) );
	if ( '' !== $news_body ) {
		return $news_body;
	}

	return wp_livescore_la_kadario_text_value( $generated, array( 'ai_match_analysis', 'news.summary' ) );
}

/**
 * Create or update a Prediction post from one Kadario record.
 *
 * @param int   $match_id Match post ID.
 * @param array $record   Kadario record.
 * @param array $context  Imported relationship context.
 * @return array
 */
function wp_livescore_la_import_kadario_prediction( $match_id, $record, $context = array() ) {
	if ( $match_id <= 0 || 'match' !== get_post_type( $match_id ) ) {
		return array( 'status' => 'skipped', 'post_id' => 0 );
	}

	$generated    = wp_livescore_la_kadario_generated_content( $record );
	$match_api_id = wp_livescore_la_record_value( $record, array( 'match_id', 'api_id', 'idMatch', 'idEvent', 'id' ) );
	if ( '' === $match_api_id ) {
		return array( 'status' => 'skipped', 'post_id' => 0 );
	}

	$prediction_id = wp_livescore_la_find_kadario_prediction_post( $match_api_id, $match_id );
	$created       = false;
	$news_headline = wp_livescore_la_kadario_text_value( $generated, array( 'news.headline' ) );
	$title         = '' !== $news_headline ? sanitize_text_field( $news_headline ) : wp_livescore_la_kadario_prediction_title( $generated, $context, $match_id );
	$canonical_slug = wp_livescore_la_kadario_text_value( $generated, array( 'seo_tags.canonical_slug' ) );
	$slug           = '' !== $canonical_slug ? sanitize_title( $canonical_slug ) : wp_livescore_la_kadario_prediction_slug( $generated, $title );

	if ( $prediction_id <= 0 ) {
		$prediction_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'   => 'prediction',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => $slug,
				)
			),
			true
		);

		if ( is_wp_error( $prediction_id ) || $prediction_id <= 0 ) {
			return array( 'status' => 'skipped', 'post_id' => 0 );
		}

		$created = true;
	}

	$content = wp_livescore_la_kadario_prediction_content( $generated );
	$excerpt = wp_livescore_la_kadario_text_value( $generated, array( 'news.summary', 'seo_tags.meta_description', 'quick_prediction.winner_prediction' ) );

	$post_data = array(
		'ID'         => $prediction_id,
		'post_title' => $title,
		'post_name'  => $slug,
	);

	if ( '' !== $content ) {
		$post_data['post_content'] = $content;
	}

	if ( '' !== $excerpt ) {
		$post_data['post_excerpt'] = sanitize_textarea_field( $excerpt );
	}

	$saved_id = wp_update_post( wp_slash( $post_data ), true );
	if ( is_wp_error( $saved_id ) || $saved_id <= 0 ) {
		return array( 'status' => 'skipped', 'post_id' => 0 );
	}

	wp_livescore_la_update_kadario_prediction_meta( $prediction_id, $match_id, $match_api_id, $generated, $record, $context );

	$image_url = wp_livescore_la_kadario_text_value( $record, array( 'image_url', 'generated_content.news.image_url' ) );
	if ( '' !== $image_url ) {
		wp_livescore_la_set_featured_image_from_url( $prediction_id, $image_url );
	}

	return array(
		'status'  => $created ? 'created' : 'updated',
		'post_id' => (int) $prediction_id,
	);
}

/**
 * Find a Kadario Prediction post by API match ID or local Match ID.
 *
 * @param string $match_api_id Match API ID.
 * @param int    $match_id     Local Match post ID.
 * @return int
 */
function wp_livescore_la_find_kadario_prediction_post( $match_api_id, $match_id = 0 ) {
	$meta_query = array(
		'relation' => 'OR',
		array(
			'key'   => '_prediction_api_id',
			'value' => sanitize_text_field( $match_api_id ),
		),
		array(
			'key'   => '_prediction_match_api_id',
			'value' => sanitize_text_field( $match_api_id ),
		),
	);

	if ( $match_id > 0 ) {
		$meta_query[] = array(
			'key'   => '_prediction_match_id',
			'value' => (int) $match_id,
		);
	}

	$posts = get_posts(
		array(
			'post_type'      => 'prediction',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => $meta_query,
		)
	);

	return ! empty( $posts[0] ) ? (int) $posts[0] : 0;
}

/**
 * Build a Prediction post title.
 *
 * @param array $generated Generated content.
 * @param array $context   Imported relationship context.
 * @param int   $match_id  Local Match post ID.
 * @return string
 */
function wp_livescore_la_kadario_prediction_title( $generated, $context, $match_id ) {
	$title = wp_livescore_la_kadario_text_value( $generated, array( 'news.headline', 'seo_tags.h1', 'seo_tags.og_title' ) );
	if ( '' !== $title ) {
		return sanitize_text_field( $title );
	}

	$home = ! empty( $context['home_team_name'] ) ? $context['home_team_name'] : get_post_meta( $match_id, '_match_home_team_name', true );
	$away = ! empty( $context['away_team_name'] ) ? $context['away_team_name'] : get_post_meta( $match_id, '_match_away_team_name', true );

	if ( '' !== $home && '' !== $away ) {
		return sanitize_text_field( $home . ' vs ' . $away . ' Prediction' );
	}

	return sanitize_text_field( get_the_title( $match_id ) . ' Prediction' );
}

/**
 * Build a Prediction post slug.
 *
 * @param array  $generated Generated content.
 * @param string $title     Prediction title fallback.
 * @return string
 */
function wp_livescore_la_kadario_prediction_slug( $generated, $title ) {
	$slug = wp_livescore_la_kadario_text_value( $generated, array( 'seo_tags.canonical_slug' ) );
	$slug = '' !== $slug ? sanitize_title( $slug ) : sanitize_title( $title );

	return '' !== $slug ? $slug : sanitize_title( uniqid( 'prediction-', false ) );
}

/**
 * Build Prediction post content.
 *
 * @param array $generated Generated content.
 * @return string
 */
function wp_livescore_la_kadario_prediction_content( $generated ) {
	$introduction = wp_livescore_la_kadario_text_value( $generated, array( 'news.body' ) );
	$analysis = wp_livescore_la_kadario_text_value( $generated, array( 'ai_match_analysis' ) );
	$content  = '';

	if ( '' !== $introduction ) {
		$content .= wp_livescore_la_kadario_paragraph_markup( $introduction );
	}

	$match_info = wp_livescore_la_kadario_match_info_block_markup( $generated );
	if ( '' !== $match_info ) {
		$content .= '' !== $content ? "\n\n" : '';
		$content .= $match_info;
	}

	$quick_prediction = wp_livescore_la_kadario_quick_prediction_block_markup( $generated );
	if ( '' !== $quick_prediction ) {
		$content .= '' !== $content ? "\n\n" : '';
		$content .= $quick_prediction;
	}

	if ( '' !== $analysis ) {
		$content   .= '' !== $content ? "\n\n" : '';
		$content   .= wp_livescore_la_kadario_paragraph_markup( $analysis );
	}

	$team_writeups = wp_livescore_la_kadario_team_writeups_block_markup( $generated );
	if ( '' !== $team_writeups ) {
		$content .= '' !== $content ? "\n\n" : '';
		$content .= $team_writeups;
	}

	$lineups_table = wp_livescore_la_kadario_expected_lineups_table( $generated );
	if ( '' !== $lineups_table ) {
		$content .= '' !== $content ? "\n\n" . $lineups_table : $lineups_table;
	}

	$faq_block = wp_livescore_la_kadario_faq_block_markup( $generated );
	if ( '' !== $faq_block ) {
		$content .= '' !== $content ? "\n\n" : '';
		$content .= $faq_block;
	}

	return $content;
}

/**
 * Convert plain Kadario text into paragraph markup.
 *
 * @param string $text Plain text.
 * @return string
 */
function wp_livescore_la_kadario_paragraph_markup( $text ) {
	$paragraphs = preg_split( "/\n\s*\n/", trim( (string) $text ) );
	$paragraphs = array_filter( array_map( 'trim', is_array( $paragraphs ) ? $paragraphs : array( $text ) ) );

	return empty( $paragraphs ) ? '' : '<p>' . implode( '</p><p>', array_map( 'esc_html', $paragraphs ) ) . '</p>';
}

/**
 * Build a Yoast FAQ block from Kadario FAQ data.
 *
 * @param array $generated Generated content.
 * @return string
 */
function wp_livescore_la_kadario_faq_block_markup( $generated ) {
	if ( empty( $generated['faq'] ) || ! is_array( $generated['faq'] ) ) {
		return '';
	}

	$questions = array();
	foreach ( $generated['faq'] as $index => $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$question = isset( $item['question'] ) ? sanitize_text_field( (string) $item['question'] ) : '';
		$answer   = isset( $item['answer'] ) ? sanitize_textarea_field( (string) $item['answer'] ) : '';

		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$questions[] = array(
			'id'           => 'faq-question-' . md5( $question . '|' . $index ),
			'question'     => $question,
			'answer'       => $answer,
			'jsonQuestion' => $question,
			'jsonAnswer'   => $answer,
			'images'       => array(),
		);
	}

	if ( empty( $questions ) ) {
		return '';
	}

	$block_attrs = wp_json_encode( array( 'questions' => $questions ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( ! is_string( $block_attrs ) ) {
		return '';
	}

	$html  = '<!-- wp:yoast/faq-block ' . $block_attrs . ' -->' . "\n";
	$html .= '<!-- wp:heading {"style":"anchor":"h-quick-prediction"} -->
<h2 id="h-quick-prediction" class="wp-block-heading" ">Frequently Asked Questions</h2>
<!-- /wp:heading -->';
	$html .= '<div class="schema-faq wp-block-yoast-faq-block">';

	foreach ( $questions as $question ) {
		$html .= '<div class="schema-faq-section" id="' . esc_attr( $question['id'] ) . '">';
		$html .= '<strong class="schema-faq-question">' . esc_html( $question['question'] ) . '</strong> ';
		$html .= '<p class="schema-faq-answer">' . esc_html( $question['answer'] ) . '</p> ';
		$html .= '</div> ';
	}

	$html .= '</div>' . "\n";
	$html .= '<!-- /wp:yoast/faq-block -->';

	return $html;
}

/**
 * Build Kadario quick_prediction block markup for Prediction content.
 *
 * @param array $generated Generated content.
 * @return string
 */
function wp_livescore_la_kadario_quick_prediction_block_markup( $generated ) {
	if ( empty( $generated['quick_prediction'] ) || ! is_array( $generated['quick_prediction'] ) ) {
		return '';
	}

	$quick_prediction = $generated['quick_prediction'];
	$score_pick       = isset( $quick_prediction['correct_score_pick'] ) ? sanitize_text_field( (string) $quick_prediction['correct_score_pick'] ) : '';
	$winner           = isset( $quick_prediction['winner_prediction'] ) ? sanitize_text_field( (string) $quick_prediction['winner_prediction'] ) : '';
	$betting_angle    = isset( $quick_prediction['best_betting_angle'] ) ? sanitize_text_field( (string) $quick_prediction['best_betting_angle'] ) : '';

	if ( '' === $score_pick && '' === $winner && '' === $betting_angle ) {
		return '';
	}

	$html  = '<!-- wp:uagb/container {"block_id":"03efe7d6","directionDesktop":"row","wrapDesktop":"wrap","variationSelected":true,"isBlockRootParent":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-03efe7d6 alignfull uagb-is-root-container"><div class="uagb-container-inner-blocks-wrap"><!-- wp:heading {"style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50"}}},"anchor":"h-quick-prediction"} -->' . "\n";
	$html .= '<h2 id="h-quick-prediction" class="wp-block-heading" ">Quick Prediction</h2>' . "\n";
	$html .= '<!-- /wp:heading -->' . "\n\n";
	$html .= '<!-- wp:uagb/container {"block_id":"5b7d5c19","directionDesktop":"row","justifyContentDesktop":"space-between","widthSetByUser":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-5b7d5c19"><!-- wp:uagb/container {"block_id":"41172bca","widthDesktop":48,"widthSetByUser":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-41172bca"><!-- wp:paragraph -->' . "\n";
	$html .= '<p><strong>' . esc_html__( 'Predicted Score Pick', 'wp-livescore-la' ) . ':</strong> ' . esc_html( $score_pick ) . '</p>' . "\n";
	$html .= '<!-- /wp:paragraph --></div>' . "\n";
	$html .= '<!-- /wp:uagb/container -->' . "\n\n";
	$html .= '<!-- wp:uagb/container {"block_id":"00621266","widthDesktop":48,"widthSetByUser":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-00621266"><!-- wp:paragraph -->' . "\n";
	$html .= '<p><strong>' . esc_html__( 'Winner Prediction', 'wp-livescore-la' ) . ': </strong>' . esc_html( $winner ) . '</p>' . "\n";
	$html .= '<!-- /wp:paragraph --></div>' . "\n";
	$html .= '<!-- /wp:uagb/container --></div>' . "\n";
	$html .= '<!-- /wp:uagb/container -->' . "\n\n";
	$html .= '<!-- wp:uagb/container {"block_id":"c1574c32","widthSetByUser":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-c1574c32"><!-- wp:paragraph -->' . "\n";
	$html .= '<p><strong>' . esc_html__( 'Best Betting Angle', 'wp-livescore-la' ) . ': </strong>' . esc_html( $betting_angle ) . '</p>' . "\n";
	$html .= '<!-- /wp:paragraph --></div>' . "\n";
	$html .= '<!-- /wp:uagb/container --></div></div>' . "\n";
	$html .= '<!-- /wp:uagb/container -->';

	return $html;
}

/**
 * Build Kadario team_writeups block markup for Prediction content.
 *
 * @param array $generated Generated content.
 * @return string
 */
function wp_livescore_la_kadario_team_writeups_block_markup( $generated ) {
	if ( empty( $generated['team_writeups'] ) || ! is_array( $generated['team_writeups'] ) ) {
		return '';
	}

	$writeups = $generated['team_writeups'];
	$teams    = wp_livescore_la_kadario_team_names_from_generated( $generated );
	$away     = isset( $writeups['away_team_writeup'] ) ? trim( (string) $writeups['away_team_writeup'] ) : '';
	$home     = isset( $writeups['home_team_writeup'] ) ? trim( (string) $writeups['home_team_writeup'] ) : '';

	if ( '' === $away && '' === $home ) {
		return '';
	}

	$away_name = ! empty( $teams['away'] ) ? $teams['away'] : __( 'Away Team', 'wp-livescore-la' );
	$home_name = ! empty( $teams['home'] ) ? $teams['home'] : __( 'Home Team', 'wp-livescore-la' );

	$html  = '<!-- wp:uagb/container {"block_id":"48eb2397","topPaddingDesktop":0,"bottomPaddingDesktop":0,"leftPaddingDesktop":0,"rightPaddingDesktop":0,"variationSelected":true,"isBlockRootParent":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-48eb2397 alignfull uagb-is-root-container"><div class="uagb-container-inner-blocks-wrap"><!-- wp:heading {"anchor":"h-about-the-team"} -->' . "\n";
	$html .= '<h2 id="h-about-the-team" class="wp-block-heading">' . esc_html__( 'About the team', 'wp-livescore-la' ) . '</h2>' . "\n";
	$html .= '<!-- /wp:heading -->' . "\n\n";
	$html .= '<!-- wp:uagb/container {"block_id":"7fe87ca8","directionDesktop":"row","alignItemsDesktop":"flex-start","variationSelected":true,"isBlockRootParent":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-7fe87ca8 alignfull uagb-is-root-container"><div class="uagb-container-inner-blocks-wrap">';
	$html .= wp_livescore_la_kadario_team_writeup_column_markup(
		'ea9d2e5f',
		$away_name,
		$away,
		'{"block_id":"ea9d2e5f","widthDesktop":50,"topPaddingDesktop":0,"bottomPaddingDesktop":0,"leftPaddingDesktop":0,"rightPaddingDesktop":0,"widthSetByUser":true}'
	);
	$html .= wp_livescore_la_kadario_team_writeup_column_markup(
		'a6615b81',
		$home_name,
		$home,
		'{"block_id":"a6615b81","widthDesktop":50,"topPaddingDesktop":0,"bottomPaddingDesktop":10,"leftPaddingDesktop":10,"rightPaddingDesktop":10,"paddingLink":false,"widthSetByUser":true}'
	);
	$html .= '</div></div>' . "\n";
	$html .= '<!-- /wp:uagb/container --></div></div>' . "\n";
	$html .= '<!-- /wp:uagb/container -->';

	return $html;
}

/**
 * Build one team writeup column.
 *
 * @param string $block_id Block ID.
 * @param string $team_name Team name.
 * @param string $writeup Team writeup.
 * @param string $attrs UAGB block attrs JSON.
 * @return string
 */
function wp_livescore_la_kadario_team_writeup_column_markup( $block_id, $team_name, $writeup, $attrs ) {
	$anchor = 'h-' . sanitize_title( $team_name );
	$html   = '<!-- wp:uagb/container ' . $attrs . ' -->' . "\n";
	$html  .= '<div class="wp-block-uagb-container uagb-block-' . esc_attr( $block_id ) . '"><!-- wp:heading {"level":3,"anchor":"' . esc_attr( $anchor ) . '"} -->' . "\n";
	$html  .= '<h3 id="' . esc_attr( $anchor ) . '" class="wp-block-heading">' . esc_html( $team_name ) . '</h3>' . "\n";
	$html  .= '<!-- /wp:heading -->' . "\n\n";

	if ( '' !== $writeup ) {
		$html .= '<!-- wp:paragraph -->' . "\n";
		$html .= wp_livescore_la_kadario_paragraph_markup( $writeup ) . "\n";
		$html .= '<!-- /wp:paragraph -->';
	}

	$html .= '</div>' . "\n";
	$html .= '<!-- /wp:uagb/container -->' . "\n\n";

	return $html;
}

/**
 * Resolve home and away team names from generated content.
 *
 * @param array $generated Generated content.
 * @return array
 */
function wp_livescore_la_kadario_team_names_from_generated( $generated ) {
	$teams = array(
		'home' => '',
		'away' => '',
	);

	if ( empty( $generated['expected_lineups'] ) || ! is_array( $generated['expected_lineups'] ) ) {
		return $teams;
	}

	foreach ( $generated['expected_lineups'] as $lineup_key => $players ) {
		if ( 'injuries_suspensions' === $lineup_key || ! is_array( $players ) ) {
			continue;
		}

		$team_name = wp_livescore_la_kadario_team_name_from_lineup_key( (string) $lineup_key );
		if ( '' === $teams['home'] ) {
			$teams['home'] = $team_name;
		} elseif ( '' === $teams['away'] ) {
			$teams['away'] = $team_name;
			break;
		}
	}

	return $teams;
}

/**
 * Build Kadario match_info block markup for Prediction content.
 *
 * @param array $generated Generated content.
 * @return string
 */
function wp_livescore_la_kadario_match_info_block_markup( $generated ) {
	if ( empty( $generated['match_info'] ) || ! is_array( $generated['match_info'] ) ) {
		return '';
	}

	$match_info = $generated['match_info'];
	$items      = array(
		array(
			'block_id' => 'aa0b4ec7',
			'label'    => __( 'Competition', 'wp-livescore-la' ),
			'value'    => isset( $match_info['competition'] ) ? sanitize_text_field( (string) $match_info['competition'] ) : '',
		),
		array(
			'block_id' => 'b89bf65f',
			'label'    => __( 'Group Round', 'wp-livescore-la' ),
			'value'    => isset( $match_info['group_round'] ) ? sanitize_text_field( (string) $match_info['group_round'] ) : '',
		),
		array(
			'block_id' => '6822dc2b',
			'label'    => __( 'Date', 'wp-livescore-la' ),
			'value'    => isset( $match_info['date'] ) ? wp_livescore_la_kadario_prediction_date_label( (string) $match_info['date'] ) : '',
		),
		array(
			'block_id' => 'a1ffbe36',
			'label'    => __( 'Kickoff Time', 'wp-livescore-la' ),
			'value'    => isset( $match_info['kickoff_time'] ) ? wp_livescore_la_kadario_prediction_kickoff_label( (string) $match_info['kickoff_time'], isset( $match_info['date'] ) ? (string) $match_info['date'] : '' ) : '',
		),
		array(
			'block_id' => 'fd107f7a',
			'label'    => __( 'Venue', 'wp-livescore-la' ),
			'value'    => isset( $match_info['venue'] ) ? sanitize_text_field( (string) $match_info['venue'] ) : '',
			'attrs'    => '{"block_id":"fd107f7a","topPaddingDesktop":0,"bottomPaddingDesktop":0,"leftPaddingDesktop":30,"rightPaddingDesktop":30,"paddingLink":false}',
		),
	);

	$has_value = false;
	foreach ( $items as $item ) {
		if ( '' !== $item['value'] ) {
			$has_value = true;
			break;
		}
	}

	if ( ! $has_value ) {
		return '';
	}

	$html  = '<!-- wp:uagb/container {"block_id":"cde17444","directionDesktop":"row","wrapDesktop":"wrap","variationSelected":true,"isBlockRootParent":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-cde17444 alignfull uagb-is-root-container"><div class="uagb-container-inner-blocks-wrap">';

	foreach ( $items as $item ) {
		$attrs = isset( $item['attrs'] ) ? $item['attrs'] : '{"block_id":"' . $item['block_id'] . '","widthDesktop":48,"widthSetByUser":true}';

		$html .= '<!-- wp:uagb/container ' . $attrs . ' -->' . "\n";
		$html .= '<div class="wp-block-uagb-container uagb-block-' . esc_attr( $item['block_id'] ) . '"><!-- wp:paragraph -->' . "\n";
		$html .= '<p><strong>' . esc_html( $item['label'] ) . ':</strong> ' . esc_html( $item['value'] ) . '</p>' . "\n";
		$html .= '<!-- /wp:paragraph --></div>' . "\n";
		$html .= '<!-- /wp:uagb/container -->' . "\n\n";
	}

	$html .= '</div></div>' . "\n";
	$html .= '<!-- /wp:uagb/container -->';

	return $html;
}

/**
 * Format a Kadario match_info date.
 *
 * @param string $date Raw date.
 * @return string
 */
function wp_livescore_la_kadario_prediction_date_label( $date ) {
	$date = sanitize_text_field( $date );

	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		$timestamp = strtotime( $date . ' 00:00:00 UTC' );
		return false !== $timestamp ? gmdate( 'F j, Y', $timestamp ) : $date;
	}

	return $date;
}

/**
 * Format a Kadario match_info kickoff time as PHT.
 *
 * @param string $kickoff_time Raw kickoff time.
 * @param string $date         Raw match date.
 * @return string
 */
function wp_livescore_la_kadario_prediction_kickoff_label( $kickoff_time, $date = '' ) {
	$kickoff_time = sanitize_text_field( $kickoff_time );
	$date         = sanitize_text_field( $date );

	if ( preg_match( '/^(\d{1,2}):(\d{2})\s*UTC$/i', $kickoff_time, $matches ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		try {
			$datetime = new DateTime( $date . ' ' . sprintf( '%02d:%02d:00', (int) $matches[1], (int) $matches[2] ), new DateTimeZone( 'UTC' ) );
			$datetime->setTimezone( new DateTimeZone( 'Asia/Manila' ) );
			return $datetime->format( 'g:i A' ) . ' PHT';
		} catch ( Exception $exception ) {
			return $kickoff_time;
		}
	}

	return $kickoff_time;
}

/**
 * Build a side-by-side expected lineups table.
 *
 * @param array $generated Generated content.
 * @return string
 */
function wp_livescore_la_kadario_expected_lineups_table( $generated ) {
	if ( empty( $generated['expected_lineups'] ) || ! is_array( $generated['expected_lineups'] ) ) {
		return '';
	}

	$teams = array();
	foreach ( $generated['expected_lineups'] as $lineup_key => $players ) {
		if ( 'injuries_suspensions' === $lineup_key || ! is_array( $players ) || empty( $players ) ) {
			continue;
		}

		$teams[] = array(
			'name'    => wp_livescore_la_kadario_team_name_from_lineup_key( (string) $lineup_key ),
			'players' => array_values( $players ),
		);

		if ( 2 === count( $teams ) ) {
			break;
		}
	}

	if ( 2 !== count( $teams ) ) {
		return '';
	}

	$row_count = max( count( $teams[0]['players'] ), count( $teams[1]['players'] ) );
	if ( $row_count <= 0 ) {
		return '';
	}

	$html  = '<!-- wp:uagb/container {"block_id":"de8a3bc9","variationSelected":true,"isBlockRootParent":true} -->' . "\n";
	$html .= '<div class="wp-block-uagb-container uagb-block-de8a3bc9 alignfull uagb-is-root-container"><div class="uagb-container-inner-blocks-wrap"><!-- wp:heading {"anchor":"h-predicted-players"} -->' . "\n";
	$html .= '<h2 id="h-predicted-players" class="wp-block-heading">' . esc_html__( 'Predicted Lineup', 'wp-livescore-la' ) . '</h2>' . "\n";
	$html .= '<!-- /wp:heading -->' . "\n\n";
	$html .= '<!-- wp:table {"className":"wp-livescore-la-prediction-lineups"} -->' . "\n";
	$html .= '<figure class="wp-block-table wp-livescore-la-prediction-lineups"><table class="has-fixed-layout"><thead>';
	$html .= '<tr><th colspan="3">' . esc_html( $teams[0]['name'] ) . '</th><th colspan="3">' . esc_html( $teams[1]['name'] ) . '</th></tr>';
	$html .= '<tr><th>' . esc_html__( 'Name', 'wp-livescore-la' ) . '</th><th>' . esc_html__( 'Position', 'wp-livescore-la' ) . '</th><th class="has-text-align-center" data-align="center">' . esc_html__( 'Jersey', 'wp-livescore-la' ) . '</th><th>' . esc_html__( 'Name', 'wp-livescore-la' ) . '</th><th>' . esc_html__( 'Position', 'wp-livescore-la' ) . '</th><th class="has-text-align-center" data-align="center">' . esc_html__( 'Jersey', 'wp-livescore-la' ) . '</th></tr>';
	$html .= '</thead><tbody>';

	for ( $index = 0; $index < $row_count; $index++ ) {
		$home_player = isset( $teams[0]['players'][ $index ] ) && is_array( $teams[0]['players'][ $index ] ) ? $teams[0]['players'][ $index ] : array();
		$away_player = isset( $teams[1]['players'][ $index ] ) && is_array( $teams[1]['players'][ $index ] ) ? $teams[1]['players'][ $index ] : array();

		$html .= '<tr>';
		$html .= wp_livescore_la_kadario_expected_lineups_table_cells( $home_player );
		$html .= wp_livescore_la_kadario_expected_lineups_table_cells( $away_player );
		$html .= '</tr>';
	}

	$html .= '</tbody></table></figure>' . "\n";
	$html .= '<!-- /wp:table --></div></div>' . "\n";
	$html .= '<!-- /wp:uagb/container -->';

	return $html;
}

/**
 * Build one side of expected lineup table cells.
 *
 * @param array $player Player row data.
 * @return string
 */
function wp_livescore_la_kadario_expected_lineups_table_cells( $player ) {
	$name     = isset( $player['name'] ) ? sanitize_text_field( (string) $player['name'] ) : '';
	$position = isset( $player['position'] ) ? wp_livescore_la_kadario_lineup_position_label( (string) $player['position'] ) : '';
	$jersey   = isset( $player['shirt_number'] ) ? sanitize_text_field( (string) $player['shirt_number'] ) : '';

	return '<td>' . esc_html( $name ) . '</td><td>' . esc_html( $position ) . '</td><td class="has-text-align-center" data-align="center">' . esc_html( $jersey ) . '</td>';
}

/**
 * Convert lineup position codes to readable labels.
 *
 * @param string $position Position code or label.
 * @return string
 */
function wp_livescore_la_kadario_lineup_position_label( $position ) {
	$value = strtoupper( trim( (string) $position ) );
	$map   = array(
		'GK' => __( 'Goalkeeper', 'wp-livescore-la' ),
		'G'  => __( 'Goalkeeper', 'wp-livescore-la' ),
		'RB' => __( 'Right Back', 'wp-livescore-la' ),
		'LB' => __( 'Left Back', 'wp-livescore-la' ),
		'CB' => __( 'Centre Back', 'wp-livescore-la' ),
		'DM' => __( 'Defensive Midfielder', 'wp-livescore-la' ),
		'CDM'=> __( 'Defensive Midfielder', 'wp-livescore-la' ),
		'CM' => __( 'Central Midfielder', 'wp-livescore-la' ),
		'AM' => __( 'Attacking Midfielder', 'wp-livescore-la' ),
		'CAM'=> __( 'Attacking Midfielder', 'wp-livescore-la' ),
		'RW' => __( 'Right Winger', 'wp-livescore-la' ),
		'LW' => __( 'Left Winger', 'wp-livescore-la' ),
		'ST' => __( 'Striker', 'wp-livescore-la' ),
		'CF' => __( 'Centre Forward', 'wp-livescore-la' ),
		'FW' => __( 'Forward', 'wp-livescore-la' ),
		'F'  => __( 'Forward', 'wp-livescore-la' ),
	);

	return isset( $map[ $value ] ) ? $map[ $value ] : sanitize_text_field( $position );
}

/**
 * Store Kadario Prediction meta.
 *
 * @param int    $prediction_id Prediction post ID.
 * @param int    $match_id      Match post ID.
 * @param string $match_api_id  Match API ID.
 * @param array  $generated     Generated content.
 * @param array  $record        Full Kadario record.
 * @param array  $context       Imported relationship context.
 * @return void
 */
function wp_livescore_la_update_kadario_prediction_meta( $prediction_id, $match_id, $match_api_id, $generated, $record, $context ) {
	update_post_meta( $prediction_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', 'kadario' );
	update_post_meta( $prediction_id, '_prediction_api_id', sanitize_text_field( $match_api_id ) );
	update_post_meta( $prediction_id, '_prediction_match_id', (int) $match_id );
	update_post_meta( $prediction_id, '_prediction_match_api_id', sanitize_text_field( $match_api_id ) );

	$news_headline   = wp_livescore_la_kadario_text_value( $generated, array( 'news.headline' ) );
	$seo_title       = '' !== $news_headline ? sanitize_text_field( $news_headline ) : wp_livescore_la_kadario_prediction_title( $generated, $context, $match_id );
	$seo_description = wp_livescore_la_kadario_text_value( $generated, array( 'seo_tags.meta_description' ) );
	if ( '' !== $seo_title ) {
		update_post_meta( $prediction_id, '_yoast_wpseo_title', sanitize_text_field( $seo_title ) );
	}
	if ( '' !== $seo_description ) {
		update_post_meta( $prediction_id, '_yoast_wpseo_metadesc', sanitize_textarea_field( $seo_description ) );
	}
	wp_livescore_la_update_kadario_prediction_yoast_keywords( $prediction_id, $generated );

	$home_team_id = ! empty( $context['home_team_id'] ) ? (int) $context['home_team_id'] : (int) get_post_meta( $match_id, '_match_home_team_id', true );
	$away_team_id = ! empty( $context['away_team_id'] ) ? (int) $context['away_team_id'] : (int) get_post_meta( $match_id, '_match_away_team_id', true );
	$home_name    = ! empty( $context['home_team_name'] ) ? $context['home_team_name'] : get_post_meta( $match_id, '_match_home_team_name', true );
	$away_name    = ! empty( $context['away_team_name'] ) ? $context['away_team_name'] : get_post_meta( $match_id, '_match_away_team_name', true );

	update_post_meta( $prediction_id, '_prediction_home_team_id', $home_team_id );
	update_post_meta( $prediction_id, '_prediction_home_team_name', sanitize_text_field( $home_name ) );
	update_post_meta( $prediction_id, '_prediction_away_team_id', $away_team_id );
	update_post_meta( $prediction_id, '_prediction_away_team_name', sanitize_text_field( $away_name ) );

	$text_meta = array(
		'_prediction_winner'          => 'quick_prediction.winner_prediction',
		'_prediction_correct_score'   => 'quick_prediction.correct_score_pick',
		'_prediction_betting_angle'   => 'quick_prediction.best_betting_angle',
		'_prediction_news_headline'   => 'news.headline',
		'_prediction_news_image_url'  => 'news.image_url',
		'_prediction_seo_title'       => 'news.headline',
		'_prediction_seo_description' => 'seo_tags.meta_description',
		'_prediction_canonical_slug'  => 'seo_tags.canonical_slug',
	);

	foreach ( $text_meta as $meta_key => $path ) {
		$value = wp_livescore_la_kadario_text_value( $generated, array( $path ) );
		if ( '' !== $value ) {
			update_post_meta( $prediction_id, $meta_key, sanitize_textarea_field( $value ) );
		}
	}

	$image_url = wp_livescore_la_kadario_text_value( $record, array( 'image_url', 'generated_content.news.image_url' ) );
	if ( '' !== $image_url ) {
		update_post_meta( $prediction_id, '_prediction_image_url', esc_url_raw( $image_url ) );
	}

	foreach ( array( 'status', 'model', 'created_at', 'updated_at' ) as $key ) {
		if ( ! empty( $record[ $key ] ) ) {
			update_post_meta( $prediction_id, '_prediction_' . $key, sanitize_text_field( (string) $record[ $key ] ) );
		}
	}

	$probability = isset( $generated['win_probability'] ) && is_array( $generated['win_probability'] ) ? $generated['win_probability'] : array();
	$home_win    = wp_livescore_la_kadario_team_win_percentage( $probability, $home_name );
	$away_win    = wp_livescore_la_kadario_team_win_percentage( $probability, $away_name );

	if ( null !== $home_win ) {
		update_post_meta( $prediction_id, '_prediction_home_win_percent', wp_livescore_la_kadario_percentage_value( $home_win ) );
	}
	if ( null !== $away_win ) {
		update_post_meta( $prediction_id, '_prediction_away_win_percent', wp_livescore_la_kadario_percentage_value( $away_win ) );
	}
	if ( isset( $probability['draw_percentage'] ) ) {
		update_post_meta( $prediction_id, '_prediction_draw_percent', wp_livescore_la_kadario_percentage_value( $probability['draw_percentage'] ) );
	}

	$json_meta = array(
		'_prediction_expected_lineups'   => isset( $generated['expected_lineups'] ) ? $generated['expected_lineups'] : array(),
		'_prediction_faq'                => isset( $generated['faq'] ) ? $generated['faq'] : array(),
		'_prediction_h2h'                => isset( $generated['h2h'] ) ? $generated['h2h'] : array(),
		'_prediction_recent_form'        => isset( $generated['recent_form'] ) ? $generated['recent_form'] : array(),
		'_prediction_win_probability'    => isset( $generated['win_probability'] ) ? $generated['win_probability'] : array(),
		'_prediction_match_info'         => isset( $generated['match_info'] ) ? $generated['match_info'] : array(),
		'_prediction_live_score_widget'  => isset( $generated['live_score_widget'] ) ? $generated['live_score_widget'] : array(),
		'_prediction_seo_tags'           => isset( $generated['seo_tags'] ) ? $generated['seo_tags'] : array(),
		'_prediction_team_writeups'      => isset( $generated['team_writeups'] ) ? $generated['team_writeups'] : array(),
		'_prediction_raw_generated_json' => $generated,
	);

	foreach ( $json_meta as $meta_key => $value ) {
		if ( ! empty( $value ) ) {
			update_post_meta( $prediction_id, $meta_key, wp_livescore_la_kadario_json_value( $value ) );
		}
	}
}

/**
 * Store Yoast focus and related keyphrases from Kadario SEO keywords.
 *
 * @param int   $prediction_id Prediction post ID.
 * @param array $generated     Generated content.
 * @return void
 */
function wp_livescore_la_update_kadario_prediction_yoast_keywords( $prediction_id, $generated ) {
	$keywords = isset( $generated['seo_tags']['keywords'] ) && is_array( $generated['seo_tags']['keywords'] ) ? $generated['seo_tags']['keywords'] : array();
	$keywords = array_values(
		array_filter(
			array_map(
				function ( $keyword ) {
					return sanitize_text_field( (string) $keyword );
				},
				$keywords
			)
		)
	);

	if ( empty( $keywords ) ) {
		return;
	}

	update_post_meta( $prediction_id, '_yoast_wpseo_focuskw', $keywords[0] );

	$related_keyphrases = array();
	foreach ( array_slice( $keywords, 1 ) as $keyword ) {
		$related_keyphrases[] = array(
			'keyword' => $keyword,
			'score'   => 'na',
		);
	}

	if ( ! empty( $related_keyphrases ) ) {
		update_post_meta( $prediction_id, '_yoast_wpseo_focuskeywords', wp_json_encode( $related_keyphrases, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	} else {
		delete_post_meta( $prediction_id, '_yoast_wpseo_focuskeywords' );
	}
}

/**
 * Store Kadario-specific match meta.
 *
 * @param int   $match_id  Match post ID.
 * @param array $generated Generated content.
 * @param array $record    Full Kadario record.
 * @return void
 */
function wp_livescore_la_update_kadario_match_extra_meta( $match_id, $generated, $record ) {
	$text_meta = array(
		'_match_kadario_ai_analysis'        => 'ai_match_analysis',
		'_match_kadario_news_headline'      => 'news.headline',
		'_match_kadario_news_summary'       => 'news.summary',
		'_match_kadario_news_body'          => 'news.body',
		'_match_kadario_winner_prediction'  => 'quick_prediction.winner_prediction',
		'_match_kadario_correct_score_pick' => 'quick_prediction.correct_score_pick',
		'_match_kadario_betting_angle'      => 'quick_prediction.best_betting_angle',
		'_match_kadario_seo_title'          => 'seo_tags.title_tag',
		'_match_kadario_seo_description'    => 'seo_tags.meta_description',
		'_match_kadario_canonical_slug'     => 'seo_tags.canonical_slug',
	);

	foreach ( $text_meta as $meta_key => $path ) {
		$value = wp_livescore_la_kadario_text_value( $generated, array( $path ) );
		if ( '' !== $value ) {
			update_post_meta( $match_id, $meta_key, sanitize_textarea_field( $value ) );
		}
	}

	$json_meta = array(
		'_match_kadario_expected_lineups' => isset( $generated['expected_lineups'] ) ? $generated['expected_lineups'] : array(),
		'_match_kadario_faq'              => isset( $generated['faq'] ) ? $generated['faq'] : array(),
		'_match_kadario_h2h'              => isset( $generated['h2h'] ) ? $generated['h2h'] : array(),
		'_match_kadario_recent_form'      => isset( $generated['recent_form'] ) ? $generated['recent_form'] : array(),
		'_match_kadario_win_probability'  => isset( $generated['win_probability'] ) ? $generated['win_probability'] : array(),
		'_match_kadario_seo_tags'         => isset( $generated['seo_tags'] ) ? $generated['seo_tags'] : array(),
	);

	foreach ( $json_meta as $meta_key => $value ) {
		if ( ! empty( $value ) ) {
			update_post_meta( $match_id, $meta_key, wp_livescore_la_kadario_json_value( $value ) );
		}
	}
}

/**
 * Update linked home and away teams with Kadario team writeups.
 *
 * @param int   $match_id Match post ID.
 * @param array $record   Kadario record.
 * @return array
 */
function wp_livescore_la_update_kadario_team_writeups( $match_id, $record ) {
	$result    = array( 'updated' => 0, 'skipped' => 0 );
	$generated = isset( $record['generated_content'] ) && is_array( $record['generated_content'] ) ? $record['generated_content'] : array();
	$writeups  = isset( $generated['team_writeups'] ) && is_array( $generated['team_writeups'] ) ? $generated['team_writeups'] : array();
	$form_data = isset( $generated['recent_form'] ) && is_array( $generated['recent_form'] ) ? $generated['recent_form'] : array();

	foreach ( array( 'home' => 'home_team_writeup', 'away' => 'away_team_writeup' ) as $side => $writeup_key ) {
		$team_id = (int) get_post_meta( $match_id, '_match_' . $side . '_team_id', true );
		$team_name = sanitize_text_field( get_post_meta( $match_id, '_match_' . $side . '_team_name', true ) );
		$writeup   = isset( $writeups[ $writeup_key ] ) ? trim( (string) $writeups[ $writeup_key ] ) : '';
		$form      = wp_livescore_la_kadario_team_recent_form( $form_data, $team_name );

		if ( $team_id <= 0 || 'team' !== get_post_type( $team_id ) || ( '' === $writeup && '' === $form ) ) {
			$result['skipped']++;
			continue;
		}

		if ( '' !== $writeup ) {
			$saved_id = wp_update_post(
				wp_slash(
					array(
						'ID'           => $team_id,
						'post_content' => wp_kses_post( $writeup ),
					)
				),
				true
			);

			if ( is_wp_error( $saved_id ) || $saved_id <= 0 ) {
				$result['skipped']++;
				continue;
			}

			update_post_meta( $team_id, '_team_kadario_writeup', sanitize_textarea_field( $writeup ) );
		}

		update_post_meta( $team_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', 'kadario' );
		if ( '' !== $form ) {
			update_post_meta( $team_id, '_team_recent_form', $form );
		}
		$result['updated']++;
	}

	return $result;
}

/**
 * Get Kadario recent form for a team by team-name keyed "last_5" data.
 *
 * @param array  $form_data Recent form data.
 * @param string $team_name Team name from local match meta.
 * @return string
 */
function wp_livescore_la_kadario_team_recent_form( $form_data, $team_name ) {
	if ( empty( $form_data ) || '' === $team_name ) {
		return '';
	}

	$candidates = array(
		$team_name . '_last_5',
		$team_name . '_last_5_matches',
		str_replace( ' ', '_', $team_name ) . '_last_5',
		str_replace( ' ', '_', $team_name ) . '_last_5_matches',
		sanitize_title( $team_name ) . '_last_5',
		sanitize_title( $team_name ) . '_last_5_matches',
	);

	foreach ( $candidates as $key ) {
		if ( array_key_exists( $key, $form_data ) ) {
			return wp_livescore_la_normalize_team_recent_form( $form_data[ $key ] );
		}
	}

	$normalized_team = wp_livescore_la_kadario_probability_key_part( $team_name );
	foreach ( $form_data as $key => $value ) {
		$key_part = preg_replace( '/_last_5(?:_matches)?$/', '', (string) $key );
		if ( $normalized_team === wp_livescore_la_kadario_probability_key_part( $key_part ) ) {
			return wp_livescore_la_normalize_team_recent_form( $value );
		}
	}

	return '';
}

/**
 * Store kickoff time and timezone from Kadario "HH:MM TZ" strings.
 *
 * @param int    $match_id     Match post ID.
 * @param string $kickoff_time Kickoff string.
 * @return void
 */
function wp_livescore_la_update_kadario_kickoff_meta( $match_id, $kickoff_time ) {
	$kickoff_time = trim( $kickoff_time );

	if ( preg_match( '/^([0-2]?\d:[0-5]\d)(?:\s+([A-Za-z_\/+-]+))?$/', $kickoff_time, $matches ) ) {
		update_post_meta( $match_id, '_match_time', sanitize_text_field( $matches[1] ) );
		if ( ! empty( $matches[2] ) ) {
			update_post_meta( $match_id, '_match_timezone', sanitize_text_field( $matches[2] ) );
		}
		return;
	}

	update_post_meta( $match_id, '_match_time', sanitize_text_field( $kickoff_time ) );
}

/**
 * Normalize Kadario status to local match status options.
 *
 * @param string $status Kadario status.
 * @return string
 */
function wp_livescore_la_normalize_kadario_status( $status ) {
	$status = sanitize_key( $status );
	$map    = array(
		'upcoming'    => 'scheduled',
		'notstarted'  => 'scheduled',
		'not_started' => 'scheduled',
		'inplay'      => 'live',
		'in_play'     => 'live',
		'finished'    => 'fulltime',
		'ft'          => 'fulltime',
	);

	$status = isset( $map[ $status ] ) ? $map[ $status ] : $status;
	return array_key_exists( $status, wp_livescore_la_match_status_options() ) ? $status : 'scheduled';
}

/**
 * Get a text value from nested arrays using dot paths.
 *
 * @param array $source Source array.
 * @param array $paths  Dot-path candidates.
 * @return string
 */
function wp_livescore_la_kadario_text_value( $source, $paths ) {
	foreach ( $paths as $path ) {
		$value = wp_livescore_la_kadario_path_value( $source, $path );
		if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
			return trim( (string) $value );
		}
	}

	return '';
}

/**
 * Get a nested array value using a dot path.
 *
 * @param array  $source Source array.
 * @param string $path   Dot path.
 * @return mixed|null
 */
function wp_livescore_la_kadario_path_value( $source, $path ) {
	$value = $source;

	foreach ( explode( '.', $path ) as $key ) {
		if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
			return null;
		}

		$value = $value[ $key ];
	}

	return $value;
}

/**
 * Convert Kadario structured values to a sanitized JSON string.
 *
 * @param mixed $value Structured value.
 * @return string
 */
function wp_livescore_la_kadario_json_value( $value ) {
	$sanitized = map_deep(
		$value,
		function ( $item ) {
			return is_scalar( $item ) ? sanitize_textarea_field( (string) $item ) : $item;
		}
	);

	$json = wp_json_encode( $sanitized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	return is_string( $json ) ? $json : '';
}
