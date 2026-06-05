<?php
/**
 * Server render for Related Prediction / Match block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context_id   = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
$context_type = $context_id > 0 ? get_post_type( $context_id ) : '';
$target_type  = isset( $attributes['targetType'] ) ? sanitize_key( $attributes['targetType'] ) : 'auto';
$target_type  = in_array( $target_type, array( 'auto', 'prediction', 'match' ), true ) ? $target_type : 'auto';

if ( 'auto' === $target_type ) {
	$target_type = 'prediction' === $context_type ? 'match' : 'prediction';
}

$api_id = isset( $attributes['apiId'] ) ? sanitize_text_field( $attributes['apiId'] ) : '';

if ( '' === $api_id && 'match' === $context_type ) {
	$api_id = get_post_meta( $context_id, '_match_api_id', true );
}

if ( '' === $api_id && 'prediction' === $context_type ) {
	$api_id = get_post_meta( $context_id, '_prediction_match_api_id', true );
	if ( '' === $api_id ) {
		$api_id = get_post_meta( $context_id, '_prediction_api_id', true );
	}
}

$related_id = 0;

if ( 'match' === $target_type && 'prediction' === $context_type ) {
	$stored_match_id = (int) get_post_meta( $context_id, '_prediction_match_id', true );
	if ( $stored_match_id > 0 && 'match' === get_post_type( $stored_match_id ) ) {
		$related_id = $stored_match_id;
	}
}

if ( $related_id <= 0 && '' !== $api_id ) {
	if ( 'prediction' === $target_type ) {
		$matches = get_posts(
			array(
				'post_type'      => 'prediction',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'   => '_prediction_match_api_id',
						'value' => $api_id,
					),
					array(
						'key'   => '_prediction_api_id',
						'value' => $api_id,
					),
				),
			)
		);
	} else {
		$matches = get_posts(
			array(
				'post_type'      => 'match',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => '_match_api_id',
				'meta_value'     => $api_id,
			)
		);
	}

	if ( ! empty( $matches[0] ) ) {
		$related_id = (int) $matches[0];
	}
}

if ( $related_id <= 0 || $target_type !== get_post_type( $related_id ) ) {
	$empty_message = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
	return '' !== $empty_message ? '<p class="wp-livescore-la-related-prediction-match__empty">' . esc_html( $empty_message ) . '</p>' : '';
}

$show_image   = ! array_key_exists( 'showImage', $attributes ) || ! empty( $attributes['showImage'] );
$show_excerpt = ! array_key_exists( 'showExcerpt', $attributes ) || ! empty( $attributes['showExcerpt'] );
$show_meta    = ! array_key_exists( 'showMeta', $attributes ) || ! empty( $attributes['showMeta'] );
$title        = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';
$permalink    = get_permalink( $related_id );
$post_title   = get_the_title( $related_id );
$excerpt      = has_excerpt( $related_id ) ? get_the_excerpt( $related_id ) : '';
$image        = '';

if ( $show_image ) {
	if ( has_post_thumbnail( $related_id ) ) {
		$image = get_the_post_thumbnail(
			$related_id,
			'medium_large',
			array(
				'class' => 'wp-livescore-la-related-prediction-match__image',
				'alt'   => trim( wp_strip_all_tags( $post_title ) ),
			)
		);
	} elseif ( function_exists( 'wp_livescore_la_get_image_placeholder' ) ) {
		$image = wp_livescore_la_get_image_placeholder( 'wp-livescore-la-related-prediction-match__image wp-livescore-la-related-prediction-match__placeholder', $post_title );
	}
}

$meta = array();
if ( $show_meta ) {
	if ( 'prediction' === $target_type ) {
		$winner = get_post_meta( $related_id, '_prediction_winner', true );
		$score  = get_post_meta( $related_id, '_prediction_correct_score', true );
		if ( '' !== (string) $winner ) {
			$meta[] = sanitize_text_field( $winner );
		}
		if ( '' !== (string) $score ) {
			$meta[] = sprintf(
				/* translators: %s: score pick. */
				__( 'Score: %s', 'wp-livescore-la' ),
				sanitize_text_field( $score )
			);
		}
	} else {
		$date = get_post_meta( $related_id, '_match_date', true );
		$time = get_post_meta( $related_id, '_match_time', true );
		if ( '' !== (string) $date ) {
			$meta[] = function_exists( 'wp_livescore_la_format_match_date' ) ? wp_livescore_la_format_match_date( $date ) : sanitize_text_field( $date );
		}
		if ( '' !== (string) $time ) {
			$meta[] = sanitize_text_field( $time );
		}
	}
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-related-prediction-match wp-livescore-la-related-prediction-match--' . $target_type,
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( '' !== $title ) : ?>
		<div class="wp-livescore-la-related-prediction-match__section-title"><?php echo esc_html( $title ); ?></div>
	<?php endif; ?>
	<article class="wp-livescore-la-related-prediction-match__card">
		<?php if ( '' !== $image ) : ?>
			<a class="wp-livescore-la-related-prediction-match__image-link" href="<?php echo esc_url( $permalink ); ?>">
				<?php echo wp_kses_post( $image ); ?>
			</a>
		<?php endif; ?>
		<div class="wp-livescore-la-related-prediction-match__content">
			<h3 class="wp-livescore-la-related-prediction-match__title">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $post_title ); ?></a>
			</h3>
			<?php if ( ! empty( $meta ) ) : ?>
				<p class="wp-livescore-la-related-prediction-match__meta"><?php echo esc_html( implode( ' | ', $meta ) ); ?></p>
			<?php endif; ?>
			<?php if ( $show_excerpt && '' !== $excerpt ) : ?>
				<p class="wp-livescore-la-related-prediction-match__excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 24 ) ); ?></p>
			<?php endif; ?>
		</div>
	</article>
</div>
