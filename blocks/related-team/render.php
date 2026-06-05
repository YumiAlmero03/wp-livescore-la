<?php
/**
 * Server render for Related Team block.
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

$team_side     = isset( $attributes['teamSide'] ) && 'away' === $attributes['teamSide'] ? 'away' : 'home';
$team_id       = (int) get_post_meta( $match_id, '_match_' . $team_side . '_team_id', true );
	$fallback_name = get_post_meta( $match_id, '_match_' . $team_side . '_team_name', true );
	$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
	$show_image    = ! array_key_exists( 'showImage', $attributes ) || ! empty( $attributes['showImage'] );
	$image_size    = isset( $attributes['imageSize'] ) ? (float) $attributes['imageSize'] : 5.0;
	$image_size    = max( 2.0, min( 12.0, $image_size ) );
	$image_position = isset( $attributes['imagePosition'] ) ? sanitize_key( $attributes['imagePosition'] ) : 'top';
	$image_position = in_array( $image_position, array( 'top', 'left', 'right' ), true ) ? $image_position : 'top';
	$show_title   = ! array_key_exists( 'showTitle', $attributes ) || ! empty( $attributes['showTitle'] );
	$use_short_name = ! empty( $attributes['useShortName'] );
	$make_link     = ! array_key_exists( 'makeLink', $attributes ) || ! empty( $attributes['makeLink'] );

if ( $team_id > 0 && 'team' !== get_post_type( $team_id ) ) {
	$team_id = 0;
}

$team_name = $team_id > 0 ? get_the_title( $team_id ) : sanitize_text_field( $fallback_name );

if ( $use_short_name && $team_id > 0 ) {
	$short_name = sanitize_text_field( get_post_meta( $team_id, '_team_short_name', true ) );

	if ( '' !== $short_name ) {
		$team_name = $short_name;
	}
}

if ( '' === $team_name ) {
	if ( '' === $empty_message ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'wp-livescore-la-related-team',
		)
	);
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<p class="wp-livescore-la-related-team__empty"><?php echo esc_html( $empty_message ); ?></p>
	</div>
	<?php
	return;
}

$team_image = '';

if ( $show_image && $team_id > 0 ) {
	if ( has_post_thumbnail( $team_id ) ) {
		$team_image = get_the_post_thumbnail(
			$team_id,
			'thumbnail',
			array(
				'class' => 'wp-livescore-la-related-team__image',
				'alt'   => trim( wp_strip_all_tags( $team_name ) ),
			)
		);
	}

	if ( '' === $team_image ) {
		$logo_url = esc_url( get_post_meta( $team_id, '_team_logo', true ) );

		if ( '' !== $logo_url ) {
			$team_image = sprintf(
				'<img class="wp-livescore-la-related-team__image" src="%1$s" alt="%2$s" />',
				esc_url( $logo_url ),
				esc_attr( trim( wp_strip_all_tags( $team_name ) ) )
			);
		}
	}

	if ( '' === $team_image && function_exists( 'wp_livescore_la_get_image_placeholder' ) ) {
		$team_image = wp_livescore_la_get_image_placeholder( 'wp-livescore-la-related-team__image wp-livescore-la-related-team__placeholder', $team_name );
	}
}

$permalink          = $make_link && $team_id > 0 ? get_permalink( $team_id ) : '';
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'wp-livescore-la-related-team wp-livescore-la-related-team--' . $team_side . ' wp-livescore-la-related-team--image-' . $image_position,
			'style' => '--wp-livescore-la-related-team-image-size:' . esc_attr( $image_size ) . 'rem;',
		)
	);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $permalink ) : ?>
		<a class="wp-livescore-la-related-team__link" href="<?php echo esc_url( $permalink ); ?>">
			<?php echo wp_kses_post( $team_image ); ?>
			<?php if ( $show_title ) : ?>
				<span class="wp-livescore-la-related-team__name"><?php echo esc_html( $team_name ); ?></span>
			<?php endif; ?>
		</a>
	<?php else : ?>
		<div class="wp-livescore-la-related-team__link">
			<?php echo wp_kses_post( $team_image ); ?>
			<?php if ( $show_title ) : ?>
				<span class="wp-livescore-la-related-team__name"><?php echo esc_html( $team_name ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
