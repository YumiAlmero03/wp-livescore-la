<?php
/**
 * Server render for League Logo block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$league_id = isset( $block->context['postId'] ) && 'league' === get_post_type( (int) $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $league_id <= 0 && ! empty( $attributes['leagueId'] ) ) {
	$manual_id = absint( $attributes['leagueId'] );
	$league_id = 'league' === get_post_type( $manual_id ) ? $manual_id : 0;
}

if ( $league_id <= 0 ) {
	return '';
}

$allowed_sizes = array( 'thumbnail', 'medium', 'large', 'full' );
$image_size    = isset( $attributes['imageSize'] ) && in_array( $attributes['imageSize'], $allowed_sizes, true ) ? $attributes['imageSize'] : 'medium';
$alt_text      = trim( wp_strip_all_tags( get_the_title( $league_id ) ) );
$image_html    = '';

if ( has_post_thumbnail( $league_id ) ) {
	$image_html = get_the_post_thumbnail(
		$league_id,
		$image_size,
		array(
			'class' => 'wp-livescore-la-league-logo__image',
			'alt'   => $alt_text,
		)
	);
}

if ( '' === $image_html ) {
	$badge_url = esc_url( get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'strBadge', true ) );

	if ( '' !== $badge_url ) {
		$image_html = sprintf(
			'<img class="wp-livescore-la-league-logo__image" src="%1$s" alt="%2$s" />',
			esc_url( $badge_url ),
			esc_attr( $alt_text )
		);
	}
}

$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';

if ( '' === $image_html ) {
	$placeholder_label = '' !== $empty_message ? $empty_message : __( 'League logo', 'wp-livescore-la' );
	$image_html        = function_exists( 'wp_livescore_la_get_image_placeholder' ) ? wp_livescore_la_get_image_placeholder( 'wp-livescore-la-league-logo__placeholder', $placeholder_label ) : '';
}

$max_width      = isset( $attributes['maxWidth'] ) ? absint( $attributes['maxWidth'] ) : 160;
$max_height     = isset( $attributes['maxHeight'] ) ? absint( $attributes['maxHeight'] ) : 160;
$wrapper_styles = array();

if ( $max_width > 0 ) {
	$wrapper_styles[] = '--wp-livescore-la-league-logo-max-width:' . $max_width . 'px';
}

if ( $max_height > 0 ) {
	$wrapper_styles[] = '--wp-livescore-la-league-logo-max-height:' . $max_height . 'px';
}

$wrapper_args = array(
	'class' => 'wp-livescore-la-league-logo',
);

if ( ! empty( $wrapper_styles ) ) {
	$wrapper_args['style'] = implode( ';', $wrapper_styles );
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$link_to_league     = ! empty( $attributes['linkToLeague'] );
?>
<figure <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $link_to_league ) : ?>
		<a class="wp-livescore-la-league-logo__link" href="<?php echo esc_url( get_permalink( $league_id ) ); ?>">
			<?php echo wp_kses_post( $image_html ); ?>
		</a>
	<?php else : ?>
		<?php echo wp_kses_post( $image_html ); ?>
	<?php endif; ?>
</figure>
