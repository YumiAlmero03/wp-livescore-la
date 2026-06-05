<?php
/**
 * Player Profile custom post type and Team relationship fields.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Player Profile custom post type.
 */
function wp_livescore_la_register_player_post_type() {
	register_post_type(
		'player',
		array(
			'labels'              => array(
				'name'               => __( 'Player Profiles', 'wp-livescore-la' ),
				'singular_name'      => __( 'Player Profile', 'wp-livescore-la' ),
				'menu_name'          => __( 'Player Profiles', 'wp-livescore-la' ),
				'add_new_item'       => __( 'Add New Player Profile', 'wp-livescore-la' ),
				'edit_item'          => __( 'Edit Player Profile', 'wp-livescore-la' ),
				'all_items'          => __( 'All Player Profiles', 'wp-livescore-la' ),
				'new_item'           => __( 'New Player Profile', 'wp-livescore-la' ),
				'view_item'          => __( 'View Player Profile', 'wp-livescore-la' ),
				'search_items'       => __( 'Search Player Profiles', 'wp-livescore-la' ),
				'not_found'          => __( 'No player profiles found.', 'wp-livescore-la' ),
				'not_found_in_trash' => __( 'No player profiles found in Trash.', 'wp-livescore-la' ),
			),
			'public'              => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wp-livescore-la-sports-manager',
			'show_in_rest'        => true,
			'has_archive'         => 'players',
			'rewrite'             => array(
				'slug'       => 'players',
				'with_front' => false,
			),
			'menu_icon'           => 'dashicons-id',
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
		)
	);
}
add_action( 'init', 'wp_livescore_la_register_player_post_type' );

/**
 * Player meta keys and labels.
 *
 * @return array
 */
function wp_livescore_la_player_meta_fields() {
	return array(
		'_player_api_id'    => __( 'API ID', 'wp-livescore-la' ),
		'_player_sport_id'  => __( 'Sport ID', 'wp-livescore-la' ),
		'_player_sport_name'=> __( 'Sport Name', 'wp-livescore-la' ),
		'_player_sport_slug'=> __( 'Sport Slug', 'wp-livescore-la' ),
		'_player_team_id'   => __( 'Team ID', 'wp-livescore-la' ),
		'_player_team_name' => __( 'Team Name', 'wp-livescore-la' ),
		'_player_team_slug' => __( 'Team Slug', 'wp-livescore-la' ),
		'_player_country'   => __( 'Country', 'wp-livescore-la' ),
		'_player_birthday'  => __( 'Birthday', 'wp-livescore-la' ),
		'_player_foot'      => __( 'Preferred Foot', 'wp-livescore-la' ),
		'_player_height'    => __( 'Height', 'wp-livescore-la' ),
		'_player_weight'    => __( 'Weight', 'wp-livescore-la' ),
		'_player_gender'    => __( 'Gender', 'wp-livescore-la' ),
		'_player_position'  => __( 'Position', 'wp-livescore-la' ),
		'_player_number'    => __( 'Jersey Number', 'wp-livescore-la' ),
		'_player_status'    => __( 'Status', 'wp-livescore-la' ),
	);
}

/**
 * Format short player position codes for display.
 *
 * @param string $position Raw position value.
 * @return string
 */
