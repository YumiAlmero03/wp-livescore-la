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

	const playerFields = [
		{ label: __( 'API ID', 'wp-livescore-la' ), value: '_player_api_id' },
		{ label: __( 'Sport ID', 'wp-livescore-la' ), value: '_player_sport_id' },
		{ label: __( 'Sport Name', 'wp-livescore-la' ), value: '_player_sport_name' },
		{ label: __( 'Sport Slug', 'wp-livescore-la' ), value: '_player_sport_slug' },
		{ label: __( 'Team ID', 'wp-livescore-la' ), value: '_player_team_id' },
		{ label: __( 'Team Name', 'wp-livescore-la' ), value: '_player_team_name' },
		{ label: __( 'Team Slug', 'wp-livescore-la' ), value: '_player_team_slug' },
		{ label: __( 'Country', 'wp-livescore-la' ), value: '_player_country' },
		{ label: __( 'Birthday', 'wp-livescore-la' ), value: '_player_birthday' },
		{ label: __( 'Preferred Foot', 'wp-livescore-la' ), value: '_player_foot' },
		{ label: __( 'Height', 'wp-livescore-la' ), value: '_player_height' },
		{ label: __( 'Weight', 'wp-livescore-la' ), value: '_player_weight' },
		{ label: __( 'Gender', 'wp-livescore-la' ), value: '_player_gender' },
		{ label: __( 'Position', 'wp-livescore-la' ), value: '_player_position' },
		{ label: __( 'Jersey Number', 'wp-livescore-la' ), value: '_player_number' },
		{ label: __( 'Status', 'wp-livescore-la' ), value: '_player_status' }
	];

	const iconOptions = [
		{ label: __( 'No icon', 'wp-livescore-la' ), value: '' },
		{ label: __( 'ID', 'wp-livescore-la' ), value: 'id' },
		{ label: __( 'Team', 'wp-livescore-la' ), value: 'groups' },
		{ label: __( 'Flag', 'wp-livescore-la' ), value: 'flag' },
		{ label: __( 'Calendar', 'wp-livescore-la' ), value: 'calendar-alt' },
		{ label: __( 'Star', 'wp-livescore-la' ), value: 'star-filled' },
		{ label: __( 'Award', 'wp-livescore-la' ), value: 'awards' },
		{ label: __( 'Status', 'wp-livescore-la' ), value: 'yes-alt' },
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

	blocks.registerBlockType( 'wp-livescore/player-data', {
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
						{ title: __( 'Player Data', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual Player ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.playerId || '',
							onChange: function ( value ) { setAttributes( { playerId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( SelectControl, {
							label: __( 'Custom field', 'wp-livescore-la' ),
							value: attributes.dataField || '_player_position',
							options: playerFields,
							onChange: function ( value ) { setAttributes( { dataField: value } ); }
						} ),
						el( SelectControl, {
							label: __( 'Icon', 'wp-livescore-la' ),
							value: attributes.icon || '',
							options: iconOptions,
							onChange: function ( value ) { setAttributes( { icon: value } ); }
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
				el( ServerSideRender, { block: 'wp-livescore/player-data', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
