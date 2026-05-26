<?php
/**
 * Fallback League archive template.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$league_archive_url = get_post_type_archive_link( 'league' );
$has_filter = ( isset( $_GET['league_sport'] ) && '' !== $_GET['league_sport'] ) || ( isset( $_GET['league_country'] ) && '' !== $_GET['league_country'] );

get_header();
?>

<?php if ( function_exists( 'astra_page_layout' ) && 'left-sidebar' === astra_page_layout() ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area primary"'; } ?>>
	<?php if ( function_exists( 'astra_primary_content_top' ) ) : ?>
		<?php astra_primary_content_top(); ?>
	<?php endif; ?>

<main class="site-main wp-livescore-la-league-archive">
	<header class="wp-livescore-la-league-archive__header">
		<h1>Leagues Directory</h1>
	</header>

	<?php echo do_blocks( '<!-- wp:wp-livescore-la/league-filters {"title":""} /-->' ); ?>

	<?php if ( have_posts() ) : ?>
		<div class="wp-livescore-la-league-archive__grid">
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article <?php post_class( 'wp-livescore-la-league-archive__card' ); ?>>
					<a class="wp-livescore-la-league-archive__image-link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium_large', array( 'class' => 'wp-livescore-la-league-archive__image' ) ); ?>
						<?php elseif ( function_exists( 'wp_livescore_la_get_image_placeholder' ) ) : ?>
							<?php echo wp_kses_post( wp_livescore_la_get_image_placeholder( 'wp-livescore-la-league-archive__image wp-livescore-la-league-archive__placeholder', get_the_title() ) ); ?>
						<?php endif; ?>
					</a>

					<div class="wp-livescore-la-league-archive__content">
						<h2 class="wp-livescore-la-league-archive__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

						<?php if ( has_excerpt() ) : ?>
							<div class="wp-livescore-la-league-archive__excerpt">
								<?php the_excerpt(); ?>
							</div>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php function_exists( 'wp_livescore_la_the_archive_pagination' ) ? wp_livescore_la_the_archive_pagination() : the_posts_pagination(); ?>
	<?php else : ?>
		<section class="wp-livescore-la-league-archive__empty">
			<h2><?php esc_html_e( 'No leagues found', 'wp-livescore-la' ); ?></h2>
			<?php if ( $has_filter ) : ?>
				<p><?php esc_html_e( 'No leagues match the selected filters.', 'wp-livescore-la' ); ?></p>
				<?php if ( $league_archive_url ) : ?>
					<a class="button wp-livescore-la-league-archive__reset" href="<?php echo esc_url( $league_archive_url ); ?>">
						<?php esc_html_e( 'View all leagues', 'wp-livescore-la' ); ?>
					</a>
				<?php endif; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No leagues have been added yet.', 'wp-livescore-la' ); ?></p>
			<?php endif; ?>
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
