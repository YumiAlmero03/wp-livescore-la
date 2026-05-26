<?php
/**
 * Server render for League Socials block.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$league_id = isset( $block->context['postId'] ) && 'league' === get_post_type( (int) $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( $league_id <= 0 && ! empty( $attributes['leagueId'] ) ) {
	$manual_id = absint( $attributes['leagueId'] );
	$league_id = 'league' === get_post_type( $manual_id ) ? $manual_id : 0;
}

if ( $league_id <= 0 ) {
	return '';
}

$social_fields = array(
	'strWebsite'   => array(
		'label' => __( 'Website', 'wp-livescore-la' ),
		'icon'  => 'dashicons-admin-site-alt3',
	),
	'strFacebook'  => array(
		'label' => __( 'Facebook', 'wp-livescore-la' ),
		'icon'  => 'dashicons-facebook',
	),
	'strInstagram' => array(
		'label' => __( 'Instagram', 'wp-livescore-la' ),
		'icon'  => 'dashicons-instagram',
	),
	'strTwitter'   => array(
		'label' => __( 'Twitter/X', 'wp-livescore-la' ),
		'icon'  => 'dashicons-twitter',
	),
	'strYoutube'   => array(
		'label' => __( 'YouTube', 'wp-livescore-la' ),
		'icon'  => 'dashicons-youtube',
	),
	'strRSS'       => array(
		'label' => __( 'RSS', 'wp-livescore-la' ),
		'icon'  => 'dashicons-rss',
	),
);
$links = array();

foreach ( $social_fields as $field => $social ) {
	$url = get_post_meta( $league_id, WP_LIVESCORE_LA_META_PREFIX . $field, true );
	$url = esc_url( $url );

	if ( '' !== $url ) {
		$links[] = array(
			'url'   => $url,
			'label' => $social['label'],
			'icon'  => $social['icon'],
		);
	}
}

if ( empty( $links ) ) {
	return '';
}

wp_enqueue_style( 'dashicons' );

$open_new_tab = ! isset( $attributes['openNewTab'] ) || ! empty( $attributes['openNewTab'] );
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-livescore-la-league-socials',
	)
);
?>
<nav <?php echo wp_kses_data( $wrapper_attributes ); ?> aria-label="<?php esc_attr_e( 'League links', 'wp-livescore-la' ); ?>">
	<?php foreach ( $links as $link ) : ?>
		<a
			class="wp-livescore-la-league-socials__link"
			href="<?php echo esc_url( $link['url'] ); ?>"
			aria-label="<?php echo esc_attr( $link['label'] ); ?>"
			<?php if ( $open_new_tab ) : ?>
				target="_blank" rel="noopener noreferrer"
			<?php endif; ?>
		>
			<span class="dashicons <?php echo esc_attr( $link['icon'] ); ?>" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php echo esc_html( $link['label'] ); ?></span>
		</a>
	<?php endforeach; ?>
</nav>
