( function ( blocks, blockEditor, components, compose, element, hooks, i18n ) {
	const el = element.createElement;
	const __ = i18n.__;
	const InspectorControls = blockEditor.InspectorControls;
	const PanelBody = components.PanelBody;
	const RangeControl = components.RangeControl;
	const SelectControl = components.SelectControl;
	const TextControl = components.TextControl;
	const ToggleControl = components.ToggleControl;
	const variationName = 'wp-livescore/match-query-loop';
	const relatedVariationName = 'wp-livescore/related-matches-query-loop';
	const featuredVariations = [
		{
			name: 'wp-livescore/featured-leagues-query-loop',
			title: __( 'Featured Leagues Query Loop', 'wp-livescore-la' ),
			description: __( 'Displays League posts with featured images and League data.', 'wp-livescore-la' ),
			icon: 'groups',
			postType: 'league',
			perPage: 6,
			innerBlocks: [
				[ 'core/post-featured-image', { isLink: true, aspectRatio: '1', width: '100%' } ],
				[ 'core/post-title', { isLink: true } ],
				[ 'wp-livescore/league-data', { dataField: 'country', title: __( 'Country', 'wp-livescore-la' ) } ],
				[ 'wp-livescore/league-data', { dataField: 'sports', title: __( 'Sport', 'wp-livescore-la' ) } ],
				[ 'wp-livescore/league-data', { dataField: 'strCurrentSeason', title: __( 'Season', 'wp-livescore-la' ) } ]
			]
		},
		{
			name: 'wp-livescore/featured-teams-query-loop',
			title: __( 'Featured Teams Query Loop', 'wp-livescore-la' ),
			description: __( 'Displays Team posts with featured images and Team data.', 'wp-livescore-la' ),
			icon: 'shield',
			postType: 'team',
			perPage: 8,
			innerBlocks: [
				[ 'core/post-featured-image', { isLink: true, aspectRatio: '1', width: '100%' } ],
				[ 'core/post-title', { isLink: true } ],
				[ 'wp-livescore/team-data', { dataField: '_team_sport_name', title: __( 'Sport', 'wp-livescore-la' ) } ],
				[ 'wp-livescore/team-data', { dataField: '_team_country_name', title: __( 'Country', 'wp-livescore-la' ) } ],
				[ 'wp-livescore/team-data', { dataField: '_team_coach_name', title: __( 'Coach', 'wp-livescore-la' ) } ]
			]
		},
		{
			name: 'wp-livescore/featured-players-query-loop',
			title: __( 'Featured Players Query Loop', 'wp-livescore-la' ),
			description: __( 'Displays Player posts with featured images and Player data.', 'wp-livescore-la' ),
			icon: 'id',
			postType: 'player',
			perPage: 8,
			innerBlocks: [
				[ 'core/post-featured-image', { isLink: true, aspectRatio: '1', width: '100%' } ],
				[ 'core/post-title', { isLink: true } ],
				[ 'wp-livescore/player-data', { dataField: '_player_position', title: __( 'Position', 'wp-livescore-la' ) } ],
				[ 'wp-livescore/player-data', { dataField: '_player_team_name', title: __( 'Team', 'wp-livescore-la' ) } ],
				[ 'wp-livescore/player-data', { dataField: '_player_country', title: __( 'Country', 'wp-livescore-la' ) } ]
			]
		},
		{
			name: 'wp-livescore/featured-matches-query-loop',
			title: __( 'Featured Matches Query Loop', 'wp-livescore-la' ),
			description: __( 'Displays Match posts with featured images and Match data.', 'wp-livescore-la' ),
			icon: 'calendar-alt',
			postType: 'match',
			perPage: 6,
			innerBlocks: [
				[ 'core/post-featured-image', { isLink: true, aspectRatio: '16/9', width: '100%' } ],
				[ 'core/post-title', { isLink: true } ],
				[ 'wp-livescore/match-data', { dataField: '_match_date', title: __( 'Date', 'wp-livescore-la' ) } ],
				[ 'wp-livescore/match-data', { dataField: '_match_home_team_name', title: __( 'Home', 'wp-livescore-la' ) } ],
				[ 'wp-livescore/match-data', { dataField: '_match_away_team_name', title: __( 'Away', 'wp-livescore-la' ) } ]
			]
		},
		{
			name: 'wp-livescore/featured-countries-query-loop',
			title: __( 'Featured Countries Query Loop', 'wp-livescore-la' ),
			description: __( 'Displays Country posts with featured images.', 'wp-livescore-la' ),
			icon: 'flag',
			postType: 'country',
			perPage: 8,
			innerBlocks: [
				[ 'core/post-featured-image', { isLink: true, aspectRatio: '4/3', width: '100%' } ],
				[ 'core/post-title', { isLink: true } ]
			]
		}
	];

	function registerFeaturedQueryLoopVariation( variation ) {
		blocks.registerBlockVariation( 'core/query', {
			name: variation.name,
			title: variation.title,
			description: variation.description,
			icon: variation.icon,
			attributes: {
				namespace: variation.name,
				query: {
					perPage: variation.perPage,
					pages: 0,
					offset: 0,
					postType: variation.postType,
					order: 'asc',
					orderBy: 'title',
					author: '',
					search: '',
					exclude: [],
					sticky: '',
					inherit: false
				}
			},
			allowedControls: [ 'order', 'search' ],
			isActive: function ( attributes ) {
				return attributes.namespace === variation.name && attributes.query && attributes.query.postType === variation.postType;
			},
			innerBlocks: [
				[
					'core/post-template',
					{},
					variation.innerBlocks
				],
				[ 'core/query-pagination' ],
				[ 'core/query-no-results' ]
			],
			scope: [ 'inserter' ]
		} );
	}

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
				wpLivescoreMatchLeagueApiId: '',
				wpLivescoreLoadMore: false
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

	featuredVariations.forEach( registerFeaturedQueryLoopVariation );

	blocks.registerBlockVariation( 'core/query', {
		name: relatedVariationName,
		title: __( 'Related Matches Query Loop', 'wp-livescore-la' ),
		description: __( 'Displays editable Match posts related to the current League or Team.', 'wp-livescore-la' ),
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
				wpLivescoreRelatedLeagueId: 0,
				wpLivescoreRelatedTeamId: 0
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
					[ 'wp-livescore/opponent-team', { imageSize: 3, imagePosition: 'left' } ]
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
						el( TextControl, {
							label: __( 'Offset', 'wp-livescore-la' ),
							type: 'number',
							value: query.offset || 0,
							onChange: function ( value ) {
								props.setAttributes( {
									query: Object.assign( {}, query, {
										offset: parseInt( value, 10 ) || 0
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
						isMatchQueryLoop && el( ToggleControl, {
							label: __( 'Show load more button', 'wp-livescore-la' ),
							checked: !! query.wpLivescoreLoadMore,
							onChange: function ( value ) {
								props.setAttributes( {
									query: Object.assign( {}, query, {
										wpLivescoreLoadMore: value
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
						,
						isRelatedMatchesQueryLoop && el( TextControl, {
							label: __( 'Manual Team ID', 'wp-livescore-la' ),
							type: 'number',
							value: query.wpLivescoreRelatedTeamId || '',
							onChange: function ( value ) {
								props.setAttributes( {
									query: Object.assign( {}, query, {
										wpLivescoreRelatedTeamId: parseInt( value, 10 ) || 0
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
