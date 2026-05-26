( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	const el = element.createElement;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const ServerSideRender = serverSideRender;
	const __ = i18n.__;

	blocks.registerBlockType( 'wp-livescore/match-counter', {
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
						{ title: __( 'Match Counter', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual League ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.leagueId || '',
							onChange: function ( value ) { setAttributes( { leagueId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show all matches', 'wp-livescore-la' ),
							checked: !! attributes.showAll,
							onChange: function ( value ) { setAttributes( { showAll: value } ); }
						} ),
						attributes.showAll ? el( TextControl, {
							label: __( 'All label', 'wp-livescore-la' ),
							value: attributes.allLabel || '',
							onChange: function ( value ) { setAttributes( { allLabel: value } ); }
						} ) : null,
						el( ToggleControl, {
							label: __( 'Show upcoming matches', 'wp-livescore-la' ),
							checked: !! attributes.showUpcoming,
							onChange: function ( value ) { setAttributes( { showUpcoming: value } ); }
						} ),
						attributes.showUpcoming ? el( TextControl, {
							label: __( 'Upcoming label', 'wp-livescore-la' ),
							value: attributes.upcomingLabel || '',
							onChange: function ( value ) { setAttributes( { upcomingLabel: value } ); }
						} ) : null,
						el( ToggleControl, {
							label: __( 'Show live matches', 'wp-livescore-la' ),
							checked: !! attributes.showLive,
							onChange: function ( value ) { setAttributes( { showLive: value } ); }
						} ),
						attributes.showLive ? el( TextControl, {
							label: __( 'Live label', 'wp-livescore-la' ),
							value: attributes.liveLabel || '',
							onChange: function ( value ) { setAttributes( { liveLabel: value } ); }
						} ) : null,
						el( ToggleControl, {
							label: __( 'Show today matches', 'wp-livescore-la' ),
							checked: !! attributes.showToday,
							onChange: function ( value ) { setAttributes( { showToday: value } ); }
						} ),
						attributes.showToday ? el( TextControl, {
							label: __( 'Today label', 'wp-livescore-la' ),
							value: attributes.todayLabel || '',
							onChange: function ( value ) { setAttributes( { todayLabel: value } ); }
						} ) : null,
						el( ToggleControl, {
							label: __( 'Show past matches', 'wp-livescore-la' ),
							checked: !! attributes.showPast,
							onChange: function ( value ) { setAttributes( { showPast: value } ); }
						} ),
						attributes.showPast ? el( TextControl, {
							label: __( 'Past label', 'wp-livescore-la' ),
							value: attributes.pastLabel || '',
							onChange: function ( value ) { setAttributes( { pastLabel: value } ); }
						} ) : null
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/match-counter', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
