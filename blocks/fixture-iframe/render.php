<?php
/**
 * Server render for Fixture Iframe block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
$post_type = $post_id > 0 ? get_post_type( $post_id ) : '';

if ( ! in_array( $post_type, array( 'match', 'league' ), true ) ) {
	return '';
}

$sportscore_meta_key = 'league' === $post_type ? WP_LIVESCORE_LA_META_PREFIX . 'sportscore_slug' : '_match_sportscore_slug';
$sportscore_slug     = wp_livescore_la_sanitize_sportscore_slug( get_post_meta( $post_id, $sportscore_meta_key, true ) );

if ( '' === $sportscore_slug ) {
	return '';
}

$encoded_slug = implode(
	'/',
	array_map(
		'rawurlencode',
		explode( '/', $sportscore_slug )
	)
);
$iframe_src = 'https://sportscore.com/embed/fixtures/' . $encoded_slug . '/';
$width      = isset( $attributes['width'] ) ? max( 1, min( 2400, (int) $attributes['width'] ) ) : 320;
$height     = isset( $attributes['height'] ) ? max( 1, min( 2400, (int) $attributes['height'] ) ) : 420;
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-fixture-iframe',
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<iframe
		class="wp-livescore-la-fixture-iframe__embed"
		src="<?php echo esc_url( $iframe_src ); ?>"
		title="<?php echo esc_attr( sprintf( __( 'SportScore fixtures for %s', 'wp-livescore-la' ), get_the_title( $post_id ) ) ); ?>"
		width="<?php echo esc_attr( (string) $width ); ?>"
		height="<?php echo esc_attr( (string) $height ); ?>"
		scrolling="no"
		loading="lazy"
		referrerpolicy="no-referrer-when-downgrade"
	></iframe>
</div>
