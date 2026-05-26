<?php
/**
 * Server render for Match List block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Matches', 'wp-livescore-la' );
$posts_per_page = isset( $attributes['postsPerPage'] ) ? max( 1, min( 50, (int) $attributes['postsPerPage'] ) ) : 10;
$display_style = isset( $attributes['displayStyle'] ) ? sanitize_key( $attributes['displayStyle'] ) : 'list';
$layout_type = isset( $attributes['layoutType'] ) ? sanitize_key( $attributes['layoutType'] ) : 'grid';
$layout_type = in_array( $layout_type, array( 'grid', 'carousel' ), true ) ? $layout_type : 'grid';
$date_filter = isset( $attributes['dateFilter'] ) ? sanitize_key( $attributes['dateFilter'] ) : 'today';

$meta_query = array( 'relation' => 'AND' );
$filters = array(
	'_match_sport_slug'   => isset( $attributes['sportSlug'] ) ? sanitize_title( $attributes['sportSlug'] ) : '',
	'_match_country_slug' => isset( $attributes['countrySlug'] ) ? sanitize_title( $attributes['countrySlug'] ) : '',
	'_match_league_id'    => ! empty( $attributes['leagueId'] ) ? absint( $attributes['leagueId'] ) : '',
	'_match_season_id'    => ! empty( $attributes['seasonId'] ) ? absint( $attributes['seasonId'] ) : '',
	'_match_status'       => isset( $attributes['status'] ) ? sanitize_key( $attributes['status'] ) : '',
);

foreach ( $filters as $meta_key => $value ) {
	if ( '' !== (string) $value ) {
		$meta_query[] = array( 'key' => $meta_key, 'value' => $value );
	}
}

if ( function_exists( 'wp_livescore_la_get_match_url_filter_meta_queries' ) ) {
	foreach ( wp_livescore_la_get_match_url_filter_meta_queries() as $filter_query ) {
		$meta_query[] = $filter_query;
	}
}

if ( ! empty( $attributes['teamId'] ) ) {
	$team_id = absint( $attributes['teamId'] );
	$meta_query[] = array(
		'relation' => 'OR',
		array( 'key' => '_match_home_team_id', 'value' => $team_id ),
		array( 'key' => '_match_away_team_id', 'value' => $team_id ),
	);
}

if ( function_exists( 'wp_livescore_la_get_match_date_filter_meta_query' ) ) {
	$date_query = wp_livescore_la_get_match_date_filter_meta_query(
		$date_filter,
		isset( $attributes['customDate'] ) ? $attributes['customDate'] : ''
	);

	if ( ! empty( $date_query ) ) {
		$meta_query[] = $date_query;
	}
}

$query = new WP_Query(
	array(
		'post_type'           => 'match',
		'post_status'         => 'publish',
		'posts_per_page'      => $posts_per_page,
		'ignore_sticky_posts' => true,
		'meta_query'          => count( $meta_query ) > 1 ? $meta_query : array(),
		'meta_key'            => '_match_datetime',
		'orderby'             => 'meta_value',
		'order'               => in_array( $date_filter, array( 'past', 'results' ), true ) ? 'DESC' : 'ASC',
	)
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-match-list wp-livescore-la-match-list--' . $display_style . ' wp-livescore-la-match-list--' . $layout_type,
	)
);
?>
<section <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( '' !== $title ) : ?>
		<h2 class="wp-livescore-la-match-list__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<?php if ( $query->have_posts() ) : ?>
		<div class="wp-livescore-la-match-list__items">
			<?php while ( $query->have_posts() ) : ?>
					<?php $query->the_post(); ?>
					<?php
					$match_id   = get_the_ID();
					$match_date = get_post_meta( $match_id, '_match_date', true );
					$match_date = function_exists( 'wp_livescore_la_format_match_date' ) ? wp_livescore_la_format_match_date( $match_date ) : $match_date;
					$match_status = function_exists( 'wp_livescore_la_get_match_status_label' ) ? wp_livescore_la_get_match_status_label( $match_id ) : get_post_meta( $match_id, '_match_status', true );
					?>
				<article class="wp-livescore-la-match-list__item">
					<a class="wp-livescore-la-match-list__teams" href="<?php echo esc_url( get_permalink() ); ?>">
						<span><?php echo esc_html( get_post_meta( $match_id, '_match_home_team_name', true ) ); ?></span>
						<strong><?php echo esc_html( get_post_meta( $match_id, '_match_home_score', true ) ); ?> - <?php echo esc_html( get_post_meta( $match_id, '_match_away_score', true ) ); ?></strong>
						<span><?php echo esc_html( get_post_meta( $match_id, '_match_away_team_name', true ) ); ?></span>
					</a>
					<div class="wp-livescore-la-match-list__meta">
							<time><?php echo esc_html( trim( $match_date . ' ' . get_post_meta( $match_id, '_match_time', true ) ) ); ?></time>
						<?php if ( ! empty( $attributes['showLeague'] ) && get_post_meta( $match_id, '_match_league_name', true ) ) : ?><span><?php echo esc_html( get_post_meta( $match_id, '_match_league_name', true ) ); ?></span><?php endif; ?>
						<?php if ( ! empty( $attributes['showSeason'] ) && get_post_meta( $match_id, '_match_season_name', true ) ) : ?><span><?php echo esc_html( get_post_meta( $match_id, '_match_season_name', true ) ); ?></span><?php endif; ?>
						<?php if ( ! empty( $attributes['showVenue'] ) && get_post_meta( $match_id, '_match_venue', true ) ) : ?><span><?php echo esc_html( get_post_meta( $match_id, '_match_venue', true ) ); ?></span><?php endif; ?>
						<?php if ( ! empty( $attributes['showStatus'] ) && $match_status ) : ?><span><?php echo esc_html( $match_status ); ?></span><?php endif; ?>
					</div>
					<?php if ( ! empty( $attributes['showSportScore'] ) && get_post_meta( $match_id, '_match_sportscore_slug', true ) ) : ?>
						<code class="wp-livescore-la-match-list__sportscore"><?php echo esc_html( get_post_meta( $match_id, '_match_sportscore_slug', true ) ); ?></code>
					<?php endif; ?>
				</article>
			<?php endwhile; ?>
		</div>
		<?php wp_reset_postdata(); ?>
	<?php else : ?>
		<p class="wp-livescore-la-match-list__empty"><?php echo esc_html( isset( $attributes['emptyMessage'] ) ? $attributes['emptyMessage'] : __( 'No matches found.', 'wp-livescore-la' ) ); ?></p>
	<?php endif; ?>
</section>
