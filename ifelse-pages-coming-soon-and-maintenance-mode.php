<?php
/**
 * Plugin Name:       IfElse Pages – Coming Soon and Maintenance Mode
 * Plugin URI:        https://zulkaif.com/ifelse.html
 * Description:       A lightweight plugin to display Coming Soon, Maintenance, or Landing Page screens to visitors.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Zulkaif Riaz
 * Author URI:        https://zulkaif.com/
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       ifelse-pages-coming-soon-and-maintenance-mode
 * Domain Path:       /languages
 *
 * @package IfElsePages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IFELSEPAGES_VERSION',    '1.0.0' );
define( 'IFELSEPAGES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IFELSEPAGES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IFELSEPAGES_OPTION_KEY', 'ifelse_pages_settings' );
define( 'IFELSEPAGES_PAGE_SLUG',  'ifelse-pages-coming-soon-and-maintenance-mode' );

require_once IFELSEPAGES_PLUGIN_DIR . 'admin/class-ifelsepages-settings.php';
require_once IFELSEPAGES_PLUGIN_DIR . 'public/class-ifelsepages-frontend.php';

/**
 * Initialise all plugin components.
 */
function ifelsepages_init() {
	IfElsePages_Settings::get_instance();
	IfElsePages_Frontend::get_instance();
}
add_action( 'plugins_loaded', 'ifelsepages_init' );

register_activation_hook( __FILE__, 'ifelsepages_activate' );
function ifelsepages_activate() {
	if ( false === get_option( IFELSEPAGES_OPTION_KEY ) ) {
		update_option( IFELSEPAGES_OPTION_KEY, ifelsepages_defaults() );
	}
}

register_deactivation_hook( __FILE__, 'ifelsepages_deactivate' );
function ifelsepages_deactivate() {
	wp_cache_delete( 'ifelse_pages_settings', 'ifelse-pages' );
}

/**
 * Default settings.
 *
 * @return array
 */
function ifelsepages_defaults() {
	return array(
		'enabled'              => 0,
		'mode'                 => 'coming_soon',
		'template_per_mode'    => array(
			'coming_soon' => 'dark',
			'maintenance' => 'warning',
			'landing'     => 'contact',
		),
		'title'                => 'Coming Soon',
		'description'          => 'We are working on something awesome. Stay tuned!',
		'footer_text'          => '',
		'logo_url'             => '',
		'bg_color'             => '#0d0d0d',
		'bg_image_url'         => '',
		'template_settings'    => array(
			'dark' => array(
				'countdown_enable'     => 0,
				'countdown_date'       => '',
				'countdown_end_action' => 'hide',   // 'hide' | 'disable'
			),
			'mystry' => array(
				'button_text'    => 'Contact Us',
				'button_url'     => '',
				'button_new_tab' => 0,
			),
			'contact' => array(
				'form_source'    => 'builtin',
				'form_shortcode' => '',
				'notify_email'   => '',
			),
			'warning' => array(
				'show_retry_time'      => 0,
				'retry_hours'          => 1,
				'retry_end_action'     => 'keep',   // 'keep' | 'disable'
				'retry_end_timestamp'  => 0,        // unix timestamp set when saving with action=disable
			),
		),
		'bypass_roles'         => array( 'administrator', 'editor' ),
		'meta_title'           => '',
		'meta_description'     => '',
		'show_admin_bar_badge' => 1,
	);
}

/**
 * Return saved settings merged with defaults.
 *
 * @return array
 */
function ifelsepages_get_settings() {
	$cached = wp_cache_get( 'ifelse_pages_settings', 'ifelse-pages' );
	if ( false !== $cached ) {
		return $cached;
	}

	$saved    = get_option( IFELSEPAGES_OPTION_KEY, array() );
	$defaults = ifelsepages_defaults();
	$settings = wp_parse_args( $saved, $defaults );

	$settings['template_per_mode'] = wp_parse_args(
		(array) $settings['template_per_mode'],
		$defaults['template_per_mode']
	);

	$settings['template_settings'] = wp_parse_args(
		(array) $settings['template_settings'],
		$defaults['template_settings']
	);

	foreach ( $defaults['template_settings'] as $tpl_slug => $tpl_defaults ) {
		if ( ! isset( $settings['template_settings'][ $tpl_slug ] ) || ! is_array( $settings['template_settings'][ $tpl_slug ] ) ) {
			$settings['template_settings'][ $tpl_slug ] = $tpl_defaults;
		} else {
			$settings['template_settings'][ $tpl_slug ] = wp_parse_args(
				$settings['template_settings'][ $tpl_slug ],
				$tpl_defaults
			);
		}
	}

	wp_cache_set( 'ifelse_pages_settings', $settings, 'ifelse-pages' );

	return $settings;
}

