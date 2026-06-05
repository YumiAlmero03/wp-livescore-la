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

	const matchFields = [
		{ label: __( 'Title', 'wp-livescore-la' ), value: '__title' },
		{ label: __( 'API ID', 'wp-livescore-la' ), value: '_match_api_id' },
		{ label: __( 'SportScore Slug', 'wp-livescore-la' ), value: '_match_sportscore_slug' },
		{ label: __( 'Sport ID', 'wp-livescore-la' ), value: '_match_sport_id' },
		{ label: __( 'Sport Name', 'wp-livescore-la' ), value: '_match_sport_name' },
		{ label: __( 'Sport Slug', 'wp-livescore-la' ), value: '_match_sport_slug' },
		{ label: __( 'Country ID', 'wp-livescore-la' ), value: '_match_country_id' },
		{ label: __( 'Country Name', 'wp-livescore-la' ), value: '_match_country_name' },
		{ label: __( 'Country Slug', 'wp-livescore-la' ), value: '_match_country_slug' },
		{ label: __( 'Country Code', 'wp-livescore-la' ), value: '_match_country_code' },
		{ label: __( 'Continent', 'wp-livescore-la' ), value: '_match_continent' },
		{ label: __( 'League ID', 'wp-livescore-la' ), value: '_match_league_id' },
		{ label: __( 'League Name', 'wp-livescore-la' ), value: '_match_league_name' },
		{ label: __( 'League Slug', 'wp-livescore-la' ), value: '_match_league_slug' },
		{ label: __( 'Season ID', 'wp-livescore-la' ), value: '_match_season_id' },
		{ label: __( 'Season Name', 'wp-livescore-la' ), value: '_match_season_name' },
		{ label: __( 'Season Slug', 'wp-livescore-la' ), value: '_match_season_slug' },
		{ label: __( 'Home Team ID', 'wp-livescore-la' ), value: '_match_home_team_id' },
		{ label: __( 'Home Team Name', 'wp-livescore-la' ), value: '_match_home_team_name' },
		{ label: __( 'Home Team Slug', 'wp-livescore-la' ), value: '_match_home_team_slug' },
		{ label: __( 'Away Team ID', 'wp-livescore-la' ), value: '_match_away_team_id' },
		{ label: __( 'Away Team Name', 'wp-livescore-la' ), value: '_match_away_team_name' },
		{ label: __( 'Away Team Slug', 'wp-livescore-la' ), value: '_match_away_team_slug' },
		{ label: __( 'Match Date', 'wp-livescore-la' ), value: '_match_date' },
		{ label: __( 'Match Time', 'wp-livescore-la' ), value: '_match_time' },
		{ label: __( 'Match Datetime', 'wp-livescore-la' ), value: '_match_datetime' },
		{ label: __( 'Timezone', 'wp-livescore-la' ), value: '_match_timezone' },
		{ label: __( 'Match Status', 'wp-livescore-la' ), value: '_match_status' },
		{ label: __( 'Home Score', 'wp-livescore-la' ), value: '_match_home_score' },
		{ label: __( 'Away Score', 'wp-livescore-la' ), value: '_match_away_score' },
		{ label: __( 'Home Win Percentage', 'wp-livescore-la' ), value: '_match_home_win_percentage' },
		{ label: __( 'Away Win Percentage', 'wp-livescore-la' ), value: '_match_away_win_percentage' },
		{ label: __( 'Draw Percentage', 'wp-livescore-la' ), value: '_match_draw_percentage' },
		{ label: __( 'Best Betting Angle', 'wp-livescore-la' ), value: '_match_best_betting_angle' },
		{ label: __( 'Correct Score Pick', 'wp-livescore-la' ), value: '_match_correct_score_pick' },
		{ label: __( 'Winner Prediction', 'wp-livescore-la' ), value: '_match_winner_prediction' },
		{ label: __( 'Group Name', 'wp-livescore-la' ), value: '_match_group_name' },
		{ label: __( 'Referee', 'wp-livescore-la' ), value: '_match_referee' },
		{ label: __( 'Venue', 'wp-livescore-la' ), value: '_match_venue' },
		{ label: __( 'Status Visibility', 'wp-livescore-la' ), value: '_match_status_visibility' }
	];

	const iconOptions = [
		{ label: __( 'No icon', 'wp-livescore-la' ), value: '' },
		{ label: __( 'Calendar', 'wp-livescore-la' ), value: 'calendar-alt' },
		{ label: __( 'Clock', 'wp-livescore-la' ), value: 'clock' },
		{ label: __( 'Location', 'wp-livescore-la' ), value: 'location' },
		{ label: __( 'Flag', 'wp-livescore-la' ), value: 'flag' },
		{ label: __( 'Groups', 'wp-livescore-la' ), value: 'groups' },
		{ label: __( 'Referee / Whistle', 'wp-livescore-la' ), value: 'megaphone' },
		{ label: __( 'Scoreboard', 'wp-livescore-la' ), value: 'chart-bar' },
		{ label: __( 'Award', 'wp-livescore-la' ), value: 'awards' },
		{ label: __( 'Link', 'wp-livescore-la' ), value: 'admin-links' }
	];

	const textTransformOptions = [
		{ label: __( 'Default', 'wp-livescore-la' ), value: '' },
		{ label: __( 'Uppercase', 'wp-livescore-la' ), value: 'uppercase' },
		{ label: __( 'Lowercase', 'wp-livescore-la' ), value: 'lowercase' },
		{ label: __( 'Capitalize', 'wp-livescore-la' ), value: 'capitalize' },
		{ label: __( 'Normal case', 'wp-livescore-la' ), value: 'none' }
	];

	const textAlignOptions = [
		{ label: __( 'Default', 'wp-livescore-la' ), value: '' },
		{ label: __( 'Left', 'wp-livescore-la' ), value: 'left' },
		{ label: __( 'Center', 'wp-livescore-la' ), value: 'center' },
		{ label: __( 'Right', 'wp-livescore-la' ), value: 'right' },
		{ label: __( 'Justify', 'wp-livescore-la' ), value: 'justify' }
	];

	const titleTagOptions = [
		{ label: __( 'Default', 'wp-livescore-la' ), value: 'div' },
		{ label: 'H2', value: 'h2' },
		{ label: 'H3', value: 'h3' },
		{ label: 'H4', value: 'h4' },
		{ label: 'H5', value: 'h5' },
		{ label: 'H6', value: 'h6' }
	];

	blocks.registerBlockType( 'wp-livescore/match-data', {
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
						{ title: __( 'Match Data', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual Match ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.matchId || '',
							onChange: function ( value ) { setAttributes( { matchId: parseInt( value, 10 ) || 0 } ); }
						} ),
							el( SelectControl, {
								label: __( 'Custom field', 'wp-livescore-la' ),
								value: attributes.dataField || '_match_league_name',
								options: matchFields,
								onChange: function ( value ) { setAttributes( { dataField: value } ); }
							} ),
							el( SelectControl, {
								label: __( 'Icon', 'wp-livescore-la' ),
								value: attributes.icon || '',
								options: iconOptions,
								onChange: function ( value ) { setAttributes( { icon: value } ); }
							} ),
							el( TextControl, {
								label: __( 'Title', 'wp-livescore-la' ),
								value: attributes.title || '',
								onChange: function ( value ) { setAttributes( { title: value } ); }
							} ),
							el( SelectControl, {
								label: __( 'Title heading type', 'wp-livescore-la' ),
								value: attributes.titleTag || 'div',
								options: titleTagOptions,
								onChange: function ( value ) { setAttributes( { titleTag: value } ); }
							} ),
							el( TextControl, {
								label: __( 'Prefix text', 'wp-livescore-la' ),
								value: attributes.prefix || '',
								onChange: function ( value ) { setAttributes( { prefix: value } ); }
							} ),
							el( TextControl, {
								label: __( 'Suffix text', 'wp-livescore-la' ),
								value: attributes.suffix || '',
								onChange: function ( value ) { setAttributes( { suffix: value } ); }
							} ),
							el( SelectControl, {
								label: __( 'Letter Case', 'wp-livescore-la' ),
								value: attributes.textTransform || '',
								options: textTransformOptions,
								onChange: function ( value ) { setAttributes( { textTransform: value } ); }
							} ),
							el( SelectControl, {
								label: __( 'Text alignment', 'wp-livescore-la' ),
								value: attributes.textAlign || '',
								options: textAlignOptions,
								onChange: function ( value ) { setAttributes( { textAlign: value } ); }
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
				el( ServerSideRender, { block: 'wp-livescore/match-data', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
