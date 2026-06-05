<?php
/**
 * Server render for Related League Blogs block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$related_post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_the_ID();
$related_type    = $related_post_id > 0 ? get_post_type( $related_post_id ) : '';

if ( ! $related_post_id || ! in_array( $related_type, array( 'league', 'team', 'match', 'sport', 'country' ), true ) || ! function_exists( 'wp_livescore_la_get_related_blog_tag_id' ) ) {
	return '';
}

$attributes = wp_livescore_la_normalize_related_blog_attributes( $attributes );
$tag_id     = wp_livescore_la_get_related_blog_tag_id( $related_post_id );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-related-league-blogs',
		'style' => '--wp-livescore-la-related-columns:' . $attributes['columns'] . ';',
	)
);

$is_random_fallback = false;

if ( $tag_id > 0 ) {
	$related_posts = new WP_Query( wp_livescore_la_get_related_blog_query_args( $tag_id, $attributes ) );
	$cards_markup  = wp_livescore_la_render_related_blog_cards( $related_posts, $attributes );
} else {
	$related_posts = null;
	$cards_markup  = '';
}

if ( '' === $cards_markup ) {
	$is_random_fallback = true;
	$related_posts      = new WP_Query( wp_livescore_la_get_random_blog_query_args( $attributes ) );
	$cards_markup       = wp_livescore_la_render_related_blog_cards( $related_posts, $attributes );
}
?>
<section <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( '' !== $attributes['title'] ) : ?>
		<h2 class="wp-livescore-la-related-league-blogs__title"><?php echo esc_html( $attributes['title'] ); ?></h2>
	<?php endif; ?>

	<?php if ( '' !== $cards_markup ) : ?>
		<div class="wp-livescore-la-related-league-blogs__grid">
			<?php echo $cards_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php if ( ! $is_random_fallback && $attributes['showLoadMore'] && $related_posts instanceof WP_Query && $related_posts->max_num_pages > 1 && '' !== $attributes['loadMoreText'] ) : ?>
			<div class="wp-livescore-la-related-league-blogs__load-more-wrap">
				<button
					type="button"
					class="wp-livescore-la-related-league-blogs__load-more"
					data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_livescore_la_related_blogs' ) ); ?>"
					data-post-id="<?php echo esc_attr( (string) $related_post_id ); ?>"
					data-next-page="2"
					data-max-pages="<?php echo esc_attr( (string) $related_posts->max_num_pages ); ?>"
					data-attributes="<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>"
				>
					<?php echo esc_html( $attributes['loadMoreText'] ); ?>
				</button>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<p class="wp-livescore-la-related-league-blogs__empty"><?php echo esc_html( $attributes['emptyMessage'] ); ?></p>
	<?php endif; ?>
</section>
