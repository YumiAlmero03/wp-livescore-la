<?php
/**
 * Server render for Related League Blogs block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$related_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$related_type    = $related_post_id > 0 ? get_post_type( $related_post_id ) : '';

if ( ! $related_post_id || ! in_array( $related_type, array( 'league', 'team', 'match', 'sport', 'country' ), true ) ) {
	return '';
}

$related_slug = sanitize_title( get_post_field( 'post_name', $related_post_id ) );
$tag          = '' !== $related_slug ? get_term_by( 'slug', $related_slug, 'post_tag' ) : false;
$tag_id       = $tag instanceof WP_Term ? (int) $tag->term_id : 0;

if ( $tag_id <= 0 && 'league' === $related_type ) {
	$tag_id = get_league_linked_tag_id( $related_post_id );

	if ( $tag_id <= 0 ) {
		$tag_id = sync_league_to_post_tag( $related_post_id );
	}
}

if ( $tag_id <= 0 && 'team' === $related_type ) {
	$tag_id = get_team_linked_tag_id( $related_post_id );

	if ( $tag_id <= 0 ) {
		$tag_id = sync_team_to_post_tag( $related_post_id );
	}
}

if ( $tag_id <= 0 && 'match' === $related_type ) {
	$tag_id = get_match_linked_tag_id( $related_post_id );

	if ( $tag_id <= 0 ) {
		$tag_id = sync_match_to_post_tag( $related_post_id );
	}
}

$title               = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Related Blogs', 'wp-livescore-la' );
$posts_per_page      = isset( $attributes['postsPerPage'] ) ? max( 1, min( 24, (int) $attributes['postsPerPage'] ) ) : 6;
$columns             = isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;
$show_featured_image = ! empty( $attributes['showFeaturedImage'] );
$show_excerpt        = ! empty( $attributes['showExcerpt'] );
$show_date           = ! empty( $attributes['showDate'] );
$show_author         = ! empty( $attributes['showAuthor'] );
$show_read_more      = ! empty( $attributes['showReadMore'] );
$read_more_text      = isset( $attributes['readMoreText'] ) ? sanitize_text_field( $attributes['readMoreText'] ) : __( 'Read More', 'wp-livescore-la' );
$empty_message       = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : __( 'No related blog posts found.', 'wp-livescore-la' );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-related-league-blogs',
		'style' => '--wp-livescore-la-related-columns:' . $columns . ';',
	)
);

if ( $tag_id <= 0 ) {
	?>
	<section <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<?php if ( '' !== $title ) : ?>
			<h2 class="wp-livescore-la-related-league-blogs__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
		<p class="wp-livescore-la-related-league-blogs__empty"><?php echo esc_html( $empty_message ); ?></p>
	</section>
	<?php
	return;
}

$related_posts = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $posts_per_page,
		'ignore_sticky_posts' => true,
		'tag_id'              => $tag_id,
	)
);
?>
<section <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( '' !== $title ) : ?>
		<h2 class="wp-livescore-la-related-league-blogs__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<?php if ( $related_posts->have_posts() ) : ?>
		<div class="wp-livescore-la-related-league-blogs__grid">
			<?php while ( $related_posts->have_posts() ) : ?>
				<?php $related_posts->the_post(); ?>
				<article class="wp-livescore-la-related-league-blogs__card">
					<?php if ( $show_featured_image ) : ?>
						<a class="wp-livescore-la-related-league-blogs__image-link" href="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'medium_large', array( 'class' => 'wp-livescore-la-related-league-blogs__image' ) );
							} elseif ( function_exists( 'wp_livescore_la_get_image_placeholder' ) ) {
								echo wp_kses_post( wp_livescore_la_get_image_placeholder( 'wp-livescore-la-related-league-blogs__image wp-livescore-la-related-league-blogs__placeholder', get_the_title() ) );
							}
							?>
						</a>
					<?php endif; ?>

					<div class="wp-livescore-la-related-league-blogs__content">
						<h3 class="wp-livescore-la-related-league-blogs__post-title">
							<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
						</h3>

						<?php if ( $show_date || $show_author ) : ?>
							<div class="wp-livescore-la-related-league-blogs__meta">
								<?php if ( $show_date ) : ?>
									<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
								<?php endif; ?>
								<?php if ( $show_author ) : ?>
									<span><?php echo esc_html( get_the_author() ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $show_excerpt ) : ?>
							<div class="wp-livescore-la-related-league-blogs__excerpt">
								<?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 24 ) ); ?>
							</div>
						<?php endif; ?>

						<?php if ( $show_read_more && '' !== $read_more_text ) : ?>
							<a class="wp-livescore-la-related-league-blogs__read-more" href="<?php echo esc_url( get_permalink() ); ?>">
								<?php echo esc_html( $read_more_text ); ?>
							</a>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
		<?php wp_reset_postdata(); ?>
	<?php else : ?>
		<p class="wp-livescore-la-related-league-blogs__empty"><?php echo esc_html( $empty_message ); ?></p>
	<?php endif; ?>
</section>
