( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const SelectControl = components.SelectControl;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const ColorPalette = components.ColorPalette;
	const RangeControl = components.RangeControl;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	const focusOptions = [
		{ label: __( 'Top', 'wp-livescore-la' ), value: 'center top' },
		{ label: __( 'Middle', 'wp-livescore-la' ), value: 'center center' },
		{ label: __( 'Bottom', 'wp-livescore-la' ), value: 'center bottom' }
	];

	blocks.registerBlockType( 'wp-livescore/league-header-image', {
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
						{ title: __( 'League Header Image', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual League ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.leagueId || '',
							onChange: function ( value ) { setAttributes( { leagueId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( SelectControl, {
							label: __( 'Image size', 'wp-livescore-la' ),
							value: attributes.imageSize,
							options: [
								{ label: __( 'Full', 'wp-livescore-la' ), value: 'full' },
								{ label: __( 'Large', 'wp-livescore-la' ), value: 'large' },
								{ label: __( 'Medium', 'wp-livescore-la' ), value: 'medium' }
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
						el( SelectControl, {
							label: __( 'Position focus', 'wp-livescore-la' ),
							value: attributes.focusPosition || 'center center',
							options: focusOptions,
							onChange: function ( value ) { setAttributes( { focusPosition: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Link to League', 'wp-livescore-la' ),
							checked: attributes.linkToLeague,
							onChange: function ( value ) { setAttributes( { linkToLeague: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Overlay', 'wp-livescore-la' ),
							checked: !! attributes.showOverlay,
							onChange: function ( value ) { setAttributes( { showOverlay: value } ); }
						} ),
						attributes.showOverlay && el(
							'div',
							null,
							el( 'p', null, __( 'Overlay color', 'wp-livescore-la' ) ),
							el( ColorPalette, {
								value: attributes.overlayColor || '#000000',
								colors: [
									{ name: __( 'Black', 'wp-livescore-la' ), color: '#000000' },
									{ name: __( 'White', 'wp-livescore-la' ), color: '#ffffff' },
									{ name: __( 'Navy', 'wp-livescore-la' ), color: '#10243e' },
									{ name: __( 'Green', 'wp-livescore-la' ), color: '#12372a' }
								],
								onChange: function ( value ) { setAttributes( { overlayColor: value || '#000000' } ); }
							} ),
							el( RangeControl, {
								label: __( 'Overlay opacity', 'wp-livescore-la' ),
								value: attributes.overlayOpacity,
								min: 0,
								max: 100,
								onChange: function ( value ) { setAttributes( { overlayOpacity: value } ); }
							} )
						)
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/league-header-image', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
