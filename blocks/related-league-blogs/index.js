( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'wp-livescore-la/related-league-blogs', {
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
							title: __( 'Content', 'wp-livescore-la' ),
							initialOpen: true
						},
						el( TextControl, {
							label: __( 'Title', 'wp-livescore-la' ),
							value: attributes.title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							}
						} ),
						el( RangeControl, {
							label: __( 'Posts per page', 'wp-livescore-la' ),
							value: attributes.postsPerPage,
							min: 1,
							max: 24,
							onChange: function ( value ) {
								setAttributes( { postsPerPage: value } );
							}
						} ),
						el( RangeControl, {
							label: __( 'Columns', 'wp-livescore-la' ),
							value: attributes.columns,
							min: 1,
							max: 4,
							onChange: function ( value ) {
								setAttributes( { columns: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Empty message', 'wp-livescore-la' ),
							value: attributes.emptyMessage,
							onChange: function ( value ) {
								setAttributes( { emptyMessage: value } );
							}
						} )
					),
					el(
						PanelBody,
						{
							title: __( 'Card Display', 'wp-livescore-la' ),
							initialOpen: true
						},
						el( ToggleControl, {
							label: __( 'Featured image', 'wp-livescore-la' ),
							checked: attributes.showFeaturedImage,
							onChange: function ( value ) {
								setAttributes( { showFeaturedImage: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Excerpt', 'wp-livescore-la' ),
							checked: attributes.showExcerpt,
							onChange: function ( value ) {
								setAttributes( { showExcerpt: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Date', 'wp-livescore-la' ),
							checked: attributes.showDate,
							onChange: function ( value ) {
								setAttributes( { showDate: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Author', 'wp-livescore-la' ),
							checked: attributes.showAuthor,
							onChange: function ( value ) {
								setAttributes( { showAuthor: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Read more button', 'wp-livescore-la' ),
							checked: attributes.showReadMore,
							onChange: function ( value ) {
								setAttributes( { showReadMore: value } );
							}
						} ),
						attributes.showReadMore &&
							el( TextControl, {
								label: __( 'Read more text', 'wp-livescore-la' ),
								value: attributes.readMoreText,
								onChange: function ( value ) {
									setAttributes( { readMoreText: value } );
								}
							} )
					)
				),
				el( ServerSideRender, {
					block: 'wp-livescore-la/related-league-blogs',
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