/**
 * Invalidate settings cache.
 */
function ifelsepages_flush_settings_cache() {
	wp_cache_delete( 'ifelse_pages_settings', 'ifelse-pages' );
}

/**
 * Convert a datetime-local string (e.g. "2026-03-09T22:00") to a UTC Unix
 * timestamp, interpreting the value in the WordPress-configured timezone.
 *
 * datetime-local inputs have no timezone indicator, so plain strtotime() or
 * new Date() will silently use the server's or browser's local timezone —
 * which is often UTC and wrong for the admin. This function always uses the
 * timezone the site owner has configured in Settings → General.
 *
 * @param string $datetime_local Value from a datetime-local input field.
 * @return int|false Unix timestamp (UTC), or false on failure.
 */
function ifelsepages_local_to_timestamp( $datetime_local ) {
	if ( empty( $datetime_local ) ) {
		return false;
	}

	$tz_string = get_option( 'timezone_string' );

	if ( $tz_string ) {
		try {
			$dt = new DateTime( $datetime_local, new DateTimeZone( $tz_string ) );
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			return false;
		}
	}

	// Fall back to numeric UTC offset (e.g. gmt_offset = 5.5 for UTC+5:30).
	$gmt_offset = (float) get_option( 'gmt_offset', 0 );
	$timestamp  = strtotime( $datetime_local );
	if ( false === $timestamp ) {
		return false;
	}

	return $timestamp - (int) ( $gmt_offset * 3600 );
}

/**
 * Auto-disable the plugin when a scheduled expiry is reached.
 * Runs early on every request (priority 5, before init).
 *
 * A transient is used as a short-circuit so that if the DB write fails,
 * subsequent requests do not hammer update_option() on every page load.
 * The transient expires after 60 seconds, giving the write another chance.
 */
function ifelsepages_maybe_auto_disable() {
	$settings = ifelsepages_get_settings();

	if ( empty( $settings['enabled'] ) ) {
		return;
	}

	// Guard: if we already attempted a disable very recently, skip.
	if ( get_transient( 'ifelsepages_disable_attempted' ) ) {
		return;
	}

	$mode         = isset( $settings['mode'] ) ? $settings['mode'] : 'coming_soon';
	$tpl_settings = isset( $settings['template_settings'] ) ? $settings['template_settings'] : array();
	$should_disable = false;

	// Coming Soon / Dark: countdown date reached and end-action = disable.
	if ( 'coming_soon' === $mode ) {
		$dark = isset( $tpl_settings['dark'] ) ? $tpl_settings['dark'] : array();
		if (
			! empty( $dark['countdown_enable'] ) &&
			! empty( $dark['countdown_date'] ) &&
			isset( $dark['countdown_end_action'] ) && 'disable' === $dark['countdown_end_action'] &&
			ifelsepages_local_to_timestamp( $dark['countdown_date'] ) <= time()
		) {
			$should_disable = true;
		}
	}

	// Maintenance / Warning: retry end timestamp reached and end-action = disable.
	if ( 'maintenance' === $mode ) {
		$warning = isset( $tpl_settings['warning'] ) ? $tpl_settings['warning'] : array();
		if (
			! empty( $warning['show_retry_time'] ) &&
			isset( $warning['retry_end_action'] ) && 'disable' === $warning['retry_end_action'] &&
			! empty( $warning['retry_end_timestamp'] ) &&
			(int) $warning['retry_end_timestamp'] <= time()
		) {
			$should_disable = true;
		}
	}

	if ( $should_disable ) {
		// Set transient first to prevent repeat attempts within 60 s.
		set_transient( 'ifelsepages_disable_attempted', 1, 60 );
		$settings['enabled'] = 0;
		update_option( IFELSEPAGES_OPTION_KEY, $settings );
		ifelsepages_flush_settings_cache();
	}
}
add_action( 'plugins_loaded', 'ifelsepages_maybe_auto_disable', 5 );

