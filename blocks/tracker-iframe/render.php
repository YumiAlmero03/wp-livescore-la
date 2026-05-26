<?php
/**
 * Server render for Tracker score block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$match_id = isset( $block->context['postId'] ) && 'match' === get_post_type( (int) $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $match_id <= 0 ) {
	return '';
}

$home_score = get_post_meta( $match_id, '_match_home_score', true );
$away_score = get_post_meta( $match_id, '_match_away_score', true );
$home_score = '' !== (string) $home_score ? sanitize_text_field( (string) $home_score ) : '0';
$away_score = '' !== (string) $away_score ? sanitize_text_field( (string) $away_score ) : '0';
$start_timestamp = function_exists( 'wp_livescore_la_get_match_start_timestamp' ) ? wp_livescore_la_get_match_start_timestamp( $match_id ) : 0;
$show_countdown  = $start_timestamp > time();
$start_date      = $start_timestamp > 0 ? wp_date( 'M. j, Y', $start_timestamp ) : '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-tracker-iframe',
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $show_countdown ) : ?>
		<span
			class="wp-livescore-la-tracker-iframe__countdown"
			data-match-countdown="<?php echo esc_attr( (string) ( $start_timestamp * 1000 ) ); ?>"
			data-started-label="<?php echo esc_attr__( 'Match started', 'wp-livescore-la' ); ?>"
		>
			<span class="wp-livescore-la-tracker-iframe__countdown-value"><?php esc_html_e( 'Loading countdown...', 'wp-livescore-la' ); ?></span>
		</span>
	<?php else : ?>
		<span class="wp-livescore-la-tracker-iframe__score" aria-label="<?php echo esc_attr__( 'Match score', 'wp-livescore-la' ); ?>">
			<span class="wp-livescore-la-tracker-iframe__home-score"><?php echo esc_html( $home_score ); ?></span>
			<span class="wp-livescore-la-tracker-iframe__separator" aria-hidden="true">-</span>
			<span class="wp-livescore-la-tracker-iframe__away-score"><?php echo esc_html( $away_score ); ?></span>
		</span>
	<?php endif; ?>
</div>
