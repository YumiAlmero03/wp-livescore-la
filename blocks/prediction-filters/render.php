<?php
/**
 * Server render for Prediction Filters block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_title       = ! empty( $attributes['showTitle'] );
$show_country     = ! array_key_exists( 'showCountry', $attributes ) || ! empty( $attributes['showCountry'] );
$show_team_search = ! array_key_exists( 'showTeamSearch', $attributes ) || ! empty( $attributes['showTeamSearch'] );
$title            = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Filter Predictions', 'wp-livescore-la' );
$button_text      = isset( $attributes['buttonText'] ) ? sanitize_text_field( $attributes['buttonText'] ) : __( 'Filter', 'wp-livescore-la' );
$reset_text       = isset( $attributes['resetText'] ) ? sanitize_text_field( $attributes['resetText'] ) : __( 'Reset', 'wp-livescore-la' );

$selected_country = isset( $_GET['prediction_country'] ) ? sanitize_title( wp_unslash( $_GET['prediction_country'] ) ) : '';
$team_search      = isset( $_GET['prediction_team'] ) ? sanitize_text_field( wp_unslash( $_GET['prediction_team'] ) ) : '';
$archive_url      = get_post_type_archive_link( 'prediction' );

if ( ! $archive_url ) {
	$archive_url = home_url( '/predictions/' );
}

$countries = function_exists( 'wp_livescore_la_get_countries_manager_items' ) ? wp_livescore_la_get_countries_manager_items( true ) : array();

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-prediction-filters',
	)
);
?>
<section <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $show_title && '' !== $title ) : ?>
		<h2 class="wp-livescore-la-prediction-filters__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<form class="wp-livescore-la-prediction-filters__form" method="get" action="<?php echo esc_url( $archive_url ); ?>">
		<?php if ( $show_country ) : ?>
			<label class="wp-livescore-la-prediction-filters__field">
				<span><?php esc_html_e( 'Country', 'wp-livescore-la' ); ?></span>
				<select name="prediction_country">
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

		<?php if ( $show_team_search ) : ?>
			<label class="wp-livescore-la-prediction-filters__field">
				<span><?php esc_html_e( 'Team', 'wp-livescore-la' ); ?></span>
				<input type="search" name="prediction_team" value="<?php echo esc_attr( $team_search ); ?>" placeholder="<?php echo esc_attr__( 'Search related team', 'wp-livescore-la' ); ?>" />
			</label>
		<?php endif; ?>

		<div class="wp-livescore-la-prediction-filters__actions">
			<button type="submit" class="wp-livescore-la-prediction-filters__submit"><?php echo esc_html( $button_text ); ?></button>
			<a class="wp-livescore-la-prediction-filters__reset" href="<?php echo esc_url( $archive_url ); ?>"><?php echo esc_html( $reset_text ); ?></a>
		</div>
	</form>
</section>