/**
 * AJAX handler – disable the plugin when the countdown or maintenance timer expires.
 * Accessible to non-logged-in visitors (nopriv) because Coming Soon pages are shown
 * to them. The nonce proves the request originates from our page, and the server
 * independently verifies that the scheduled time has actually elapsed before acting.
 */
function ifelsepages_ajax_auto_disable() {
	check_ajax_referer( 'ifelsepages_auto_disable', 'nonce' );

	$settings = ifelsepages_get_settings();

	if ( empty( $settings['enabled'] ) ) {
		wp_send_json_success( array( 'already_disabled' => true ) );
	}

	// Server-side guard: only honour this request if the expiry has actually passed.
	$mode           = isset( $settings['mode'] ) ? $settings['mode'] : 'coming_soon';
	$tpl_settings   = isset( $settings['template_settings'] ) ? $settings['template_settings'] : array();
	$expiry_reached = false;

	if ( 'coming_soon' === $mode ) {
		$dark = isset( $tpl_settings['dark'] ) ? $tpl_settings['dark'] : array();
		if (
			! empty( $dark['countdown_enable'] ) &&
			! empty( $dark['countdown_date'] ) &&
			isset( $dark['countdown_end_action'] ) && 'disable' === $dark['countdown_end_action'] &&
			ifelsepages_local_to_timestamp( $dark['countdown_date'] ) <= time()
		) {
			$expiry_reached = true;
		}
	}

	if ( 'maintenance' === $mode ) {
		$warning = isset( $tpl_settings['warning'] ) ? $tpl_settings['warning'] : array();
		if (
			! empty( $warning['show_retry_time'] ) &&
			isset( $warning['retry_end_action'] ) && 'disable' === $warning['retry_end_action'] &&
			! empty( $warning['retry_end_timestamp'] ) &&
			(int) $warning['retry_end_timestamp'] <= time()
		) {
			$expiry_reached = true;
		}
	}

	if ( ! $expiry_reached ) {
		wp_send_json_error( array( 'reason' => 'not_expired' ), 403 );
	}

	$settings['enabled'] = 0;
	update_option( IFELSEPAGES_OPTION_KEY, $settings );
	ifelsepages_flush_settings_cache();

	wp_send_json_success( array( 'disabled' => true ) );
}
add_action( 'wp_ajax_nopriv_ifelsepages_auto_disable', 'ifelsepages_ajax_auto_disable' );
add_action( 'wp_ajax_ifelsepages_auto_disable',        'ifelsepages_ajax_auto_disable' );

/**
 * Template registry.
 *
 * Third-party plugins and themes may extend this list by filtering
 * 'ifelsepages_template_registry'. Each entry must contain at minimum:
 * 'slug', 'name', 'description', and 'available' (bool) keys.
 *
 * @return array
 */
