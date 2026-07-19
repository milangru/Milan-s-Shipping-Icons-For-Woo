document.addEventListener( 'DOMContentLoaded', function () {
	var link = document.querySelector( '.msiw-leave-review-link' );

	if ( ! link ) {
		return;
	}

	// The link already opens the WordPress.org review page normally via
	// target="_blank". We only need to record that the user was sent there,
	// which we do with a background fetch() instead of redirecting the
	// current tab -- redirecting the current tab at the same time can stop
	// the browser from opening the new tab at all in some cases.
	link.addEventListener( 'click', function () {
		var notice = link.closest( '.msiw-review-notice' );

		fetch( link.getAttribute( 'data-dismiss-url' ), { credentials: 'same-origin' } )
			.then( function () {
				// The server-side dismissal is now saved. Also remove the
				// notice from the current tab right away instead of waiting
				// for the admin page to be manually refreshed.
				dismissNotice( notice );
			} )
			.catch( function ( error ) {
				console.error( 'MSIW review notice: dismiss request failed.', error );
			} );
	} );

	function dismissNotice( notice ) {
		if ( ! notice ) {
			return;
		}

		notice.style.transition = 'opacity 0.2s ease';
		notice.style.opacity = '0';

		setTimeout( function () {
			notice.remove();
		}, 200 );
	}
} );
