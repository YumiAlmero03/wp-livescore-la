( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	blocks.registerBlockType( 'wp-livescore/sports', {
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
						{ title: __( 'Sports', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual Post ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.postId || '',
							onChange: function ( value ) {
								setAttributes( { postId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Show icon', 'wp-livescore-la' ),
							checked: !! attributes.showIcon,
							onChange: function ( value ) {
								setAttributes( { showIcon: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Make this a link', 'wp-livescore-la' ),
							checked: !! attributes.makeLink,
							onChange: function ( value ) {
								setAttributes( { makeLink: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Empty message', 'wp-livescore-la' ),
							value: attributes.emptyMessage || '',
							onChange: function ( value ) {
								setAttributes( { emptyMessage: value } );
							}
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/sports', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
