( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const SelectControl = components.SelectControl;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	blocks.registerBlockType( 'wp-livescore/league-logo', {
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
						{ title: __( 'League Logo', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual League ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.leagueId || '',
							onChange: function ( value ) { setAttributes( { leagueId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( SelectControl, {
							label: __( 'Image size', 'wp-livescore-la' ),
							value: attributes.imageSize || 'medium',
							options: [
								{ label: __( 'Thumbnail', 'wp-livescore-la' ), value: 'thumbnail' },
								{ label: __( 'Medium', 'wp-livescore-la' ), value: 'medium' },
								{ label: __( 'Large', 'wp-livescore-la' ), value: 'large' },
								{ label: __( 'Full', 'wp-livescore-la' ), value: 'full' }
							],
							onChange: function ( value ) { setAttributes( { imageSize: value } ); }
						} ),
						el( TextControl, {
							label: __( 'Max width (px)', 'wp-livescore-la' ),
							type: 'number',
							min: 0,
							value: attributes.maxWidth || '',
							onChange: function ( value ) { setAttributes( { maxWidth: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( TextControl, {
							label: __( 'Max height (px)', 'wp-livescore-la' ),
							type: 'number',
							min: 0,
							value: attributes.maxHeight || '',
							onChange: function ( value ) { setAttributes( { maxHeight: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Link to League', 'wp-livescore-la' ),
							checked: !! attributes.linkToLeague,
							onChange: function ( value ) { setAttributes( { linkToLeague: value } ); }
						} ),
						el( TextControl, {
							label: __( 'Empty message', 'wp-livescore-la' ),
							value: attributes.emptyMessage || '',
							onChange: function ( value ) { setAttributes( { emptyMessage: value } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/league-logo', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
