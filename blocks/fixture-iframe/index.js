( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const RangeControl = components.RangeControl;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	blocks.registerBlockType( 'wp-livescore/fixture-iframe', {
		edit: function ( props ) {
			const attributes = props.attributes;
			const setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Fixture Iframe', 'wp-livescore-la' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Width', 'wp-livescore-la' ),
							value: attributes.width,
							min: 240,
							max: 1200,
							onChange: function ( value ) { setAttributes( { width: value } ); }
						} ),
						el( RangeControl, {
							label: __( 'Height', 'wp-livescore-la' ),
							value: attributes.height,
							min: 240,
							max: 1200,
							onChange: function ( value ) { setAttributes( { height: value } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/fixture-iframe', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