function wp_livescore_la_format_player_position( $position ) {
	$position = trim( (string) $position );

	if ( '' === $position ) {
		return '';
	}

	$positions = array(
		'G'   => __( 'Goalkeeper', 'wp-livescore-la' ),
		'GK'  => __( 'Goalkeeper', 'wp-livescore-la' ),
		'D'   => __( 'Defender', 'wp-livescore-la' ),
		'DF'  => __( 'Defender', 'wp-livescore-la' ),
		'DEF' => __( 'Defender', 'wp-livescore-la' ),
		'RB'  => __( 'Right Back', 'wp-livescore-la' ),
		'RWB' => __( 'Right Wing Back', 'wp-livescore-la' ),
		'LB'  => __( 'Left Back', 'wp-livescore-la' ),
		'LWB' => __( 'Left Wing Back', 'wp-livescore-la' ),
		'CB'  => __( 'Centre Back', 'wp-livescore-la' ),
		'RCB' => __( 'Right Centre Back', 'wp-livescore-la' ),
		'LCB' => __( 'Left Centre Back', 'wp-livescore-la' ),
		'WB'  => __( 'Wing Back', 'wp-livescore-la' ),
		'M'   => __( 'Midfielder', 'wp-livescore-la' ),
		'MF'  => __( 'Midfielder', 'wp-livescore-la' ),
		'MID' => __( 'Midfielder', 'wp-livescore-la' ),
		'DM'  => __( 'Defensive Midfielder', 'wp-livescore-la' ),
		'CDM' => __( 'Defensive Midfielder', 'wp-livescore-la' ),
		'CM'  => __( 'Central Midfielder', 'wp-livescore-la' ),
		'AM'  => __( 'Attacking Midfielder', 'wp-livescore-la' ),
		'CAM' => __( 'Attacking Midfielder', 'wp-livescore-la' ),
		'RM'  => __( 'Right Midfielder', 'wp-livescore-la' ),
		'LM'  => __( 'Left Midfielder', 'wp-livescore-la' ),
		'F'   => __( 'Forward', 'wp-livescore-la' ),
		'FW'  => __( 'Forward', 'wp-livescore-la' ),
		'ATT' => __( 'Forward', 'wp-livescore-la' ),
		'RW'  => __( 'Right Winger', 'wp-livescore-la' ),
		'LW'  => __( 'Left Winger', 'wp-livescore-la' ),
		'ST'  => __( 'Striker', 'wp-livescore-la' ),
		'CF'  => __( 'Centre Forward', 'wp-livescore-la' ),
	);

	$format_token = function ( $value ) use ( $positions ) {
		$value      = trim( (string) $value );
		$normalized = strtoupper( preg_replace( '/[^A-Za-z0-9]+/', '', $value ) );

		return isset( $positions[ $normalized ] ) ? $positions[ $normalized ] : $value;
	};

	$parts = preg_split( '/(\s*[,\/|]\s*)/', $position, -1, PREG_SPLIT_DELIM_CAPTURE );

	if ( is_array( $parts ) && count( $parts ) > 1 ) {
		$formatted = '';

		foreach ( $parts as $part ) {
			$formatted .= preg_match( '/^\s*[,\/|]\s*$/', $part ) ? trim( $part ) . ' ' : $format_token( $part );
		}

		return trim( $formatted );
	}

	return $format_token( $position );
}

/**
 * Register Player meta fields.
 */
