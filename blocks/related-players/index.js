( function ( blocks, blockEditor, components, element, i18n ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const InnerBlocks = blockEditor.InnerBlocks;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const RangeControl = components.RangeControl;
	const SelectControl = components.SelectControl;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const __ = i18n.__;

	const template = [
		[
			'core/group',
			{ className: 'wp-livescore-la-related-players__card' },
			[
				[ 'core/post-featured-image', { isLink: true, width: '64px', height: '64px', scale: 'cover' } ],
				[
					'core/group',
					{ className: 'wp-livescore-la-related-players__content' },
					[
						[ 'core/post-title', { level: 3, isLink: true } ],
						[ 'wp-livescore/player-data', { dataField: '_player_position' } ],
						[ 'wp-livescore/player-data', { dataField: '_player_number', prefix: '#' } ]
					]
				]
			]
		]
	];

	blocks.registerBlockType( 'wp-livescore/related-players', {
		edit: function ( props ) {
			const attributes = props.attributes;
			const setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps( { className: 'wp-livescore-la-related-players wp-livescore-la-related-players--editor' } ),
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
						el( TextControl, {
							label: __( 'Manual Team ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.teamId || '',
							onChange: function ( value ) { setAttributes( { teamId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( SelectControl, {
							label: __( 'Team from match', 'wp-livescore-la' ),
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
				el(
					'div',
					{ className: 'wp-livescore-la-related-players__template-editor' },
					el( 'div', { className: 'wp-livescore-la-related-players__template-label' }, __( 'Player item template', 'wp-livescore-la' ) ),
					el( InnerBlocks, {
						template: template,
						templateLock: false
					} )
				)
			);
		},
		save: function () {
			return el( InnerBlocks.Content );
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n );
