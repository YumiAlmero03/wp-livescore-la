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

	blocks.registerBlockType( 'wp-livescore/related-players', {
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
						{ title: __( 'Related Players', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual Match ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.matchId || '',
							onChange: function ( value ) { setAttributes( { matchId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( SelectControl, {
							label: __( 'Team', 'wp-livescore-la' ),
							value: attributes.teamSide || 'both',
							options: [
								{ label: __( 'Home and Away', 'wp-livescore-la' ), value: 'both' },
								{ label: __( 'Home Team', 'wp-livescore-la' ), value: 'home' },
								{ label: __( 'Away Team', 'wp-livescore-la' ), value: 'away' }
							],
							onChange: function ( value ) { setAttributes( { teamSide: value } ); }
						} ),
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
							label: __( 'Show images', 'wp-livescore-la' ),
							checked: !! attributes.showImages,
							onChange: function ( value ) { setAttributes( { showImages: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show meta', 'wp-livescore-la' ),
							checked: !! attributes.showMeta,
							onChange: function ( value ) { setAttributes( { showMeta: value } ); }
						} ),
						attributes.showMeta ? el( ToggleControl, {
							label: __( 'Show jersey number', 'wp-livescore-la' ),
							checked: !! attributes.showNumber,
							onChange: function ( value ) { setAttributes( { showNumber: value } ); }
						} ) : null,
						attributes.showMeta ? el( ToggleControl, {
							label: __( 'Show position', 'wp-livescore-la' ),
							checked: !! attributes.showPosition,
							onChange: function ( value ) { setAttributes( { showPosition: value } ); }
						} ) : null,
						el( ToggleControl, {
							label: __( 'Make players links', 'wp-livescore-la' ),
							checked: !! attributes.makeLinks,
							onChange: function ( value ) { setAttributes( { makeLinks: value } ); }
						} ),
						el( RangeControl, {
							label: __( 'Columns', 'wp-livescore-la' ),
							value: attributes.columns || 2,
							min: 1,
							max: 4,
							step: 1,
							onChange: function ( value ) { setAttributes( { columns: parseInt( value, 10 ) || 2 } ); }
						} ),
						el( RangeControl, {
							label: __( 'Players per team', 'wp-livescore-la' ),
							value: attributes.postsPerPage || 50,
							min: 1,
							max: 200,
							step: 1,
							onChange: function ( value ) { setAttributes( { postsPerPage: parseInt( value, 10 ) || 50 } ); }
						} ),
						el( TextControl, {
							label: __( 'Empty message', 'wp-livescore-la' ),
							value: attributes.emptyMessage,
							onChange: function ( value ) { setAttributes( { emptyMessage: value } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/related-players', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