function wp_livescore_la_register_player_meta() {
	foreach ( wp_livescore_la_player_meta_fields() as $meta_key => $label ) {
		$is_id = in_array( $meta_key, array( '_player_team_id', '_player_sport_id' ), true );

		register_post_meta(
			'player',
			$meta_key,
			array(
				'type'              => $is_id ? 'integer' : 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $is_id ? 'absint' : 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'wp_livescore_la_register_player_meta' );

/**
 * Add the Player Profile meta box.
 */
function wp_livescore_la_add_player_meta_box() {
	add_meta_box(
		'wp-livescore-la-player-profile',
		__( 'Player Profile', 'wp-livescore-la' ),
		'wp_livescore_la_render_player_meta_box',
		'player',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_player', 'wp_livescore_la_add_player_meta_box' );

/**
 * Render the Player Profile meta box.
 *
 * @param WP_Post $post Player post.
 */
function wp_livescore_la_render_player_meta_box( $post ) {
	wp_nonce_field( 'wp_livescore_la_save_player_meta', 'wp_livescore_la_player_meta_nonce' );

	$team_id = (int) get_post_meta( $post->ID, '_player_team_id', true );
	$sport_id = (int) get_post_meta( $post->ID, '_player_sport_id', true );
	$status  = get_post_meta( $post->ID, '_player_status', true );
	$status  = '' !== $status ? $status : 'active';
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="wp_livescore_la_player_sport_id"><?php esc_html_e( 'Sport', 'wp-livescore-la' ); ?></label></th>
				<td><?php wp_livescore_la_render_sport_select( 'wp_livescore_la_player_sport_id', $sport_id, __( 'Select sport', 'wp-livescore-la' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_player_team_id"><?php esc_html_e( 'Team', 'wp-livescore-la' ); ?></label></th>
				<td><?php wp_livescore_la_render_post_select( 'wp_livescore_la_player_team_id', 'team', $team_id, __( 'Select team', 'wp-livescore-la' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="wp_livescore_la_player_status"><?php esc_html_e( 'Status', 'wp-livescore-la' ); ?></label></th>
				<td>
					<select id="wp_livescore_la_player_status" name="wp_livescore_la_player_status">
						<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'wp-livescore-la' ); ?></option>
						<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wp-livescore-la' ); ?></option>
					</select>
				</td>
			</tr>
			<?php foreach ( array( '_player_api_id', '_player_country', '_player_birthday', '_player_foot', '_player_height', '_player_weight', '_player_gender', '_player_position', '_player_number' ) as $meta_key ) : ?>
				<?php $value = get_post_meta( $post->ID, $meta_key, true ); ?>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( wp_livescore_la_player_meta_fields()[ $meta_key ] ); ?></label></th>
					<td>
						<input
							type="<?php echo '_player_birthday' === $meta_key ? 'date' : 'text'; ?>"
							id="<?php echo esc_attr( $meta_key ); ?>"
							name="wp_livescore_la_player_meta[<?php echo esc_attr( $meta_key ); ?>]"
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
 * Save Player Profile meta.
 *
 * @param int $post_id Player post ID.
 */
function wp_livescore_la_save_player_meta( $post_id ) {
	if ( ! isset( $_POST['wp_livescore_la_player_meta_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_livescore_la_player_meta_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'wp_livescore_la_save_player_meta' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$posted_meta = isset( $_POST['wp_livescore_la_player_meta'] ) && is_array( $_POST['wp_livescore_la_player_meta'] ) ? wp_unslash( $_POST['wp_livescore_la_player_meta'] ) : array();

	foreach ( array( '_player_api_id', '_player_country', '_player_birthday', '_player_foot', '_player_height', '_player_weight', '_player_gender', '_player_position', '_player_number' ) as $meta_key ) {
		$value = isset( $posted_meta[ $meta_key ] ) ? sanitize_text_field( $posted_meta[ $meta_key ] ) : '';

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	$status = isset( $_POST['wp_livescore_la_player_status'] ) ? sanitize_key( wp_unslash( $_POST['wp_livescore_la_player_status'] ) ) : 'active';
	update_post_meta( $post_id, '_player_status', in_array( $status, array( 'active', 'inactive' ), true ) ? $status : 'active' );

	$team_id = isset( $_POST['wp_livescore_la_player_team_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_player_team_id'] ) ) : 0;
	wp_livescore_la_sync_player_team_meta( $post_id, $team_id );

	$sport_id = isset( $_POST['wp_livescore_la_player_sport_id'] ) ? absint( wp_unslash( $_POST['wp_livescore_la_player_sport_id'] ) ) : 0;
	wp_livescore_la_sync_player_sport_meta( $post_id, $sport_id );
}
add_action( 'save_post_player', 'wp_livescore_la_save_player_meta' );

/**
 * Sync a Player's Sport relationship metadata.
 *
 * @param int $player_id Player post ID.
 * @param int $sport_id  Sport term ID.
 */
function wp_livescore_la_sync_player_sport_meta( $player_id, $sport_id ) {
	$term = $sport_id > 0 ? get_term( $sport_id, 'sport' ) : null;

	if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
		update_post_meta( $player_id, '_player_sport_id', (int) $term->term_id );
		update_post_meta( $player_id, '_player_sport_name', sanitize_text_field( $term->name ) );
		update_post_meta( $player_id, '_player_sport_slug', sanitize_title( $term->slug ) );
		wp_set_object_terms( $player_id, array( (int) $term->term_id ), 'sport', false );
		return;
	}

	delete_post_meta( $player_id, '_player_sport_id' );
	delete_post_meta( $player_id, '_player_sport_name' );
	delete_post_meta( $player_id, '_player_sport_slug' );
}

/**
 * Sync a Player's Team relationship metadata.
 *
 * @param int $player_id Player post ID.
 * @param int $team_id   Team post ID.
 */
function wp_livescore_la_sync_player_team_meta( $player_id, $team_id ) {
	if ( $team_id > 0 && 'team' === get_post_type( $team_id ) ) {
		update_post_meta( $player_id, '_player_team_id', $team_id );
		update_post_meta( $player_id, '_player_team_name', sanitize_text_field( get_the_title( $team_id ) ) );
		update_post_meta( $player_id, '_player_team_slug', sanitize_title( get_post_field( 'post_name', $team_id ) ) );
		return;
	}

	delete_post_meta( $player_id, '_player_team_id' );
	delete_post_meta( $player_id, '_player_team_name' );
	delete_post_meta( $player_id, '_player_team_slug' );
}

/**
 * Append basic Player Profile details on single Player pages.
 *
 * @param string $content Player content.
 * @return string
 */
function wp_livescore_la_append_player_details_to_content( $content ) {
	if ( ! is_singular( 'player' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$player_id = get_the_ID();
	$team_id   = (int) get_post_meta( $player_id, '_player_team_id', true );
	$team_name = get_post_meta( $player_id, '_player_team_name', true );
	$team_url  = $team_id > 0 && 'team' === get_post_type( $team_id ) ? get_permalink( $team_id ) : '';
	$fields    = array(
		__( 'Sport', 'wp-livescore-la' )         => get_post_meta( $player_id, '_player_sport_name', true ),
		__( 'Country', 'wp-livescore-la' )       => get_post_meta( $player_id, '_player_country', true ),
		__( 'Birthday', 'wp-livescore-la' )      => get_post_meta( $player_id, '_player_birthday', true ),
		__( 'Preferred Foot', 'wp-livescore-la' )=> get_post_meta( $player_id, '_player_foot', true ),
		__( 'Height', 'wp-livescore-la' )        => get_post_meta( $player_id, '_player_height', true ),
		__( 'Weight', 'wp-livescore-la' )        => get_post_meta( $player_id, '_player_weight', true ),
		__( 'Gender', 'wp-livescore-la' )        => get_post_meta( $player_id, '_player_gender', true ),
		__( 'Position', 'wp-livescore-la' )      => wp_livescore_la_format_player_position( get_post_meta( $player_id, '_player_position', true ) ),
		__( 'Jersey Number', 'wp-livescore-la' ) => get_post_meta( $player_id, '_player_number', true ),
	);

	ob_start();
	?>
	<div class="wp-livescore-la-player-profile">
		<?php if ( '' !== $team_name ) : ?>
			<p class="wp-livescore-la-player-profile__team">
				<strong><?php esc_html_e( 'Team:', 'wp-livescore-la' ); ?></strong>
				<?php if ( $team_url ) : ?>
					<a href="<?php echo esc_url( $team_url ); ?>"><?php echo esc_html( $team_name ); ?></a>
				<?php else : ?>
					<span><?php echo esc_html( $team_name ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<dl class="wp-livescore-la-player-profile__details">
			<?php foreach ( $fields as $label => $value ) : ?>
				<?php if ( '' !== (string) $value ) : ?>
					<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
				<?php endif; ?>
			<?php endforeach; ?>
		</dl>
	</div>
	<?php

	return $content . ob_get_clean();
}
add_filter( 'the_content', 'wp_livescore_la_append_player_details_to_content' );

/**
 * Add Player admin columns.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function wp_livescore_la_player_admin_columns( $columns ) {
	$columns['player_team']     = __( 'Team', 'wp-livescore-la' );
	$columns['player_sport']    = __( 'Sport', 'wp-livescore-la' );
	$columns['player_position'] = __( 'Position', 'wp-livescore-la' );
	$columns['player_number']   = __( 'Number', 'wp-livescore-la' );
	$columns['player_status']   = __( 'Status', 'wp-livescore-la' );
	$columns['player_api_id']   = __( 'API ID', 'wp-livescore-la' );

	return $columns;
}
add_filter( 'manage_player_posts_columns', 'wp_livescore_la_player_admin_columns' );

/**
 * Render Player admin column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Player post ID.
 */
function wp_livescore_la_player_admin_column_content( $column, $post_id ) {
	$values = array(
		'player_team'     => get_post_meta( $post_id, '_player_team_name', true ),
		'player_sport'    => get_post_meta( $post_id, '_player_sport_name', true ),
		'player_position' => wp_livescore_la_format_player_position( get_post_meta( $post_id, '_player_position', true ) ),
		'player_number'   => get_post_meta( $post_id, '_player_number', true ),
		'player_status'   => get_post_meta( $post_id, '_player_status', true ),
		'player_api_id'   => get_post_meta( $post_id, '_player_api_id', true ),
	);

	if ( isset( $values[ $column ] ) ) {
		echo '' !== trim( (string) $values[ $column ] ) ? esc_html( $values[ $column ] ) : '&mdash;';
	}
}
add_action( 'manage_player_posts_custom_column', 'wp_livescore_la_player_admin_column_content', 10, 2 );

/**
 * Use the plugin Player archive template when the theme does not provide one.
 *
 * @param string $template Current template path.
 * @return string
 */
function wp_livescore_la_player_archive_template( $template ) {
	if ( ! is_post_type_archive( 'player' ) ) {
		return $template;
	}

	if ( function_exists( 'wp_livescore_la_get_astra_site_builder_template' ) ) {
		$astra_template = wp_livescore_la_get_astra_site_builder_template( 'Players' );
		if ( '' !== $astra_template ) {
			return $astra_template;
		}
	}

	if ( locate_template( array( 'archive-player.php', 'archive-players.php' ) ) ) {
		return $template;
	}

	$plugin_template = WP_LIVESCORE_LA_DIR . 'templates/archive-player.php';

	return file_exists( $plugin_template ) ? $plugin_template : $template;
}
add_filter( 'template_include', 'wp_livescore_la_player_archive_template' );

/**
 * Find an existing Player by API ID, then by Team and title.
 *
 * @param string $api_id  Player API ID.
 * @param string $name    Player name.
 * @param int    $team_id Team post ID.
 * @return int
 */
function wp_livescore_la_find_player_post( $api_id, $name, $team_id = 0 ) {
	if ( '' !== $api_id ) {
		$players = get_posts(
			array(
				'post_type'      => 'player',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => '_player_api_id',
				'meta_value'     => sanitize_text_field( $api_id ),
			)
		);

		if ( ! empty( $players ) ) {
			return (int) $players[0];
		}
	}

	if ( '' === $name ) {
		return 0;
	}

	$args = array(
		'post_type'      => 'player',
		'post_status'    => 'any',
		'fields'         => 'ids',
		'posts_per_page' => 1,
		'title'          => sanitize_text_field( $name ),
	);

	if ( $team_id > 0 ) {
		$args['meta_query'] = array(
			array(
				'key'   => '_player_team_id',
				'value' => $team_id,
			),
		);
	}

	$players = get_posts( $args );

	return ! empty( $players ) ? (int) $players[0] : 0;
}

/**
 * Import or update Player Profile posts.
 *
 * @param array  $records    Player records.
 * @param int    $team_id    Team post ID.
 * @param string $api_source Import provider.
 * @return array
 */
function wp_livescore_la_import_players( $records, $team_id = 0, $api_source = '' ) {
	$result = array( 'created' => 0, 'updated' => 0, 'skipped' => 0 );

	foreach ( $records as $record ) {
		if ( ! is_array( $record ) ) {
			$result['skipped']++;
			continue;
		}

		$name = wp_livescore_la_record_value( $record, array( 'name', 'player_name', 'title' ) );
		if ( '' === $name ) {
			$result['skipped']++;
			continue;
		}

		$api_id              = wp_livescore_la_record_value( $record, array( 'api_id', 'id', 'player_id' ) );
		$is_sofascore_import = 'sofascore' === sanitize_key( $api_source );
		$post_id             = wp_livescore_la_find_player_post( $is_sofascore_import ? '' : $api_id, $name, $team_id );
		$is_update           = $post_id > 0;
		if ( $is_sofascore_import && ! $is_update ) {
			$result['skipped']++;
			continue;
		}

		$post    = array(
			'post_type'   => 'player',
			'post_status' => 'publish',
			'post_title'  => sanitize_text_field( $name ),
		);

		if ( $post_id > 0 ) {
			$post['ID'] = $post_id;
			$saved_id   = wp_update_post( wp_slash( $post ), true );
		} else {
			$saved_id = wp_insert_post( wp_slash( $post ), true );
		}

		if ( is_wp_error( $saved_id ) || $saved_id <= 0 ) {
			$result['skipped']++;
			continue;
		}

		update_post_meta( $saved_id, '_player_status', 'active' );

		if ( '' !== $api_id && ! ( $is_sofascore_import && $is_update ) ) {
			update_post_meta( $saved_id, '_player_api_id', sanitize_text_field( $api_id ) );
		}

		if ( '' !== $api_source ) {
			update_post_meta( $saved_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', sanitize_key( $api_source ) );
		}

			wp_livescore_la_sync_player_team_meta( $saved_id, $team_id );
			if ( $team_id > 0 ) {
				wp_livescore_la_sync_player_sport_meta( $saved_id, (int) get_post_meta( $team_id, '_team_sport_id', true ) );
			}

		$map = array(
			'_player_country'  => array( 'country' ),
			'_player_birthday' => array( 'birthday', 'dateOfBirth' ),
			'_player_foot'     => array( 'preferred_foot', 'preferredFoot' ),
			'_player_height'   => array( 'height' ),
			'_player_weight'   => array( 'weight' ),
			'_player_gender'   => array( 'gender' ),
			'_player_position' => array( 'position' ),
			'_player_number'   => array( 'jersey', 'jerseyNumber' ),
		);

		foreach ( $map as $meta_key => $keys ) {
			$value = wp_livescore_la_record_value( $record, $keys );
			if ( '' !== $value ) {
				update_post_meta( $saved_id, $meta_key, sanitize_text_field( $value ) );
			}
		}

		if ( $post_id > 0 ) {
			$result['updated']++;
		} else {
			$result['created']++;
		}
	}

	return $result;
}
