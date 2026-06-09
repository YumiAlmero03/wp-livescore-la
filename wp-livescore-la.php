<?php
/**
 * Plugin Name:       WP Livescore by LA
 * Description:       Lists sports categories and imports livescore custom posts from a configured API.
 * Version:           1.2.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Lailanie Almero
 * License:           GPL-2.0-or-later
 * Text Domain:       wp-livescore-la
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_LIVESCORE_LA_VERSION', '1.2.1' );
define( 'WP_LIVESCORE_LA_FILE', __FILE__ );
define( 'WP_LIVESCORE_LA_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_LIVESCORE_LA_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_LIVESCORE_LA_OPTION', 'wp_livescore_la_settings' );
define( 'WP_LIVESCORE_LA_META_PREFIX', '_wp_livescore_la_' );
define( 'WP_LIVESCORE_LA_LOG_FILE', WP_CONTENT_DIR . '/livescore.log' );

/**
 * Write a plugin log entry.
 *
 * @param string $source  Log source.
 * @param string $message Log message.
 * @param array  $context Extra context.
 * @param string $level   Log level.
 */
function wp_livescore_la_log( $source, $message, $context = array(), $level = 'error' ) {
	$source  = sanitize_key( $source );
	$level   = sanitize_key( $level );
	$message = sanitize_text_field( (string) $message );
	$context = is_array( $context ) ? $context : array();

	if ( '' === $source ) {
		$source = 'general';
	}

	if ( '' === $level ) {
		$level = 'error';
	}

	$entry = array(
		'time'    => current_time( 'mysql' ),
		'level'   => $level,
		'source'  => $source,
		'message' => $message,
		'context' => $context,
	);

	$line = wp_json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $line ) {
		$line = '[' . current_time( 'mysql' ) . '] ' . strtoupper( $level ) . ' ' . $source . ' ' . $message;
	}

	$log_dir = dirname( WP_LIVESCORE_LA_LOG_FILE );
	if ( ! is_dir( $log_dir ) || ! wp_is_writable( $log_dir ) ) {
		return;
	}

	file_put_contents( WP_LIVESCORE_LA_LOG_FILE, $line . PHP_EOL, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}

/**
 * Write a WP_Error to the plugin log.
 *
 * @param string   $source  Log source.
 * @param WP_Error $error   Error object.
 * @param array    $context Extra context.
 */
function wp_livescore_la_log_wp_error( $source, $error, $context = array() ) {
	if ( ! is_wp_error( $error ) ) {
		return;
	}

	wp_livescore_la_log(
		$source,
		$error->get_error_message(),
		array_merge(
			$context,
			array(
				'code' => $error->get_error_code(),
				'data' => $error->get_error_data(),
			)
		)
	);
}

/**
 * Load translations.
 */
