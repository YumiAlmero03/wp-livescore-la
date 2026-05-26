<?php
/**
 * Server render for Match Status block.
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

if ( $match_id <= 0 || ! function_exists( 'wp_livescore_la_get_match_status' ) ) {
	return '';
}

$status = wp_livescore_la_get_match_status( $match_id );
$label  = isset( $status['label'] ) ? (string) $status['label'] : '';
$key    = isset( $status['key'] ) ? sanitize_html_class( $status['key'] ) : '';

if ( '' === $label ) {
	$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
	return '' !== $empty_message ? '<p class="wp-livescore-la-match-status__empty">' . esc_html( $empty_message ) . '</p>' : '';
}

if ( ! empty( $attributes['showIcon'] ) ) {
	wp_enqueue_style( 'dashicons' );
}

$prefix = isset( $attributes['prefix'] ) ? sanitize_text_field( $attributes['prefix'] ) : '';
$suffix = isset( $attributes['suffix'] ) ? sanitize_text_field( $attributes['suffix'] ) : '';
$class  = 'wp-livescore-la-match-status';

if ( '' !== $key ) {
	$class .= ' wp-livescore-la-match-status--' . $key;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $class,
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( ! empty( $attributes['showIcon'] ) ) : ?>
		<span class="wp-livescore-la-match-status__icon dashicons dashicons-clock" aria-hidden="true"></span>
	<?php endif; ?>
	<span class="wp-livescore-la-match-status__content">
		<?php if ( '' !== $prefix ) : ?>
			<span class="wp-livescore-la-match-status__prefix"><?php echo esc_html( $prefix ); ?></span>
		<?php endif; ?>
		<span class="wp-livescore-la-match-status__label"><?php echo esc_html( $label ); ?></span>
		<?php if ( '' !== $suffix ) : ?>
			<span class="wp-livescore-la-match-status__suffix"><?php echo esc_html( $suffix ); ?></span>
		<?php endif; ?>
	</span>
</div>
