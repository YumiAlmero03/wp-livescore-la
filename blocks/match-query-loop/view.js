( function () {
	function pad( value ) {
		return String( value ).padStart( 2, '0' );
	}

	function updateCountdown( countdown ) {
		var value = countdown.querySelector( '.wp-livescore-la-tracker-iframe__countdown-value' );
		var target = parseInt( countdown.dataset.matchCountdown, 10 );

		if ( ! value || ! target ) {
			return;
		}

		var remaining = Math.max( 0, target - Date.now() );

		if ( 0 === remaining ) {
			value.textContent = countdown.dataset.startedLabel || 'Match started';
			return;
		}

		var seconds = Math.floor( remaining / 1000 );
		var days = Math.floor( seconds / 86400 );
		var hours = Math.floor( ( seconds % 86400 ) / 3600 );
		var minutes = Math.floor( ( seconds % 3600 ) / 60 );
		var restSeconds = seconds % 60;

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

	function ready( callback ) {
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', callback );
			return;
		}

		callback();
	}

	ready( function () {
		initCountdowns( document );

		if ( 'MutationObserver' in window ) {
			new MutationObserver( function ( mutations ) {
				mutations.forEach( function ( mutation ) {
					mutation.addedNodes.forEach( function ( node ) {
						if ( node && 1 === node.nodeType ) {
							initCountdowns( node );
						}
					} );
				} );
			} ).observe( document.body, { childList: true, subtree: true } );
		}

		document.querySelectorAll( '.wp-livescore-la-match-query-loop-load-more' ).forEach( function ( button ) {
			var queryBlock = button.closest( '.wp-block-query' );
			var postTemplate = queryBlock ? queryBlock.querySelector( '.wp-block-post-template' ) : null;

			if ( ! postTemplate ) {
				return;
			}

			button.addEventListener( 'click', function () {
				var nextPage = parseInt( button.dataset.nextPage || '2', 10 );
				var originalText = button.dataset.originalText || button.textContent;

				button.dataset.originalText = originalText;

				if ( button.disabled ) {
					return;
				}

				button.disabled = true;
				button.classList.add( 'is-loading' );
				button.setAttribute( 'aria-busy', 'true' );
				button.textContent = button.dataset.loadingText || 'Loading...';

				var formData = new FormData();
				formData.append( 'action', 'wp_livescore_la_load_more_match_query_loop' );
				formData.append( 'nonce', button.dataset.nonce || '' );
				formData.append( 'page', String( nextPage ) );
				formData.append( 'query', button.dataset.query || '{}' );
				formData.append( 'template', button.dataset.template || '{}' );
				formData.append( 'display_layout', button.dataset.displayLayout || '{}' );
				formData.append( 'context', button.dataset.context || '{}' );

				fetch( button.dataset.ajaxUrl || '', {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				} )
					.then( function ( response ) {
						return response.json();
					} )
					.then( function ( response ) {
						if ( ! response || ! response.success || ! response.data || ! response.data.html ) {
							throw new Error( 'No matches returned.' );
						}

							postTemplate.insertAdjacentHTML( 'beforeend', response.data.html );
							if ( 'function' === typeof window.wpLivescoreLaInitCountdowns ) {
								window.wpLivescoreLaInitCountdowns( postTemplate );
							}
							initCountdowns( postTemplate );
							window.requestAnimationFrame( function () {
								initCountdowns( postTemplate );
							} );
							button.dataset.nextPage = String( nextPage + 1 );

						if ( ! response.data.hasMore ) {
							if ( button.parentNode ) {
								button.parentNode.remove();
							}
							return;
						}

						button.disabled = false;
						button.classList.remove( 'is-loading' );
						button.removeAttribute( 'aria-busy' );
						button.textContent = originalText;
					} )
					.catch( function () {
						button.disabled = false;
						button.classList.remove( 'is-loading' );
						button.removeAttribute( 'aria-busy' );
						button.textContent = button.dataset.errorText || originalText;
					} );
			} );
		} );
	} );
} )();
