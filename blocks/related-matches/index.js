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

	blocks.registerBlockType( 'wp-livescore/related-matches', {
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
						{ title: __( 'Related Matches', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, { label: __( 'Section title', 'wp-livescore-la' ), value: attributes.title, onChange: function ( value ) { setAttributes( { title: value } ); } } ),
						el( TextControl, {
							label: __( 'Manual League API ID', 'wp-livescore-la' ),
							value: attributes.leagueApiId || '',
							onChange: function ( value ) { setAttributes( { leagueApiId: value } ); }
						} ),
						el( RangeControl, { label: __( 'Matches to show', 'wp-livescore-la' ), value: attributes.postsPerPage, min: 1, max: 50, onChange: function ( value ) { setAttributes( { postsPerPage: value } ); } } ),
						el( SelectControl, {
							label: __( 'Layout type', 'wp-livescore-la' ),
							value: attributes.layoutType || 'grid',
							options: [
								{ label: __( 'Grid posts', 'wp-livescore-la' ), value: 'grid' },
								{ label: __( 'Carousel', 'wp-livescore-la' ), value: 'carousel' }
							],
							onChange: function ( value ) { setAttributes( { layoutType: value } ); }
						} ),
						el( SelectControl, {
							label: __( 'Matches', 'wp-livescore-la' ),
							value: attributes.dateFilter,
							options: [
								{ label: __( 'All', 'wp-livescore-la' ), value: 'all' },
								{ label: __( 'Live', 'wp-livescore-la' ), value: 'live' },
								{ label: __( 'Upcoming', 'wp-livescore-la' ), value: 'upcoming' },
								{ label: __( 'Past', 'wp-livescore-la' ), value: 'past' },
								{ label: __( 'Today', 'wp-livescore-la' ), value: 'today' },
								{ label: __( 'Custom date', 'wp-livescore-la' ), value: 'custom' }
							],
							onChange: function ( value ) { setAttributes( { dateFilter: value } ); }
						} ),
						'custom' === attributes.dateFilter ? el( TextControl, { label: __( 'Custom date', 'wp-livescore-la' ), type: 'date', value: attributes.customDate, onChange: function ( value ) { setAttributes( { customDate: value } ); } } ) : null,
						el( SelectControl, {
							label: __( 'Display style', 'wp-livescore-la' ),
							value: attributes.displayStyle,
							options: [
								{ label: __( 'List', 'wp-livescore-la' ), value: 'list' },
								{ label: __( 'Compact cards', 'wp-livescore-la' ), value: 'compact' },
								{ label: __( 'Full cards', 'wp-livescore-la' ), value: 'full' }
							],
							onChange: function ( value ) { setAttributes( { displayStyle: value } ); }
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Display', 'wp-livescore-la' ), initialOpen: false },
						el( ToggleControl, { label: __( 'Show league', 'wp-livescore-la' ), checked: attributes.showLeague, onChange: function ( value ) { setAttributes( { showLeague: value } ); } } ),
						el( ToggleControl, { label: __( 'Show season', 'wp-livescore-la' ), checked: attributes.showSeason, onChange: function ( value ) { setAttributes( { showSeason: value } ); } } ),
						el( ToggleControl, { label: __( 'Show venue', 'wp-livescore-la' ), checked: attributes.showVenue, onChange: function ( value ) { setAttributes( { showVenue: value } ); } } ),
						el( ToggleControl, { label: __( 'Show status', 'wp-livescore-la' ), checked: attributes.showStatus, onChange: function ( value ) { setAttributes( { showStatus: value } ); } } ),
						el( ToggleControl, { label: __( 'Show SportScore slug', 'wp-livescore-la' ), checked: attributes.showSportScore, onChange: function ( value ) { setAttributes( { showSportScore: value } ); } } ),
						el( TextControl, { label: __( 'Empty message', 'wp-livescore-la' ), value: attributes.emptyMessage, onChange: function ( value ) { setAttributes( { emptyMessage: value } ); } } )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/related-matches', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
