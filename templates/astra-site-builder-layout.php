<?php
/**
 * Proxy template for Astra Site Builder layouts.
 *
 * @package WPLivescoreLA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$layout_id = isset( $GLOBALS['wp_livescore_la_astra_site_builder_layout_id'] ) ? absint( $GLOBALS['wp_livescore_la_astra_site_builder_layout_id'] ) : 0;

if ( $layout_id > 0 && class_exists( 'Astra_Ext_Advanced_Hooks_Markup' ) ) {
	if ( function_exists( 'wp_livescore_la_prepare_astra_site_builder_layout' ) ) {
		wp_livescore_la_prepare_astra_site_builder_layout( $layout_id );
	}

	ob_start();
	Astra_Ext_Advanced_Hooks_Markup::render_overridden_template( $layout_id );
	$template_output = (string) ob_get_clean();
	$template_output = function_exists( 'wp_livescore_la_replace_astra_content_container' ) ? wp_livescore_la_replace_astra_content_container( $template_output ) : $template_output;

	echo $template_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

get_header();
?>
	<main id="primary" class="site-main">
		<p><?php esc_html_e( 'Astra Site Builder layout was not found.', 'wp-livescore-la' ); ?></p>
	</main>
<?php
get_footer();
