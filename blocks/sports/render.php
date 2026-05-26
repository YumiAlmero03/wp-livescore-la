<?php
/**
 * Server render for Sports block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$allowed_post_types = array( 'league', 'match', 'team', 'player' );
$post_id            = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $post_id <= 0 && ! empty( $attributes['postId'] ) ) {
	$post_id = absint( $attributes['postId'] );
}

if ( $post_id <= 0 || ! in_array( get_post_type( $post_id ), $allowed_post_types, true ) ) {
	return '';
}

$terms = get_the_terms( $post_id, 'sport' );

if ( ( empty( $terms ) || is_wp_error( $terms ) ) && function_exists( 'wp_livescore_la_find_sport_id_by_value' ) ) {
	$meta_name = get_post_meta( $post_id, '_' . get_post_type( $post_id ) . '_sport_name', true );
	$term_id   = wp_livescore_la_find_sport_id_by_value( $meta_name );
	$term      = $term_id > 0 ? get_term( $term_id, 'sport' ) : null;
	$terms     = $term instanceof WP_Term && ! is_wp_error( $term ) ? array( $term ) : array();
}

$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
$show_icon     = ! array_key_exists( 'showIcon', $attributes ) || ! empty( $attributes['showIcon'] );
$make_link     = ! empty( $attributes['makeLink'] );

if ( empty( $terms ) || is_wp_error( $terms ) ) {
	if ( '' === $empty_message ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'wp-livescore-la-sports',
		)
	);
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<p class="wp-livescore-la-sports__empty"><?php echo esc_html( $empty_message ); ?></p>
	</div>
	<?php
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-sports',
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php foreach ( $terms as $term ) : ?>
		<?php
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$icon = $show_icon ? get_term_meta( $term->term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_icon', true ) : '';
		$icon = function_exists( 'wp_livescore_la_sanitize_icon_classes' ) ? wp_livescore_la_sanitize_icon_classes( $icon ) : sanitize_html_class( $icon );
		$url  = $make_link ? get_term_link( $term, 'sport' ) : '';
		$url  = is_wp_error( $url ) ? '' : $url;
		?>
		<span class="wp-livescore-la-sports__item">
			<?php if ( '' !== $icon ) : ?>
				<i class="wp-livescore-la-sports__icon <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i>
			<?php endif; ?>

			<?php if ( '' !== $url ) : ?>
				<a class="wp-livescore-la-sports__name" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $term->name ); ?></a>
			<?php else : ?>
				<span class="wp-livescore-la-sports__name"><?php echo esc_html( $term->name ); ?></span>
			<?php endif; ?>
		</span>
	<?php endforeach; ?>
</div>
