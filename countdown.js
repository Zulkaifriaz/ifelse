/* IfElse Pages – Countdown Timer (no external dependencies)
 * @package IfElsePages
 */
( function ( cfg ) {
	'use strict';

	if ( ! cfg || ! cfg.targetMs ) {
		return;
	}

	// targetMs is a Unix timestamp in milliseconds, set by the server using the
	// site's configured timezone. This avoids ambiguity from new Date('string'),
	// which would be parsed relative to the visitor's local browser timezone.
	var endTime   = cfg.targetMs;
	var endAction = cfg.endAction || 'hide';
	var wrap      = document.getElementById( 'ifelsepages-countdown' );

	if ( ! wrap ) {
		return;
	}

	function pad( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	/**
	 * Call the AJAX endpoint to disable the plugin, then reload so the
	 * site becomes publicly visible. Falls back to a simple reload if the
	 * AJAX data is missing for any reason.
	 */
	function triggerDisable() {
		if ( cfg.ajaxUrl && cfg.nonce ) {
			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', cfg.ajaxUrl, true );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			xhr.onload = function () {
				// Reload regardless of response — PHP auto-disable will also catch it.
				window.location.reload();
			};
			xhr.onerror = function () {
				window.location.reload();
			};
			xhr.send( 'action=ifelsepages_auto_disable&nonce=' + encodeURIComponent( cfg.nonce ) );
		} else {
			window.location.reload();
		}
	}

	function tick() {
		var diff = endTime - Date.now();

		if ( diff <= 0 ) {
			if ( 'disable' === endAction ) {
				// Zero out the display first so it doesn't freeze mid-count.
				var els = {
					days:    document.getElementById( 'iep-days' ),
					hours:   document.getElementById( 'iep-hours' ),
					minutes: document.getElementById( 'iep-minutes' ),
					seconds: document.getElementById( 'iep-seconds' )
				};
				if ( els.days )    { els.days.textContent    = '00'; }
				if ( els.hours )   { els.hours.textContent   = '00'; }
				if ( els.minutes ) { els.minutes.textContent = '00'; }
				if ( els.seconds ) { els.seconds.textContent = '00'; }

				triggerDisable();
			} else {
				// Default: just hide the countdown block.
				wrap.style.display = 'none';
			}
			return;
		}

		var days    = Math.floor( diff / 86400000 );
		var hours   = Math.floor( ( diff % 86400000 ) / 3600000 );
		var minutes = Math.floor( ( diff % 3600000 ) / 60000 );
		var seconds = Math.floor( ( diff % 60000 ) / 1000 );

		var elDays    = document.getElementById( 'iep-days' );
		var elHours   = document.getElementById( 'iep-hours' );
		var elMinutes = document.getElementById( 'iep-minutes' );
		var elSeconds = document.getElementById( 'iep-seconds' );

		if ( elDays )    { elDays.textContent    = pad( days ); }
		if ( elHours )   { elHours.textContent   = pad( hours ); }
		if ( elMinutes ) { elMinutes.textContent = pad( minutes ); }
		if ( elSeconds ) { elSeconds.textContent = pad( seconds ); }

		setTimeout( tick, 1000 );
	}

	// If time already passed when the page loads (e.g. after a quick reload),
	// act immediately rather than waiting another tick.
	tick();

}( window.ifelsepagesCountdown ) );