function ifelsepages_template_registry() {
	$registry = array(

		'coming_soon' => array(
			array(
				'slug'          => 'dark',
				'name'          => __( 'Dark', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'description'   => __( 'Dark background, large countdown, centered logo.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'available'     => true,
				'has_countdown' => true,
				'has_form'      => false,
			),
			array(
				'slug'          => 'mystry',
				'name'          => __( 'Mystry', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'description'   => __( 'Colorful, playful layout with optional email capture.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'available'     => true,
				'has_countdown' => false,
				'has_form'      => true,
			),
			array(
				'slug'        => 'neon_glow',
				'name'        => __( 'Neon Glow', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'description' => __( 'Coming soon in a future update.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'available'   => false,
			),
			array(
				'slug'        => 'minimal_white',
				'name'        => __( 'Minimal White', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'description' => __( 'Coming soon in a future update.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'available'   => false,
			),
		),

		'maintenance' => array(
			array(
				'slug'          => 'warning',
				'name'          => __( 'Warning', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'description'   => __( 'Neutral technical style, 503 header, no countdown.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'available'     => true,
				'has_countdown' => false,
				'has_form'      => false,
			),
			array(
				'slug'        => 'tech_dark',
				'name'        => __( 'Tech Dark', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'description' => __( 'Coming soon in a future update.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'available'   => false,
			),
		),

		'landing' => array(
			array(
				'slug'          => 'contact',
				'name'          => __( 'Contact', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'description'   => __( 'Branded mini-page with optional contact form hook.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'available'     => true,
				'has_countdown' => false,
				'has_form'      => true,
			),
			array(
				'slug'        => 'portfolio',
				'name'        => __( 'Portfolio', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'description' => __( 'Coming soon in a future update.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
				'available'   => false,
			),
		),

	);

	/**
	 * Filters the template registry.
	 *
	 * Allows third-party plugins or themes to register additional templates.
	 * Each entry must include: 'slug' (string), 'name' (string),
	 * 'description' (string), 'available' (bool).
	 *
	 * @param array $registry Associative array keyed by mode slug.
	 */
	return apply_filters( 'ifelsepages_template_registry', $registry );
}

/**
 * Get the active template slug for a given mode.
 *
 * @param string $mode
 * @param array  $settings
 * @return string
 */
function ifelsepages_active_template( $mode, $settings ) {
	$defaults = ifelsepages_defaults();
	$slug = isset( $settings['template_per_mode'][ $mode ] )
		? $settings['template_per_mode'][ $mode ]
		: $defaults['template_per_mode'][ $mode ];
	return sanitize_key( $slug );
}

/**
 * Return the full path to a template file; falls back to the mode default.
 *
 * @param string $mode
 * @param string $slug
 * @return string
 */
function ifelsepages_template_path( $mode, $slug ) {
	$mode = sanitize_key( $mode );
	$slug = sanitize_key( $slug );

	$dir  = str_replace( '_', '-', $mode );
	$file = IFELSEPAGES_PLUGIN_DIR . 'templates/' . $dir . '/' . $slug . '.php';

	if ( file_exists( $file ) ) {
		return $file;
	}

	$registry = ifelsepages_template_registry();
	if ( isset( $registry[ $mode ] ) ) {
		foreach ( $registry[ $mode ] as $tpl ) {
			if ( ! empty( $tpl['available'] ) ) {
				$fallback = IFELSEPAGES_PLUGIN_DIR . 'templates/' . $dir . '/' . $tpl['slug'] . '.php';
				if ( file_exists( $fallback ) ) {
					return $fallback;
				}
			}
		}
	}

	return '';
}

/**
 * Add Settings link on Plugins page.
 *
 * @param array $links
 * @return array
 */
function ifelsepages_plugin_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . IFELSEPAGES_PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'ifelse-pages-coming-soon-and-maintenance-mode' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	'ifelsepages_plugin_action_links'
);

/**
 * Show admin bar badge when plugin is active.
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function ifelsepages_admin_bar_notice( $wp_admin_bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = ifelsepages_get_settings();

	if ( empty( $settings['enabled'] ) || empty( $settings['show_admin_bar_badge'] ) ) {
		return;
	}

	$mode_labels = array(
		'coming_soon' => __( 'Coming Soon', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
		'maintenance' => __( 'Maintenance', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
		'landing'     => __( 'Landing Page', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
	);
	$mode  = isset( $settings['mode'] ) ? $settings['mode'] : 'coming_soon';
	$label = isset( $mode_labels[ $mode ] ) ? $mode_labels[ $mode ] : __( 'Active', 'ifelse-pages-coming-soon-and-maintenance-mode' );

	$wp_admin_bar->add_node(
		array(
			'id'    => 'ifelsepages-status',
			'title' => '<span style="color:#f0b429;font-weight:700;">&#9679; IfElse: ' . esc_html( $label ) . ' ON</span>',
			'href'  => esc_url( admin_url( 'options-general.php?page=' . IFELSEPAGES_PAGE_SLUG ) ),
			'meta'  => array(
				'title' => __( 'IfElse Pages is active — visitors see this page. Click to manage.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
			),
		)
	);
}
add_action( 'admin_bar_menu', 'ifelsepages_admin_bar_notice', 100 );
