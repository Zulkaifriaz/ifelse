<?php
/**
 * Front-end logic: intercept visitors and serve the correct mode + template.
 *
 * @package IfElsePages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IfElsePages_Frontend {

	/** @var self|null */
	private static $instance = null;

	/** @var array Plugin settings for the current request. */
	private $settings = array();

	/** @var bool Whether settings have been loaded for this request. */
	private $settings_loaded = false;

	/** @var bool True when we have decided to intercept this request. */
	private $will_intercept = false;

	private function __construct() {
		add_action( 'wp',                 array( $this, 'check_intercept' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'template_redirect',  array( $this, 'do_intercept' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// ── Settings loader ───────────────────────────────────────────────────────

	private function load_settings() {
		if ( ! $this->settings_loaded ) {
			$this->settings        = ifelsepages_get_settings();
			$this->settings_loaded = true;
		}
	}

	// ── Helper: should this request be intercepted? ───────────────────────────

	private function should_intercept() {
		$this->load_settings();

		if ( empty( $this->settings['enabled'] ) ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return false;
		}
		if ( is_feed() ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		// Allow robots.txt and sitemaps through unconditionally.
		if ( preg_match( '#/(robots\.txt|sitemap[^/]*\.xml)#i', $request_uri ) ) {
			return false;
		}

		// Never intercept the login page or wp-admin — users must be able to log in.
		if ( preg_match( '#/wp-login\.php#i', $request_uri ) ) {
			return false;
		}
		if ( is_admin() ) {
			return false;
		}

		if ( $this->user_can_bypass() ) {
			return false;
		}

		return true;
	}

	// ── Step 1: wp hook ───────────────────────────────────────────────────────

	public function check_intercept() {
		if ( $this->should_intercept() ) {
			$this->will_intercept = true;
		}
	}

	// ── Step 2: wp_enqueue_scripts hook ──────────────────────────────────────

	public function enqueue_assets() {
		if ( ! $this->will_intercept ) {
			return;
		}

		$this->load_settings();

		$mode = sanitize_key( $this->settings['mode'] );
		$slug = ifelsepages_active_template( $mode, $this->settings );

		// Base shared CSS.
		wp_register_style(
			'ifelsepages-base',
			IFELSEPAGES_PLUGIN_URL . 'assets/css/public-base.css',
			array(),
			IFELSEPAGES_VERSION
		);
		wp_enqueue_style( 'ifelsepages-base' );

		// Template-specific CSS.
		$tpl_css_handle = 'ifelsepages-tpl-' . $mode . '-' . $slug;
		$tpl_css_file   = 'assets/css/templates/' . $mode . '-' . $slug . '.css';
		$tpl_css_path   = IFELSEPAGES_PLUGIN_DIR . $tpl_css_file;

		if ( file_exists( $tpl_css_path ) ) {
			wp_register_style(
				$tpl_css_handle,
				IFELSEPAGES_PLUGIN_URL . $tpl_css_file,
				array( 'ifelsepages-base' ),
				IFELSEPAGES_VERSION
			);
			wp_enqueue_style( $tpl_css_handle );
		}

		// Dynamic inline CSS.
		$inline_parent = file_exists( $tpl_css_path ) ? $tpl_css_handle : 'ifelsepages-base';
		wp_add_inline_style( $inline_parent, $this->build_inline_css( $this->settings ) );

		// ── Countdown JS (Coming Soon / Dark template) ────────────────────────
		$registry = ifelsepages_template_registry();
		$tpl_meta = $this->find_template_meta( $registry, $mode, $slug );

		if ( ! empty( $tpl_meta['has_countdown'] ) ) {
			$tpl_s                = isset( $this->settings['template_settings'][ $slug ] ) ? $this->settings['template_settings'][ $slug ] : array();
			$countdown_enabled    = ! empty( $tpl_s['countdown_enable'] );
			$countdown_date       = ! empty( $tpl_s['countdown_date'] ) ? $tpl_s['countdown_date'] : '';
			$countdown_end_action = isset( $tpl_s['countdown_end_action'] ) ? $tpl_s['countdown_end_action'] : 'hide';

			if ( $countdown_enabled && $countdown_date ) {
				wp_register_script(
					'ifelsepages-countdown',
					IFELSEPAGES_PLUGIN_URL . 'assets/js/countdown.js',
					array(),
					IFELSEPAGES_VERSION,
					true
				);
				wp_enqueue_script( 'ifelsepages-countdown' );

				// Convert the datetime-local string to a UTC Unix timestamp using the
				// WordPress-configured timezone, then pass it as milliseconds to JS.
				// strtotime() must not be used here — it interprets the string in the
				// server's PHP timezone which is often UTC regardless of WP settings.
				$countdown_ts_ms = ifelsepages_local_to_timestamp( $countdown_date ) * 1000;

				$inline_data = array(
					'targetMs'  => $countdown_ts_ms,
					'endAction' => $countdown_end_action,
					'labels'    => array(
						'days'    => __( 'Days', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
						'hours'   => __( 'Hours', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
						'minutes' => __( 'Minutes', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
						'seconds' => __( 'Seconds', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
					),
				);

				if ( 'disable' === $countdown_end_action ) {
					$inline_data['ajaxUrl'] = admin_url( 'admin-ajax.php' );
					$inline_data['nonce']   = wp_create_nonce( 'ifelsepages_auto_disable' );
				}

				wp_add_inline_script(
					'ifelsepages-countdown',
					'var ifelsepagesCountdown = ' . wp_json_encode( $inline_data ) . ';',
					'before'
				);
			}
		}

		// ── Warning / Maintenance auto-disable JS ─────────────────────────────
		if ( 'maintenance' === $mode ) {
			$warning_s        = isset( $this->settings['template_settings']['warning'] ) ? $this->settings['template_settings']['warning'] : array();
			$show_retry       = ! empty( $warning_s['show_retry_time'] );
			$retry_end_action = isset( $warning_s['retry_end_action'] ) ? $warning_s['retry_end_action'] : 'keep';
			$end_ts           = isset( $warning_s['retry_end_timestamp'] ) ? (int) $warning_s['retry_end_timestamp'] : 0;

			if ( $show_retry && 'disable' === $retry_end_action && $end_ts > 0 ) {
				$ms_until = max( 0, ( $end_ts - time() ) * 1000 );

				wp_register_script(
					'ifelsepages-auto-disable',
					IFELSEPAGES_PLUGIN_URL . 'assets/js/auto-disable.js',
					array(),
					IFELSEPAGES_VERSION,
					true
				);
				wp_enqueue_script( 'ifelsepages-auto-disable' );

				wp_add_inline_script(
					'ifelsepages-auto-disable',
					'var ifelsepagesAutoDisable = ' . wp_json_encode(
						array(
							'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
							'nonce'    => wp_create_nonce( 'ifelsepages_auto_disable' ),
							'msUntil'  => $ms_until,
						)
					) . ';',
					'before'
				);
			}
		}
	}

	// ── Step 3: template_redirect hook ───────────────────────────────────────

	public function do_intercept() {
		if ( ! $this->will_intercept ) {
			return;
		}

		$this->send_status_header();
		$this->load_template();
		exit;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function user_can_bypass() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$bypass_roles = (array) $this->settings['bypass_roles'];
		$user         = wp_get_current_user();
		foreach ( $bypass_roles as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}
		return false;
	}

	private function send_status_header() {
		if ( 'maintenance' === $this->settings['mode'] ) {
			status_header( 503 );

			// Only send Retry-After when the admin has opted to show a retry time.
			$warning_s  = isset( $this->settings['template_settings']['warning'] )
				? $this->settings['template_settings']['warning']
				: array();
			$show_retry = ! empty( $warning_s['show_retry_time'] );

			if ( $show_retry ) {
				$retry_hours   = ! empty( $warning_s['retry_hours'] ) ? absint( $warning_s['retry_hours'] ) : 1;
				$retry_seconds = max( 1, $retry_hours ) * 3600;
				header( 'Retry-After: ' . $retry_seconds );
			}
		} else {
			status_header( 200 );
		}
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
	}

	private function load_template() {
		$mode = sanitize_key( $this->settings['mode'] );
		$slug = ifelsepages_active_template( $mode, $this->settings );
		$file = ifelsepages_template_path( $mode, $slug );

		if ( ! $file ) {
			wp_die(
				esc_html( $this->settings['title'] ),
				esc_html( $this->settings['title'] ),
				array( 'response' => ( 'maintenance' === $mode ) ? 503 : 200 )
			);
		}

		$ifelsepages_s = $this->settings;
		require $file;
	}

	private function build_inline_css( $ifelsepages_s ) {
		$bg_color = sanitize_hex_color( $ifelsepages_s['bg_color'] );
		if ( ! $bg_color ) {
			$bg_color = '#0d0d0d';
		}
		$css = 'body.ifelsepages-body { background-color: ' . esc_attr( $bg_color ) . '; }';

		if ( ! empty( $ifelsepages_s['bg_image_url'] ) ) {
			$css .= ' body.ifelsepages-body { background-image: url("' . esc_url( $ifelsepages_s['bg_image_url'] ) . '"); background-size: cover; background-position: center; background-attachment: fixed; }';
		}

		return $css;
	}

	private function find_template_meta( $registry, $mode, $slug ) {
		if ( ! isset( $registry[ $mode ] ) ) {
			return null;
		}
		foreach ( $registry[ $mode ] as $tpl ) {
			if ( $tpl['slug'] === $slug ) {
				return $tpl;
			}
		}
		return null;
	}
}
