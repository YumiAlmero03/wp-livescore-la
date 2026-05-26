( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'wp-livescore-la/league-filters', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: __( 'Filter Display', 'wp-livescore-la' ),
							initialOpen: true
						},
						el( ToggleControl, {
							label: __( 'Show title', 'wp-livescore-la' ),
							checked: attributes.showTitle,
							onChange: function ( value ) {
								setAttributes( { showTitle: value } );
							}
						} ),
						attributes.showTitle &&
							el( TextControl, {
								label: __( 'Title', 'wp-livescore-la' ),
								value: attributes.title,
								onChange: function ( value ) {
									setAttributes( { title: value } );
								}
							} ),
						el( ToggleControl, {
							label: __( 'Sport filter', 'wp-livescore-la' ),
							checked: attributes.showSport,
							onChange: function ( value ) {
								setAttributes( { showSport: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Country filter', 'wp-livescore-la' ),
							checked: attributes.showCountry,
							onChange: function ( value ) {
								setAttributes( { showCountry: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Button text', 'wp-livescore-la' ),
							value: attributes.buttonText,
							onChange: function ( value ) {
								setAttributes( { buttonText: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Reset text', 'wp-livescore-la' ),
							value: attributes.resetText,
							onChange: function ( value ) {
								setAttributes( { resetText: value } );
							}
						} )
					)
				),
				el( ServerSideRender, {
					block: 'wp-livescore-la/league-filters',
					attributes: attributes
				} )
			);
		},
		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
);
