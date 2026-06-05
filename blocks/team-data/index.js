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

	const teamFields = [
		{ label: __( 'Title', 'wp-livescore-la' ), value: '__title' },
		{ label: __( 'API ID', 'wp-livescore-la' ), value: '_team_api_id' },
		{ label: __( 'Shortcut Name', 'wp-livescore-la' ), value: '_team_short_name' },
		{ label: __( 'Logo URL', 'wp-livescore-la' ), value: '_team_logo' },
		{ label: __( 'Website', 'wp-livescore-la' ), value: '_team_website' },
		{ label: __( 'Facebook', 'wp-livescore-la' ), value: '_team_facebook' },
		{ label: __( 'Instagram', 'wp-livescore-la' ), value: '_team_instagram' },
		{ label: __( 'Twitter/X', 'wp-livescore-la' ), value: '_team_twitter' },
		{ label: __( 'YouTube', 'wp-livescore-la' ), value: '_team_youtube' },
		{ label: __( 'Recent Form', 'wp-livescore-la' ), value: '_team_recent_form' },
		{ label: __( 'Coach Name', 'wp-livescore-la' ), value: '_team_coach_name' },
		{ label: __( 'Status', 'wp-livescore-la' ), value: '_team_status' },
		{ label: __( 'Sport ID', 'wp-livescore-la' ), value: '_team_sport_id' },
		{ label: __( 'Sport Name', 'wp-livescore-la' ), value: '_team_sport_name' },
		{ label: __( 'Sport Slug', 'wp-livescore-la' ), value: '_team_sport_slug' },
		{ label: __( 'Country ID', 'wp-livescore-la' ), value: '_team_country_id' },
		{ label: __( 'Country Name', 'wp-livescore-la' ), value: '_team_country_name' },
		{ label: __( 'Country Slug', 'wp-livescore-la' ), value: '_team_country_slug' },
		{ label: __( 'Country Code', 'wp-livescore-la' ), value: '_team_country_code' },
		{ label: __( 'Continent', 'wp-livescore-la' ), value: '_team_continent' }
	];

	const iconOptions = [
		{ label: __( 'No icon', 'wp-livescore-la' ), value: '' },
		{ label: __( 'Website', 'wp-livescore-la' ), value: 'admin-site-alt3' },
		{ label: __( 'Shield', 'wp-livescore-la' ), value: 'shield' },
		{ label: __( 'Flag', 'wp-livescore-la' ), value: 'flag' },
		{ label: __( 'Groups', 'wp-livescore-la' ), value: 'groups' },
		{ label: __( 'Link', 'wp-livescore-la' ), value: 'admin-links' },
		{ label: __( 'Image', 'wp-livescore-la' ), value: 'format-image' },
		{ label: __( 'Status', 'wp-livescore-la' ), value: 'yes-alt' },
		{ label: __( 'Location', 'wp-livescore-la' ), value: 'location' }
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

	blocks.registerBlockType( 'wp-livescore/team-data', {
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
						{ title: __( 'Team Data', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual Team ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.teamId || '',
							onChange: function ( value ) { setAttributes( { teamId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( SelectControl, {
							label: __( 'Custom field', 'wp-livescore-la' ),
							value: attributes.dataField || '_team_short_name',
							options: teamFields,
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
				el( ServerSideRender, { block: 'wp-livescore/team-data', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
