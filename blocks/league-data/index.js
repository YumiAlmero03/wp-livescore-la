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

	const iconOptions = [
		{ label: __( 'No icon', 'wp-livescore-la' ), value: '' },
		{ label: __( 'Calendar', 'wp-livescore-la' ), value: 'calendar-alt' },
		{ label: __( 'Clock', 'wp-livescore-la' ), value: 'clock' },
		{ label: __( 'Location', 'wp-livescore-la' ), value: 'location' },
		{ label: __( 'Flag', 'wp-livescore-la' ), value: 'flag' },
		{ label: __( 'Groups', 'wp-livescore-la' ), value: 'groups' },
		{ label: __( 'Scoreboard', 'wp-livescore-la' ), value: 'chart-bar' },
		{ label: __( 'Award', 'wp-livescore-la' ), value: 'awards' },
		{ label: __( 'Link', 'wp-livescore-la' ), value: 'admin-links' },
		{ label: __( 'Star', 'wp-livescore-la' ), value: 'star-filled' },
		{ label: __( 'Shield', 'wp-livescore-la' ), value: 'shield' }
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

	blocks.registerBlockType( 'wp-livescore/league-data', {
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
						{ title: __( 'League Data', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Manual League ID', 'wp-livescore-la' ),
							type: 'number',
							value: attributes.leagueId || '',
							onChange: function ( value ) { setAttributes( { leagueId: parseInt( value, 10 ) || 0 } ); }
						} ),
						el( SelectControl, {
							label: __( 'Custom field', 'wp-livescore-la' ),
							value: attributes.dataField || 'country',
							options: [
								{ label: __( 'Title', 'wp-livescore-la' ), value: '__title' },
								{ label: __( 'Country', 'wp-livescore-la' ), value: 'country' },
								{ label: __( 'Sports', 'wp-livescore-la' ), value: 'sports' },
								{ label: __( 'API ID', 'wp-livescore-la' ), value: 'api_id' },
								{ label: __( 'API Source', 'wp-livescore-la' ), value: 'api_source' },
								{ label: __( 'SportScore Slug', 'wp-livescore-la' ), value: 'sportscore_slug' },
								{ label: __( 'Current Season', 'wp-livescore-la' ), value: 'strCurrentSeason' },
								{ label: __( 'Formed Year', 'wp-livescore-la' ), value: 'intFormedYear' },
								{ label: __( 'First Event', 'wp-livescore-la' ), value: 'dateFirstEvent' },
								{ label: __( 'Website', 'wp-livescore-la' ), value: 'strWebsite' },
								{ label: __( 'Facebook', 'wp-livescore-la' ), value: 'strFacebook' },
								{ label: __( 'Instagram', 'wp-livescore-la' ), value: 'strInstagram' },
								{ label: __( 'Twitter', 'wp-livescore-la' ), value: 'strTwitter' },
								{ label: __( 'Youtube', 'wp-livescore-la' ), value: 'strYoutube' },
								{ label: __( 'RSS', 'wp-livescore-la' ), value: 'strRSS' },
								{ label: __( 'Banner', 'wp-livescore-la' ), value: 'strBanner' }
							],
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
						el( TextControl, { label: __( 'Empty message', 'wp-livescore-la' ), value: attributes.emptyMessage, onChange: function ( value ) { setAttributes( { emptyMessage: value } ); } } )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/league-data', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
