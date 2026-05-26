<?php
/**
 * SportsDB import provider.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * SportsDB API settings.
 *
 * Use $wp_livescore_la_sportsdb_api_link as the base URL and
 * $wp_livescore_la_sportsdb_api_key as the path segment before each import slug.
 * Full URLs in the import list do not need these values.
 */
$wp_livescore_la_sportsdb_api_link = 'https://www.thesportsdb.com/api/v1/json';
$wp_livescore_la_sportsdb_api_key  = '123';
$wp_livescore_la_sportsdb_import_links = array(
	array(
		'label'  => 'Leagues',
		'target' => "search_all_leagues.php?c=Philippines",
		'type'   => 'leagues',
	),
	array(
		'label'  => 'Leagues',
		'target' => "search_all_leagues.php?s=football",
		'type'   => 'leagues',
	),
	array(
		'label'  => 'Leagues',
		'target' => "lookupleague.php?id=4429",
		'type'   => 'leagues',
	),
	
);

/**
 * Get raw SportsDB import targets.
 *
 * @return string
 */
function wp_livescore_la_get_sportsdb_import_links_raw() {
	global $wp_livescore_la_sportsdb_import_links;

	if ( is_array( $wp_livescore_la_sportsdb_import_links ) ) {
		$lines = array();
		foreach ( $wp_livescore_la_sportsdb_import_links as $link ) {
			if ( ! is_array( $link ) || empty( $link['target'] ) ) {
				continue;
			}

			$label   = isset( $link['label'] ) ? sanitize_text_field( $link['label'] ) : sanitize_text_field( $link['target'] );
			$target  =  $link['target'] ;
			$lines[] = $label . '|' . $target;
		}

		return implode( "\n", $lines );
	}

	return isset( $wp_livescore_la_sportsdb_import_links ) ? (string) $wp_livescore_la_sportsdb_import_links : "Leagues|leagues\n";
}

/**
 * Parse SportsDB import targets.
 *
 * Each line can be:
 * - leagues
 * - Leagues|leagues
 * - Leagues|https://example.com/api/key/leagues
 *
 * @return array
 */
function wp_livescore_la_get_sportsdb_import_links() {
	global $wp_livescore_la_sportsdb_import_links;

	if ( is_array( $wp_livescore_la_sportsdb_import_links ) ) {
		$links = array();

		foreach ( $wp_livescore_la_sportsdb_import_links as $index => $link ) {
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
				'url'    => wp_livescore_la_resolve_import_target_url( $target ),
			);
		}

		return $links;
	}

	$lines = preg_split( '/\r\n|\r|\n/', wp_livescore_la_get_sportsdb_import_links_raw() );
	$links = array();

	foreach ( $lines as $index => $line ) {
		$line = trim( (string) $line );
		if ( '' === $line ) {
			continue;
		}

		$parts  = array_map( 'trim', explode( '|', $line, 2 ) );
		$target = isset( $parts[1] ) ? $parts[1] : $parts[0];
		$label  = isset( $parts[1] ) ? $parts[0] : $parts[0];

		if ( '' === $target ) {
			continue;
		}

		$key = sanitize_key( sanitize_title( $index . '-' . $label . '-' . $target ) );

		$links[ $key ] = array(
			'key'    => $key,
			'label'  => sanitize_text_field( $label ),
			'target' => $target ,
			'type'   => 'leagues',
			'url'    => wp_livescore_la_resolve_import_target_url( $target ),
		);
	}

	return $links;
}

/**
 * Resolve a full URL or configured endpoint slug to an import URL.
 *
 * @param string $target Full URL or slug.
 * @return string
 */
