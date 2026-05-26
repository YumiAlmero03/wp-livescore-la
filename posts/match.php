<?php
/**
 * Match custom post type, metadata, frontend output, and import helpers.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Match custom post type.
 */
function wp_livescore_la_register_match_post_type() {
	register_post_type(
		'match',
		array(
			'labels'              => array(
				'name'               => __( 'Matches', 'wp-livescore-la' ),
				'singular_name'      => __( 'Match', 'wp-livescore-la' ),
				'menu_name'          => __( 'Matches', 'wp-livescore-la' ),
				'add_new_item'       => __( 'Add New Match', 'wp-livescore-la' ),
				'edit_item'          => __( 'Edit Match', 'wp-livescore-la' ),
				'all_items'          => __( 'All Matches', 'wp-livescore-la' ),
				'new_item'           => __( 'New Match', 'wp-livescore-la' ),
				'view_item'          => __( 'View Match', 'wp-livescore-la' ),
				'search_items'       => __( 'Search Matches', 'wp-livescore-la' ),
				'not_found'          => __( 'No matches found.', 'wp-livescore-la' ),
				'not_found_in_trash' => __( 'No matches found in Trash.', 'wp-livescore-la' ),
			),
			'public'              => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wp-livescore-la-sports-manager',
			'show_in_rest'        => true,
			'has_archive'         => 'matches',
			'rewrite'             => array(
				'slug'       => 'matches',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-calendar-alt',
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_match_post_type' );

/**
 * Apply public Match archive filters.
 *
 * @param WP_Query $query Main query.
 */
function wp_livescore_la_filter_frontend_match_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$is_match_query = is_post_type_archive( 'match' ) || 'match' === $query->get( 'post_type' );
	if ( ! $is_match_query ) {
		return;
	}

	$meta_query = (array) $query->get( 'meta_query' );
	foreach ( wp_livescore_la_get_match_url_filter_meta_queries() as $filter_query ) {
		$meta_query[] = $filter_query;
	}

	if ( ! empty( $meta_query ) ) {
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'wp_livescore_la_filter_frontend_match_query' );

/**
 * Get Match relationship and date filter meta queries from the current URL.
 *
 * @return array
 */
function wp_livescore_la_get_match_url_filter_meta_queries() {
	$meta_queries = array();
	$filters      = array(
		'match_sport'   => array(
			'post_type' => 'sport',
			'prefix'    => '_match_sport',
		),
		'match_country' => array(
			'post_type' => 'country',
			'prefix'    => '_match_country',
		),
		'match_league'  => array(
			'post_type' => 'league',
			'prefix'    => '_match_league',
		),
	);

	foreach ( $filters as $query_key => $filter ) {
		if ( isset( $_GET[ $query_key ] ) && '' !== $_GET[ $query_key ] ) {
			$filter_query = wp_livescore_la_get_match_relationship_filter_query(
				wp_unslash( $_GET[ $query_key ] ),
				$filter['post_type'],
				$filter['prefix']
			);

			if ( ! empty( $filter_query ) ) {
				$meta_queries[] = $filter_query;
			}
		}
	}

	$date_query = wp_livescore_la_get_match_date_filter_meta_query(
		isset( $_GET['match_date_filter'] ) ? wp_unslash( $_GET['match_date_filter'] ) : '',
		isset( $_GET['match_date'] ) ? wp_unslash( $_GET['match_date'] ) : ''
	);

	if ( ! empty( $date_query ) ) {
		$meta_queries[] = $date_query;
	}

	return $meta_queries;
}

/**
 * Build a Match date filter meta query.
 *
 * The live filter treats a match as live from its match datetime until two
 * hours after that time.
 *
 * @param string $raw_filter  Raw date filter value.
 * @param string $raw_custom_date Raw custom date value.
 * @return array
 */
function wp_livescore_la_get_match_date_filter_meta_query( $raw_filter, $raw_custom_date = '' ) {
	$date_filter = sanitize_key( $raw_filter );
	$today       = current_time( 'Y-m-d' );
	$now_time    = current_time( 'timestamp' );

	if ( 'live' === $date_filter ) {
		return array(
			'relation' => 'OR',
			array(
				'key'     => '_match_datetime',
				'value'   => array(
					wp_date( 'Y-m-d H:i:s', $now_time - ( 2 * HOUR_IN_SECONDS ) ),
					wp_date( 'Y-m-d H:i:s', $now_time ),
				),
				'compare' => 'BETWEEN',
				'type'    => 'DATETIME',
			),
			array(
				'relation' => 'AND',
				array(
					'key'     => '_match_datetime',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_match_date',
					'value'   => $today,
					'compare' => '=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_match_time',
					'value'   => array(
						wp_date( 'H:i:s', $now_time - ( 2 * HOUR_IN_SECONDS ) ),
						wp_date( 'H:i:s', $now_time ),
					),
					'compare' => 'BETWEEN',
					'type'    => 'TIME',
				),
			),
		);
	}

	if ( 'today' === $date_filter ) {
		return array(
			'key'     => '_match_date',
			'value'   => $today,
			'compare' => '=',
			'type'    => 'DATE',
		);
	}

	if ( 'upcoming' === $date_filter ) {
		return array(
			'key'     => '_match_date',
			'value'   => $today,
			'compare' => '>=',
			'type'    => 'DATE',
		);
	}

	if ( in_array( $date_filter, array( 'past', 'results' ), true ) ) {
		return array(
			'key'     => '_match_date',
			'value'   => $today,
			'compare' => '<',
			'type'    => 'DATE',
		);
	}

	if ( 'custom' === $date_filter ) {
		$custom_date = sanitize_text_field( $raw_custom_date );

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $custom_date ) ) {
			return array(
				'key'     => '_match_date',
				'value'   => $custom_date,
				'compare' => '=',
				'type'    => 'DATE',
			);
		}
	}

	return array();
}

/**
 * Build a Match relationship meta query from a filter URL value.
 *
 * Matches may have been imported at different times, so this accepts the
 * related post slug, ID, or stored name instead of relying on one meta key.
 *
 * @param string $raw_value Raw filter value.
 * @param string $post_type Related post type.
 * @param string $prefix    Match meta prefix.
 * @return array
 */
function wp_livescore_la_get_match_relationship_filter_query( $raw_value, $post_type, $prefix ) {
	$slug = sanitize_title( $raw_value );

	if ( '' === $slug ) {
		return array();
	}

	$related_post = 'sport' === $post_type ? null : get_page_by_path( $slug, OBJECT, $post_type );
	$related_term = 'sport' === $post_type ? get_term_by( 'slug', $slug, 'sport' ) : null;
	$related_id   = $related_post instanceof WP_Post ? (int) $related_post->ID : 0;
	$related_id   = $related_term instanceof WP_Term ? (int) $related_term->term_id : $related_id;
	$name_values  = array();

	if ( $related_post instanceof WP_Post ) {
		$name_values[] = sanitize_text_field( get_the_title( $related_post ) );
	}

	if ( $related_term instanceof WP_Term ) {
		$name_values[] = sanitize_text_field( $related_term->name );
	}

	$name_values[] = sanitize_text_field( str_replace( '-', ' ', $slug ) );
	$name_values[] = sanitize_text_field( ucwords( str_replace( '-', ' ', $slug ) ) );
	$name_values   = array_values( array_unique( array_filter( $name_values ) ) );

	$filter_query = array(
		'relation' => 'OR',
		array(
			'key'   => $prefix . '_slug',
			'value' => $slug,
		),
	);

	if ( $related_id > 0 ) {
		$filter_query[] = array(
			'key'   => $prefix . '_id',
			'value' => $related_id,
		);
	}

	if ( ! empty( $name_values ) ) {
		$filter_query[] = array(
			'key'     => $prefix . '_name',
			'value'   => $name_values,
			'compare' => 'IN',
		);
	}

	return $filter_query;
}

/**
 * Apply public Match filters to Match Query Loop blocks.
 *
 * @param array    $query Query Loop WP_Query arguments.
 * @param WP_Block $block Query Loop block.
 * @param int      $page  Query page.
 * @return array
 */
function wp_livescore_la_filter_match_query_loop_by_url_filters( $query, $block, $page ) {
	$block_query = isset( $block->context['query'] ) && is_array( $block->context['query'] ) ? $block->context['query'] : array();

	if ( 'match' !== ( isset( $block_query['postType'] ) ? $block_query['postType'] : '' ) ) {
		return $query;
	}

	$url_filter_queries = wp_livescore_la_get_match_url_filter_meta_queries();

	if ( empty( $url_filter_queries ) ) {
		return $query;
	}

	$meta_query = isset( $query['meta_query'] ) && is_array( $query['meta_query'] ) ? $query['meta_query'] : array();
	foreach ( $url_filter_queries as $filter_query ) {
		$meta_query[] = $filter_query;
	}

	$query['meta_query'] = $meta_query;

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'wp_livescore_la_filter_match_query_loop_by_url_filters', 30, 3 );

/**
 * Use the plugin Match archive template when the theme does not provide one.
 *
 * @param string $template Current template path.
 * @return string
 */
function wp_livescore_la_match_archive_template( $template ) {
	if ( ! is_post_type_archive( 'match' ) ) {
		return $template;
	}

	if ( function_exists( 'wp_livescore_la_get_astra_site_builder_template' ) ) {
		$astra_template = wp_livescore_la_get_astra_site_builder_template( 'Matches' );
		if ( '' === $astra_template ) {
			$astra_template = wp_livescore_la_get_astra_site_builder_template( 'Leagues' );
		}
		if ( '' !== $astra_template ) {
			return $astra_template;
		}
	}

	if ( locate_template( array( 'archive-match.php', 'archive-matches.php' ) ) ) {
		return $template;
	}

	$plugin_template = WP_LIVESCORE_LA_DIR . 'templates/archive-matches.php';

	return file_exists( $plugin_template ) ? $plugin_template : $template;
}
add_filter( 'template_include', 'wp_livescore_la_match_archive_template' );

/**
 * Sort the Match Query Loop variation by Match Date.
 *
 * @param array    $query Query Loop WP_Query arguments.
 * @param WP_Block $block Query Loop block.
 * @param int      $page  Query page.
 * @return array
 */
function wp_livescore_la_sort_match_query_loop_by_date( $query, $block, $page ) {
	$block_query = isset( $block->context['query'] ) && is_array( $block->context['query'] ) ? $block->context['query'] : array();

	$is_match_date_loop = ! empty( $block_query['wpLivescoreMatchDate'] ) || ! empty( $block_query['wpLivescoreMatchDatetime'] );

	if ( ! $is_match_date_loop || 'match' !== ( isset( $block_query['postType'] ) ? $block_query['postType'] : '' ) ) {
		return $query;
	}

	$query['meta_key'] = '_match_date';
	$query['meta_type'] = 'DATE';
	$query['orderby']  = 'meta_value';
	$query['order']    = isset( $query['order'] ) && 'DESC' === strtoupper( $query['order'] ) ? 'DESC' : 'ASC';

	$date_query = wp_livescore_la_get_match_date_filter_meta_query(
		isset( $block_query['wpLivescoreMatchDateFilter'] ) ? $block_query['wpLivescoreMatchDateFilter'] : '',
		isset( $block_query['wpLivescoreMatchCustomDate'] ) ? $block_query['wpLivescoreMatchCustomDate'] : ''
	);

	if ( ! empty( $date_query ) ) {
		$meta_query   = isset( $query['meta_query'] ) && is_array( $query['meta_query'] ) ? $query['meta_query'] : array();
		$meta_query[] = $date_query;

		$query['meta_query'] = $meta_query;
	}

	$league_api_id = isset( $block_query['wpLivescoreMatchLeagueApiId'] ) ? sanitize_text_field( $block_query['wpLivescoreMatchLeagueApiId'] ) : '';

	if ( '' !== $league_api_id ) {
		$league_id  = wp_livescore_la_get_league_id_by_api_id( $league_api_id );
		$meta_query = isset( $query['meta_query'] ) && is_array( $query['meta_query'] ) ? $query['meta_query'] : array();

		if ( $league_id > 0 ) {
			$meta_query[] = array(
				'key'   => '_match_league_id',
				'value' => $league_id,
			);
		} else {
			$query['post__in'] = array( 0 );
		}

		$query['meta_query'] = $meta_query;
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'wp_livescore_la_sort_match_query_loop_by_date', 10, 3 );

/**
 * Resolve a League post ID from its imported API ID.
 *
 * @param string $api_id League API ID.
 * @return int
 */
function wp_livescore_la_get_league_id_by_api_id( $api_id ) {
	$api_id = sanitize_text_field( (string) $api_id );

	if ( '' === $api_id ) {
		return 0;
	}

	$league_posts = get_posts(
		array(
			'post_type'      => 'league',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => WP_LIVESCORE_LA_META_PREFIX . 'api_id',
			'meta_value'     => $api_id,
		)
	);

	return ! empty( $league_posts[0] ) ? absint( $league_posts[0] ) : 0;
}

/**
 * Filter the Related Matches Query Loop to the current League.
 *
 * @param array    $query Query Loop WP_Query arguments.
 * @param WP_Block $block Query Loop block.
 * @param int      $page  Query page.
 * @return array
 */
function wp_livescore_la_filter_related_matches_query_loop_by_league( $query, $block, $page ) {
	$block_query = isset( $block->context['query'] ) && is_array( $block->context['query'] ) ? $block->context['query'] : array();

	if ( empty( $block_query['wpLivescoreRelatedMatches'] ) || 'match' !== ( isset( $block_query['postType'] ) ? $block_query['postType'] : '' ) ) {
		return $query;
	}

	$league_id = ! empty( $block_query['wpLivescoreRelatedLeagueId'] ) ? absint( $block_query['wpLivescoreRelatedLeagueId'] ) : 0;

	if ( $league_id <= 0 && isset( $block->context['postId'] ) && 'league' === get_post_type( (int) $block->context['postId'] ) ) {
		$league_id = (int) $block->context['postId'];
	}

	if ( $league_id <= 0 && is_singular( 'league' ) ) {
		$league_id = (int) get_queried_object_id();
	}

	if ( $league_id <= 0 && ! empty( $block_query['wpLivescoreMatchLeagueApiId'] ) ) {
		$league_id = wp_livescore_la_get_league_id_by_api_id( $block_query['wpLivescoreMatchLeagueApiId'] );
	}

	if ( $league_id <= 0 || 'league' !== get_post_type( $league_id ) ) {
		$query['post__in'] = array( 0 );
		return $query;
	}

	$meta_query   = isset( $query['meta_query'] ) && is_array( $query['meta_query'] ) ? $query['meta_query'] : array();
	$meta_query[] = array(
		'key'   => '_match_league_id',
		'value' => $league_id,
	);

	$query['meta_query'] = $meta_query;

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'wp_livescore_la_filter_related_matches_query_loop_by_league', 20, 3 );

/**
 * Allow custom Match Query Loop controls through the REST preview request.
 *
 * @param array $params REST collection params.
 * @return array
 */
function wp_livescore_la_register_match_rest_query_params( $params ) {
	$params['wpLivescoreMatchDate'] = array(
		'description' => __( 'Enable WP Livescore Match date sorting.', 'wp-livescore-la' ),
		'type'        => 'boolean',
	);
	$params['wpLivescoreMatchDatetime'] = array(
		'description' => __( 'Enable WP Livescore Match datetime sorting.', 'wp-livescore-la' ),
		'type'        => 'boolean',
	);
	$params['wpLivescoreMatchDateFilter'] = array(
		'description' => __( 'Filter matches by date range.', 'wp-livescore-la' ),
		'type'        => 'string',
	);
	$params['wpLivescoreMatchCustomDate'] = array(
		'description' => __( 'Custom match date filter value.', 'wp-livescore-la' ),
		'type'        => 'string',
	);
	$params['wpLivescoreMatchLeagueApiId'] = array(
		'description' => __( 'Filter matches by League API ID.', 'wp-livescore-la' ),
		'type'        => 'string',
	);
	$params['wpLivescoreRelatedMatches'] = array(
		'description' => __( 'Filter matches by a related League.', 'wp-livescore-la' ),
		'type'        => 'boolean',
	);
	$params['wpLivescoreRelatedLeagueId'] = array(
		'description' => __( 'Related League post ID.', 'wp-livescore-la' ),
		'type'        => 'integer',
	);

	return $params;
}
add_filter( 'rest_match_collection_params', 'wp_livescore_la_register_match_rest_query_params' );

/**
 * Match the block editor preview sorting to the Match Query Loop front end.
 *
 * @param array           $args    REST Match query args.
 * @param WP_REST_Request $request REST request.
 * @return array
 */
function wp_livescore_la_sort_match_rest_query_by_date( $args, $request ) {
	if ( ! $request->get_param( 'wpLivescoreMatchDate' ) && ! $request->get_param( 'wpLivescoreMatchDatetime' ) ) {
		return $args;
	}

	$args['meta_key'] = '_match_date';
	$args['meta_type'] = 'DATE';
	$args['orderby']  = 'meta_value';
	$args['order']    = isset( $args['order'] ) && 'desc' === strtolower( $args['order'] ) ? 'DESC' : 'ASC';

	$date_query = wp_livescore_la_get_match_date_filter_meta_query(
		$request->get_param( 'wpLivescoreMatchDateFilter' ),
		$request->get_param( 'wpLivescoreMatchCustomDate' )
	);

	if ( ! empty( $date_query ) ) {
		$meta_query   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
		$meta_query[] = $date_query;

		$args['meta_query'] = $meta_query;
	}

	$league_api_id = sanitize_text_field( (string) $request->get_param( 'wpLivescoreMatchLeagueApiId' ) );

	if ( '' !== $league_api_id ) {
		$league_id  = wp_livescore_la_get_league_id_by_api_id( $league_api_id );
		$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();

		if ( $league_id > 0 ) {
			$meta_query[] = array(
				'key'   => '_match_league_id',
				'value' => $league_id,
			);
		} else {
			$args['post__in'] = array( 0 );
		}

		$args['meta_query'] = $meta_query;
	}

	return $args;
}
add_filter( 'rest_match_query', 'wp_livescore_la_sort_match_rest_query_by_date', 10, 2 );

/**
 * Filter Related Matches Query Loop block editor previews when a League ID is supplied.
 *
 * @param array           $args    REST Match query args.
 * @param WP_REST_Request $request REST request.
 * @return array
 */
function wp_livescore_la_filter_related_matches_rest_query_by_league( $args, $request ) {
	if ( ! $request->get_param( 'wpLivescoreRelatedMatches' ) ) {
		return $args;
	}

	$league_id = absint( $request->get_param( 'wpLivescoreRelatedLeagueId' ) );

	if ( $league_id <= 0 ) {
		$league_id = wp_livescore_la_get_league_id_by_api_id( $request->get_param( 'wpLivescoreMatchLeagueApiId' ) );
	}

	if ( $league_id <= 0 || 'league' !== get_post_type( $league_id ) ) {
		return $args;
	}

	$meta_query   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$meta_query[] = array(
		'key'   => '_match_league_id',
		'value' => $league_id,
	);

	$args['meta_query'] = $meta_query;

	return $args;
}
add_filter( 'rest_match_query', 'wp_livescore_la_filter_related_matches_rest_query_by_league', 20, 2 );

/**
 * Match status options.
 *
 * @return array
 */
function wp_livescore_la_match_status_options() {
	return array(
		'scheduled' => __( 'Scheduled', 'wp-livescore-la' ),
		'live'      => __( 'Live', 'wp-livescore-la' ),
		'halftime'  => __( 'HT', 'wp-livescore-la' ),
		'fulltime'  => __( 'FT', 'wp-livescore-la' ),
		'postponed' => __( 'Postponed', 'wp-livescore-la' ),
		'cancelled' => __( 'Cancelled', 'wp-livescore-la' ),
		'abandoned' => __( 'Abandoned', 'wp-livescore-la' ),
		'delayed'   => __( 'Delayed', 'wp-livescore-la' ),
	);
}

/**
 * Match meta keys and labels.
 *
 * @return array
 */
function wp_livescore_la_match_meta_fields() {
	return array(
		'_match_api_id'            => __( 'API ID', 'wp-livescore-la' ),
		'_match_sportscore_slug'   => __( 'SportScore Slug', 'wp-livescore-la' ),
		'_match_sport_id'          => __( 'Sport ID', 'wp-livescore-la' ),
		'_match_sport_name'        => __( 'Sport Name', 'wp-livescore-la' ),
		'_match_sport_slug'        => __( 'Sport Slug', 'wp-livescore-la' ),
		'_match_country_id'        => __( 'Country ID', 'wp-livescore-la' ),
		'_match_country_name'      => __( 'Country Name', 'wp-livescore-la' ),
		'_match_country_slug'      => __( 'Country Slug', 'wp-livescore-la' ),
		'_match_country_code'      => __( 'Country Code', 'wp-livescore-la' ),
		'_match_continent'         => __( 'Continent', 'wp-livescore-la' ),
		'_match_league_id'         => __( 'League ID', 'wp-livescore-la' ),
		'_match_league_name'       => __( 'League Name', 'wp-livescore-la' ),
		'_match_league_slug'       => __( 'League Slug', 'wp-livescore-la' ),
		'_match_season_id'         => __( 'Season ID', 'wp-livescore-la' ),
		'_match_season_name'       => __( 'Season Name', 'wp-livescore-la' ),
		'_match_season_slug'       => __( 'Season Slug', 'wp-livescore-la' ),
		'_match_home_team_id'      => __( 'Home Team ID', 'wp-livescore-la' ),
		'_match_home_team_name'    => __( 'Home Team Name', 'wp-livescore-la' ),
		'_match_home_team_slug'    => __( 'Home Team Slug', 'wp-livescore-la' ),
		'_match_away_team_id'      => __( 'Away Team ID', 'wp-livescore-la' ),
		'_match_away_team_name'    => __( 'Away Team Name', 'wp-livescore-la' ),
		'_match_away_team_slug'    => __( 'Away Team Slug', 'wp-livescore-la' ),
		'_match_date'              => __( 'Match Date', 'wp-livescore-la' ),
		'_match_time'              => __( 'Match Time', 'wp-livescore-la' ),
		'_match_datetime'          => __( 'Match Datetime', 'wp-livescore-la' ),
		'_match_timezone'          => __( 'Timezone', 'wp-livescore-la' ),
		'_match_status'            => __( 'Match Status', 'wp-livescore-la' ),
		'_match_home_score'        => __( 'Home Score', 'wp-livescore-la' ),
		'_match_away_score'        => __( 'Away Score', 'wp-livescore-la' ),
		'_match_group_name'        => __( 'Group Name', 'wp-livescore-la' ),
		'_match_referee'           => __( 'Referee', 'wp-livescore-la' ),
		'_match_venue'             => __( 'Venue', 'wp-livescore-la' ),
		'_match_status_visibility' => __( 'Status Visibility', 'wp-livescore-la' ),
	);
}

/**
 * Format a stored Match date for viewer-facing output.
 *
 * @param string $date Stored Match date.
 * @return string
 */
function wp_livescore_la_format_match_date( $date ) {
	$date = sanitize_text_field( (string) $date );

	if ( '' === $date ) {
		return '';
	}

	$timestamp = strtotime( $date );

	return false === $timestamp ? $date : wp_date( 'M, j Y', $timestamp );
}

/**
 * Get the Match start timestamp from stored date/time meta.
 *
 * @param int $match_id Match post ID.
 * @return int
 */
function wp_livescore_la_get_match_start_timestamp( $match_id ) {
	$match_id = absint( $match_id );

	if ( $match_id <= 0 ) {
		return 0;
	}

	$timezone = wp_timezone();
	$datetime = sanitize_text_field( (string) get_post_meta( $match_id, '_match_datetime', true ) );

	if ( '' === $datetime ) {
		$date = sanitize_text_field( (string) get_post_meta( $match_id, '_match_date', true ) );
		$time = sanitize_text_field( (string) get_post_meta( $match_id, '_match_time', true ) );

		if ( '' === $date ) {
			return 0;
		}

		$datetime = trim( $date . ' ' . ( '' !== $time ? $time : '00:00:00' ) );
	}

	try {
		$date = new DateTimeImmutable( $datetime, $timezone );
		return $date->getTimestamp();
	} catch ( Exception $e ) {
		$timestamp = strtotime( $datetime );
		return false === $timestamp ? 0 : $timestamp;
	}
}

/**
 * Get a calculated Match status based on match time.
 *
 * A match is live from its start time until two hours after start.
 *
 * @param int $match_id Match post ID.
 * @return array
 */
function wp_livescore_la_get_match_status( $match_id ) {
	$match_id = absint( $match_id );
	$stored_status = sanitize_key( get_post_meta( $match_id, '_match_status', true ) );
	$status_options = wp_livescore_la_match_status_options();
	$fixed_statuses = array( 'postponed', 'cancelled', 'abandoned', 'delayed' );

	if ( in_array( $stored_status, $fixed_statuses, true ) ) {
		return array(
			'key'   => $stored_status,
			'label' => isset( $status_options[ $stored_status ] ) ? $status_options[ $stored_status ] : ucfirst( $stored_status ),
		);
	}

	$start_timestamp = wp_livescore_la_get_match_start_timestamp( $match_id );

	if ( $start_timestamp <= 0 ) {
		return array(
			'key'   => '' !== $stored_status ? $stored_status : 'scheduled',
			'label' => isset( $status_options[ $stored_status ] ) ? $status_options[ $stored_status ] : __( 'Scheduled', 'wp-livescore-la' ),
		);
	}

	$now_timestamp = time();
	$end_timestamp = $start_timestamp + ( 2 * HOUR_IN_SECONDS );

	if ( $now_timestamp < $start_timestamp ) {
		return array(
			'key'   => 'upcoming',
			'label' => sprintf(
				/* translators: %s: relative time until match starts. */
				__( 'Upcoming in %s', 'wp-livescore-la' ),
				human_time_diff( $now_timestamp, $start_timestamp )
			),
		);
	}

	if ( $now_timestamp <= $end_timestamp ) {
		return array(
			'key'   => 'live',
			'label' => __( 'Live', 'wp-livescore-la' ),
		);
	}

	return array(
		'key'   => 'ended',
		'label' => sprintf(
			/* translators: %s: relative time since the match ended. */
			__( 'Match ended %s ago', 'wp-livescore-la' ),
			human_time_diff( $end_timestamp, $now_timestamp )
		),
	);
}

/**
 * Get a calculated Match status label.
 *
 * @param int $match_id Match post ID.
 * @return string
 */
function wp_livescore_la_get_match_status_label( $match_id ) {
	$status = wp_livescore_la_get_match_status( $match_id );
	return isset( $status['label'] ) ? (string) $status['label'] : '';
}

/**
 * Sanitize a SportScore slug while preserving forward slashes.
 *
 * @param string $slug Raw slug.
 * @return string
 */
function wp_livescore_la_sanitize_sportscore_slug( $slug ) {
	$slug = trim( wp_strip_all_tags( (string) $slug ) );
	$slug = preg_replace( '#/+#', '/', $slug );
	$slug = trim( $slug, '/' );

	$parts = array_filter(
		array_map( 'sanitize_title', explode( '/', $slug ) ),
		function ( $part ) {
			return '' !== $part;
		}
	);

	return implode( '/', $parts );
}

/**
 * Register Match meta.
 */
function wp_livescore_la_register_match_meta() {
	foreach ( wp_livescore_la_match_meta_fields() as $meta_key => $label ) {
		$is_int = in_array( $meta_key, array( '_match_sport_id', '_match_country_id', '_match_league_id', '_match_season_id', '_match_home_team_id', '_match_away_team_id' ), true );

		register_post_meta(
			'match',
			$meta_key,
			array(
				'type'              => $is_int ? 'integer' : 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => '_match_sportscore_slug' === $meta_key ? 'wp_livescore_la_sanitize_sportscore_slug' : ( $is_int ? 'absint' : 'sanitize_text_field' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		'match',
		'_linked_post_tag_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_match_meta' );

/**
 * Get linked Match tag ID.
 *
 * @param int $match_id Match post ID.
 * @return int
 */
function get_match_linked_tag_id( $match_id ) {
	return (int) get_post_meta( $match_id, '_linked_post_tag_id', true );
}

/**
 * Sync a Match to a normal WordPress post tag.
 *
 * @param int $match_id Match post ID.
 * @return int
 */
function sync_match_to_post_tag( $match_id ) {
	$match = get_post( $match_id );

	if ( ! $match instanceof WP_Post || 'match' !== $match->post_type || in_array( $match->post_status, array( 'auto-draft', 'trash' ), true ) ) {
		return 0;
	}

	$tag_name = sanitize_text_field( $match->post_title );
	$tag_slug = '' !== $match->post_name ? sanitize_title( $match->post_name ) : sanitize_title( $tag_name );

	if ( '' === $tag_name || '' === $tag_slug ) {
		return 0;
	}

	$linked_tag_id = get_match_linked_tag_id( $match_id );
	$linked_tag    = $linked_tag_id > 0 ? get_term( $linked_tag_id, 'post_tag' ) : null;
	$slug_match    = get_term_by( 'slug', $tag_slug, 'post_tag' );

	if ( $linked_tag instanceof WP_Term && ! is_wp_error( $linked_tag ) ) {
		$target_id = $slug_match instanceof WP_Term && (int) $slug_match->term_id !== (int) $linked_tag->term_id ? (int) $slug_match->term_id : (int) $linked_tag->term_id;
		$updated   = wp_update_term(
			$target_id,
			'post_tag',
			array(
				'name' => $tag_name,
				'slug' => $tag_slug,
			)
		);

		if ( ! is_wp_error( $updated ) && isset( $updated['term_id'] ) ) {
			update_post_meta( $match_id, '_linked_post_tag_id', (int) $updated['term_id'] );
			return (int) $updated['term_id'];
		}
	}

	if ( $slug_match instanceof WP_Term ) {
		update_post_meta( $match_id, '_linked_post_tag_id', (int) $slug_match->term_id );
		return (int) $slug_match->term_id;
	}

	$inserted = wp_insert_term(
		$tag_name,
		'post_tag',
		array(
			'slug' => $tag_slug,
		)
	);

	if ( is_wp_error( $inserted ) ) {
		$existing_id = (int) $inserted->get_error_data( 'term_exists' );

		if ( $existing_id > 0 ) {
			update_post_meta( $match_id, '_linked_post_tag_id', $existing_id );
			return $existing_id;
		}

		return 0;
	}

	if ( isset( $inserted['term_id'] ) ) {
		update_post_meta( $match_id, '_linked_post_tag_id', (int) $inserted['term_id'] );
		return (int) $inserted['term_id'];
	}

	return 0;
}

/**
 * Sync the Match tag after Match saves.
 *
 * @param int $post_id Match post ID.
 */
function wp_livescore_la_sync_match_tag_on_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	sync_match_to_post_tag( $post_id );
}
add_action( 'save_post_match', 'wp_livescore_la_sync_match_tag_on_save', 30 );

/**
 * Add Match Details meta box.
 */
function wp_livescore_la_add_match_meta_box() {
	add_meta_box(
		'wp-livescore-la-match-details',
		__( 'Match Details', 'wp-livescore-la' ),
		'wp_livescore_la_render_match_meta_box',
		'match',
		'normal',
		'default'
	);

	add_meta_box(
		'wp-livescore-la-match-prediction-post',
		__( 'Prediction Blog', 'wp-livescore-la' ),
		'wp_livescore_la_render_match_prediction_meta_box',
		'match',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_match', 'wp_livescore_la_add_match_meta_box' );

/**
 * Render the Match editor button for creating a prediction draft.
 *
 * @param WP_Post $post Match post.
 */
function wp_livescore_la_render_match_prediction_meta_box( $post ) {
	if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( 'edit_post', $post->ID ) || in_array( $post->post_status, array( 'auto-draft', 'trash' ), true ) ) {
		esc_html_e( 'Save this Match before creating a prediction blog.', 'wp-livescore-la' );
		return;
	}

	$create_url = add_query_arg(
		array(
			'action'   => 'wp_livescore_la_create_match_prediction',
			'match_id' => $post->ID,
		),
		admin_url( 'admin-post.php' )
	);
	$create_url = wp_nonce_url( $create_url, 'wp_livescore_la_create_match_prediction_' . $post->ID );
	?>
	<p><?php esc_html_e( 'Create a draft WordPress post for a Match prediction.', 'wp-livescore-la' ); ?></p>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( $create_url ); ?>">
			<?php esc_html_e( 'New Prediction Blog', 'wp-livescore-la' ); ?>
		</a>
	</p>
	<?php
}

/**
 * Create a prediction blog draft from a Match editor action.
 */
function wp_livescore_la_handle_create_match_prediction() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to create prediction posts.', 'wp-livescore-la' ) );
	}

	$match_id = isset( $_GET['match_id'] ) ? absint( wp_unslash( $_GET['match_id'] ) ) : 0;
	if ( $match_id <= 0 || 'match' !== get_post_type( $match_id ) || ! current_user_can( 'edit_post', $match_id ) ) {
		wp_die( esc_html__( 'The selected Match could not be used for a prediction post.', 'wp-livescore-la' ) );
	}

	check_admin_referer( 'wp_livescore_la_create_match_prediction_' . $match_id );

	$match_title = sanitize_text_field( get_the_title( $match_id ) );
	$post_title  = '' !== $match_title
		? sprintf(
			/* translators: %s: Match title. */
			__( '%s Prediction', 'wp-livescore-la' ),
			$match_title
		)
		: __( 'Match Prediction', 'wp-livescore-la' );

	$prediction_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'   => 'post',
				'post_status' => 'draft',
				'post_title'  => $post_title,
				'post_author' => get_current_user_id(),
			)
		),
		true
	);

	if ( is_wp_error( $prediction_id ) || $prediction_id <= 0 ) {
		wp_die( esc_html__( 'The prediction post could not be created.', 'wp-livescore-la' ) );
	}

	update_post_meta( $prediction_id, WP_LIVESCORE_LA_META_PREFIX . 'prediction_match_id', $match_id );

	$tag_id = get_match_linked_tag_id( $match_id );
	if ( $tag_id <= 0 ) {
		$tag_id = sync_match_to_post_tag( $match_id );
	}

	if ( $tag_id > 0 ) {
		wp_set_post_terms( $prediction_id, array( $tag_id ), 'post_tag', false );
	}

	$edit_url = get_edit_post_link( $prediction_id, 'raw' );
	wp_safe_redirect( $edit_url ? $edit_url : admin_url( 'post.php?post=' . $prediction_id . '&action=edit' ) );
	exit;
}
add_action( 'admin_post_wp_livescore_la_create_match_prediction', 'wp_livescore_la_handle_create_match_prediction' );

/**
 * Render Match Details meta box.
 *
 * @param WP_Post $post Match post.
 */
function wp_livescore_la_render_match_meta_box( $post ) {
	wp_nonce_field( 'wp_livescore_la_save_match_meta', 'wp_livescore_la_match_meta_nonce' );

	$sport_id = (int) get_post_meta( $post->ID, '_match_sport_id', true );
	$country_id = (int) get_post_meta( $post->ID, '_match_country_id', true );
	$league_id = (int) get_post_meta( $post->ID, '_match_league_id', true );
	$season_id = (int) get_post_meta( $post->ID, '_match_season_id', true );
	$home_team_id = (int) get_post_meta( $post->ID, '_match_home_team_id', true );
	$away_team_id = (int) get_post_meta( $post->ID, '_match_away_team_id', true );
	$status = get_post_meta( $post->ID, '_match_status', true );
	$visibility = get_post_meta( $post->ID, '_match_status_visibility', true );
	$visibility = '' !== $visibility ? $visibility : 'active';
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr><th scope="row"><label for="wp_livescore_la_match_sport_id"><?php esc_html_e( 'Sport', 'wp-livescore-la' ); ?></label></th><td><?php wp_livescore_la_render_post_select( 'wp_livescore_la_match_sport_id', 'sport', $sport_id, __( 'Select sport', 'wp-livescore-la' ) ); ?></td></tr>
			<tr><th scope="row"><label for="wp_livescore_la_match_country_id"><?php esc_html_e( 'Country', 'wp-livescore-la' ); ?></label></th><td><?php wp_livescore_la_render_post_select( 'wp_livescore_la_match_country_id', 'country', $country_id, __( 'Select country', 'wp-livescore-la' ) ); ?></td></tr>
			<tr><th scope="row"><label for="wp_livescore_la_match_league_id"><?php esc_html_e( 'League', 'wp-livescore-la' ); ?></label></th><td><?php wp_livescore_la_render_post_select( 'wp_livescore_la_match_league_id', 'league', $league_id, __( 'Select league', 'wp-livescore-la' ) ); ?></td></tr>
			<tr><th scope="row"><label for="wp_livescore_la_match_season_id"><?php esc_html_e( 'Season', 'wp-livescore-la' ); ?></label></th><td><?php wp_livescore_la_render_season_select( 'wp_livescore_la_match_season_id', $season_id, $league_id ); ?></td></tr>
			<tr><th scope="row"><label for="wp_livescore_la_match_home_team_id"><?php esc_html_e( 'Home Team', 'wp-livescore-la' ); ?></label></th><td><?php wp_livescore_la_render_post_select( 'wp_livescore_la_match_home_team_id', 'team', $home_team_id, __( 'Select home team', 'wp-livescore-la' ) ); ?></td></tr>
			<tr><th scope="row"><label for="wp_livescore_la_match_away_team_id"><?php esc_html_e( 'Away Team', 'wp-livescore-la' ); ?></label></th><td><?php wp_livescore_la_render_post_select( 'wp_livescore_la_match_away_team_id', 'team', $away_team_id, __( 'Select away team', 'wp-livescore-la' ) ); ?></td></tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_match_status"><?php esc_html_e( 'Match Status', 'wp-livescore-la' ); ?></label></th>
				<td>
					<select id="wp_livescore_la_match_status" name="wp_livescore_la_match_meta[_match_status]">
						<?php foreach ( wp_livescore_la_match_status_options() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_match_status_visibility"><?php esc_html_e( 'Status', 'wp-livescore-la' ); ?></label></th>
				<td>
					<select id="wp_livescore_la_match_status_visibility" name="wp_livescore_la_match_meta[_match_status_visibility]">
						<option value="active" <?php selected( $visibility, 'active' ); ?>><?php esc_html_e( 'Active', 'wp-livescore-la' ); ?></option>
						<option value="inactive" <?php selected( $visibility, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wp-livescore-la' ); ?></option>
					</select>
				</td>
			</tr>
			<?php foreach ( array( '_match_api_id', '_match_sportscore_slug', '_match_date', '_match_time', '_match_datetime', '_match_timezone', '_match_home_score', '_match_away_score', '_match_group_name', '_match_referee', '_match_venue' ) as $meta_key ) : ?>
				<?php $value = get_post_meta( $post->ID, $meta_key, true ); ?>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( wp_livescore_la_match_meta_fields()[ $meta_key ] ); ?></label></th>
					<td>
						<input
							type="<?php echo '_match_date' === $meta_key ? 'date' : ( '_match_time' === $meta_key ? 'time' : 'text' ); ?>"
							id="<?php echo esc_attr( $meta_key ); ?>"
							name="wp_livescore_la_match_meta[<?php echo esc_attr( $meta_key ); ?>]"
							value="<?php echo esc_attr( $value ); ?>"
							class="regular-text"
						/>
						<?php if ( '_match_sportscore_slug' === $meta_key ) : ?>
							<p class="description"><?php esc_html_e( 'Example: basketball/pba/home-team-vs-away-team', 'wp-livescore-la' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Save Match meta.
 *
 * @param int $post_id Match post ID.
 */
function wp_livescore_la_save_match_meta( $post_id ) {
	if ( ! isset( $_POST['wp_livescore_la_match_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_match_meta_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'wp_livescore_la_save_match_meta' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$home_team_id = isset( $_POST['wp_livescore_la_match_home_team_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_match_home_team_id'] ) ) : 0;
	$away_team_id = isset( $_POST['wp_livescore_la_match_away_team_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_match_away_team_id'] ) ) : 0;
	if ( $home_team_id > 0 && $home_team_id === $away_team_id ) {
		$away_team_id = 0;
	}

	wp_livescore_la_sync_match_sport_meta( $post_id, isset( $_POST['wp_livescore_la_match_sport_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_match_sport_id'] ) ) : 0 );
	wp_livescore_la_sync_match_country_meta( $post_id, isset( $_POST['wp_livescore_la_match_country_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_match_country_id'] ) ) : 0 );
	wp_livescore_la_sync_match_league_meta( $post_id, isset( $_POST['wp_livescore_la_match_league_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_match_league_id'] ) ) : 0 );
	wp_livescore_la_sync_match_season_meta( $post_id, isset( $_POST['wp_livescore_la_match_season_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_match_season_id'] ) ) : 0 );
	wp_livescore_la_sync_match_team_meta( $post_id, $home_team_id, 'home' );
	wp_livescore_la_sync_match_team_meta( $post_id, $away_team_id, 'away' );

	$posted_meta = isset( $_POST['wp_livescore_la_match_meta'] ) && is_array( $_POST['wp_livescore_la_match_meta'] ) ? wp_unslash( $_POST['wp_livescore_la_match_meta'] ) : array();
	foreach ( array( '_match_api_id', '_match_sportscore_slug', '_match_date', '_match_time', '_match_datetime', '_match_timezone', '_match_status', '_match_home_score', '_match_away_score', '_match_group_name', '_match_referee', '_match_venue', '_match_status_visibility' ) as $meta_key ) {
		$value = isset( $posted_meta[ $meta_key ] ) ? $posted_meta[ $meta_key ] : '';
		$value = '_match_sportscore_slug' === $meta_key ? wp_livescore_la_sanitize_sportscore_slug( $value ) : sanitize_text_field( $value );
		if ( '_match_status' === $meta_key && ! array_key_exists( $value, wp_livescore_la_match_status_options() ) ) {
			$value = 'scheduled';
		}
		if ( '_match_status_visibility' === $meta_key && ! in_array( $value, array( 'active', 'inactive' ), true ) ) {
			$value = 'active';
		}
		'' === $value ? delete_post_meta( $post_id, $meta_key ) : update_post_meta( $post_id, $meta_key, $value );
	}

	wp_livescore_la_maybe_generate_match_title_slug( $post_id );
	wp_livescore_la_maybe_generate_match_sportscore_slug( $post_id, false );
}
add_action( 'save_post_match', 'wp_livescore_la_save_match_meta' );

/**
 * Sync simple post relationships into Match meta.
 */
function wp_livescore_la_sync_match_sport_meta( $match_id, $sport_id ) {
	wp_livescore_la_sync_match_post_meta( $match_id, $sport_id, 'sport', '_match_sport' );
}

function wp_livescore_la_sync_match_country_meta( $match_id, $country_id ) {
	wp_livescore_la_sync_match_post_meta( $match_id, $country_id, 'country', '_match_country' );
	if ( $country_id > 0 && 'country' === get_post_type( $country_id ) ) {
		update_post_meta( $match_id, '_match_country_code', sanitize_text_field( get_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true ) ) );
		update_post_meta( $match_id, '_match_continent', sanitize_text_field( get_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_continent', true ) ) );
	}
}

function wp_livescore_la_sync_match_league_meta( $match_id, $league_id ) {
	wp_livescore_la_sync_match_post_meta( $match_id, $league_id, 'league', '_match_league' );
}

function wp_livescore_la_sync_match_post_meta( $match_id, $post_id, $post_type, $prefix ) {
	if ( 'sport' === $post_type ) {
		$term = $post_id > 0 ? get_term( $post_id, 'sport' ) : null;
		if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
			update_post_meta( $match_id, $prefix . '_id', (int) $term->term_id );
			update_post_meta( $match_id, $prefix . '_name', sanitize_text_field( $term->name ) );
			update_post_meta( $match_id, $prefix . '_slug', sanitize_title( $term->slug ) );
			wp_set_object_terms( $match_id, array( (int) $term->term_id ), 'sport', false );
			return;
		}
	}

	if ( $post_id > 0 && $post_type === get_post_type( $post_id ) ) {
		update_post_meta( $match_id, $prefix . '_id', $post_id );
		update_post_meta( $match_id, $prefix . '_name', sanitize_text_field( get_the_title( $post_id ) ) );
		update_post_meta( $match_id, $prefix . '_slug', sanitize_title( get_post_field( 'post_name', $post_id ) ) );
		return;
	}

	delete_post_meta( $match_id, $prefix . '_id' );
	delete_post_meta( $match_id, $prefix . '_name' );
	delete_post_meta( $match_id, $prefix . '_slug' );
}

function wp_livescore_la_sync_match_season_meta( $match_id, $season_id ) {
	$term = $season_id > 0 ? get_term( $season_id, 'league_season' ) : null;
	if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
		update_post_meta( $match_id, '_match_season_id', (int) $term->term_id );
		update_post_meta( $match_id, '_match_season_name', sanitize_text_field( $term->name ) );
		update_post_meta( $match_id, '_match_season_slug', sanitize_title( $term->slug ) );
		return;
	}

	delete_post_meta( $match_id, '_match_season_id' );
	delete_post_meta( $match_id, '_match_season_name' );
	delete_post_meta( $match_id, '_match_season_slug' );
}

function wp_livescore_la_sync_match_team_meta( $match_id, $team_id, $side ) {
	if ( ! in_array( $side, array( 'home', 'away' ), true ) ) {
		return;
	}
	wp_livescore_la_sync_match_post_meta( $match_id, $team_id, 'team', '_match_' . $side . '_team' );
}

/**
 * Generate public Match title and slug from teams when suitable.
 *
 * @param int $match_id Match post ID.
 */
function wp_livescore_la_maybe_generate_match_title_slug( $match_id ) {
	$home = get_post_meta( $match_id, '_match_home_team_name', true );
	$away = get_post_meta( $match_id, '_match_away_team_name', true );
	if ( '' === $home || '' === $away ) {
		return;
	}

	$title = sanitize_text_field( $home . ' vs ' . $away );
	$post = get_post( $match_id );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$post_data = array( 'ID' => $match_id );
	if ( '' === trim( $post->post_title ) || 'Auto Draft' === $post->post_title ) {
		$post_data['post_title'] = $title;
	}
	if ( '' === trim( $post->post_name ) || false === strpos( $post->post_name, '-vs-' ) ) {
		$post_data['post_name'] = sanitize_title( get_post_meta( $match_id, '_match_home_team_slug', true ) . '-vs-' . get_post_meta( $match_id, '_match_away_team_slug', true ) );
	}
	if ( count( $post_data ) > 1 ) {
		remove_action( 'save_post_match', 'wp_livescore_la_save_match_meta' );
		wp_update_post( wp_slash( $post_data ) );
		add_action( 'save_post_match', 'wp_livescore_la_save_match_meta' );
	}
}

/**
 * Generate fallback SportScore slug only if empty or refresh is requested.
 *
 * @param int  $match_id Match post ID.
 * @param bool $force    Whether to overwrite existing value.
 */
function wp_livescore_la_maybe_generate_match_sportscore_slug( $match_id, $force = false ) {
	$current = get_post_meta( $match_id, '_match_sportscore_slug', true );
	if ( ! $force && '' !== $current ) {
		return;
	}

	$parts = array(
		get_post_meta( $match_id, '_match_sport_slug', true ),
		get_post_meta( $match_id, '_match_league_slug', true ),
		get_post_meta( $match_id, '_match_home_team_slug', true ) . '-vs-' . get_post_meta( $match_id, '_match_away_team_slug', true ),
	);
	$slug = wp_livescore_la_sanitize_sportscore_slug( implode( '/', array_filter( $parts ) ) );
	if ( '' !== $slug ) {
		update_post_meta( $match_id, '_match_sportscore_slug', $slug );
	}
}

/**
 * Find an existing Match by API ID, SportScore slug, or relationship/date.
 *
 * @param array $criteria Match criteria.
 * @return int
 */
function wp_livescore_la_find_match_post( $criteria ) {
	if ( ! empty( $criteria['api_id'] ) ) {
		$matches = get_posts( array( 'post_type' => 'match', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_key' => '_match_api_id', 'meta_value' => sanitize_text_field( $criteria['api_id'] ) ) );
		if ( ! empty( $matches ) ) {
			return (int) $matches[0];
		}
	}

	if ( ! empty( $criteria['sportscore_slug'] ) ) {
		$matches = get_posts( array( 'post_type' => 'match', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_key' => '_match_sportscore_slug', 'meta_value' => wp_livescore_la_sanitize_sportscore_slug( $criteria['sportscore_slug'] ) ) );
		if ( ! empty( $matches ) ) {
			return (int) $matches[0];
		}
	}

	$meta_query = array( 'relation' => 'AND' );
	foreach ( array( '_match_league_id' => 'league_id', '_match_season_id' => 'season_id', '_match_home_team_id' => 'home_team_id', '_match_away_team_id' => 'away_team_id', '_match_date' => 'date' ) as $meta_key => $key ) {
		if ( empty( $criteria[ $key ] ) ) {
			return 0;
		}
		$meta_query[] = array( 'key' => $meta_key, 'value' => sanitize_text_field( (string) $criteria[ $key ] ) );
	}

	$matches = get_posts( array( 'post_type' => 'match', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_query' => $meta_query ) );
	return ! empty( $matches ) ? (int) $matches[0] : 0;
}

/**
 * Extract Match records from common API response shapes.
 *
 * @param mixed $payload Decoded JSON payload.
 * @return array
 */
function wp_livescore_la_extract_match_records( $payload ) {
	if ( ! is_array( $payload ) ) {
		return array();
	}

	foreach ( array( 'events', 'matches', 'match', 'results', 'data', 'items' ) as $key ) {
		if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
			return wp_livescore_la_extract_match_records( $payload[ $key ] );
		}
	}

	return wp_livescore_la_is_list( $payload ) ? $payload : array( $payload );
}

/**
 * Import or update Match posts from API records.
 *
 * @param array  $records API records.
 * @param string $api_source API source.
 * @return array
 */
function wp_livescore_la_import_matches( $records, $api_source = '' ) {
	$result = array( 'created' => 0, 'updated' => 0, 'skipped' => 0 );

	foreach ( $records as $record ) {
		if ( ! is_array( $record ) ) {
			$result['skipped']++;
			continue;
		}

		$home_name = wp_livescore_la_record_value( $record, array( 'strHomeTeam', 'home_team', 'homeTeam', 'home' ) );
		$away_name = wp_livescore_la_record_value( $record, array( 'strAwayTeam', 'away_team', 'awayTeam', 'away' ) );
		$title = wp_livescore_la_record_value( $record, array( 'strEvent', 'name', 'title', 'event' ) );
		if ( '' === $title && '' !== $home_name && '' !== $away_name ) {
			$title = $home_name . ' vs ' . $away_name;
		}
		if ( '' === $title ) {
			$result['skipped']++;
			continue;
		}

		$api_id = wp_livescore_la_record_value( $record, array( 'idEvent', 'idMatch', 'api_id', 'id', 'event_id', 'match_id' ) );
		$sportscore_slug = wp_livescore_la_record_value( $record, array( 'sportscore_slug', 'strSportScoreSlug', 'slug' ) );
		$league_id = wp_livescore_la_match_import_league_id( $record );
		$season_id = wp_livescore_la_match_import_season_id( $record, $league_id );
		$home_team_id = wp_livescore_la_match_import_team_id( $record, 'home', $league_id );
		$away_team_id = wp_livescore_la_match_import_team_id( $record, 'away', $league_id );
		$date = wp_livescore_la_record_value( $record, array( 'dateEvent', 'date', 'match_date' ) );
		$date = '' !== $date ? sanitize_text_field( $date ) : '';

		$post_id = wp_livescore_la_find_match_post(
			array(
				'api_id'          => $api_id,
				'sportscore_slug' => $sportscore_slug,
				'league_id'       => $league_id,
				'season_id'       => $season_id,
				'home_team_id'    => $home_team_id,
				'away_team_id'    => $away_team_id,
				'date'            => $date,
			)
		);

		$post_data = array(
			'post_type'    => 'match',
			'post_status'  => 'publish',
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => wp_kses_post( wp_livescore_la_record_value( $record, array( 'strDescriptionEN', 'description', 'summary', 'content' ) ) ),
		);

		if ( '' !== $home_name && '' !== $away_name ) {
			$post_data['post_name'] = sanitize_title( $home_name . '-vs-' . $away_name );
		}

		if ( $post_id > 0 ) {
			$post_data['ID'] = $post_id;
			$saved_id = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$saved_id = wp_insert_post( wp_slash( $post_data ), true );
		}

		if ( is_wp_error( $saved_id ) || $saved_id <= 0 ) {
			$result['skipped']++;
			continue;
		}

		wp_livescore_la_update_match_import_meta( $saved_id, $record, $league_id, $season_id, $home_team_id, $away_team_id, $api_id, $api_source );
		$post_id > 0 ? $result['updated']++ : $result['created']++;
	}

	return $result;
}

function wp_livescore_la_match_import_league_id( $record ) {
	$league_id = wp_livescore_la_find_league_post( wp_livescore_la_record_value( $record, array( 'idLeague', 'league_id' ) ), wp_livescore_la_record_value( $record, array( 'strLeague', 'league', 'League' ) ) );
	if ( $league_id > 0 ) {
		return $league_id;
	}

	$league_name = wp_livescore_la_record_value( $record, array( 'strLeague', 'league', 'League' ) );
	if ( '' === $league_name ) {
		return 0;
	}

	$imported = wp_livescore_la_import_leagues( array( array( 'name' => $league_name, 'api_id' => wp_livescore_la_record_value( $record, array( 'idLeague', 'league_id' ) ), 'sport' => wp_livescore_la_record_value( $record, array( 'strSport', 'sport' ) ), 'country' => wp_livescore_la_record_value( $record, array( 'strCountry', 'country' ) ) ) ) );
	return wp_livescore_la_find_league_post( wp_livescore_la_record_value( $record, array( 'idLeague', 'league_id' ) ), $league_name );
}

function wp_livescore_la_match_import_season_id( $record, $league_id ) {
	$season = wp_livescore_la_record_value( $record, array( 'strSeason', 'season', 'Season', 'strCurrentSeason' ) );
	return '' !== $season && $league_id > 0 ? wp_livescore_la_sync_league_season_term( $league_id, $season ) : 0;
}

function wp_livescore_la_match_import_team_id( $record, $side, $league_id = 0 ) {
	$is_home = 'home' === $side;
	$name = wp_livescore_la_record_value( $record, $is_home ? array( 'strHomeTeam', 'home_team', 'homeTeam', 'home' ) : array( 'strAwayTeam', 'away_team', 'awayTeam', 'away' ) );
	$api_id = wp_livescore_la_record_value( $record, $is_home ? array( 'idHomeTeam', 'home_team_id', 'homeTeamId' ) : array( 'idAwayTeam', 'away_team_id', 'awayTeamId' ) );
	if ( '' === $name && '' === $api_id ) {
		return 0;
	}

	$team_id = wp_livescore_la_find_team_post( $api_id, $name );
	if ( $team_id > 0 ) {
		return $team_id;
	}

	$team_record = array(
		'name'      => $name,
		'api_id'    => $api_id,
		'strSport'  => wp_livescore_la_record_value( $record, array( 'strSport', 'sport' ) ),
		'strCountry'=> wp_livescore_la_record_value( $record, array( 'strCountry', 'country' ) ),
		'idLeague'  => wp_livescore_la_record_value( $record, array( 'idLeague', 'league_id' ) ),
		'strLeague' => wp_livescore_la_record_value( $record, array( 'strLeague', 'league' ) ),
	);
	wp_livescore_la_import_teams( array( $team_record ) );
	return wp_livescore_la_find_team_post( $api_id, $name );
}

function wp_livescore_la_update_match_import_meta( $match_id, $record, $league_id, $season_id, $home_team_id, $away_team_id, $api_id, $api_source ) {
	if ( '' !== $api_id ) {
		update_post_meta( $match_id, '_match_api_id', sanitize_text_field( $api_id ) );
	}
	if ( '' !== $api_source ) {
		update_post_meta( $match_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', sanitize_key( $api_source ) );
	}

	$sport_id = wp_livescore_la_get_or_create_sport_id( wp_livescore_la_record_value( $record, array( 'strSport', 'sport', 'Sport' ) ) );
	wp_livescore_la_sync_match_sport_meta( $match_id, $sport_id );
	if ( '1' !== wp_livescore_la_record_value( $record, array( 'skipMatchCountry' ) ) ) {
		$country_id = wp_livescore_la_get_or_create_country_id( wp_livescore_la_record_value( $record, array( 'strCountry', 'country', 'Country' ) ), wp_livescore_la_record_value( $record, array( 'strCountryCode', 'country_code', 'code' ) ) );
		wp_livescore_la_sync_match_country_meta( $match_id, $country_id );
	}
	wp_livescore_la_sync_match_league_meta( $match_id, $league_id );
	wp_livescore_la_sync_match_season_meta( $match_id, $season_id );
	wp_livescore_la_sync_match_team_meta( $match_id, $home_team_id, 'home' );
	wp_livescore_la_sync_match_team_meta( $match_id, $away_team_id, 'away' );

	$mapping = array(
		'_match_date'       => array( 'dateEvent', 'date', 'match_date' ),
			'_match_time'       => array( 'strTime', 'time', 'match_time' ),
			'_match_datetime'   => array( 'strTimestamp', 'datetime', 'timestamp' ),
			'_match_timezone'   => array( 'timezone', 'strTimezone' ),
			'_match_group_name' => array( 'strGroup', 'group_name', 'groupName', 'group' ),
			'_match_referee'    => array( 'strReferee', 'referee', 'refereeName', 'official' ),
			'_match_venue'      => array( 'strVenue', 'venue' ),
		'_match_home_score' => array( 'intHomeScore', 'home_score' ),
		'_match_away_score' => array( 'intAwayScore', 'away_score' ),
		'_match_status'     => array( 'strStatus', 'status' ),
	);

	foreach ( $mapping as $meta_key => $keys ) {
		$value = wp_livescore_la_record_value( $record, $keys );
		if ( '' !== $value ) {
			update_post_meta( $match_id, $meta_key, sanitize_text_field( $value ) );
		}
	}
	update_post_meta( $match_id, '_match_status_visibility', 'active' );

	$imported_slug = wp_livescore_la_sanitize_sportscore_slug( wp_livescore_la_record_value( $record, array( 'sportscore_slug', 'strSportScoreSlug', 'slug' ) ) );
	if ( '' !== $imported_slug && '' === get_post_meta( $match_id, '_match_sportscore_slug', true ) ) {
		update_post_meta( $match_id, '_match_sportscore_slug', $imported_slug );
	}

	wp_livescore_la_maybe_generate_match_title_slug( $match_id );
	wp_livescore_la_maybe_generate_match_sportscore_slug( $match_id, false );
}

/**
 * Append readable match details on single Match pages.
 *
 * @param string $content Existing content.
 * @return string
 */
function wp_livescore_la_append_match_details_to_content( $content ) {
	if ( ! is_singular( 'match' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$match_id = get_the_ID();
	$fields = array(
		__( 'League', 'wp-livescore-la' )  => get_post_meta( $match_id, '_match_league_name', true ),
		__( 'Season', 'wp-livescore-la' )  => get_post_meta( $match_id, '_match_season_name', true ),
		__( 'Sport', 'wp-livescore-la' )   => get_post_meta( $match_id, '_match_sport_name', true ),
		__( 'Country', 'wp-livescore-la' ) => get_post_meta( $match_id, '_match_country_name', true ),
			__( 'Date', 'wp-livescore-la' )    => wp_livescore_la_format_match_date( get_post_meta( $match_id, '_match_date', true ) ),
			__( 'Time', 'wp-livescore-la' )    => get_post_meta( $match_id, '_match_time', true ),
			__( 'Group', 'wp-livescore-la' )   => get_post_meta( $match_id, '_match_group_name', true ),
			__( 'Referee', 'wp-livescore-la' ) => get_post_meta( $match_id, '_match_referee', true ),
			__( 'Venue', 'wp-livescore-la' )   => get_post_meta( $match_id, '_match_venue', true ),
		__( 'Status', 'wp-livescore-la' )  => wp_livescore_la_get_match_status_label( $match_id ),
	);

	$home_team_id = (int) get_post_meta( $match_id, '_match_home_team_id', true );
	$away_team_id = (int) get_post_meta( $match_id, '_match_away_team_id', true );
	$sportscore_slug = get_post_meta( $match_id, '_match_sportscore_slug', true );

	ob_start();
	?>
	<div class="wp-livescore-la-match-details">
		<div class="wp-livescore-la-match-details__scoreboard">
			<?php wp_livescore_la_render_match_team_side( $home_team_id, get_post_meta( $match_id, '_match_home_team_name', true ) ); ?>
			<div class="wp-livescore-la-match-details__score">
				<span><?php echo esc_html( get_post_meta( $match_id, '_match_home_score', true ) ); ?></span>
				<span>:</span>
				<span><?php echo esc_html( get_post_meta( $match_id, '_match_away_score', true ) ); ?></span>
			</div>
			<?php wp_livescore_la_render_match_team_side( $away_team_id, get_post_meta( $match_id, '_match_away_team_name', true ) ); ?>
		</div>
		<dl class="wp-livescore-la-match-details__list">
			<?php foreach ( $fields as $label => $value ) : ?>
				<?php if ( '' !== (string) $value ) : ?>
					<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
				<?php endif; ?>
			<?php endforeach; ?>
		</dl>
		<?php if ( '' !== $sportscore_slug ) : ?>
			<p class="wp-livescore-la-match-details__sportscore"><?php echo esc_html__( 'SportScore:', 'wp-livescore-la' ); ?> <code><?php echo esc_html( $sportscore_slug ); ?></code></p>
		<?php endif; ?>
	</div>
	<?php

	return $content . ob_get_clean();
}
add_filter( 'the_content', 'wp_livescore_la_append_match_details_to_content' );

function wp_livescore_la_render_match_team_side( $team_id, $fallback_name ) {
	$name = $team_id > 0 ? get_the_title( $team_id ) : $fallback_name;
	$url = $team_id > 0 ? get_permalink( $team_id ) : '';
	?>
	<div class="wp-livescore-la-match-details__team">
		<?php if ( $team_id > 0 && has_post_thumbnail( $team_id ) ) : ?>
			<?php echo get_the_post_thumbnail( $team_id, 'thumbnail' ); ?>
		<?php endif; ?>
		<?php if ( '' !== $url ) : ?>
			<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
		<?php else : ?>
			<span><?php echo esc_html( $name ); ?></span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Add useful admin columns.
 */
function wp_livescore_la_match_admin_columns( $columns ) {
	$columns['match_league'] = __( 'League', 'wp-livescore-la' );
	$columns['match_season'] = __( 'Season', 'wp-livescore-la' );
	$columns['match_sport'] = __( 'Sport', 'wp-livescore-la' );
	$columns['match_teams'] = __( 'Teams', 'wp-livescore-la' );
	$columns['match_date'] = __( 'Match Date', 'wp-livescore-la' );
	$columns['match_group'] = __( 'Group', 'wp-livescore-la' );
	$columns['match_status'] = __( 'Status', 'wp-livescore-la' );
	$columns['match_score'] = __( 'Score', 'wp-livescore-la' );
	$columns['match_sportscore_slug'] = __( 'SportScore Slug', 'wp-livescore-la' );
	$columns['match_api_id'] = __( 'API ID', 'wp-livescore-la' );
	return $columns;
}
add_filter( 'manage_match_posts_columns', 'wp_livescore_la_match_admin_columns' );

function wp_livescore_la_match_admin_column_content( $column, $post_id ) {
	$values = array(
		'match_league' => get_post_meta( $post_id, '_match_league_name', true ),
		'match_season' => get_post_meta( $post_id, '_match_season_name', true ),
		'match_sport' => get_post_meta( $post_id, '_match_sport_name', true ),
		'match_teams' => get_post_meta( $post_id, '_match_home_team_name', true ) . ' vs ' . get_post_meta( $post_id, '_match_away_team_name', true ),
		'match_date' => get_post_meta( $post_id, '_match_date', true ),
		'match_group' => get_post_meta( $post_id, '_match_group_name', true ),
		'match_status' => get_post_meta( $post_id, '_match_status', true ),
		'match_score' => get_post_meta( $post_id, '_match_home_score', true ) . ' - ' . get_post_meta( $post_id, '_match_away_score', true ),
		'match_sportscore_slug' => get_post_meta( $post_id, '_match_sportscore_slug', true ),
		'match_api_id' => get_post_meta( $post_id, '_match_api_id', true ),
	);
	if ( isset( $values[ $column ] ) ) {
		echo '' !== trim( (string) $values[ $column ] ) && ' vs ' !== $values[ $column ] && ' - ' !== $values[ $column ] ? esc_html( $values[ $column ] ) : '&mdash;';
	}
}
add_action( 'manage_match_posts_custom_column', 'wp_livescore_la_match_admin_column_content', 10, 2 );
