<?php
/**
 * Livescore category shortcode.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build the category endpoint from shortcode attributes.
 *
 * Accepts api_key and the earlier api_code attribute for backwards compatibility.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function wp_livescore_la_build_categories_url( $atts ) {
	$api_link        = isset( $atts['api_link'] ) && '' !== $atts['api_link'] ? $atts['api_link'] : wp_livescore_la_get_setting( 'api_link' );
	$categories_slug = isset( $atts['categories_slug'] ) ? $atts['categories_slug'] : '';

	if ( isset( $atts['api_key'] ) && '' !== $atts['api_key'] ) {
		$api_key = $atts['api_key'];
	} elseif ( isset( $atts['api_code'] ) && '' !== $atts['api_code'] ) {
		$api_key = $atts['api_code'];
	} else {
		$api_key = wp_livescore_la_get_setting( 'api_key' );
	}

	return wp_livescore_la_build_api_url( $api_link, $api_key, $categories_slug );
}

/**
 * Return a likely category collection from common API response shapes.
 *
 * @param mixed $payload Decoded JSON payload.
 * @return array
 */
function wp_livescore_la_extract_categories( $payload ) {
	if ( is_array( $payload ) ) {
		if ( isset( $payload['categories'] ) && is_array( $payload['categories'] ) ) {
			return $payload['categories'];
		}

		if ( isset( $payload['sports'] ) && is_array( $payload['sports'] ) ) {
			return $payload['sports'];
		}

		if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
			return wp_livescore_la_extract_categories( $payload['data'] );
		}

		if ( wp_livescore_la_is_list( $payload ) ) {
			return $payload;
		}
	}

	return array();
}

/**
 * Read a display label from a category item.
 *
 * @param mixed $category Category item.
 * @return string
 */
function wp_livescore_la_category_label( $category ) {
	if ( is_string( $category ) || is_numeric( $category ) ) {
		return (string) $category;
	}

	if ( ! is_array( $category ) ) {
		return '';
	}

	foreach ( array( 'name', 'title', 'category_name', 'sport_name', 'label', 'slug', 'id' ) as $key ) {
		if ( isset( $category[ $key ] ) && '' !== (string) $category[ $key ] ) {
			return (string) $category[ $key ];
		}
	}

	return '';
}

/**
 * Render sports categories fetched from the API.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function wp_livescore_la_render_category_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'api_link'        => '',
			'api_key'         => '',
			'api_code'        => '',
			'categories_slug' => '',
			'cache'           => 300,
		),
		is_array( $atts ) ? $atts : array(),
		'livescore_category'
	);

	$endpoint = wp_livescore_la_build_categories_url( $atts );
	if ( '' === $endpoint ) {
		return '<p class="wp-livescore-la-error">' . esc_html__( 'Livescore category shortcode is missing API settings.', 'wp-livescore-la' ) . '</p>';
	}

	$cache_seconds = max( 0, (int) $atts['cache'] );
	$cache_key     = 'wp_livescore_la_categories_' . md5( $endpoint );
	$payload       = false;

	if ( $cache_seconds > 0 ) {
		$payload = get_transient( $cache_key );
	}

	if ( false === $payload ) {
		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return '<p class="wp-livescore-la-error">' . esc_html__( 'Unable to load sports categories.', 'wp-livescore-la' ) . '</p>';
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			return '<p class="wp-livescore-la-error">' . esc_html__( 'Sports categories API returned an error.', 'wp-livescore-la' ) . '</p>';
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return '<p class="wp-livescore-la-error">' . esc_html__( 'Sports categories API returned invalid JSON.', 'wp-livescore-la' ) . '</p>';
		}

		if ( $cache_seconds > 0 ) {
			set_transient( $cache_key, $payload, $cache_seconds );
		}
	}

	$categories = wp_livescore_la_extract_categories( $payload );
	if ( empty( $categories ) ) {
		return '<p class="wp-livescore-la-empty">' . esc_html__( 'No sports categories found.', 'wp-livescore-la' ) . '</p>';
	}

	ob_start();
	?>
	<ul class="wp-livescore-la-categories">
		<?php foreach ( $categories as $category ) : ?>
			<?php $label = wp_livescore_la_category_label( $category ); ?>
			<?php if ( '' !== $label ) : ?>
				<li class="wp-livescore-la-category"><?php echo esc_html( $label ); ?></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'livescore_category', 'wp_livescore_la_render_category_shortcode' );