function wp_livescore_la_resolve_import_target_url( $target ) {
	$target = trim( (string) $target );

	if ( preg_match( '#^https?://#i', $target ) ) {
		return esc_url_raw( $target );
	}

	$api_link = wp_livescore_la_get_sportsdb_api_link();
	$api_key  = wp_livescore_la_clean_path_segment( wp_livescore_la_get_sportsdb_api_key() );
	$parts    = explode( '?', $target, 2 );
	$path     = trim( $parts[0], " \t\n\r\0\x0B/" );
	$query    = isset( $parts[1] ) ? trim( $parts[1] ) : '';

	if ( '' === $api_link || '' === $api_key || '' === $path ) {
		return '';
	}

	$url = untrailingslashit( $api_link ) . '/' . rawurlencode( $api_key ) . '/' . $path;

	if ( '' !== $query ) {
		$url .= '?' . $query;
	}

	return esc_url_raw( $url );
}

/**
 * Get the SportsDB API link configured in this provider file.
 *
 * @return string
 */
function wp_livescore_la_get_sportsdb_api_link() {
	global $wp_livescore_la_sportsdb_api_link;

	return isset( $wp_livescore_la_sportsdb_api_link ) ? esc_url_raw( $wp_livescore_la_sportsdb_api_link ) : '';
}

/**
 * Get the SportsDB API key configured in this provider file.
 *
 * @return string
 */
function wp_livescore_la_get_sportsdb_api_key() {
	global $wp_livescore_la_sportsdb_api_key;

	return isset( $wp_livescore_la_sportsdb_api_key ) ? sanitize_text_field( $wp_livescore_la_sportsdb_api_key ) : '';
}

/**
 * Import one or all configured SportsDB targets.
 *
 * @param string $selected_key Import target key, or all.
 * @return array
 */
function wp_livescore_la_run_sportsdb_import( $selected_key = 'all' ) {
	$links = wp_livescore_la_get_sportsdb_import_links();

	if ( empty( $links ) ) {
		return new WP_Error( 'wp_livescore_la_no_links', __( 'No SportsDB import links are configured.', 'wp-livescore-la' ) );
	}

	if ( 'all' !== $selected_key ) {
		if ( ! isset( $links[ $selected_key ] ) ) {
			return new WP_Error( 'wp_livescore_la_missing_link', __( 'The selected SportsDB import link was not found.', 'wp-livescore-la' ) );
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
		if ( empty( $link['url'] ) ) {
			return new WP_Error( 'wp_livescore_la_invalid_url', __( 'Please set the SportsDB API link and key in import-api/sportsdb.php, or use full import URLs.', 'wp-livescore-la' ) );
		}

		$response = wp_remote_get(
			$link['url'],
			array(
				'timeout' => 30,
				'headers' => array(
					'Accept' => 'application/json',
				),
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
					__( 'SportsDB returned HTTP status %1$d. %2$s', 'wp-livescore-la' ),
					$status_code,
					$link['url']
				)
			);
		}

		$payload = json_decode( $body, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'wp_livescore_la_invalid_json', __( 'SportsDB returned invalid JSON.', 'wp-livescore-la' ) );
		}

		if ( 'matches' === $link['type'] ) {
			$records = wp_livescore_la_extract_match_records( $payload );
		} elseif ( 'teams' === $link['type'] ) {
			$records = wp_livescore_la_extract_team_records( $payload );
		} else {
			$records = wp_livescore_la_extract_league_records( $payload );
		}

		if ( empty( $records ) ) {
			$total['skipped']++;
			continue;
		}

		if ( 'matches' === $link['type'] ) {
			$result = wp_livescore_la_import_matches( $records, 'sportsdb' );
		} elseif ( 'teams' === $link['type'] ) {
			$result = wp_livescore_la_import_teams( $records, 'sportsdb' );
		} else {
			$result = wp_livescore_la_import_leagues( $records, '', '', 'sportsdb' );
		}

		$total['created'] += (int) $result['created'];
		$total['updated'] += (int) $result['updated'];
		$total['skipped'] += (int) $result['skipped'];
		$total['fetched']++;
	}

	return $total;
}
