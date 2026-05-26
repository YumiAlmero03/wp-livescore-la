<?php
/**
 * League importer and media handling.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extract league records from common API response shapes.
 *
 * @param mixed $payload Decoded JSON payload.
 * @return array
 */
function wp_livescore_la_extract_league_records( $payload ) {
	if ( ! is_array( $payload ) ) {
		return array();
	}

	foreach ( array( 'leagues', 'countries' ) as $key ) {
		if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
			return wp_livescore_la_extract_league_records( $payload[ $key ] );
		}
	}

	return wp_livescore_la_is_list( $payload ) ? $payload : array();
}

/**
 * Import or update League posts from API records.
 *
 * @param array  $records          API records.
 * @param string $fallback_country Optional form country.
 * @param string $fallback_sports  Optional form sports.
 * @param string $api_source       API provider slug.
 * @return array
 */
function wp_livescore_la_import_leagues( $records, $fallback_country = '', $fallback_sports = '', $api_source = '' ) {
	$result = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
	);

	foreach ( $records as $record ) {
		if ( ! is_array( $record ) ) {
			$result['skipped']++;
			continue;
		}

		$name = wp_livescore_la_record_value( $record, array( 'strLeague', 'name', 'title', 'league_name', 'League' ) );
		if ( '' === $name ) {
			$result['skipped']++;
			continue;
		}

		$api_id      = wp_livescore_la_record_value( $record, array( 'idLeague', 'api_id', 'id', 'league_id' ) );
		$post_id     = wp_livescore_la_find_league_post( $api_id, $name );
		$description = wp_livescore_la_record_value( $record, array( 'strDescriptionEN', 'description', 'desc', 'content' ) );

		$post_data = array(
			'post_type'    => 'league',
			'post_status'  => 'publish',
			'post_title'   => sanitize_text_field( $name ),
			'post_content' => wp_kses_post( $description ),
		);

		$record_slug = wp_livescore_la_record_value( $record, array( 'slug', 'strSlug', 'post_name' ) );
		if ( '' !== $record_slug ) {
			$post_data['post_name'] = sanitize_title( $record_slug );
		}

		if ( $post_id > 0 ) {
			$post_data['ID'] = $post_id;
			$saved_id        = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$saved_id = wp_insert_post( wp_slash( $post_data ), true );
		}

		if ( is_wp_error( $saved_id ) || $saved_id <= 0 ) {
			$result['skipped']++;
			continue;
		}

		wp_livescore_la_update_league_meta_from_record( $saved_id, $record, $fallback_country, $fallback_sports );
		if ( '' !== $api_source ) {
			update_post_meta( $saved_id, WP_LIVESCORE_LA_META_PREFIX . 'api_source', sanitize_key( $api_source ) );
		}

		wp_livescore_la_sync_league_season_from_record( $saved_id, $record );

		$sport_value = wp_livescore_la_record_value( $record, array( 'strSport', 'sports', 'sport', 'Sport' ) );
		if ( '' === $sport_value ) {
			$sport_value = $fallback_sports;
		}

		$sport_id = wp_livescore_la_get_or_create_sport_id( $sport_value );
		wp_livescore_la_sync_league_sport_meta( $saved_id, $sport_id, $sport_value );

		$country_value = wp_livescore_la_record_value( $record, array( 'strCountry', 'country', 'Country' ) );
		if ( '' === $country_value ) {
			$country_value = $fallback_country;
		}

		$country_code = wp_livescore_la_record_value( $record, array( 'strCountryCode', 'country_code', 'countryCode', 'code', 'CountryCode' ) );
		$continent    = wp_livescore_la_record_value( $record, array( 'strContinent', 'continent', 'Continent' ) );
		$flag_url     = wp_livescore_la_record_value( $record, array( 'strFlag', 'flag', 'Flag', 'flag_url', 'country_flag' ) );
		$country_id   = wp_livescore_la_get_or_create_country_id( $country_value, $country_code, $continent, $flag_url );
		wp_livescore_la_sync_league_country_meta( $saved_id, $country_id, $country_value );
		sync_league_to_post_tag( $saved_id );

		$badge_url = wp_livescore_la_record_value( $record, array( 'strBadge', 'badge', 'Badge', 'logo', 'image', 'image_url' ) );
		wp_livescore_la_set_featured_image_from_url( $saved_id, $badge_url );

		$banner_url = wp_livescore_la_record_value( $record, array( 'strBanner', 'banner', 'Banner', 'header_image', 'header_image_url' ) );
		wp_livescore_la_set_header_image_from_url( $saved_id, $banner_url );

		if ( $post_id > 0 ) {
			$result['updated']++;
		} else {
			$result['created']++;
		}
	}

	return $result;
}

