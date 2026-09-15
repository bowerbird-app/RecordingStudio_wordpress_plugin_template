/**
 * @param {HTMLElement} root Block root from DOM.
 */
function mountRecordingStudioWidget( root ) {
	const raw = root.getAttribute( 'data-rs-payload' );
	if ( ! raw ) {
		return;
	}

	let payload;
	try {
		payload = JSON.parse( raw );
	} catch ( error ) {
		return;
	}

	const sdk = window.RecordingStudioPluginSdk;
	if ( ! sdk || typeof sdk.mount !== 'function' ) {
		return;
	}

	sdk.mount( root, payload );
}

function initRecordingStudioWidgets() {
	document
		.querySelectorAll( '[data-rs-widget]' )
		.forEach( ( root ) => {
			if ( root instanceof HTMLElement ) {
				mountRecordingStudioWidget( root );
			}
		} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initRecordingStudioWidgets );
} else {
	initRecordingStudioWidgets();
}
