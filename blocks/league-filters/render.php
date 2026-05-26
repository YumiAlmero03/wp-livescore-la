<?php
/**
 * Server render for League Filters block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_title   = ! empty( $attributes['showTitle'] );
$show_sport   = ! array_key_exists( 'showSport', $attributes ) || ! empty( $attributes['showSport'] );
$show_country = ! array_key_exists( 'showCountry', $attributes ) || ! empty( $attributes['showCountry'] );
$title        = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Filter Leagues', 'wp-livescore-la' );
$button_text  = isset( $attributes['buttonText'] ) ? sanitize_text_field( $attributes['buttonText'] ) : __( 'Filter', 'wp-livescore-la' );
$reset_text   = isset( $attributes['resetText'] ) ? sanitize_text_field( $attributes['resetText'] ) : __( 'Reset', 'wp-livescore-la' );

$selected_sport   = isset( $_GET['league_sport'] ) ? sanitize_title( wp_unslash( $_GET['league_sport'] ) ) : '';
$selected_country = isset( $_GET['league_country'] ) ? sanitize_title( wp_unslash( $_GET['league_country'] ) ) : '';
$league_archive   = get_post_type_archive_link( 'league' );

if ( ! $league_archive ) {
	$league_archive = home_url( '/league/' );
}

$sports    = function_exists( 'wp_livescore_la_get_sports_manager_items' ) ? wp_livescore_la_get_sports_manager_items( true ) : array();
$countries = function_exists( 'wp_livescore_la_get_countries_manager_items' ) ? wp_livescore_la_get_countries_manager_items( true ) : array();

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-league-filters',
	)
);
?>
<section <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $show_title && '' !== $title ) : ?>
		<h2 class="wp-livescore-la-league-filters__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<form class="wp-livescore-la-league-filters__form" method="get" action="<?php echo esc_url( $league_archive ); ?>">
		<?php if ( $show_sport ) : ?>
			<label class="wp-livescore-la-league-filters__field">
				<span><?php esc_html_e( 'Sport', 'wp-livescore-la' ); ?></span>
				<select name="league_sport">
						<option value=""><?php esc_html_e( 'All sports', 'wp-livescore-la' ); ?></option>
						<?php foreach ( $sports as $sport ) : ?>
							<?php $sport_slug = $sport instanceof WP_Term ? sanitize_title( $sport->slug ) : sanitize_title( get_post_field( 'post_name', $sport ) ); ?>
							<option value="<?php echo esc_attr( $sport_slug ); ?>" <?php selected( $selected_sport, $sport_slug ); ?>>
								<?php echo esc_html( $sport instanceof WP_Term ? $sport->name : get_the_title( $sport ) ); ?>
							</option>
						<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<?php if ( $show_country ) : ?>
			<label class="wp-livescore-la-league-filters__field">
				<span><?php esc_html_e( 'Country', 'wp-livescore-la' ); ?></span>
				<select name="league_country">
					<option value=""><?php esc_html_e( 'All countries', 'wp-livescore-la' ); ?></option>
					<?php foreach ( $countries as $country ) : ?>
						<?php $country_slug = sanitize_title( get_post_field( 'post_name', $country ) ); ?>
						<option value="<?php echo esc_attr( $country_slug ); ?>" <?php selected( $selected_country, $country_slug ); ?>>
							<?php echo esc_html( get_the_title( $country ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<div class="wp-livescore-la-league-filters__actions">
			<button type="submit" class="wp-livescore-la-league-filters__submit"><?php echo esc_html( $button_text ); ?></button>
			<a class="wp-livescore-la-league-filters__reset" href="<?php echo esc_url( $league_archive ); ?>"><?php echo esc_html( $reset_text ); ?></a>
		</div>
	</form>
</section>
