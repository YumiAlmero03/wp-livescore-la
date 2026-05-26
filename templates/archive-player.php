<?php
/**
 * Fallback Player archive template.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<?php if ( function_exists( 'astra_page_layout' ) && 'left-sidebar' === astra_page_layout() ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area primary"'; } ?>>
	<?php if ( function_exists( 'astra_primary_content_top' ) ) : ?>
		<?php astra_primary_content_top(); ?>
	<?php endif; ?>

<main class="site-main wp-livescore-la-player-archive">
	<header class="wp-livescore-la-player-archive__header">
		<h1><?php esc_html_e( 'Players Directory', 'wp-livescore-la' ); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="wp-livescore-la-player-archive__grid">
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<?php
				$player_id   = get_the_ID();
				$player_meta = array_filter(
					array(
						get_post_meta( $player_id, '_player_team_name', true ),
						get_post_meta( $player_id, '_player_sport_name', true ),
						get_post_meta( $player_id, '_player_position', true ),
						get_post_meta( $player_id, '_player_number', true ) ? '#' . get_post_meta( $player_id, '_player_number', true ) : '',
						get_post_meta( $player_id, '_player_country', true ),
					),
					function ( $value ) {
						return '' !== trim( (string) $value );
					}
				);
				?>
				<article <?php post_class( 'wp-livescore-la-player-archive__card' ); ?>>
					<a class="wp-livescore-la-player-archive__image-link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium_large', array( 'class' => 'wp-livescore-la-player-archive__image' ) ); ?>
						<?php elseif ( function_exists( 'wp_livescore_la_get_image_placeholder' ) ) : ?>
							<?php echo wp_kses_post( wp_livescore_la_get_image_placeholder( 'wp-livescore-la-player-archive__image wp-livescore-la-player-archive__placeholder', get_the_title() ) ); ?>
						<?php endif; ?>
					</a>

					<div class="wp-livescore-la-player-archive__content">
						<h2 class="wp-livescore-la-player-archive__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

						<?php if ( ! empty( $player_meta ) ) : ?>
							<p class="wp-livescore-la-player-archive__meta"><?php echo esc_html( implode( ' | ', $player_meta ) ); ?></p>
						<?php endif; ?>

						<?php if ( has_excerpt() ) : ?>
							<div class="wp-livescore-la-player-archive__excerpt">
								<?php the_excerpt(); ?>
							</div>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php function_exists( 'wp_livescore_la_the_archive_pagination' ) ? wp_livescore_la_the_archive_pagination() : the_posts_pagination(); ?>
	<?php else : ?>
		<section class="wp-livescore-la-player-archive__empty">
			<h2><?php esc_html_e( 'No players found', 'wp-livescore-la' ); ?></h2>
			<p><?php esc_html_e( 'No player profiles have been added yet.', 'wp-livescore-la' ); ?></p>
		</section>
	<?php endif; ?>
</main>

	<?php if ( function_exists( 'astra_primary_content_bottom' ) ) : ?>
		<?php astra_primary_content_bottom(); ?>
	<?php endif; ?>
</div><!-- #primary -->

<?php if ( function_exists( 'astra_page_layout' ) && 'right-sidebar' === astra_page_layout() ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<?php
get_footer();
