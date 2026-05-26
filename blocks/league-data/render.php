<?php
/**
 * Server render for League Data block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$league_id = isset( $block->context['postId'] ) && 'league' === get_post_type( (int) $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $league_id <= 0 && ! empty( $attributes['leagueId'] ) ) {
	$manual_id = absint( $attributes['leagueId'] );
	$league_id = 'league' === get_post_type( $manual_id ) ? $manual_id : 0;
}

if ( $league_id <= 0 ) {
	return '';
}

$data_field = isset( $attributes['dataField'] ) ? sanitize_text_field( $attributes['dataField'] ) : 'country';
$value      = '';

switch ( $data_field ) {
	case 'sport':
	case 'sports':
		$value = get_post_meta( $league_id, '_league_sport_name', true );
		if ( '' === $value ) {
			$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'sports', true );
		}
		break;
	case 'season':
	case 'strCurrentSeason':
		$value = get_post_meta( $league_id, '_league_current_season_name', true );
		if ( '' === $value ) {
			$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'strCurrentSeason', true );
		}
		break;
	case 'formedyear':
	case 'intFormedYear':
		$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'intFormedYear', true );
		break;
	case 'firstevent':
	case 'dateFirstEvent':
		$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'dateFirstEvent', true );
		break;
	case 'apiid':
	case 'api_id':
		$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'api_id', true );
		break;
	case 'apisource':
	case 'api_source':
		$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', true );
		break;
	case 'sportscoreslug':
	case 'sportscore_slug':
		$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'sportscore_slug', true );
		break;
	case 'strWebsite':
	case 'strFacebook':
	case 'strInstagram':
	case 'strTwitter':
	case 'strYoutube':
	case 'strRSS':
	case 'strBanner':
		$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . $data_field, true );
		break;
	case 'country':
	default:
		$value = get_post_meta( $league_id, '_league_country_name', true );
		if ( '' === $value ) {
			$value = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'country', true );
		}
		break;
}

$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
$make_link     = ! empty( $attributes['makeLink'] );
$permalink     = $make_link ? get_permalink( $league_id ) : '';
$prefix        = isset( $attributes['prefix'] ) ? sanitize_text_field( $attributes['prefix'] ) : '';
$suffix        = isset( $attributes['suffix'] ) ? sanitize_text_field( $attributes['suffix'] ) : '';
$text_align_options = array( 'left', 'center', 'right', 'justify' );
$text_align     = isset( $attributes['textAlign'] ) ? sanitize_key( $attributes['textAlign'] ) : '';
$text_align     = in_array( $text_align, $text_align_options, true ) ? $text_align : '';
$icon_options  = array( 'calendar-alt', 'clock', 'location', 'flag', 'groups', 'chart-bar', 'awards', 'admin-links', 'star-filled', 'shield' );
$icon          = isset( $attributes['icon'] ) ? sanitize_key( $attributes['icon'] ) : '';
$icon          = in_array( $icon, $icon_options, true ) ? $icon : '';

if ( '' === (string) $value && '' === $empty_message ) {
	return '';
}

if ( '' !== $icon ) {
	wp_enqueue_style( 'dashicons' );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => trim( 'wp-livescore-la-league-data' . ( '' !== $text_align ? ' has-text-align-' . $text_align : '' ) ),
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( '' !== (string) $value ) : ?>
		<div class="wp-livescore-la-league-data__value">
			<?php if ( '' !== $icon ) : ?>
				<span class="wp-livescore-la-league-data__icon dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
			<span class="wp-livescore-la-league-data__content">
				<?php if ( '' !== $prefix ) : ?>
					<span class="wp-livescore-la-league-data__prefix"><?php echo esc_html( $prefix ); ?></span>
				<?php endif; ?>
				<?php if ( $make_link && $permalink ) : ?>
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $value ); ?></a>
				<?php else : ?>
					<span class="wp-livescore-la-league-data__field"><?php echo esc_html( $value ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $suffix ) : ?>
					<span class="wp-livescore-la-league-data__suffix"><?php echo esc_html( $suffix ); ?></span>
				<?php endif; ?>
			</span>
		</div>
	<?php else : ?>
		<p class="wp-livescore-la-league-data__empty"><?php echo esc_html( $empty_message ); ?></p>
	<?php endif; ?>
</div>
