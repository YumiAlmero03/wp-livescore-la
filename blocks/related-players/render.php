<?php
/**
 * Server render for Related Players block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context_post_id   = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
$context_post_type = $context_post_id > 0 ? get_post_type( $context_post_id ) : '';
$match_id          = 'match' === $context_post_type ? $context_post_id : 0;
$team_id           = 'team' === $context_post_type ? $context_post_id : 0;

if ( $match_id <= 0 && ! empty( $attributes['matchId'] ) ) {
	$manual_id = absint( $attributes['matchId'] );
	$match_id  = 'match' === get_post_type( $manual_id ) ? $manual_id : 0;
}

if ( $team_id <= 0 && ! empty( $attributes['teamId'] ) ) {
	$manual_id = absint( $attributes['teamId'] );
	$team_id   = 'team' === get_post_type( $manual_id ) ? $manual_id : 0;
}

if ( $match_id <= 0 && $team_id <= 0 ) {
	return '';
}

$team_side      = isset( $attributes['teamSide'] ) ? sanitize_key( $attributes['teamSide'] ) : 'both';
$team_side      = in_array( $team_side, array( 'home', 'away', 'both' ), true ) ? $team_side : 'both';
$show_title     = ! array_key_exists( 'showTitle', $attributes ) || ! empty( $attributes['showTitle'] );
$title          = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Players', 'wp-livescore-la' );
$show_images    = ! array_key_exists( 'showImages', $attributes ) || ! empty( $attributes['showImages'] );
$show_meta      = ! array_key_exists( 'showMeta', $attributes ) || ! empty( $attributes['showMeta'] );
$show_number    = ! array_key_exists( 'showNumber', $attributes ) || ! empty( $attributes['showNumber'] );
$show_position  = ! array_key_exists( 'showPosition', $attributes ) || ! empty( $attributes['showPosition'] );
$make_links     = ! array_key_exists( 'makeLinks', $attributes ) || ! empty( $attributes['makeLinks'] );
$columns        = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 2;
$columns        = max( 1, min( 4, $columns ) );
$posts_per_page = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 50;
$posts_per_page = max( 1, min( 200, $posts_per_page ) );
$empty_message  = isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : '';
$inner_blocks   = isset( $block->parsed_block['innerBlocks'] ) && is_array( $block->parsed_block['innerBlocks'] ) ? $block->parsed_block['innerBlocks'] : array();
$sections       = array();

if ( $team_id > 0 ) {
	$sections[] = array(
		'side'      => 'team',
		'team_id'   => $team_id,
		'team_name' => get_the_title( $team_id ),
	);
} else {
	$sides = 'both' === $team_side ? array( 'home', 'away' ) : array( $team_side );

	foreach ( $sides as $side ) {
		$related_team_id = (int) get_post_meta( $match_id, '_match_' . $side . '_team_id', true );
		$team_name       = sanitize_text_field( get_post_meta( $match_id, '_match_' . $side . '_team_name', true ) );

		if ( $related_team_id <= 0 || 'team' !== get_post_type( $related_team_id ) ) {
			continue;
		}

		$sections[] = array(
			'side'      => $side,
			'team_id'   => $related_team_id,
			'team_name' => '' !== $team_name ? $team_name : get_the_title( $related_team_id ),
		);
	}
}

$render_player_inner_blocks = function ( $player_id ) use ( $inner_blocks ) {
	if ( empty( $inner_blocks ) ) {
		return '';
	}

	$output = '';

	foreach ( $inner_blocks as $inner_block ) {
		if ( ! is_array( $inner_block ) ) {
			continue;
		}

		$block_instance = new WP_Block(
			$inner_block,
			array(
				'postId'   => $player_id,
				'postType' => 'player',
			)
		);

		$output .= $block_instance->render();
	}

	return $output;
};

$render_default_player_card = function ( $player_id ) use ( $show_images, $show_meta, $show_number, $show_position, $make_links ) {
	$number = sanitize_text_field( get_post_meta( $player_id, '_player_number', true ) );
	$position = sanitize_text_field( get_post_meta( $player_id, '_player_position', true ) );
	$meta   = array();

	if ( $show_number && '' !== $number ) {
		$meta[] = '#' . $number;
	}

	if ( $show_position && '' !== $position ) {
		$meta[] = $position;
	}

	$player_image = '';

	if ( $show_images ) {
		if ( has_post_thumbnail( $player_id ) ) {
			$player_image = get_the_post_thumbnail(
				$player_id,
				'thumbnail',
				array(
					'class' => 'wp-livescore-la-related-players__image',
					'alt'   => trim( wp_strip_all_tags( get_the_title( $player_id ) ) ),
				)
			);
		} elseif ( function_exists( 'wp_livescore_la_get_image_placeholder' ) ) {
			$player_image = wp_livescore_la_get_image_placeholder( 'wp-livescore-la-related-players__image wp-livescore-la-related-players__placeholder', get_the_title( $player_id ) );
		}
	}

	ob_start();
	?>
	<article class="wp-livescore-la-related-players__card">
		<?php echo wp_kses_post( $player_image ); ?>
		<div class="wp-livescore-la-related-players__content">
			<div class="wp-livescore-la-related-players__name">
				<?php if ( $make_links ) : ?>
					<a href="<?php echo esc_url( get_permalink( $player_id ) ); ?>"><?php echo esc_html( get_the_title( $player_id ) ); ?></a>
				<?php else : ?>
					<?php echo esc_html( get_the_title( $player_id ) ); ?>
				<?php endif; ?>
			</div>

			<?php if ( $show_meta && ! empty( $meta ) ) : ?>
				<p class="wp-livescore-la-related-players__meta"><?php echo esc_html( implode( ' | ', $meta ) ); ?></p>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return ob_get_clean();
};

$section_queries = array();

foreach ( $sections as $section ) {
	$players = new WP_Query(
		array(
			'post_type'              => 'player',
			'post_status'            => 'publish',
			'posts_per_page'         => $posts_per_page,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => '_player_team_id',
					'value' => (int) $section['team_id'],
				),
			),
		)
	);

	if ( $players->have_posts() ) {
		$section['query'] = $players;
		$section_queries[] = $section;
	} else {
		wp_reset_postdata();
	}
}

if ( empty( $section_queries ) ) {
	if ( '' === $empty_message ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'wp-livescore-la-related-players',
		)
	);
	?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<p class="wp-livescore-la-related-players__empty"><?php echo esc_html( $empty_message ); ?></p>
	</div>
	<?php
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-related-players wp-livescore-la-related-players--' . ( $team_id > 0 ? 'team' : $team_side ) . ( ! empty( $inner_blocks ) ? ' wp-livescore-la-related-players--query-loop' : '' ),
		'style' => '--wp-livescore-la-related-players-columns:' . esc_attr( $columns ) . ';',
	)
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php if ( $show_title && '' !== $title ) : ?>
		<h2 class="wp-livescore-la-related-players__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<div class="wp-livescore-la-related-players__sections">
		<?php foreach ( $section_queries as $section ) : ?>
			<section class="wp-livescore-la-related-players__section wp-livescore-la-related-players__section--<?php echo esc_attr( $section['side'] ); ?>">
				<?php if ( count( $section_queries ) > 1 ) : ?>
					<h3 class="wp-livescore-la-related-players__team-title"><?php echo esc_html( $section['team_name'] ); ?></h3>
				<?php endif; ?>

				<div class="wp-livescore-la-related-players__grid">
					<?php while ( $section['query']->have_posts() ) : ?>
						<?php $section['query']->the_post(); ?>
						<?php
						$player_id = get_the_ID();
						$player_output = $render_player_inner_blocks( $player_id );

						if ( '' === trim( $player_output ) ) {
							$player_output = $render_default_player_card( $player_id );
						}
						?>
						<div class="wp-livescore-la-related-players__item">
							<?php echo wp_kses_post( $player_output ); ?>
						</div>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				</div>
			</section>
		<?php endforeach; ?>
	</div>
</div>
