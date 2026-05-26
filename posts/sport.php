<?php
/**
 * Sports taxonomy and helpers.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Sport taxonomy used by Sports Manager.
 */
function wp_livescore_la_register_sport_post_type() {
	register_taxonomy(
		'sport',
		array( 'league', 'team', 'match', 'player' ),
		array(
			'labels'            => array(
				'name'          => __( 'Sports', 'wp-livescore-la' ),
				'singular_name' => __( 'Sport', 'wp-livescore-la' ),
				'menu_name'     => __( 'Sports', 'wp-livescore-la' ),
				'all_items'     => __( 'All Sports', 'wp-livescore-la' ),
				'edit_item'     => __( 'Edit Sport', 'wp-livescore-la' ),
				'add_new_item'  => __( 'Add New Sport', 'wp-livescore-la' ),
				'new_item_name' => __( 'New Sport Name', 'wp-livescore-la' ),
				'view_item'     => __( 'View Sport', 'wp-livescore-la' ),
				'search_items'  => __( 'Search Sports', 'wp-livescore-la' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'show_ui'           => true,
			'rewrite'           => array( 'slug' => 'sport' ),
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_sport_post_type' );

/**
 * Sanitize one or more CSS icon classes.
 *
 * @param string $classes Raw class list.
 * @return string
 */
function wp_livescore_la_sanitize_icon_classes( $classes ) {
	$classes = preg_split( '/\s+/', trim( (string) $classes ) );
	$classes = array_filter( array_map( 'sanitize_html_class', (array) $classes ) );

	return implode( ' ', array_unique( $classes ) );
}

/**
 * Suggested Astra/Spectra icon classes for Sports.
 *
 * @return array
 */
function wp_livescore_la_sport_icon_suggestions() {
	return array(
		'fa-solid fa-futbol',
		'fa-solid fa-basketball',
		'fa-solid fa-baseball',
		'fa-solid fa-volleyball',
		'fa-solid fa-football',
		'fa-solid fa-table-tennis-paddle-ball',
		'fa-solid fa-person-running',
		'fa-solid fa-person-swimming',
		'fa-solid fa-trophy',
		'fa-solid fa-medal',
		'fa-solid fa-dumbbell',
		'fa-solid fa-flag-checkered',
	);
}

/**
 * Register Sport term meta fields.
 */
function wp_livescore_la_register_sport_meta() {
	register_term_meta(
		'sport',
		WP_LIVESCORE_LA_META_PREFIX . 'sport_status',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);

	register_term_meta(
		'sport',
		WP_LIVESCORE_LA_META_PREFIX . 'sport_icon',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'wp_livescore_la_sanitize_icon_classes',
			'auth_callback'     => function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);

}
add_action( 'init', 'wp_livescore_la_register_sport_meta' );

/**
 * Register Sports taxonomy submenu under Sports Manager.
 */
function wp_livescore_la_register_sport_taxonomy_submenu() {
	add_submenu_page(
		'wp-livescore-la-sports-manager',
		__( 'Sports', 'wp-livescore-la' ),
		__( 'Sports', 'wp-livescore-la' ),
		'manage_categories',
		'edit-tags.php?taxonomy=sport&post_type=league'
	);
}
add_action( 'admin_menu', 'wp_livescore_la_register_sport_taxonomy_submenu', 20 );

/**
 * Render Sport term fields when adding a Sport.
 */
function wp_livescore_la_render_sport_add_fields() {
	wp_livescore_la_render_sport_term_fields();
}
add_action( 'sport_add_form_fields', 'wp_livescore_la_render_sport_add_fields' );

/**
 * Render Sport term fields when editing a Sport.
 *
 * @param WP_Term $term Sport term.
 */
function wp_livescore_la_render_sport_edit_fields( $term ) {
	wp_livescore_la_render_sport_term_fields( $term );
}
add_action( 'sport_edit_form_fields', 'wp_livescore_la_render_sport_edit_fields' );

/**
 * Render shared Sport term fields.
 *
 * @param WP_Term|null $term Sport term.
 */
function wp_livescore_la_render_sport_term_fields( $term = null ) {
	$term_id = $term instanceof WP_Term ? (int) $term->term_id : 0;
	$status  = $term_id > 0 ? get_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_status', true ) : 'active';
	$status  = '' !== $status ? $status : 'active';
	$icon    = $term_id > 0 ? get_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_icon', true ) : '';

	if ( $term_id <= 0 ) :
		?>
		<div class="form-field">
			<label for="wp_livescore_la_sport_status"><?php esc_html_e( 'Status', 'wp-livescore-la' ); ?></label>
			<?php wp_livescore_la_render_sport_status_select( $status ); ?>
		</div>
		<div class="form-field">
			<label for="wp_livescore_la_sport_icon"><?php esc_html_e( 'Icon', 'wp-livescore-la' ); ?></label>
			<?php wp_livescore_la_render_sport_icon_field( $icon ); ?>
		</div>
		<?php
		return;
	endif;
	?>
	<tr class="form-field">
		<th scope="row"><label for="wp_livescore_la_sport_status"><?php esc_html_e( 'Status', 'wp-livescore-la' ); ?></label></th>
		<td><?php wp_livescore_la_render_sport_status_select( $status ); ?></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="wp_livescore_la_sport_icon"><?php esc_html_e( 'Icon', 'wp-livescore-la' ); ?></label></th>
		<td><?php wp_livescore_la_render_sport_icon_field( $icon ); ?></td>
	</tr>
	<?php
}

/**
 * Render Sport status select.
 *
 * @param string $status Current status.
 */
function wp_livescore_la_render_sport_status_select( $status ) {
	?>
	<select id="wp_livescore_la_sport_status" name="wp_livescore_la_sport_status">
		<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'wp-livescore-la' ); ?></option>
		<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wp-livescore-la' ); ?></option>
	</select>
	<?php
}

/**
 * Render Sport icon field.
 *
 * @param string $icon Current icon classes.
 */
function wp_livescore_la_render_sport_icon_field( $icon ) {
	$icon = wp_livescore_la_sanitize_icon_classes( $icon );
	?>
	<input
		type="text"
		id="wp_livescore_la_sport_icon"
		name="wp_livescore_la_sport_icon"
		class="regular-text"
		value="<?php echo esc_attr( $icon ); ?>"
		list="wp_livescore_la_sport_icon_suggestions"
		placeholder="fa-solid fa-futbol"
	>
	<datalist id="wp_livescore_la_sport_icon_suggestions">
		<?php foreach ( wp_livescore_la_sport_icon_suggestions() as $suggestion ) : ?>
			<option value="<?php echo esc_attr( $suggestion ); ?>"></option>
		<?php endforeach; ?>
	</datalist>
	<?php if ( '' !== $icon ) : ?>
		<i class="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i>
	<?php endif; ?>
	<p class="description"><?php esc_html_e( 'Use an Astra/Spectra icon class. Example: fa-solid fa-futbol. Icons display when Astra, Spectra, or another asset already loads the icon font on the page.', 'wp-livescore-la' ); ?></p>
	<?php
}

/**
 * Save Sport term meta.
 *
 * @param int $term_id Sport term ID.
 */
function wp_livescore_la_save_sport_meta( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	$status = isset( $_POST['wp_livescore_la_sport_status'] ) ? sanitize_key( wp_unslash( $_POST['wp_livescore_la_sport_status'] ) ) : 'active';
	if ( ! in_array( $status, array( 'active', 'inactive' ), true ) ) {
		$status = 'active';
	}

	$icon = isset( $_POST['wp_livescore_la_sport_icon'] ) ? wp_livescore_la_sanitize_icon_classes( wp_unslash( $_POST['wp_livescore_la_sport_icon'] ) ) : '';

	update_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_status', $status );
	delete_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_icon_library' );

	if ( '' !== $icon ) {
		update_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_icon', $icon );
	} else {
		delete_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_icon' );
	}
}
add_action( 'created_sport', 'wp_livescore_la_save_sport_meta' );
add_action( 'edited_sport', 'wp_livescore_la_save_sport_meta' );

/**
 * Normalize a sport string for matching.
 *
 * @param string $value Raw sport value.
 * @return string
 */
function wp_livescore_la_normalize_sport_value( $value ) {
	return sanitize_title( trim( (string) $value ) );
}

/**
 * Get Sport terms.
 *
 * @param bool $active_only Whether to only return active sports.
 * @return WP_Term[]
 */
function wp_livescore_la_get_sports_manager_items( $active_only = false ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'sport',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( ! is_array( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	if ( ! $active_only ) {
		return $terms;
	}

	return array_values(
		array_filter(
			$terms,
			function ( $term ) {
				$status = get_term_meta( $term->term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_status', true );
				return '' === $status || 'active' === $status;
			}
		)
	);
}

/**
 * Find a Sport term by slug or name.
 *
 * @param string $value Sport slug or name.
 * @return int
 */
function wp_livescore_la_find_sport_id_by_value( $value ) {
	$normalized = wp_livescore_la_normalize_sport_value( $value );

	if ( '' === $normalized ) {
		return 0;
	}

	$term = get_term_by( 'slug', $normalized, 'sport' );
	if ( $term instanceof WP_Term ) {
		return (int) $term->term_id;
	}

	foreach ( wp_livescore_la_get_sports_manager_items( false ) as $sport_item ) {
		if ( $normalized === wp_livescore_la_normalize_sport_value( $sport_item->name ) ) {
			return (int) $sport_item->term_id;
		}
	}

	return 0;
}

/**
 * Get an existing Sport term or create one from an imported value.
 *
 * @param string $value Imported sport name or slug.
 * @return int
 */
function wp_livescore_la_get_or_create_sport_id( $value ) {
	$name = sanitize_text_field( $value );

	if ( '' === $name ) {
		return 0;
	}

	$sport_id = wp_livescore_la_find_sport_id_by_value( $name );
	if ( $sport_id > 0 ) {
		return $sport_id;
	}

	$inserted = wp_insert_term(
		$name,
		'sport',
		array(
			'slug' => wp_livescore_la_normalize_sport_value( $name ),
		)
	);

	if ( is_wp_error( $inserted ) ) {
		$existing_id = (int) $inserted->get_error_data( 'term_exists' );
		return $existing_id > 0 ? $existing_id : 0;
	}

	if ( empty( $inserted['term_id'] ) ) {
		return 0;
	}

	update_term_meta( (int) $inserted['term_id'], WP_LIVESCORE_LA_META_PREFIX . 'sport_status', 'active' );

	return (int) $inserted['term_id'];
}

/**
 * Render a Sport taxonomy select.
 *
 * @param string $name        Field name.
 * @param int    $selected_id Selected term ID.
 * @param string $placeholder Placeholder label.
 */
function wp_livescore_la_render_sport_select( $name, $selected_id, $placeholder ) {
	$sports = wp_livescore_la_get_sports_manager_items( true );

	if ( $selected_id > 0 ) {
		$selected = get_term( $selected_id, 'sport' );
		if ( $selected instanceof WP_Term && ! is_wp_error( $selected ) && ! in_array( $selected_id, wp_list_pluck( $sports, 'term_id' ), true ) ) {
			$sports[] = $selected;
		}
	}
	?>
	<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text">
		<option value=""><?php echo esc_html( $placeholder ); ?></option>
		<?php foreach ( $sports as $sport ) : ?>
			<?php
			$status = get_term_meta( $sport->term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_status', true );
			$label  = $sport->name;
			if ( 'inactive' === $status ) {
				$label .= ' (' . __( 'Inactive', 'wp-livescore-la' ) . ')';
			}
			?>
			<option value="<?php echo esc_attr( $sport->term_id ); ?>" <?php selected( $selected_id, $sport->term_id ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Save selected Sport term details onto a League.
 *
 * @param int    $league_id      League post ID.
 * @param int    $sport_id       Selected Sport term ID.
 * @param string $fallback_value Existing/imported sport text.
 */
function wp_livescore_la_sync_league_sport_meta( $league_id, $sport_id = 0, $fallback_value = '' ) {
	if ( $sport_id <= 0 && '' !== $fallback_value ) {
		$sport_id = wp_livescore_la_find_sport_id_by_value( $fallback_value );
	}

	$term = $sport_id > 0 ? get_term( $sport_id, 'sport' ) : null;
	if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
		update_post_meta( $league_id, '_league_sport_id', (int) $term->term_id );
		update_post_meta( $league_id, '_league_sport_name', sanitize_text_field( $term->name ) );
		update_post_meta( $league_id, '_league_sport_slug', sanitize_title( $term->slug ) );
		update_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'sports', sanitize_text_field( $term->name ) );
		wp_set_object_terms( $league_id, array( (int) $term->term_id ), 'sport', false );
		return;
	}

	if ( '' !== $fallback_value ) {
		update_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . 'sports', sanitize_text_field( $fallback_value ) );
	}
}

/**
 * Add Sport term admin columns.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function wp_livescore_la_sport_columns( $columns ) {
	$columns['wp_livescore_la_sport_status'] = __( 'Status', 'wp-livescore-la' );
	$columns['wp_livescore_la_sport_icon']   = __( 'Icon', 'wp-livescore-la' );
	return $columns;
}
add_filter( 'manage_edit-sport_columns', 'wp_livescore_la_sport_columns' );

/**
 * Render Sport term admin columns.
 *
 * @param string $content Current column content.
 * @param string $column  Column name.
 * @param int    $term_id Sport term ID.
 * @return string
 */
function wp_livescore_la_render_sport_columns( $content, $column, $term_id ) {
	if ( 'wp_livescore_la_sport_status' === $column ) {
		$status = get_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_status', true );
		return esc_html( '' !== $status ? ucfirst( $status ) : __( 'Active', 'wp-livescore-la' ) );
	}

	if ( 'wp_livescore_la_sport_icon' === $column ) {
		$icon = wp_livescore_la_sanitize_icon_classes( get_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_icon', true ) );
		if ( '' === $icon ) {
			return '&mdash;';
		}
		return '<i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i> ' . esc_html( $icon );
	}

	return $content;
}
add_filter( 'manage_sport_custom_column', 'wp_livescore_la_render_sport_columns', 10, 3 );

/**
 * Migrate legacy Sport posts into the Sport taxonomy once.
 */
function wp_livescore_la_migrate_legacy_sport_posts_to_terms() {
	if ( '1' === get_option( 'wp_livescore_la_sport_terms_migrated' ) ) {
		return;
	}

	$legacy_sports = get_posts(
		array(
			'post_type'      => 'sport',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);

	foreach ( $legacy_sports as $legacy_sport ) {
		if ( ! $legacy_sport instanceof WP_Post ) {
			continue;
		}

		$name = sanitize_text_field( $legacy_sport->post_title );
		if ( '' === $name ) {
			continue;
		}

		$term_id = wp_livescore_la_get_or_create_sport_id( $name );
		if ( $term_id <= 0 ) {
			continue;
		}

		$status = get_post_meta( $legacy_sport->ID, WP_LIVESCORE_LA_META_PREFIX . 'sport_status', true );
		if ( '' !== $status ) {
			update_term_meta( $term_id, WP_LIVESCORE_LA_META_PREFIX . 'sport_status', sanitize_key( $status ) );
		}

		wp_livescore_la_migrate_legacy_sport_relationships( (int) $legacy_sport->ID, $term_id );
	}

	update_option( 'wp_livescore_la_sport_terms_migrated', '1', false );
}
add_action( 'init', 'wp_livescore_la_migrate_legacy_sport_posts_to_terms', 40 );

/**
 * Update legacy post meta sport IDs to the new Sport term IDs.
 *
 * @param int $old_sport_id Legacy Sport post ID.
 * @param int $term_id      New Sport term ID.
 */
function wp_livescore_la_migrate_legacy_sport_relationships( $old_sport_id, $term_id ) {
	$term = get_term( $term_id, 'sport' );
	if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
		return;
	}

	$targets = array(
		'league' => array( 'meta_key' => '_league_sport_id', 'sync' => 'wp_livescore_la_sync_league_sport_meta' ),
		'team'   => array( 'meta_key' => '_team_sport_id', 'sync' => 'wp_livescore_la_sync_team_sport_meta' ),
		'match'  => array( 'meta_key' => '_match_sport_id', 'sync' => 'wp_livescore_la_sync_match_sport_meta' ),
		'player' => array( 'meta_key' => '_player_sport_id', 'sync' => 'wp_livescore_la_sync_player_sport_meta' ),
	);

	foreach ( $targets as $post_type => $target ) {
		$post_ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => $target['meta_key'],
				'meta_value'     => $old_sport_id,
			)
		);

		foreach ( $post_ids as $post_id ) {
			if ( function_exists( $target['sync'] ) ) {
				call_user_func( $target['sync'], (int) $post_id, $term_id );
			} else {
				update_post_meta( (int) $post_id, $target['meta_key'], $term_id );
				update_post_meta( (int) $post_id, str_replace( '_id', '_name', $target['meta_key'] ), sanitize_text_field( $term->name ) );
				update_post_meta( (int) $post_id, str_replace( '_id', '_slug', $target['meta_key'] ), sanitize_title( $term->slug ) );
				wp_set_object_terms( (int) $post_id, array( $term_id ), 'sport', false );
			}
		}
	}
}
