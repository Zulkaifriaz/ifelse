<?php
/**
 * Template: Landing Page – Contact
 * Branded mini-landing page with contact form.
 * Supports external form plugins (CF7, WPForms, Ninja) via shortcode or built-in form.
 *
 * Variables available: $ifelsepages_s (settings array)
 *
 * @package IfElsePages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ifelsepages_meta_title = ! empty( $ifelsepages_s['meta_title'] ) ? $ifelsepages_s['meta_title'] : $ifelsepages_s['title'];
$ifelsepages_meta_desc  = ! empty( $ifelsepages_s['meta_description'] ) ? $ifelsepages_s['meta_description'] : '';

// Form source settings.
$ifelsepages_form_source    = ! empty( $ifelsepages_s['template_settings']['contact']['form_source'] ) ? $ifelsepages_s['template_settings']['contact']['form_source'] : 'builtin';
$ifelsepages_form_shortcode = ! empty( $ifelsepages_s['template_settings']['contact']['form_shortcode'] ) ? $ifelsepages_s['template_settings']['contact']['form_shortcode'] : '';

// Notification email: use custom setting or fall back to admin email.
$ifelsepages_notify_email = ! empty( $ifelsepages_s['template_settings']['contact']['notify_email'] )
	? $ifelsepages_s['template_settings']['contact']['notify_email']
	: get_option( 'admin_email' );

// Determine if we should use external form.
$ifelsepages_use_external = ( 'shortcode' === $ifelsepages_form_source && ! empty( $ifelsepages_form_shortcode ) );

// ── Built-in form handler (only runs if NOT using external shortcode) ─────────
$ifelsepages_form_message = '';
$ifelsepages_form_success = false;
$ifelsepages_sender_name  = '';
$ifelsepages_sender_email = '';
$ifelsepages_message_text = '';

if (
	! $ifelsepages_use_external &&
	isset( $_POST['ifelsepages_contact'] ) &&
	isset( $_POST['ifelsepages_contact_nonce'] ) &&
	wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ifelsepages_contact_nonce'] ) ), 'ifelsepages_contact_submit' )
) {
	$ifelsepages_sender_name  = isset( $_POST['ifelsepages_name'] )
		? sanitize_text_field( wp_unslash( $_POST['ifelsepages_name'] ) )
		: '';
	$ifelsepages_sender_email = isset( $_POST['ifelsepages_email'] )
		? sanitize_email( wp_unslash( $_POST['ifelsepages_email'] ) )
		: '';
	$ifelsepages_message_text = isset( $_POST['ifelsepages_message'] )
		? sanitize_textarea_field( wp_unslash( $_POST['ifelsepages_message'] ) )
		: '';

	// Honeypot anti-spam: bots fill hidden field, humans leave it blank.
	$ifelsepages_honeypot = isset( $_POST['ifelsepages_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['ifelsepages_hp'] ) ) : '';

	if ( ! empty( $ifelsepages_honeypot ) ) {
		// Silently discard; show success to avoid revealing detection.
		$ifelsepages_form_success = true;
		$ifelsepages_form_message = __( 'Your message has been sent. We will get back to you soon!', 'ifelse-pages-coming-soon-and-maintenance-mode' );
	} elseif ( $ifelsepages_sender_name && is_email( $ifelsepages_sender_email ) && $ifelsepages_message_text ) {
		/**
		 * Action fired when the built-in contact form is submitted.
		 *
		 * @param string $name    Sender name.
		 * @param string $email   Sender email.
		 * @param string $message Message body.
		 */
		do_action( 'ifelsepages_contact_submit', $ifelsepages_sender_name, $ifelsepages_sender_email, $ifelsepages_message_text );

		$ifelsepages_subject = sprintf(
			/* translators: 1: sender name, 2: site name */
			__( 'Contact from %1$s via %2$s', 'ifelse-pages-coming-soon-and-maintenance-mode' ),
			$ifelsepages_sender_name,
			get_bloginfo( 'name' )
		);

		$ifelsepages_body  = $ifelsepages_message_text . "\n\n";
		$ifelsepages_body .= '-- ' . $ifelsepages_sender_name . ' <' . $ifelsepages_sender_email . '>';

		// Strip newlines and null bytes from name to prevent email header injection.
		$ifelsepages_safe_sender_name = preg_replace( '/[\r\n\0]/', '', $ifelsepages_sender_name );

		$ifelsepages_headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $ifelsepages_safe_sender_name . ' <' . $ifelsepages_sender_email . '>',
		);

		wp_mail( $ifelsepages_notify_email, $ifelsepages_subject, $ifelsepages_body, $ifelsepages_headers );

		$ifelsepages_form_success = true;
		$ifelsepages_form_message = __( 'Your message has been sent. We will get back to you soon!', 'ifelse-pages-coming-soon-and-maintenance-mode' );
	} else {
		$ifelsepages_form_message = __( 'Please fill in all fields with a valid email address.', 'ifelse-pages-coming-soon-and-maintenance-mode' );
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $ifelsepages_meta_title ); ?></title>
<?php if ( $ifelsepages_meta_desc ) : ?>
<meta name="description" content="<?php echo esc_attr( $ifelsepages_meta_desc ); ?>">
<?php endif; ?>
<meta name="robots" content="noindex, nofollow">
<?php wp_head(); ?>
</head>
<body class="ifelsepages-body ifelsepages-contact-body">

