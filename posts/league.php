<?php
/**
 * League custom post type and admin post UI.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return all League meta fields and their admin labels.
 *
 * @return array
 */
function wp_livescore_la_league_meta_fields() {
	return array(
		'country'          => 'Country',
		'sports'           => 'Sports',
		'api_id'           => 'API Id',
		'api_source'       => 'API Source',
		'sportscore_slug' => 'SportScore Slug',
		'strCurrentSeason' => 'Current Season',
		'intFormedYear'    => 'Formed Year',
		'dateFirstEvent'   => 'First Event',
		'strWebsite'       => 'Website',
		'strFacebook'      => 'Facebook',
		'strInstagram'     => 'Instagram',
		'strTwitter'       => 'Twitter',
		'strYoutube'       => 'Youtube',
		'strRSS'           => 'RSS',
		'strBadge'         => 'Badge',
		'strBanner'        => 'Banner',
	);
}

/**
 * Return the sanitizer/input type for a League meta field.
 *
 * @param string $field League meta field key.
 * @return string
 */
function wp_livescore_la_get_league_meta_field_type( $field ) {
	return in_array( $field, array( 'strWebsite', 'strFacebook', 'strInstagram', 'strTwitter', 'strYoutube', 'strRSS', 'strBadge', 'strBanner' ), true ) ? 'url' : 'text';
}

/**
 * Register the League custom post type.
 */
