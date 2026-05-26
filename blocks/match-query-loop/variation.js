( function ( blocks, blockEditor, components, compose, element, hooks, i18n ) {
	const el = element.createElement;
	const __ = i18n.__;
	const InspectorControls = blockEditor.InspectorControls;
	const PanelBody = components.PanelBody;
	const RangeControl = components.RangeControl;
	const SelectControl = components.SelectControl;
	const TextControl = components.TextControl;
	const variationName = 'wp-livescore/match-query-loop';
	const relatedVariationName = 'wp-livescore/related-matches-query-loop';

	blocks.registerBlockVariation( 'core/query', {
		name: variationName,
		title: __( 'Match Query Loop', 'wp-livescore-la' ),
		description: __( 'Displays Matches sorted by Match Date.', 'wp-livescore-la' ),
		icon: 'calendar-alt',
		attributes: {
			namespace: variationName,
			query: {
				perPage: 10,
				pages: 0,
				offset: 0,
				postType: 'match',
				order: 'asc',
				orderBy: 'date',
				author: '',
				search: '',
				exclude: [],
				sticky: '',
				inherit: false,
				wpLivescoreMatchDate: true,
				wpLivescoreMatchDateFilter: 'all',
				wpLivescoreMatchCustomDate: '',
				wpLivescoreMatchLeagueApiId: ''
			}
		},
		allowedControls: [ 'order', 'search' ],
		isActive: function ( attributes ) {
			return attributes.namespace === variationName && attributes.query && attributes.query.postType === 'match';
		},
		innerBlocks: [
			[
				'core/post-template',
				{},
				[
					[ 'core/post-title', { isLink: true } ],
					[ 'core/post-excerpt' ]
				]
			],
			[ 'core/query-pagination' ],
			[ 'core/query-no-results' ]
		],
		scope: [ 'inserter' ]
	} );

	blocks.registerBlockVariation( 'core/query', {
		name: relatedVariationName,
		title: __( 'Related Matches Query Loop', 'wp-livescore-la' ),
		description: __( 'Displays editable Match posts related to the current League.', 'wp-livescore-la' ),
		icon: 'calendar-alt',
		attributes: {
			namespace: relatedVariationName,
			query: {
				perPage: 6,
				pages: 0,
				offset: 0,
				postType: 'match',
				order: 'asc',
				orderBy: 'date',
				author: '',
				search: '',
				exclude: [],
				sticky: '',
				inherit: false,
				wpLivescoreMatchDate: true,
				wpLivescoreMatchDateFilter: 'all',
				wpLivescoreMatchCustomDate: '',
				wpLivescoreMatchLeagueApiId: '',
				wpLivescoreRelatedMatches: true,
				wpLivescoreRelatedLeagueId: 0
			}
		},
		allowedControls: [ 'order', 'search' ],
		isActive: function ( attributes ) {
			return attributes.namespace === relatedVariationName && attributes.query && attributes.query.postType === 'match';
		},
		innerBlocks: [
			[
				'core/post-template',
				{},
				[
					[ 'core/post-title', { isLink: true } ],
					[ 'wp-livescore/match-data', { dataField: '_match_date' } ],
					[ 'wp-livescore/match-data', { dataField: '_match_home_team_name' } ],
					[ 'wp-livescore/match-data', { dataField: '_match_away_team_name' } ]
				]
			],
			[ 'core/query-pagination' ],
			[ 'core/query-no-results' ]
		],
		scope: [ 'inserter' ]
	} );

	const withMatchQueryLoopControls = compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			const attributes = props.attributes || {};
			const query = attributes.query || {};
			const isMatchQueryLoop = props.name === 'core/query' && attributes.namespace === variationName && query.postType === 'match';
			const isRelatedMatchesQueryLoop = props.name === 'core/query' && attributes.namespace === relatedVariationName && query.postType === 'match';

			if ( ! isMatchQueryLoop && ! isRelatedMatchesQueryLoop ) {
				return el( BlockEdit, props );
			}

			return el(
				element.Fragment,
				null,
				el( BlockEdit, props ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: isRelatedMatchesQueryLoop ? __( 'Related Matches Query Loop', 'wp-livescore-la' ) : __( 'Match Query Loop', 'wp-livescore-la' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Posts per page', 'wp-livescore-la' ),
							value: query.perPage || 10,
							min: 1,
							max: 100,
							onChange: function ( value ) {
								props.setAttributes( {
									query: Object.assign( {}, query, {
										perPage: parseInt( value, 10 ) || 10
									} )
								} );
							}
						} ),
						el( SelectControl, {
							label: __( 'Date filter', 'wp-livescore-la' ),
							value: query.wpLivescoreMatchDateFilter || 'all',
							options: [
								{ label: __( 'All', 'wp-livescore-la' ), value: 'all' },
								{ label: __( 'Live', 'wp-livescore-la' ), value: 'live' },
								{ label: __( 'Today', 'wp-livescore-la' ), value: 'today' },
								{ label: __( 'Upcoming', 'wp-livescore-la' ), value: 'upcoming' },
								{ label: __( 'Past', 'wp-livescore-la' ), value: 'past' },
								{ label: __( 'Results', 'wp-livescore-la' ), value: 'results' },
								{ label: __( 'Custom date', 'wp-livescore-la' ), value: 'custom' }
							],
							onChange: function ( value ) {
								props.setAttributes( {
									query: Object.assign( {}, query, {
										wpLivescoreMatchDateFilter: value
									} )
								} );
							}
						} ),
						'custom' === query.wpLivescoreMatchDateFilter && el( TextControl, {
							label: __( 'Custom date', 'wp-livescore-la' ),
							type: 'date',
							value: query.wpLivescoreMatchCustomDate || '',
							onChange: function ( value ) {
								props.setAttributes( {
									query: Object.assign( {}, query, {
										wpLivescoreMatchCustomDate: value
									} )
								} );
							}
						} ),
						el( TextControl, {
							label: __( 'League API ID', 'wp-livescore-la' ),
							value: query.wpLivescoreMatchLeagueApiId || '',
							onChange: function ( value ) {
								props.setAttributes( {
									query: Object.assign( {}, query, {
										wpLivescoreMatchLeagueApiId: value
									} )
								} );
							}
						} ),
						isRelatedMatchesQueryLoop && el( TextControl, {
							label: __( 'Manual League ID', 'wp-livescore-la' ),
							type: 'number',
							value: query.wpLivescoreRelatedLeagueId || '',
							onChange: function ( value ) {
								props.setAttributes( {
									query: Object.assign( {}, query, {
										wpLivescoreRelatedLeagueId: parseInt( value, 10 ) || 0
									} )
								} );
							}
						} )
					)
				)
			);
		};
	}, 'withMatchQueryLoopControls' );

	hooks.addFilter(
		'editor.BlockEdit',
		'wp-livescore-la/match-query-loop-controls',
		withMatchQueryLoopControls
	);
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.compose, window.wp.element, window.wp.hooks, window.wp.i18n );
