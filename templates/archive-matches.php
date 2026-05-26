<?php
/**
 * Fallback Match archive template.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$match_archive_url = get_post_type_archive_link( 'match' );
$has_filter        = ( isset( $_GET['match_sport'] ) && '' !== $_GET['match_sport'] )
	|| ( isset( $_GET['match_country'] ) && '' !== $_GET['match_country'] )
	|| ( isset( $_GET['match_league'] ) && '' !== $_GET['match_league'] )
	|| ( isset( $_GET['match_date_filter'] ) && '' !== $_GET['match_date_filter'] )
	|| ( isset( $_GET['match_date'] ) && '' !== $_GET['match_date'] );

get_header();
?>

<?php if ( function_exists( 'astra_page_layout' ) && 'left-sidebar' === astra_page_layout() ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area primary"'; } ?>>
	<?php if ( function_exists( 'astra_primary_content_top' ) ) : ?>
		<?php astra_primary_content_top(); ?>
	<?php endif; ?>

<main class="site-main wp-livescore-la-match-archive">
	<header class="wp-livescore-la-match-archive__header">
		<h1><?php esc_html_e( 'Matches Directory', 'wp-livescore-la' ); ?></h1>
	</header>

	<?php echo do_blocks( '<!-- wp:wp-livescore/match-filters {"showTitle":false} /-->' ); ?>

	<?php if ( have_posts() ) : ?>
		<div class="wp-livescore-la-match-archive__grid">
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<?php
				$match_id   = get_the_ID();
				$teams      = trim( get_post_meta( $match_id, '_match_home_team_name', true ) . ' vs ' . get_post_meta( $match_id, '_match_away_team_name', true ) );
				$match_date = get_post_meta( $match_id, '_match_date', true );
				$match_date = function_exists( 'wp_livescore_la_format_match_date' ) ? wp_livescore_la_format_match_date( $match_date ) : $match_date;
				$match_status = function_exists( 'wp_livescore_la_get_match_status_label' ) ? wp_livescore_la_get_match_status_label( $match_id ) : get_post_meta( $match_id, '_match_status', true );
				$match_meta = array_filter(
					array(
						get_post_meta( $match_id, '_match_league_name', true ),
						get_post_meta( $match_id, '_match_season_name', true ),
						$match_date,
						$match_status,
					),
					function ( $value ) {
						return '' !== trim( (string) $value );
					}
				);
				?>
				<article <?php post_class( 'wp-livescore-la-match-archive__card' ); ?>>
					<a class="wp-livescore-la-match-archive__image-link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium_large', array( 'class' => 'wp-livescore-la-match-archive__image' ) ); ?>
						<?php elseif ( function_exists( 'wp_livescore_la_get_image_placeholder' ) ) : ?>
							<?php echo wp_kses_post( wp_livescore_la_get_image_placeholder( 'wp-livescore-la-match-archive__image wp-livescore-la-match-archive__placeholder', get_the_title() ) ); ?>
						<?php endif; ?>
					</a>

					<div class="wp-livescore-la-match-archive__content">
						<h2 class="wp-livescore-la-match-archive__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

						<?php if ( '' !== $teams && 'vs' !== $teams ) : ?>
							<p class="wp-livescore-la-match-archive__teams"><?php echo esc_html( $teams ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $match_meta ) ) : ?>
							<p class="wp-livescore-la-match-archive__meta"><?php echo esc_html( implode( ' | ', $match_meta ) ); ?></p>
						<?php endif; ?>

						<?php if ( has_excerpt() ) : ?>
							<div class="wp-livescore-la-match-archive__excerpt">
								<?php the_excerpt(); ?>
							</div>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php function_exists( 'wp_livescore_la_the_archive_pagination' ) ? wp_livescore_la_the_archive_pagination() : the_posts_pagination(); ?>
	<?php else : ?>
		<section class="wp-livescore-la-match-archive__empty">
			<h2><?php esc_html_e( 'No matches found', 'wp-livescore-la' ); ?></h2>
			<?php if ( $has_filter ) : ?>
				<p><?php esc_html_e( 'No matches match the selected filters.', 'wp-livescore-la' ); ?></p>
				<?php if ( $match_archive_url ) : ?>
					<a class="button wp-livescore-la-match-archive__reset" href="<?php echo esc_url( $match_archive_url ); ?>">
						<?php esc_html_e( 'View all matches', 'wp-livescore-la' ); ?>
					</a>
				<?php endif; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No matches have been added yet.', 'wp-livescore-la' ); ?></p>
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
