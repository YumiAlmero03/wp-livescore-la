<?php
/**
 * Server render for Team Data block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$team_id = isset( $block->context['postId'] ) && 'team' === get_post_type( (int) $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $team_id <= 0 && ! empty( $attributes['teamId'] ) ) {
	$manual_id = absint( $attributes['teamId'] );
	$team_id   = 'team' === get_post_type( $manual_id ) ? $manual_id : 0;
}

if ( $team_id <= 0 ) {
	return '';
}

$field_options = function_exists( 'wp_livescore_la_team_meta_fields' ) ? wp_livescore_la_team_meta_fields() : array();
$data_field    = isset( $attributes['dataField'] ) ? sanitize_key( $attributes['dataField'] ) : '_team_short_name';

if ( ! isset( $field_options[ $data_field ] ) ) {
	$data_field = '_team_short_name';
}

$value         = get_post_meta( $team_id, $data_field, true );
$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
$make_link     = ! empty( $attributes['makeLink'] );
$prefix        = isset( $attributes['prefix'] ) ? sanitize_text_field( $attributes['prefix'] ) : '';
$suffix        = isset( $attributes['suffix'] ) ? sanitize_text_field( $attributes['suffix'] ) : '';
$url_fields    = array( '_team_logo', '_team_website', '_team_facebook', '_team_instagram', '_team_twitter', '_team_youtube' );
$permalink     = $make_link ? get_permalink( $team_id ) : '';

if ( $make_link && in_array( $data_field, $url_fields, true ) && '' !== (string) $value ) {
	$permalink = esc_url_raw( $value );
}

$text_transform_options = array( 'uppercase', 'lowercase', 'capitalize', 'none' );
$text_transform         = isset( $attributes['textTransform'] ) ? sanitize_key( $attributes['textTransform'] ) : '';
$text_transform         = in_array( $text_transform, $text_transform_options, true ) ? $text_transform : '';
$text_align_options     = array( 'left', 'center', 'right', 'justify' );
$text_align             = isset( $attributes['textAlign'] ) ? sanitize_key( $attributes['textAlign'] ) : '';
$text_align             = in_array( $text_align, $text_align_options, true ) ? $text_align : '';
$icon_options           = array( 'admin-site-alt3', 'shield', 'flag', 'groups', 'admin-links', 'format-image', 'yes-alt', 'location' );
$icon                   = isset( $attributes['icon'] ) ? sanitize_key( $attributes['icon'] ) : '';
$icon                   = in_array( $icon, $icon_options, true ) ? $icon : '';

if ( '' === (string) $value && '' === $empty_message ) {
	return '';
}

if ( '' !== $icon ) {
	wp_enqueue_style( 'dashicons' );
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => trim( 'wp-livescore-la-team-data' . ( '' !== $text_transform ? ' has-text-transform-' . $text_transform : '' ) . ( '' !== $text_align ? ' has-text-align-' . $text_align : '' ) ),
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( '' !== (string) $value ) : ?>
		<div class="wp-livescore-la-team-data__value">
			<?php if ( '' !== $icon ) : ?>
				<span class="wp-livescore-la-team-data__icon dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
			<span class="wp-livescore-la-team-data__content">
				<?php if ( '' !== $prefix ) : ?>
					<span class="wp-livescore-la-team-data__prefix"><?php echo esc_html( $prefix ); ?></span>
				<?php endif; ?>
				<?php if ( $make_link && $permalink ) : ?>
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $value ); ?></a>
				<?php else : ?>
					<span class="wp-livescore-la-team-data__field"><?php echo esc_html( $value ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $suffix ) : ?>
					<span class="wp-livescore-la-team-data__suffix"><?php echo esc_html( $suffix ); ?></span>
				<?php endif; ?>
			</span>
		</div>
	<?php else : ?>
		<p class="wp-livescore-la-team-data__empty"><?php echo esc_html( $empty_message ); ?></p>
	<?php endif; ?>
</div>
