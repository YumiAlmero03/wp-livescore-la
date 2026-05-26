( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const RangeControl = components.RangeControl;
	const SelectControl = components.SelectControl;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	blocks.registerBlockType( 'wp-livescore/related-team', {
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
						{ title: __( 'Related Team', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual Match ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.matchId || '',
							onChange: function ( value ) { setAttributes( { matchId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( SelectControl, {
							label: __( 'Team', 'wp-livescore-la' ),
							value: attributes.teamSide || 'home',
							options: [
								{ label: __( 'Home Team', 'wp-livescore-la' ), value: 'home' },
								{ label: __( 'Away Team', 'wp-livescore-la' ), value: 'away' }
							],
							onChange: function ( value ) { setAttributes( { teamSide: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show team image', 'wp-livescore-la' ),
							checked: !! attributes.showImage,
							onChange: function ( value ) { setAttributes( { showImage: value } ); }
						} ),
						el( SelectControl, {
							label: __( 'Team logo position', 'wp-livescore-la' ),
							value: attributes.imagePosition || 'top',
							options: [
								{ label: __( 'Top', 'wp-livescore-la' ), value: 'top' },
								{ label: __( 'Left', 'wp-livescore-la' ), value: 'left' },
								{ label: __( 'Right', 'wp-livescore-la' ), value: 'right' }
							],
							onChange: function ( value ) { setAttributes( { imagePosition: value } ); }
						} ),
						attributes.showImage ? el( RangeControl, {
							label: __( 'Image size', 'wp-livescore-la' ),
							value: attributes.imageSize || 5,
							min: 2,
							max: 12,
							step: 0.5,
							onChange: function ( value ) { setAttributes( { imageSize: parseFloat( value ) || 5 } ); }
						} ) : null,
						el( ToggleControl, {
							label: __( 'Use shortcut name', 'wp-livescore-la' ),
							checked: !! attributes.useShortName,
							onChange: function ( value ) { setAttributes( { useShortName: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Make this a link', 'wp-livescore-la' ),
							checked: !! attributes.makeLink,
							onChange: function ( value ) { setAttributes( { makeLink: value } ); }
						} ),
						el( TextControl, {
							label: __( 'Empty message', 'wp-livescore-la' ),
							value: attributes.emptyMessage,
							onChange: function ( value ) { setAttributes( { emptyMessage: value } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/related-team', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
