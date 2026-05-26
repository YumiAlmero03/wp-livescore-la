<?php
/**
 * Server render for League Header Image block.
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

$allowed_sizes   = array( 'medium', 'large', 'full' );
$image_size      = isset( $attributes['imageSize'] ) && in_array( $attributes['imageSize'], $allowed_sizes, true ) ? $attributes['imageSize'] : 'full';
$header_image_id = (int) get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'header_image_id', true );
$image_html      = '';

if ( $header_image_id > 0 ) {
	$image_html = wp_get_attachment_image(
		$header_image_id,
		$image_size,
		false,
		array(
			'class' => 'wp-livescore-la-league-header-image__image',
			'alt'   => trim( wp_strip_all_tags( get_the_title( $league_id ) ) ),
		)
	);
}

if ( '' === $image_html ) {
	$banner_url = esc_url( get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'strBanner', true ) );

	if ( '' !== $banner_url ) {
		$image_html = sprintf(
			'<img class="wp-livescore-la-league-header-image__image" src="%1$s" alt="%2$s" />',
			esc_url( $banner_url ),
			esc_attr( trim( wp_strip_all_tags( get_the_title( $league_id ) ) ) )
		);
	}
}

if ( '' === $image_html ) {
	$image_html = function_exists( 'wp_livescore_la_get_image_placeholder' ) ? wp_livescore_la_get_image_placeholder( 'wp-livescore-la-league-header-image__placeholder', __( 'League image', 'wp-livescore-la' ) ) : '';
}

$max_width      = isset( $attributes['maxWidth'] ) ? absint( $attributes['maxWidth'] ) : 0;
$max_height     = isset( $attributes['maxHeight'] ) ? absint( $attributes['maxHeight'] ) : 0;
$wrapper_styles = array();
$focus_options  = array(
	'center center',
	'center top',
	'center bottom',
);
$focus_position = isset( $attributes['focusPosition'] ) ? sanitize_text_field( $attributes['focusPosition'] ) : 'center center';
$focus_position = in_array( $focus_position, $focus_options, true ) ? $focus_position : 'center center';

if ( $max_width > 0 ) {
	$wrapper_styles[] = '--wp-livescore-la-league-header-max-width:' . $max_width . 'px';
}

if ( $max_height > 0 ) {
	$wrapper_styles[] = '--wp-livescore-la-league-header-max-height:' . $max_height . 'px';
}

$wrapper_styles[] = '--wp-livescore-la-league-header-position:' . $focus_position;

$wrapper_args = array(
	'class' => 'wp-livescore-la-league-header-image',
);

if ( ! empty( $wrapper_styles ) ) {
	$wrapper_args['style'] = implode( ';', $wrapper_styles );
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$link_to_league  = ! empty( $attributes['linkToLeague'] );
$show_overlay    = ! empty( $attributes['showOverlay'] );
$overlay_color   = isset( $attributes['overlayColor'] ) ? sanitize_hex_color( $attributes['overlayColor'] ) : '#000000';
$overlay_opacity = isset( $attributes['overlayOpacity'] ) ? min( 100, max( 0, absint( $attributes['overlayOpacity'] ) ) ) : 35;
$overlay_html    = '';

if ( $show_overlay ) {
	$overlay_html = sprintf(
		'<span class="wp-livescore-la-league-header-image__overlay" style="background-color:%1$s;opacity:%2$s" aria-hidden="true"></span>',
		esc_attr( $overlay_color ? $overlay_color : '#000000' ),
		esc_attr( (string) ( $overlay_opacity / 100 ) )
	);
}
?>
<figure <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $link_to_league ) : ?>
		<a class="wp-livescore-la-league-header-image__link" href="<?php echo esc_url( get_permalink( $league_id ) ); ?>">
			<?php echo wp_kses_post( $image_html ); ?>
			<?php echo wp_kses_post( $overlay_html ); ?>
		</a>
	<?php else : ?>
		<?php echo wp_kses_post( $image_html ); ?>
		<?php echo wp_kses_post( $overlay_html ); ?>
	<?php endif; ?>
</figure>
