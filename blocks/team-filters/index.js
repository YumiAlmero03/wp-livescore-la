( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	blocks.registerBlockType( 'wp-livescore/team-filters', {
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
						{ title: __( 'Filter Display', 'wp-livescore-la' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Show title', 'wp-livescore-la' ),
							checked: !! attributes.showTitle,
							onChange: function ( value ) { setAttributes( { showTitle: value } ); }
						} ),
						attributes.showTitle ? el( TextControl, {
							label: __( 'Title', 'wp-livescore-la' ),
							value: attributes.title || '',
							onChange: function ( value ) { setAttributes( { title: value } ); }
						} ) : null,
						el( ToggleControl, {
							label: __( 'Sport filter', 'wp-livescore-la' ),
							checked: !! attributes.showSport,
							onChange: function ( value ) { setAttributes( { showSport: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'League filter', 'wp-livescore-la' ),
							checked: !! attributes.showLeague,
							onChange: function ( value ) { setAttributes( { showLeague: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Country filter', 'wp-livescore-la' ),
							checked: !! attributes.showCountry,
							onChange: function ( value ) { setAttributes( { showCountry: value } ); }
						} ),
						el( TextControl, {
							label: __( 'Button text', 'wp-livescore-la' ),
							value: attributes.buttonText || '',
							onChange: function ( value ) { setAttributes( { buttonText: value } ); }
						} ),
						el( TextControl, {
							label: __( 'Reset text', 'wp-livescore-la' ),
							value: attributes.resetText || '',
							onChange: function ( value ) { setAttributes( { resetText: value } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/team-filters', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
