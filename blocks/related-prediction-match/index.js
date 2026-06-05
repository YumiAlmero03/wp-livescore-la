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

	blocks.registerBlockType( 'wp-livescore/related-prediction-match', {
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
						{ title: __( 'Related Prediction / Match', 'wp-livescore-la' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Section title', 'wp-livescore-la' ),
							value: attributes.title || '',
							onChange: function ( value ) { setAttributes( { title: value } ); }
						} ),
						el( SelectControl, {
							label: __( 'Related content', 'wp-livescore-la' ),
							value: attributes.targetType || 'auto',
							options: [
								{ label: __( 'Auto', 'wp-livescore-la' ), value: 'auto' },
								{ label: __( 'Prediction', 'wp-livescore-la' ), value: 'prediction' },
								{ label: __( 'Match', 'wp-livescore-la' ), value: 'match' }
							],
							onChange: function ( value ) { setAttributes( { targetType: value } ); }
						} ),
						el( TextControl, {
							label: __( 'Manual Match API ID', 'wp-livescore-la' ),
							value: attributes.apiId || '',
							onChange: function ( value ) { setAttributes( { apiId: value } ); }
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Display', 'wp-livescore-la' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show image', 'wp-livescore-la' ),
							checked: !! attributes.showImage,
							onChange: function ( value ) { setAttributes( { showImage: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show excerpt', 'wp-livescore-la' ),
							checked: !! attributes.showExcerpt,
							onChange: function ( value ) { setAttributes( { showExcerpt: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show meta', 'wp-livescore-la' ),
							checked: !! attributes.showMeta,
							onChange: function ( value ) { setAttributes( { showMeta: value } ); }
						} ),
						el( TextControl, {
							label: __( 'Empty message', 'wp-livescore-la' ),
							value: attributes.emptyMessage || '',
							onChange: function ( value ) { setAttributes( { emptyMessage: value } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'wp-livescore/related-prediction-match', attributes: attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender );
