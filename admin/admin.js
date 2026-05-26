( function ( $, wp ) {
	'use strict';

	$( function () {
		var frame;

		$( document ).on( 'click', '.wp-livescore-la-upload-header-image', function ( event ) {
			event.preventDefault();

			var $wrap = $( this ).closest( '.wp-livescore-la-header-image' );

			frame = wp.media( {
				title: 'Select League Header Image',
				button: {
					text: 'Use Header Image'
				},
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var previewUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

				$wrap.find( '#wp_livescore_la_header_image_id' ).val( attachment.id );
				$wrap.find( '.wp-livescore-la-header-image__preview' ).empty().append(
					$( '<img />', {
						src: previewUrl,
						alt: ''
					} )
				);
				$wrap.find( '.wp-livescore-la-header-image__id' ).text( 'Attachment ID: ' + attachment.id );
				$wrap.find( '.wp-livescore-la-header-image__link' ).remove();
				$wrap.find( '.wp-livescore-la-header-image__details' ).append(
					$( '<a />', {
						class: 'wp-livescore-la-header-image__link',
						href: attachment.url,
						target: '_blank',
						rel: 'noopener noreferrer',
						text: 'View image'
					} )
				);
				$wrap.find( '.wp-livescore-la-remove-header-image' ).prop( 'disabled', false );
			} );

			frame.open();
		} );

		$( document ).on( 'click', '.wp-livescore-la-remove-header-image', function ( event ) {
			event.preventDefault();

			var $wrap = $( this ).closest( '.wp-livescore-la-header-image' );
			$wrap.find( '#wp_livescore_la_header_image_id' ).val( '' );
			$wrap.find( '.wp-livescore-la-header-image__preview' ).empty();
			$wrap.find( '.wp-livescore-la-header-image__id' ).text( 'Attachment ID: 0' );
			$wrap.find( '.wp-livescore-la-header-image__link' ).remove();
			$( this ).prop( 'disabled', true );
		} );

		$( document ).on( 'click', '.wp-livescore-la-select-sport-icon', function ( event ) {
			event.preventDefault();

			var $button = $( this );

			frame = wp.media( {
				title: 'Select Sport Image',
				button: {
					text: 'Use Sport Image'
				},
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var previewUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

				$button.siblings( '.wp-livescore-la-sport-icon-url' ).val( attachment.url );
				$button.parent().find( '.wp-livescore-la-sport-icon-preview' ).attr( 'src', previewUrl ).show();
			} );

			frame.open();
		} );

		$( document ).on( 'click', '.wp-livescore-la-select-country-flag', function ( event ) {
			event.preventDefault();

			var $button = $( this );

			frame = wp.media( {
				title: 'Select Country Flag',
				button: {
					text: 'Use Country Flag'
				},
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var previewUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

				$button.siblings( '.wp-livescore-la-country-flag-url' ).val( attachment.url );
				$button.parent().find( '.wp-livescore-la-country-flag-preview' ).attr( 'src', previewUrl ).show();
			} );

			frame.open();
		} );

	} );
} )( window.jQuery, window.wp );
