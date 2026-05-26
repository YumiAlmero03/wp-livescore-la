( function () {
	'use strict';

	function toggleCustomDate( form ) {
		var dateFilter = form.querySelector( 'select[name="match_date_filter"]' );
		var customDate = form.querySelector( '[data-match-custom-date]' );

		if ( ! dateFilter || ! customDate ) {
			return;
		}

		customDate.hidden = 'custom' !== dateFilter.value;
	}

	document.addEventListener( 'change', function ( event ) {
		if ( ! event.target.matches || ! event.target.matches( 'select[name="match_date_filter"]' ) ) {
			return;
		}

		toggleCustomDate( event.target.form );
	} );

	document.querySelectorAll( '.wp-livescore-la-match-filters__form' ).forEach( toggleCustomDate );

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;

		if ( ! form.classList || ! form.classList.contains( 'wp-livescore-la-match-filters__form' ) ) {
			return;
		}

		var fields = form.querySelectorAll( 'select[name="match_sport"], select[name="match_country"], select[name="match_league"], select[name="match_date_filter"], input[name="match_date"]' );
		var hasValue = false;
		var dateFilter = form.querySelector( 'select[name="match_date_filter"]' );
		var customDate = form.querySelector( 'input[name="match_date"]' );

		fields.forEach( function ( field ) {
			if ( 'match_date_filter' === field.name && 'custom' === field.value && ( ! customDate || ! customDate.value ) ) {
				field.disabled = true;
				return;
			}

			if ( 'match_date' === field.name && ( ! dateFilter || 'custom' !== dateFilter.value ) ) {
				field.disabled = true;
				return;
			}

			if ( field.value ) {
				hasValue = true;
				return;
			}

			field.disabled = true;
		} );

		if ( ! hasValue ) {
			event.preventDefault();
			window.location.href = form.getAttribute( 'action' );
		}
	} );
} )();
