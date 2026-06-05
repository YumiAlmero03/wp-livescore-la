<?php
/**
 * Server render for Player Data block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$player_id = isset( $block->context['postId'] ) && 'player' === get_post_type( (int) $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $player_id <= 0 && ! empty( $attributes['playerId'] ) ) {
	$manual_id = absint( $attributes['playerId'] );
	$player_id = 'player' === get_post_type( $manual_id ) ? $manual_id : 0;
}

if ( $player_id <= 0 ) {
	return '';
}

$field_options = function_exists( 'wp_livescore_la_player_meta_fields' ) ? wp_livescore_la_player_meta_fields() : array();
$data_field    = isset( $attributes['dataField'] ) ? sanitize_key( $attributes['dataField'] ) : '_player_position';

if ( '__title' !== $data_field && ! isset( $field_options[ $data_field ] ) ) {
	$data_field = '_player_position';
}

$value = '__title' === $data_field ? get_the_title( $player_id ) : get_post_meta( $player_id, $data_field, true );

if ( '_player_position' === $data_field && function_exists( 'wp_livescore_la_format_player_position' ) ) {
	$value = wp_livescore_la_format_player_position( $value );
}

if ( '_player_birthday' === $data_field && '' !== (string) $value ) {
	$birthday_timestamp = strtotime( (string) $value );

	if ( false !== $birthday_timestamp ) {
		$value = wp_date( 'M. j, Y', $birthday_timestamp );
	}
}

$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
$make_link     = ! empty( $attributes['makeLink'] );
$permalink     = $make_link ? get_permalink( $player_id ) : '';
$title         = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';
$title_tag_options = array( 'div', 'h2', 'h3', 'h4', 'h5', 'h6' );
$title_tag     = isset( $attributes['titleTag'] ) ? sanitize_key( $attributes['titleTag'] ) : 'div';
$title_tag     = in_array( $title_tag, $title_tag_options, true ) ? $title_tag : 'div';
$prefix        = isset( $attributes['prefix'] ) ? sanitize_text_field( $attributes['prefix'] ) : '';
$suffix        = isset( $attributes['suffix'] ) ? sanitize_text_field( $attributes['suffix'] ) : '';
$text_transform_options = array( 'uppercase', 'lowercase', 'capitalize', 'none' );
$text_transform = isset( $attributes['textTransform'] ) ? sanitize_key( $attributes['textTransform'] ) : '';
$text_transform = in_array( $text_transform, $text_transform_options, true ) ? $text_transform : '';
$text_align_options = array( 'left', 'center', 'right', 'justify' );
$text_align = isset( $attributes['textAlign'] ) ? sanitize_key( $attributes['textAlign'] ) : '';
$text_align = in_array( $text_align, $text_align_options, true ) ? $text_align : '';
$icon_options  = array( 'id', 'groups', 'flag', 'calendar-alt', 'star-filled', 'awards', 'yes-alt', 'admin-links' );
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
		'class' => trim( 'wp-livescore-la-player-data' . ( '' !== $text_transform ? ' has-text-transform-' . $text_transform : '' ) . ( '' !== $text_align ? ' has-text-align-' . $text_align : '' ) ),
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( '' !== (string) $value ) : ?>
		<?php if ( '' !== $title ) : ?>
			<<?php echo tag_escape( $title_tag ); ?> class="wp-livescore-la-player-data__title"><?php echo esc_html( $title ); ?></<?php echo tag_escape( $title_tag ); ?>>
		<?php endif; ?>
		<div class="wp-livescore-la-player-data__value">
			<?php if ( '' !== $icon ) : ?>
				<span class="wp-livescore-la-player-data__icon dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
			<span class="wp-livescore-la-player-data__content">
				<?php if ( '' !== $prefix ) : ?>
					<span class="wp-livescore-la-player-data__prefix"><?php echo esc_html( $prefix ); ?></span>
				<?php endif; ?>
				<?php if ( $make_link && $permalink ) : ?>
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $value ); ?></a>
				<?php else : ?>
					<span class="wp-livescore-la-player-data__field"><?php echo esc_html( $value ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $suffix ) : ?>
					<span class="wp-livescore-la-player-data__suffix"><?php echo esc_html( $suffix ); ?></span>
				<?php endif; ?>
			</span>
		</div>
	<?php else : ?>
		<p class="wp-livescore-la-player-data__empty"><?php echo esc_html( $empty_message ); ?></p>
	<?php endif; ?>
</div>
