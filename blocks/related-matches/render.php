<?php
/**
 * Server render for Related Matches block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$league_id = isset( $block->context['postId'] ) && 'league' === get_post_type( (int) $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $league_id <= 0 && ! empty( $attributes['leagueApiId'] ) ) {
	$league_api_id = sanitize_text_field( $attributes['leagueApiId'] );
	$league_posts  = get_posts(
		array(
			'post_type'      => 'league',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => WP_LIVESCORE_LA_META_PREFIX . 'api_id',
			'meta_value'     => $league_api_id,
		)
	);

	if ( ! empty( $league_posts[0] ) ) {
		$league_id = absint( $league_posts[0] );
	}
}

if ( $league_id <= 0 && ! empty( $attributes['leagueId'] ) ) {
	$manual_id = absint( $attributes['leagueId'] );
	$league_id = 'league' === get_post_type( $manual_id ) ? $manual_id : 0;
}

$layout_type = isset( $attributes['layoutType'] ) ? sanitize_key( $attributes['layoutType'] ) : 'grid';
$attributes['layoutType'] = in_array( $layout_type, array( 'grid', 'carousel' ), true ) ? $layout_type : 'grid';

if ( $league_id > 0 ) {
	$attributes['leagueId'] = $league_id;
} else {
	unset( $attributes['leagueId'] );
	if ( empty( $attributes['title'] ) || 'Related Matches' === $attributes['title'] ) {
		$attributes['title'] = __( 'Matches', 'wp-livescore-la' );
	}
}

require WP_LIVESCORE_LA_DIR . 'blocks/match-list/render.php';
