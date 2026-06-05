<?php
/**
 * Server render for Opponent Team block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$match_id = isset( $block->context['postId'] ) && 'match' === get_post_type( (int) $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $match_id <= 0 && ! empty( $attributes['matchId'] ) ) {
	$manual_id = absint( $attributes['matchId'] );
	$match_id  = 'match' === get_post_type( $manual_id ) ? $manual_id : 0;
}

if ( $match_id <= 0 ) {
	return '';
}

$selected_team_id = ! empty( $attributes['teamId'] ) ? absint( $attributes['teamId'] ) : 0;
$context_query    = isset( $block->context['query'] ) && is_array( $block->context['query'] ) ? $block->context['query'] : array();

if ( $selected_team_id <= 0 && ! empty( $context_query['wpLivescoreRelatedTeamId'] ) ) {
	$selected_team_id = absint( $context_query['wpLivescoreRelatedTeamId'] );
}

if ( $selected_team_id <= 0 && isset( $GLOBALS['wp_livescore_la_related_matches_context_stack'] ) && is_array( $GLOBALS['wp_livescore_la_related_matches_context_stack'] ) ) {
	$related_context = end( $GLOBALS['wp_livescore_la_related_matches_context_stack'] );

	if ( is_array( $related_context ) && ! empty( $related_context['team_id'] ) ) {
		$selected_team_id = absint( $related_context['team_id'] );
	}
}

if ( $selected_team_id <= 0 && is_singular( 'team' ) ) {
	$selected_team_id = (int) get_queried_object_id();
}

if ( $selected_team_id <= 0 || 'team' !== get_post_type( $selected_team_id ) ) {
	$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
	if ( '' === $empty_message ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'wp-livescore-la-opponent-team',
		)
	);
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<p class="wp-livescore-la-opponent-team__empty"><?php echo esc_html( $empty_message ); ?></p>
	</div>
	<?php
	return;
}

$home_team_id = (int) get_post_meta( $match_id, '_match_home_team_id', true );
$away_team_id = (int) get_post_meta( $match_id, '_match_away_team_id', true );
$opponent_id  = 0;
$opponent_side = '';

if ( $selected_team_id === $home_team_id && $away_team_id > 0 ) {
	$opponent_id   = $away_team_id;
	$opponent_side = 'away';
} elseif ( $selected_team_id === $away_team_id && $home_team_id > 0 ) {
	$opponent_id   = $home_team_id;
	$opponent_side = 'home';
}

$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';

if ( $opponent_id <= 0 || 'team' !== get_post_type( $opponent_id ) ) {
	if ( '' === $empty_message ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'wp-livescore-la-opponent-team',
		)
	);
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<p class="wp-livescore-la-opponent-team__empty"><?php echo esc_html( $empty_message ); ?></p>
	</div>
	<?php
	return;
}

$show_image     = ! array_key_exists( 'showImage', $attributes ) || ! empty( $attributes['showImage'] );
$image_size     = isset( $attributes['imageSize'] ) ? (float) $attributes['imageSize'] : 3.0;
$image_size     = max( 2.0, min( 12.0, $image_size ) );
$image_position = isset( $attributes['imagePosition'] ) ? sanitize_key( $attributes['imagePosition'] ) : 'left';
$image_position = in_array( $image_position, array( 'top', 'left', 'right' ), true ) ? $image_position : 'left';
$use_short_name = ! empty( $attributes['useShortName'] );
$make_link      = ! array_key_exists( 'makeLink', $attributes ) || ! empty( $attributes['makeLink'] );
$team_name      = get_the_title( $opponent_id );

if ( $use_short_name ) {
	$short_name = sanitize_text_field( get_post_meta( $opponent_id, '_team_short_name', true ) );

	if ( '' !== $short_name ) {
		$team_name = $short_name;
	}
}

$team_image = '';

if ( $show_image ) {
	if ( has_post_thumbnail( $opponent_id ) ) {
		$team_image = get_the_post_thumbnail(
			$opponent_id,
			'thumbnail',
			array(
				'class' => 'wp-livescore-la-opponent-team__image',
				'alt'   => trim( wp_strip_all_tags( $team_name ) ),
			)
		);
	}

	if ( '' === $team_image ) {
		$logo_url = esc_url( get_post_meta( $opponent_id, '_team_logo', true ) );

		if ( '' !== $logo_url ) {
			$team_image = sprintf(
				'<img class="wp-livescore-la-opponent-team__image" src="%1$s" alt="%2$s" />',
				esc_url( $logo_url ),
				esc_attr( trim( wp_strip_all_tags( $team_name ) ) )
			);
		}
	}

	if ( '' === $team_image && function_exists( 'wp_livescore_la_get_image_placeholder' ) ) {
		$team_image = wp_livescore_la_get_image_placeholder( 'wp-livescore-la-opponent-team__image wp-livescore-la-opponent-team__placeholder', $team_name );
	}
}

$permalink = $make_link ? get_permalink( $opponent_id ) : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-opponent-team wp-livescore-la-opponent-team--' . $opponent_side . ' wp-livescore-la-opponent-team--image-' . $image_position,
		'style' => '--wp-livescore-la-opponent-team-image-size:' . esc_attr( $image_size ) . 'rem;',
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $permalink ) : ?>
		<a class="wp-livescore-la-opponent-team__link" href="<?php echo esc_url( $permalink ); ?>">
			<?php echo wp_kses_post( $team_image ); ?>
			<span class="wp-livescore-la-opponent-team__name"><?php echo esc_html( $team_name ); ?></span>
		</a>
	<?php else : ?>
		<div class="wp-livescore-la-opponent-team__link">
			<?php echo wp_kses_post( $team_image ); ?>
			<span class="wp-livescore-la-opponent-team__name"><?php echo esc_html( $team_name ); ?></span>
		</div>
	<?php endif; ?>
</div>
