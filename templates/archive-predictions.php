<?php
/**
 * Fallback Prediction archive template.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_filter = ( isset( $_GET['prediction_country'] ) && '' !== $_GET['prediction_country'] )
	|| ( isset( $_GET['prediction_team'] ) && '' !== $_GET['prediction_team'] );
$prediction_archive_url = get_post_type_archive_link( 'prediction' );

get_header();
?>

<?php if ( function_exists( 'astra_page_layout' ) && 'left-sidebar' === astra_page_layout() ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area primary"'; } ?>>
	<?php if ( function_exists( 'astra_primary_content_top' ) ) : ?>
		<?php astra_primary_content_top(); ?>
	<?php endif; ?>

<main class="site-main wp-livescore-la-prediction-archive">
	<header class="wp-livescore-la-prediction-archive__header">
		<h1><?php esc_html_e( 'Predictions', 'wp-livescore-la' ); ?></h1>
	</header>

	<?php echo do_blocks( '<!-- wp:wp-livescore/prediction-filters {"showTitle":false} /-->' ); ?>

	<?php if ( have_posts() ) : ?>
		<div class="wp-livescore-la-prediction-archive__grid">
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<?php
				$prediction_id = get_the_ID();
				$teams         = trim( get_post_meta( $prediction_id, '_prediction_home_team_name', true ) . ' vs ' . get_post_meta( $prediction_id, '_prediction_away_team_name', true ) );
				$prediction_meta = array_filter(
					array(
						get_post_meta( $prediction_id, '_prediction_winner', true ),
						get_post_meta( $prediction_id, '_prediction_correct_score', true ) ? __( 'Score:', 'wp-livescore-la' ) . ' ' . get_post_meta( $prediction_id, '_prediction_correct_score', true ) : '',
						get_post_meta( $prediction_id, '_prediction_betting_angle', true ),
					),
					function ( $value ) {
						return '' !== trim( (string) $value );
					}
				);
				?>
				<article <?php post_class( 'wp-livescore-la-prediction-archive__card' ); ?>>
					<a class="wp-livescore-la-prediction-archive__image-link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium_large', array( 'class' => 'wp-livescore-la-prediction-archive__image' ) ); ?>
						<?php elseif ( function_exists( 'wp_livescore_la_get_image_placeholder' ) ) : ?>
							<?php echo wp_kses_post( wp_livescore_la_get_image_placeholder( 'wp-livescore-la-prediction-archive__image wp-livescore-la-prediction-archive__placeholder', get_the_title() ) ); ?>
						<?php endif; ?>
					</a>

					<div class="wp-livescore-la-prediction-archive__content">
						<h2 class="wp-livescore-la-prediction-archive__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

						<?php if ( '' !== $teams && 'vs' !== $teams ) : ?>
							<p class="wp-livescore-la-prediction-archive__teams"><?php echo esc_html( $teams ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $prediction_meta ) ) : ?>
							<p class="wp-livescore-la-prediction-archive__meta"><?php echo esc_html( implode( ' | ', $prediction_meta ) ); ?></p>
						<?php endif; ?>

						<?php if ( has_excerpt() ) : ?>
							<div class="wp-livescore-la-prediction-archive__excerpt">
								<?php the_excerpt(); ?>
							</div>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php function_exists( 'wp_livescore_la_the_archive_pagination' ) ? wp_livescore_la_the_archive_pagination() : the_posts_pagination(); ?>
	<?php else : ?>
		<section class="wp-livescore-la-prediction-archive__empty">
			<h2><?php esc_html_e( 'No predictions found', 'wp-livescore-la' ); ?></h2>
			<?php if ( $has_filter ) : ?>
				<p><?php esc_html_e( 'No predictions match the selected filters.', 'wp-livescore-la' ); ?></p>
				<?php if ( $prediction_archive_url ) : ?>
					<a class="button wp-livescore-la-prediction-archive__reset" href="<?php echo esc_url( $prediction_archive_url ); ?>">
						<?php esc_html_e( 'View all predictions', 'wp-livescore-la' ); ?>
					</a>
				<?php endif; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No predictions have been added yet.', 'wp-livescore-la' ); ?></p>
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