/**
 * Get the first non-empty value from a record.
 *
 * @param array $record API record.
 * @param array $keys   Candidate keys.
 * @return string
 */
function wp_livescore_la_record_value( $record, $keys ) {
	foreach ( $keys as $key ) {
		if ( isset( $record[ $key ] ) && '' !== (string) $record[ $key ] ) {
			return trim( (string) $record[ $key ] );
		}
	}

	return '';
}

/**
 * Find an existing League by api_id first, then slug/title.
 *
 * @param string $api_id API ID.
 * @param string $name   League name.
 * @return int
 */
function wp_livescore_la_find_league_post( $api_id, $name ) {
	if ( '' !== $api_id ) {
		$matches = get_posts(
			array(
				'post_type'      => 'league',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => WP_LIVESCORE_LA_META_PREFIX . 'api_id',
				'meta_value'     => sanitize_text_field( $api_id ),
			)
		);

		if ( ! empty( $matches ) ) {
			return (int) $matches[0];
		}
	}

	$post = get_page_by_path( sanitize_title( $name ), OBJECT, 'league' );
	if ( $post instanceof WP_Post ) {
		return (int) $post->ID;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'league',
			'post_status'    => 'any',
			'title'          => sanitize_text_field( $name ),
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);

	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Update League meta from an API record.
 *
 * @param int    $post_id          League post ID.
 * @param array  $record           API record.
 * @param string $fallback_country Optional form country.
 * @param string $fallback_sports  Optional form sports.
 */
function wp_livescore_la_update_league_meta_from_record( $post_id, $record, $fallback_country, $fallback_sports ) {
	$mapping = array(
		'country'          => array( 'strCountry', 'country', 'Country' ),
		'sports'           => array( 'strSport', 'sports', 'sport', 'Sport' ),
		'api_id'           => array( 'idLeague', 'api_id', 'id', 'league_id' ),
		'api_source'       => array( 'api_source', 'apiSource', 'source' ),
		'sportscore_slug' => array( 'sportscore_slug', 'sportScoreSlug', 'strSportScoreSlug', 'SportScoreSlug' ),
		'strCurrentSeason' => array( 'strCurrentSeason', 'current_season' ),
		'intFormedYear'    => array( 'intFormedYear', 'formed_year' ),
		'dateFirstEvent'   => array( 'dateFirstEvent', 'first_event_date' ),
		'strWebsite'       => array( 'strWebsite', 'website', 'url' ),
		'strFacebook'      => array( 'strFacebook', 'facebook' ),
		'strInstagram'     => array( 'strInstagram', 'instagram' ),
		'strTwitter'       => array( 'strTwitter', 'twitter' ),
		'strYoutube'       => array( 'strYoutube', 'youtube' ),
		'strRSS'           => array( 'strRSS', 'rss' ),
		'strBadge'         => array( 'strBadge', 'badge', 'Badge', 'logo', 'image', 'image_url' ),
		'strBanner'        => array( 'strBanner', 'banner', 'Banner', 'header_image', 'header_image_url' ),
	);

	foreach ( wp_livescore_la_league_meta_fields() as $field => $label ) {
		$value = isset( $mapping[ $field ] ) ? wp_livescore_la_record_value( $record, $mapping[ $field ] ) : '';

		if ( 'country' === $field && '' === $value ) {
			$value = $fallback_country;
		}

		if ( 'sports' === $field && '' === $value ) {
			$value = $fallback_sports;
		}

		if ( 'sportscore_slug' === $field && '' === $value ) {
			$value = wp_livescore_la_get_league_sportscore_slug_from_record( $post_id, $record, $fallback_sports );
		}

		$type  = wp_livescore_la_get_league_meta_field_type( $field );
		$value = 'url' === $type ? esc_url_raw( $value ) : sanitize_text_field( $value );

		if ( 'sportscore_slug' === $field ) {
			$value = wp_livescore_la_sanitize_sportscore_slug( $value );
		}

		if ( '' === $value && in_array( $field, array( 'country', 'sports' ), true ) ) {
			continue;
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . $field );
		} else {
			update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . $field, $value );
		}
	}
}

