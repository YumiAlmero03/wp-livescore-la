<?php
/**
 * Team custom post type, metadata, tag sync, and import helpers.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Team custom post type.
 */
function wp_livescore_la_register_team_post_type() {
	register_post_type(
		'team',
		array(
			'labels'              => array(
				'name'               => __( 'Teams', 'wp-livescore-la' ),
				'singular_name'      => __( 'Team', 'wp-livescore-la' ),
				'menu_name'          => __( 'Teams', 'wp-livescore-la' ),
				'add_new_item'       => __( 'Add New Team', 'wp-livescore-la' ),
				'edit_item'          => __( 'Edit Team', 'wp-livescore-la' ),
				'all_items'          => __( 'All Teams', 'wp-livescore-la' ),
				'new_item'           => __( 'New Team', 'wp-livescore-la' ),
				'view_item'          => __( 'View Team', 'wp-livescore-la' ),
				'search_items'       => __( 'Search Teams', 'wp-livescore-la' ),
				'not_found'          => __( 'No teams found.', 'wp-livescore-la' ),
				'not_found_in_trash' => __( 'No teams found in Trash.', 'wp-livescore-la' ),
			),
			'public'              => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wp-livescore-la-sports-manager',
			'show_in_rest'        => true,
			'has_archive'         => 'teams',
			'rewrite'             => array(
				'slug'       => 'teams',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-shield',
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_team_post_type' );

/**
 * Team meta keys and labels.
 *
 * @return array
 */
function wp_livescore_la_team_meta_fields() {
	return array(
		'_team_api_id'       => __( 'API ID', 'wp-livescore-la' ),
		'_team_short_name'   => __( 'Shortcut Name', 'wp-livescore-la' ),
		'_team_logo'         => __( 'Logo URL', 'wp-livescore-la' ),
		'_team_website'      => __( 'Website', 'wp-livescore-la' ),
		'_team_facebook'     => __( 'Facebook', 'wp-livescore-la' ),
		'_team_instagram'    => __( 'Instagram', 'wp-livescore-la' ),
		'_team_twitter'      => __( 'Twitter/X', 'wp-livescore-la' ),
		'_team_youtube'      => __( 'YouTube', 'wp-livescore-la' ),
		'_team_status'       => __( 'Status', 'wp-livescore-la' ),
		'_team_sport_id'     => __( 'Sport ID', 'wp-livescore-la' ),
		'_team_sport_name'   => __( 'Sport Name', 'wp-livescore-la' ),
		'_team_sport_slug'   => __( 'Sport Slug', 'wp-livescore-la' ),
		'_team_country_id'   => __( 'Country ID', 'wp-livescore-la' ),
		'_team_country_name' => __( 'Country Name', 'wp-livescore-la' ),
		'_team_country_slug' => __( 'Country Slug', 'wp-livescore-la' ),
		'_team_country_code' => __( 'Country Code', 'wp-livescore-la' ),
		'_team_continent'    => __( 'Continent', 'wp-livescore-la' ),
	);
}

/**
 * Register Team meta.
 */
function wp_livescore_la_register_team_meta() {
	foreach ( wp_livescore_la_team_meta_fields() as $meta_key => $label ) {
		$is_int = in_array( $meta_key, array( '_team_sport_id', '_team_country_id' ), true );
		$is_url = in_array( $meta_key, array( '_team_logo', '_team_website', '_team_facebook', '_team_instagram', '_team_twitter', '_team_youtube' ), true );

		register_post_meta(
			'team',
			$meta_key,
			array(
				'type'              => $is_int ? 'integer' : 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $is_int ? 'absint' : ( $is_url ? 'esc_url_raw' : 'sanitize_text_field' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		'team',
		'_linked_post_tag_id',
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
add_action( 'init', 'wp_livescore_la_register_team_meta' );

/**
 * Add Team Details meta box.
 */
function wp_livescore_la_add_team_meta_box() {
	add_meta_box(
		'wp-livescore-la-team-details',
		__( 'Team Details', 'wp-livescore-la' ),
		'wp_livescore_la_render_team_meta_box',
		'team',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_team', 'wp_livescore_la_add_team_meta_box' );

/**
 * Render Team Details meta box.
 *
 * @param WP_Post $post Team post.
 */
function wp_livescore_la_render_team_meta_box( $post ) {
	wp_nonce_field( 'wp_livescore_la_save_team_meta', 'wp_livescore_la_team_meta_nonce' );

	$sport_id   = (int) get_post_meta( $post->ID, '_team_sport_id', true );
	$country_id = (int) get_post_meta( $post->ID, '_team_country_id', true );
	$status     = get_post_meta( $post->ID, '_team_status', true );
	$status     = '' !== $status ? $status : 'active';
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="wp_livescore_la_team_status"><?php esc_html_e( 'Status', 'wp-livescore-la' ); ?></label></th>
				<td>
					<select id="wp_livescore_la_team_status" name="wp_livescore_la_team_status">
						<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'wp-livescore-la' ); ?></option>
						<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wp-livescore-la' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_team_sport_id"><?php esc_html_e( 'Sport', 'wp-livescore-la' ); ?></label></th>
				<td><?php wp_livescore_la_render_post_select( 'wp_livescore_la_team_sport_id', 'sport', $sport_id, __( 'Select sport', 'wp-livescore-la' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_team_country_id"><?php esc_html_e( 'Country', 'wp-livescore-la' ); ?></label></th>
				<td><?php wp_livescore_la_render_post_select( 'wp_livescore_la_team_country_id', 'country', $country_id, __( 'Select country', 'wp-livescore-la' ) ); ?></td>
			</tr>
			<?php foreach ( array( '_team_api_id', '_team_short_name', '_team_logo', '_team_website', '_team_facebook', '_team_instagram', '_team_twitter', '_team_youtube' ) as $meta_key ) : ?>
				<?php $value = get_post_meta( $post->ID, $meta_key, true ); ?>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( wp_livescore_la_team_meta_fields()[ $meta_key ] ); ?></label></th>
					<td>
						<input
							type="<?php echo in_array( $meta_key, array( '_team_logo', '_team_website', '_team_facebook', '_team_instagram', '_team_twitter', '_team_youtube' ), true ) ? 'url' : 'text'; ?>"
							id="<?php echo esc_attr( $meta_key ); ?>"
							name="wp_livescore_la_team_meta[<?php echo esc_attr( $meta_key ); ?>]"
							value="<?php echo esc_attr( $value ); ?>"
							class="regular-text"
						/>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render a post select field.
 *
 * @param string $name        Field name.
 * @param string $post_type   Post type.
 * @param int    $selected_id Selected post ID.
 * @param string $placeholder Placeholder label.
 */
function wp_livescore_la_render_post_select( $name, $post_type, $selected_id, $placeholder ) {
	if ( 'sport' === $post_type && function_exists( 'wp_livescore_la_render_sport_select' ) ) {
		wp_livescore_la_render_sport_select( $name, $selected_id, $placeholder );
		return;
	}

	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	?>
	<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text">
		<option value=""><?php echo esc_html( $placeholder ); ?></option>
		<?php foreach ( $posts as $item ) : ?>
			<option value="<?php echo esc_attr( $item->ID ); ?>" <?php selected( $selected_id, $item->ID ); ?>>
				<?php echo esc_html( get_the_title( $item ) ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Render Season term select.
 *
 * @param string $name        Field name.
 * @param int    $selected_id Selected term ID.
 * @param int    $league_id   League post ID.
 */
function wp_livescore_la_render_season_select( $name, $selected_id, $league_id = 0 ) {
	$terms = array();

	if ( $league_id > 0 ) {
		$terms = wp_get_object_terms( $league_id, 'league_season', array( 'hide_empty' => false ) );
	}

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'league_season',
				'hide_empty' => false,
			)
		);
	}
	?>
	<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text">
		<option value=""><?php esc_html_e( 'Select season', 'wp-livescore-la' ); ?></option>
		<?php if ( is_array( $terms ) ) : ?>
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $selected_id, $term->term_id ); ?>>
					<?php echo esc_html( $term->name ); ?>
				</option>
			<?php endforeach; ?>
		<?php endif; ?>
	</select>
	<?php
}

/**
 * Save Team meta.
 *
 * @param int $post_id Team post ID.
 */
function wp_livescore_la_save_team_meta( $post_id ) {
	if ( ! isset( $_POST['wp_livescore_la_team_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_team_meta_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'wp_livescore_la_save_team_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$posted_meta = isset( $_POST['wp_livescore_la_team_meta'] ) && is_array( $_POST['wp_livescore_la_team_meta'] ) ? wp_unslash( $_POST['wp_livescore_la_team_meta'] ) : array();
	foreach ( array( '_team_api_id', '_team_short_name', '_team_logo', '_team_website', '_team_facebook', '_team_instagram', '_team_twitter', '_team_youtube' ) as $meta_key ) {
		$value = isset( $posted_meta[ $meta_key ] ) ? $posted_meta[ $meta_key ] : '';
		$value = in_array( $meta_key, array( '_team_logo', '_team_website', '_team_facebook', '_team_instagram', '_team_twitter', '_team_youtube' ), true ) ? esc_url_raw( $value ) : sanitize_text_field( $value );

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	$status = isset( $_POST['wp_livescore_la_team_status'] ) ? sanitize_key( wp_unslash( $_POST['wp_livescore_la_team_status'] ) ) : 'active';
	update_post_meta( $post_id, '_team_status', in_array( $status, array( 'active', 'inactive' ), true ) ? $status : 'active' );

	wp_livescore_la_sync_team_sport_meta( $post_id, isset( $_POST['wp_livescore_la_team_sport_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_team_sport_id'] ) ) : 0 );
	wp_livescore_la_sync_team_country_meta( $post_id, isset( $_POST['wp_livescore_la_team_country_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_team_country_id'] ) ) : 0 );
}
add_action( 'save_post_team', 'wp_livescore_la_save_team_meta' );

/**
 * Sync Team sport details.
 *
 * @param int $team_id Team ID.
 * @param int $sport_id Sport ID.
 */
function wp_livescore_la_sync_team_sport_meta( $team_id, $sport_id ) {
	$term = $sport_id > 0 ? get_term( $sport_id, 'sport' ) : null;

	if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
		update_post_meta( $team_id, '_team_sport_id', (int) $term->term_id );
		update_post_meta( $team_id, '_team_sport_name', sanitize_text_field( $term->name ) );
		update_post_meta( $team_id, '_team_sport_slug', sanitize_title( $term->slug ) );
		wp_set_object_terms( $team_id, array( (int) $term->term_id ), 'sport', false );
		return;
	}

	delete_post_meta( $team_id, '_team_sport_id' );
	delete_post_meta( $team_id, '_team_sport_name' );
	delete_post_meta( $team_id, '_team_sport_slug' );
}

/**
 * Sync Team country details.
 *
 * @param int $team_id Team ID.
 * @param int $country_id Country ID.
 */
function wp_livescore_la_sync_team_country_meta( $team_id, $country_id ) {
	if ( $country_id > 0 && 'country' === get_post_type( $country_id ) ) {
		update_post_meta( $team_id, '_team_country_id', $country_id );
		update_post_meta( $team_id, '_team_country_name', sanitize_text_field( get_the_title( $country_id ) ) );
		update_post_meta( $team_id, '_team_country_slug', sanitize_title( get_post_field( 'post_name', $country_id ) ) );
		update_post_meta( $team_id, '_team_country_code', sanitize_text_field( get_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_code', true ) ) );
		update_post_meta( $team_id, '_team_continent', sanitize_text_field( get_post_meta( $country_id, WP_LIVESCORE_LA_META_PREFIX . 'country_continent', true ) ) );
		return;
	}

	delete_post_meta( $team_id, '_team_country_id' );
	delete_post_meta( $team_id, '_team_country_name' );
	delete_post_meta( $team_id, '_team_country_slug' );
	delete_post_meta( $team_id, '_team_country_code' );
	delete_post_meta( $team_id, '_team_continent' );
}

/**
 * Get linked Team tag ID.
 *
 * @param int $team_id Team ID.
 * @return int
 */
function get_team_linked_tag_id( $team_id ) {
	return (int) get_post_meta( $team_id, '_linked_post_tag_id', true );
}

/**
 * Sync a Team to a normal WordPress post tag.
 *
 * @param int $team_id Team ID.
 * @return int
 */
function sync_team_to_post_tag( $team_id ) {
	$team = get_post( $team_id );
	if ( ! $team instanceof WP_Post || 'team' !== $team->post_type || in_array( $team->post_status, array( 'auto-draft', 'trash' ), true ) ) {
		return 0;
	}

	$tag_name = sanitize_text_field( $team->post_title );
	$tag_slug = '' !== $team->post_name ? sanitize_title( $team->post_name ) : sanitize_title( $tag_name );

	if ( '' === $tag_name || '' === $tag_slug ) {
		return 0;
	}

	$linked_tag_id = get_team_linked_tag_id( $team_id );
	$linked_tag    = $linked_tag_id > 0 ? get_term( $linked_tag_id, 'post_tag' ) : null;
	$slug_match    = get_term_by( 'slug', $tag_slug, 'post_tag' );

	if ( $linked_tag instanceof WP_Term && ! is_wp_error( $linked_tag ) ) {
		$target_id = $slug_match instanceof WP_Term && (int) $slug_match->term_id !== (int) $linked_tag->term_id ? (int) $slug_match->term_id : (int) $linked_tag->term_id;
		$updated = wp_update_term( $target_id, 'post_tag', array( 'name' => $tag_name, 'slug' => $tag_slug ) );
		if ( ! is_wp_error( $updated ) && isset( $updated['term_id'] ) ) {
			update_post_meta( $team_id, '_linked_post_tag_id', (int) $updated['term_id'] );
			return (int) $updated['term_id'];
		}
	}

	if ( $slug_match instanceof WP_Term ) {
		update_post_meta( $team_id, '_linked_post_tag_id', (int) $slug_match->term_id );
		return (int) $slug_match->term_id;
	}

	$inserted = wp_insert_term( $tag_name, 'post_tag', array( 'slug' => $tag_slug ) );
	if ( is_wp_error( $inserted ) ) {
		$existing_id = (int) $inserted->get_error_data( 'term_exists' );
		if ( $existing_id > 0 ) {
			update_post_meta( $team_id, '_linked_post_tag_id', $existing_id );
			return $existing_id;
		}
		return 0;
	}

	if ( isset( $inserted['term_id'] ) ) {
		update_post_meta( $team_id, '_linked_post_tag_id', (int) $inserted['term_id'] );
		return (int) $inserted['term_id'];
	}

	return 0;
}

/**
 * Sync the Team tag after saves.
 *
 * @param int $post_id Team ID.
 */
function wp_livescore_la_sync_team_tag_on_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	sync_team_to_post_tag( $post_id );
}
add_action( 'save_post_team', 'wp_livescore_la_sync_team_tag_on_save', 20 );

/**
 * Query related posts by Team tag.
 *
 * @param int $team_id Team ID.
 * @return WP_Query
 */
function get_related_posts_by_team_tag( $team_id ) {
	$tag_id = get_team_linked_tag_id( $team_id );
	if ( $tag_id <= 0 ) {
		$tag_id = sync_team_to_post_tag( $team_id );
	}

	if ( $tag_id <= 0 ) {
		return new WP_Query(
			array(
				'post_type' => 'post',
				'post__in' => array( 0 ),
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
 * Add readable Team details to single Team pages.
 *
 * @param string $content Existing post content.
 * @return string
 */
function wp_livescore_la_append_team_details_to_content( $content ) {
	if ( ! is_singular( 'team' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$team_id = get_the_ID();
	$details = array(
		__( 'Sport', 'wp-livescore-la' )   => get_post_meta( $team_id, '_team_sport_name', true ),
		__( 'Country', 'wp-livescore-la' ) => get_post_meta( $team_id, '_team_country_name', true ),
	);

	$socials = array(
		__( 'Website', 'wp-livescore-la' )   => get_post_meta( $team_id, '_team_website', true ),
		__( 'Facebook', 'wp-livescore-la' )  => get_post_meta( $team_id, '_team_facebook', true ),
		__( 'Instagram', 'wp-livescore-la' ) => get_post_meta( $team_id, '_team_instagram', true ),
		__( 'Twitter/X', 'wp-livescore-la' ) => get_post_meta( $team_id, '_team_twitter', true ),
		__( 'YouTube', 'wp-livescore-la' )   => get_post_meta( $team_id, '_team_youtube', true ),
	);

	ob_start();
	?>
	<div class="wp-livescore-la-team-details">
		<?php if ( has_post_thumbnail( $team_id ) ) : ?>
			<div class="wp-livescore-la-team-details__logo"><?php echo get_the_post_thumbnail( $team_id, 'medium' ); ?></div>
		<?php endif; ?>

		<dl class="wp-livescore-la-team-details__list">
			<?php foreach ( $details as $label => $value ) : ?>
				<?php if ( '' !== (string) $value ) : ?>
					<div class="wp-livescore-la-team-details__item">
						<dt><?php echo esc_html( $label ); ?></dt>
						<dd><?php echo esc_html( $value ); ?></dd>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</dl>

		<div class="wp-livescore-la-team-details__links">
			<?php foreach ( $socials as $label => $url ) : ?>
				<?php if ( '' !== (string) $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>" rel="nofollow noopener" target="_blank"><?php echo esc_html( $label ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	return $content . ob_get_clean();
}
add_filter( 'the_content', 'wp_livescore_la_append_team_details_to_content' );

/**
 * Add admin columns for Teams.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function wp_livescore_la_team_admin_columns( $columns ) {
	$columns['team_sport']   = __( 'Sport', 'wp-livescore-la' );
	$columns['team_country'] = __( 'Country', 'wp-livescore-la' );
	$columns['team_api_id']  = __( 'API ID', 'wp-livescore-la' );
	$columns['team_status']  = __( 'Status', 'wp-livescore-la' );

	return $columns;
}
add_filter( 'manage_team_posts_columns', 'wp_livescore_la_team_admin_columns' );

/**
 * Render Team admin column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Team post ID.
 */
function wp_livescore_la_team_admin_column_content( $column, $post_id ) {
	$map = array(
		'team_sport'   => '_team_sport_name',
		'team_country' => '_team_country_name',
		'team_api_id'  => '_team_api_id',
		'team_status'  => '_team_status',
	);

	if ( isset( $map[ $column ] ) ) {
		$value = get_post_meta( $post_id, $map[ $column ], true );
		echo '' !== (string) $value ? esc_html( $value ) : '&mdash;';
	}
}
add_action( 'manage_team_posts_custom_column', 'wp_livescore_la_team_admin_column_content', 10, 2 );

/**
 * Use the plugin Team archive template when the theme does not provide one.
 *
 * @param string $template Current template path.
 * @return string
 */
function wp_livescore_la_team_archive_template( $template ) {
	if ( ! is_post_type_archive( 'team' ) ) {
		return $template;
	}

	if ( function_exists( 'wp_livescore_la_get_astra_site_builder_template' ) ) {
		$astra_template = wp_livescore_la_get_astra_site_builder_template( 'Teams' );
		if ( '' !== $astra_template ) {
			return $astra_template;
		}
	}

	if ( locate_template( array( 'archive-team.php', 'archive-teams.php' ) ) ) {
		return $template;
	}

	$plugin_template = WP_LIVESCORE_LA_DIR . 'templates/archive-teams.php';

	return file_exists( $plugin_template ) ? $plugin_template : $template;
}
add_filter( 'template_include', 'wp_livescore_la_team_archive_template' );

/**
 * Get Team archive filters from the current URL.
 *
 * @return array
 */
function wp_livescore_la_get_team_url_filter_meta_queries() {
	$meta_queries = array();

	if ( isset( $_GET['team_sport'] ) && '' !== $_GET['team_sport'] ) {
		$meta_queries[] = array(
			'key'   => '_team_sport_slug',
			'value' => sanitize_title( wp_unslash( $_GET['team_sport'] ) ),
		);
	}

	if ( isset( $_GET['team_country'] ) && '' !== $_GET['team_country'] ) {
		$meta_queries[] = array(
			'key'   => '_team_country_slug',
			'value' => sanitize_title( wp_unslash( $_GET['team_country'] ) ),
		);
	}

	if ( isset( $_GET['team_league'] ) && '' !== $_GET['team_league'] ) {
		$league_slug = sanitize_title( wp_unslash( $_GET['team_league'] ) );
		$league      = '' !== $league_slug ? get_page_by_path( $league_slug, OBJECT, 'league' ) : null;
		$league_id   = $league instanceof WP_Post ? (int) $league->ID : 0;
		$team_ids    = array();

		if ( $league_id > 0 ) {
			$matches = get_posts(
				array(
					'post_type'      => 'match',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_key'       => '_match_league_id',
					'meta_value'     => $league_id,
				)
			);

			foreach ( $matches as $match_id ) {
				$home_team_id = (int) get_post_meta( $match_id, '_match_home_team_id', true );
				$away_team_id = (int) get_post_meta( $match_id, '_match_away_team_id', true );

				if ( $home_team_id > 0 ) {
					$team_ids[] = $home_team_id;
				}

				if ( $away_team_id > 0 ) {
					$team_ids[] = $away_team_id;
				}
			}
		}

		$team_ids = array_values( array_unique( array_map( 'absint', $team_ids ) ) );
		$GLOBALS['wp_livescore_la_team_archive_post__in'] = ! empty( $team_ids ) ? $team_ids : array( 0 );
	}

	return $meta_queries;
}

/**
 * Apply frontend Team archive filters from the Team Filters block.
 *
 * @param WP_Query $query Query object.
 */
function wp_livescore_la_filter_frontend_team_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$is_team_query = is_post_type_archive( 'team' ) || 'team' === $query->get( 'post_type' );
	if ( ! $is_team_query ) {
		return;
	}

	$meta_query = (array) $query->get( 'meta_query' );
	foreach ( wp_livescore_la_get_team_url_filter_meta_queries() as $filter_query ) {
		$meta_query[] = $filter_query;
	}

	if ( ! empty( $GLOBALS['wp_livescore_la_team_archive_post__in'] ) ) {
		$query->set( 'post__in', array_map( 'absint', (array) $GLOBALS['wp_livescore_la_team_archive_post__in'] ) );
	}

	if ( ! empty( $meta_query ) ) {
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'wp_livescore_la_filter_frontend_team_query' );

/**
 * Find an existing Team by API ID, slug, then title.
 *
 * @param string $api_id API ID.
 * @param string $name Team name.
 * @return int
 */
function wp_livescore_la_find_team_post( $api_id, $name ) {
	if ( '' !== $api_id ) {
		$matches = get_posts(
			array(
				'post_type'      => 'team',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => '_team_api_id',
				'meta_value'     => sanitize_text_field( $api_id ),
			)
		);
		if ( ! empty( $matches ) ) {
			return (int) $matches[0];
		}
	}

	$post = get_page_by_path( sanitize_title( $name ), OBJECT, 'team' );
	if ( $post instanceof WP_Post ) {
		return (int) $post->ID;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'team',
			'post_status'    => 'any',
			'title'          => sanitize_text_field( $name ),
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);

	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Extract Team records from common API response shapes.
 *
 * @param mixed $payload Decoded JSON payload.
 * @return array
 */
function wp_livescore_la_extract_team_records( $payload ) {
	if ( ! is_array( $payload ) ) {
		return array();
	}

	foreach ( array( 'teams', 'team', 'results', 'data', 'items' ) as $key ) {
		if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
			return wp_livescore_la_extract_team_records( $payload[ $key ] );
		}
	}

	if ( ! wp_livescore_la_is_list( $payload ) ) {
		$record = wp_livescore_la_normalize_team_record( $payload );
		return empty( $record ) ? array() : array( $record );
	}

	$records = array();
	foreach ( $payload as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$record = wp_livescore_la_normalize_team_record( $item );
		if ( ! empty( $record ) ) {
			$records[] = $record;
		}
	}

	return $records;
}

/**
 * Normalize direct or wrapped Team API records into the shared Team shape.
 *
 * @param array $item API item.
 * @return array
 */
function wp_livescore_la_normalize_team_record( $item ) {
	if ( isset( $item['entity'] ) && is_array( $item['entity'] ) ) {
		$entity = $item['entity'];
		if ( isset( $item['type'] ) ) {
			$entity['type'] = $item['type'];
		}
		$item = $entity;
	}

	if ( isset( $item['team'] ) && is_array( $item['team'] ) ) {
		$item = array_merge( $item['team'], $item );
		unset( $item['team'] );
	}

	$category = isset( $item['category'] ) && is_array( $item['category'] ) ? $item['category'] : array();
	$sport    = isset( $item['sport'] ) && is_array( $item['sport'] ) ? $item['sport'] : array();

	if ( empty( $sport ) && isset( $category['sport'] ) && is_array( $category['sport'] ) ) {
		$sport = $category['sport'];
	}

	$name = wp_livescore_la_record_value( $item, array( 'strTeam', 'name', 'title', 'team_name', 'Team' ) );
	if ( '' === $name ) {
		return array();
	}

	if ( ! empty( $sport ) && empty( $item['sport'] ) && isset( $sport['name'] ) ) {
		$item['sport'] = $sport['name'];
	}

	if ( ! empty( $category ) && empty( $item['country'] ) && isset( $category['name'] ) ) {
		$item['country'] = $category['name'];
	}

	if ( ! empty( $category ) && empty( $item['country_code'] ) && isset( $category['alpha2'] ) ) {
		$item['country_code'] = $category['alpha2'];
	}

	return $item;
}

/**
 * Import or update Team posts from API records.
 *
 * @param array  $records API records.
 * @param string $api_source API source.
 * @return array
 */
function wp_livescore_la_import_teams( $records, $api_source = '' ) {
	$result = array( 'created' => 0, 'updated' => 0, 'skipped' => 0 );

	foreach ( $records as $record ) {
		if ( ! is_array( $record ) ) {
			$result['skipped']++;
			continue;
		}

		$name = wp_livescore_la_record_value( $record, array( 'strTeam', 'name', 'title', 'team_name', 'Team' ) );
		if ( '' === $name ) {
			$result['skipped']++;
			continue;
		}

		$api_id  = wp_livescore_la_record_value( $record, array( 'idTeam', 'api_id', 'id', 'team_id' ) );
		$post_id = wp_livescore_la_find_team_post( $api_id, $name );
		$content = wp_livescore_la_record_value( $record, array( 'strDescriptionEN', 'description', 'desc', 'content' ) );

		$post_data = array(
			'post_type'    => 'team',
			'post_status'  => 'publish',
			'post_title'   => sanitize_text_field( $name ),
			'post_content' => wp_kses_post( $content ),
		);

		$slug = wp_livescore_la_record_value( $record, array( 'slug', 'strSlug', 'post_name' ) );
		if ( '' !== $slug ) {
			$post_data['post_name'] = sanitize_title( $slug );
		}

		if ( $post_id > 0 ) {
			$post_data['ID'] = $post_id;
			$saved_id = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$saved_id = wp_insert_post( wp_slash( $post_data ), true );
		}

		if ( is_wp_error( $saved_id ) || $saved_id <= 0 ) {
			$result['skipped']++;
			continue;
		}

		update_post_meta( $saved_id, '_team_status', 'active' );
		if ( '' !== $api_id ) {
			update_post_meta( $saved_id, '_team_api_id', sanitize_text_field( $api_id ) );
		}
		if ( '' !== $api_source ) {
			update_post_meta( $saved_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', sanitize_key( $api_source ) );
		}

		wp_livescore_la_update_team_import_meta( $saved_id, $record );
		sync_team_to_post_tag( $saved_id );

		$logo = wp_livescore_la_record_value( $record, array( 'strTeamBadge', 'badge', 'logo', 'image', 'image_url' ) );
		if ( '' !== $logo ) {
			update_post_meta( $saved_id, '_team_logo', esc_url_raw( $logo ) );
			wp_livescore_la_set_featured_image_from_url( $saved_id, $logo );
		}

		if ( $post_id > 0 ) {
			$result['updated']++;
		} else {
			$result['created']++;
		}
	}

	return $result;
}

/**
 * Update Team meta from import record.
 *
 * @param int $team_id Team ID.
 * @param array $record API record.
 */
function wp_livescore_la_update_team_import_meta( $team_id, $record ) {
	$urls = array(
		'_team_website'   => array( 'strWebsite', 'website', 'url' ),
		'_team_facebook'  => array( 'strFacebook', 'facebook' ),
		'_team_instagram' => array( 'strInstagram', 'instagram' ),
		'_team_twitter'   => array( 'strTwitter', 'twitter' ),
		'_team_youtube'   => array( 'strYoutube', 'youtube' ),
	);

	$short_name = wp_livescore_la_record_value( $record, array( 'strTeamShort', 'strShortName', 'short_name', 'shortName', 'abbreviation', 'abbr', 'code' ) );
	if ( '' !== $short_name ) {
		update_post_meta( $team_id, '_team_short_name', sanitize_text_field( $short_name ) );
	}

	foreach ( $urls as $meta_key => $keys ) {
		$value = wp_livescore_la_record_value( $record, $keys );
		if ( '' !== $value ) {
			update_post_meta( $team_id, $meta_key, esc_url_raw( $value ) );
		}
	}

	$sport_value = wp_livescore_la_record_value( $record, array( 'strSport', 'sports', 'sport', 'Sport' ) );
	$sport_id = wp_livescore_la_get_or_create_sport_id( $sport_value );
	wp_livescore_la_sync_team_sport_meta( $team_id, $sport_id );

	$country_value = wp_livescore_la_record_value( $record, array( 'strCountry', 'country', 'Country' ) );
	$country_code = wp_livescore_la_record_value( $record, array( 'strCountryCode', 'country_code', 'countryCode', 'code', 'CountryCode' ) );
	$country_id = wp_livescore_la_get_or_create_country_id( $country_value, $country_code );
	wp_livescore_la_sync_team_country_meta( $team_id, $country_id );
}
