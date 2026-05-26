( function () {
	'use strict';

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;

		if ( ! form.classList || ! form.classList.contains( 'wp-livescore-la-league-filters__form' ) ) {
			return;
		}

		var fields = form.querySelectorAll( 'select[name="league_sport"], select[name="league_country"]' );
		var hasValue = false;

		fields.forEach( function ( field ) {
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