/**
 * Build an imported League SportScore slug from its sport and League name.
 *
 * @param int    $post_id         League post ID.
 * @param array  $record          API record.
 * @param string $fallback_sports Optional form sports fallback.
 * @return string
 */
function wp_livescore_la_get_league_sportscore_slug_from_record( $post_id, $record, $fallback_sports ) {
	$sports = wp_livescore_la_record_value( $record, array( 'strSport', 'sports', 'sport', 'Sport' ) );
	$name   = wp_livescore_la_record_value( $record, array( 'strLeague', 'name', 'title', 'league_name', 'League' ) );

	if ( '' === $sports ) {
		$sports = $fallback_sports;
	}

	if ( '' === $name ) {
		$name = get_the_title( $post_id );
	}

	if ( '' === $sports || '' === $name ) {
		return '';
	}

	return wp_livescore_la_sanitize_sportscore_slug( $sports . '/competition/' . $name );
}

/**
 * Create and assign the League Season taxonomy term from imported data.
 *
 * @param int   $post_id League post ID.
 * @param array $record  API record.
 */
function wp_livescore_la_sync_league_season_from_record( $post_id, $record ) {
	$season = wp_livescore_la_record_value( $record, array( 'strCurrentSeason', 'current_season', 'season', 'Season' ) );

	wp_livescore_la_sync_league_season_term( $post_id, $season );
}

/**
 * Sideload and set a featured image while avoiding duplicate downloads by source URL.
 *
 * @param int    $post_id   Post ID.
 * @param string $image_url Image URL.
 */
function wp_livescore_la_set_featured_image_from_url( $post_id, $image_url ) {
	$image_url = esc_url_raw( $image_url );
	if ( '' === $image_url || ! wp_http_validate_url( $image_url ) ) {
		return;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_key'       => WP_LIVESCORE_LA_META_PREFIX . 'source_url',
			'meta_value'     => $image_url,
		)
	);

	if ( ! empty( $existing ) ) {
		set_post_thumbnail( $post_id, (int) $existing[0] );
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return;
	}

	update_post_meta( (int) $attachment_id, WP_LIVESCORE_LA_META_PREFIX . 'source_url', $image_url );
	set_post_thumbnail( $post_id, (int) $attachment_id );
}

/**
 * Sideload and set a League header image from a URL.
 *
 * @param int    $post_id   League post ID.
 * @param string $image_url Header image URL.
 */
function wp_livescore_la_set_header_image_from_url( $post_id, $image_url ) {
	$attachment_id = wp_livescore_la_get_or_sideload_image_id( $post_id, $image_url );

	if ( $attachment_id > 0 ) {
		update_post_meta( $post_id, WP_LIVESCORE_LA_META_PREFIX . 'header_image_id', $attachment_id );
	}
}

/**
 * Return an existing attachment by source URL or sideload a new image.
 *
 * @param int    $post_id   Parent post ID.
 * @param string $image_url Image URL.
 * @return int
 */
function wp_livescore_la_get_or_sideload_image_id( $post_id, $image_url ) {
	$image_url = esc_url_raw( $image_url );
	if ( '' === $image_url || ! wp_http_validate_url( $image_url ) ) {
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_key'       => WP_LIVESCORE_LA_META_PREFIX . 'source_url',
			'meta_value'     => $image_url,
		)
	);

	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	update_post_meta( (int) $attachment_id, WP_LIVESCORE_LA_META_PREFIX . 'source_url', $image_url );
	return (int) $attachment_id;
}
