( function ( blocks, blockEditor, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const useBlockProps = blockEditor.useBlockProps;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	blocks.registerBlockType( 'wp-livescore/tracker-iframe', {
		edit: function () {
			return el(
				'div',
				useBlockProps(),
				el( ServerSideRender, { block: 'wp-livescore/tracker-iframe' } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
