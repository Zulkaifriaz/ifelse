<?php
/**
 * Admin settings page for IfElse Pages.
 *
 * @package IfElsePages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IfElsePages_Settings {

	/** @var self|null */
	private static $instance = null;

	private function __construct() {
		add_action( 'admin_menu',                  array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts',       array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_ifelsepages_save', array( $this, 'handle_save' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// ── Menu ─────────────────────────────────────────────────────────────────

	public function register_menu() {
		add_options_page(
			__( 'IfElse Pages', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
			__( 'IfElse Pages', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
			'manage_options',
			IFELSEPAGES_PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	// ── Assets ───────────────────────────────────────────────────────────────

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_' . IFELSEPAGES_PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		wp_register_style(
			'ifelsepages-admin',
			IFELSEPAGES_PLUGIN_URL . 'assets/css/admin.css',
			array( 'wp-color-picker' ),
			IFELSEPAGES_VERSION
		);
		wp_enqueue_style( 'ifelsepages-admin' );

		wp_register_script(
			'ifelsepages-admin',
			IFELSEPAGES_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			IFELSEPAGES_VERSION,
			true
		);
		wp_enqueue_script( 'ifelsepages-admin' );

		$registry      = ifelsepages_template_registry();
		$js_registry   = array();
		$js_thumb_base = IFELSEPAGES_PLUGIN_URL . 'assets/images/thumbnails/';

		foreach ( $registry as $mode_key => $templates ) {
			$js_registry[ $mode_key ] = array();
			foreach ( $templates as $tpl ) {
				$thumb_file = $tpl['slug'] . '.svg';
				$thumb_path = IFELSEPAGES_PLUGIN_DIR . 'assets/images/thumbnails/' . $tpl['slug'] . '.svg';
				$thumb_url  = file_exists( $thumb_path )
					? $js_thumb_base . $thumb_file
					: IFELSEPAGES_PLUGIN_URL . 'assets/images/thumbnails/placeholder.svg';

				$js_registry[ $mode_key ][] = array(
					'slug'        => $tpl['slug'],
					'name'        => $tpl['name'],
					'description' => $tpl['description'],
					'available'   => ! empty( $tpl['available'] ),
					'thumb'       => $thumb_url,
				);
			}
		}

		wp_add_inline_script(
			'ifelsepages-admin',
			'var ifelsepagesAdmin = ' . wp_json_encode(
				array(
					'mediaTitle'     => __( 'Select Image', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
					'mediaButton'    => __( 'Use this image', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
					'removeLabel'    => __( 'Remove', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
					'statusActive'   => __( 'Active – visitors see your page', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
					'statusInactive' => __( 'Inactive – site is publicly visible', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
					'lockedLabel'    => __( 'Coming Soon', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
					'registry'       => $js_registry,
					// Current Unix timestamp (ms) in the site's configured timezone.
					// Used to drive the live site-clock shown next to the countdown date picker.
					'siteTimeMs'     => (int) current_time( 'timestamp' ) * 1000,
					'i18n'           => array(
						'dateRequired'   => __( 'Please set a future date/time for the countdown.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
						'datePast'       => __( 'The countdown date must be in the future.', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
					),
				)
			) . ';',
			'before'
		);
	}

	// ── Save ─────────────────────────────────────────────────────────────────

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorised.', 'ifelse-pages-coming-soon-and-maintenance-mode' ) );
		}

		check_admin_referer( 'ifelsepages_save_settings', 'ifelsepages_nonce' );

		$defaults = ifelsepages_defaults();

		$enabled          = isset( $_POST['enabled'] ) ? 1 : 0;
		$mode             = isset( $_POST['mode'] )             ? sanitize_text_field( wp_unslash( $_POST['mode'] ) )             : 'coming_soon';
		$title            = isset( $_POST['title'] )            ? sanitize_text_field( wp_unslash( $_POST['title'] ) )            : '';
		$description      = isset( $_POST['description'] )      ? wp_kses_post( wp_unslash( $_POST['description'] ) )             : '';
		$footer_text      = isset( $_POST['footer_text'] )      ? sanitize_text_field( wp_unslash( $_POST['footer_text'] ) )      : '';
		$logo_url         = isset( $_POST['logo_url'] )         ? esc_url_raw( wp_unslash( $_POST['logo_url'] ) )                 : '';
		$bg_color         = isset( $_POST['bg_color'] )         ? sanitize_hex_color( wp_unslash( $_POST['bg_color'] ) )          : '#0d0d0d';
		$bg_image_url     = isset( $_POST['bg_image_url'] )     ? esc_url_raw( wp_unslash( $_POST['bg_image_url'] ) )             : '';
		$bypass_roles     = isset( $_POST['bypass_roles'] )     ? array_map( 'sanitize_key', wp_unslash( (array) $_POST['bypass_roles'] ) ) : array();
		$meta_title       = isset( $_POST['meta_title'] )       ? sanitize_text_field( wp_unslash( $_POST['meta_title'] ) )       : '';
		$meta_description = isset( $_POST['meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['meta_description'] ) ) : '';
		$show_admin_bar   = isset( $_POST['show_admin_bar_badge'] ) ? 1 : 0;

		// template_per_mode.
		$raw_tpm           = isset( $_POST['template_per_mode'] ) ? array_map( 'sanitize_key', wp_unslash( (array) $_POST['template_per_mode'] ) ) : array();
		$template_per_mode = array();
		foreach ( array( 'coming_soon', 'maintenance', 'landing' ) as $ifelsepages_m ) {
			$template_per_mode[ $ifelsepages_m ] = isset( $raw_tpm[ $ifelsepages_m ] )
				? sanitize_key( $raw_tpm[ $ifelsepages_m ] )
				: $defaults['template_per_mode'][ $ifelsepages_m ];
		}

		$raw_tpl_settings = array();
		if ( isset( $_POST['template_settings'] ) && is_array( $_POST['template_settings'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_tpl_settings = wp_unslash( $_POST['template_settings'] );
		}

		$template_settings = array();

		// ── Dark template: countdown ──────────────────────────────────────────
		$countdown_enable     = isset( $raw_tpl_settings['dark']['countdown_enable'] ) ? 1 : 0;
		$countdown_date       = isset( $raw_tpl_settings['dark']['countdown_date'] )
			? sanitize_text_field( $raw_tpl_settings['dark']['countdown_date'] )
			: '';
		$countdown_end_action = isset( $raw_tpl_settings['dark']['countdown_end_action'] )
			? ( 'disable' === $raw_tpl_settings['dark']['countdown_end_action'] ? 'disable' : 'hide' )
			: 'hide';

		// Validate countdown only when the plugin is being enabled, countdown is on,
		// mode is coming_soon, AND the dark template is the currently selected one.
		// Saving with a different template or with the plugin disabled skips this check.
		$sanitized_mode         = $this->sanitize_mode( $mode );
		$active_coming_soon_tpl = isset( $template_per_mode['coming_soon'] ) ? $template_per_mode['coming_soon'] : 'dark';
		$save_error             = '';

		if (
			$enabled &&
			$countdown_enable &&
			'coming_soon' === $sanitized_mode &&
			'dark' === $active_coming_soon_tpl
		) {
			if ( empty( $countdown_date ) ) {
				$save_error = __( 'Please set a date/time for the countdown (Dark template).', 'ifelse-pages-coming-soon-and-maintenance-mode' );
			} elseif ( ifelsepages_local_to_timestamp( $countdown_date ) <= time() ) {
				$save_error = __( 'The countdown date must be in the future (Dark template).', 'ifelse-pages-coming-soon-and-maintenance-mode' );
			}
		}

		$template_settings['dark'] = array(
			'countdown_enable'     => $countdown_enable,
			'countdown_date'       => $countdown_date,
			'countdown_end_action' => $countdown_end_action,
		);

		// ── Mystry template ───────────────────────────────────────────────────
		$template_settings['mystry'] = array(
			'button_text'    => isset( $raw_tpl_settings['mystry']['button_text'] )
				? sanitize_text_field( $raw_tpl_settings['mystry']['button_text'] )
				: 'Contact Us',
			'button_url'     => isset( $raw_tpl_settings['mystry']['button_url'] )
				? esc_url_raw( $raw_tpl_settings['mystry']['button_url'] )
				: '',
			'button_new_tab' => isset( $raw_tpl_settings['mystry']['button_new_tab'] ) ? 1 : 0,
		);

		// ── Contact template ──────────────────────────────────────────────────
		$template_settings['contact'] = array(
			'form_source'    => isset( $raw_tpl_settings['contact']['form_source'] )
				? $this->sanitize_form_source( $raw_tpl_settings['contact']['form_source'] )
				: 'builtin',
			'form_shortcode' => isset( $raw_tpl_settings['contact']['form_shortcode'] )
				? $this->sanitize_shortcode( $raw_tpl_settings['contact']['form_shortcode'] )
				: '',
			'notify_email'   => isset( $raw_tpl_settings['contact']['notify_email'] )
				? sanitize_email( $raw_tpl_settings['contact']['notify_email'] )
				: '',
		);

		// ── Warning template: retry / estimated time ──────────────────────────
		$show_retry_time  = isset( $raw_tpl_settings['warning']['show_retry_time'] ) ? 1 : 0;
		$retry_hours      = isset( $raw_tpl_settings['warning']['retry_hours'] )
			? absint( $raw_tpl_settings['warning']['retry_hours'] )
			: 1;
		$retry_end_action = isset( $raw_tpl_settings['warning']['retry_end_action'] )
			? ( 'disable' === $raw_tpl_settings['warning']['retry_end_action'] ? 'disable' : 'keep' )
			: 'keep';

		// Preserve the existing end timestamp unless the action is freshly set to
		// 'disable' (i.e. it was previously something else). Recalculating on every
		// save would reset the auto-disable clock each time the admin opens Settings.
		$existing_settings       = ifelsepages_get_settings();
		$prev_retry_end_action   = isset( $existing_settings['template_settings']['warning']['retry_end_action'] )
			? $existing_settings['template_settings']['warning']['retry_end_action']
			: 'keep';
		$prev_retry_end_ts       = isset( $existing_settings['template_settings']['warning']['retry_end_timestamp'] )
			? (int) $existing_settings['template_settings']['warning']['retry_end_timestamp']
			: 0;

		if ( $show_retry_time && 'disable' === $retry_end_action && $retry_hours > 0 ) {
			// Only (re)calculate the timestamp when the action is newly switched to 'disable'
			// or when no timestamp has been set yet. Re-saving with the same action preserves it.
			if ( 'disable' !== $prev_retry_end_action || 0 === $prev_retry_end_ts ) {
				$retry_end_timestamp = time() + ( $retry_hours * 3600 );
			} else {
				$retry_end_timestamp = $prev_retry_end_ts;
			}
		} else {
			$retry_end_timestamp = 0;
		}

		$template_settings['warning'] = array(
			'show_retry_time'     => $show_retry_time,
			'retry_hours'         => $retry_hours,
			'retry_end_action'    => $retry_end_action,
			'retry_end_timestamp' => $retry_end_timestamp,
		);

		// Enforce administrator always bypass.
		if ( ! in_array( 'administrator', $bypass_roles, true ) ) {
			$bypass_roles[] = 'administrator';
		}

		$settings = array(
			'enabled'              => $enabled,
			'mode'                 => $sanitized_mode,
			'template_per_mode'    => $template_per_mode,
			'template_settings'    => $template_settings,
			'title'                => $title,
			'description'          => $description,
			'footer_text'          => $footer_text,
			'logo_url'             => $logo_url,
			'bg_color'             => $bg_color ?: '#0d0d0d',
			'bg_image_url'         => $bg_image_url,
			'bypass_roles'         => $this->sanitize_roles( $bypass_roles ),
			'meta_title'           => $meta_title,
			'meta_description'     => $meta_description,
			'show_admin_bar_badge' => $show_admin_bar,
		);

		update_option( IFELSEPAGES_OPTION_KEY, $settings );
		ifelsepages_flush_settings_cache();

		// Pass notices via transient to avoid polluting the URL with error text.
		if ( $save_error ) {
			set_transient( 'ifelsepages_save_error_' . get_current_user_id(), $save_error, 60 );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => IFELSEPAGES_PAGE_SLUG,
					'saved' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	// ── Sanitization helpers ─────────────────────────────────────────────────

	private function sanitize_mode( $value ) {
		$allowed = array( 'coming_soon', 'maintenance', 'landing' );
		return in_array( $value, $allowed, true ) ? $value : 'coming_soon';
	}

	private function sanitize_roles( $roles ) {
		if ( ! is_array( $roles ) ) {
			return array( 'administrator' );
		}
		$all_roles = array_keys( wp_roles()->roles );
		return array_values( array_intersect( $roles, $all_roles ) );
	}

	private function sanitize_form_source( $value ) {
		$allowed = array( 'builtin', 'shortcode', 'custom' );
		return in_array( $value, $allowed, true ) ? $value : 'builtin';
	}

	private function sanitize_shortcode( $value ) {
		// sanitize_text_field preserves shortcode syntax (square brackets, quotes)
		// while stripping tags and excess whitespace. wp_kses( $value, array() )
		// was incorrectly stripping the brackets that shortcodes rely on.
		return sanitize_text_field( $value );
	}

	// ── Admin page HTML ──────────────────────────────────────────────────────

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$s        = ifelsepages_get_settings();
		$registry = ifelsepages_template_registry();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$saved      = isset( $_GET['saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved'] ) );
		$save_error = (string) get_transient( 'ifelsepages_save_error_' . get_current_user_id() );
		if ( $save_error ) {
			delete_transient( 'ifelsepages_save_error_' . get_current_user_id() );
		}
		?>
		<div class="wrap ifelsepages-wrap">

			<!-- Header -->
			<div class="ifelsepages-header">
				<div class="ifelsepages-header-inner">
					<h1><?php esc_html_e( 'IfElse Pages', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></h1>
					<span class="ifelsepages-version">v<?php echo esc_html( IFELSEPAGES_VERSION ); ?></span>
				</div>
				<p class="ifelsepages-tagline"><?php esc_html_e( 'Coming Soon · Maintenance · Landing Page', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
			</div>

			<?php if ( $saved && ! $save_error ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $save_error ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $save_error ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ifelsepages-form">
				<?php wp_nonce_field( 'ifelsepages_save_settings', 'ifelsepages_nonce' ); ?>
				<input type="hidden" name="action" value="ifelsepages_save">

				<!-- ══ Master Toggle ══ -->
				<div class="ifelsepages-toggle-row">
					<label class="ifelsepages-toggle-label" for="ifelsepages-enabled">
						<?php esc_html_e( 'Enable Plugin', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
					</label>
					<label class="ifelsepages-switch">
						<input type="checkbox" name="enabled" id="ifelsepages-enabled" value="1"
							   <?php checked( 1, $s['enabled'] ); ?>>
						<span class="ifelsepages-slider"></span>
					</label>
					<span class="ifelsepages-status-text">
						<?php
						if ( $s['enabled'] ) {
							esc_html_e( 'Active – visitors see your page', 'ifelse-pages-coming-soon-and-maintenance-mode' );
						} else {
							esc_html_e( 'Inactive – site is publicly visible', 'ifelse-pages-coming-soon-and-maintenance-mode' );
						}
						?>
					</span>
				</div>

				<!-- ══ Mode Selector ══ -->
				<div class="ifelsepages-mode-row">
					<label class="ifelsepages-section-label"><?php esc_html_e( 'Page Mode', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
					<div class="ifelsepages-mode-tabs" role="tablist">
						<?php
						$modes = array(
							'coming_soon' => __( 'Coming Soon', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
							'maintenance' => __( 'Maintenance', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
							'landing'     => __( 'Landing Page', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
						);
						foreach ( $modes as $ifelsepages_mode_key => $ifelsepages_mode_label ) :
							$ifelsepages_active_cls = ( $s['mode'] === $ifelsepages_mode_key ) ? 'active' : '';
						?>
							<button type="button"
									class="ifelsepages-mode-tab <?php echo esc_attr( $ifelsepages_active_cls ); ?>"
									data-mode="<?php echo esc_attr( $ifelsepages_mode_key ); ?>"
									role="tab"
									aria-selected="<?php echo $s['mode'] === $ifelsepages_mode_key ? 'true' : 'false'; ?>">
								<?php echo esc_html( $ifelsepages_mode_label ); ?>
							</button>
						<?php endforeach; ?>
					</div>
					<input type="hidden" name="mode" id="ifelsepages-mode-input" value="<?php echo esc_attr( $s['mode'] ); ?>">
				</div>

				<!-- ══ Template Picker ══ -->
				<div class="ifelsepages-section">
					<h2 class="ifelsepages-section-label"><?php esc_html_e( 'Choose Template', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></h2>
					<p class="ifelsepages-section-desc"><?php esc_html_e( 'Each mode remembers its own template selection.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>

					<?php foreach ( $registry as $ifelsepages_mode_key => $ifelsepages_templates ) : ?>
						<div class="ifelsepages-tpl-grid"
							 id="ifelsepages-tpl-grid-<?php echo esc_attr( $ifelsepages_mode_key ); ?>"
							 data-mode="<?php echo esc_attr( $ifelsepages_mode_key ); ?>"
							 <?php echo $s['mode'] !== $ifelsepages_mode_key ? 'style="display:none"' : ''; ?>>

							<?php foreach ( $ifelsepages_templates as $ifelsepages_tpl ) :
								$ifelsepages_active_tpl = ifelsepages_active_template( $ifelsepages_mode_key, $s );
								$ifelsepages_is_active  = ( $ifelsepages_active_tpl === $ifelsepages_tpl['slug'] );
								$ifelsepages_is_locked  = empty( $ifelsepages_tpl['available'] );

								$ifelsepages_thumb_path = IFELSEPAGES_PLUGIN_DIR . 'assets/images/thumbnails/' . $ifelsepages_tpl['slug'] . '.svg';
								$ifelsepages_thumb_url  = file_exists( $ifelsepages_thumb_path )
									? IFELSEPAGES_PLUGIN_URL . 'assets/images/thumbnails/' . $ifelsepages_tpl['slug'] . '.svg'
									: IFELSEPAGES_PLUGIN_URL . 'assets/images/thumbnails/placeholder.svg';
							?>
								<div class="ifelsepages-tpl-card <?php echo $ifelsepages_is_active ? 'is-active' : ''; ?> <?php echo $ifelsepages_is_locked ? 'is-locked' : ''; ?>"
									 data-mode="<?php echo esc_attr( $ifelsepages_mode_key ); ?>"
									 data-slug="<?php echo esc_attr( $ifelsepages_tpl['slug'] ); ?>">

									<?php if ( $ifelsepages_is_locked ) : ?>
										<span class="ifelsepages-locked-badge"><?php esc_html_e( 'Coming Soon', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></span>
									<?php endif; ?>

									<?php if ( $ifelsepages_is_active && ! $ifelsepages_is_locked ) : ?>
										<span class="ifelsepages-active-badge">&#10003;</span>
									<?php endif; ?>

									<div class="ifelsepages-tpl-thumb">
										<img src="<?php echo esc_url( $ifelsepages_thumb_url ); ?>"
											 alt="<?php echo esc_attr( $ifelsepages_tpl['name'] ); ?>">
									</div>
									<div class="ifelsepages-tpl-info">
										<strong><?php echo esc_html( $ifelsepages_tpl['name'] ); ?></strong>
										<span><?php echo esc_html( $ifelsepages_tpl['description'] ); ?></span>
									</div>
								</div>

							<?php endforeach; ?>
						</div><!-- .ifelsepages-tpl-grid -->

						<input type="hidden"
							   name="template_per_mode[<?php echo esc_attr( $ifelsepages_mode_key ); ?>]"
							   id="ifelsepages-tpl-input-<?php echo esc_attr( $ifelsepages_mode_key ); ?>"
							   value="<?php echo esc_attr( ifelsepages_active_template( $ifelsepages_mode_key, $s ) ); ?>">

					<?php endforeach; ?>
				</div><!-- .ifelsepages-section (template picker) -->

				<!-- ══ Template-Specific Settings ══ -->
				<div class="ifelsepages-section ifelsepages-template-options">
					<h2 class="ifelsepages-section-label"><?php esc_html_e( 'Template Options', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></h2>
					<p class="ifelsepages-section-desc"><?php esc_html_e( 'Options for the currently selected template.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>

					<!-- Dark template: Countdown -->
					<div class="ifelsepages-tpl-option-group" data-template="dark" style="display:none">
						<h3 class="ifelsepages-tpl-option-title"><?php esc_html_e( 'Dark Template', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></h3>

						<div class="ifelsepages-field">
							<label class="ifelsepages-inline-label" for="ifelsepages-dark-countdown">
								<input type="checkbox"
									   name="template_settings[dark][countdown_enable]"
									   id="ifelsepages-dark-countdown"
									   value="1"
									   <?php checked( 1, $s['template_settings']['dark']['countdown_enable'] ); ?>>
								<?php esc_html_e( 'Enable Countdown Timer', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
							</label>
						</div>

						<!-- Countdown sub-options (visible when countdown is enabled) -->
						<div class="ifelsepages-dark-countdown-options" <?php echo $s['template_settings']['dark']['countdown_enable'] ? '' : 'style="display:none"'; ?>>

							<div class="ifelsepages-field">
								<label for="ifelsepages-dark-countdown-date"><?php esc_html_e( 'Launch / End Date &amp; Time', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
								<input type="datetime-local"
									   name="template_settings[dark][countdown_date]"
									   id="ifelsepages-dark-countdown-date"
									   value="<?php echo esc_attr( $s['template_settings']['dark']['countdown_date'] ); ?>"
									   >
								<p class="description"><?php esc_html_e( 'Required when plugin is active. Must be a future date/time.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>

								<!-- Site clock: shows WP-configured current time so admin can pick the right value -->
								<div class="ifelsepages-site-clock">
									<span class="ifelsepages-site-clock-icon" aria-hidden="true">🕐</span>
									<?php esc_html_e( 'Current time on this site:', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
									<strong id="ifelsepages-site-clock-time"></strong>
									&nbsp;&middot;&nbsp;
									<?php
									$ifelsepages_tz_string  = get_option( 'timezone_string' );
									$ifelsepages_gmt_offset = get_option( 'gmt_offset' );
									if ( $ifelsepages_tz_string ) {
										$ifelsepages_tz_label = $ifelsepages_tz_string;
									} elseif ( 0 !== (float) $ifelsepages_gmt_offset ) {
										$ifelsepages_tz_label = sprintf(
											'UTC%+g',
											(float) $ifelsepages_gmt_offset
										);
									} else {
										$ifelsepages_tz_label = 'UTC';
									}
									?>
									<?php
									printf(
										/* translators: %s: timezone identifier e.g. "UTC+5" or "America/New_York" */
										esc_html__( 'Timezone: %s', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
										'<strong>' . esc_html( $ifelsepages_tz_label ) . '</strong>'
									);
									?>
									&nbsp;&middot;&nbsp;
									<a href="<?php echo esc_url( admin_url( 'options-general.php#timezone_string' ) ); ?>">
										<?php esc_html_e( 'Change in Settings →', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
									</a>
								</div>
							</div>

							<div class="ifelsepages-field">
								<label><?php esc_html_e( 'When the date/time is reached:', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
								<div class="ifelsepages-radio-group">
									<label class="ifelsepages-radio-label">
										<input type="radio"
											   name="template_settings[dark][countdown_end_action]"
											   value="hide"
											   <?php checked( 'hide', $s['template_settings']['dark']['countdown_end_action'] ); ?>>
										<?php esc_html_e( 'Hide the countdown', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
									</label>
									<label class="ifelsepages-radio-label">
										<input type="radio"
											   name="template_settings[dark][countdown_end_action]"
											   value="disable"
											   <?php checked( 'disable', $s['template_settings']['dark']['countdown_end_action'] ); ?>>
										<?php esc_html_e( 'Turn off the Coming Soon mode automatically', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
									</label>
								</div>
							</div>

						</div><!-- .ifelsepages-dark-countdown-options -->
					</div><!-- [data-template="dark"] -->

					<!-- Mystry template: Button -->
					<div class="ifelsepages-tpl-option-group" data-template="mystry" style="display:none">
						<h3 class="ifelsepages-tpl-option-title"><?php esc_html_e( 'Mystry Template', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></h3>
						<div class="ifelsepages-field">
							<label for="ifelsepages-mystry-button-text"><?php esc_html_e( 'Button Text', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
							<input type="text"
								   name="template_settings[mystry][button_text]"
								   id="ifelsepages-mystry-button-text"
								   value="<?php echo esc_attr( $s['template_settings']['mystry']['button_text'] ); ?>"
								   placeholder="<?php esc_attr_e( 'Contact Us', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>">
						</div>
						<div class="ifelsepages-field">
							<label for="ifelsepages-mystry-button-url"><?php esc_html_e( 'Button URL', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
							<input type="url"
								   name="template_settings[mystry][button_url]"
								   id="ifelsepages-mystry-button-url"
								   value="<?php echo esc_attr( $s['template_settings']['mystry']['button_url'] ); ?>"
								   placeholder="<?php esc_attr_e( 'https://example.com/contact', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>">
							<p class="description"><?php esc_html_e( 'Button will be hidden if URL is empty.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
						</div>
						<div class="ifelsepages-field">
							<label class="ifelsepages-inline-label" for="ifelsepages-mystry-new-tab">
								<input type="checkbox"
									   name="template_settings[mystry][button_new_tab]"
									   id="ifelsepages-mystry-new-tab"
									   value="1"
									   <?php checked( 1, $s['template_settings']['mystry']['button_new_tab'] ); ?>>
								<?php esc_html_e( 'Open in New Tab', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
							</label>
						</div>
					</div>

					<!-- Warning template: Estimated Return Time -->
					<div class="ifelsepages-tpl-option-group" data-template="warning" style="display:none">
						<h3 class="ifelsepages-tpl-option-title"><?php esc_html_e( 'Warning Template', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></h3>

						<div class="ifelsepages-field">
							<label class="ifelsepages-inline-label" for="ifelsepages-warning-retry">
								<input type="checkbox"
									   name="template_settings[warning][show_retry_time]"
									   id="ifelsepages-warning-retry"
									   value="1"
									   <?php checked( 1, $s['template_settings']['warning']['show_retry_time'] ); ?>>
								<?php esc_html_e( 'Show estimated return time', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
							</label>
						</div>

						<!-- Retry sub-options (visible when show_retry_time is checked) -->
						<div class="ifelsepages-warning-retry-options" <?php echo $s['template_settings']['warning']['show_retry_time'] ? '' : 'style="display:none"'; ?>>

							<div class="ifelsepages-field">
								<label for="ifelsepages-warning-retry-hours"><?php esc_html_e( 'Estimated Hours Until Back', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
								<input type="number"
									   name="template_settings[warning][retry_hours]"
									   id="ifelsepages-warning-retry-hours"
									   value="<?php echo esc_attr( $s['template_settings']['warning']['retry_hours'] ); ?>"
									   min="1" max="168" step="1">
							</div>

							<div class="ifelsepages-field">
								<label><?php esc_html_e( 'When the estimated time ends:', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
								<div class="ifelsepages-radio-group">
									<label class="ifelsepages-radio-label">
										<input type="radio"
											   name="template_settings[warning][retry_end_action]"
											   value="keep"
											   <?php checked( 'keep', $s['template_settings']['warning']['retry_end_action'] ); ?>>
										<?php esc_html_e( 'Keep the time as is', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
									</label>
									<label class="ifelsepages-radio-label">
										<input type="radio"
											   name="template_settings[warning][retry_end_action]"
											   value="disable"
											   <?php checked( 'disable', $s['template_settings']['warning']['retry_end_action'] ); ?>>
										<?php esc_html_e( 'Automatically turn off the maintenance mode after selected time ends', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
									</label>
								</div>
								<?php if ( ! empty( $s['template_settings']['warning']['retry_end_timestamp'] ) && 'disable' === $s['template_settings']['warning']['retry_end_action'] ) :
									$ts = (int) $s['template_settings']['warning']['retry_end_timestamp'];
								?>
									<p class="description">
										<?php
										printf(
											/* translators: %s: human-readable date/time */
											esc_html__( 'Scheduled to auto-disable at: %s', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
											esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) )
										);
										?>
									</p>
								<?php endif; ?>
							</div>

						</div><!-- .ifelsepages-warning-retry-options -->
					</div><!-- [data-template="warning"] -->

					<!-- Contact template: Form Source -->
					<div class="ifelsepages-tpl-option-group" data-template="contact" style="display:none">
						<h3 class="ifelsepages-tpl-option-title"><?php esc_html_e( 'Contact Template', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></h3>
						<div class="ifelsepages-field">
							<label><?php esc_html_e( 'Contact Form Source', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
							<div class="ifelsepages-form-source-options">
								<label class="ifelsepages-radio-label">
									<input type="radio"
										   name="template_settings[contact][form_source]"
										   value="builtin"
										   <?php checked( 'builtin', $s['template_settings']['contact']['form_source'] ); ?>>
									<?php esc_html_e( 'Built-in Form', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
								</label>
								<label class="ifelsepages-radio-label">
									<input type="radio"
										   name="template_settings[contact][form_source]"
										   value="shortcode"
										   <?php checked( 'shortcode', $s['template_settings']['contact']['form_source'] ); ?>>
									<?php esc_html_e( 'Shortcode', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
								</label>
								<label class="ifelsepages-radio-label ifelsepages-radio-disabled">
									<input type="radio"
										   name="template_settings[contact][form_source]"
										   value="custom"
										   disabled>
									<?php esc_html_e( 'Custom Form (Coming in Next Update)', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
								</label>
							</div>
							<p class="description"><?php esc_html_e( 'Shortcode: Accepts any shortcode from Contact Form 7, WPForms, Ninja Forms, or other form plugins.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
						</div>
						<div class="ifelsepages-field ifelsepages-shortcode-field" <?php echo 'shortcode' === $s['template_settings']['contact']['form_source'] ? '' : 'style="display:none"'; ?>>
							<label for="ifelsepages-contact-shortcode"><?php esc_html_e( 'Form Shortcode', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
							<input type="text"
								   name="template_settings[contact][form_shortcode]"
								   id="ifelsepages-contact-shortcode"
								   value="<?php echo esc_attr( $s['template_settings']['contact']['form_shortcode'] ); ?>"
								   placeholder="<?php esc_attr_e( '[contact-form-7 id="123"]', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>">
							<p class="description"><?php esc_html_e( 'Paste the shortcode from your form plugin. Falls back to built-in form if empty.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
						</div>
						<div class="ifelsepages-field">
							<label for="ifelsepages-contact-notify-email"><?php esc_html_e( 'Notification Email', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
							<input type="email"
								   name="template_settings[contact][notify_email]"
								   id="ifelsepages-contact-notify-email"
								   value="<?php echo esc_attr( $s['template_settings']['contact']['notify_email'] ); ?>"
								   placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Leave blank to use the site admin email. Built-in form submissions will be sent here.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
						</div>
					</div>

				</div><!-- .ifelsepages-section (template options) -->

				<!-- ══ Tabs (Content / Design / Settings) ══ -->
				<div class="ifelsepages-tabs" role="tablist">
					<button type="button" class="ifelsepages-tab active" data-tab="content" role="tab" aria-selected="true">
						<?php esc_html_e( 'Content', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
					</button>
					<button type="button" class="ifelsepages-tab" data-tab="design" role="tab" aria-selected="false">
						<?php esc_html_e( 'Design', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
					</button>
					<button type="button" class="ifelsepages-tab" data-tab="advsettings" role="tab" aria-selected="false">
						<?php esc_html_e( 'Settings', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
					</button>
				</div>

				<!-- Tab: Content -->
				<div class="ifelsepages-tab-panel active" id="ifelsepages-tab-content" role="tabpanel">

					<div class="ifelsepages-field">
						<label for="ifelsepages-title"><?php esc_html_e( 'Title', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<input type="text" name="title" id="ifelsepages-title"
							   value="<?php echo esc_attr( $s['title'] ); ?>">
					</div>

					<div class="ifelsepages-field">
						<label for="ifelsepages-description"><?php esc_html_e( 'Description', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<textarea name="description" id="ifelsepages-description" rows="4"><?php echo wp_kses_post( $s['description'] ); ?></textarea>
					</div>

					<div class="ifelsepages-field">
						<label for="ifelsepages-footer"><?php esc_html_e( 'Footer Text', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<input type="text" name="footer_text" id="ifelsepages-footer"
							   value="<?php echo esc_attr( $s['footer_text'] ); ?>"
							   placeholder="<?php esc_attr_e( '© 2025 My Company', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>">
					</div>

					<div class="ifelsepages-field">
						<label><?php esc_html_e( 'Logo', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<div class="ifelsepages-media-row">
							<?php if ( $s['logo_url'] ) : ?>
								<img src="<?php echo esc_url( $s['logo_url'] ); ?>" class="ifelsepages-preview-img" alt="">
							<?php endif; ?>
							<input type="hidden" name="logo_url" id="ifelsepages-logo-url"
								   value="<?php echo esc_attr( $s['logo_url'] ); ?>">
							<button type="button" class="button ifelsepages-upload-btn" data-target="ifelsepages-logo-url">
								<?php esc_html_e( 'Upload / Select Logo', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
							</button>
							<?php if ( $s['logo_url'] ) : ?>
								<button type="button" class="button ifelsepages-remove-btn" data-target="ifelsepages-logo-url">
									<?php esc_html_e( 'Remove', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>

				</div><!-- /Tab Content -->

				<!-- Tab: Design -->
				<div class="ifelsepages-tab-panel" id="ifelsepages-tab-design" role="tabpanel">

					<div class="ifelsepages-field">
						<label for="ifelsepages-bg-color"><?php esc_html_e( 'Background Colour', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<input type="text" name="bg_color" id="ifelsepages-bg-color"
							   value="<?php echo esc_attr( $s['bg_color'] ); ?>"
							   class="ifelsepages-color-picker">
					</div>

					<div class="ifelsepages-field">
						<label><?php esc_html_e( 'Background Image', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<p class="description"><?php esc_html_e( 'Overrides the background colour when set.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
						<div class="ifelsepages-media-row">
							<?php if ( $s['bg_image_url'] ) : ?>
								<img src="<?php echo esc_url( $s['bg_image_url'] ); ?>" class="ifelsepages-preview-img" alt="">
							<?php endif; ?>
							<input type="hidden" name="bg_image_url" id="ifelsepages-bg-url"
								   value="<?php echo esc_attr( $s['bg_image_url'] ); ?>">
							<button type="button" class="button ifelsepages-upload-btn" data-target="ifelsepages-bg-url">
								<?php esc_html_e( 'Upload / Select Image', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
							</button>
							<?php if ( $s['bg_image_url'] ) : ?>
								<button type="button" class="button ifelsepages-remove-btn" data-target="ifelsepages-bg-url">
									<?php esc_html_e( 'Remove', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>

				</div><!-- /Tab Design -->

				<!-- Tab: Settings -->
				<div class="ifelsepages-tab-panel" id="ifelsepages-tab-advsettings" role="tabpanel">

					<div class="ifelsepages-field">
						<label><?php esc_html_e( 'Bypass Roles', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<p class="description"><?php esc_html_e( 'Users with these roles will always see the real site. Administrator is always bypassed.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
						<div class="ifelsepages-roles-grid">
							<?php foreach ( wp_roles()->roles as $ifelsepages_role_key => $ifelsepages_role_data ) : ?>
								<label class="ifelsepages-checkbox-label">
									<input type="checkbox"
										   name="bypass_roles[]"
										   value="<?php echo esc_attr( $ifelsepages_role_key ); ?>"
										   <?php checked( in_array( $ifelsepages_role_key, (array) $s['bypass_roles'], true ) ); ?>
										   <?php echo 'administrator' === $ifelsepages_role_key ? 'disabled checked' : ''; ?>>
									<?php echo esc_html( $ifelsepages_role_data['name'] ); ?>
									<?php if ( 'administrator' === $ifelsepages_role_key ) : ?>
										<em>(<?php esc_html_e( 'always', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>)</em>
									<?php endif; ?>
								</label>
								<?php if ( 'administrator' === $ifelsepages_role_key ) : ?>
									<input type="hidden" name="bypass_roles[]" value="administrator">
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="ifelsepages-field">
						<label for="ifelsepages-meta-title"><?php esc_html_e( 'Meta Title', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<input type="text" name="meta_title" id="ifelsepages-meta-title"
							   value="<?php echo esc_attr( $s['meta_title'] ); ?>">
						<p class="description"><?php esc_html_e( 'Defaults to the Title field above if left blank.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
					</div>

					<div class="ifelsepages-field">
						<label for="ifelsepages-meta-desc"><?php esc_html_e( 'Meta Description', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<textarea name="meta_description" id="ifelsepages-meta-desc" rows="3"><?php echo esc_textarea( $s['meta_description'] ); ?></textarea>
					</div>

					<div class="ifelsepages-field">
						<label class="ifelsepages-inline-label" for="ifelsepages-admin-bar-badge">
							<input type="checkbox"
								   name="show_admin_bar_badge"
								   id="ifelsepages-admin-bar-badge"
								   value="1"
								   <?php checked( 1, $s['show_admin_bar_badge'] ); ?>>
							<?php esc_html_e( 'Show active-mode badge in admin bar (admins only)', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'A highlighted notice appears in the WordPress admin bar when the plugin is active, reminding you the page is live.', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></p>
					</div>

				</div><!-- /Tab Settings -->

				<!-- Save -->
				<div class="ifelsepages-save-row">
					<?php submit_button( __( 'Save Settings', 'ifelse-pages-coming-soon-and-maintenance-mode' ), 'primary', 'submit', false ); ?>
				</div>

			</form>
		</div><!-- .ifelsepages-wrap -->
		<?php
	}
}
