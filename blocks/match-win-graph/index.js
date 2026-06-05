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

	const graphColors = [
		{ label: __( 'Default', 'wp-livescore-la' ), value: '' },
		{ label: __( 'Astra Color 1', 'wp-livescore-la' ), value: 'astra-0' },
		{ label: __( 'Astra Color 2', 'wp-livescore-la' ), value: 'astra-1' },
		{ label: __( 'Astra Color 3', 'wp-livescore-la' ), value: 'astra-2' },
		{ label: __( 'Astra Color 4', 'wp-livescore-la' ), value: 'astra-3' },
		{ label: __( 'Astra Color 5', 'wp-livescore-la' ), value: 'astra-4' },
		{ label: __( 'Astra Color 6', 'wp-livescore-la' ), value: 'astra-5' },
		{ label: __( 'Astra Color 7', 'wp-livescore-la' ), value: 'astra-6' },
		{ label: __( 'Astra Color 8', 'wp-livescore-la' ), value: 'astra-7' },
		{ label: __( 'Astra Color 9', 'wp-livescore-la' ), value: 'astra-8' }
	];

	blocks.registerBlockType( 'wp-livescore/match-win-graph', {
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
						{ title: __( 'Match Win Graph', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual Match ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.matchId || '',
							onChange: function ( value ) {
								setAttributes( { matchId: parseInt( value, 10 ) || 0 } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Show title', 'wp-livescore-la' ),
							checked: !! attributes.showTitle,
							onChange: function ( value ) {
								setAttributes( { showTitle: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Title', 'wp-livescore-la' ),
							value: attributes.title || '',
							onChange: function ( value ) {
								setAttributes( { title: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Empty message', 'wp-livescore-la' ),
							value: attributes.emptyMessage || '',
							onChange: function ( value ) {
								setAttributes( { emptyMessage: value } );
							}
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Colors', 'wp-livescore-la' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Home background color', 'wp-livescore-la' ),
							value: attributes.homeColor || '',
							options: graphColors,
							onChange: function ( value ) {
								setAttributes( { homeColor: value || '' } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Draw background color', 'wp-livescore-la' ),
							value: attributes.drawColor || '',
							options: graphColors,
							onChange: function ( value ) {
								setAttributes( { drawColor: value || '' } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Away background color', 'wp-livescore-la' ),
							value: attributes.awayColor || '',
							options: graphColors,
							onChange: function ( value ) {
								setAttributes( { awayColor: value || '' } );
							}
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/match-win-graph', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
