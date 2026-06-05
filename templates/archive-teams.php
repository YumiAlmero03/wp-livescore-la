<?php
/**
 * Fallback Team archive template.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

$team_archive_url = get_post_type_archive_link( 'team' );
$has_filter       = ( isset( $_GET['team_sport'] ) && '' !== $_GET['team_sport'] )
	|| ( isset( $_GET['team_league'] ) && '' !== $_GET['team_league'] )
	|| ( isset( $_GET['team_country'] ) && '' !== $_GET['team_country'] );
$load_more_query_vars = function_exists( 'wp_livescore_la_get_team_archive_load_more_query_vars' )
	? wp_livescore_la_get_team_archive_load_more_query_vars( $wp_query )
	: array();

get_header();
?>

<?php if ( function_exists( 'astra_page_layout' ) && 'left-sidebar' === astra_page_layout() ) : ?>
	<?php get_sidebar(); ?>
<?php endif; ?>

<div id="primary" <?php if ( function_exists( 'astra_primary_class' ) ) { astra_primary_class(); } else { echo 'class="content-area primary"'; } ?>>
	<?php if ( function_exists( 'astra_primary_content_top' ) ) : ?>
		<?php astra_primary_content_top(); ?>
	<?php endif; ?>

<main class="site-main wp-livescore-la-team-archive">
	<header class="wp-livescore-la-team-archive__header">
		<h1><?php esc_html_e( 'Teams Directory', 'wp-livescore-la' ); ?></h1>
	</header>

	<?php echo do_blocks( '<!-- wp:wp-livescore/team-filters {"showTitle":false} /-->' ); ?>

	<?php if ( have_posts() ) : ?>
		<div class="wp-livescore-la-team-archive__grid">
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<?php echo function_exists( 'wp_livescore_la_render_team_archive_card' ) ? wp_livescore_la_render_team_archive_card() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endwhile; ?>
		</div>

		<?php if ( $wp_query instanceof WP_Query && $wp_query->max_num_pages > 1 ) : ?>
			<div class="wp-livescore-la-team-archive__load-more-wrap">
				<button
					type="button"
					class="button wp-livescore-la-team-archive__load-more"
					data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_livescore_la_load_more_teams' ) ); ?>"
					data-next-page="2"
					data-max-pages="<?php echo esc_attr( (string) $wp_query->max_num_pages ); ?>"
					data-query-vars="<?php echo esc_attr( wp_json_encode( $load_more_query_vars ) ); ?>"
				>
					<?php esc_html_e( 'View More', 'wp-livescore-la' ); ?>
				</button>
			</div>
			<noscript>
				<?php function_exists( 'wp_livescore_la_the_archive_pagination' ) ? wp_livescore_la_the_archive_pagination() : the_posts_pagination(); ?>
			</noscript>
		<?php endif; ?>
	<?php else : ?>
		<section class="wp-livescore-la-team-archive__empty">
			<h2><?php esc_html_e( 'No teams found', 'wp-livescore-la' ); ?></h2>
			<?php if ( $has_filter ) : ?>
				<p><?php esc_html_e( 'No teams match the selected filters.', 'wp-livescore-la' ); ?></p>
				<?php if ( $team_archive_url ) : ?>
					<a class="button wp-livescore-la-team-archive__reset" href="<?php echo esc_url( $team_archive_url ); ?>">
						<?php esc_html_e( 'View all teams', 'wp-livescore-la' ); ?>
					</a>
				<?php endif; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No teams have been added yet.', 'wp-livescore-la' ); ?></p>
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
