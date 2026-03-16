/* IfElse Pages – Maintenance Auto-Disable
 * Fires an AJAX request after the configured delay to disable the plugin,
 * then reloads the page so the live site becomes publicly visible.
 * Data is supplied via the ifelsepagesAutoDisable global (JSON-encoded by PHP).
 * @package IfElsePages
 */
( function ( cfg ) {
	'use strict';

	if ( ! cfg || ! cfg.ajaxUrl || ! cfg.nonce ) {
		return;
	}

	function doDisable() {
		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', cfg.ajaxUrl, true );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		xhr.onload = function () {
			// Reload regardless of response body — the PHP server-side guard
			// will also catch and enforce the disable on next request.
			window.location.reload();
		};
		xhr.onerror = function () {
			window.location.reload();
		};
		xhr.send(
			'action=ifelsepages_auto_disable&nonce=' + encodeURIComponent( cfg.nonce )
		);
	}

	if ( cfg.msUntil > 0 ) {
		setTimeout( doDisable, cfg.msUntil );
	} else {
		// Delay already elapsed (e.g. page cached), fire immediately.
		doDisable();
	}

}( window.ifelsepagesAutoDisable ) );
