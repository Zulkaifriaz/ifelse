/* IfElse Pages – Admin JS
 * Handles: mode tabs, template card selection, template-specific options visibility,
 *          content/design/settings tabs, colour picker, media uploader,
 *          countdown date validation, auto-disable radio interactions.
 * @package IfElsePages
 */
( function ( $, data ) {
	'use strict';

	// ── Mode tab switching ─────────────────────────────────────────────────────
	$( '.ifelsepages-mode-tab' ).on( 'click', function () {
		var mode = $( this ).data( 'mode' );

		$( '.ifelsepages-mode-tab' )
			.removeClass( 'active' )
			.attr( 'aria-selected', 'false' );
		$( this )
			.addClass( 'active' )
			.attr( 'aria-selected', 'true' );

		$( '#ifelsepages-mode-input' ).val( mode );

		$( '.ifelsepages-tpl-grid' ).hide();
		$( '#ifelsepages-tpl-grid-' + mode ).show();

		updateTemplateOptions();
	} );

	// ── Template card selection ────────────────────────────────────────────────
	$( document ).on( 'click', '.ifelsepages-tpl-card:not(.is-locked)', function () {
		var mode = $( this ).data( 'mode' );
		var slug = $( this ).data( 'slug' );

		$( '#ifelsepages-tpl-grid-' + mode + ' .ifelsepages-tpl-card' )
			.removeClass( 'is-active' )
			.find( '.ifelsepages-active-badge' )
			.remove();

		$( this ).addClass( 'is-active' );
		$( this ).prepend(
			$( '<span>' )
				.addClass( 'ifelsepages-active-badge' )
				.text( '\u2713' )
		);

		$( '#ifelsepages-tpl-input-' + mode ).val( slug );

		updateTemplateOptions();
	} );

	// ── Update template-specific options visibility ────────────────────────────
	function updateTemplateOptions() {
		var currentMode = $( '#ifelsepages-mode-input' ).val();
		var currentSlug = $( '#ifelsepages-tpl-input-' + currentMode ).val();

		$( '.ifelsepages-tpl-option-group' ).hide();
		$( '.ifelsepages-tpl-option-group[data-template="' + currentSlug + '"]' ).show();
	}

	// ── Dark template: Countdown enable toggle ─────────────────────────────────
	$( '#ifelsepages-dark-countdown' ).on( 'change', function () {
		if ( $( this ).is( ':checked' ) ) {
			$( '.ifelsepages-dark-countdown-options' ).slideDown( 200 );
		} else {
			$( '.ifelsepages-dark-countdown-options' ).slideUp( 200 );
		}
	} );

	// ── Warning template: Show retry time toggle ───────────────────────────────
	$( '#ifelsepages-warning-retry' ).on( 'change', function () {
		if ( $( this ).is( ':checked' ) ) {
			$( '.ifelsepages-warning-retry-options' ).slideDown( 200 );
		} else {
			$( '.ifelsepages-warning-retry-options' ).slideUp( 200 );
		}
	} );

	// ── Contact template: Form source shortcode toggle ─────────────────────────
	$( 'input[name="template_settings[contact][form_source]"]' ).on( 'change', function () {
		var val = $( this ).val();
		if ( val === 'shortcode' ) {
			$( '.ifelsepages-shortcode-field' ).slideDown( 200 );
		} else {
			$( '.ifelsepages-shortcode-field' ).slideUp( 200 );
		}
	} );

	// ── Form validation: countdown date must be in the future ────────────────
	// Only validates when ALL four conditions are true:
	//   1. Plugin is being enabled
	//   2. Active mode is "coming_soon"
	//   3. The "dark" template is selected for that mode
	//   4. The countdown toggle is on
	$( '#ifelsepages-form' ).on( 'submit', function ( e ) {
		var pluginEnabled    = $( '#ifelsepages-enabled' ).is( ':checked' );
		var currentMode      = $( '#ifelsepages-mode-input' ).val();
		var activeTpl        = $( '#ifelsepages-tpl-input-coming_soon' ).val();
		var countdownEnabled = $( '#ifelsepages-dark-countdown' ).is( ':checked' );

		// Skip validation if any of the four conditions are not met.
		if ( ! pluginEnabled || 'coming_soon' !== currentMode || 'dark' !== activeTpl || ! countdownEnabled ) {
			return true;
		}

		var $dateField = $( '#ifelsepages-dark-countdown-date' );
		var dateVal    = $dateField.val();
		var errorMsg   = '';

		if ( ! dateVal ) {
			errorMsg = data.i18n.dateRequired;
		} else if ( new Date( dateVal ).getTime() <= Date.now() ) {
			errorMsg = data.i18n.datePast;
		}

		if ( errorMsg ) {
			e.preventDefault();

			// Show an inline notice at the top of the form, consistent with WP admin UI.
			var $existing = $( '#ifelsepages-js-validation-error' );
			if ( $existing.length ) {
				$existing.find( 'p' ).text( errorMsg );
			} else {
				$( '.ifelsepages-wrap form' ).before(
					$( '<div>' )
						.attr( { id: 'ifelsepages-js-validation-error', role: 'alert' } )
						.addClass( 'notice notice-error is-dismissible' )
						.append( $( '<p>' ).text( errorMsg ) )
				);
			}

			$dateField.focus();
			return false;
		}

		return true;
	} );

	// ── Content / Design / Settings tab switching ──────────────────────────────
	$( '.ifelsepages-tab' ).on( 'click', function () {
		var target = $( this ).data( 'tab' );

		$( '.ifelsepages-tab' )
			.removeClass( 'active' )
			.attr( 'aria-selected', 'false' );
		$( this )
			.addClass( 'active' )
			.attr( 'aria-selected', 'true' );

		$( '.ifelsepages-tab-panel' ).removeClass( 'active' );
		$( '#ifelsepages-tab-' + target ).addClass( 'active' );
	} );

	// ── Colour picker ──────────────────────────────────────────────────────────
	$( '.ifelsepages-color-picker' ).wpColorPicker();

	// ── Master toggle status text ──────────────────────────────────────────────
	$( 'input[name="enabled"]' ).on( 'change', function () {
		var $status = $( '.ifelsepages-status-text' );
		$status.text(
			$( this ).is( ':checked' ) ? data.statusActive : data.statusInactive
		);
	} );

	// ── WordPress media uploader ───────────────────────────────────────────────
	$( document ).on( 'click', '.ifelsepages-upload-btn', function ( e ) {
		e.preventDefault();

		var targetId = $( this ).data( 'target' );
		var $btn     = $( this );

		var frame = wp.media( {
			title:    data.mediaTitle,
			button:   { text: data.mediaButton },
			multiple: false,
			library:  { type: 'image' }
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var url        = attachment.url;

			$( '#' + targetId ).val( url );

			var $existing = $btn.siblings( 'img.ifelsepages-preview-img' );
			if ( $existing.length ) {
				$existing.attr( 'src', url );
			} else {
				$btn.before(
					$( '<img>' )
						.addClass( 'ifelsepages-preview-img' )
						.attr( { src: url, alt: '' } )
				);
			}

			if ( ! $btn.siblings( '.ifelsepages-remove-btn' ).length ) {
				$btn.after(
					$( '<button>' )
						.attr( { type: 'button', 'data-target': targetId } )
						.addClass( 'button ifelsepages-remove-btn' )
						.text( data.removeLabel )
				);
			}
		} );

		frame.open();
	} );

	// ── Remove image ──────────────────────────────────────────────────────────
	$( document ).on( 'click', '.ifelsepages-remove-btn', function ( e ) {
		e.preventDefault();
		var targetId = $( this ).data( 'target' );
		$( '#' + targetId ).val( '' );
		$( this ).siblings( 'img.ifelsepages-preview-img' ).remove();
		$( this ).remove();
	} );

	// ── On page load: show correct template options ────────────────────────────
	$( document ).ready( function () {
		updateTemplateOptions();
	} );

	// ── Live site clock ────────────────────────────────────────────────────────
	// Seeded from the WordPress site time (current_time via PHP), then ticked
	// forward every second in JS. This means the clock always reflects the
	// WordPress-configured timezone, completely independent of the browser clock.
	( function () {
		var $clock = $( '#ifelsepages-site-clock-time' );
		if ( ! $clock.length || ! data.siteTimeMs ) {
			return;
		}

		// Start from the server-provided timestamp and increment it ourselves.
		// We never touch Date.now() so the browser timezone has zero influence.
		var currentMs = data.siteTimeMs;

		var months = [
			'January', 'February', 'March', 'April', 'May', 'June',
			'July', 'August', 'September', 'October', 'November', 'December'
		];

		function pad( n ) {
			return n < 10 ? '0' + n : String( n );
		}

		function tickClock() {
			// Use UTC methods on a Date seeded with the WP Unix timestamp.
			// Because we set the ms value directly (not via local parsing),
			// getUTC* gives us back exactly the site's wall-clock time.
			var d     = new Date( currentMs );
			var year  = d.getUTCFullYear();
			var month = months[ d.getUTCMonth() ];
			var day   = d.getUTCDate();
			var hrs   = d.getUTCHours();
			var mins  = pad( d.getUTCMinutes() );
			var secs  = pad( d.getUTCSeconds() );
			var ampm  = hrs >= 12 ? 'PM' : 'AM';
			var hrs12 = hrs % 12 || 12;

			$clock.text(
				day + ' ' + month + ' ' + year +
				' \u2013 ' + hrs12 + ':' + mins + ':' + secs + ' ' + ampm
			);

			currentMs += 1000;
		}

		tickClock();
		setInterval( tickClock, 1000 );
	}() );

}( jQuery, window.ifelsepagesAdmin || {} ) );
