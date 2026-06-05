( function () {
	function pad( value ) {
		return String( value ).padStart( 2, '0' );
	}

	function updateCountdown( countdown ) {
		const value = countdown.querySelector( '.wp-livescore-la-tracker-iframe__countdown-value' );
		const target = parseInt( countdown.dataset.matchCountdown, 10 );

		if ( ! value || ! target ) {
			return;
		}

		const remaining = Math.max( 0, target - Date.now() );

		if ( 0 === remaining ) {
			value.textContent = countdown.dataset.startedLabel || 'Match started';
			return;
		}

		const seconds = Math.floor( remaining / 1000 );
		const days = Math.floor( seconds / 86400 );
		const hours = Math.floor( ( seconds % 86400 ) / 3600 );
		const minutes = Math.floor( ( seconds % 3600 ) / 60 );
		const restSeconds = seconds % 60;

		value.textContent = ( days > 0 ? days + 'd ' : '' ) + pad( hours ) + ':' + pad( minutes ) + ':' + pad( restSeconds );
	}

	function startCountdown( countdown ) {
		if ( '1' === countdown.dataset.countdownInitialized ) {
			return;
		}

		countdown.dataset.countdownInitialized = '1';
		updateCountdown( countdown );
		window.setInterval( function () {
			updateCountdown( countdown );
		}, 1000 );
	}

	function initCountdowns( root ) {
		( root || document ).querySelectorAll( '[data-match-countdown]' ).forEach( startCountdown );
	}

	window.wpLivescoreLaInitCountdowns = initCountdowns;
	initCountdowns( document );
} )();