function wp_livescore_la_register_league_post_type() {
	register_post_type(
		'league',
		array(
			'labels'              => array(
				'name'               => __( 'Leagues', 'wp-livescore-la' ),
				'singular_name'      => __( 'League', 'wp-livescore-la' ),
				'menu_name'          => __( 'Leagues', 'wp-livescore-la' ),
				'add_new'            => __( 'Add New', 'wp-livescore-la' ),
				'add_new_item'       => __( 'Add New League', 'wp-livescore-la' ),
				'edit_item'          => __( 'Edit League', 'wp-livescore-la' ),
				'new_item'           => __( 'New League', 'wp-livescore-la' ),
				'view_item'          => __( 'View League', 'wp-livescore-la' ),
				'search_items'       => __( 'Search Leagues', 'wp-livescore-la' ),
				'not_found'          => __( 'No leagues found.', 'wp-livescore-la' ),
				'not_found_in_trash' => __( 'No leagues found in Trash.', 'wp-livescore-la' ),
			),
			'public'              => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wp-livescore-la-sports-manager',
			'show_in_rest'        => true,
			'has_archive'         => 'league',
			'rewrite'             => array(
				'slug'       => 'league',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-groups',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_league_post_type' );

/**
 * Register Seasons as a viewable League taxonomy.
 */
function wp_livescore_la_register_league_season_taxonomy() {
	register_taxonomy(
		'league_season',
		array( 'league' ),
		array(
			'labels'            => array(
				'name'                       => __( 'Seasons', 'wp-livescore-la' ),
				'singular_name'              => __( 'Season', 'wp-livescore-la' ),
				'menu_name'                  => __( 'Seasons', 'wp-livescore-la' ),
				'all_items'                  => __( 'All Seasons', 'wp-livescore-la' ),
				'edit_item'                  => __( 'Edit Season', 'wp-livescore-la' ),
				'view_item'                  => __( 'View Season', 'wp-livescore-la' ),
				'update_item'                => __( 'Update Season', 'wp-livescore-la' ),
				'add_new_item'               => __( 'Add New Season', 'wp-livescore-la' ),
				'new_item_name'              => __( 'New Season Name', 'wp-livescore-la' ),
				'search_items'               => __( 'Search Seasons', 'wp-livescore-la' ),
				'popular_items'              => __( 'Popular Seasons', 'wp-livescore-la' ),
				'separate_items_with_commas' => __( 'Separate seasons with commas', 'wp-livescore-la' ),
				'add_or_remove_items'        => __( 'Add or remove seasons', 'wp-livescore-la' ),
				'choose_from_most_used'      => __( 'Choose from the most used seasons', 'wp-livescore-la' ),
				'not_found'                  => __( 'No seasons found.', 'wp-livescore-la' ),
			),
			'public'            => true,
			'publicly_queryable'=> true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'league-season',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_league_season_taxonomy' );

/**
 * Create and assign a Season term to a League.
 *
 * @param int    $post_id     League post ID.
 * @param string $season      Season name.
 * @param bool   $set_current Whether to save this as the current season.
 * @param bool   $append      Whether to append instead of replacing Season terms.
 * @return int Season term ID, or 0 on failure.
 */
function wp_livescore_la_sync_league_season_term( $post_id, $season, $set_current = true, $append = false ) {
	$season = sanitize_text_field( $season );

	if ( '' === $season || ! taxonomy_exists( 'league_season' ) ) {
		return 0;
	}

	$league_name = get_the_title( $post_id );
	$term_name   = '' !== $league_name ? $season . ' - ' . sanitize_text_field( $league_name ) : $season;
	$season_slug = sanitize_title( $term_name );

	if ( '' === $season_slug ) {
		return 0;
	}

	$term = get_term_by( 'slug', $season_slug, 'league_season' );

	if ( ! $term instanceof WP_Term ) {
		$inserted = wp_insert_term(
			$term_name,
			'league_season',
			array(
				'slug' => $season_slug,
			)
		);

		if ( is_wp_error( $inserted ) ) {
			$existing_id = (int) $inserted->get_error_data( 'term_exists' );
			$term_id     = $existing_id > 0 ? $existing_id : 0;
		} else {
			$term_id = isset( $inserted['term_id'] ) ? (int) $inserted['term_id'] : 0;
		}
	} else {
		$term_id = (int) $term->term_id;
	}

	if ( $term_id <= 0 ) {
		return 0;
	}

	wp_set_object_terms( $post_id, array( $term_id ), 'league_season', $append );

	if ( $set_current ) {
		update_post_meta( $post_id, '_league_current_season_name', $term_name );
		update_post_meta( $post_id, '_league_current_season_slug', $season_slug );
		update_post_meta( $post_id, '_league_current_season_term_id', $term_id );
	}

	return $term_id;
}

/**
 * Register League meta fields for REST, sanitization, and editor support.
 */
function wp_livescore_la_register_league_meta() {
	foreach ( wp_livescore_la_league_meta_fields() as $field => $label ) {
		$type              = wp_livescore_la_get_league_meta_field_type( $field );
		$sanitize_callback = 'url' === $type ? 'esc_url_raw' : 'sanitize_text_field';

		if ( 'sportscore_slug' === $field ) {
			$sanitize_callback = 'wp_livescore_la_sanitize_sportscore_slug';
		}

		register_post_meta(
			'league',
			WP_LIVESCORE_LA_META_PREFIX . $field,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $sanitize_callback,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	$linked_meta_fields = array(
		'_league_sport_id',
		'_league_sport_name',
		'_league_sport_slug',
		'_league_country_id',
		'_league_country_name',
		'_league_country_slug',
		'_league_country_code',
		'_league_continent',
	);

	foreach ( $linked_meta_fields as $meta_key ) {
		$is_id_field = in_array( $meta_key, array( '_league_sport_id', '_league_country_id' ), true );

		register_post_meta(
			'league',
			$meta_key,
			array(
				'type'              => $is_id_field ? 'integer' : 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $is_id_field ? 'absint' : 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		'league',
		WP_LIVESCORE_LA_META_PREFIX . 'header_image_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_league_meta' );

/**
 * Keep League visible to Yoast SEO features when Yoast is active.
 *
 * @param array $post_types Accessible post types.
 * @return array
 */
function wp_livescore_la_yoast_accessible_post_types( $post_types ) {
	$post_types['league'] = 'league';
	$post_types['prediction'] = 'prediction';
	return $post_types;
}
add_filter( 'wpseo_accessible_post_types', 'wp_livescore_la_yoast_accessible_post_types' );

/**
 * Make sure Yoast indexables include League posts by default.
 *
 * @param array $post_types Included post types.
 * @return array
 */
function wp_livescore_la_yoast_include_indexables( $post_types ) {
	foreach ( array( 'league', 'prediction' ) as $post_type ) {
		if ( ! in_array( $post_type, $post_types, true ) ) {
			$post_types[] = $post_type;
		}
	}

	return $post_types;
}
add_filter( 'wpseo_indexable_forced_included_post_types', 'wp_livescore_la_yoast_include_indexables' );

/**
 * Allow Yoast editor features on League posts unless explicitly disabled.
 *
 * @param bool|null $enabled Current setting.
 * @return bool
 */
function wp_livescore_la_yoast_enable_league_editor_features( $enabled ) {
	return false === $enabled ? false : true;
}
add_filter( 'wpseo_enable_editor_features_league', 'wp_livescore_la_yoast_enable_league_editor_features' );
add_filter( 'wpseo_enable_editor_features_prediction', 'wp_livescore_la_yoast_enable_league_editor_features' );

/**
 * Keep Yoast League breadcrumbs on the current League archive URL.
 *
 * Yoast can retain an older post type archive URL in its indexable breadcrumb
 * ancestor after the CPT rewrite changes.
 *
 * @param array $crumbs Yoast breadcrumb crumbs.
 * @return array
 */
function wp_livescore_la_fix_yoast_league_breadcrumb_archive_url( $crumbs ) {
	if ( ! is_singular( 'league' ) || ! is_array( $crumbs ) ) {
		return $crumbs;
	}

	$archive_url = get_post_type_archive_link( 'league' );

	if ( ! $archive_url ) {
		return $crumbs;
	}

	foreach ( $crumbs as $index => $crumb ) {
		if ( isset( $crumb['ptarchive'] ) && 'league' === $crumb['ptarchive'] ) {
			$crumbs[ $index ]['url'] = $archive_url;
		}
	}

	return $crumbs;
}
add_filter( 'wpseo_breadcrumb_links', 'wp_livescore_la_fix_yoast_league_breadcrumb_archive_url' );

/**
 * Add League Details meta box.
 */
function wp_livescore_la_add_league_meta_box() {
	add_meta_box(
		'wp-livescore-la-league-details',
		__( 'League Details', 'wp-livescore-la' ),
		'wp_livescore_la_render_league_meta_box',
		'league',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_league', 'wp_livescore_la_add_league_meta_box' );

/**
 * Render League Details meta box.
 *
 * @param WP_Post $post Current post.
 */
function wp_livescore_la_render_league_meta_box( $post ) {
	wp_nonce_field( 'wp_livescore_la_save_league_meta', 'wp_livescore_la_league_meta_nonce' );
	$header_image_id  = (int) get_post_meta( $post->ID, WP_LIVESCORE_LA_META_PREFIX . 'header_image_id', true );
	$header_image_url = $header_image_id > 0 ? wp_get_attachment_image_url( $header_image_id, 'medium' ) : '';
	$header_image_full_url = $header_image_id > 0 ? wp_get_attachment_image_url( $header_image_id, 'full' ) : '';
	?>
	<table class="form-table wp-livescore-la-meta-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label for="wp_livescore_la_header_image_id"><?php esc_html_e( 'Header Image', 'wp-livescore-la' ); ?></label>
				</th>
				<td>
					<div class="wp-livescore-la-header-image">
						<input type="hidden" id="wp_livescore_la_header_image_id" name="wp_livescore_la_header_image_id" value="<?php echo esc_attr( $header_image_id ); ?>" />
						<div class="wp-livescore-la-header-image__preview">
							<?php if ( '' !== $header_image_url ) : ?>
								<img src="<?php echo esc_url( $header_image_url ); ?>" alt="" />
							<?php endif; ?>
						</div>
						<p class="wp-livescore-la-header-image__details">
							<span class="wp-livescore-la-header-image__id">
								<?php
								printf(
									/* translators: %d: attachment ID. */
									esc_html__( 'Attachment ID: %d', 'wp-livescore-la' ),
									$header_image_id
								);
								?>
							</span>
							<?php if ( '' !== $header_image_full_url ) : ?>
								<a class="wp-livescore-la-header-image__link" href="<?php echo esc_url( $header_image_full_url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'View image', 'wp-livescore-la' ); ?>
								</a>
							<?php endif; ?>
						</p>
						<p>
							<button type="button" class="button wp-livescore-la-upload-header-image"><?php esc_html_e( 'Select Header Image', 'wp-livescore-la' ); ?></button>
							<button type="button" class="button wp-livescore-la-remove-header-image" <?php disabled( $header_image_id <= 0 ); ?>><?php esc_html_e( 'Remove Header Image', 'wp-livescore-la' ); ?></button>
						</p>
					</div>
				</td>
			</tr>
			<?php foreach ( wp_livescore_la_league_meta_fields() as $field => $label ) : ?>
				<?php
				$meta_key = WP_LIVESCORE_LA_META_PREFIX . $field;
				$type     = wp_livescore_la_get_league_meta_field_type( $field );
				$value    = get_post_meta( $post->ID, $meta_key, true );
				?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $label ); ?></label>
					</th>
					<td>
						<?php if ( 'sports' === $field ) : ?>
							<?php wp_livescore_la_render_league_sport_select( $post->ID, $value ); ?>
						<?php elseif ( 'country' === $field ) : ?>
							<?php wp_livescore_la_render_league_country_select( $post->ID, $value ); ?>
						<?php else : ?>
							<input
								type="<?php echo 'url' === $type ? 'url' : 'text'; ?>"
								id="<?php echo esc_attr( $meta_key ); ?>"
								name="wp_livescore_la_meta[<?php echo esc_attr( $field ); ?>]"
								value="<?php echo esc_attr( $value ); ?>"
								class="regular-text"
							/>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Save League meta fields securely.
 *
 * @param int $post_id Post ID.
 */
function wp_livescore_la_save_league_meta( $post_id ) {
	if ( ! isset( $_POST['wp_livescore_la_league_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_league_meta_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'wp_livescore_la_save_league_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$posted_meta = isset( $_POST['wp_livescore_la_meta'] ) && is_array( $_POST['wp_livescore_la_meta'] ) ? wp_unslash( $_POST['wp_livescore_la_meta'] ) : array();

	foreach ( wp_livescore_la_league_meta_fields() as $field => $label ) {
		if ( in_array( $field, array( 'country', 'sports' ), true ) ) {
			continue;
		}

		$type  = wp_livescore_la_get_league_meta_field_type( $field );
		$value = isset( $posted_meta[ $field ] ) ? $posted_meta[ $field ] : '';
		$value = 'url' === $type ? esc_url_raw( $value ) : sanitize_text_field( $value );

		if ( 'sportscore_slug' === $field ) {
			$value = wp_livescore_la_sanitize_sportscore_slug( $value );
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . $field );
		} else {
			update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . $field, $value );
		}
	}

	if ( isset( $posted_meta['strCurrentSeason'] ) && '' !== $posted_meta['strCurrentSeason'] ) {
		wp_livescore_la_sync_league_season_term( $post_id, $posted_meta['strCurrentSeason'] );
	}

	$header_image_id = isset( $_POST['wp_livescore_la_header_image_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_header_image_id'] ) ) : 0;
	if ( $header_image_id > 0 ) {
		update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'header_image_id', $header_image_id );
	} else {
		delete_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'header_image_id' );
	}

	$sport_id = isset( $_POST['wp_livescore_la_sport_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_sport_id'] ) ) : 0;
	$fallback_sport = isset( $_POST['wp_livescore_la_unmatched_sports_value'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_unmatched_sports_value'] ) ) : '';
	wp_livescore_la_sync_league_sport_meta( $post_id, $sport_id, $fallback_sport );

	$country_id = isset( $_POST['wp_livescore_la_country_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_country_id'] ) ) : 0;
	$fallback_country = isset( $_POST['wp_livescore_la_unmatched_country_value'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_unmatched_country_value'] ) ) : '';
	wp_livescore_la_sync_league_country_meta( $post_id, $country_id, $fallback_country );
}
add_action( 'save_post_league', 'wp_livescore_la_save_league_meta' );

/**
 * Render the League Sports dropdown from Sports Manager.
 *
 * @param int    $league_id      League post ID.
 * @param string $existing_value Existing sports text meta.
 */
function wp_livescore_la_render_league_sport_select( $league_id, $existing_value ) {
	$linked_sport_id = (int) get_post_meta( $league_id, '_league_sport_id', true );

	if ( $linked_sport_id <= 0 && '' !== $existing_value ) {
		$linked_sport_id = wp_livescore_la_find_sport_id_by_value( $existing_value );
	}

	$sports = wp_livescore_la_get_sports_manager_items( true );

	if ( $linked_sport_id > 0 ) {
		$linked_sport = get_term( $linked_sport_id, 'sport' );
		if ( $linked_sport instanceof WP_Term && ! is_wp_error( $linked_sport ) && ! in_array( $linked_sport_id, wp_list_pluck( $sports, 'term_id' ), true ) ) {
			$sports[] = $linked_sport;
		}
	}

	$has_match = $linked_sport_id > 0;
	?>
	<select id="<?php echo esc_attr( WP_LIVESCORE_LA_META_PREFIX . 'sports' ); ?>" name="wp_livescore_la_sport_id" class="regular-text">
		<option value=""><?php esc_html_e( 'Select sport', 'wp-livescore-la' ); ?></option>
		<?php foreach ( $sports as $sport ) : ?>
			<?php
			$status = get_term_meta( $sport->term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_status', true );
			$label  = $sport->name;
			if ( 'inactive' === $status ) {
				$label .= ' (' . __( 'Inactive', 'wp-livescore-la' ) . ')';
			}
			?>
			<option value="<?php echo esc_attr( $sport->term_id ); ?>" <?php selected( $linked_sport_id, $sport->term_id ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<input type="hidden" name="wp_livescore_la_unmatched_sports_value" value="<?php echo esc_attr( $has_match ? '' : $existing_value ); ?>" />
	<?php if ( ! $has_match && '' !== $existing_value ) : ?>
		<p class="description">
			<?php
			printf(
				/* translators: %s: existing sports value. */
				esc_html__( 'Current unmatched value will be preserved: %s', 'wp-livescore-la' ),
				esc_html( $existing_value )
			);
			?>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Render the League Country dropdown from Countries Manager.
 *
 * @param int    $league_id      League post ID.
 * @param string $existing_value Existing country text meta.
 */
function wp_livescore_la_render_league_country_select( $league_id, $existing_value ) {
	$linked_country_id = (int) get_post_meta( $league_id, '_league_country_id', true );

	if ( $linked_country_id <= 0 && '' !== $existing_value ) {
		$linked_country_id = wp_livescore_la_find_country_id_by_value( $existing_value );
	}

	$countries = wp_livescore_la_get_countries_manager_items( true );

	if ( $linked_country_id > 0 ) {
		$linked_country = get_post( $linked_country_id );
		if ( $linked_country instanceof WP_Post && ! in_array( $linked_country_id, wp_list_pluck( $countries, 'ID' ), true ) ) {
			$countries[] = $linked_country;
		}
	}

	$has_match = $linked_country_id > 0;
	?>
	<select id="<?php echo esc_attr( WP_LIVESCORE_LA_META_PREFIX . 'country' ); ?>" name="wp_livescore_la_country_id" class="regular-text">
		<option value=""><?php esc_html_e( 'Select country', 'wp-livescore-la' ); ?></option>
		<?php foreach ( $countries as $country ) : ?>
			<?php
			$status = get_post_meta( $country->ID, WP_LIVESCORE_LA_META_PREFIX . 'country_status', true );
			$code   = get_post_meta( $country->ID, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true );
			$label  = get_the_title( $country );
			if ( '' !== $code ) {
				$label .= ' (' . $code . ')';
			}
			if ( 'inactive' === $status ) {
				$label .= ' (' . __( 'Inactive', 'wp-livescore-la' ) . ')';
			}
			?>
			<option value="<?php echo esc_attr( $country->ID ); ?>" <?php selected( $linked_country_id, $country->ID ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<input type="hidden" name="wp_livescore_la_unmatched_country_value" value="<?php echo esc_attr( $has_match ? '' : $existing_value ); ?>" />
	<?php if ( ! $has_match && '' !== $existing_value ) : ?>
		<p class="description">
			<?php
			printf(
				/* translators: %s: existing country value. */
				esc_html__( 'Current unmatched value will be preserved: %s', 'wp-livescore-la' ),
				esc_html( $existing_value )
			);
			?>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Get the linked normal WordPress post tag ID for a League.
 *
 * @param int $league_id League post ID.
 * @return int
 */
function get_league_linked_tag_id( $league_id ) {
	return (int) get_post_meta( $league_id, '_linked_post_tag_id', true );
}

/**
 * Sync a League custom post to a normal WordPress post tag.
 *
 * This intentionally uses the built-in post_tag taxonomy and does not register
 * a custom taxonomy.
 *
 * @param int $league_id League post ID.
 * @return int Linked tag ID, or 0 on failure.
 */
function sync_league_to_post_tag( $league_id ) {
	$league = get_post( $league_id );

	if ( ! $league instanceof WP_Post || 'league' !== $league->post_type ) {
		return 0;
	}

	if ( in_array( $league->post_status, array( 'auto-draft', 'trash' ), true ) ) {
		return 0;
	}

	$tag_name = sanitize_text_field( $league->post_title );
	$tag_slug = '' !== $league->post_name ? sanitize_title( $league->post_name ) : sanitize_title( $tag_name );

	if ( '' === $tag_name || '' === $tag_slug ) {
		return 0;
	}

	$linked_tag_id = get_league_linked_tag_id( $league_id );
	$linked_tag    = $linked_tag_id > 0 ? get_term( $linked_tag_id, 'post_tag' ) : null;
	$slug_match    = get_term_by( 'slug', $tag_slug, 'post_tag' );

	if ( $linked_tag instanceof WP_Term && ! is_wp_error( $linked_tag ) ) {
		if ( $slug_match instanceof WP_Term && (int) $slug_match->term_id !== (int) $linked_tag->term_id ) {
			wp_update_term(
				(int) $slug_match->term_id,
				'post_tag',
				array(
					'name' => $tag_name,
					'slug' => $tag_slug,
				)
			);
			update_post_meta( $league_id, '_linked_post_tag_id', (int) $slug_match->term_id );
			return (int) $slug_match->term_id;
		}

		$updated = wp_update_term(
			(int) $linked_tag->term_id,
			'post_tag',
			array(
				'name' => $tag_name,
				'slug' => $tag_slug,
			)
		);

		if ( ! is_wp_error( $updated ) && isset( $updated['term_id'] ) ) {
			update_post_meta( $league_id, '_linked_post_tag_id', (int) $updated['term_id'] );
			return (int) $updated['term_id'];
		}
	}

	if ( $slug_match instanceof WP_Term ) {
		wp_update_term(
			(int) $slug_match->term_id,
			'post_tag',
			array(
				'name' => $tag_name,
				'slug' => $tag_slug,
			)
		);
		update_post_meta( $league_id, '_linked_post_tag_id', (int) $slug_match->term_id );
		return (int) $slug_match->term_id;
	}

	$name_match = term_exists( $tag_name, 'post_tag' );
	$name_match_id = is_array( $name_match ) && ! empty( $name_match['term_id'] ) ? (int) $name_match['term_id'] : (int) $name_match;
	if ( $name_match_id > 0 ) {
		$updated = wp_update_term(
			$name_match_id,
			'post_tag',
			array(
				'name' => $tag_name,
				'slug' => $tag_slug,
			)
		);

		if ( ! is_wp_error( $updated ) && isset( $updated['term_id'] ) ) {
			update_post_meta( $league_id, '_linked_post_tag_id', (int) $updated['term_id'] );
			return (int) $updated['term_id'];
		}
	}

	$inserted = wp_insert_term(
		$tag_name,
		'post_tag',
		array(
			'slug' => $tag_slug,
		)
	);

	if ( is_wp_error( $inserted ) ) {
		$existing_id = (int) $inserted->get_error_data( 'term_exists' );
		if ( $existing_id > 0 ) {
			update_post_meta( $league_id, '_linked_post_tag_id', $existing_id );
			return $existing_id;
		}

		return 0;
	}

	if ( isset( $inserted['term_id'] ) ) {
		update_post_meta( $league_id, '_linked_post_tag_id', (int) $inserted['term_id'] );
		return (int) $inserted['term_id'];
	}

	return 0;
}

/**
 * Sync the linked tag whenever a League is saved.
 *
 * @param int $post_id League post ID.
 */
function wp_livescore_la_sync_league_tag_on_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	sync_league_to_post_tag( $post_id );
}
add_action( 'save_post_league', 'wp_livescore_la_sync_league_tag_on_save', 20 );

/**
 * Get blog posts related to a League through its linked normal WordPress tag.
 *
 * @param int $league_id League post ID.
 * @return WP_Query
 */
function get_related_posts_by_league_tag( $league_id ) {
	$tag_id = get_league_linked_tag_id( $league_id );

	if ( $tag_id <= 0 ) {
		$tag_id = sync_league_to_post_tag( $league_id );
	}

	if ( $tag_id <= 0 ) {
		return new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'post__in'       => array( 0 ),
			)
		);
	}

	return new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			'ignore_sticky_posts' => true,
			'tag_id'              => $tag_id,
		)
	);
}

/**
 * Render selected sport details for a League single page.
 *
 * @param int $league_id League post ID.
 * @return string
 */
function wp_livescore_la_render_league_selected_sport( $league_id ) {
	$sport_name = get_post_meta( $league_id, '_league_sport_name', true );
	$sport_slug = get_post_meta( $league_id, '_league_sport_slug', true );

	if ( '' === $sport_name ) {
		$sport_name = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'sports', true );
		$sport_slug = sanitize_title( $sport_name );
	}

	if ( '' === $sport_name ) {
		return '';
	}

	return sprintf(
		'<p class="wp-livescore-la-league-sport" data-sport-slug="%1$s"><strong>%2$s</strong> %3$s</p>',
		esc_attr( $sport_slug ),
		esc_html__( 'Sport:', 'wp-livescore-la' ),
		esc_html( $sport_name )
	);
}

/**
 * Render selected country details for a League single page.
 *
 * @param int $league_id League post ID.
 * @return string
 */
function wp_livescore_la_render_league_selected_country( $league_id ) {
	$country_name = get_post_meta( $league_id, '_league_country_name', true );
	$country_slug = get_post_meta( $league_id, '_league_country_slug', true );
	$continent    = get_post_meta( $league_id, '_league_continent', true );

	if ( '' === $country_name ) {
		$country_name = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'country', true );
		$country_slug = sanitize_title( $country_name );
	}

	if ( '' === $country_name ) {
		return '';
	}

	$output = sprintf(
		'<p class="wp-livescore-la-league-country" data-country-slug="%1$s"><strong>%2$s</strong> %3$s</p>',
		esc_attr( $country_slug ),
		esc_html__( 'Country:', 'wp-livescore-la' ),
		esc_html( $country_name )
	);

	if ( '' !== $continent ) {
		$output .= sprintf(
			'<p class="wp-livescore-la-league-continent" data-continent="%1$s"><strong>%2$s</strong> %3$s</p>',
			esc_attr( sanitize_title( $continent ) ),
			esc_html__( 'Continent:', 'wp-livescore-la' ),
			esc_html( $continent )
		);
	}

	return $output;
}

/**
 * Render selected season details for a League single page.
 *
 * @param int $league_id League post ID.
 * @return string
 */
function wp_livescore_la_render_league_selected_season( $league_id ) {
	$season_name = get_post_meta( $league_id, '_league_current_season_name', true );
	$season_slug = get_post_meta( $league_id, '_league_current_season_slug', true );

	if ( '' === $season_name ) {
		$terms = get_the_terms( $league_id, 'league_season' );
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			usort(
				$terms,
				function ( $a, $b ) {
					return strnatcasecmp( $b->name, $a->name );
				}
			);
			$season_name = $terms[0]->name;
			$season_slug = $terms[0]->slug;
		}
	}

	if ( '' === $season_name ) {
		$season_name = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'strCurrentSeason', true );
		$season_slug = sanitize_title( $season_name );
	}

	if ( '' === $season_name ) {
		return '';
	}

	return sprintf(
		'<p class="wp-livescore-la-league-season" data-season-slug="%1$s"><strong>%2$s</strong> %3$s</p>',
		esc_attr( $season_slug ),
		esc_html__( 'Season:', 'wp-livescore-la' ),
		esc_html( $season_name )
	);
}

/**
 * Append selected League details on single League pages.
 *
 * @param string $content Main content.
 * @return string
 */
function wp_livescore_la_append_league_selected_sport( $content ) {
	if ( ! is_singular( 'league' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$league_id = get_the_ID();

	return $content . wp_livescore_la_render_league_selected_sport( $league_id ) . wp_livescore_la_render_league_selected_country( $league_id ) . wp_livescore_la_render_league_selected_season( $league_id );
}
add_filter( 'the_content', 'wp_livescore_la_append_league_selected_sport' );

/**
 * Add admin list columns for League metadata.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function wp_livescore_la_league_columns( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $label ) {
		$new_columns[ $key ] = $label;

		if ( 'title' === $key ) {
			$new_columns['wp_livescore_la_country'] = __( 'Country', 'wp-livescore-la' );
			$new_columns['wp_livescore_la_sports']  = __( 'Sports', 'wp-livescore-la' );
			$new_columns['wp_livescore_la_api_id']  = __( 'API ID', 'wp-livescore-la' );
			$new_columns['wp_livescore_la_season']  = __( 'Current Season', 'wp-livescore-la' );
		}
	}

	return $new_columns;
}
add_filter( 'manage_league_posts_columns', 'wp_livescore_la_league_columns' );

/**
 * Render admin list column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function wp_livescore_la_render_league_columns( $column, $post_id ) {
	$map = array(
		'wp_livescore_la_country' => 'country',
		'wp_livescore_la_sports'  => 'sports',
		'wp_livescore_la_api_id'  => 'api_id',
		'wp_livescore_la_season'  => 'strCurrentSeason',
	);

	if ( isset( $map[ $column ] ) ) {
		echo esc_html( get_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . $map[ $column ], true ) );
	}
}
add_action( 'manage_league_posts_custom_column', 'wp_livescore_la_render_league_columns', 10, 2 );

/**
 * Add country, sports, and api_id admin filters for Leagues.
 *
 * @param string $post_type Current post type.
 */
function wp_livescore_la_league_admin_filters( $post_type ) {
	if ( 'league' !== $post_type ) {
		return;
	}

	$filters = array(
		'country' => __( 'All countries', 'wp-livescore-la' ),
		'sports'  => __( 'All sports', 'wp-livescore-la' ),
		'api_id'  => __( 'All API IDs', 'wp-livescore-la' ),
	);

	foreach ( $filters as $field => $label ) {
		$selected = isset( $_GET[ 'wp_livescore_la_' . $field ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'wp_livescore_la_' . $field ] ) ) : '';
		$values   = wp_livescore_la_get_distinct_meta_values( WP_LIVESCORE_LA_META_PREFIX . $field );
		?>
		<select name="<?php echo esc_attr( 'wp_livescore_la_' . $field ); ?>">
			<option value=""><?php echo esc_html( $label ); ?></option>
			<?php foreach ( $values as $value ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected, $value ); ?>>
					<?php echo esc_html( $value ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}
}
add_action( 'restrict_manage_posts', 'wp_livescore_la_league_admin_filters' );

/**
 * Get distinct non-empty meta values for filter dropdowns.
 *
 * @param string $meta_key Meta key.
 * @return array
 */
function wp_livescore_la_get_distinct_meta_values( $meta_key ) {
	global $wpdb;

	$values = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != '' ORDER BY meta_value ASC LIMIT 200",
			$meta_key
		)
	);

	return array_map( 'sanitize_text_field', is_array( $values ) ? $values : array() );
}

/**
 * Apply League admin filters.
 *
 * @param WP_Query $query Query object.
 */
function wp_livescore_la_filter_league_admin_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'league' !== $query->get( 'post_type' ) ) {
		return;
	}

	$meta_query = array();
	foreach ( array( 'country', 'sports', 'api_id' ) as $field ) {
		$query_key = 'wp_livescore_la_' . $field;
		if ( isset( $_GET[ $query_key ] ) && '' !== $_GET[ $query_key ] ) {
			$meta_query[] = array(
				'key'   => WP_LIVESCORE_LA_META_PREFIX . $field,
				'value' => sanitize_text_field( wp_unslash( $_GET[ $query_key ] ) ),
			);
		}
	}

	if ( ! empty( $meta_query ) ) {
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'wp_livescore_la_filter_league_admin_query' );

/**
 * Extend League admin search into selected meta fields.
 *
 * @param string   $search Search SQL.
 * @param WP_Query $query  Query object.
 * @return string
 */
function wp_livescore_la_league_posts_search( $search, $query ) {
	global $wpdb;

	if ( ! is_admin() || ! $query->is_main_query() || 'league' !== $query->get( 'post_type' ) ) {
		return $search;
	}

	$term = $query->get( 's' );
	if ( '' === $term ) {
		return $search;
	}

	$like      = '%' . $wpdb->esc_like( $term ) . '%';
	$meta_keys = array(
		WP_LIVESCORE_LA_META_PREFIX . 'country',
		WP_LIVESCORE_LA_META_PREFIX . 'sports',
		WP_LIVESCORE_LA_META_PREFIX . 'api_id',
	);

	$meta_sql = $wpdb->prepare(
		" OR {$wpdb->posts}.ID IN (
			SELECT post_id FROM {$wpdb->postmeta}
			WHERE meta_key IN (%s, %s, %s) AND meta_value LIKE %s
		)",
		$meta_keys[0],
		$meta_keys[1],
		$meta_keys[2],
		$like
	);

	return preg_replace( '/\)\s*$/', $meta_sql . ')', $search, 1 );
}
add_filter( 'posts_search', 'wp_livescore_la_league_posts_search', 10, 2 );

/**
 * Use the plugin League archive template when the theme does not provide one.
 *
 * @param string $template Current template path.
 * @return string
 */
function wp_livescore_la_league_archive_template( $template ) {
	if ( ! is_post_type_archive( 'league' ) ) {
		return $template;
	}

	if ( function_exists( 'wp_livescore_la_get_astra_site_builder_template' ) ) {
		$astra_template = wp_livescore_la_get_astra_site_builder_template( 'Leagues' );
		if ( '' !== $astra_template ) {
			return $astra_template;
		}
	}

	if ( locate_template( array( 'archive-league.php' ) ) ) {
		return $template;
	}

	$plugin_template = WP_LIVESCORE_LA_DIR . 'templates/archive-league.php';

	return file_exists( $plugin_template ) ? $plugin_template : $template;
}
add_filter( 'template_include', 'wp_livescore_la_league_archive_template' );

/**
 * Apply frontend League archive filters from the League Filters block.
 *
 * @param WP_Query $query Query object.
 */
function wp_livescore_la_filter_frontend_league_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$is_league_query = is_post_type_archive( 'league' ) || 'league' === $query->get( 'post_type' );
	if ( ! $is_league_query ) {
		return;
	}

	$meta_query = (array) $query->get( 'meta_query' );

	if ( isset( $_GET['league_sport'] ) && '' !== $_GET['league_sport'] ) {
		$meta_query[] = array(
			'key'   => '_league_sport_slug',
			'value' => sanitize_title( wp_unslash( $_GET['league_sport'] ) ),
		);
	}

	if ( isset( $_GET['league_country'] ) && '' !== $_GET['league_country'] ) {
		$meta_query[] = array(
			'key'   => '_league_country_slug',
			'value' => sanitize_title( wp_unslash( $_GET['league_country'] ) ),
		);
	}

	if ( ! empty( $meta_query ) ) {
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'wp_livescore_la_filter_frontend_league_query' );
