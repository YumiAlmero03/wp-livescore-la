<?php
/**
 * Server render for Match Filters block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_title   = ! empty( $attributes['showTitle'] );
$show_sport   = ! array_key_exists( 'showSport', $attributes ) || ! empty( $attributes['showSport'] );
$show_country = ! array_key_exists( 'showCountry', $attributes ) || ! empty( $attributes['showCountry'] );
$show_league  = ! array_key_exists( 'showLeague', $attributes ) || ! empty( $attributes['showLeague'] );
$show_date    = ! array_key_exists( 'showDate', $attributes ) || ! empty( $attributes['showDate'] );
$title        = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Filter Matches', 'wp-livescore-la' );
$button_text  = isset( $attributes['buttonText'] ) ? sanitize_text_field( $attributes['buttonText'] ) : __( 'Filter', 'wp-livescore-la' );
$reset_text   = isset( $attributes['resetText'] ) ? sanitize_text_field( $attributes['resetText'] ) : __( 'Reset', 'wp-livescore-la' );

$selected_sport   = isset( $_GET['match_sport'] ) ? sanitize_title( wp_unslash( $_GET['match_sport'] ) ) : '';
$selected_country = isset( $_GET['match_country'] ) ? sanitize_title( wp_unslash( $_GET['match_country'] ) ) : '';
$selected_league  = isset( $_GET['match_league'] ) ? sanitize_title( wp_unslash( $_GET['match_league'] ) ) : '';
$selected_date    = isset( $_GET['match_date_filter'] ) ? sanitize_key( wp_unslash( $_GET['match_date_filter'] ) ) : '';
$custom_date      = isset( $_GET['match_date'] ) ? sanitize_text_field( wp_unslash( $_GET['match_date'] ) ) : '';
$match_archive    = get_post_type_archive_link( 'match' );

if ( ! in_array( $selected_date, array( '', 'live', 'today', 'upcoming', 'past', 'results', 'custom' ), true ) ) {
	$selected_date = '';
}

if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $custom_date ) ) {
	$custom_date = '';
}

if ( ! $match_archive ) {
	$match_archive = home_url( '/matches/' );
}

$sports    = function_exists( 'wp_livescore_la_get_sports_manager_items' ) ? wp_livescore_la_get_sports_manager_items( true ) : array();
$countries = function_exists( 'wp_livescore_la_get_countries_manager_items' ) ? wp_livescore_la_get_countries_manager_items( true ) : array();
$leagues   = get_posts(
	array(
		'post_type'      => 'league',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-match-filters',
	)
);
?>
<section <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $show_title && '' !== $title ) : ?>
		<h2 class="wp-livescore-la-match-filters__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<form class="wp-livescore-la-match-filters__form" method="get" action="<?php echo esc_url( $match_archive ); ?>">
		<?php if ( $show_sport ) : ?>
			<label class="wp-livescore-la-match-filters__field">
				<span><?php esc_html_e( 'Sport', 'wp-livescore-la' ); ?></span>
				<select name="match_sport">
						<option value=""><?php esc_html_e( 'All sports', 'wp-livescore-la' ); ?></option>
						<?php foreach ( $sports as $sport ) : ?>
							<?php $sport_slug = $sport instanceof WP_Term ? sanitize_title( $sport->slug ) : sanitize_title( get_post_field( 'post_name', $sport ) ); ?>
							<option value="<?php echo esc_attr( $sport_slug ); ?>" <?php selected( $selected_sport, $sport_slug ); ?>><?php echo esc_html( $sport instanceof WP_Term ? $sport->name : get_the_title( $sport ) ); ?></option>
						<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<?php if ( $show_country ) : ?>
			<label class="wp-livescore-la-match-filters__field">
				<span><?php esc_html_e( 'Country', 'wp-livescore-la' ); ?></span>
				<select name="match_country">
					<option value=""><?php esc_html_e( 'All countries', 'wp-livescore-la' ); ?></option>
					<?php foreach ( $countries as $country ) : ?>
						<?php $country_slug = sanitize_title( get_post_field( 'post_name', $country ) ); ?>
						<option value="<?php echo esc_attr( $country_slug ); ?>" <?php selected( $selected_country, $country_slug ); ?>><?php echo esc_html( get_the_title( $country ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<?php if ( $show_league ) : ?>
			<label class="wp-livescore-la-match-filters__field">
				<span><?php esc_html_e( 'League', 'wp-livescore-la' ); ?></span>
				<select name="match_league">
					<option value=""><?php esc_html_e( 'All leagues', 'wp-livescore-la' ); ?></option>
					<?php foreach ( $leagues as $league ) : ?>
						<?php $league_slug = sanitize_title( get_post_field( 'post_name', $league ) ); ?>
						<option value="<?php echo esc_attr( $league_slug ); ?>" <?php selected( $selected_league, $league_slug ); ?>><?php echo esc_html( get_the_title( $league ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<?php if ( $show_date ) : ?>
			<label class="wp-livescore-la-match-filters__field">
				<span><?php esc_html_e( 'Date', 'wp-livescore-la' ); ?></span>
				<select name="match_date_filter">
					<option value=""><?php esc_html_e( 'All dates', 'wp-livescore-la' ); ?></option>
					<option value="live" <?php selected( $selected_date, 'live' ); ?>><?php esc_html_e( 'Live', 'wp-livescore-la' ); ?></option>
					<option value="today" <?php selected( $selected_date, 'today' ); ?>><?php esc_html_e( 'Today', 'wp-livescore-la' ); ?></option>
					<option value="upcoming" <?php selected( $selected_date, 'upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'wp-livescore-la' ); ?></option>
					<option value="past" <?php selected( $selected_date, 'past' ); ?>><?php esc_html_e( 'Past', 'wp-livescore-la' ); ?></option>
					<option value="results" <?php selected( $selected_date, 'results' ); ?>><?php esc_html_e( 'Results', 'wp-livescore-la' ); ?></option>
					<option value="custom" <?php selected( $selected_date, 'custom' ); ?>><?php esc_html_e( 'Custom date', 'wp-livescore-la' ); ?></option>
				</select>
			</label>

			<label class="wp-livescore-la-match-filters__field wp-livescore-la-match-filters__custom-date" data-match-custom-date <?php echo 'custom' !== $selected_date ? 'hidden' : ''; ?>>
				<span><?php esc_html_e( 'Custom Date', 'wp-livescore-la' ); ?></span>
				<input type="date" name="match_date" value="<?php echo esc_attr( $custom_date ); ?>">
			</label>
		<?php endif; ?>

		<div class="wp-livescore-la-match-filters__actions">
			<button type="submit" class="wp-livescore-la-match-filters__submit"><?php echo esc_html( $button_text ); ?></button>
			<a class="wp-livescore-la-match-filters__reset" href="<?php echo esc_url( $match_archive ); ?>"><?php echo esc_html( $reset_text ); ?></a>
		</div>
	</form>
</section>
