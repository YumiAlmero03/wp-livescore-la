( function () {
	function ready( callback ) {
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', callback );
			return;
		}

		callback();
	}

	ready( function () {
		document.querySelectorAll( '.wp-livescore-la-related-league-blogs' ).forEach( function ( block ) {
			var button = block.querySelector( '.wp-livescore-la-related-league-blogs__load-more' );
			var grid = block.querySelector( '.wp-livescore-la-related-league-blogs__grid' );

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
				formData.append( 'action', 'wp_livescore_la_load_related_blogs' );
				formData.append( 'nonce', button.dataset.nonce || '' );
				formData.append( 'post_id', button.dataset.postId || '' );
				formData.append( 'page', String( nextPage ) );
				formData.append( 'attributes', button.dataset.attributes || '{}' );

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
							throw new Error( 'No posts returned.' );
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
