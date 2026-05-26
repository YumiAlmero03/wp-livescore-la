<?php
/**
 * Countries Manager custom post type and helpers.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Country custom post type used by Countries Manager.
 */
function wp_livescore_la_register_country_post_type() {
	register_post_type(
		'country',
		array(
			'labels'              => array(
				'name'               => __( 'Countries', 'wp-livescore-la' ),
				'singular_name'      => __( 'Country', 'wp-livescore-la' ),
				'menu_name'          => __( 'Countries', 'wp-livescore-la' ),
				'add_new_item'       => __( 'Add New Country', 'wp-livescore-la' ),
				'edit_item'          => __( 'Edit Country', 'wp-livescore-la' ),
				'new_item'           => __( 'New Country', 'wp-livescore-la' ),
				'view_item'          => __( 'View Country', 'wp-livescore-la' ),
				'search_items'       => __( 'Search Countries', 'wp-livescore-la' ),
				'not_found'          => __( 'No countries found.', 'wp-livescore-la' ),
				'not_found_in_trash' => __( 'No countries found in Trash.', 'wp-livescore-la' ),
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => 'wp-livescore-la-sports-manager',
			'show_in_rest'        => true,
			'has_archive'         => 'country',
			'rewrite'             => array(
				'slug'       => 'country',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-flag',
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_country_post_type' );

/**
 * Return the static continent choices.
 *
 * @return array
 */
function wp_livescore_la_get_continent_options() {
	return array(
		'Africa',
		'Antarctica',
		'Asia',
		'Europe',
		'North America',
		'South America',
		'Oceania',
		'International',
	);
}

/**
 * Sanitize a continent against the static choices.
 *
 * @param string $continent Raw continent.
 * @return string
 */
function wp_livescore_la_sanitize_continent( $continent ) {
	$continent = sanitize_text_field( $continent );
	return in_array( $continent, wp_livescore_la_get_continent_options(), true ) ? $continent : 'International';
}

/**
 * Register Country meta fields.
 */
function wp_livescore_la_register_country_meta() {
	$meta_fields = array(
		'country_code'      => 'sanitize_text_field',
		'country_flag_url'  => 'esc_url_raw',
		'country_continent' => 'wp_livescore_la_sanitize_continent',
		'country_status'    => 'sanitize_key',
	);

	foreach ( $meta_fields as $field => $sanitizer ) {
		register_post_meta(
			'country',
			WP_LIVESCORE_LA_META_PREFIX . $field,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $sanitizer,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'wp_livescore_la_register_country_meta' );

/**
 * Add Country Details meta box.
 */
function wp_livescore_la_add_country_meta_box() {
	add_meta_box(
		'wp-livescore-la-country-details',
		__( 'Country Details', 'wp-livescore-la' ),
		'wp_livescore_la_render_country_meta_box',
		'country',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_country', 'wp_livescore_la_add_country_meta_box' );

/**
 * Render Country Details meta box.
 *
 * @param WP_Post $post Country post.
 */
function wp_livescore_la_render_country_meta_box( $post ) {
	$status    = get_post_meta( $post->ID, WP_LIVESCORE_LA_META_PREFIX . 'country_status', true );
	$status    = '' !== $status ? $status : 'active';
	$code      = get_post_meta( $post->ID, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true );
	$flag_url  = get_post_meta( $post->ID, WP_LIVESCORE_LA_META_PREFIX . 'country_flag_url', true );
	$continent = wp_livescore_la_sanitize_continent( get_post_meta( $post->ID, WP_LIVESCORE_LA_META_PREFIX . 'country_continent', true ) );

	wp_nonce_field( 'wp_livescore_la_save_country_meta', 'wp_livescore_la_country_meta_nonce' );
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="wp_livescore_la_country_status"><?php esc_html_e( 'Status', 'wp-livescore-la' ); ?></label></th>
				<td>
					<select id="wp_livescore_la_country_status" name="wp_livescore_la_country_status">
						<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'wp-livescore-la' ); ?></option>
						<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wp-livescore-la' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_country_code"><?php esc_html_e( 'Country Code', 'wp-livescore-la' ); ?></label></th>
				<td><input type="text" id="wp_livescore_la_country_code" name="wp_livescore_la_country_code" value="<?php echo esc_attr( $code ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_country_continent"><?php esc_html_e( 'Continent', 'wp-livescore-la' ); ?></label></th>
				<td>
					<select id="wp_livescore_la_country_continent" name="wp_livescore_la_country_continent">
						<?php foreach ( wp_livescore_la_get_continent_options() as $option ) : ?>
							<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $continent, $option ); ?>><?php echo esc_html( $option ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_country_flag_url"><?php esc_html_e( 'Flag/Icon URL', 'wp-livescore-la' ); ?></label></th>
				<td>
					<input type="url" id="wp_livescore_la_country_flag_url" name="wp_livescore_la_country_flag_url" value="<?php echo esc_attr( $flag_url ); ?>" class="regular-text wp-livescore-la-country-flag-url" />
					<button type="button" class="button wp-livescore-la-select-country-flag"><?php esc_html_e( 'Select Image', 'wp-livescore-la' ); ?></button>
					<?php if ( '' !== $flag_url ) : ?>
						<p><img class="wp-livescore-la-country-flag-preview" src="<?php echo esc_url( $flag_url ); ?>" alt="" /></p>
					<?php else : ?>
						<p><img class="wp-livescore-la-country-flag-preview" src="" alt="" style="display:none;" /></p>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Save Country meta.
 *
 * @param int $post_id Country post ID.
 */
function wp_livescore_la_save_country_meta( $post_id ) {
	if ( ! isset( $_POST['wp_livescore_la_country_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_country_meta_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'wp_livescore_la_save_country_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$status = isset( $_POST['wp_livescore_la_country_status'] ) ? sanitize_key( wp_unslash( $_POST['wp_livescore_la_country_status'] ) ) : 'active';
	if ( ! in_array( $status, array( 'active', 'inactive' ), true ) ) {
		$status = 'active';
	}

	$code      = isset( $_POST['wp_livescore_la_country_code'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_country_code'] ) ) : '';
	$continent = isset( $_POST['wp_livescore_la_country_continent'] ) ? wp_livescore_la_sanitize_continent( wp_unslash( $_POST['wp_livescore_la_country_continent'] ) ) : 'International';
	$flag_url  = isset( $_POST['wp_livescore_la_country_flag_url'] ) ? esc_url_raw( wp_unslash( $_POST['wp_livescore_la_country_flag_url'] ) ) : '';

	update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_status', $status );
	update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_continent', $continent );

	if ( '' !== $code ) {
		update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', $code );
	} else {
		delete_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code' );
	}

	if ( '' !== $flag_url ) {
		update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_flag_url', $flag_url );
	} else {
		delete_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_flag_url' );
	}
}
add_action( 'save_post_country', 'wp_livescore_la_save_country_meta' );

/**
 * Normalize a country string for matching.
 *
 * @param string $value Raw country value.
 * @return string
 */
function wp_livescore_la_normalize_country_value( $value ) {
	return sanitize_title( trim( (string) $value ) );
}

/**
 * Get Countries Manager posts.
 *
 * @param bool $active_only Whether to only return active countries.
 * @return WP_Post[]
 */
function wp_livescore_la_get_countries_manager_items( $active_only = false ) {
	$args = array(
		'post_type'      => 'country',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	if ( $active_only ) {
		$args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => WP_LIVESCORE_LA_META_PREFIX . 'country_status',
				'value'   => 'active',
				'compare' => '=',
			),
			array(
				'key'     => WP_LIVESCORE_LA_META_PREFIX . 'country_status',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	return get_posts( $args );
}

/**
 * Find a Countries Manager post by slug, name, or country code.
 *
 * @param string $value Country slug, name, or code.
 * @return int
 */
function wp_livescore_la_find_country_id_by_value( $value ) {
	$normalized = wp_livescore_la_normalize_country_value( $value );
	$code       = strtoupper( sanitize_text_field( $value ) );

	if ( '' === $normalized && '' === $code ) {
		return 0;
	}

	if ( '' !== $normalized ) {
		$country = get_page_by_path( $normalized, OBJECT, 'country' );
		if ( $country instanceof WP_Post ) {
			return (int) $country->ID;
		}
	}

	foreach ( wp_livescore_la_get_countries_manager_items( false ) as $country_item ) {
		$item_code = strtoupper( (string) get_post_meta( $country_item->ID, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true ) );

		if ( $normalized === wp_livescore_la_normalize_country_value( $country_item->post_title ) || ( '' !== $code && $code === $item_code ) ) {
			return (int) $country_item->ID;
		}
	}

	return 0;
}

/**
 * Get an existing Countries Manager post or create one from imported data.
 *
 * @param string $name      Imported country name or slug.
 * @param string $code      Optional country code.
 * @param string $continent Optional continent.
 * @param string $flag_url  Optional flag image URL.
 * @return int
 */
function wp_livescore_la_get_or_create_country_id( $name, $code = '', $continent = '', $flag_url = '' ) {
	$name      = sanitize_text_field( $name );
	$code      = sanitize_text_field( $code );
	$continent = wp_livescore_la_sanitize_continent( $continent );
	$flag_url  = esc_url_raw( $flag_url );

	if ( '' === $name && '' === $code ) {
		return 0;
	}

	$country_id = wp_livescore_la_find_country_id_by_value( '' !== $name ? $name : $code );
	if ( $country_id <= 0 && '' !== $code ) {
		$country_id = wp_livescore_la_find_country_id_by_value( $code );
	}

	if ( $country_id > 0 ) {
		if ( '' !== $code ) {
			update_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', $code );
		}
		update_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_continent', $continent );
		if ( '' !== $flag_url ) {
			update_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_flag_url', $flag_url );
		}
		return $country_id;
	}

	$title = '' !== $name ? $name : $code;

	$inserted_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'   => 'country',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => wp_livescore_la_normalize_country_value( $title ),
			)
		),
		true
	);

	if ( is_wp_error( $inserted_id ) || $inserted_id <= 0 ) {
		return 0;
	}

	update_post_meta( $inserted_id, WP_LIVESCORE_LA_META_PREFIX . 'country_status', 'active' );
	update_post_meta( $inserted_id, WP_LIVESCORE_LA_META_PREFIX . 'country_continent', $continent );

	if ( '' !== $code ) {
		update_post_meta( $inserted_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', $code );
	}

	if ( '' !== $flag_url ) {
		update_post_meta( $inserted_id, WP_LIVESCORE_LA_META_PREFIX . 'country_flag_url', $flag_url );
	}

	return (int) $inserted_id;
}

/**
 * Save selected Countries Manager details onto a League.
 *
 * @param int    $league_id      League post ID.
 * @param int    $country_id     Selected Countries Manager post ID.
 * @param string $fallback_value Existing/imported country text.
 */
function wp_livescore_la_sync_league_country_meta( $league_id, $country_id = 0, $fallback_value = '' ) {
	if ( $country_id <= 0 && '' !== $fallback_value ) {
		$country_id = wp_livescore_la_find_country_id_by_value( $fallback_value );
	}

	if ( $country_id > 0 && 'country' === get_post_type( $country_id ) ) {
		$name      = get_the_title( $country_id );
		$slug      = get_post_field( 'post_name', $country_id );
		$code      = get_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true );
		$continent = wp_livescore_la_sanitize_continent( get_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_continent', true ) );

		update_post_meta( $league_id, '_league_country_id', (int) $country_id );
		update_post_meta( $league_id, '_league_country_name', sanitize_text_field( $name ) );
		update_post_meta( $league_id, '_league_country_slug', sanitize_title( $slug ) );
		update_post_meta( $league_id, '_league_country_code', sanitize_text_field( $code ) );
		update_post_meta( $league_id, '_league_continent', $continent );
		update_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'country', sanitize_text_field( $name ) );
		return;
	}

	if ( '' !== $fallback_value ) {
		update_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'country', sanitize_text_field( $fallback_value ) );
	}
}

/**
 * Add Country admin columns.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function wp_livescore_la_country_columns( $columns ) {
	$columns['wp_livescore_la_country_code']      = __( 'Code', 'wp-livescore-la' );
	$columns['wp_livescore_la_country_continent'] = __( 'Continent', 'wp-livescore-la' );
	$columns['wp_livescore_la_country_status']    = __( 'Status', 'wp-livescore-la' );
	return $columns;
}
add_filter( 'manage_country_posts_columns', 'wp_livescore_la_country_columns' );

/**
 * Render Country admin columns.
 *
 * @param string $column  Column name.
 * @param int    $post_id Country post ID.
 */
function wp_livescore_la_render_country_columns( $column, $post_id ) {
	if ( 'wp_livescore_la_country_code' === $column ) {
		echo esc_html( get_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true ) );
	}

	if ( 'wp_livescore_la_country_continent' === $column ) {
		echo esc_html( get_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_continent', true ) );
	}

	if ( 'wp_livescore_la_country_status' === $column ) {
		$status = get_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'country_status', true );
		echo esc_html( '' !== $status ? ucfirst( $status ) : __( 'Active', 'wp-livescore-la' ) );
	}
}
add_action( 'manage_country_posts_custom_column', 'wp_livescore_la_render_country_columns', 10, 2 );