<div class="ifelsepages-contact-layout">

	<!-- Left branding panel -->
	<div class="ifelsepages-contact-brand">
		<?php if ( ! empty( $ifelsepages_s['logo_url'] ) ) : ?>
			<div class="ifelsepages-logo">
				<img src="<?php echo esc_url( $ifelsepages_s['logo_url'] ); ?>"
					 alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			</div>
		<?php endif; ?>

		<h1 class="ifelsepages-contact-title">
			<?php echo esc_html( $ifelsepages_s['title'] ); ?>
		</h1>

		<?php if ( ! empty( $ifelsepages_s['description'] ) ) : ?>
			<div class="ifelsepages-contact-desc">
				<?php echo wp_kses_post( $ifelsepages_s['description'] ); ?>
			</div>
		<?php endif; ?>
	</div><!-- .ifelsepages-contact-brand -->

	<!-- Right form panel -->
	<div class="ifelsepages-contact-form-panel">

		<h2 class="ifelsepages-contact-form-heading">
			<?php esc_html_e( 'Get in Touch', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
		</h2>

		<?php if ( $ifelsepages_use_external ) : ?>
			<!-- External form via shortcode -->
			<div class="ifelsepages-contact-external-form">
				<?php echo do_shortcode( $ifelsepages_form_shortcode ); ?>
			</div>

		<?php else : ?>
			<!-- Built-in form -->
			<?php if ( $ifelsepages_form_success ) : ?>
				<div class="ifelsepages-contact-success" role="status">
					<?php echo esc_html( $ifelsepages_form_message ); ?>
				</div>
			<?php else : ?>

				<?php if ( $ifelsepages_form_message ) : ?>
					<p class="ifelsepages-contact-error" role="alert"><?php echo esc_html( $ifelsepages_form_message ); ?></p>
				<?php endif; ?>

				<form class="ifelsepages-contact-form" method="post" action="">
					<?php wp_nonce_field( 'ifelsepages_contact_submit', 'ifelsepages_contact_nonce' ); ?>

					<!-- Honeypot anti-spam field (hidden from real users via CSS) -->
					<div class="ifelsepages-hp-field" aria-hidden="true">
						<label for="ifelsepages-hp"><?php esc_html_e( 'Leave this blank', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<input type="text" name="ifelsepages_hp" id="ifelsepages-hp" value="" tabindex="-1" autocomplete="off">
					</div>

					<div class="ifelsepages-contact-field">
						<label for="ifelsepages-contact-name"><?php esc_html_e( 'Name', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<input type="text"
							   id="ifelsepages-contact-name"
							   name="ifelsepages_name"
							   value="<?php echo esc_attr( $ifelsepages_sender_name ); ?>"
							   placeholder="<?php esc_attr_e( 'Your name', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>"
							   required
							   autocomplete="name">
					</div>

					<div class="ifelsepages-contact-field">
						<label for="ifelsepages-contact-email"><?php esc_html_e( 'Email', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<input type="email"
							   id="ifelsepages-contact-email"
							   name="ifelsepages_email"
							   value="<?php echo esc_attr( $ifelsepages_sender_email ); ?>"
							   placeholder="<?php esc_attr_e( 'your@email.com', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>"
							   required
							   autocomplete="email">
					</div>

					<div class="ifelsepages-contact-field">
						<label for="ifelsepages-contact-message"><?php esc_html_e( 'Message', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?></label>
						<textarea id="ifelsepages-contact-message"
								  name="ifelsepages_message"
								  rows="5"
								  placeholder="<?php esc_attr_e( 'How can we help you?', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>"
								  required><?php echo esc_textarea( $ifelsepages_message_text ); ?></textarea>
					</div>

					<button type="submit" name="ifelsepages_contact" value="1" class="ifelsepages-contact-submit">
						<?php esc_html_e( 'Send Message', 'ifelse-pages-coming-soon-and-maintenance-mode' ); ?>
					</button>

				</form>
			<?php endif; ?>
		<?php endif; ?>

	</div><!-- .ifelsepages-contact-form-panel -->

</div><!-- .ifelsepages-contact-layout -->

<?php if ( ! empty( $ifelsepages_s['footer_text'] ) ) : ?>
	<footer class="ifelsepages-contact-footer">
		<?php echo esc_html( $ifelsepages_s['footer_text'] ); ?>
	</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
