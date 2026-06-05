<?php
/**
 * Server render for Match Win Graph block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id   = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
$post_type = $post_id > 0 ? get_post_type( $post_id ) : '';
$source_id = 0;
$source    = '';

if ( 'match' === $post_type || 'prediction' === $post_type ) {
	$source_id = $post_id;
	$source    = $post_type;
}

if ( ! empty( $attributes['matchId'] ) ) {
	$manual_id = absint( $attributes['matchId'] );
	if ( 'match' === get_post_type( $manual_id ) ) {
		$source_id = $manual_id;
		$source    = 'match';
	}
}

if ( $source_id <= 0 ) {
	return '';
}

if ( 'prediction' === $source ) {
	$home_value = get_post_meta( $source_id, '_prediction_home_win_percent', true );
	$draw_value = get_post_meta( $source_id, '_prediction_draw_percent', true );
	$away_value = get_post_meta( $source_id, '_prediction_away_win_percent', true );
	$home_label = get_post_meta( $source_id, '_prediction_home_team_name', true );
	$away_label = get_post_meta( $source_id, '_prediction_away_team_name', true );
} else {
	$home_value = get_post_meta( $source_id, '_match_home_win_percentage', true );
	$draw_value = get_post_meta( $source_id, '_match_draw_percentage', true );
	$away_value = get_post_meta( $source_id, '_match_away_win_percentage', true );
	$home_label = get_post_meta( $source_id, '_match_home_team_name', true );
	$away_label = get_post_meta( $source_id, '_match_away_team_name', true );
}

$home = max( 0, min( 100, absint( $home_value ) ) );
$draw = max( 0, min( 100, absint( $draw_value ) ) );
$away = max( 0, min( 100, absint( $away_value ) ) );

if ( 0 === $home && 0 === $draw && 0 === $away ) {
	$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
	return '' !== $empty_message ? '<p class="wp-livescore-la-match-win-graph__empty">' . esc_html( $empty_message ) . '</p>' : '';
}

$home_label = '' !== (string) $home_label ? sanitize_text_field( $home_label ) : __( 'Home Win', 'wp-livescore-la' );
$away_label = '' !== (string) $away_label ? sanitize_text_field( $away_label ) : __( 'Away Win', 'wp-livescore-la' );
$draw_label = __( 'Draw', 'wp-livescore-la' );
$title      = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Win Probability', 'wp-livescore-la' );
$show_title = ! array_key_exists( 'showTitle', $attributes ) || ! empty( $attributes['showTitle'] );
$color_vars = array();
$graph_colors = array();
$astra_color_fallbacks = array(
	'astra-0' => array( 'var' => 'var(--ast-global-color-0)', 'fallback' => '#0170b9' ),
	'astra-1' => array( 'var' => 'var(--ast-global-color-1)', 'fallback' => '#3a3a3a' ),
	'astra-2' => array( 'var' => 'var(--ast-global-color-2)', 'fallback' => '#3f3f46' ),
	'astra-3' => array( 'var' => 'var(--ast-global-color-3)', 'fallback' => '#4b5563' ),
	'astra-4' => array( 'var' => 'var(--ast-global-color-4)', 'fallback' => '#f5f5f5' ),
	'astra-5' => array( 'var' => 'var(--ast-global-color-5)', 'fallback' => '#ffffff' ),
	'astra-6' => array( 'var' => 'var(--ast-global-color-6)', 'fallback' => '#111827' ),
	'astra-7' => array( 'var' => 'var(--ast-global-color-7)', 'fallback' => '#e5e7eb' ),
	'astra-8' => array( 'var' => 'var(--ast-global-color-8)', 'fallback' => '#f9fafb' ),
);

foreach ( array( 'homeColor' => '--wp-livescore-la-win-home', 'drawColor' => '--wp-livescore-la-win-draw', 'awayColor' => '--wp-livescore-la-win-away' ) as $attribute_key => $css_var ) {
	$raw_color = isset( $attributes[ $attribute_key ] ) ? trim( (string) $attributes[ $attribute_key ] ) : '';
	$color     = sanitize_hex_color( $raw_color );
	$fallback  = '';

	if ( empty( $color ) && isset( $astra_color_fallbacks[ $raw_color ] ) ) {
		$fallback = $astra_color_fallbacks[ $raw_color ]['fallback'];
		$color    = $astra_color_fallbacks[ $raw_color ]['var'];
	}

	if ( empty( $color ) && preg_match( '/^var\(--ast-global-color-([0-8])\)$/', $raw_color, $matches ) ) {
		$legacy_key = 'astra-' . $matches[1];
		if ( isset( $astra_color_fallbacks[ $legacy_key ] ) ) {
			$fallback = $astra_color_fallbacks[ $legacy_key ]['fallback'];
		}
		$color = $raw_color;
	}

	if ( ! empty( $color ) ) {
		$color_vars[] = $css_var . ':' . $color . ';';
		$graph_colors[ $attribute_key ] = array(
			'color'    => $color,
			'fallback' => $fallback,
		);
	}
}

$items = array(
	array(
		'class' => 'home',
		'label' => $home_label,
		'value' => $home,
		'color' => isset( $graph_colors['homeColor'] ) ? $graph_colors['homeColor'] : array(),
	),
	array(
		'class' => 'draw',
		'label' => $draw_label,
		'value' => $draw,
		'color' => isset( $graph_colors['drawColor'] ) ? $graph_colors['drawColor'] : array(),
	),
	array(
		'class' => 'away',
		'label' => $away_label,
		'value' => $away,
		'color' => isset( $graph_colors['awayColor'] ) ? $graph_colors['awayColor'] : array(),
	),
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-match-win-graph',
		'style' => implode( '', $color_vars ),
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $show_title && '' !== $title ) : ?>
		<div class="wp-livescore-la-match-win-graph__title"><?php echo esc_html( $title ); ?></div>
	<?php endif; ?>
	<div class="wp-livescore-la-match-win-graph__bar" aria-hidden="true">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$color_style = '';
			if ( ! empty( $item['color']['fallback'] ) ) {
				$color_style .= 'background-color:' . esc_attr( $item['color']['fallback'] ) . ';';
			}
			if ( ! empty( $item['color']['color'] ) ) {
				$color_style .= 'background-color:' . esc_attr( $item['color']['color'] ) . ';';
			}
			?>
			<span class="wp-livescore-la-match-win-graph__segment wp-livescore-la-match-win-graph__segment--<?php echo esc_attr( $item['class'] ); ?>" style="width: <?php echo esc_attr( (string) $item['value'] ); ?>%;<?php echo $color_style; ?>"></span>
		<?php endforeach; ?>
	</div>
	<div class="wp-livescore-la-match-win-graph__legend">
		<?php foreach ( $items as $item ) : ?>
			<div class="wp-livescore-la-match-win-graph__item wp-livescore-la-match-win-graph__item--<?php echo esc_attr( $item['class'] ); ?>">
				<?php
				$swatch_style = '';
				if ( ! empty( $item['color']['fallback'] ) ) {
					$swatch_style .= 'background-color:' . esc_attr( $item['color']['fallback'] ) . ';';
				}
				if ( ! empty( $item['color']['color'] ) ) {
					$swatch_style .= 'background-color:' . esc_attr( $item['color']['color'] ) . ';';
				}
				?>
				<span class="wp-livescore-la-match-win-graph__swatch" style="<?php echo $swatch_style; ?>" aria-hidden="true"></span>
				<span class="wp-livescore-la-match-win-graph__label"><?php echo esc_html( $item['label'] ); ?></span>
				<span class="wp-livescore-la-match-win-graph__value"><?php echo esc_html( $item['value'] ); ?>%</span>
			</div>
		<?php endforeach; ?>
	</div>
</div>
