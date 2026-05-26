<?php
/**
 * Server render for Match Counter block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$league_id = ! empty( $attributes['leagueId'] ) ? absint( $attributes['leagueId'] ) : 0;

if ( $league_id <= 0 && isset( $block->context['postId'] ) && 'league' === get_post_type( (int) $block->context['postId'] ) ) {
	$league_id = (int) $block->context['postId'];
}

if ( $league_id > 0 && 'league' !== get_post_type( $league_id ) ) {
	$league_id = 0;
}

$now_time = current_time( 'timestamp' );
$today    = current_time( 'Y-m-d' );

$date_queries = array(
	'all'      => array(),
	'upcoming' => array(
		'relation' => 'OR',
		array(
			'key'     => '_match_datetime',
			'value'   => wp_date( 'Y-m-d H:i:s', $now_time ),
			'compare' => '>',
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
				'compare' => '>',
				'type'    => 'DATE',
			),
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
				'value'   => wp_date( 'H:i:s', $now_time ),
				'compare' => '>',
				'type'    => 'TIME',
			),
		),
	),
	'live'     => function_exists( 'wp_livescore_la_get_match_date_filter_meta_query' ) ? wp_livescore_la_get_match_date_filter_meta_query( 'live' ) : array(),
	'today'    => array(
		'key'     => '_match_date',
		'value'   => $today,
		'compare' => '=',
		'type'    => 'DATE',
	),
	'past'     => array(
		'relation' => 'OR',
		array(
			'key'     => '_match_datetime',
			'value'   => wp_date( 'Y-m-d H:i:s', $now_time - ( 2 * HOUR_IN_SECONDS ) ),
			'compare' => '<',
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
				'compare' => '<',
				'type'    => 'DATE',
			),
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
				'value'   => wp_date( 'H:i:s', $now_time - ( 2 * HOUR_IN_SECONDS ) ),
				'compare' => '<',
				'type'    => 'TIME',
			),
		),
	),
);

$count_matches = function ( $date_query ) use ( $league_id ) {
	$meta_query = array( 'relation' => 'AND' );

	if ( $league_id > 0 ) {
		$meta_query[] = array(
			'key'   => '_match_league_id',
			'value' => $league_id,
		);
	}

	if ( ! empty( $date_query ) ) {
		$meta_query[] = $date_query;
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'match',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'meta_query'          => $meta_query,
		)
	);

	return (int) $query->found_posts;
};

$items = array();

if ( ! array_key_exists( 'showAll', $attributes ) || ! empty( $attributes['showAll'] ) ) {
	$items[] = array(
		'key'   => 'all',
		'label' => isset( $attributes['allLabel'] ) && '' !== $attributes['allLabel'] ? sanitize_text_field( $attributes['allLabel'] ) : __( 'All', 'wp-livescore-la' ),
		'count' => $count_matches( $date_queries['all'] ),
	);
}

if ( ! array_key_exists( 'showUpcoming', $attributes ) || ! empty( $attributes['showUpcoming'] ) ) {
	$items[] = array(
		'key'   => 'upcoming',
		'label' => isset( $attributes['upcomingLabel'] ) && '' !== $attributes['upcomingLabel'] ? sanitize_text_field( $attributes['upcomingLabel'] ) : __( 'Upcoming', 'wp-livescore-la' ),
		'count' => $count_matches( $date_queries['upcoming'] ),
	);
}

if ( ! array_key_exists( 'showLive', $attributes ) || ! empty( $attributes['showLive'] ) ) {
	$items[] = array(
		'key'   => 'live',
		'label' => isset( $attributes['liveLabel'] ) && '' !== $attributes['liveLabel'] ? sanitize_text_field( $attributes['liveLabel'] ) : __( 'Live', 'wp-livescore-la' ),
		'count' => $count_matches( $date_queries['live'] ),
	);
}

if ( ! array_key_exists( 'showToday', $attributes ) || ! empty( $attributes['showToday'] ) ) {
	$items[] = array(
		'key'   => 'today',
		'label' => isset( $attributes['todayLabel'] ) && '' !== $attributes['todayLabel'] ? sanitize_text_field( $attributes['todayLabel'] ) : __( 'Today', 'wp-livescore-la' ),
		'count' => $count_matches( $date_queries['today'] ),
	);
}

if ( ! array_key_exists( 'showPast', $attributes ) || ! empty( $attributes['showPast'] ) ) {
	$items[] = array(
		'key'   => 'past',
		'label' => isset( $attributes['pastLabel'] ) && '' !== $attributes['pastLabel'] ? sanitize_text_field( $attributes['pastLabel'] ) : __( 'Past', 'wp-livescore-la' ),
		'count' => $count_matches( $date_queries['past'] ),
	);
}

if ( empty( $items ) ) {
	return '';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-match-counter',
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php foreach ( $items as $item ) : ?>
		<div class="wp-livescore-la-match-counter__item wp-livescore-la-match-counter__item--<?php echo esc_attr( $item['key'] ); ?>">
			<span class="wp-livescore-la-match-counter__count"><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?></span>
			<span class="wp-livescore-la-match-counter__label"><?php echo esc_html( $item['label'] ); ?></span>
		</div>
	<?php endforeach; ?>
</div>
