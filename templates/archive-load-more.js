( function () {
	function ready( callback ) {
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', callback );
			return;
		}

		callback();
	}

	ready( function () {
		document.querySelectorAll( '.wp-livescore-la-team-archive' ).forEach( function ( archive ) {
			var button = archive.querySelector( '.wp-livescore-la-team-archive__load-more' );
			var grid = archive.querySelector( '.wp-livescore-la-team-archive__grid' );

			if ( ! button || ! grid ) {
				return;
			}

			button.addEventListener( 'click', function () {
				var nextPage = parseInt( button.dataset.nextPage || '2', 10 );
				var maxPages = parseInt( button.dataset.maxPages || '1', 10 );

				if ( button.disabled || nextPage > maxPages ) {
					return;
				}

				button.disabled = true;
				button.classList.add( 'is-loading' );
				button.setAttribute( 'aria-busy', 'true' );

				var formData = new FormData();
				formData.append( 'action', 'wp_livescore_la_load_more_teams' );
				formData.append( 'nonce', button.dataset.nonce || '' );
				formData.append( 'page', String( nextPage ) );
				formData.append( 'query_vars', button.dataset.queryVars || '{}' );

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
							throw new Error( 'No teams returned.' );
						}

						grid.insertAdjacentHTML( 'beforeend', response.data.html );
						button.dataset.nextPage = String( nextPage + 1 );

						if ( ! response.data.hasMore ) {
							button.remove();
							return;
						}

						button.disabled = false;
						button.classList.remove( 'is-loading' );
						button.removeAttribute( 'aria-busy' );
					} )
					.catch( function () {
						button.disabled = false;
						button.classList.remove( 'is-loading' );
						button.removeAttribute( 'aria-busy' );
					} );
			} );
		} );
	} );
} )();