function wp_livescore_la_load_textdomain() {
	load_plugin_textdomain( 'wp-livescore-la', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wp_livescore_la_load_textdomain' );

/**
 * Get a stored plugin setting.
 *
 * @param string $key     Setting key.
 * @param string $default Fallback value.
 * @return string
 */
function wp_livescore_la_get_setting( $key, $default = '' ) {
	$options = get_option( WP_LIVESCORE_LA_OPTION, array() );
	return isset( $options[ $key ] ) ? (string) $options[ $key ] : $default;
}

/**
 * Clean one API URL segment.
 *
 * @param string $segment Raw segment.
 * @return string
 */
function wp_livescore_la_clean_path_segment( $segment ) {
	$segment = trim( (string) $segment, " \t\n\r\0\x0B/" );
	return sanitize_text_field( $segment );
}

/**
 * Determine whether an array is a list while staying compatible with PHP 7.4.
 *
 * @param array $value Array to inspect.
 * @return bool
 */
function wp_livescore_la_is_list( $value ) {
	if ( array() === $value ) {
		return true;
	}

	return array_keys( $value ) === range( 0, count( $value ) - 1 );
}

/**
 * Build an API endpoint from configured or provided values.
 *
 * @param string $api_link API base URL.
 * @param string $api_key  API key.
 * @param string $slug     Endpoint slug.
 * @return string
 */
function wp_livescore_la_build_api_url( $api_link, $api_key, $slug ) {
	$api_link = esc_url_raw( trim( (string) $api_link ) );
	$api_key  = wp_livescore_la_clean_path_segment( $api_key );
	$slug     = wp_livescore_la_clean_path_segment( $slug );

	if ( '' === $api_link || '' === $api_key || '' === $slug ) {
		return '';
	}

	return untrailingslashit( $api_link ) . '/' . rawurlencode( $api_key ) . '/' . rawurlencode( $slug );
}

/**
 * Mask the API key for admin previews.
 *
 * @param string $api_key API key.
 * @return string
 */
function wp_livescore_la_mask_api_key( $api_key ) {
	$api_key = (string) $api_key;
	$length  = strlen( $api_key );

	if ( 0 === $length ) {
		return '';
	}

	if ( $length <= 6 ) {
		return str_repeat( '*', $length );
	}

	return substr( $api_key, 0, 4 ) . str_repeat( '*', max( 4, $length - 6 ) ) . substr( $api_key, -2 );
}

/**
 * Build shared placeholder markup for missing images.
 *
 * @param string $class Image-specific placeholder class.
 * @param string $label Accessible/visible placeholder label.
 * @return string
 */
function wp_livescore_la_get_image_placeholder( $class = '', $label = '' ) {
	$classes = preg_split( '/\s+/', trim( (string) $class ) );
	$classes = array_filter( array_map( 'sanitize_html_class', is_array( $classes ) ? $classes : array() ) );
	array_unshift( $classes, 'wp-livescore-la-image-placeholder' );
	$class = trim( implode( ' ', array_unique( $classes ) ) );
	$label = '' !== trim( (string) $label ) ? sanitize_text_field( $label ) : __( 'Image unavailable', 'wp-livescore-la' );

	return sprintf(
		'<div class="%1$s" role="img" aria-label="%2$s"><span>%3$s</span></div>',
		esc_attr( $class ),
		esc_attr( $label ),
		esc_html( $label )
	);
}

/**
 * Render archive pagination using /page/{number}/ URLs.
 */
function wp_livescore_la_the_archive_pagination() {
	global $wp_query;

	$total = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 0;

	if ( $total <= 1 ) {
		return;
	}

	$current = max( 1, (int) get_query_var( 'paged' ) );
	$post_type = get_query_var( 'post_type' );
	$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
	$base_url  = '' !== (string) $post_type ? get_post_type_archive_link( sanitize_key( $post_type ) ) : '';
	$base_url  = $base_url ? $base_url : strtok( get_pagenum_link( 1 ), '?' );
	$base      = trailingslashit( $base_url ) . '%_%';
	$add_args = array();

	foreach ( $_GET as $key => $value ) {
		$key = sanitize_key( wp_unslash( $key ) );

		if ( '' === $key || 'paged' === $key ) {
			continue;
		}

		$add_args[ $key ] = is_array( $value )
			? array_map( 'sanitize_text_field', wp_unslash( $value ) )
			: sanitize_text_field( wp_unslash( $value ) );
	}

	$links = paginate_links(
		array(
			'base'      => $base,
			'format'    => 'page/%#%/',
			'current'   => $current,
			'total'     => $total,
			'add_args'  => $add_args,
			'prev_text' => __( 'Previous', 'wp-livescore-la' ),
			'next_text' => __( 'Next', 'wp-livescore-la' ),
			'type'      => 'list',
		)
	);

	if ( '' !== (string) $links ) {
		echo '<nav class="navigation pagination wp-livescore-la-pagination" aria-label="' . esc_attr__( 'Posts pagination', 'wp-livescore-la' ) . '">';
		echo wp_kses_post( $links );
		echo '</nav>';
	}
}

require_once WP_LIVESCORE_LA_DIR . 'posts/sport.php';
require_once WP_LIVESCORE_LA_DIR . 'posts/country.php';
require_once WP_LIVESCORE_LA_DIR . 'posts/league.php';
require_once WP_LIVESCORE_LA_DIR . 'posts/team.php';
require_once WP_LIVESCORE_LA_DIR . 'posts/player.php';
require_once WP_LIVESCORE_LA_DIR . 'posts/prediction.php';
require_once WP_LIVESCORE_LA_DIR . 'posts/match.php';
require_once WP_LIVESCORE_LA_DIR . 'posts/importer.php';
require_once WP_LIVESCORE_LA_DIR . 'import-api/sportsdb.php';
require_once WP_LIVESCORE_LA_DIR . 'import-api/sofascore.php';
require_once WP_LIVESCORE_LA_DIR . 'import-api/kadario.php';
require_once WP_LIVESCORE_LA_DIR . 'Cron/import-queue.php';
require_once WP_LIVESCORE_LA_DIR . 'admin/settings.php';
require_once WP_LIVESCORE_LA_DIR . 'shortcodes/categories.php';

/**
 * Add a dedicated Livescore block category.
 *
 * @param array                   $categories Existing block categories.
 * @param WP_Block_Editor_Context $context    Editor context.
 * @return array
 */
function wp_livescore_la_register_block_category( $categories, $context ) {
	foreach ( $categories as $category ) {
		if ( isset( $category['slug'] ) && 'livescore' === $category['slug'] ) {
			return $categories;
		}
	}

	$categories[] = array(
		'slug'  => 'livescore',
		'title' => __( 'Livescore', 'wp-livescore-la' ),
		'icon'  => null,
	);

	return $categories;
}
add_filter( 'block_categories_all', 'wp_livescore_la_register_block_category', 10, 2 );

/**
 * Register archive widget areas.
 */
function wp_livescore_la_register_sidebars() {
	register_sidebar(
		array(
			'name'          => __( 'Livescore League Archive Sidebar', 'wp-livescore-la' ),
			'id'            => 'wp-livescore-la-league-archive-sidebar',
			'description'   => __( 'Widgets shown on the League archive page.', 'wp-livescore-la' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Livescore Match Archive Sidebar', 'wp-livescore-la' ),
			'id'            => 'wp-livescore-la-match-archive-sidebar',
			'description'   => __( 'Widgets shown on the Match archive page.', 'wp-livescore-la' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'wp_livescore_la_register_sidebars' );

/**
 * Get a block pattern by slug or title.
 *
 * @param string $pattern_name Pattern slug or title.
 * @return string
 */
function wp_livescore_la_get_pattern_content( $pattern_name ) {
	$pattern_name = sanitize_title( $pattern_name );

	if ( '' === $pattern_name ) {
		return '';
	}

	if ( class_exists( 'WP_Block_Patterns_Registry' ) ) {
		$patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
		foreach ( $patterns as $pattern ) {
			$name  = isset( $pattern['name'] ) ? sanitize_text_field( $pattern['name'] ) : '';
			$title = isset( $pattern['title'] ) ? sanitize_title( $pattern['title'] ) : '';

			if ( $pattern_name === sanitize_title( $name ) || $pattern_name === basename( $name ) || $pattern_name === $title ) {
				return isset( $pattern['content'] ) ? (string) $pattern['content'] : '';
			}
		}
	}

	$pattern = get_page_by_path( $pattern_name, OBJECT, 'wp_block' );
	if ( $pattern instanceof WP_Post ) {
		return (string) $pattern->post_content;
	}

	$patterns = get_posts(
		array(
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			's'              => $pattern_name,
		)
	);

	foreach ( $patterns as $pattern ) {
		if ( $pattern instanceof WP_Post && $pattern_name === sanitize_title( $pattern->post_title ) ) {
			return (string) $pattern->post_content;
		}
	}

	return '';
}

/**
 * Get archive sidebar markup from the Sidebar site builder item or widget fallback.
 *
 * @param string $sidebar_id Custom sidebar ID.
 * @return string
 */
function wp_livescore_la_get_archive_sidebar_content( $sidebar_id ) {
	$theme_slug            = function_exists( 'wp_get_theme' ) ? sanitize_key( wp_get_theme()->get_stylesheet() ) : '';
	$template_part_comment = '<!-- wp:template-part {"slug":"sidebar"' . ( '' !== $theme_slug ? ',"theme":"' . esc_attr( $theme_slug ) . '"' : '' ) . '} /-->';
	$template_part_content = do_blocks( $template_part_comment );

	if ( '' !== trim( $template_part_content ) ) {
		return $template_part_content;
	}

	$pattern_content = do_blocks( '<!-- wp:pattern {"slug":"sidebar"} /-->' );

	if ( '' !== trim( $pattern_content ) ) {
		return $pattern_content;
	}

	$pattern_content = wp_livescore_la_get_pattern_content( 'sidebar' );

	if ( '' !== trim( $pattern_content ) ) {
		return do_blocks( $pattern_content );
	}

	ob_start();

	if ( is_active_sidebar( $sidebar_id ) ) {
		dynamic_sidebar( $sidebar_id );
		return (string) ob_get_clean();
	}

	if ( is_active_sidebar( 'sidebar-1' ) ) {
		dynamic_sidebar( 'sidebar-1' );
		return (string) ob_get_clean();
	}

	ob_end_clean();

	return '';
}

/**
 * Render an archive sidebar with a Sidebar pattern and widget fallback.
 *
 * @param string $sidebar_id Custom sidebar ID.
 * @return bool
 */
function wp_livescore_la_render_archive_sidebar( $sidebar_id ) {
	$sidebar_content = wp_livescore_la_get_archive_sidebar_content( $sidebar_id );

	if ( '' === trim( $sidebar_content ) ) {
		return false;
	}

	echo wp_kses_post( $sidebar_content );
	return true;
}

/**
 * Find an enabled Astra Site Builder template layout by title.
 *
 * @param string $title Layout title.
 * @return int
 */
function wp_livescore_la_get_astra_site_builder_template_id( $title ) {
	$title = sanitize_text_field( $title );

	if ( '' === $title || ! post_type_exists( 'astra-advanced-hook' ) ) {
		return 0;
	}

	$layouts = get_posts(
		array(
			'post_type'      => 'astra-advanced-hook',
			'post_status'    => 'publish',
			'title'          => $title,
			'posts_per_page' => 5,
			'fields'         => 'ids',
		)
	);

	foreach ( $layouts as $layout_id ) {
		$layout_id = absint( $layout_id );
		if ( $layout_id <= 0 || $title !== get_the_title( $layout_id ) ) {
			continue;
		}

		$enabled = get_post_meta( $layout_id, 'ast-advanced-hook-enabled', true );
		$layout  = get_post_meta( $layout_id, 'ast-advanced-hook-layout', true );

		if ( 'no' !== $enabled && 'template' === $layout ) {
			return $layout_id;
		}
	}

	return 0;
}

/**
 * Use an Astra Site Builder layout as the current archive template.
 *
 * @param string $layout_title Astra layout title.
 * @return string
 */
function wp_livescore_la_get_astra_site_builder_template( $layout_title ) {
	$layout_id = wp_livescore_la_get_astra_site_builder_template_id( $layout_title );

	if ( $layout_id <= 0 ) {
		return '';
	}

	$GLOBALS['wp_livescore_la_astra_site_builder_layout_id'] = $layout_id;

	$template = WP_LIVESCORE_LA_DIR . 'templates/astra-site-builder-layout.php';
	return file_exists( $template ) ? $template : '';
}

/**
 * Determine whether the current page is a Livescore archive using a forced Astra layout.
 *
 * @return bool
 */
function wp_livescore_la_is_forced_astra_archive_layout() {
	return ! empty( $GLOBALS['wp_livescore_la_astra_site_builder_layout_id'] )
		&& ( is_post_type_archive( 'league' ) || is_post_type_archive( 'match' ) || is_post_type_archive( 'team' ) || is_post_type_archive( 'player' ) || is_post_type_archive( 'prediction' ) );
}

/**
 * Force full-width Astra container for Livescore Site Builder archives.
 *
 * @param string $layout Astra content layout.
 * @return string
 */
function wp_livescore_la_force_full_width_content_layout( $layout ) {
	return wp_livescore_la_is_forced_astra_archive_layout() ? 'full-width-container' : $layout;
}
add_filter( 'astra_get_content_layout', 'wp_livescore_la_force_full_width_content_layout', 999 );

/**
 * Remove sidebar for Livescore Site Builder archives.
 *
 * @param string $layout Astra sidebar layout.
 * @return string
 */
function wp_livescore_la_force_no_sidebar_layout( $layout ) {
	return wp_livescore_la_is_forced_astra_archive_layout() ? 'no-sidebar' : $layout;
}
add_filter( 'astra_page_layout', 'wp_livescore_la_force_no_sidebar_layout', 999 );

/**
 * Replace Astra's content container with a full-width wrapper.
 *
 * Astra prints this wrapper directly in header.php, so filter-based layout
 * changes cannot always change the actual class in the markup.
 *
 * @param string $html Rendered page HTML.
 * @return string
 */
function wp_livescore_la_replace_astra_content_container( $html ) {
	return (string) preg_replace(
		'/(<div\b(?=[^>]*\bid=(["\'])content\2)(?=[^>]*\bclass=(["\'])[^"\']*\bsite-content\b[^"\']*\3)[^>]*>\s*)<div\b(?=[^>]*\bclass=(["\'])[^"\']*\bast-container\b[^"\']*\4)[^>]*>/',
		'$1<div class="ast-full-width">',
		(string) $html,
		1
	);
}

/**
 * Buffer Livescore archive output so the Astra header wrapper can be changed.
 */
function wp_livescore_la_buffer_astra_archive_container() {
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( is_post_type_archive( 'league' ) || is_post_type_archive( 'match' ) || is_post_type_archive( 'team' ) || is_post_type_archive( 'player' ) || is_post_type_archive( 'prediction' ) ) {
		ob_start( 'wp_livescore_la_replace_astra_content_container' );
	}
}
add_action( 'template_redirect', 'wp_livescore_la_buffer_astra_archive_container', 0 );

/**
 * Load assets and inline styles for a forced Astra Site Builder layout.
 *
 * @param int $layout_id Astra Site Builder post ID.
 */
function wp_livescore_la_prepare_astra_site_builder_layout( $layout_id ) {
	$layout_id = absint( $layout_id );

	if ( $layout_id <= 0 ) {
		return;
	}

	if ( class_exists( 'Astra_Addon_Page_Builder_Compatibility' ) ) {
		$page_builder_base_instance = Astra_Addon_Page_Builder_Compatibility::get_instance();
		$page_builder_instance      = $page_builder_base_instance->get_active_page_builder( $layout_id );

		if ( is_callable( array( $page_builder_instance, 'enqueue_scripts' ) ) ) {
			$page_builder_instance->enqueue_scripts( $layout_id );
		}
	}

	if ( class_exists( 'Astra_Addon_Gutenberg_Compatibility' ) ) {
		$astra_gutenberg_instance = new Astra_Addon_Gutenberg_Compatibility();

		if ( is_callable( array( $astra_gutenberg_instance, 'enqueue_blocks_assets' ) ) ) {
			$astra_gutenberg_instance->enqueue_blocks_assets( $layout_id );
		}
	}

	$styles = wp_strip_all_tags( get_post_meta( $layout_id, '_wpb_shortcodes_custom_css', true ) );
	$padding = get_post_meta( $layout_id, 'ast-advanced-hook-padding', true );

	if ( is_array( $padding ) && function_exists( 'astra_addon_sanitize_css_value' ) ) {
		$padding_top    = isset( $padding['top'] ) ? astra_addon_sanitize_css_value( $padding['top'], 'spacing' ) : '';
		$padding_bottom = isset( $padding['bottom'] ) ? astra_addon_sanitize_css_value( $padding['bottom'], 'spacing' ) : '';

		if ( '' !== $padding_top || '' !== $padding_bottom ) {
			$styles .= ' .astra-advanced-hook-' . $layout_id . ' { ';
			if ( '' !== $padding_top ) {
				$styles .= 'padding-top: ' . $padding_top . ';';
			}
			if ( '' !== $padding_bottom ) {
				$styles .= 'padding-bottom: ' . $padding_bottom . ';';
			}
			$styles .= '}';
		}
	}

	if ( '' !== trim( $styles ) ) {
		wp_add_inline_style( 'astra-addon-css', $styles );
	}
}

/**
 * Normalize Related Blogs block attributes.
 *
 * @param array<string, mixed> $attributes Raw block attributes.
 * @return array<string, mixed>
 */
function wp_livescore_la_normalize_related_blog_attributes( $attributes ) {
	$attributes = is_array( $attributes ) ? $attributes : array();

	return array(
		'title'              => isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Related Blogs', 'wp-livescore-la' ),
		'postsPerPage'       => isset( $attributes['postsPerPage'] ) ? max( 1, min( 24, (int) $attributes['postsPerPage'] ) ) : 6,
		'columns'            => isset( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3,
		'showFeaturedImage'  => ! array_key_exists( 'showFeaturedImage', $attributes ) || ! empty( $attributes['showFeaturedImage'] ),
		'showExcerpt'        => ! array_key_exists( 'showExcerpt', $attributes ) || ! empty( $attributes['showExcerpt'] ),
		'showDate'           => ! array_key_exists( 'showDate', $attributes ) || ! empty( $attributes['showDate'] ),
		'showAuthor'         => ! empty( $attributes['showAuthor'] ),
		'showReadMore'       => ! array_key_exists( 'showReadMore', $attributes ) || ! empty( $attributes['showReadMore'] ),
		'readMoreText'       => isset( $attributes['readMoreText'] ) ? sanitize_text_field( $attributes['readMoreText'] ) : __( 'Read More', 'wp-livescore-la' ),
		'showLoadMore'       => ! array_key_exists( 'showLoadMore', $attributes ) || ! empty( $attributes['showLoadMore'] ),
		'loadMoreText'       => isset( $attributes['loadMoreText'] ) ? sanitize_text_field( $attributes['loadMoreText'] ) : __( 'View More', 'wp-livescore-la' ),
		'emptyMessage'       => isset( $attributes['emptyMessage'] ) ? sanitize_text_field( $attributes['emptyMessage'] ) : __( 'No related blog posts found.', 'wp-livescore-la' ),
	);
}

/**
 * Get the linked blog tag ID for a Livescore post.
 *
 * @param int $related_post_id Current related entity post ID.
 * @return int
 */
function wp_livescore_la_get_related_blog_tag_id( $related_post_id ) {
	$related_post_id = absint( $related_post_id );
	$related_type    = $related_post_id > 0 ? get_post_type( $related_post_id ) : '';

	if ( ! $related_post_id || ! in_array( $related_type, array( 'league', 'team', 'match', 'sport', 'country' ), true ) ) {
		return 0;
	}

	$related_slug = sanitize_title( get_post_field( 'post_name', $related_post_id ) );
	$tag          = '' !== $related_slug ? get_term_by( 'slug', $related_slug, 'post_tag' ) : false;
	$tag_id       = $tag instanceof WP_Term ? (int) $tag->term_id : 0;

	if ( $tag_id <= 0 && 'league' === $related_type ) {
		$tag_id = get_league_linked_tag_id( $related_post_id );

		if ( $tag_id <= 0 ) {
			$tag_id = sync_league_to_post_tag( $related_post_id );
		}
	}

	if ( $tag_id <= 0 && 'team' === $related_type ) {
		$tag_id = get_team_linked_tag_id( $related_post_id );

		if ( $tag_id <= 0 ) {
			$tag_id = sync_team_to_post_tag( $related_post_id );
		}
	}

	if ( $tag_id <= 0 && 'match' === $related_type ) {
		$tag_id = get_match_linked_tag_id( $related_post_id );

		if ( $tag_id <= 0 ) {
			$tag_id = sync_match_to_post_tag( $related_post_id );
		}
	}

	return max( 0, (int) $tag_id );
}

/**
 * Build Related Blogs query args.
 *
 * @param int                  $tag_id     Related post tag ID.
 * @param array<string, mixed> $attributes Normalized block attributes.
 * @param int                  $page       Query page.
 * @return array<string, mixed>
 */
function wp_livescore_la_get_related_blog_query_args( $tag_id, $attributes, $page = 1 ) {
	return array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, min( 24, (int) $attributes['postsPerPage'] ) ),
		'paged'               => max( 1, absint( $page ) ),
		'ignore_sticky_posts' => true,
		'tag_id'              => absint( $tag_id ),
	);
}

/**
 * Build random fallback blog query args.
 *
 * @param array<string, mixed> $attributes Normalized block attributes.
 * @return array<string, mixed>
 */
function wp_livescore_la_get_random_blog_query_args( $attributes ) {
	return array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, min( 24, (int) $attributes['postsPerPage'] ) ),
		'ignore_sticky_posts' => true,
		'orderby'             => 'rand',
	);
}

/**
 * Render one Related Blogs card.
 *
 * @param array<string, mixed> $attributes Normalized block attributes.
 * @return string
 */
function wp_livescore_la_render_related_blog_card( $attributes ) {
	ob_start();
	?>
	<article class="wp-livescore-la-related-league-blogs__card">
		<?php if ( $attributes['showFeaturedImage'] ) : ?>
			<a class="wp-livescore-la-related-league-blogs__image-link" href="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'medium_large', array( 'class' => 'wp-livescore-la-related-league-blogs__image' ) );
				} elseif ( function_exists( 'wp_livescore_la_get_image_placeholder' ) ) {
					echo wp_kses_post( wp_livescore_la_get_image_placeholder( 'wp-livescore-la-related-league-blogs__image wp-livescore-la-related-league-blogs__placeholder', get_the_title() ) );
				}
				?>
			</a>
		<?php endif; ?>

		<div class="wp-livescore-la-related-league-blogs__content">
			<h3 class="wp-livescore-la-related-league-blogs__post-title">
				<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
			</h3>

			<?php if ( $attributes['showDate'] || $attributes['showAuthor'] ) : ?>
				<div class="wp-livescore-la-related-league-blogs__meta">
					<?php if ( $attributes['showDate'] ) : ?>
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<?php endif; ?>
					<?php if ( $attributes['showAuthor'] ) : ?>
						<span><?php echo esc_html( get_the_author() ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showExcerpt'] ) : ?>
				<div class="wp-livescore-la-related-league-blogs__excerpt">
					<?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 24 ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showReadMore'] && '' !== $attributes['readMoreText'] ) : ?>
				<a class="wp-livescore-la-related-league-blogs__read-more" href="<?php echo esc_url( get_permalink() ); ?>">
					<?php echo esc_html( $attributes['readMoreText'] ); ?>
				</a>
			<?php endif; ?>
		</div>
	</article>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render Related Blogs cards from a WP_Query.
 *
 * @param WP_Query             $query      Query object.
 * @param array<string, mixed> $attributes Normalized block attributes.
 * @return string
 */
function wp_livescore_la_render_related_blog_cards( $query, $attributes ) {
	if ( ! $query instanceof WP_Query || ! $query->have_posts() ) {
		return '';
	}

	ob_start();

	while ( $query->have_posts() ) {
		$query->the_post();
		echo wp_livescore_la_render_related_blog_card( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	wp_reset_postdata();

	return (string) ob_get_clean();
}

/**
 * AJAX handler for Related Blogs "View More".
 */
function wp_livescore_la_load_related_blogs() {
	check_ajax_referer( 'wp_livescore_la_related_blogs', 'nonce' );

	$related_post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$page            = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
	$raw_attributes  = isset( $_POST['attributes'] ) ? wp_unslash( $_POST['attributes'] ) : '{}';
	$attributes      = json_decode( (string) $raw_attributes, true );
	$attributes      = wp_livescore_la_normalize_related_blog_attributes( is_array( $attributes ) ? $attributes : array() );
	$tag_id          = wp_livescore_la_get_related_blog_tag_id( $related_post_id );

	if ( $tag_id <= 0 || $page <= 1 ) {
		wp_send_json_error();
	}

	$query = new WP_Query( wp_livescore_la_get_related_blog_query_args( $tag_id, $attributes, $page ) );
	$html  = wp_livescore_la_render_related_blog_cards( $query, $attributes );

	if ( '' === $html ) {
		wp_send_json_error();
	}

	wp_send_json_success(
		array(
			'html'    => $html,
			'hasMore' => $page < (int) $query->max_num_pages,
		)
	);
}
add_action( 'wp_ajax_wp_livescore_la_load_related_blogs', 'wp_livescore_la_load_related_blogs' );
add_action( 'wp_ajax_nopriv_wp_livescore_la_load_related_blogs', 'wp_livescore_la_load_related_blogs' );

/**
 * Register plugin blocks.
 */
function wp_livescore_la_register_blocks() {
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/related-league-blogs' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/related-team' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/opponent-team' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/related-prediction-match' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/league-filters' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/team-filters' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/prediction-filters' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/related-team-news' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/match-counter' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/match-list' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/match-filters' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/match-win-graph' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/tracker-iframe' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/fixture-iframe' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/league-data' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/sports' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/match-data' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/team-data' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/player-data' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/match-status' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/related-players' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/league-logo' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/league-header-image' );
	register_block_type( WP_LIVESCORE_LA_DIR . 'blocks/league-socials' );
}
add_action( 'init', 'wp_livescore_la_register_blocks' );

/**
 * Load the Match Query Loop variation in block editors.
 */
function wp_livescore_la_enqueue_match_query_loop_variation() {
	$asset_path = WP_LIVESCORE_LA_DIR . 'blocks/match-query-loop/variation.asset.php';
	$asset      = file_exists( $asset_path ) ? require $asset_path : array(
		'dependencies' => array( 'wp-blocks', 'wp-i18n' ),
		'version'      => WP_LIVESCORE_LA_VERSION,
	);

	wp_enqueue_script(
		'wp-livescore-la-match-query-loop-variation',
		WP_LIVESCORE_LA_URL . 'blocks/match-query-loop/variation.js',
		isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
		isset( $asset['version'] ) ? $asset['version'] : WP_LIVESCORE_LA_VERSION,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'wp_livescore_la_enqueue_match_query_loop_variation' );

/**
 * Load frontend behavior for Match Query Loop load more buttons.
 */
function wp_livescore_la_enqueue_match_query_loop_view() {
	$view_script_path = WP_LIVESCORE_LA_DIR . 'blocks/match-query-loop/view.js';

	wp_enqueue_style(
		'wp-livescore-la-match-query-loop',
		WP_LIVESCORE_LA_URL . 'blocks/match-query-loop/style.css',
		array(),
		WP_LIVESCORE_LA_VERSION
	);

	wp_enqueue_script(
		'wp-livescore-la-match-query-loop-view',
		WP_LIVESCORE_LA_URL . 'blocks/match-query-loop/view.js',
		array(),
		file_exists( $view_script_path ) ? (string) filemtime( $view_script_path ) : WP_LIVESCORE_LA_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'wp_livescore_la_enqueue_match_query_loop_view' );

/**
 * Flush rewrite rules once after the Prediction post type is introduced.
 *
 * Existing active installs do not run the activation hook again, so the new
 * /predictions/%postname% route needs one migration flush.
 */
function wp_livescore_la_maybe_flush_prediction_rewrite_rules() {
	if ( '1' === get_option( 'wp_livescore_la_prediction_rewrite_flushed', '' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'wp_livescore_la_prediction_rewrite_flushed', '1', false );
}
add_action( 'init', 'wp_livescore_la_maybe_flush_prediction_rewrite_rules', 20 );

/**
 * Register activation behavior.
 */
function wp_livescore_la_activate() {
	wp_livescore_la_register_sport_post_type();
	wp_livescore_la_register_country_post_type();
	wp_livescore_la_register_league_post_type();
	wp_livescore_la_register_league_season_taxonomy();
	wp_livescore_la_register_team_post_type();
	wp_livescore_la_register_player_post_type();
	wp_livescore_la_register_prediction_post_type();
	wp_livescore_la_register_match_post_type();
	if ( function_exists( 'wp_livescore_la_install_import_queue_table' ) ) {
		wp_livescore_la_install_import_queue_table();
	}
	if ( function_exists( 'wp_livescore_la_schedule_kadario_daily_import' ) ) {
		wp_livescore_la_schedule_kadario_daily_import();
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wp_livescore_la_activate' );

/**
 * Register deactivation behavior.
 */
function wp_livescore_la_deactivate() {
	wp_livescore_la_unschedule_import_queue_processing();
	if ( function_exists( 'wp_livescore_la_unschedule_kadario_daily_import' ) ) {
		wp_livescore_la_unschedule_kadario_daily_import();
	}
	if ( function_exists( 'wp_livescore_la_unschedule_kadario_daily_match_update' ) ) {
		wp_livescore_la_unschedule_kadario_daily_match_update();
	}
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wp_livescore_la_deactivate' );

/**
 * Refresh rewrites once after public archive routes are added or changed.
 */
function wp_livescore_la_maybe_refresh_archive_rewrites() {
	$rewrite_version = 'archive-pagination-v2';

	if ( $rewrite_version === get_option( 'wp_livescore_la_archive_rewrite_version' ) ) {
		return;
	}

	flush_rewrite_rules();
	update_option( 'wp_livescore_la_archive_rewrite_version', $rewrite_version, false );
}
add_action( 'init', 'wp_livescore_la_maybe_refresh_archive_rewrites', 99 );

/**
 * Register the Sports Manager menu and Settings > Livescore page.
 */
function wp_livescore_la_register_admin_menus() {
	add_menu_page(
		__( 'Sports Manager', 'wp-livescore-la' ),
		__( 'Sports Manager', 'wp-livescore-la' ),
		'edit_posts',
		'wp-livescore-la-sports-manager',
		'wp_livescore_la_render_sports_manager_page',
		'dashicons-groups',
		56
	);

	add_options_page(
		__( 'Livescore Settings', 'wp-livescore-la' ),
		__( 'Livescore', 'wp-livescore-la' ),
		'manage_options',
		'wp-livescore-la-settings',
		'wp_livescore_la_render_settings_page'
	);
}
add_action( 'admin_menu', 'wp_livescore_la_register_admin_menus' );

/**
 * Render the Sports Manager landing page.
 */
function wp_livescore_la_render_sports_manager_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage sports content.', 'wp-livescore-la' ) );
	}
	?>
	<div class="wrap wp-livescore-la-admin">
		<h1><?php esc_html_e( 'Sports Manager', 'wp-livescore-la' ); ?></h1>
		<p><?php esc_html_e( 'Manage livescore sports content and custom post types.', 'wp-livescore-la' ); ?></p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=league' ) ); ?>">
				<?php esc_html_e( 'Manage Leagues', 'wp-livescore-la' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=sport&post_type=league' ) ); ?>">
				<?php esc_html_e( 'Manage Sports', 'wp-livescore-la' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=country' ) ); ?>">
				<?php esc_html_e( 'Manage Countries', 'wp-livescore-la' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=team' ) ); ?>">
				<?php esc_html_e( 'Manage Teams', 'wp-livescore-la' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=match' ) ); ?>">
				<?php esc_html_e( 'Manage Matches', 'wp-livescore-la' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'options-general.php?page=wp-livescore-la-settings' ) ); ?>">
				<?php esc_html_e( 'Livescore Settings', 'wp-livescore-la' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Enqueue admin styles on plugin screens.
 *
 * @param string $hook Current admin page hook.
 */
function wp_livescore_la_enqueue_admin_assets( $hook ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$is_league_screen = $screen && isset( $screen->post_type ) && 'league' === $screen->post_type;
	$is_team_screen = $screen && isset( $screen->post_type ) && 'team' === $screen->post_type;
	$is_match_screen = $screen && isset( $screen->post_type ) && 'match' === $screen->post_type;
	$is_sport_screen = $screen && isset( $screen->taxonomy ) && 'sport' === $screen->taxonomy;
	$is_country_screen = $screen && isset( $screen->post_type ) && 'country' === $screen->post_type;

	if (
		'toplevel_page_wp-livescore-la-sports-manager' !== $hook
		&& 'settings_page_wp-livescore-la-settings' !== $hook
		&& ! $is_league_screen
		&& ! $is_team_screen
		&& ! $is_match_screen
		&& ! $is_sport_screen
		&& ! $is_country_screen
	) {
		return;
	}

	wp_enqueue_style(
		'wp-livescore-la-admin',
		WP_LIVESCORE_LA_URL . 'style/admin.css',
		array(),
		WP_LIVESCORE_LA_VERSION
	);

	if ( $is_league_screen || $is_team_screen || $is_match_screen || $is_sport_screen || $is_country_screen ) {
		wp_enqueue_media();
		wp_enqueue_script(
			'wp-livescore-la-admin',
			WP_LIVESCORE_LA_URL . 'admin/admin.js',
			array( 'jquery', 'media-editor' ),
			WP_LIVESCORE_LA_VERSION,
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wp_livescore_la_enqueue_admin_assets' );

/**
 * Enqueue lightweight frontend assets.
 */
function wp_livescore_la_enqueue_frontend_assets() {
	if ( ! is_singular( array( 'team', 'match' ) ) && ! is_post_type_archive( array( 'team', 'player', 'prediction' ) ) ) {
		return;
	}

	wp_enqueue_style(
		'wp-livescore-la-frontend',
		WP_LIVESCORE_LA_URL . 'blocks/match-list/style.css',
		array(),
		WP_LIVESCORE_LA_VERSION
	);

	if ( is_post_type_archive( array( 'team', 'player', 'prediction' ) ) ) {
		wp_enqueue_style(
			'wp-livescore-la-player-archive',
			WP_LIVESCORE_LA_URL . 'blocks/team-filters/style.css',
			array(),
			WP_LIVESCORE_LA_VERSION
		);
	}

	if ( is_post_type_archive( 'team' ) ) {
		wp_enqueue_script(
			'wp-livescore-la-archive-load-more',
			WP_LIVESCORE_LA_URL . 'templates/archive-load-more.js',
			array(),
			WP_LIVESCORE_LA_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'wp_livescore_la_enqueue_frontend_assets' );
