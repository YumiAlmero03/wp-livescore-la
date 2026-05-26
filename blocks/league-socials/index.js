( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	blocks.registerBlockType( 'wp-livescore/league-socials', {
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
						{ title: __( 'League Socials', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual League ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.leagueId || '',
							onChange: function ( value ) { setAttributes( { leagueId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Open links in new tab', 'wp-livescore-la' ),
							checked: attributes.openNewTab,
							onChange: function ( value ) { setAttributes( { openNewTab: value } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/league-socials', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
