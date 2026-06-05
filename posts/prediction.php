<?php
/**
 * Prediction custom post type and metadata.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Prediction custom post type.
 */
function wp_livescore_la_register_prediction_post_type() {
	register_post_type(
		'prediction',
		array(
			'labels'              => array(
				'name'               => __( 'Predictions', 'wp-livescore-la' ),
				'singular_name'      => __( 'Prediction', 'wp-livescore-la' ),
				'menu_name'          => __( 'Predictions', 'wp-livescore-la' ),
				'add_new_item'       => __( 'Add New Prediction', 'wp-livescore-la' ),
				'edit_item'          => __( 'Edit Prediction', 'wp-livescore-la' ),
				'all_items'          => __( 'All Predictions', 'wp-livescore-la' ),
				'new_item'           => __( 'New Prediction', 'wp-livescore-la' ),
				'view_item'          => __( 'View Prediction', 'wp-livescore-la' ),
				'search_items'       => __( 'Search Predictions', 'wp-livescore-la' ),
				'not_found'          => __( 'No predictions found.', 'wp-livescore-la' ),
				'not_found_in_trash' => __( 'No predictions found in Trash.', 'wp-livescore-la' ),
			),
			'public'              => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wp-livescore-la-sports-manager',
			'show_in_rest'        => true,
			'has_archive'         => 'predictions',
			'rewrite'             => array(
				'slug'       => 'predictions',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-chart-area',
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_prediction_post_type' );

/**
 * Prediction meta keys and labels.
 *
 * @return array
 */
function wp_livescore_la_prediction_meta_fields() {
	return array(
		'_prediction_api_id'             => __( 'API Match ID', 'wp-livescore-la' ),
		'_prediction_match_id'           => __( 'Match ID', 'wp-livescore-la' ),
		'_prediction_match_api_id'       => __( 'Match API ID', 'wp-livescore-la' ),
		'_prediction_home_team_id'       => __( 'Home Team ID', 'wp-livescore-la' ),
		'_prediction_home_team_name'     => __( 'Home Team Name', 'wp-livescore-la' ),
		'_prediction_away_team_id'       => __( 'Away Team ID', 'wp-livescore-la' ),
		'_prediction_away_team_name'     => __( 'Away Team Name', 'wp-livescore-la' ),
		'_prediction_winner'             => __( 'Winner Prediction', 'wp-livescore-la' ),
		'_prediction_correct_score'      => __( 'Correct Score Pick', 'wp-livescore-la' ),
		'_prediction_betting_angle'      => __( 'Best Betting Angle', 'wp-livescore-la' ),
		'_prediction_home_win_percent'   => __( 'Home Win %', 'wp-livescore-la' ),
		'_prediction_away_win_percent'   => __( 'Away Win %', 'wp-livescore-la' ),
		'_prediction_draw_percent'       => __( 'Draw %', 'wp-livescore-la' ),
		'_prediction_news_headline'      => __( 'News Headline', 'wp-livescore-la' ),
		'_prediction_news_image_url'     => __( 'News Image URL', 'wp-livescore-la' ),
		'_prediction_image_url'          => __( 'Image URL', 'wp-livescore-la' ),
		'_prediction_seo_title'          => __( 'SEO Title', 'wp-livescore-la' ),
		'_prediction_seo_description'    => __( 'SEO Description', 'wp-livescore-la' ),
		'_prediction_canonical_slug'     => __( 'Canonical Slug', 'wp-livescore-la' ),
		'_prediction_status'             => __( 'API Status', 'wp-livescore-la' ),
		'_prediction_model'              => __( 'AI Model', 'wp-livescore-la' ),
		'_prediction_created_at'         => __( 'API Created At', 'wp-livescore-la' ),
		'_prediction_updated_at'         => __( 'API Updated At', 'wp-livescore-la' ),
		'_prediction_expected_lineups'   => __( 'Expected Lineups JSON', 'wp-livescore-la' ),
		'_prediction_faq'                => __( 'FAQ JSON', 'wp-livescore-la' ),
		'_prediction_h2h'                => __( 'H2H JSON', 'wp-livescore-la' ),
		'_prediction_recent_form'        => __( 'Recent Form JSON', 'wp-livescore-la' ),
		'_prediction_win_probability'    => __( 'Win Probability JSON', 'wp-livescore-la' ),
		'_prediction_match_info'         => __( 'Match Info JSON', 'wp-livescore-la' ),
		'_prediction_live_score_widget'  => __( 'Live Score Widget JSON', 'wp-livescore-la' ),
		'_prediction_seo_tags'           => __( 'SEO Tags JSON', 'wp-livescore-la' ),
		'_prediction_team_writeups'      => __( 'Team Writeups JSON', 'wp-livescore-la' ),
		'_prediction_raw_generated_json' => __( 'Generated Content JSON', 'wp-livescore-la' ),
	);
}

/**
 * Register Prediction meta fields.
 */
function wp_livescore_la_register_prediction_meta() {
	$integer_meta = array(
		'_prediction_match_id',
		'_prediction_home_team_id',
		'_prediction_away_team_id',
		'_prediction_home_win_percent',
		'_prediction_away_win_percent',
		'_prediction_draw_percent',
	);

	foreach ( wp_livescore_la_prediction_meta_fields() as $meta_key => $label ) {
		$is_integer = in_array( $meta_key, $integer_meta, true );

		register_post_meta(
			'prediction',
			$meta_key,
			array(
				'type'              => $is_integer ? 'integer' : 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $is_integer ? 'absint' : 'sanitize_textarea_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'wp_livescore_la_register_prediction_meta' );

/**
 * Use the plugin Prediction archive template when the theme does not provide one.
 *
 * @param string $template Current template path.
 * @return string
 */
function wp_livescore_la_prediction_archive_template( $template ) {
	if ( ! is_post_type_archive( 'prediction' ) ) {
		return $template;
	}

	if ( function_exists( 'wp_livescore_la_get_astra_site_builder_template' ) ) {
		$astra_template = wp_livescore_la_get_astra_site_builder_template( 'Predictions' );
		if ( '' !== $astra_template ) {
			return $astra_template;
		}
	}

	if ( locate_template( array( 'archive-prediction.php', 'archive-predictions.php' ) ) ) {
		return $template;
	}

	$plugin_template = WP_LIVESCORE_LA_DIR . 'templates/archive-predictions.php';

	return file_exists( $plugin_template ) ? $plugin_template : $template;
}
add_filter( 'template_include', 'wp_livescore_la_prediction_archive_template' );

/**
 * Build Prediction archive meta filters from URL parameters.
 *
 * @return array
 */
function wp_livescore_la_get_prediction_url_filter_meta_query() {
	$filters = array();

	if ( isset( $_GET['prediction_country'] ) && '' !== $_GET['prediction_country'] ) {
		$country_slug = sanitize_title( wp_unslash( $_GET['prediction_country'] ) );
		$team_ids     = get_posts(
			array(
				'post_type'      => 'team',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => '_team_country_slug',
				'meta_value'     => $country_slug,
			)
		);

		$team_ids = array_values( array_unique( array_map( 'absint', $team_ids ) ) );
		$filters[] = ! empty( $team_ids )
			? array(
				'relation' => 'OR',
				array(
					'key'     => '_prediction_home_team_id',
					'value'   => $team_ids,
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => '_prediction_away_team_id',
					'value'   => $team_ids,
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				),
			)
			: array(
				'key'   => '_prediction_home_team_id',
				'value' => 0,
			);
	}

	if ( isset( $_GET['prediction_team'] ) && '' !== trim( (string) $_GET['prediction_team'] ) ) {
		$team_search = sanitize_text_field( wp_unslash( $_GET['prediction_team'] ) );
		$team_ids    = get_posts(
			array(
				'post_type'      => 'team',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				's'              => $team_search,
			)
		);

		$team_filter = array(
			'relation' => 'OR',
			array(
				'key'     => '_prediction_home_team_name',
				'value'   => $team_search,
				'compare' => 'LIKE',
			),
			array(
				'key'     => '_prediction_away_team_name',
				'value'   => $team_search,
				'compare' => 'LIKE',
			),
		);

		$team_ids = array_values( array_unique( array_map( 'absint', $team_ids ) ) );
		if ( ! empty( $team_ids ) ) {
			$team_filter[] = array(
				'key'     => '_prediction_home_team_id',
				'value'   => $team_ids,
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			);
			$team_filter[] = array(
				'key'     => '_prediction_away_team_id',
				'value'   => $team_ids,
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			);
		}

		$filters[] = $team_filter;
	}

	return $filters;
}

/**
 * Apply frontend Prediction archive filters.
 *
 * @param WP_Query $query Query object.
 */
function wp_livescore_la_filter_frontend_prediction_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$is_prediction_query = is_post_type_archive( 'prediction' ) || 'prediction' === $query->get( 'post_type' );
	if ( ! $is_prediction_query ) {
		return;
	}

	$filters = wp_livescore_la_get_prediction_url_filter_meta_query();
	if ( empty( $filters ) ) {
		return;
	}

	$meta_query = (array) $query->get( 'meta_query' );
	$meta_query[] = count( $filters ) > 1 ? array_merge( array( 'relation' => 'AND' ), $filters ) : $filters[0];
	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'wp_livescore_la_filter_frontend_prediction_query' );

/**
 * Add the Prediction Details meta box.
 */
function wp_livescore_la_add_prediction_meta_box() {
	add_meta_box(
		'wp-livescore-la-prediction-details',
		__( 'Prediction Details', 'wp-livescore-la' ),
		'wp_livescore_la_render_prediction_meta_box',
		'prediction',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_prediction', 'wp_livescore_la_add_prediction_meta_box' );

/**
 * Render Prediction Details meta box.
 *
 * @param WP_Post $post Prediction post.
 */
function wp_livescore_la_render_prediction_meta_box( $post ) {
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<?php foreach ( wp_livescore_la_prediction_meta_fields() as $meta_key => $label ) : ?>
				<?php $value = get_post_meta( $post->ID, $meta_key, true ); ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td>
						<?php if ( strlen( (string) $value ) > 120 || false !== strpos( (string) $value, "\n" ) ) : ?>
							<textarea class="large-text" rows="4" readonly><?php echo esc_textarea( $value ); ?></textarea>
						<?php else : ?>
							<input class="regular-text" type="text" readonly value="<?php echo esc_attr( $value ); ?>" />
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
